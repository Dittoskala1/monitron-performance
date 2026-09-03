<?php

return [
    'menus' => [
        // ==========================================
        // UTAMA
        // ==========================================
        [
            'label' => 'Dashboard',
            'icon' => 'bi bi-grid-1x2',
            'route' => 'admin.dashboard',
            'permission' => 'laporan.view',
            'group' => 'Utama',
        ],
        // ==========================================
        // DATA
        // ==========================================
        [
            'label' => 'Data Alat',
            'icon' => 'bi bi-cpu',
            'route' => 'admin.alat.index',
            'permission' => 'alat.view',
            'group' => 'Data',
        ],
        [
            'label' => 'Data Harian',
            'icon' => 'bi bi-calendar-check',
            'route' => 'admin.data-harian.index',
            'permission' => 'data-harian.view', // ⚠️ DIUBAH dari 'laporan.view'
            'group' => 'Data',
        ],
        // ==========================================
        // PERALATAN
        // ==========================================
        [
            'label' => 'Peralatan Idle',
            'icon' => 'bi bi-pause-circle',
            'route' => 'admin.peralatan-idle.index',
            'permission' => 'idle.view',
            'group' => 'Peralatan',
        ],
        [
            'label' => 'Peralatan Booking',
            'icon' => 'bi bi-calendar-check',
            'route' => 'admin.peralatan-booking.index',
            'permission' => 'booking.view', // ⚠️ DIPERBAIKI dari 'mutasi.view' — route aslinya cek booking.view
            'group' => 'Peralatan',
        ],
        [
            'label' => 'Peralatan Mutasi',
            'icon' => 'bi bi-arrow-left-right',
            'route' => 'admin.peralatan-mutasi.index',
            'permission' => 'mutasi.view',
            'group' => 'Peralatan',
        ],
        // ==========================================
        // REKAP & LAPORAN
        // ==========================================
        [
            'label' => 'Rekap Bulanan',
            'icon' => 'bi bi-file-earmark-bar-graph',
            'route' => 'admin.rekap-bulanan.index',
            'permission' => 'laporan.view',
            'group' => 'Rekap & Laporan',
        ],
        [
            'label' => 'Lap. Perbaikan',
            'icon' => 'bi bi-tools',
            'route' => 'admin.laporan-perbaikan.index',
            'permission' => 'laporan.view',
            'group' => 'Rekap & Laporan',
        ],
        // ==========================================
        // SISTEM
        // ==========================================
        [
            'label' => 'Notifikasi',
            'icon' => 'bi bi-bell',
            'route' => 'admin.notifikasi.index',
            'permission' => null,
            'group' => 'Sistem',
        ],
        [
            'label' => 'Pengguna',
            'icon' => 'bi bi-people',
            'route' => 'admin.pengguna.index',
            'permission' => 'user.view',
            'group' => 'Sistem',
        ],
        [
            'label' => 'Role & Permission',
            'icon' => 'bi bi-shield-lock',
            'route' => 'admin.roles.index',
            'permission' => 'user.change-role',
            'group' => 'Sistem',
        ],
        [
            'label' => 'Pengaturan',
            'icon' => 'bi bi-gear',
            'route' => 'admin.pengaturan.index',
            'permission' => 'pengaturan.manage',
            'group' => 'Sistem',
        ],
    ],
];