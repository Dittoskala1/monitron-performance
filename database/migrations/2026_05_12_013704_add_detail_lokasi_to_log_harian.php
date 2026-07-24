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
    Schema::table('log_harian', function (Blueprint $table) {
        $table->string('detail_lokasi')->nullable()->after('catatan');
    });
}

    public function down(): void
{
    Schema::table('log_harian', function (Blueprint $table) {
        $table->dropColumn('detail_lokasi');
    });
}

};
