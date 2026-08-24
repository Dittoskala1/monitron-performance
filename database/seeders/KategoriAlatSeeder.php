<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriAlatSeeder extends Seeder
{
    /**
     * Hanya 2 kategori alat: Faskampen & DBU.
     * Jenis alat spesifik (X-Ray, WTMD, Fire Alarm, dst) disimpan
     * di kolom `jenis_alat` pada tabel `alat`, BUKAN sebagai kategori
     * terpisah. Lihat KategoriHelper::resolveKategoriId() untuk mapping
     * jenis alat -> kategori.
     */
    public function run(): void
    {
        $kategoriList = [
            [
                'nama_kategori' => 'Faskampen',
                'deskripsi' => 'Fasilitas Keamanan Penerbangan: X-Ray, WTMD, HHMD, ETD, CCTV, Body Scanner, Access Control',
            ],
            [
                'nama_kategori' => 'DBU',
                'deskripsi' => 'Fasilitas Dukungan Bandar Udara (non-keamanan): Fire Alarm, Radio Communication, FIDS, Public Address, Bird Deterrent, dan fasilitas penunjang operasional lainnya',
            ],
        ];

        foreach ($kategoriList as $kategori) {
            DB::table('kategori_alat')->updateOrInsert(
                ['nama_kategori' => $kategori['nama_kategori']],
                [
                    'deskripsi' => $kategori['deskripsi'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ 2 kategori alat (Faskampen & DBU) siap dipakai.');
    }
}