<?php
// database/seeders/DashboardSeeder.php
//
// Cara pakai:
//   php artisan db:seed --class=DashboardSeeder
//
// ⚠️ CATATAN PENTING:
// - Seeder ini REUSE data bandara/lokasi yang sudah ada (tidak bikin baru).
//   Kalau belum ada bandara/lokasi sama sekali, seeder akan berhenti
//   dengan warning.
// - Seeder ini TIDAK idempotent: setiap kali dijalankan ulang, akan
//   menambah alat BARU lagi (bukan update yang lama). Kalau mau bersih,
//   uncomment blok TRUNCATE di bawah sebelum menjalankan ulang — tapi
//   HATI-HATI, itu akan menghapus SEMUA data alat/log_harian/hasil_bulanan
//   /notifikasi yang sudah ada (termasuk yang dibuat manual dari UI).

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardSeeder extends Seeder
{
    // Jenis alat sesuai yang dipakai DashboardController untuk grouping chart
    private array $jenisKeamanan = ['X-Ray', 'WTMD', 'HHMD', 'ETD', 'CCTV', 'Body Scanner'];
    private array $jenisOperasional = ['Fire Alarm', 'Radio Communication', 'FIDS', 'Public Address', 'Bird Deterrent'];

    public function run(): void
    {
        // ==========================================
        // (OPSIONAL) BERSIHKAN DATA LAMA HASIL SEEDER
        // Uncomment kalau mau mulai dari nol tiap run.
        // ==========================================
        // DB::statement('SET FOREIGN_KEY_CHECKS=0');
        // DB::table('notifikasi')->truncate();
        // DB::table('hasil_bulanan')->truncate();
        // DB::table('log_harian')->truncate();
        // DB::table('alat')->truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1');

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
        // 2. KATEGORI ALAT (reuse kalau nama sudah ada)
        // ==========================================
        $semuaJenis = array_merge($this->jenisKeamanan, $this->jenisOperasional);
        $kategoriMap = [];

        foreach ($semuaJenis as $jenis) {
            $kategori = DB::table('kategori_alat')->where('nama_kategori', 'LIKE', "%{$jenis}%")->first();

            if (!$kategori) {
                $idKategori = DB::table('kategori_alat')->insertGetId([
                    'nama_kategori' => $jenis,
                    'deskripsi' => "Kategori otomatis untuk {$jenis} (dibuat oleh DashboardSeeder)",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $kategoriMap[$jenis] = $idKategori;
            } else {
                $kategoriMap[$jenis] = $kategori->id_kategori;
            }
        }

        $this->command->info('✅ ' . count($kategoriMap) . ' kategori alat siap dipakai.');

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

        $bulanIni = (int) now()->month;
        $tahunIni = (int) now()->year;
        $hariIni = (int) now()->day;

        $totalAlatDibuat = 0;
        $totalLogDibuat = 0;
        $totalHasilDibuat = 0;
        $totalNotifDibuat = 0;

        foreach ($bandaraList as $bandara) {
            $lokasiList = DB::table('lokasi')->where('id_bandara', $bandara->id_bandara)->get();

            if ($lokasiList->isEmpty()) {
                $this->command->warn("⚠️ Bandara {$bandara->kode_bandara} tidak punya lokasi, dilewati.");
                continue;
            }

            foreach ($semuaJenis as $jenis) {
                $lokasi = $lokasiList->random();
                $isKeamanan = in_array($jenis, $this->jenisKeamanan);

                // ── BUAT ALAT ──
                $kodeAlat = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $bandara->kode_bandara . '-' . $jenis . '-' . rand(100, 999)));
                $barcode = 'BC-' . strtoupper(bin2hex(random_bytes(4)));

                $idAlat = DB::table('alat')->insertGetId([
                    'id_lokasi' => $lokasi->id_lokasi,
                    'id_bandara' => $bandara->id_bandara,
                    'id_kategori' => $kategoriMap[$jenis],
                    'kode_alat' => $kodeAlat,
                    'detail_lokasi' => $lokasi->nama_lokasi,
                    'unit_kerja' => 'AFET',
                    'barcode' => $barcode,
                    'jenis_alat' => $isKeamanan ? 'Keamanan Penerbangan' : 'Operasional',
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

                // ── BUAT LOG HARIAN (tanggal 1 s.d. hari ini bulan berjalan) ──
                // Distribusi performa: 70% bagus (>=90%), 20% warning (80-89%),
                // 10% buruk (<80%) — biar chart & tabel dashboard variatif.
                $totalJamOperasional = 0;
                $totalJamTerputus = 0;

                for ($tgl = 1; $tgl <= $hariIni; $tgl++) {
                    $tanggal = Carbon::create($tahunIni, $bulanIni, $tgl);
                    $jamOperasional = 24.00;

                    $peluang = rand(1, 100);
                    if ($peluang <= 70) {
                        // performa kira-kira 90-100%
                        $jamTerputus = round(rand(0, 240) / 100, 2);
                    } elseif ($peluang <= 90) {
                        // performa kira-kira 80-90%
                        $jamTerputus = round(rand(250, 480) / 100, 2);
                    } else {
                        // performa < 80%
                        $jamTerputus = round(rand(500, 1000) / 100, 2);
                    }

                    $kondisi = $jamTerputus >= 5 ? 'Gangguan' : 'Normal';

                    DB::table('log_harian')->insert([
                        'id_alat' => $idAlat,
                        'id_pengguna' => $idPenggunaLog,
                        'tanggal' => $tanggal->toDateString(),
                        'jam_operasional' => $jamOperasional,
                        'jam_terputus' => $jamTerputus,
                        // 'performa' TIDAK diisi — ini generated/stored column
                        'kondisi' => $kondisi,
                        'catatan' => $kondisi === 'Gangguan' ? 'Gangguan otomatis dari data dummy seeder.' : null,
                        'detail_lokasi' => $lokasi->nama_lokasi,
                        'created_at' => $tanggal,
                        'updated_at' => $tanggal,
                    ]);

                    $totalJamOperasional += $jamOperasional;
                    $totalJamTerputus += $jamTerputus;
                    $totalLogDibuat++;
                }

                // ── HITUNG & BUAT HASIL BULANAN (agregat dari log_harian) ──
                $rataPerforma = DB::table('log_harian')
                    ->where('id_alat', $idAlat)
                    ->whereMonth('tanggal', $bulanIni)
                    ->whereYear('tanggal', $tahunIni)
                    ->avg('performa');

                $rataPerforma = round($rataPerforma ?? 0, 2);

                $status = $rataPerforma >= $batasBaik
                    ? 'Baik'
                    : ($rataPerforma >= $batasWarning ? 'Warning' : 'Buruk');

                DB::table('hasil_bulanan')->insert([
                    'id_alat' => $idAlat,
                    'bulan' => $bulanIni,
                    'tahun' => $tahunIni,
                    'detail_lokasi' => $lokasi->nama_lokasi,
                    'rata_performa' => $rataPerforma,
                    'total_jam_operasional' => $totalJamOperasional,
                    'total_jam_terputus' => $totalJamTerputus,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $totalHasilDibuat++;

                // ── NOTIFIKASI kalau performa Warning/Buruk (biar kartu notifikasi terisi) ──
                if ($status !== 'Baik') {
                    DB::table('notifikasi')->insert([
                        'alat_id' => $idAlat,
                        'id_pengguna' => null,
                        'jenis' => 'status_error',
                        'judul' => "Performa {$jenis} menurun",
                        'pesan' => "Alat {$jenis} di {$bandara->kode_bandara} ({$lokasi->nama_lokasi}) memiliki rata-rata performa {$rataPerforma}% bulan ini.",
                        'meta' => json_encode(['id_alat' => $idAlat, 'rata_performa' => $rataPerforma]),
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

        $this->command->info("✅ {$totalAlatDibuat} alat dibuat.");
        $this->command->info("✅ {$totalLogDibuat} baris log_harian dibuat.");
        $this->command->info("✅ {$totalHasilDibuat} baris hasil_bulanan dibuat.");
        $this->command->info("✅ {$totalNotifDibuat} notifikasi dibuat.");
        $this->command->info('🎉 DashboardSeeder selesai! Buka /admin/dashboard untuk lihat hasilnya.');
    }
}