<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alat;
use App\Models\HasilBulanan;
use App\Models\LogHarian;
use App\Models\LaporanPerbaikan;
use App\Models\Threshold;
use App\Models\Notifikasi;
use Carbon\Carbon;

class TeknisiController extends Controller
{
    public function getAlat(Request $request)
    {
        $pengguna = $request->user();

        $alat = Alat::with(['lokasi.bandara', 'kategori'])
            ->where('id_lokasi', $pengguna->id_lokasi)
            ->where('status', 'Aktif')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $alat
        ]);
    }

    public function inputLog(Request $request)
    {
        $request->validate([
            'id_alat'       => 'required|exists:alat,id_alat',
            'tanggal'       => 'required|date|before_or_equal:today',
            'kondisi'       => 'required|in:Normal,Gangguan,Rusak',
            'catatan'       => 'nullable|string|max:500',
            'detail_lokasi' => 'nullable|string|max:255',
        ]);

        $pengguna = $request->user();

        $alat = Alat::with(['lokasi.bandara'])
            ->where('id_lokasi', $pengguna->id_lokasi)
            ->where('id_alat', $request->id_alat)
            ->where('status', 'Aktif')
            ->first();

        if (!$alat) {
            return response()->json([
                'success' => false,
                'message' => 'Alat tidak ditemukan atau tidak dalam lokasi Anda'
            ], 403);
        }

        // Jam operasional dari bandara
        $jamOperasional = $alat->lokasi->bandara->jam_operasional ?? 24;

        // Hitung total jam terputus dari laporan perbaikan hari ini yang sudah selesai
        $jamTerputus = LaporanPerbaikan::where('id_alat', $request->id_alat)
            ->whereDate('tanggal_kerusakan', $request->tanggal)
            ->where('status', 'Selesai')
            ->get()
            ->sum(function ($lap) {
                if ($lap->tanggal_selesai) {
                    return Carbon::parse($lap->tanggal_kerusakan)
                        ->diffInMinutes(Carbon::parse($lap->tanggal_selesai)) / 60;
                }
                return 0;
            });

        $log = LogHarian::create([
            'id_alat'         => $request->id_alat,
            'id_pengguna'     => $pengguna->id_pengguna,
            'tanggal'         => $request->tanggal,
            'jam_operasional' => $jamOperasional,
            'jam_terputus'    => round($jamTerputus, 2),
            'kondisi'         => $request->kondisi,
            'catatan'         => $request->catatan,
            'detail_lokasi'   => $request->detail_lokasi,
        ]);

        // Auto-buat laporan perbaikan kalau kondisi Gangguan/Rusak
        $laporan = null;
        if (in_array($request->kondisi, ['Gangguan', 'Rusak'])) {
            $laporan = LaporanPerbaikan::create([
                'id_alat'            => $request->id_alat,
                'id_pengguna'        => $pengguna->id_pengguna,
                'nama_peralatan'     => $alat->nama_alat,
                'kategori_kerusakan' => $request->kondisi === 'Rusak' ? 'III' : 'II',
                'bagian_kerusakan'   => $request->catatan ?? '-',
                'tindakan'           => '-',
                'tanggal_kerusakan'  => Carbon::now(),
                'jam_terputus'       => 0,
                'detail_lokasi'      => $request->detail_lokasi,
                'keterangan'         => null,
                'status'             => 'Proses',
            ]);
        }

        // Hitung performa & cek threshold
        $performa  = $jamOperasional > 0
            ? (($jamOperasional - $jamTerputus) / $jamOperasional) * 100
            : 0;
        $performa  = max(0, round($performa, 2));
        $threshold = Threshold::first();

        // ✅ Ganti Notifikasi::create biasa dengan static factory buatPerformaRendah
        if ($threshold && $performa < $threshold->nilai_warning) {
            Notifikasi::buatPerformaRendah($alat, $performa);
        }

        $this->generateHasilBulanan($request->id_alat, $request->tanggal);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'data'    => [
                'log'           => $log->load('alat'),
                'performa'      => $performa . '%',
                'laporan'       => $laporan ? [
                    'id_laporan'     => $laporan->id_laporan,
                    'nama_peralatan' => $laporan->nama_peralatan,
                    'kategori'       => $laporan->kategori_kerusakan,
                    'status'         => $laporan->status,
                ] : null,
                'perlu_laporan' => $laporan !== null,
            ]
        ]);
    }

    public function updateLog(Request $request, $id_log)
    {
        $request->validate([
            'kondisi'       => 'required|in:Normal,Gangguan,Rusak',
            'catatan'       => 'nullable|string|max:500',
            'detail_lokasi' => 'nullable|string|max:255',
        ]);

        $pengguna = $request->user();

        $log = LogHarian::whereHas('alat', function ($query) use ($pengguna) {
                $query->where('id_lokasi', $pengguna->id_lokasi);
            })
            ->where('id_log', $id_log)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log tidak ditemukan'
            ], 404);
        }

        $log->update([
            'kondisi'       => $request->kondisi,
            'catatan'       => $request->catatan,
            'detail_lokasi' => $request->detail_lokasi,
        ]);

        $this->generateHasilBulanan($log->id_alat, $log->tanggal);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diupdate',
            'data'    => $log->load('alat')
        ]);
    }

    private function generateHasilBulanan($id_alat, $tanggal)
    {
        $bulan = Carbon::parse($tanggal)->month;
        $tahun = Carbon::parse($tanggal)->year;

        $logs = LogHarian::where('id_alat', $id_alat)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        if ($logs->isEmpty()) return;

        $rataPerforma        = $logs->avg('performa');
        $totalJamOperasional = $logs->sum('jam_operasional');
        $totalJamTerputus    = $logs->sum('jam_terputus');

        $threshold = Threshold::first();
        $status    = 'Baik';

        if ($threshold) {
            if ($rataPerforma < $threshold->nilai_buruk) {
                $status = 'Buruk';
            } elseif ($rataPerforma < $threshold->nilai_baik) {
                $status = 'Warning';
            }
        }

        HasilBulanan::updateOrCreate(
            [
                'id_alat' => $id_alat,
                'bulan'   => $bulan,
                'tahun'   => $tahun,
            ],
            [
                'rata_performa'         => round($rataPerforma, 2),
                'total_jam_operasional' => $totalJamOperasional,
                'total_jam_terputus'    => $totalJamTerputus,
                'status'                => $status,
            ]
        );
    }

    public function getHistory(Request $request, $id_alat)
    {
        $request->validate([
            'bulan'    => 'nullable|integer|min:1|max:12',
            'tahun'    => 'nullable|integer|min:2000|max:2100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $pengguna = $request->user();

        $alat = Alat::with(['lokasi.bandara'])
            ->where('id_lokasi', $pengguna->id_lokasi)
            ->where('id_alat', $id_alat)
            ->first();

        if (!$alat) {
            return response()->json([
                'success' => false,
                'message' => 'Alat tidak ditemukan atau tidak dalam lokasi Anda'
            ], 403);
        }

        $logs = LogHarian::where('id_alat', $id_alat)
            ->when($request->bulan, fn($q) => $q->whereMonth('tanggal', $request->bulan))
            ->when($request->tahun,  fn($q) => $q->whereYear('tanggal',  $request->tahun))
            ->orderBy('tanggal', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'alat'    => [
                'id_alat'   => $alat->id_alat,
                'nama_alat' => $alat->nama_alat,
                'lokasi'    => $alat->lokasi->nama_lokasi ?? null,
            ],
            'data' => $logs
        ]);
    }

    public function getDetailLog(Request $request, $id_log)
    {
        $pengguna = $request->user();

        $log = LogHarian::with('alat.lokasi')
            ->whereHas('alat', function ($query) use ($pengguna) {
                $query->where('id_lokasi', $pengguna->id_lokasi);
            })
            ->where('id_log', $id_log)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $log
        ]);
    }

    public function getNotifikasi(Request $request)
    {
        $pengguna = $request->user();

        $notifikasi = Notifikasi::whereHas('alat', function ($query) use ($pengguna) {
                $query->where('id_lokasi', $pengguna->id_lokasi);
            })
            ->orderBy('tanggal', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $notifikasi
        ]);
    }

    public function bacaNotifikasi(Request $request, $id_notifikasi)
    {
        $pengguna = $request->user();

        $notifikasi = Notifikasi::whereHas('alat', function ($query) use ($pengguna) {
                $query->where('id_lokasi', $pengguna->id_lokasi);
            })
            ->where('id_notifikasi', $id_notifikasi)
            ->first();

        if (!$notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        $notifikasi->update(['status' => 'Dibaca']);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sebagai dibaca'
        ]);
    }

    public function scanBarcode(Request $request, $barcode)
    {
        $pengguna = $request->user();

        $alat = Alat::with(['lokasi.bandara', 'kategori'])
            ->where('barcode', $barcode)
            ->where('id_lokasi', $pengguna->id_lokasi)
            ->where('status', 'Aktif')
            ->first();

        if (!$alat) {
            return response()->json([
                'success' => false,
                'message' => 'Alat tidak ditemukan atau tidak dalam lokasi Anda'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Alat ditemukan',
            'data'    => [
                'id_alat'         => $alat->id_alat,
                'kode_alat'       => $alat->kode_alat,
                'nama_alat'       => $alat->nama_alat,
                'merek'           => $alat->merek,
                'kategori'        => $alat->kategori->nama_kategori ?? '-',
                'lokasi'          => $alat->lokasi->nama_lokasi ?? '-',
                'bandara'         => $alat->lokasi->bandara->kode_bandara ?? '-',
                'nama_bandara'    => $alat->lokasi->bandara->nama_bandara ?? '-',
                'jam_operasional' => $alat->lokasi->bandara->jam_operasional ?? 24,
                'status'          => $alat->status,
            ]
        ]);
    }
}