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
    Schema::create('bandara', function (Blueprint $table) {
        $table->bigIncrements('id_bandara');
        $table->string('nama_bandara');
        $table->string('kode_bandara', 10);
        $table->string('lokasi');
        $table->decimal('jam_operasional', 5, 2)->default(24); // ← tambah ini
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bandara');
    }
};
