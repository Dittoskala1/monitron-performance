<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alat;
use App\Models\Bandara;
use App\Models\LogHarian;
use App\Models\HasilBulanan;
use App\Models\Notifikasi;
use App\Models\Threshold;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulan  = $request->get('bulan', Carbon::now()->month);
        $tahun  = $request->get('tahun', Carbon::now()->year);

        // ============================================================
        // PEMBATASAN AKSES BANDARA PER ROLE
        // - afet_regional, ho, ceo   → bebas pilih bandara manapun (atau "Semua")
        // - afet_bandara, div_head, gm_kc → terkunci ke bandara sendiri,
        //   TIDAK boleh mengambil id_bandara dari query string, supaya
        //   tidak bisa dibobol lewat manipulasi URL (?id_bandara=X).
        // ============================================================
        $role = session('pengguna.role');
        $rolesBandaraOnly = ['afet_bandara', 'div_head', 'dep_head', 'gm_kc'];
        $isLocked = in_array($role, $rolesBandaraOnly);

        $idBandaraFilter = $isLocked
            ? session('pengguna.id_bandara')
            : $request->id_bandara;

        // ⚠️ BARU: kalau akun ini terikat ke 1 unit kerja spesifik (mis. SSES
        // T1 di CGK), seluruh angka & grafik di dashboard dibatasi ke lokasi +
        // jenis alat cakupan unit itu saja. Bandara dengan 1 admin (HLP, KJT,
        // BDO) tidak punya unit_kerja, jadi $unit selalu null untuk mereka
        // dan tidak ada perubahan perilaku sama sekali.
        $unit = $this->unitKerjaSaya();

        $threshold = Threshold::first();
        $totalAlat = Alat::where('status', 'Aktif')
            ->when($idBandaraFilter, fn($q) => $q->where('id_bandara', $idBandaraFilter))
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q))
            ->count();

        // Dropdown filter bandara: role terkunci cuma lihat bandaranya sendiri
        // (dropdown akan disembunyikan/dikunci di view, tapi tetap dibatasi
        // di sini sebagai lapisan keamanan kedua)
        $bandara = $isLocked
            ? Bandara::where('id_bandara', $idBandaraFilter)->get()
            : Bandara::all();

        // ============================================================
        // HASIL BULANAN (base query)
        // ============================================================
        $hasilBulanan = HasilBulanan::with('alat.lokasi.bandara')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($idBandaraFilter, fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $idBandaraFilter)
            ))
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
            ->orderByDesc('rata_performa')
            ->get();

        $rataPerforma = round($hasilBulanan->avg('rata_performa') ?? 0, 2);
        $totalBaik    = $hasilBulanan->where('status', 'Baik')->count();
        $totalWarning = $hasilBulanan->where('status', 'Warning')->count();
        $totalBuruk   = $hasilBulanan->where('status', 'Buruk')->count();

        // ============================================================
        // PERFORMA HARIAN (line chart)
        // ============================================================
        $performaHarian = LogHarian::selectRaw('tanggal, ROUND(AVG(performa), 2) as rata_performa')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->when($idBandaraFilter, fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $idBandaraFilter)
            ))
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // ============================================================
        // NOTIFIKASI TERBARU
        // Role terkunci hanya lihat notifikasi alat di bandaranya sendiri.
        // ============================================================
        $notifikasi = Notifikasi::with('alat.lokasi.bandara')
            ->where('status', 'Belum Dibaca')
            ->when($idBandaraFilter, fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $idBandaraFilter)
            ))
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
            ->orderByDesc('tanggal')
            ->take(5)
            ->get();

        // ============================================================
        // PERFORMA PER JENIS ALAT — KEAMANAN PENERBANGAN
        // Alat: X-Ray, WTMD, HHMD, ETD, CCTV, Body Scanner
        // ============================================================
        $jenisKeamanan = ['X-Ray', 'WTMD', 'HHMD', 'ETD', 'CCTV', 'Body Scanner'];

        $performaPerJenisKeamanan = collect($jenisKeamanan)->map(function ($jenis) use ($bulan, $tahun, $idBandaraFilter, $unit) {
            // ⚠️ Cari berdasarkan jenis_alat (kolom kategorisasi), BUKAN
            // nama_alat. Sebelumnya pakai nama_alat LIKE, jadi alat yang
            // nama_alat-nya tidak literally mengandung nama jenisnya (mis.
            // "PA System Koridor" untuk jenis "Public Address") kelewatan
            // dari rata-rata performa jenis ini.
            $avg = HasilBulanan::whereHas('alat', fn($q) => $q->whereRaw('LOWER(jenis_alat) = ?', [strtolower($jenis)]))
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->when($idBandaraFilter, fn($q) => $q->whereHas('alat.lokasi',
                    fn($q) => $q->where('id_bandara', $idBandaraFilter)
                ))
                ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
                ->avg('rata_performa');
            return round($avg ?? 0, 2);
        })->values();

        // ============================================================
        // PERFORMA PER JENIS ALAT — OPERASIONAL
        // Alat: Fire Alarm System, Radio Communication, FIDS,
        //       Public Address System, Bird Deterrent System
        // ============================================================
        $jenisOperasional = [
            'Fire Alarm',
            'Radio Communication',
            'FIDS',
            'Public Address',
            'Bird Deterrent',
        ];

        $performaPerJenisOperasional = collect($jenisOperasional)->map(function ($jenis) use ($bulan, $tahun, $idBandaraFilter, $unit) {
            // ⚠️ Sama seperti di atas: match ke jenis_alat, bukan nama_alat.
            $avg = HasilBulanan::whereHas('alat', fn($q) => $q->whereRaw('LOWER(jenis_alat) = ?', [strtolower($jenis)]))
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->when($idBandaraFilter, fn($q) => $q->whereHas('alat.lokasi',
                    fn($q) => $q->where('id_bandara', $idBandaraFilter)
                ))
                ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
                ->avg('rata_performa');
            return round($avg ?? 0, 2);
        })->values();

        // ============================================================
        // PERFORMA PER BANDARA
        // Role terkunci: hanya tampilkan baris bandaranya sendiri
        // (kartu "Performa Per Bandara" jadi tidak relevan untuk
        // perbandingan, tapi tetap ditampilkan sebagai info singkat).
        // ============================================================
        $performaBandara = Bandara::withCount(['lokasi as jumlah_alat' => function ($q) use ($unit) {
                $q->whereHas('alat', function ($q2) use ($unit) {
                    $q2->where('status', 'Aktif');
                    if ($unit) {
                        $this->scopeByUnitKerja($q2);
                    }
                });
            }])
            ->when($isLocked, fn($q) => $q->where('id_bandara', $idBandaraFilter))
            ->get()
            ->map(function ($b) use ($bulan, $tahun, $unit) {
                $rata = HasilBulanan::whereHas('alat.lokasi',
                        fn($q) => $q->where('id_bandara', $b->id_bandara)
                    )
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
                    ->avg('rata_performa');

                $b->rata_performa = round($rata ?? 0, 2);
                return $b;
            })
            ->filter(fn($b) => $b->rata_performa > 0)
            ->sortByDesc('rata_performa')
            ->values();

        return view('admin.dashboard', compact(
            'bulan', 'tahun', 'threshold', 'totalAlat',
            'bandara', 'rataPerforma', 'totalBaik', 'totalWarning',
            'totalBuruk', 'performaHarian', 'hasilBulanan', 'notifikasi',
            'performaPerJenisKeamanan', 'jenisKeamanan',
            'performaPerJenisOperasional', 'jenisOperasional',
            'performaBandara', 'isLocked'
        ));
    }
}