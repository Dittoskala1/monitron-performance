<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BandaraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
 {
    DB::table('bandara')->insert([
        ['nama_bandara' => 'Soekarno-Hatta', 'kode_bandara' => 'CGK', 'lokasi' => 'Tangerang', 'created_at' => now(), 'updated_at' => now()],
        ['nama_bandara' => 'Husein Sastranegara', 'kode_bandara' => 'BDO', 'lokasi' => 'Bandung', 'created_at' => now(), 'updated_at' => now()],
        ['nama_bandara' => 'Halim Perdanakusuma', 'kode_bandara' => 'HLP', 'lokasi' => 'Jakarta Timur', 'created_at' => now(), 'updated_at' => now()],
        ['nama_bandara' => 'Kertajati', 'kode_bandara' => 'KJT', 'lokasi' => 'Majalengka', 'created_at' => now(), 'updated_at' => now()],
    ]);
 }
}
