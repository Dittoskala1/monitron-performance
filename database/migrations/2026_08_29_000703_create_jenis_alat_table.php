<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ⚠️ BARU: tabel jenis_alat.
     *
     * Sebelumnya daftar jenis alat hardcode di
     * PengaturanController::JENIS_ALAT_OPTIONS, jadi setiap nambah jenis
     * baru harus ubah kode & deploy ulang. Sekarang jenis alat jadi data
     * di database, bisa dikelola admin langsung dari halaman Pengaturan
     * (sama kayak Kategori Alat).
     */
    public function up(): void
    {
        Schema::create('jenis_alat', function (Blueprint $table) {
            $table->id('id_jenis');
            $table->string('nama_jenis')->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        // Migrasi data: isi tabel baru dengan opsi yang dulu hardcode,
        // supaya alat & unit kerja yang sudah pakai jenis_alat lama tetap valid.
        $jenisLama = [
            'X-Ray', 'WTMD', 'HHMD', 'ETD', 'CCTV', 'Body Scanner', 'Access Control',
            'Fire Alarm', 'Radio Communication', 'FIDS', 'Public Address', 'Bird Deterrent',
            'HVAC', 'Genset', 'Conveyor Belt', 'Network Device', 'Server/UPS',
        ];

        $now = now();
        DB::table('jenis_alat')->insert(array_map(fn ($nama) => [
            'nama_jenis' => $nama,
            'created_at' => $now,
            'updated_at' => $now,
        ], $jenisLama));
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_alat');
    }
};