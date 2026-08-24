<?php
// app/Http/Middleware/AuthWebMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuthWebMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Cek apakah user sudah login
        if (!session('pengguna')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // ==========================================
        // CEK EXPIRED SESSION (2 JAM)
        // ==========================================   
        $user = session('pengguna');
        
        if (isset($user['login_at'])) {
            $loginTime = Carbon::parse($user['login_at']);
            $expiredTime = $loginTime->addHours(2);
            
            if (now()->greaterThan($expiredTime)) {
                session()->forget('pengguna');
                return redirect()->route('login')->with('error', 'Session Anda telah berakhir (8 jam). Silakan login kembali.');
            }
        }

        return $next($request);
    }
}