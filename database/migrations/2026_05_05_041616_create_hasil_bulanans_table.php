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
    Schema::create('hasil_bulanan', function (Blueprint $table) {
        $table->id('id_hasil_bulanan');
        $table->unsignedBigInteger('id_alat');
        $table->foreign('id_alat')->references('id_alat')->on('alat')->onDelete('cascade');
        $table->tinyInteger('bulan');
        $table->year('tahun');
        $table->string ('detail_lokasi')->nullable();
        $table->decimal('rata_performa', 5, 2)->default(0);
        $table->decimal('total_jam_operasional', 8, 2)->default(0);
        $table->decimal('total_jam_terputus', 8, 2)->default(0);
        $table->enum('status', ['Baik', 'Warning', 'Buruk'])->default('Baik');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_bulanans');
    }
};
