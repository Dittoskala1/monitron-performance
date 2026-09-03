<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->dropForeign(['alat_id']);
        });

        DB::statement('ALTER TABLE `notifikasi` MODIFY `alat_id` BIGINT UNSIGNED NULL');

        Schema::table('notifikasi', function (Blueprint $table) {
            $table->foreign('alat_id')
                  ->references('id_alat')
                  ->on('alat')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->dropForeign(['alat_id']);
        });

        DB::statement('ALTER TABLE `notifikasi` MODIFY `alat_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('notifikasi', function (Blueprint $table) {
            $table->foreign('alat_id')
                  ->references('id_alat')
                  ->on('alat')
                  ->onDelete('cascade');
        });
    }
};
