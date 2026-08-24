<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_idle', function (Blueprint $table) {
            $table->id('id_pengajuan');

            $table->unsignedBigInteger('id_alat');
            $table->foreign('id_alat')->references('id_alat')->on('alat')->onDelete('cascade');

            // Nomor Aset — input manual, cuma berlaku untuk pengajuan ini
            $table->string('nomor_aset')->nullable();

            $table->unsignedBigInteger('id_lokasi_asal');
            $table->foreign('id_lokasi_asal')->references('id_lokasi')->on('lokasi')->onDelete('cascade');

            // Detail lokasi
            $table->string('detail_lokasi')->nullable();

            // Tanggal Terbit Alat
            $table->date('tanggal_terbit_alat')->nullable();

            // Kondisi alat — Rusak Ringan & Rusak Berat DIHAPUS,
            // alat rusak berat sekarang masuk gudang, bukan lewat alur idle.
            $table->enum('kondisi_alat', ['Baik', 'Improvement'])
                  ->default('Baik');

            // Penjelasan Kondisi
            $table->text('penjelasan_kondisi')->nullable();

            $table->unsignedBigInteger('id_lokasi_unused');
            $table->foreign('id_lokasi_unused')->references('id_lokasi')->on('lokasi')->onDelete('cascade');

            $table->unsignedBigInteger('id_pengguna');
            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->onDelete('cascade');

            $table->text('alasan_idle')->nullable();

            // Status sekarang 2 tahap approval (Dep Head → Admin AFET)
            // ⚠️ DIUBAH: sebelumnya 'Waiting Approval Div Head'. Sekarang Dep
            // Head per unit kerja (mis. Dep Head SSES, Dep Head BHS, Dep Head
            // SSIT di CGK) yang approve tahap 1, menggantikan Div Head.
            // Div Head sekarang cuma "mengetahui" (permission idle.view).
            $table->enum('status', [
                'Waiting Approval Dep Head',
                'Waiting Approval Admin AFET',
                'Approved',
                'Rejected'
            ])->default('Waiting Approval Dep Head');

            $table->text('alasan_reject')->nullable();

            $table->enum('status_ketersediaan', [
                'available',
                'booked',
                'pending_booking',
                'pending_approval',
                'not_available',
                'mutasi'
            ])->nullable();

            $table->timestamp('tanggal_pengajuan')->useCurrent();
            $table->timestamp('tanggal_keputusan')->nullable();

            // Approval tahap FINAL (Admin AFET Regional)
            $table->unsignedBigInteger('id_pengguna_approval')->nullable();
            $table->foreign('id_pengguna_approval')->references('id_pengguna')->on('pengguna')->onDelete('set null');

            // Approval tahap 1 (Dep Head) — dicatat terpisah dari tahap final
            // ⚠️ DIUBAH: sebelumnya id_pengguna_approval_div_head /
            // tanggal_approval_div_head, sekarang dep_head.
            $table->unsignedBigInteger('id_pengguna_approval_dep_head')->nullable();
            $table->foreign('id_pengguna_approval_dep_head')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            $table->timestamp('tanggal_approval_dep_head')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_idle');
    }
};