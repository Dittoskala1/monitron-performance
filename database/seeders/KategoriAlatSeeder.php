<?php
 
namespace Database\Seeders;
 
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
 
class KategoriAlatSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('kategori_alat')->insert([
            ['nama_kategori' => 'Fasilitas Keamanan Penerbangan', 'deskripsi' => 'X-Ray, WTMD, HHMD dan peralatan keamanan penerbangan', 'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Fasilitas Komunikasi & Navigasi', 'deskripsi' => 'Peralatan komunikasi dan navigasi penerbangan', 'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Fasilitas Mekanikal & Elektrikal', 'deskripsi' => 'Peralatan mekanikal dan elektrikal bandara', 'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Fasilitas IT & CCTV', 'deskripsi' => 'Peralatan IT, CCTV dan sistem pengawasan', 'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Fasilitas Penunjang Operasional', 'deskripsi' => 'Peralatan penunjang operasional bandara', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}