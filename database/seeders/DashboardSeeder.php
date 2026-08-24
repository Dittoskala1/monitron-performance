<?php
// database/seeders/DashboardSeeder.php
//
// Cara pakai:
//   php artisan db:seed --class=DashboardSeeder
//
// ⚠️ CATATAN:
// - Seeder ini REUSE data bandara/lokasi yang sudah ada (tidak bikin baru).
//   Kalau belum ada bandara/lokasi sama sekali, seeder akan berhenti
//   dengan warning.
// - IDEMPOTENT: aman dijalankan berkali-kali.
//   - Alat "demo" (1 per jenis per bandara) dibuat pakai cek exists dulu,
//     jadi TIDAK akan numpuk/dobel kalau dijalankan ulang.
//   - Log harian & hasil bulanan pakai updateOrInsert (per id_alat+tanggal
//     / id_alat+bulan+tahun), jadi ditimpa, bukan dobel.
// - Log harian diisi untuk SEMUA alat aktif yang ada di database saat
//   seeder dijalankan — bukan cuma alat demo yang dibuat seeder ini.
//   Jadi alat yang sudah kamu buat/import manual (termasuk yang sudah
//   di-assign ke Unit Kerja) ikut kebagian data performa.
// - Rentang waktu: 3 bulan penuh (2 bulan lalu, bulan lalu, bulan ini
//   sampai hari ini). Edit method daftarBulan() kalau mau bulan lain.

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardSeeder extends Seeder
{
    // Jenis alat sesuai yang dipakai DashboardController untuk grouping chart
    private array $jenisKeamanan = ['X-Ray', 'WTMD', 'HHMD', 'ETD', 'CCTV', 'Body Scanner'];
    private array $jenisOperasional = ['Fire Alarm', 'Radio Communication', 'FIDS', 'Public Address', 'Bird Deterrent'];

    /**
     * Daftar bulan yang mau diisi log_harian & hasil_bulanan-nya.
     * Default: 3 bulan terakhir (termasuk bulan berjalan, sampai hari ini).
     * Edit array ini kalau mau bulan lain (mis. Juni 2026 spesifik):
     *   return [['tahun' => 2026, 'bulan' => 6]];
     */
    protected function daftarBulan(): array
    {
        $bulanIni = Carbon::now()->startOfMonth();

        return [
            ['tahun' => $bulanIni->copy()->subMonths(2)->year, 'bulan' => $bulanIni->copy()->subMonths(2)->month],
            ['tahun' => $bulanIni->copy()->subMonths(1)->year, 'bulan' => $bulanIni->copy()->subMonths(1)->month],
            ['tahun' => $bulanIni->year,                        'bulan' => $bulanIni->month],
        ];
    }

    public function run(): void
    {
        // ==========================================
        // 1. THRESHOLD (kalau belum ada)
        // ==========================================
        if (!DB::table('threshold')->exists()) {
            DB::table('threshold')->insert([
                'nilai_baik' => 90.00,
                'nilai_warning' => 80.00,
                'nilai_buruk' => 0.00,
                'keterangan' => 'Threshold default (dibuat otomatis oleh DashboardSeeder)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('✅ Threshold default dibuat.');
        } else {
            $this->command->info('ℹ️ Threshold sudah ada, dilewati.');
        }

        $threshold = DB::table('threshold')->first();
        $batasBaik = (float) $threshold->nilai_baik;
        $batasWarning = (float) $threshold->nilai_warning;

        // ==========================================
        // 2. KATEGORI ALAT (reuse 2 kategori: Faskampen & DBU)
        // ==========================================
        $semuaJenis = array_merge($this->jenisKeamanan, $this->jenisOperasional);
        $kategoriMap = [];

        foreach ($semuaJenis as $jenis) {
            $kategoriMap[$jenis] = KategoriHelper::resolveKategoriId($jenis);
        }

        $this->command->info('✅ Kategori Faskampen & DBU siap dipakai.');

        // ==========================================
        // 3. AMBIL BANDARA & LOKASI YANG SUDAH ADA
        // ==========================================
        $bandaraList = DB::table('bandara')->get();

        if ($bandaraList->isEmpty()) {
            $this->command->warn('⚠️ Tidak ada data bandara sama sekali. Seeder dihentikan — isi dulu data bandara & lokasi.');
            return;
        }

        // Ambil 1 user teknisi buat jadi id_pengguna di log_harian (kolom wajib diisi)
        $idPenggunaLog = DB::table('pengguna')->where('role', 'teknisi')->value('id_pengguna')
            ?? DB::table('pengguna')->value('id_pengguna');

        if (!$idPenggunaLog) {
            $this->command->warn('⚠️ Tidak ada data pengguna sama sekali. Seeder dihentikan — log_harian butuh id_pengguna yang valid.');
            return;
        }

        // ==========================================
        // 4. PASTIKAN ADA ALAT DEMO (1 per jenis per bandara)
        //    ⚠️ Cek exists dulu -> gak numpuk kalau dijalankan ulang.
        //    Kalau bandara itu SUDAH punya alat dengan jenis_alat yang
        //    sama (dari sumber manapun -- manual/import/seeder lain),
        //    alat itu yang dipakai, tidak bikin alat demo baru lagi.
        // ==========================================
        $totalAlatDibuat = 0;

        foreach ($bandaraList as $bandara) {
            $lokasiList = DB::table('lokasi')->where('id_bandara', $bandara->id_bandara)->get();

            if ($lokasiList->isEmpty()) {
                $this->command->warn("⚠️ Bandara {$bandara->kode_bandara} tidak punya lokasi, dilewati.");
                continue;
            }

            foreach ($semuaJenis as $jenis) {
                $sudahAda = DB::table('alat')
                    ->where('id_bandara', $bandara->id_bandara)
                    ->whereRaw('LOWER(jenis_alat) = ?', [strtolower($jenis)])
                    ->exists();

                if ($sudahAda) {
                    continue; // udah ada alat jenis ini di bandara ini, gak perlu bikin lagi
                }

                $lokasi = $lokasiList->random();
                $kodeAlat = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $bandara->kode_bandara . '-' . $jenis . '-' . rand(100, 999)));
                $barcode = 'BC-' . strtoupper(bin2hex(random_bytes(4)));

                DB::table('alat')->insert([
                    'id_lokasi' => $lokasi->id_lokasi,
                    'id_bandara' => $bandara->id_bandara,
                    'id_kategori' => $kategoriMap[$jenis],
                    'kode_alat' => $kodeAlat,
                    'detail_lokasi' => $lokasi->nama_lokasi,
                    'unit_kerja' => 'AFET',
                    'barcode' => $barcode,
                    'jenis_alat' => $jenis,
                    'nama_alat' => "{$jenis} - {$bandara->kode_bandara}",
                    'merek' => 'Generic',
                    'ip_address' => null,
                    'buatan' => 'Indonesia',
                    'tahun_pembuatan' => rand(2018, 2024),
                    'kondisi_awal' => 'Baik',
                    'status' => 'Aktif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $totalAlatDibuat++;
            }
        }

        $this->command->info("✅ {$totalAlatDibuat} alat demo baru dibuat (jenis yang sudah ada dilewati).");

        // ==========================================
        // 5. ISI LOG HARIAN + HASIL BULANAN
        //    ⚠️ Untuk SEMUA alat aktif yang ada di database saat ini
        //    (termasuk alat lama/manual/import, bukan cuma alat demo
        //    yang baru dibuat di atas), selama BEBERAPA BULAN PENUH.
        // ==========================================
        $alatList = DB::table('alat')->where('status', 'Aktif')->get();
        $totalLogDibuat = 0;
        $totalHasilDibuat = 0;
        $totalNotifDibuat = 0;

        foreach ($this->daftarBulan() as $periode) {
            $bulan = $periode['bulan'];
            $tahun = $periode['tahun'];

            $sekarang = Carbon::now();
            $isBulanBerjalan = ($bulan == $sekarang->month && $tahun == $sekarang->year);
            $jumlahHari = $isBulanBerjalan
                ? $sekarang->day
                : Carbon::create($tahun, $bulan, 1)->daysInMonth;

            $this->command->info("→ Mengisi log_harian untuk {$bulan}/{$tahun} ({$jumlahHari} hari, {$alatList->count()} alat)...");

            foreach ($alatList as $alat) {
                $totalJamOperasional = 0;
                $totalJamTerputus = 0;

                // Distribusi performa: 70% bagus (>=90%), 20% warning
                // (80-90%), 10% buruk (<80%) — biar chart & tabel dashboard
                // kelihatan variatif, bukan seragam semua "Baik".
                for ($tgl = 1; $tgl <= $jumlahHari; $tgl++) {
                    $tanggal = Carbon::create($tahun, $bulan, $tgl);
                    $jamOperasional = 24.00;

                    $peluang = rand(1, 100);
                    if ($peluang <= 70) {
                        $jamTerputus = round(rand(0, 240) / 100, 2);
                    } elseif ($peluang <= 90) {
                        $jamTerputus = round(rand(250, 480) / 100, 2);
                    } else {
                        $jamTerputus = round(rand(500, 1000) / 100, 2);
                    }

                    $kondisi = $jamTerputus >= 5 ? 'Gangguan' : 'Normal';

                    DB::table('log_harian')->updateOrInsert(
                        ['id_alat' => $alat->id_alat, 'tanggal' => $tanggal->toDateString()],
                        [
                            'id_pengguna'     => $idPenggunaLog,
                            'jam_operasional' => $jamOperasional,
                            'jam_terputus'    => $jamTerputus,
                            // 'performa' TIDAK diisi — ini generated/stored column
                            'kondisi'         => $kondisi,
                            'catatan'         => $kondisi === 'Gangguan' ? 'Gangguan otomatis dari data dummy seeder.' : null,
                            'detail_lokasi'   => $alat->detail_lokasi,
                            'created_at'      => $tanggal,
                            'updated_at'      => $tanggal,
                        ]
                    );

                    $totalJamOperasional += $jamOperasional;
                    $totalJamTerputus += $jamTerputus;
                    $totalLogDibuat++;
                }

                // ── HITUNG & SIMPAN HASIL BULANAN (agregat dari log_harian) ──
                $rataPerforma = DB::table('log_harian')
                    ->where('id_alat', $alat->id_alat)
                    ->whereMonth('tanggal', $bulan)
                    ->whereYear('tanggal', $tahun)
                    ->avg('performa');

                $rataPerforma = round($rataPerforma ?? 0, 2);
                $status = $rataPerforma >= $batasBaik
                    ? 'Baik'
                    : ($rataPerforma >= $batasWarning ? 'Warning' : 'Buruk');

                DB::table('hasil_bulanan')->updateOrInsert(
                    [
                        'id_alat'       => $alat->id_alat,
                        'bulan'         => $bulan,
                        'tahun'         => $tahun,
                        'detail_lokasi' => $alat->detail_lokasi,
                    ],
                    [
                        'rata_performa'         => $rataPerforma,
                        'total_jam_operasional' => $totalJamOperasional,
                        'total_jam_terputus'    => $totalJamTerputus,
                        'status'                => $status,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]
                );
                $totalHasilDibuat++;

                // ── NOTIFIKASI kalau performa Warning/Buruk, khusus bulan
                //    berjalan saja (biar notif gak nyampah dari bulan lama) ──
                if ($isBulanBerjalan && $status !== 'Baik') {
                    $sudahAdaNotif = DB::table('notifikasi')
                        ->where('alat_id', $alat->id_alat)
                        ->whereDate('tanggal', now()->toDateString())
                        ->exists();

                    if (!$sudahAdaNotif) {
                        DB::table('notifikasi')->insert([
                            'alat_id' => $alat->id_alat,
                            'id_pengguna' => null,
                            'jenis' => 'status_error',
                            'judul' => "Performa {$alat->nama_alat} menurun",
                            'pesan' => "Alat {$alat->nama_alat} memiliki rata-rata performa {$rataPerforma}% bulan ini.",
                            'meta' => json_encode(['id_alat' => $alat->id_alat, 'rata_performa' => $rataPerforma]),
                            'prioritas' => $status === 'Buruk' ? 'tinggi' : 'sedang',
                            'status' => 'Belum Dibaca',
                            'tanggal' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $totalNotifDibuat++;
                    }
                }
            }
        }

        $this->command->info("✅ {$totalLogDibuat} baris log_harian dibuat/diperbarui.");
        $this->command->info("✅ {$totalHasilDibuat} baris hasil_bulanan dibuat/diperbarui.");
        $this->command->info("✅ {$totalNotifDibuat} notifikasi baru dibuat.");
        $this->command->info('🎉 DashboardSeeder selesai! Buka /admin/dashboard atau /admin/rekap-bulanan untuk lihat hasilnya.');
    }
}