<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_mutasi', function (Blueprint $table) {
            $table->id('id_dokumen');

            $table->unsignedBigInteger('id_pengajuan_mutasi');
            $table->foreign('id_pengajuan_mutasi')->references('id_pengajuan_mutasi')->on('pengajuan_mutasi')->onDelete('cascade');

            $table->enum('jenis_dokumen', [
                'mapping_kebutuhan',
                'pemastian_idle',
                'terima_barang',
                'sertifikasi',
            ]);

            $table->string('nama_file');
            $table->string('path_file');
            $table->string('tipe_file');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_mutasi');
    }
};