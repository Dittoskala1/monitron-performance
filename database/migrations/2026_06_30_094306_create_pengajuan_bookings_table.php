<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_booking', function (Blueprint $table) {
            $table->id('id_booking');

            $table->unsignedBigInteger('id_pengajuan_idle');
            $table->foreign('id_pengajuan_idle')->references('id_pengajuan')->on('pengajuan_idle')->onDelete('cascade');

            // Snapshot data alat saat booking dibuat
            $table->string('kode_alat_snapshot')->nullable();
            $table->string('nama_alat_snapshot')->nullable();

            $table->unsignedBigInteger('id_pengguna_pemesan'); // Admin AFET Bandara Penerima
            $table->foreign('id_pengguna_pemesan')->references('id_pengguna')->on('pengguna')->onDelete('cascade');

            $table->unsignedBigInteger('id_bandara_penerima');
            $table->foreign('id_bandara_penerima')->references('id_bandara')->on('bandara')->onDelete('cascade');

            // Status sederhana: booking cuma lock, tidak ada approval
            $table->enum('status', ['Aktif', 'Dibatalkan', 'Lanjut Mutasi'])->default('Aktif');

            $table->timestamp('tanggal_booking')->useCurrent();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_booking');
    }
};