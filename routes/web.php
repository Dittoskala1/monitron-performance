<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\AlatController;
use App\Http\Controllers\Web\DataHarianController;
use App\Http\Controllers\Web\RekapBulananController;
use App\Http\Controllers\Web\NotifikasiController;
use App\Http\Controllers\Web\PenggunaController;
use App\Http\Controllers\Web\PengaturanController;
use App\Http\Controllers\Web\LaporanPerbaikanWebController;
use App\Http\Controllers\Web\PengajuanIdleController;
use App\Http\Controllers\Web\PengajuanBookingController;
use App\Http\Controllers\Web\PengajuanMutasiController;
use App\Http\Controllers\Web\RoleController;

// ==========================================
// LOGIN (Tanpa middleware)
// ==========================================
Route::get('/',        [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthWebController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

//FORGOT PASSWORD
Route::get('/lupa-password',  [AuthWebController::class, 'showForgotPassword'])->name('password.forgot');
Route::post('/lupa-password', [AuthWebController::class, 'resetPassword'])->name('password.reset');

// ==========================================
// DASHBOARD ADMIN (Semua yang login)
// ==========================================
Route::middleware('auth.web')->prefix('admin')->name('admin.')->group(function () {

    // ==========================================
    // DASHBOARD - Semua role yang login bisa lihat
    // ==========================================
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:laporan.view')
        ->name('dashboard');

    // ==========================================
    // DATA ALAT - AFET Bandara & AFET Regional
    // ==========================================
    Route::prefix('alat')->name('alat.')->group(function () {
        Route::get('/', [AlatController::class, 'index'])
            ->middleware('permission:alat.view')
            ->name('index');

        Route::get('/create', [AlatController::class, 'create'])
            ->middleware('permission:alat.create')
            ->name('create');
        Route::post('/', [AlatController::class, 'store'])
            ->middleware('permission:alat.create')
            ->name('store');

        Route::get('/{id}', [AlatController::class, 'show'])
            ->middleware('permission:alat.view')
            ->name('show');

        Route::get('/{id}/edit', [AlatController::class, 'edit'])
            ->middleware('permission:alat.edit')
            ->name('edit');
        Route::put('/{id}', [AlatController::class, 'update'])
            ->middleware('permission:alat.edit')
            ->name('update');

        Route::delete('/{id}', [AlatController::class, 'destroy'])
            ->middleware('permission:alat.delete')
            ->name('destroy');

        Route::get('/{id}/qr', [AlatController::class, 'downloadQr'])
            ->middleware('permission:alat.view')
            ->name('qr');
    });

    // ==========================================
    // PERALATAN IDLE
    // ==========================================
    Route::prefix('peralatan-idle')->name('peralatan-idle.')->group(function () {

        // ── LIHAT DAFTAR IDLE ──
        // AFET Bandara, AFET Regional, Div Head
        Route::get('/', [PengajuanIdleController::class, 'index'])
            ->middleware('permission:idle.view')
            ->name('index');

        // ── INPUT PENGAJUAN IDLE ──
        // AFET Bandara saja
        Route::get('/create', [PengajuanIdleController::class, 'create'])
            ->middleware('permission:idle.create')
            ->name('create');
        Route::post('/', [PengajuanIdleController::class, 'store'])
            ->middleware('permission:idle.create')
            ->name('store');

        // ── LIHAT DETAIL IDLE ──
        // AFET Bandara, AFET Regional, Div Head
        Route::get('/{id}', [PengajuanIdleController::class, 'show'])
            ->middleware('permission:idle.view')
            ->name('show');

        // ── APPROVE IDLE ──
        // Hanya Div Head
        Route::post('/{id}/approve', [PengajuanIdleController::class, 'approve'])
            ->middleware('permission:idle.approve')
            ->name('approve');

        // ── REJECT IDLE ──
        // Hanya Div Head
        Route::post('/{id}/reject', [PengajuanIdleController::class, 'reject'])
            ->middleware('permission:idle.reject')
            ->name('reject');

        // ── UPDATE PENGAJUAN IDLE ──
        // AFET Bandara (pemohon)
        Route::put('/{id}', [PengajuanIdleController::class, 'update'])
            ->middleware('permission:idle.create')
            ->name('update');

        // ── HAPUS DOKUMEN ──
        // AFET Bandara (pemohon)
        Route::delete('/{id}/dokumen/{idDokumen}', [PengajuanIdleController::class, 'hapusDokumen'])
            ->middleware('permission:idle.create')
            ->name('hapus-dokumen');

        // ── TARIK KEMBALI PENGAJUAN ──
        // AFET Bandara (pemohon)
        Route::post('/{id}/tarik-kembali', [PengajuanIdleController::class, 'tarikKembali'])
            ->middleware('permission:idle.create')
            ->name('tarik-kembali');

        // ── LIHAT / UNDUH DOKUMEN ──
        // Dipakai juga dari halaman Peralatan Booking (dokumen milik
        // Pengajuan Idle yang sama). Tidak bergantung symlink storage,
        // jadi aman untuk semua tipe file (pdf, jpg, jpeg, png, dll).
        Route::get('/dokumen/{idDokumen}/download', [PengajuanIdleController::class, 'downloadDokumen'])
            ->name('download-dokumen');
    });

    // ==========================================
    // PERALATAN BOOKING
    // ⚠️ DIPERBAIKI: sebelumnya seluruh grup dikunci
    // ->middleware('role.web:afet_bandara') secara hardcode di route,
    // jadi tidak bisa diatur dari halaman Role & Permission.
    // Sekarang pakai permission dinamis (booking.view, booking.create,
    // booking.cancel) yang bisa dicentang/dilepas per role dari
    // admin/roles.
    // ==========================================
    Route::prefix('peralatan-booking')->name('peralatan-booking.')->group(function () {

        // ── LIHAT DAFTAR & DETAIL ──
        Route::get('/', [PengajuanBookingController::class, 'index'])
            ->middleware('permission:booking.view')
            ->name('index');
        Route::get('/{id}', [PengajuanBookingController::class, 'show'])
            ->middleware('permission:booking.view')
            ->name('show');

        // ── BOOKING & CANCEL ──
        Route::post('/', [PengajuanBookingController::class, 'store'])
            ->middleware('permission:booking.create')
            ->name('store');
        Route::post('/{id}/cancel', [PengajuanBookingController::class, 'cancel'])
            ->middleware('permission:booking.cancel')
            ->name('cancel');
    });

    // ==========================================
    // PERALATAN MUTASI
    // ==========================================
    Route::prefix('peralatan-mutasi')->name('peralatan-mutasi.')->group(function () {

        Route::get('/', [PengajuanMutasiController::class, 'index'])
            ->middleware('permission:mutasi.view')
            ->name('index');

        // Input Mapping Kebutuhan — dari booking (bisa banyak sekaligus, lewat ?bookings[]=..)
        Route::get('/create', [PengajuanMutasiController::class, 'create'])
            ->middleware('permission:mutasi.create')
            ->name('create');

        Route::post('/', [PengajuanMutasiController::class, 'store'])
            ->middleware('permission:mutasi.create')
            ->name('store');

        Route::get('/{id}', [PengajuanMutasiController::class, 'show'])
            ->middleware('permission:mutasi.view')
            ->name('show');

        // CEO approve/reject pertama
        Route::post('/{id}/approve-ceo', [PengajuanMutasiController::class, 'approveCeo'])
            ->middleware('permission:mutasi.approve')
            ->name('approve-ceo');

        Route::post('/{id}/reject-ceo', [PengajuanMutasiController::class, 'rejectCeo'])
            ->middleware('permission:mutasi.reject')
            ->name('reject-ceo');

        // GM Pemberi approve/reject
        Route::post('/{id}/approve-gm', [PengajuanMutasiController::class, 'approveGm'])
    ->middleware('permission:mutasi.approve')
    ->name('approve-gm');

Route::post('/{id}/reject-gm', [PengajuanMutasiController::class, 'rejectGm'])
    ->middleware('permission:mutasi.approve')
    ->name('reject-gm');

        // Pemohon ajukan ulang setelah Ditolak GM Pemberi (skip CEO)
        Route::post('/{id}/ajukan-ulang-gm', [PengajuanMutasiController::class, 'ajukanUlangGm'])
            ->middleware('permission:mutasi.create')
            ->name('ajukan-ulang-gm');

        // Pemastian Fasilitas Idle
        Route::post('/{id}/upload-dokumen-idle', [PengajuanMutasiController::class, 'uploadDokumenIdle'])
            ->middleware('permission:mutasi.proses-idle')
            ->name('upload-dokumen-idle');

        Route::post('/{id}/konfirmasi-idle', [PengajuanMutasiController::class, 'konfirmasiIdle'])
            ->middleware('permission:mutasi.approve')
            ->name('konfirmasi-idle');

        // Reject saat Pemastian Fasilitas Idle
        Route::post('/{id}/reject-idle', [PengajuanMutasiController::class, 'rejectIdle'])
            ->middleware('permission:mutasi.approve')
            ->name('reject-idle');

        // ==========================================================
        // SERTIFIKASI MUTASI
        // ==========================================================
        // BA wajib saat menyelesaikan sertifikasi.
        // Dokumen Sertifikasi/Pendukung dapat diupload setelah status Selesai.
        Route::post('/{id}/sertifikasi', [PengajuanMutasiController::class, 'sertifikasi'])
            ->middleware('permission:mutasi.proses-idle')
            ->name('sertifikasi');

        Route::post('/{id}/dokumen-sertifikasi', [PengajuanMutasiController::class, 'uploadDokumenSertifikasi'])
            ->middleware('permission:mutasi.proses-idle')
            ->name('dokumen-sertifikasi');

        // Dokumen
        Route::delete('/{idMutasi}/dokumen/{idDokumen}', [PengajuanMutasiController::class, 'deleteDokumen'])
            ->middleware('permission:mutasi.create')
            ->name('delete-dokumen');

        // Lihat / tampilkan dokumen
        Route::get('/dokumen/{idDokumen}/download', [PengajuanMutasiController::class, 'downloadDokumen'])
            ->middleware('permission:mutasi.view')
            ->name('download-dokumen');
    });

    // ==========================================
    // DATA HARIAN
    // ==========================================
    Route::prefix('data-harian')->name('data-harian.')->middleware('permission:data-harian.view')->group(function () {
        Route::get('/',          [DataHarianController::class, 'index'])->name('index');
        Route::get('/tabel',     [DataHarianController::class, 'table'])->name('table');
        Route::get('/events',    [DataHarianController::class, 'events'])->name('events');
        Route::get('/{id}',      [DataHarianController::class, 'show'])->name('show');
    });

    // ==========================================
    // REKAP BULANAN
    // ==========================================
    Route::prefix('rekap-bulanan')->name('rekap-bulanan.')->middleware('permission:laporan.view')->group(function () {
        Route::get('/',           [RekapBulananController::class, 'index'])->name('index');
        Route::post('/generate',  [RekapBulananController::class, 'generate'])->name('generate');
        Route::get('/export',     [RekapBulananController::class, 'export'])->name('export');
    });

    // ==========================================
    // NOTIFIKASI - Semua role yang login
    // ==========================================
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::get('/',              [NotifikasiController::class, 'index'])->name('index');
        Route::post('/{id}/baca',    [NotifikasiController::class, 'baca'])->name('baca');
        Route::post('/baca-semua',   [NotifikasiController::class, 'bacaSemua'])->name('baca-semua');
        Route::delete('/{id}',       [NotifikasiController::class, 'hapus'])->name('hapus');
        Route::get('/api/jumlah',    [NotifikasiController::class, 'jumlahBelumDibaca'])->name('api.jumlah');
        Route::get('/api/terbaru',   [NotifikasiController::class, 'terbaru'])->name('api.terbaru');
    });

    // ==========================================
    // MANAJEMEN PENGGUNA
    // ⚠️ DIUBAH (Temuan 1): dari Route::resource() 1-permission-untuk-semua-aksi,
    // dipecah jadi middleware per-aksi sesuai permission granular yang sudah ada.
    // ==========================================
    Route::prefix('pengguna')->name('pengguna.')->group(function () {
        Route::get('/', [PenggunaController::class, 'index'])
            ->middleware('permission:user.view')
            ->name('index');

        Route::post('/', [PenggunaController::class, 'store'])
            ->middleware('permission:user.create')
            ->name('store');

        Route::put('/{id}', [PenggunaController::class, 'update'])
            ->middleware('permission:user.edit')
            ->name('update');

        Route::delete('/{id}', [PenggunaController::class, 'destroy'])
            ->middleware('permission:user.delete')
            ->name('destroy');
    });

    // ==========================================
    // MANAJEMEN ROLE & PERMISSION
    // ==========================================
    Route::prefix('roles')->name('roles.')->middleware('permission:user.change-role')->group(function () {
        Route::get('/',          [RoleController::class, 'index'])->name('index');
        Route::post('/',         [RoleController::class, 'store'])->name('store');
        Route::put('/{id}',      [RoleController::class, 'update'])->name('update');
        Route::delete('/{id}',   [RoleController::class, 'destroy'])->name('destroy');
    });

    // ==========================================
    // PENGATURAN - HANYA AFET Regional
    // ==========================================
    Route::middleware('ensure.regional')->group(function () {
        Route::get('/pengaturan',                     [PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::put('/pengaturan/threshold',           [PengaturanController::class, 'updateThreshold'])->name('pengaturan.threshold');

        // Bandara
        Route::post('/pengaturan/bandara',            [PengaturanController::class, 'storeBandara'])->name('pengaturan.bandara.store');
        Route::put('/pengaturan/bandara/{id}',        [PengaturanController::class, 'updateBandara'])->name('pengaturan.bandara.update');
        Route::delete('/pengaturan/bandara/{id}',     [PengaturanController::class, 'deleteBandara'])->name('pengaturan.bandara.delete');

        // Lokasi
        Route::post('/pengaturan/lokasi',             [PengaturanController::class, 'storeLokasi'])->name('pengaturan.lokasi.store');
        Route::put('/pengaturan/lokasi/{id}',         [PengaturanController::class, 'updateLokasi'])->name('pengaturan.lokasi.update');
        Route::delete('/pengaturan/lokasi/{id}',      [PengaturanController::class, 'deleteLokasi'])->name('pengaturan.lokasi.delete');

        // Kategori
        Route::post('/pengaturan/kategori',           [PengaturanController::class, 'storeKategori'])->name('pengaturan.kategori.store');
        Route::put('/pengaturan/kategori/{id}',       [PengaturanController::class, 'updateKategori'])->name('pengaturan.kategori.update');
        Route::delete('/pengaturan/kategori/{id}',    [PengaturanController::class, 'deleteKategori'])->name('pengaturan.kategori.delete');

        // Unit Kerja
        Route::post('/pengaturan/unit',               [PengaturanController::class, 'storeUnit'])->name('pengaturan.unit.store');
        Route::put('/pengaturan/unit/{id}',           [PengaturanController::class, 'updateUnit'])->name('pengaturan.unit.update');
        Route::delete('/pengaturan/unit/{id}',        [PengaturanController::class, 'deleteUnit'])->name('pengaturan.unit.delete');

        // ⚠️ BARU: Jenis Alat (dulu hardcode, sekarang dikelola dari sini)
        Route::post('/pengaturan/jenis',              [PengaturanController::class, 'storeJenis'])->name('pengaturan.jenis.store');
        Route::put('/pengaturan/jenis/{id}',          [PengaturanController::class, 'updateJenis'])->name('pengaturan.jenis.update');
        Route::delete('/pengaturan/jenis/{id}',       [PengaturanController::class, 'deleteJenis'])->name('pengaturan.jenis.delete');
    });

    // ==========================================
    // LAPORAN PERBAIKAN
    // ==========================================
    Route::prefix('laporan-perbaikan')->name('laporan-perbaikan.')->middleware('permission:laporan.view')->group(function () {
        Route::get('/',                  [LaporanPerbaikanWebController::class, 'index'])->name('index');
        Route::get('/export-excel',      [LaporanPerbaikanWebController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export-pdf',        [LaporanPerbaikanWebController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{id}',              [LaporanPerbaikanWebController::class, 'show'])->name('show');
    });

});