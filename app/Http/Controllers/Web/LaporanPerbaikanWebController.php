<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanPerbaikan;
use App\Models\Bandara;
use App\Exports\LaporanPerbaikanExport;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanPerbaikanWebController extends Controller
{
    /**
     * - afet_regional : lihat semua laporan, semua bandara
     * - afet_bandara  : HANYA lihat laporan untuk alat di bandaranya sendiri
     */
    public function index(Request $request)
    {
        $role      = session('pengguna.role');
        $isLocked  = $this->isBandaraLocked($role);
        $idBandara = session('pengguna.id_bandara');
        $unit      = $this->unitKerjaSaya();

        $laporan = LaporanPerbaikan::with(['alat.lokasi.bandara', 'pengguna'])
            ->when($isLocked, function ($q) use ($idBandara) {
                $q->whereHas('alat.lokasi', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when(!$isLocked && $request->id_bandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q2) => $q2->where('id_bandara', $request->id_bandara)
            ))
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->kategori_kerusakan, fn($q) => $q->where('kategori_kerusakan', $request->kategori_kerusakan))
            ->when($request->tanggal_dari, fn($q) => $q->whereDate('tanggal_kerusakan', '>=', $request->tanggal_dari))
            ->when($request->tanggal_sampai, fn($q) => $q->whereDate('tanggal_kerusakan', '<=', $request->tanggal_sampai))
            ->orderByDesc('tanggal_kerusakan')
            ->paginate(15);

        $bandara = $isLocked
            ? Bandara::where('id_bandara', $idBandara)->get()
            : Bandara::orderBy('nama_bandara')->get();

        return view('admin.laporan-perbaikan.index', compact('laporan', 'bandara', 'isLocked'));
    }

    /**
     * - role terkunci (afet_bandara, div_head, gm_kc) : hanya bisa lihat detail laporan di bandaranya sendiri
     */
    public function show($id)
    {
        $role      = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $laporan = LaporanPerbaikan::with(['alat.lokasi.bandara', 'pengguna'])
            ->findOrFail($id);

        if ($this->isBandaraLocked($role) && ($laporan->alat->lokasi->id_bandara ?? null) != $idBandara) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        if (! $this->alatMasukCakupanUnit($laporan->alat)) {
            abort(403, 'Alat ini bukan cakupan unit kerja Anda.');
        }

        return view('admin.laporan-perbaikan.show', compact('laporan'));
    }

    /**
     * Export Excel — role terkunci dipaksa export hanya bandaranya sendiri.
     */
    public function exportExcel(Request $request)
    {
        $role      = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $idBandaraUntukExport = $this->isBandaraLocked($role) ? $idBandara : $request->id_bandara;

        $export = new LaporanPerbaikanExport(
            $idBandaraUntukExport,
            $request->status,
            $request->kategori_kerusakan,
            $request->tanggal_dari,
            $request->tanggal_sampai,
            $this->unitKerjaSaya()
        );

        return $export->export();
    }

    /**
     * Export PDF — role terkunci dipaksa export hanya bandaranya sendiri.
     */
    public function exportPdf(Request $request)
    {
        $role      = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');
        $unit      = $this->unitKerjaSaya();

        $idBandaraUntukExport = $this->isBandaraLocked($role) ? $idBandara : $request->id_bandara;

        $laporan = LaporanPerbaikan::with(['alat.lokasi.bandara', 'pengguna'])
            ->when($idBandaraUntukExport, fn($q) => $q->whereHas('alat.lokasi',
                fn($q2) => $q2->where('id_bandara', $idBandaraUntukExport)
            ))
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->kategori_kerusakan, fn($q) => $q->where('kategori_kerusakan', $request->kategori_kerusakan))
            ->when($request->tanggal_dari, fn($q) => $q->whereDate('tanggal_kerusakan', '>=', $request->tanggal_dari))
            ->when($request->tanggal_sampai, fn($q) => $q->whereDate('tanggal_kerusakan', '<=', $request->tanggal_sampai))
            ->orderBy('tanggal_kerusakan')
            ->get();

        $namaBandara = 'Semua Bandara';
        $kodeBandara = 'ALL';
        if ($idBandaraUntukExport) {
            $bandara = Bandara::find($idBandaraUntukExport);
            if ($bandara) {
                $namaBandara = $bandara->nama_bandara;
                $kodeBandara = $bandara->kode_bandara;
            }
        }

        $rangeTanggal = 'Semua Tanggal';
        if ($request->tanggal_dari && $request->tanggal_sampai) {
            $rangeTanggal = \Carbon\Carbon::parse($request->tanggal_dari)->format('d M Y')
                . ' - ' . \Carbon\Carbon::parse($request->tanggal_sampai)->format('d M Y');
        } elseif ($request->tanggal_dari) {
            $rangeTanggal = 'Mulai ' . \Carbon\Carbon::parse($request->tanggal_dari)->format('d M Y');
        }

        $jamMenit = function ($jam) {
            if (! $jam) return '0 Jam 0 Menit';
            $jamBulat = floor($jam);
            $menit    = round(($jam - $jamBulat) * 60);
            return "{$jamBulat} Jam {$menit} Menit";
        };

        $pdf = Pdf::loadView('admin.laporan-perbaikan.pdf', compact(
            'laporan', 'namaBandara', 'kodeBandara', 'rangeTanggal', 'jamMenit'
        ))->setPaper('a4', 'landscape');

        $fileName = "LAPORAN_PERBAIKAN_{$kodeBandara}_" . now()->format('Ymd_His') . ".pdf";

        return $pdf->download($fileName);
    }
}