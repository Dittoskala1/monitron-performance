<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,   // 1. BUAT ROLE & PERMISSION DULU
            BandaraSeeder::class,           // 2. MASTER DATA
            KategoriAlatSeeder::class,
            LokasiSeeder::class,
            PenggunaSeeder::class,          // 3. PENGGUNA (SETELAH ROLE & PERMISSION)
            AlatSeeder::class,
            ThresholdSeeder::class,
            PengajuanIdleSeeder::class,
            DashboardSeeder::class,
        ]);
    }
}