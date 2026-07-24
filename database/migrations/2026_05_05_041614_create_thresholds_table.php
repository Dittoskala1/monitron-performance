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
    Schema::create('threshold', function (Blueprint $table) {
        $table->id('id_threshold');
        $table->decimal('nilai_baik', 5, 2)->default(90.00);
        $table->decimal('nilai_warning', 5, 2)->default(80.00);
        $table->decimal('nilai_buruk', 5, 2)->default(0.00);
        $table->string('keterangan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thresholds');
    }
};
