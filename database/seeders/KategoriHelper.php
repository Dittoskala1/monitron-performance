<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

class KategoriHelper
{
    // Semua jenis alat yang masuk payung "Faskampen"
    private const JENIS_FASKAMPEN = [
        'X-RAY', 'WTMD', 'HHMD', 'ETD', 'CCTV', 'BODY SCANNER', 'ACCESS CONTROL',
    ];

    /**
     * Ambil id_kategori (Faskampen / DBU) berdasarkan nama jenis alat.
     * Selain jenis-jenis di JENIS_FASKAMPEN, semua dianggap DBU.
     */
    public static function resolveKategoriId(string $jenisAlat): int
    {
        static $cache = [];

        $isFaskampen = in_array(strtoupper($jenisAlat), self::JENIS_FASKAMPEN, true);
        $namaKategori = $isFaskampen ? 'Faskampen' : 'DBU';

        if (!isset($cache[$namaKategori])) {
            $cache[$namaKategori] = DB::table('kategori_alat')
                ->where('nama_kategori', $namaKategori)
                ->value('id_kategori');

            if (!$cache[$namaKategori]) {
                throw new \RuntimeException(
                    "Kategori '{$namaKategori}' belum ada. Jalankan KategoriAlatSeeder dulu."
                );
            }
        }

        return $cache[$namaKategori];
    }
}