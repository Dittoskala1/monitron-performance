<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilBulanan;
use App\Models\LogHarian;
use App\Models\Alat;
use App\Models\Bandara;
use App\Models\Threshold;
use Carbon\Carbon;
use App\Exports\RekapBulananExport;

class RekapBulananController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);

        $data = HasilBulanan::with(['alat.lokasi.bandara', 'alat.kategori'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($request->id_bandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $request->id_bandara)
            ))
            ->get()
            ->sortBy([
                fn($a, $b) => strcmp(
                    $a->alat->lokasi->nama_lokasi ?? '',
                    $b->alat->lokasi->nama_lokasi ?? ''
                ),
                fn($a, $b) => strcmp(
                    $a->alat->kategori->nama_kategori ?? '',
                    $b->alat->kategori->nama_kategori ?? ''
                ),
            ]);

        $rekap = $data
            ->groupBy(fn($r) => $r->alat->lokasi->nama_lokasi ?? 'Lainnya')
            ->map(fn($lokasiItems) => $lokasiItems
                ->groupBy(fn($r) => $r->alat->kategori->nama_kategori ?? 'Lainnya')
                ->map(fn($kategoriItems) => $kategoriItems
                    ->groupBy(fn($r) => $r->alat->nama_alat ?? 'Unknown')
                )
            );

        $bandara = Bandara::orderBy('nama_bandara')->get();

        return view('admin.rekap-bulanan.index', compact('rekap', 'bandara', 'bulan', 'tahun'));
    }

    public function generate(Request $request)
{
    $request->validate([
        'bulan' => 'required|integer|min:1|max:12',
        'tahun' => 'required|integer|min:2000|max:2100',
    ]);

    $bulan      = $request->bulan;
    $tahun      = $request->tahun;
    $threshold  = Threshold::first();
    $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

    $kombinasi = LogHarian::whereMonth('tanggal', $bulan)
        ->whereYear('tanggal', $tahun)
        ->select('id_alat', 'detail_lokasi')
        ->distinct()
        ->get();

    foreach ($kombinasi as $k) {
        $logs = LogHarian::where('id_alat', $k->id_alat)
            ->when(
                is_null($k->detail_lokasi),
                fn($q) => $q->whereNull('detail_lokasi'),
                fn($q) => $q->where('detail_lokasi', $k->detail_lokasi)
            )
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        if ($logs->isEmpty()) continue;

        $alat          = Alat::with('lokasi.bandara')->find($k->id_alat);
        $jamOpsBandara = $alat->lokasi->bandara->jam_operasional ?? 24;
        $jamOpsSebulan = $jamOpsBandara * $jumlahHari;

        $totalJamTerputus = $logs->sum('jam_terputus');
        $availability     = $jamOpsSebulan > 0
            ? (($jamOpsSebulan - $totalJamTerputus) / $jamOpsSebulan) * 100
            : 0;
        $availability = max(0, round($availability, 2));

        $status = 'Buruk';
        if ($threshold) {
            if ($availability >= $threshold->nilai_baik) {
                $status = 'Baik';
            } elseif ($availability >= $threshold->nilai_warning) {
                $status = 'Warning';
            }
        }

        // ✅ updateOrCreate dengan detail_lokasi sebagai bagian dari key
        HasilBulanan::updateOrCreate(
            [
                'id_alat'       => $k->id_alat,
                'bulan'         => $bulan,
                'tahun'         => $tahun,
                'detail_lokasi' => $k->detail_lokasi,
            ],
            [
                'rata_performa'         => $availability,
                'total_jam_operasional' => $jamOpsSebulan,
                'total_jam_terputus'    => $totalJamTerputus,
                'status'                => $status,
            ]
        );
    }

    return redirect()->route('admin.rekap-bulanan.index', [
        'bulan'      => $bulan,
        'tahun'      => $tahun,
        'id_bandara' => $request->id_bandara,
    ])->with('success', 'Rekap bulanan berhasil digenerate!');
}

    public function export(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2000|max:2100',
        ]);

        $export = new RekapBulananExport(
            $request->bulan,
            $request->tahun,
            $request->id_bandara
        );

        return $export->export();
    }
}