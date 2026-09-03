<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah value 'dokumen_pendukung' ke enum jenis_dokumen di tabel dokumen_mutasi.
     * Dipakai untuk dokumen pendukung bertipe file bebas yang bisa diupload
     * opsional pada tahap approve GM Pemberi maupun tahap sertifikasi
     * (AFET Bandara Penerima).
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE dokumen_mutasi
                MODIFY jenis_dokumen ENUM(
                    'mapping_kebutuhan',
                    'pemastian_idle',
                    'terima_barang',
                    'sertifikasi',
                    'dokumen_pendukung'
                ) NOT NULL
            ");
            return;
        }

        if ($driver === 'pgsql') {
            // Postgres tidak punya ALTER TYPE ... ADD VALUE yang bisa
            // dijalankan di dalam transaksi migrasi Laravel, jadi kolom
            // jenis_dokumen di sini diasumsikan bertipe string/varchar
            // dengan CHECK constraint (bukan native enum type). Kalau
            // kolomnya memang native enum type Postgres, sesuaikan manual.
            DB::statement("ALTER TABLE dokumen_mutasi DROP CONSTRAINT IF EXISTS dokumen_mutasi_jenis_dokumen_check");
            DB::statement("
                ALTER TABLE dokumen_mutasi
                ADD CONSTRAINT dokumen_mutasi_jenis_dokumen_check
                CHECK (jenis_dokumen IN (
                    'mapping_kebutuhan',
                    'pemastian_idle',
                    'terima_barang',
                    'sertifikasi',
                    'dokumen_pendukung'
                ))
            ");
            return;
        }

        // SQLite dan driver lain umumnya tidak menegakkan enum di level
        // kolom, jadi tidak perlu perubahan skema apa pun.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE dokumen_mutasi
                MODIFY jenis_dokumen ENUM(
                    'mapping_kebutuhan',
                    'pemastian_idle',
                    'terima_barang',
                    'sertifikasi'
                ) NOT NULL
            ");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE dokumen_mutasi DROP CONSTRAINT IF EXISTS dokumen_mutasi_jenis_dokumen_check");
            DB::statement("
                ALTER TABLE dokumen_mutasi
                ADD CONSTRAINT dokumen_mutasi_jenis_dokumen_check
                CHECK (jenis_dokumen IN (
                    'mapping_kebutuhan',
                    'pemastian_idle',
                    'terima_barang',
                    'sertifikasi'
                ))
            ");
        }
    }
};