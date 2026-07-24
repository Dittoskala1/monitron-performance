<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_mobilisasi_mutasi', function (Blueprint $table) {
            $table->id('id_verifikasi');

            $table->unsignedBigInteger('id_pengajuan_mutasi');
            $table->foreign('id_pengajuan_mutasi')->references('id_pengajuan_mutasi')->on('pengajuan_mutasi')->onDelete('cascade');

            // ── AFET Regional ──
            $table->enum('status_regional', ['Pending', 'Konfirmasi', 'Tidak Sesuai'])->default('Pending');
            $table->text('catatan_regional')->nullable();
            $table->timestamp('tanggal_regional')->nullable();
            $table->unsignedBigInteger('id_pengguna_regional')->nullable();
            $table->foreign('id_pengguna_regional')->references('id_pengguna')->on('pengguna')->onDelete('set null');

            // ── Admin AFET Bandara Penerima ──
            $table->enum('status_penerima', ['Pending', 'Konfirmasi', 'Tidak Sesuai'])->default('Pending');
            $table->text('catatan_penerima')->nullable();
            $table->timestamp('tanggal_penerima')->nullable();
            $table->unsignedBigInteger('id_pengguna_penerima')->nullable();
            $table->foreign('id_pengguna_penerima')->references('id_pengguna')->on('pengguna')->onDelete('set null');

            // ── Admin AFET Bandara Pemberi ──
            $table->enum('status_pemberi', ['Pending', 'Konfirmasi', 'Tidak Sesuai'])->default('Pending');
            $table->text('catatan_pemberi')->nullable();
            $table->timestamp('tanggal_pemberi')->nullable();
            $table->unsignedBigInteger('id_pengguna_pemberi')->nullable();
            $table->foreign('id_pengguna_pemberi')->references('id_pengguna')->on('pengguna')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_mobilisasi_mutasi');
    }
};