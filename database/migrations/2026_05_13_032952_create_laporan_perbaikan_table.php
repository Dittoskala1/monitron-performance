<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laporan_perbaikan', function (Blueprint $table) {
            $table->id('id_laporan');
            $table->unsignedBigInteger('id_alat');
            $table->foreign('id_alat')->references('id_alat')->on('alat')->onDelete('cascade');
            $table->unsignedBigInteger('id_pengguna');
            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->onDelete('cascade');
            $table->string('nama_peralatan');
            $table->enum('kategori_kerusakan', ['I', 'II', 'III']);
            $table->string('bagian_kerusakan');
            $table->text('tindakan');
            $table->dateTime('tanggal_kerusakan');
            $table->dateTime('tanggal_selesai')->nullable();
            $table->decimal('jam_terputus', 5, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->string('detail_lokasi')->nullable(); 
            $table->enum('status', ['Proses', 'Selesai'])->default('Proses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_perbaikan');
    }
};
