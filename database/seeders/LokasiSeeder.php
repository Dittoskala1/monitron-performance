<?php
 
namespace Database\Seeders;
 
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
 
class LokasiSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('lokasi')->insert([
            // CGK - 3 Terminal + Non Terminal (id_bandara: 1)
            ['id_bandara' => 1, 'nama_lokasi' => 'Terminal 1',    'keterangan' => 'Terminal Domestik',                    'created_at' => now(), 'updated_at' => now()], // id_lokasi: 1
            ['id_bandara' => 1, 'nama_lokasi' => 'Terminal 2',    'keterangan' => 'Terminal Domestik & Internasional',    'created_at' => now(), 'updated_at' => now()], // id_lokasi: 2
            ['id_bandara' => 1, 'nama_lokasi' => 'Terminal 3',    'keterangan' => 'Terminal Internasional',               'created_at' => now(), 'updated_at' => now()], // id_lokasi: 3
            ['id_bandara' => 1, 'nama_lokasi' => 'Non Terminal',  'keterangan' => 'Area Non Terminal CGK',                'created_at' => now(), 'updated_at' => now()], // id_lokasi: 4
 
            // BDO - 1 Terminal + Non Terminal (id_bandara: 2)
            ['id_bandara' => 2, 'nama_lokasi' => 'Terminal 1',    'keterangan' => 'Terminal Utama',                       'created_at' => now(), 'updated_at' => now()], // id_lokasi: 5
            ['id_bandara' => 2, 'nama_lokasi' => 'Non Terminal',  'keterangan' => 'Area Non Terminal BDO',                'created_at' => now(), 'updated_at' => now()], // id_lokasi: 6
 
            // HLP - 1 Terminal + Non Terminal (id_bandara: 3)
            ['id_bandara' => 3, 'nama_lokasi' => 'Terminal 1',    'keterangan' => 'Terminal Utama',                       'created_at' => now(), 'updated_at' => now()], // id_lokasi: 7
            ['id_bandara' => 3, 'nama_lokasi' => 'Non Terminal',  'keterangan' => 'Area Non Terminal HLP',                'created_at' => now(), 'updated_at' => now()], // id_lokasi: 8
 
            // KJT - 1 Terminal + Non Terminal (id_bandara: 4)
            ['id_bandara' => 4, 'nama_lokasi' => 'Terminal 1',    'keterangan' => 'Terminal Utama',                       'created_at' => now(), 'updated_at' => now()], // id_lokasi: 9
            ['id_bandara' => 4, 'nama_lokasi' => 'Non Terminal',  'keterangan' => 'Area Non Terminal KJT',                'created_at' => now(), 'updated_at' => now()], // id_lokasi: 10
        ]);
    }
}