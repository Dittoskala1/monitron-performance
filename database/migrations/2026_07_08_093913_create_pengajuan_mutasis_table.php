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

            // id_booking & id_alat dipindah ke tabel mutasi_alat (1 pengajuan bisa
            // mencakup beberapa alat sekaligus, selama semuanya dari 1 bandara pemberi).

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
                'Ditolak GM Pemberi',
                'Menunggu Pemastian Fasilitas Idle',
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

            // ── Ajukan ulang setelah Ditolak GM Pemberi (skip CEO, CEO cuma notif) ──
            $table->unsignedBigInteger('id_pengguna_ajukan_ulang')->nullable();
            $table->foreign('id_pengguna_ajukan_ulang')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_ajukan_ulang')->nullable();
            $table->unsignedInteger('jumlah_ajukan_ulang')->default(0);

            // ── Pemastian Fasilitas Idle (dokumen BA — GM Pemberi atau Admin AFET Bandara Pemberi) ──
            $table->unsignedBigInteger('id_pengguna_upload_ba_idle')->nullable();
            $table->foreign('id_pengguna_upload_ba_idle')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_upload_ba_idle')->nullable();

            $table->unsignedBigInteger('id_pengguna_konfirmasi_idle')->nullable();
            $table->foreign('id_pengguna_konfirmasi_idle')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_konfirmasi_idle')->nullable();
            $table->text('alasan_reject_idle')->nullable();

            // ── BA Penerimaan Barang (dikonfirmasi KC Bandara Penerima, gak wajib bareng sertifikasi) ──
            $table->unsignedBigInteger('id_pengguna_terima_barang')->nullable();
            $table->foreign('id_pengguna_terima_barang')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_terima_barang')->nullable();
            $table->text('catatan_terima_barang')->nullable();

            // ── Sertifikasi (opsional, bisa Selesai tanpa dokumen) ──
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