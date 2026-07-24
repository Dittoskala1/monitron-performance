<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThresholdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('threshold')->insert([
            [
                'nilai_baik' => 90.00,
                'nilai_warning' => 80.00,
                'nilai_buruk' => 0.00,
                'keterangan' => 'Standar threshold performa alat',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
