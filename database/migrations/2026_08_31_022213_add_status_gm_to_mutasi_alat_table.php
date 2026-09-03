<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasi_alat', function (Blueprint $table) {
            // Keputusan GM Pemberi per-alat saat approve (bukan cuma approve/reject
            // seluruh pengajuan). Default 'lanjut' karena baris ini baru ada setelah
            // dibuat di tahap Diajukan, sebelum GM Pemberi sempat memutuskan.
            //
            // 'dikeluarkan' = GM Pemberi menilai alat ini secara bisnis belum/tidak
            // boleh dimutasikan meski statusnya Unused/idle di sistem — baris tetap
            // disimpan (bukan dihapus) supaya ada jejak audit, tapi alat ini tidak
            // ikut lanjut ke tahap Pemastian Fasilitas Idle / Sertifikasi.
            $table->enum('status_gm', ['lanjut', 'dikeluarkan'])
                  ->default('lanjut')
                  ->after('id_alat');

            $table->text('catatan_dikeluarkan')->nullable()->after('status_gm');
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_alat', function (Blueprint $table) {
            $table->dropColumn(['status_gm', 'catatan_dikeluarkan']);
        });
    }
};