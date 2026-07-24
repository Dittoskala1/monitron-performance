<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',    // ← TAMBAHKAN INI!
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            // Middleware bawaan
            // 'auth' => \App\Http\Middleware\Authenticate::class,
            // 'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
           // 'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
           // 'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
           // 'can' => \Illuminate\Auth\Middleware\Authorize::class,
           // 'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
           // 'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
           // 'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
           // 'signed' => \App\Http\Middleware\ValidateSignature::class,
           // 'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
           // 'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            // ==========================================
            // MIDDLEWARE CUSTOM
            // ==========================================
            'auth.web' => \App\Http\Middleware\AuthWebMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,  // ← HAPUS / KOMENTAR INI
            'role.web' => \App\Http\Middleware\RoleWebMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'ensure.regional' => \App\Http\Middleware\EnsureRegionalMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();