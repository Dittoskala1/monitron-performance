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
            'dep_head', // ⚠️ BARU
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
            'id_unit'     => $pengguna->id_unit ?? null,
            'nama_unit'   => $pengguna->unit->nama_unit ?? null,
            'login_at'    => now()->toDateTimeString(),
        ]]);

        // Redirect ke dashboard
        return redirect()->route('admin.dashboard');
    }

    /**
     * Tampilkan halaman "Buat Password Baru"
     * GET /lupa-password
     */
    public function showForgotPassword()
    {
        // Jika sudah login, redirect ke dashboard
        if (session()->has('pengguna')) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.forgot-password');
    }

    /**
     * Proses set password baru
     * POST /lupa-password
     *
     * ⚠️ Sistem ini belum punya kolom email/OTP untuk verifikasi identitas,
     * jadi reset password di sini murni berdasarkan username yang cocok.
     */
    public function resetPassword(Request $request)
    {
        // Validasi input
        $request->validate([
            'username' => 'required|string|exists:pengguna,username',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'username.exists' => 'Username tidak ditemukan.',
        ]);

        // Cari user berdasarkan username
        $pengguna = Pengguna::where('username', $request->username)->first();

        // Update password
        $pengguna->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('login')
            ->with('success', 'Password berhasil diganti. Silakan login dengan password baru Anda.');
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