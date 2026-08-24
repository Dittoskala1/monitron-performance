<?php
// database/seeders/UnitKerjaSeeder.php
//
// Unit kerja di CGK, sesuai daftar laporan bulanan per-unit yang sudah
// dipakai tim (BHS, CCIT, DANET, GES.., IT NON PUBLIK, PSIT, SSES.., SSIT).
//
// ⚠️ Cakupan jenis alat SSES sudah dikonfirmasi user (X-Ray, WTMD, HHMD,
// Body Scanner, Access Control). Cakupan 13 unit lainnya adalah TEBAKAN
// masuk akal berdasarkan nama unitnya — BUKAN data resmi dari user.
// Silakan koreksi lewat halaman Pengaturan > Unit Kerja kalau ternyata
// beda dari kenyataan di lapangan.
//
// ⚠️ Idempotent: pakai updateOrInsert berdasarkan (id_bandara, kode_unit),
// aman dijalankan ulang tanpa bikin duplikat.

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitKerjaSeeder extends Seeder
{
    public function run(): void
    {
        $bandaraCgk = DB::table('bandara')->where('kode_bandara', 'CGK')->value('id_bandara');

        if (!$bandaraCgk) {
            $this->command->warn('⚠️ Bandara CGK tidak ditemukan, UnitKerjaSeeder dilewati.');
            return;
        }

        $idLokasi = fn (string $namaLokasi) => DB::table('lokasi')
            ->where('id_bandara', $bandaraCgk)
            ->where('nama_lokasi', $namaLokasi)
            ->value('id_lokasi');

        $terminal1  = $idLokasi('Terminal 1');
        $terminal2  = $idLokasi('Terminal 2');
        $terminal3  = $idLokasi('Terminal 3');
        $nonTerminal = $idLokasi('Non Terminal');

        $cakupanSses = ['X-Ray', 'WTMD', 'HHMD', 'Body Scanner', 'Access Control'];
        $cakupanGes  = ['Fire Alarm', 'FIDS', 'Public Address', 'HVAC', 'Genset', 'Bird Deterrent'];

        $units = [
            ['kode_unit' => 'BHS',            'nama_unit' => 'Baggage Handling System',                              'id_lokasi' => null,        'cakupan_alat' => ['Conveyor Belt']],
            ['kode_unit' => 'CCIT',           'nama_unit' => 'Communication & IT',                                   'id_lokasi' => null,        'cakupan_alat' => ['CCTV', 'Radio Communication']],
            ['kode_unit' => 'DANET',          'nama_unit' => 'Data Network',                                         'id_lokasi' => null,        'cakupan_alat' => ['Network Device']],
            ['kode_unit' => 'GES-NT',         'nama_unit' => 'General Electronic Services - Non Terminal',           'id_lokasi' => $nonTerminal,'cakupan_alat' => $cakupanGes],
            ['kode_unit' => 'GES-T1',         'nama_unit' => 'General Electronic Services - Terminal 1',             'id_lokasi' => $terminal1,  'cakupan_alat' => $cakupanGes],
            ['kode_unit' => 'GES-T2',         'nama_unit' => 'General Electronic Services - Terminal 2',             'id_lokasi' => $terminal2,  'cakupan_alat' => $cakupanGes],
            ['kode_unit' => 'GES-T3',         'nama_unit' => 'General Electronic Services - Terminal 3',             'id_lokasi' => $terminal3,  'cakupan_alat' => $cakupanGes],
            ['kode_unit' => 'IT-NON-PUBLIK',  'nama_unit' => 'IT Non Publik',                                        'id_lokasi' => null,        'cakupan_alat' => ['Server/UPS', 'Network Device']],
            ['kode_unit' => 'PSIT',           'nama_unit' => 'PSIT',                                                 'id_lokasi' => null,        'cakupan_alat' => ['Genset', 'Server/UPS']],
            ['kode_unit' => 'SSES-NT',        'nama_unit' => 'Safety & Security Electronic Services - Non Terminal', 'id_lokasi' => $nonTerminal,'cakupan_alat' => $cakupanSses],
            ['kode_unit' => 'SSES-T1',        'nama_unit' => 'Safety & Security Electronic Services - Terminal 1',   'id_lokasi' => $terminal1,  'cakupan_alat' => $cakupanSses],
            ['kode_unit' => 'SSES-T2',        'nama_unit' => 'Safety & Security Electronic Services - Terminal 2',   'id_lokasi' => $terminal2,  'cakupan_alat' => $cakupanSses],
            ['kode_unit' => 'SSES-T3',        'nama_unit' => 'Safety & Security Electronic Services - Terminal 3',   'id_lokasi' => $terminal3,  'cakupan_alat' => $cakupanSses],
            ['kode_unit' => 'SSIT',           'nama_unit' => 'SSIT',                                                 'id_lokasi' => null,        'cakupan_alat' => ['Server/UPS', 'Access Control']],
        ];

        foreach ($units as $unit) {
            DB::table('unit_kerja')->updateOrInsert(
                ['id_bandara' => $bandaraCgk, 'kode_unit' => $unit['kode_unit']],
                [
                    'id_lokasi'    => $unit['id_lokasi'],
                    'nama_unit'    => $unit['nama_unit'],
                    'cakupan_alat' => json_encode($unit['cakupan_alat']),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );
        }

        $this->command->info('✅ ' . count($units) . ' unit kerja CGK siap dipakai (nama & cakupan alat bisa diedit lewat halaman Pengaturan).');
    }
}