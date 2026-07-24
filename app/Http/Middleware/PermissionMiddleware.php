<?php
// app/Http/Middleware/PermissionMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Pengguna;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = session('pengguna');

        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $pengguna = Pengguna::find($user['id']);
        if (!$pengguna) {
            abort(403, 'Pengguna tidak ditemukan.');
        }

        if (!$pengguna->hasPermission($permission)) {
            abort(403, "Anda tidak memiliki izin untuk melakukan aksi ini.");
        }

        return $next($request);
    }
}