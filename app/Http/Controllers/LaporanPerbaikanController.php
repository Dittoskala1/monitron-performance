<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LaporanPerbaikan;
use App\Models\LogHarian;
use App\Models\Alat;
use Carbon\Carbon;

class LaporanPerbaikanController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_alat'            => 'required|exists:alat,id_alat',
            'nama_peralatan'     => 'required|string|max:100',
            'kategori_kerusakan' => 'required|in:I,II,III',
            'bagian_kerusakan'   => 'required|string|max:255',
            'tindakan'           => 'nullable|string',
            'keterangan'         => 'nullable|string|max:255',
            'detail_lokasi'      => 'nullable|string|max:255',
        ]);

        $pengguna = $request->user();

        $alat = Alat::where('id_lokasi', $pengguna->id_lokasi)
            ->where('id_alat', $request->id_alat)
            ->where('status', 'Aktif')
            ->first();

        if (!$alat) {
            return response()->json([
                'success' => false,
                'message' => 'Alat tidak ditemukan atau tidak dalam lokasi Anda'
            ], 403);
        }

        $laporan = LaporanPerbaikan::create([
            'id_alat'            => $request->id_alat,
            'id_pengguna'        => $pengguna->id_pengguna,
            'nama_peralatan'     => $request->nama_peralatan,
            'kategori_kerusakan' => $request->kategori_kerusakan,
            'bagian_kerusakan'   => $request->bagian_kerusakan,
            'tindakan'           => $request->tindakan ?? '-',
            'tanggal_kerusakan'  => Carbon::now(),
            'jam_terputus'       => 0,
            'keterangan'         => $request->keterangan,
            'detail_lokasi'      => $request->detail_lokasi,
            'status'             => 'Proses',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan perbaikan berhasil disimpan',
            'data'    => $laporan->load('alat')
        ], 201);
    }

    public function selesai(Request $request, $id_laporan)
    {
        $request->validate([
            'tindakan'   => 'nullable|string',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $pengguna = $request->user();

        $laporan = LaporanPerbaikan::whereHas('alat', function ($query) use ($pengguna) {
                $query->where('id_lokasi', $pengguna->id_lokasi);
            })
            ->where('id_laporan', $id_laporan)
            ->where('status', 'Proses')
            ->first();

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan atau sudah selesai'
            ], 404);
        }

        $tanggalSelesai   = Carbon::now();
        $tanggalKerusakan = Carbon::parse($laporan->tanggal_kerusakan);
        $diffInMinutes    = $tanggalKerusakan->diffInMinutes($tanggalSelesai);
        $jamTerputus      = $diffInMinutes / 60;
        $jamDisplay       = floor($diffInMinutes / 60);
        $menitDisplay     = $diffInMinutes % 60;

        $laporan->update([
            'tanggal_selesai' => $tanggalSelesai,
            'tindakan'        => $request->tindakan ?? $laporan->tindakan,
            'keterangan'      => $request->keterangan ?? $laporan->keterangan,
            'jam_terputus'    => round($jamTerputus, 2),
            'status'          => 'Selesai',
        ]);

        $tanggalLog = Carbon::parse($laporan->tanggal_kerusakan)->toDateString();

        // Hitung total jam terputus untuk alat + detail_lokasi yang sama di hari itu
        $totalJamTerputus = LaporanPerbaikan::where('id_alat', $laporan->id_alat)
            ->where('detail_lokasi', $laporan->detail_lokasi)
            ->whereDate('tanggal_kerusakan', $tanggalLog)
            ->where('status', 'Selesai')
            ->get()
            ->sum(function ($lap) {
                if ($lap->tanggal_selesai) {
                    return Carbon::parse($lap->tanggal_kerusakan)
                        ->diffInMinutes(Carbon::parse($lap->tanggal_selesai)) / 60;
                }
                return 0;
            });

        // Update atau buat log_harian per alat + detail_lokasi + tanggal
        $kondisi = $laporan->kategori_kerusakan === 'I' ? 'Rusak' : 'Gangguan';

        LogHarian::updateOrCreate(
            [
                'id_alat'       => $laporan->id_alat,
                'detail_lokasi' => $laporan->detail_lokasi,
                'tanggal'       => $tanggalLog,
            ],
            [
                'id_pengguna'     => $laporan->id_pengguna,
                'jam_operasional' => $laporan->alat->lokasi->bandara->jam_operasional ?? 24,
                'jam_terputus'    => round($totalJamTerputus, 2),
                'kondisi'         => $kondisi,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan perbaikan ditandai selesai',
            'data'    => [
                'laporan'      => $laporan->load('alat'),
                'jam_terputus' => "{$jamDisplay} jam {$menitDisplay} menit",
            ]
        ]);
    }

    public function index(Request $request)
    {
        $pengguna = $request->user();

        $laporan = LaporanPerbaikan::with(['alat.lokasi.bandara', 'pengguna'])
            ->whereHas('alat', function ($query) use ($pengguna) {
                $query->where('id_lokasi', $pengguna->id_lokasi);
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('tanggal_kerusakan')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => $laporan
        ]);
    }

    public function show(Request $request, $id_laporan)
    {
        $pengguna = $request->user();

        $laporan = LaporanPerbaikan::with(['alat.lokasi.bandara', 'pengguna'])
            ->whereHas('alat', function ($query) use ($pengguna) {
                $query->where('id_lokasi', $pengguna->id_lokasi);
            })
            ->where('id_laporan', $id_laporan)
            ->first();

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ], 404);
        }

        if ($laporan->status === 'Proses') {
            $diffInMinutes = Carbon::parse($laporan->tanggal_kerusakan)->diffInMinutes(Carbon::now());
        } else {
            $diffInMinutes = Carbon::parse($laporan->tanggal_kerusakan)->diffInMinutes(Carbon::parse($laporan->tanggal_selesai));
        }

        $jam   = floor($diffInMinutes / 60);
        $menit = $diffInMinutes % 60;

        return response()->json([
            'success' => true,
            'data'    => array_merge($laporan->toArray(), [
                'durasi_terputus' => "{$jam} jam {$menit} menit",
            ])
        ]);
    }

    public function adminIndex(Request $request)
    {
        $laporan = LaporanPerbaikan::with(['alat.lokasi.bandara', 'pengguna'])
            ->when($request->status,             fn($q) => $q->where('status', $request->status))
            ->when($request->id_bandara,         fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $request->id_bandara)
            ))
            ->when($request->kategori_kerusakan, fn($q) => $q->where('kategori_kerusakan', $request->kategori_kerusakan))
            ->orderByDesc('tanggal_kerusakan')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $laporan
        ]);
    }
}