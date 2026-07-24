<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Models\UserRequest;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ================================================================
    // LOGIN
    // ================================================================

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $pengguna = Pengguna::with(['bandara', 'lokasi', 'roles'])
            ->where('username', $request->username)
            ->first();

        if (!$pengguna || !Hash::check($request->password, $pengguna->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah'
            ], 401);
        }

        // Hapus token lama
        $pengguna->tokens()->delete();

        // Buat token baru
        $deviceName = $request->device_name ?? 'mobile_app';
        $token = $pengguna->createToken($deviceName)->plainTextToken;

        // Ambil role user
        $role = $pengguna->roles()->first();

        // Ambil semua permissions user
        $permissions = [];
        foreach ($pengguna->roles as $roleItem) {
            foreach ($roleItem->permissions as $perm) {
                $permissions[] = $perm->name;
            }
        }
        $permissions = array_unique($permissions);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $pengguna->id_pengguna,
                    'nama' => $pengguna->nama,
                    'username' => $pengguna->username,
                    'role' => $role->slug ?? $pengguna->role,
                    'role_name' => $role->name ?? null,
                    'id_bandara' => $pengguna->id_bandara,
                    'nama_bandara' => $pengguna->bandara->nama_bandara ?? null,
                    'id_lokasi' => $pengguna->id_lokasi,
                    'nama_lokasi' => $pengguna->lokasi->nama_lokasi ?? null,
                ],
                'permissions' => $permissions
            ]
        ]);
    }

    // ================================================================
    // LOGOUT
    // ================================================================

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    // ================================================================
    // PROFILE
    // ================================================================

    public function me(Request $request)
    {
        $pengguna = $request->user()->load(['bandara', 'lokasi', 'roles']);

        $role = $pengguna->roles()->first();

        $permissions = [];
        foreach ($pengguna->roles as $roleItem) {
            foreach ($roleItem->permissions as $perm) {
                $permissions[] = $perm->name;
            }
        }
        $permissions = array_unique($permissions);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pengguna->id_pengguna,
                'nama' => $pengguna->nama,
                'username' => $pengguna->username,
                'role' => $role->slug ?? $pengguna->role,
                'role_name' => $role->name ?? null,
                'id_bandara' => $pengguna->id_bandara,
                'nama_bandara' => $pengguna->bandara->nama_bandara ?? null,
                'id_lokasi' => $pengguna->id_lokasi,
                'nama_lokasi' => $pengguna->lokasi->nama_lokasi ?? null,
                'permissions' => $permissions
            ]
        ]);
    }

    // ================================================================
    // REGISTER (REQUEST APPROVAL)
    // ================================================================

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|unique:pengguna,username|unique:user_requests,username',
            // 'email' => 'nullable|email|unique:pengguna,email|unique:user_requests,email',
            'password' => 'required|min:8|confirmed',
            'id_bandara' => 'nullable|exists:bandara,id_bandara',
            'id_lokasi' => 'nullable|exists:lokasi,id_lokasi',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $userRequest = UserRequest::create([
            'nama' => $request->nama,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'id_bandara' => $request->id_bandara,
            'id_lokasi' => $request->id_lokasi,
            'reason' => $request->reason,
            'status' => 'pending',
            'role_requested' => 'teknisi'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permintaan registrasi berhasil dikirim. Tunggu approval admin.',
            'data' => [
                'id' => $userRequest->id,
                'nama' => $userRequest->nama,
                'username' => $userRequest->username,
                'status' => $userRequest->status,
                'created_at' => $userRequest->created_at
            ]
        ], 201);
    }

    // ================================================================
    // CEK STATUS REGISTRASI
    // ================================================================

    public function checkStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $userRequest = UserRequest::where('username', $request->username)->first();

        if (!$userRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Username tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $userRequest->status,
                'rejection_reason' => $userRequest->rejection_reason,
                'approved_at' => $userRequest->approved_at,
                'rejected_at' => $userRequest->rejected_at
            ]
        ]);
    }

    // ================================================================
    // FORGOT PASSWORD
    // ================================================================

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|exists:pengguna,username'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Username tidak ditemukan',
                'errors' => $validator->errors()
            ], 404);
        }

        $user = Pengguna::where('username', $request->username)->first();
        $email = $user->email ?? $user->username . '@monitoring.com';
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token,
                'created_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Token reset password berhasil dibuat.',
            'data' => [
                'token' => $token,
                'username' => $request->username
            ]
        ]);
    }

    // ================================================================
    // RESET PASSWORD
    // ================================================================

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|exists:pengguna,username',
            'password' => 'required|min:8|confirmed',
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Pengguna::where('username', $request->username)->first();
        $email = $user->email ?? $user->username . '@monitoring.com';

        $reset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $request->token)
            ->first();

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'Token reset password tidak valid atau sudah kadaluarsa'
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login dengan password baru.'
        ]);
    }
}