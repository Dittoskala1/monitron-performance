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
        Schema::create('alat', function (Blueprint $table) {
            $table->bigIncrements('id_alat');
            
            $table->unsignedBigInteger('id_lokasi')->nullable();
            $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi')->onDelete('cascade');
            
            // ===== TAMBAH: id_bandara untuk mencatat kepemilikan =====
            $table->unsignedBigInteger('id_bandara');
            $table->foreign('id_bandara')->references('id_bandara')->on('bandara')->onDelete('cascade');
            // =========================================================
            
            $table->unsignedBigInteger('id_kategori')->nullable();
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori_alat')->onDelete('set null');
            
            $table->string('kode_alat')->nullable();
            $table->string('detail_lokasi')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('jenis_alat')->nullable();
            $table->string('nama_alat');
            $table->string('merek')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('buatan')->nullable();
            $table->year('tahun_pembuatan')->nullable();
            $table->string('kondisi_awal')->nullable();
            $table->enum('status', ['Aktif', 'Tidak'])->default('Aktif');

            // ===== TAMBAH: kondisi kesehatan alat, diturunkan otomatis dari =====
            // ===== entri log_harian terbaru (lihat LogHarianObserver) =====
            $table->enum('kondisi_terkini', ['Normal', 'Gangguan', 'Rusak'])->default('Normal');
            $table->timestamp('kondisi_terkini_at')->nullable();
            // =====================================================================

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alat');
    }
};