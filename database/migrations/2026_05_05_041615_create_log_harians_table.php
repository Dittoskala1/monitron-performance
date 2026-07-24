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
    Schema::create('log_harian', function (Blueprint $table) {
        $table->id('id_log');
        $table->unsignedBigInteger('id_alat');
        $table->foreign('id_alat')->references('id_alat')->on('alat')->onDelete('cascade');
        $table->unsignedBigInteger('id_pengguna');
        $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->onDelete('cascade');
        $table->date('tanggal');
        $table->decimal('jam_operasional', 5, 2)->default(24);
        $table->decimal('jam_terputus', 5, 2)->default(0);
        $table->decimal('performa', 5, 2)->storedAs('((jam_operasional - jam_terputus) / jam_operasional) * 100');
        $table->enum('kondisi', ['Normal', 'Gangguan', 'Rusak'])->default('Normal');
        $table->text('catatan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_harians');
    }
};
