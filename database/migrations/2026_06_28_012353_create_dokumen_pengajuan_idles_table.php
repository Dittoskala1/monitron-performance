<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_pengajuan_idle', function (Blueprint $table) {
            $table->id('id_dokumen');

            $table->unsignedBigInteger('id_pengajuan');
            $table->foreign('id_pengajuan')->references('id_pengajuan')->on('pengajuan_idle')->onDelete('cascade');

            $table->string('nama_file');       // nama asli file saat diupload
            $table->string('path_file');       // lokasi file di storage
            $table->string('tipe_file')->nullable(); // ekstensi/mime type, untuk tampilkan ikon yang sesuai

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_pengajuan_idle');
    }
};