<?php
// app/Http/Middleware/RoleMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $userRole = $user->roles()->first();
        $roleSlug = $userRole->slug ?? $user->role;

        if (!in_array($roleSlug, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Role "' . $roleSlug . '" tidak memiliki akses.'
            ], 403);
        }

        return $next($request);
    }
}