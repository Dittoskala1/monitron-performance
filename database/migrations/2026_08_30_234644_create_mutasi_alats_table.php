<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_alat', function (Blueprint $table) {
            $table->id('id_mutasi_alat');

            $table->unsignedBigInteger('id_pengajuan_mutasi');
            $table->foreign('id_pengajuan_mutasi')->references('id_pengajuan_mutasi')->on('pengajuan_mutasi')->onDelete('cascade');

            $table->unsignedBigInteger('id_booking');
            $table->foreign('id_booking')->references('id_booking')->on('pengajuan_booking')->onDelete('cascade');

            $table->unsignedBigInteger('id_alat');
            $table->foreign('id_alat')->references('id_alat')->on('alat')->onDelete('cascade');

            // Diisi belakangan di tahap Sertifikasi — opsional, per-alat (bisa beda-beda
            // lokasi tujuan meski masih dalam 1 pengajuan mutasi yang sama).
            $table->unsignedBigInteger('id_lokasi_tujuan')->nullable();
            $table->foreign('id_lokasi_tujuan')->references('id_lokasi')->on('lokasi')->onDelete('set null');

            $table->timestamps();

            // 1 booking cuma boleh dipakai di 1 baris mutasi_alat (mencegah alat yang
            // sama diajukan mutasi dobel dalam pengajuan yang berbeda).
            $table->unique('id_booking');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_alat');
    }
};