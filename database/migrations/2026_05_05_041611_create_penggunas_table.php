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
        Schema::create('pengguna', function (Blueprint $table) {
            $table->bigIncrements('id_pengguna');
            $table->string('nama');
            $table->string('username')->unique();
            $table->string('password');
            
            // ==========================================
            // ROLE: 7 role sesuai proses bisnis
            // ==========================================
            $table->enum('role', [
                'teknisi', 
                'afet_bandara', 
                'afet_regional', 
                'div_head', 
                'gm_kc', 
                'ho', 
                'ceo'
            ])->default('teknisi');
            
            $table->unsignedBigInteger('id_bandara')->nullable();
            $table->foreign('id_bandara')
                  ->references('id_bandara')
                  ->on('bandara')
                  ->onDelete('set null');
                  
            $table->unsignedBigInteger('id_lokasi')->nullable();
            $table->foreign('id_lokasi')
                  ->references('id_lokasi')
                  ->on('lokasi')
                  ->onDelete('set null');
                  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengguna');  // ← PERBAIKI: dari 'penggunas' menjadi 'pengguna'
    }
};