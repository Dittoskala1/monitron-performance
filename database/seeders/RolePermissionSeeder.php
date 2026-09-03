<?php
// database/seeders/RolePermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // HAPUS DATA LAMA (biar aman)
        // ⚠️ CATATAN: seeder ini TRUNCATE semua permission & assignment.
        // Kalau kamu sudah pernah atur ulang permission manual lewat
        // halaman Role & Permission (checkbox UI), jalankan seeder ini
        // akan MENIMPA balik ke default di bawah. Kalau sudah production
        // dan tidak mau ke-reset, jangan jalankan seeder ini lagi —
        // cukup tambahkan permission baru manual lewat UI / tinker.
        // ==========================================
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('user_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('✅ Data role & permission berhasil dibersihkan!');

        // ==========================================
        // 1. INSERT ROLES (7 role sesuai proses bisnis)
        // ==========================================
        $roles = [
            ['name' => 'Teknisi', 'slug' => 'teknisi', 'description' => 'Input data performance via mobile'],
            ['name' => 'AFET Bandara', 'slug' => 'afet_bandara', 'description' => 'Kelola idle & mutasi di bandara sendiri'],
            ['name' => 'AFET Regional', 'slug' => 'afet_regional', 'description' => 'Kelola semua bandara & approve mutasi'],
            ['name' => 'Divisi Head', 'slug' => 'div_head', 'description' => 'Mengetahui pengajuan idle di bandaranya (tanpa approve)'],
            ['name' => 'Dep Head', 'slug' => 'dep_head', 'description' => 'Approve pengajuan idle per unit kerja (mis. Dep Head SSES, Dep Head BHS, Dep Head SSIT)'],
            ['name' => 'General Manager KC', 'slug' => 'gm_kc', 'description' => 'Menerima notifikasi & laporan'],
            ['name' => 'Head Office', 'slug' => 'ho', 'description' => 'Menerima arsip sertifikasi & laporan strategis'],
            ['name' => 'CEO', 'slug' => 'ceo', 'description' => 'Menerima notifikasi final & laporan strategis'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert([
                'name' => $role['name'],
                'slug' => $role['slug'],
                'description' => $role['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ ' . count($roles) . ' roles berhasil dibuat!');

        // ==========================================
        // 2. INSERT PERMISSIONS
        // ==========================================
        $permissions = [
            // ---- DATA ALAT (4) ----
            ['name' => 'alat.view', 'display_name' => 'Lihat Data Alat', 'group' => 'data_alat'],
            ['name' => 'alat.create', 'display_name' => 'Tambah Data Alat', 'group' => 'data_alat'],
            ['name' => 'alat.edit', 'display_name' => 'Edit Data Alat', 'group' => 'data_alat'],
            ['name' => 'alat.delete', 'display_name' => 'Hapus Data Alat', 'group' => 'data_alat'],

            // ---- PERALATAN IDLE (6) ----
            ['name' => 'idle.view', 'display_name' => 'Lihat Daftar Idle', 'group' => 'peralatan_idle'],
            ['name' => 'idle.create', 'display_name' => 'Input Pengajuan Idle', 'group' => 'peralatan_idle'],
            ['name' => 'idle.approve', 'display_name' => 'Approve Pengajuan Idle', 'group' => 'peralatan_idle'],
            ['name' => 'idle.reject', 'display_name' => 'Reject Pengajuan Idle', 'group' => 'peralatan_idle'],
            ['name' => 'idle.ajukan-ulang', 'display_name' => 'Ajukan Ulang Idle', 'group' => 'peralatan_idle'],
            ['name' => 'idle.view-rejected', 'display_name' => 'Lihat Daftar Rejected', 'group' => 'peralatan_idle'],

            // ---- PERALATAN BOOKING (3) ----
            // ⚠️ BARU DITAMBAHKAN: sebelumnya akses booking hardcode di
            // route pakai role.web:afet_bandara, sekarang jadi permission
            // supaya bisa diatur dari halaman Role & Permission.
            ['name' => 'booking.view', 'display_name' => 'Lihat Daftar & Detail Booking', 'group' => 'peralatan_booking'],
            ['name' => 'booking.create', 'display_name' => 'Booking Alat', 'group' => 'peralatan_booking'],
            ['name' => 'booking.cancel', 'display_name' => 'Batalkan Booking', 'group' => 'peralatan_booking'],

            // ---- MUTASI (6) ----
            ['name' => 'mutasi.view', 'display_name' => 'Lihat Daftar Mutasi', 'group' => 'mutasi'],
            ['name' => 'mutasi.create', 'display_name' => 'Input Mapping Kebutuhan', 'group' => 'mutasi'],
            ['name' => 'mutasi.approve', 'display_name' => 'Approve Pengajuan Mutasi', 'group' => 'mutasi'],
            ['name' => 'mutasi.reject', 'display_name' => 'Reject Pengajuan Mutasi', 'group' => 'mutasi'],
            ['name' => 'mutasi.ajukan-ulang', 'display_name' => 'Ajukan Ulang Mutasi', 'group' => 'mutasi'],
            ['name' => 'mutasi.proses-idle', 'display_name' => 'Proses Pemastian Idle & Sertifikasi', 'group' => 'mutasi'],

            // ---- MANAJEMEN PENGGUNA (5) ----
            ['name' => 'user.view', 'display_name' => 'Lihat Daftar Pengguna', 'group' => 'manajemen_pengguna'],
            ['name' => 'user.create', 'display_name' => 'Tambah Pengguna', 'group' => 'manajemen_pengguna'],
            ['name' => 'user.edit', 'display_name' => 'Edit Pengguna', 'group' => 'manajemen_pengguna'],
            ['name' => 'user.delete', 'display_name' => 'Hapus Pengguna', 'group' => 'manajemen_pengguna'],
            ['name' => 'user.change-role', 'display_name' => 'Ubah Role Pengguna', 'group' => 'manajemen_pengguna'],

            // ---- NOTIFIKASI & LAPORAN (4) ----
            ['name' => 'notifikasi.terima-idle', 'display_name' => 'Terima Notifikasi Idle', 'group' => 'notifikasi'],
            ['name' => 'notifikasi.terima-mutasi', 'display_name' => 'Terima Notifikasi Mutasi', 'group' => 'notifikasi'],
            ['name' => 'laporan.view', 'display_name' => 'Lihat Dashboard Rekap', 'group' => 'laporan'],
            ['name' => 'laporan.view-semua-bandara', 'display_name' => 'Lihat Semua Bandara', 'group' => 'laporan'],

            // ---- DATA HARIAN (1) ----
            ['name' => 'data-harian.view', 'display_name' => 'Lihat Data Harian', 'group' => 'data_harian'],

            // ---- PENGATURAN (1) ----
            // ⚠️ BARU DITAMBAHKAN: sebelumnya halaman /pengaturan hardcode
            // cek role afet_regional di EnsureRegionalMiddleware, sekarang
            // jadi permission dinamis biar konsisten sama modul lain.
            ['name' => 'pengaturan.manage', 'display_name' => 'Kelola Pengaturan Sistem', 'group' => 'pengaturan'],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->insert([
                'name' => $perm['name'],
                'display_name' => $perm['display_name'],
                'group' => $perm['group'],
                'description' => $perm['display_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ ' . count($permissions) . ' permissions berhasil dibuat!');

        // ==========================================
        // 3. ASSIGN PERMISSIONS KE ROLES
        // ==========================================
        $this->assignPermissions();

        // ==========================================
        // 4. RE-LINK USER KE ROLE (user_has_roles)
        // ⚠️ BARU DITAMBAHKAN: karena step 'HAPUS DATA LAMA' di atas
        // truncate tabel user_has_roles, semua user existing kehilangan
        // link ke role barunya (walau kolom pengguna.role masih terisi).
        // Tanpa langkah ini, semua pengecekan middleware `permission:...`
        // akan selalu gagal (403) setelah seeder dijalankan ulang.
        // ==========================================
        $this->relinkUserRoles();
    }

    /**
     * Hubungkan kembali semua user existing ke role barunya di
     * tabel pivot user_has_roles, berdasarkan kolom pengguna.role (slug).
     */
    private function relinkUserRoles(): void
    {
        $penggunaList = DB::table('pengguna')->select('id_pengguna', 'role')->get();

        $totalRelink = 0;
        $gagal = 0;

        foreach ($penggunaList as $pengguna) {
            $roleId = DB::table('roles')->where('slug', $pengguna->role)->value('id');

            if (!$roleId) {
                $this->command->warn("⚠️ User id_pengguna={$pengguna->id_pengguna} punya role '{$pengguna->role}' yang tidak ditemukan di tabel roles. Dilewati.");
                $gagal++;
                continue;
            }

            DB::table('user_has_roles')->insertOrIgnore([
                'user_id' => $pengguna->id_pengguna,
                'role_id' => $roleId,
            ]);
            $totalRelink++;
        }

        $this->command->info("✅ {$totalRelink} user berhasil di-relink ke role-nya!");
        if ($gagal > 0) {
            $this->command->warn("⚠️ {$gagal} user gagal di-relink (role tidak ditemukan). Cek isian kolom pengguna.role.");
        }
    }

    private function assignPermissions(): void
    {
        $rolePermissions = [
            // ==========================================
            // TEKNISI (Mobile only - minimal)
            // ==========================================
            'teknisi' => [],

            // ==========================================
            // AFET BANDARA
            // ==========================================
            'afet_bandara' => [
                'alat.view', 'alat.create', 'alat.edit', 'alat.delete',
                'idle.view', 'idle.create', 'idle.ajukan-ulang', 'idle.view-rejected',
                'booking.view', 'booking.create', 'booking.cancel',
                'mutasi.view', 'mutasi.create', 'mutasi.ajukan-ulang',
                'mutasi.proses-idle',
                'user.view', 'user.create', 'user.edit', 'user.delete',
                'notifikasi.terima-idle', 'notifikasi.terima-mutasi',
                'laporan.view',
                'data-harian.view',
            ],

            // ==========================================
            // AFET REGIONAL
            // ==========================================
            'afet_regional' => [
                'alat.view', 'alat.create', 'alat.edit', 'alat.delete',
                'idle.view', 'idle.approve', 'idle.reject',
                'booking.view',
                'mutasi.view', 'mutasi.approve', 'mutasi.reject',
                'mutasi.proses-idle',
                'user.view', 'user.create', 'user.edit', 'user.delete', 'user.change-role',
                'notifikasi.terima-idle', 'notifikasi.terima-mutasi',
                'laporan.view', 'laporan.view-semua-bandara',
                'data-harian.view',
                'pengaturan.manage',
            ],

            // ==========================================
            // DIVISI HEAD
            // ⚠️ DIUBAH: 'idle.approve' & 'idle.reject' ditambahkan.
            // Div Head sekarang bukan cuma "mengetahui" — di bandara yang
            // belum punya struktur Dep Head, dialah approver tahap 1
            // (lihat Pengguna::approverTahap1IdleRole()). Tanpa 2 permission
            // ini, middleware `permission:idle.approve`/`idle.reject` di
            // routes/web.php akan 403 duluan sebelum sempat sampai ke
            // controller — jadi fallback Div Head gak akan pernah bisa
            // dipakai walau logic di controller sudah benar. Di bandara yang
            // masih punya Dep Head (mis. CGK), Div Head tetap gak bisa
            // approve sungguhan karena PengajuanIdleController::approve()/
            // reject() tetap mencocokkan role dengan approverTahap1IdleRole()
            // per pengajuan — permission ini cuma gerbang kasar di level role.
            // ==========================================
            'div_head' => [
                'alat.view',
                'idle.view', 'idle.approve', 'idle.reject',
                'notifikasi.terima-idle',
                'laporan.view',
                'data-harian.view',
            ],

            // ==========================================
            // DEP HEAD (per unit kerja, approve tahap 1 pengajuan idle)
            // ==========================================
            'dep_head' => [
                'alat.view', 'alat.create', 'alat.edit', 'alat.delete',
                'idle.view', 'idle.approve', 'idle.reject',
                'notifikasi.terima-idle',
                'laporan.view',
                'data-harian.view',
            ],

            // ==========================================
            // GM KC
            // ==========================================
            'gm_kc' => [
                'mutasi.view', 'mutasi.create', 'mutasi.approve', 'mutasi.reject', 'mutasi.ajukan-ulang',
                'mutasi.proses-idle',
                'notifikasi.terima-idle', 'notifikasi.terima-mutasi',
                'laporan.view',
            ],

            // ==========================================
            // HEAD OFFICE
            // ==========================================
            'ho' => [
                'idle.view',
                'booking.view',
                'mutasi.view',
                'user.view',
                'notifikasi.terima-idle', 'notifikasi.terima-mutasi',
                'laporan.view', 'laporan.view-semua-bandara',
            ],

            // ==========================================
            // CEO
            // ==========================================
            'ceo' => [
                'booking.view',
                'mutasi.view', 'mutasi.create', 'mutasi.approve', 'mutasi.reject', 'mutasi.ajukan-ulang',
                'mutasi.proses-idle',
                'notifikasi.terima-mutasi',
                'user.view',
                'laporan.view', 'laporan.view-semua-bandara',
            ],
        ];

        $totalAssign = 0;

        foreach ($rolePermissions as $roleSlug => $permissionNames) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

            if (!$roleId) {
                $this->command->warn("⚠️ Role '{$roleSlug}' tidak ditemukan!");
                continue;
            }

            foreach ($permissionNames as $permName) {
                $permId = DB::table('permissions')->where('name', $permName)->value('id');

                if ($permId) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permId,
                    ]);
                    $totalAssign++;
                } else {
                    $this->command->warn("⚠️ Permission '{$permName}' tidak ditemukan!");
                }
            }
        }

        $this->command->info('✅ ' . $totalAssign . ' permission assignments berhasil dibuat!');
        $this->command->info('🎉 Seeder RolePermissionSeeder selesai!');
    }
}