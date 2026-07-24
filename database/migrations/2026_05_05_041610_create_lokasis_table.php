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
    Schema::create('lokasi', function (Blueprint $table) {
        $table->bigIncrements('id_lokasi');
        $table->unsignedBigInteger('id_bandara');
        $table->string('nama_lokasi');
        $table->string('keterangan')->nullable();
        $table->timestamps();

        $table->foreign('id_bandara')->references('id_bandara')->on('bandara')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lokasi');
    }
};
