<?php
// app/Http/Controllers/Web/AuthWebController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Pengguna;

class AuthWebController extends Controller
{
    /**
     * Tampilkan halaman login
     * GET /
     */
    public function showLogin()
    {
        // Jika sudah login, redirect ke dashboard
        if (session()->has('pengguna')) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('auth.login');
    }

    /**
     * Proses login
     * POST /login
     */
    public function login(Request $request)
    {
        // Validasi input
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Cari user berdasarkan username
        $pengguna = Pengguna::where('username', $request->username)->first();

        // Cek user & password
        if (!$pengguna || !Hash::check($request->password, $pengguna->password)) {
            return back()->withErrors([
                'username' => 'Username atau password salah.'
            ])->withInput();
        }

        // ==========================================
        // ROLE YANG BOLEH AKSES WEB ADMIN
        // ==========================================
        $allowedRoles = [
            'afet_bandara',
            'afet_regional',
            'div_head',
            'gm_kc',
            'ho',
            'ceo',
        ];

        // Cek apakah role diizinkan akses web
        if (!in_array($pengguna->role, $allowedRoles)) {
            return back()->withErrors([
                'username' => 'Role "' . $pengguna->role . '" tidak diperbolehkan mengakses sistem ini. Silakan gunakan aplikasi mobile.'
            ])->withInput();
        }

        // ==========================================
        // REGENERATE SESSION ID (Keamanan)
        // ==========================================
        $request->session()->regenerate();

        // ==========================================
        // SIMPAN DATA USER KE SESSION
        // ==========================================
        session(['pengguna' => [
            'id'          => $pengguna->id_pengguna,
            'nama'        => $pengguna->nama,
            'username'    => $pengguna->username,
            'role'        => $pengguna->role,
            'role_name'   => $pengguna->roles()->first()->name ?? $pengguna->role,
            'id_bandara'  => $pengguna->id_bandara,
            'nama_bandara' => $pengguna->bandara->nama_bandara ?? null,
            'id_lokasi'   => $pengguna->id_lokasi ?? null,
            'nama_lokasi' => $pengguna->lokasi->nama_lokasi ?? null,
            'login_at'    => now()->toDateTimeString(),
        ]]);

        // Redirect ke dashboard
        return redirect()->route('admin.dashboard');
    }

    /**
     * Proses logout
     * POST /logout
     */
    public function logout(Request $request)
    {
        // Hapus session
        session()->forget('pengguna');
        
        // Invalidate session (keamanan)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil logout.');
    }

    /**
     * Cek apakah user sudah login (helper)
     */
    public function checkAuth()
    {
        if (session()->has('pengguna')) {
            return response()->json([
                'status' => 'authenticated',
                'user' => session('pengguna')
            ]);
        }

        return response()->json([
            'status' => 'unauthenticated'
        ], 401);
    }
}