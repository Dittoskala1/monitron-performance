<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRegionalMiddleware
{
    /**
     * Hanya AFET Regional (admin pusat) yang boleh lewat.
     * AFET Bandara akan ditolak dengan 403.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (session('pengguna.role') !== 'afet_regional') {
            abort(403, 'Halaman ini hanya dapat diakses oleh Admin Pusat.');
        }

        return $next($request);
    }
}