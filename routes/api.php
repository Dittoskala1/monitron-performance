<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeknisiController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LaporanPerbaikanController;

// ==========================================
// PUBLIC ROUTES (Tanpa Login)
// ==========================================

// Login
Route::post('/login', [AuthController::class, 'login']);

// Register (Request Approval) - Pakai AuthController
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/check-status', [AuthController::class, 'checkStatus']);

// Forgot & Reset Password - Pakai AuthController
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ==========================================
// PROTECTED ROUTES (Harus Login)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // ==========================================
    // AUTH (Semua Role)
    // ==========================================
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ==========================================
    // TEKNISI ROUTES (Hanya Teknisi)
    // ==========================================
    Route::middleware('role:teknisi')->prefix('teknisi')->group(function () {
        
        // Alat
        Route::get('/alat', [TeknisiController::class, 'getAlat']);
        Route::get('/alat/scan/{barcode}', [TeknisiController::class, 'scanBarcode']);

        // Log Harian
        Route::post('/log', [TeknisiController::class, 'inputLog']);
        Route::put('/log/{id_log}', [TeknisiController::class, 'updateLog']);
        Route::get('/log/detail/{id_log}', [TeknisiController::class, 'getDetailLog']);
        Route::get('/log/{id_alat}/history', [TeknisiController::class, 'getHistory']);

        // Notifikasi
        Route::get('/notifikasi', [TeknisiController::class, 'getNotifikasi']);
        Route::patch('/notifikasi/{id}/baca', [TeknisiController::class, 'bacaNotifikasi']);

        // Laporan Perbaikan
        Route::get('/laporan', [LaporanPerbaikanController::class, 'index']);
        Route::post('/laporan', [LaporanPerbaikanController::class, 'store']);
        Route::get('/laporan/{id_laporan}', [LaporanPerbaikanController::class, 'show']);
        Route::patch('/laporan/{id_laporan}/selesai', [LaporanPerbaikanController::class, 'selesai']);
    });

    // ==========================================
    // ADMIN ROUTES (AFET Bandara & AFET Regional)
    // ==========================================
    Route::middleware('role:afet_bandara,afet_regional')->prefix('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // ==========================================
        // MANAGE USER REQUESTS (Approve/Reject)
        // ==========================================
        Route::get('/user-requests', [AdminController::class, 'getUserRequests']);
        Route::post('/user-requests/{id}/approve', [AdminController::class, 'approveUserRequest']);
        Route::post('/user-requests/{id}/reject', [AdminController::class, 'rejectUserRequest']);

        // ==========================================
        // CRUD BANDARA
        // ==========================================
        Route::get('/bandara', [AdminController::class, 'getBandara']);
        Route::post('/bandara', [AdminController::class, 'storeBandara']);
        Route::put('/bandara/{id}', [AdminController::class, 'updateBandara']);
        Route::delete('/bandara/{id}', [AdminController::class, 'deleteBandara']);

        // ==========================================
        // CRUD LOKASI
        // ==========================================
        Route::get('/lokasi', [AdminController::class, 'getLokasi']);
        Route::post('/lokasi', [AdminController::class, 'storeLokasi']);
        Route::put('/lokasi/{id}', [AdminController::class, 'updateLokasi']);
        Route::delete('/lokasi/{id}', [AdminController::class, 'deleteLokasi']);

        // ==========================================
        // CRUD KATEGORI
        // ==========================================
        Route::get('/kategori', [AdminController::class, 'getKategori']);
        Route::post('/kategori', [AdminController::class, 'storeKategori']);
        Route::put('/kategori/{id}', [AdminController::class, 'updateKategori']);
        Route::delete('/kategori/{id}', [AdminController::class, 'deleteKategori']);

        // ==========================================
        // CRUD ALAT
        // ==========================================
        Route::get('/alat', [AdminController::class, 'getAlat']);
        Route::post('/alat', [AdminController::class, 'storeAlat']);
        Route::put('/alat/{id}', [AdminController::class, 'updateAlat']);
        Route::delete('/alat/{id}', [AdminController::class, 'deleteAlat']);

        // ==========================================
        // CRUD PENGGUNA
        // ==========================================
        Route::get('/pengguna', [AdminController::class, 'getPengguna']);
        Route::post('/pengguna', [AdminController::class, 'storePengguna']);
        Route::put('/pengguna/{id}', [AdminController::class, 'updatePengguna']);
        Route::delete('/pengguna/{id}', [AdminController::class, 'deletePengguna']);

        // ==========================================
        // CRUD ROLES
        // ==========================================
        Route::get('/roles', [AdminController::class, 'getRoles']);

        // ==========================================
        // THRESHOLD
        // ==========================================
        Route::get('/threshold', [AdminController::class, 'getThreshold']);
        Route::put('/threshold', [AdminController::class, 'updateThreshold']);

        // ==========================================
        // REKAP & NOTIFIKASI
        // ==========================================
        Route::get('/rekap-bulanan', [AdminController::class, 'rekapBulanan']);
        Route::get('/notifikasi', [AdminController::class, 'getNotifikasi']);
        Route::patch('/notifikasi/{id}/baca', [AdminController::class, 'bacaNotifikasi']);

        // ==========================================
        // LOG HARIAN
        // ==========================================
        Route::get('/log-harian', [AdminController::class, 'getLogHarian']);
        Route::get('/log-harian/{id}', [AdminController::class, 'getDetailLogHarian']);

        // ==========================================
        // LAPORAN PERBAIKAN (Admin)
        // ==========================================
        Route::get('/laporan-perbaikan', [LaporanPerbaikanController::class, 'adminIndex']);
    });
});