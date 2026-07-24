<?php
// database/migrations/xxxx_create_user_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_requests', function (Blueprint $table) {
            $table->id();
            
            // ==========================================
            // DATA PEMOHON
            // ==========================================
            $table->string('nama');
            $table->string('username')->unique();           // ✅ TAMBAH
            $table->string('email')->nullable()->unique();  // ✅ UBAH jadi nullable
            $table->string('password');
            
            // ==========================================
            // ROLE YANG DIMINTA
            // ==========================================
            $table->string('role_requested')->default('teknisi'); // ✅ GANTI dari 'role'
            
            // ==========================================
            // LOKASI (untuk teknisi/afet)
            // ==========================================
            $table->integer('id_bandara')->nullable();      // ✅ TAMBAH
            $table->integer('id_lokasi')->nullable();       // ✅ TAMBAH
            
            // ==========================================
            // ALASAN & STATUS
            // ==========================================
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            
            // ==========================================
            // TIMESTAMP
            // ==========================================
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_requests');
    }
};