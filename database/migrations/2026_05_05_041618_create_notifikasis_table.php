<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            
            // ==========================================
            // RELASI KE ALAT
            // ==========================================
            $table->unsignedBigInteger('alat_id');
            $table->foreign('alat_id')
                  ->references('id_alat')
                  ->on('alat')
                  ->onDelete('cascade');
            
            // ==========================================
            // RELASI KE PENGGUNA (PENERIMA NOTIFIKASI)
            // ==========================================
            $table->unsignedBigInteger('id_pengguna')->nullable();
            $table->foreign('id_pengguna')
                  ->references('id_pengguna')
                  ->on('pengguna')
                  ->onDelete('cascade');
            
            // ==========================================
            // JENIS NOTIFIKASI
            // ==========================================
            $table->enum('jenis', [
                'alat_baru',
                'status_error',
                'status_offline',
                'status_online',
                'pemeliharaan',
                'sistem',
                'pengajuan_idle',
                'approve_idle',
                'reject_idle',
                'pengajuan_booking',
                'approve_booking',
                'reject_booking',
                'ajukan_pengembalian',
                'approve_pengembalian',
                'reject_pengembalian',
                'laporan_perbaikan',
                // ==========================================
                // TAMBAHAN UNTUK MUTASI
                // ==========================================
                'pengajuan_mutasi',
                'approve_mutasi',
                'reject_mutasi',
                'konfirmasi_mutasi',
                'mobilisasi_mutasi',
                'sertifikasi_mutasi',
            ])->default('sistem');
            
            // ==========================================
            // KONTEN NOTIFIKASI
            // ==========================================
            $table->string('judul');
            $table->text('pesan');
            $table->json('meta')->nullable();
            
            // ==========================================
            // PRIORITAS & STATUS
            // ==========================================
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi', 'kritis'])->default('sedang');
            $table->enum('status', ['Belum Dibaca', 'Dibaca'])->default('Belum Dibaca');
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamp('tanggal')->useCurrent();
            $table->timestamps();

            // ==========================================
            // INDEX
            // ==========================================
            $table->index(['status', 'tanggal']);
            $table->index(['alat_id', 'jenis']);
            $table->index(['id_pengguna', 'status']);  // ← TAMBAHKAN
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};