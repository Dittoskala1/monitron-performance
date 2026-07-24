<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bandara;
use App\Models\Lokasi;

class PengajuanIdleSeeder extends Seeder
{
    /**
     * Membuat 1 lokasi "Unused" di setiap bandara yang sudah ada,
     * kalau bandara itu belum punya lokasi "Unused".
     */
    public function run(): void
    {
        $bandaraList = Bandara::all();

        foreach ($bandaraList as $bandara) {
            $sudahAda = Lokasi::where('id_bandara', $bandara->id_bandara)
                ->where('nama_lokasi', 'Unused')
                ->exists();

            if (! $sudahAda) {
                Lokasi::create([
                    'id_bandara'  => $bandara->id_bandara,
                    'nama_lokasi' => 'Unused',
                    'keterangan'  => 'Lokasi untuk fasilitas & peralatan yang tidak digunakan (idle).',
                ]);

                $this->command->info("Lokasi 'Unused' dibuat untuk bandara: {$bandara->nama_bandara}");
            } else {
                $this->command->info("Bandara {$bandara->nama_bandara} sudah punya lokasi 'Unused', dilewati.");
            }
        }
    }
}