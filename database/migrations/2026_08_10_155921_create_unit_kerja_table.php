<?php
// database/migrations/2026_08_10_024700_create_unit_kerja_table.php
//
// Tabel ini menampung "Unit Kerja" di dalam sebuah bandara — misalnya di
// CGK ada banyak unit terpisah (BHS, CCIT, DANET, GES T1/T2/T3/NT,
// IT NON PUBLIK, PSIT, SSES T1/T2/T3/NT, SSIT) yang masing-masing punya
// tanggung jawab sendiri, biasanya terikat ke satu terminal/lokasi dan
// jenis alat tertentu (mis. SSES = X-Ray, WTMD, Body Scanner, Access
// Control). Bandara yang cuma punya 1 admin (tidak ada pemisahan unit)
// tidak perlu diisi tabel ini sama sekali.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_kerja', function (Blueprint $table) {
            $table->bigIncrements('id_unit');

            $table->unsignedBigInteger('id_bandara');
            $table->foreign('id_bandara')
                  ->references('id_bandara')
                  ->on('bandara')
                  ->onDelete('cascade');

            // Nullable: unit yang tidak terikat 1 terminal spesifik
            // (mis. BHS, CCIT, DANET yang cakupannya se-bandara)
            $table->unsignedBigInteger('id_lokasi')->nullable();
            $table->foreign('id_lokasi')
                  ->references('id_lokasi')
                  ->on('lokasi')
                  ->onDelete('set null');

            $table->string('kode_unit', 30);      // Contoh: SSES-T1, BHS, CCIT
            $table->string('nama_unit');          // Contoh: Safety & Security Electronic Services - Terminal 1
            $table->text('keterangan')->nullable();

            // Jenis alat yang jadi tanggung jawab unit ini, contoh:
            // ["X-Ray","WTMD","Body Scanner","Access Control"].
            // Boleh kosong dulu ([] / null) — diisi belakangan lewat halaman Kelola Unit.
            $table->json('cakupan_alat')->nullable();

            $table->timestamps();

            $table->unique(['id_bandara', 'kode_unit']);
        });

        // ⚠️ BARU: tempelkan foreign key id_unit di tabel pengguna ke sini,
        // karena tabel unit_kerja baru saja selesai dibuat di atas.
        // Kolom id_unit sendiri sudah ada duluan dari migration create_penggunas_table.
        Schema::table('pengguna', function (Blueprint $table) {
            $table->foreign('id_unit')
                  ->references('id_unit')
                  ->on('unit_kerja')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropForeign(['id_unit']);
        });

        Schema::dropIfExists('unit_kerja');
    }
};