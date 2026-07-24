<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_mutasi', function (Blueprint $table) {
            $table->id('id_pengajuan_mutasi');

            $table->unsignedBigInteger('id_booking');
            $table->foreign('id_booking')->references('id_booking')->on('pengajuan_booking')->onDelete('cascade');

            $table->unsignedBigInteger('id_alat');
            $table->foreign('id_alat')->references('id_alat')->on('alat')->onDelete('cascade');

            $table->unsignedBigInteger('id_bandara_pemberi');
            $table->foreign('id_bandara_pemberi')->references('id_bandara')->on('bandara')->onDelete('cascade');

            $table->unsignedBigInteger('id_bandara_penerima');
            $table->foreign('id_bandara_penerima')->references('id_bandara')->on('bandara')->onDelete('cascade');

            $table->unsignedBigInteger('id_pengguna_pemohon');
            $table->foreign('id_pengguna_pemohon')->references('id_pengguna')->on('pengguna')->onDelete('cascade');

            $table->text('keterangan_kebutuhan')->nullable();

            $table->enum('status', [
                'Waiting Approval CEO',
                'Waiting Approval GM Pemberi',
                'Waiting Konfirmasi CEO',
                'Menunggu Pemastian Fasilitas Idle',
                'Siap Mobilisasi',
                'Menunggu Verifikasi Mobilisasi',
                'Menunggu Sertifikasi',
                'Selesai',
            ])->default('Waiting Approval CEO');

            // ── CEO approve/reject pertama ──
            $table->unsignedBigInteger('id_pengguna_ceo_approval')->nullable();
            $table->foreign('id_pengguna_ceo_approval')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_ceo_approval')->nullable();
            $table->text('alasan_reject_ceo')->nullable();

            // ── GM Pemberi approve/reject ──
            $table->unsignedBigInteger('id_pengguna_gm_approval')->nullable();
            $table->foreign('id_pengguna_gm_approval')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_gm_approval')->nullable();
            $table->enum('keputusan_gm', ['Approve', 'Reject'])->nullable();
            $table->text('catatan_gm')->nullable();

            // ── CEO teruskan keputusan GM ke Admin AFET Penerima ──
            $table->unsignedBigInteger('id_pengguna_ceo_teruskan')->nullable();
            $table->foreign('id_pengguna_ceo_teruskan')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_ceo_teruskan')->nullable();

            // ── Pemastian Fasilitas Idle (sebelum mobilisasi) ──
            $table->unsignedBigInteger('id_pengguna_upload_ba_idle')->nullable();
            $table->foreign('id_pengguna_upload_ba_idle')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_upload_ba_idle')->nullable();

            $table->unsignedBigInteger('id_pengguna_konfirmasi_idle')->nullable();
            $table->foreign('id_pengguna_konfirmasi_idle')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_konfirmasi_idle')->nullable();

            // ── Mobilisasi ──
            $table->unsignedBigInteger('id_pengguna_mobilisasi')->nullable();
            $table->foreign('id_pengguna_mobilisasi')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_mobilisasi')->nullable();
            $table->text('catatan_mobilisasi')->nullable();

            // ── Sertifikasi ──
            $table->unsignedBigInteger('id_pengguna_sertifikasi')->nullable();
            $table->foreign('id_pengguna_sertifikasi')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_sertifikasi')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_mutasi');
    }
};