<?php

if (!function_exists('jamMenit')) {
    function jamMenit($decimalJam): string
    {
        if ($decimalJam === null || $decimalJam < 0) return '0 jam 0 menit';

        $totalMenit = (int) round($decimalJam * 60);
        $jam        = (int) floor($totalMenit / 60);
        $menit      = $totalMenit % 60;

        return "{$jam} jam {$menit} menit";
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission(string $permission): bool
    {
        $user = session('pengguna');
        if (!$user) return false;

        $pengguna = \App\Models\Pengguna::find($user['id']);
        return $pengguna ? $pengguna->hasPermission($permission) : false;
    }
}

if (!function_exists('hasRole')) {
    function hasRole(string $roleSlug): bool
    {
        $user = session('pengguna');
        if (!$user) return false;

        $pengguna = \App\Models\Pengguna::find($user['id']);
        return $pengguna ? $pengguna->hasRole($roleSlug) : false;
    }
}

// ==========================================
// 🔥 getMenus() HARUS TERPISAH!
// ==========================================
if (!function_exists('getMenus')) {
    function getMenus()
    {
        $userId = session('pengguna.id');
        $pengguna = \App\Models\Pengguna::find($userId);

        if (!$pengguna) {
            return [];
        }

        $menus = config('menus.menus', []);

        $filtered = array_filter($menus, function ($menu) use ($pengguna) {
            if (is_null($menu['permission'])) {
                return true;
            }
            return $pengguna->hasPermission($menu['permission']);
        });

        $grouped = [];
        foreach ($filtered as $menu) {
            $grouped[$menu['group']][] = $menu;
        }

        return $grouped;
    }
}