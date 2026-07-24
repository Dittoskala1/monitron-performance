<?php
// app/Http/Middleware/RoleWebMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleWebMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        $user = session('pengguna');

        if (!$user) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (!in_array($user['role'], $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}