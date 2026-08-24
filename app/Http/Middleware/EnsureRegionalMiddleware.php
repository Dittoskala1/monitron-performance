<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Pengguna;

class EnsureRegionalMiddleware
{
    /**
     * Hanya pengguna dengan permission 'pengaturan.manage' yang boleh lewat.
     * ⚠️ SEBELUMNYA: dikunci hardcode session('pengguna.role') !== 'afet_regional'.
     * Diganti jadi cek permission dinamis supaya konsisten dengan
     * PermissionMiddleware, dan bisa diatur lewat halaman Role & Permission
     * tanpa perlu ubah kode kalau suatu saat role lain butuh akses ini.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = session('pengguna');

        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $pengguna = Pengguna::find($user['id']);
        if (!$pengguna) {
            abort(403, 'Pengguna tidak ditemukan.');
        }

        if (!$pengguna->hasPermission('pengaturan.manage')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Admin Pusat.');
        }

        return $next($request);
    }
}