<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisAlat extends Model
{
    protected $table = 'jenis_alat';
    protected $primaryKey = 'id_jenis';

    protected $fillable = [
        'nama_jenis',
        'deskripsi',
    ];

    // ===== RELASI ALAT =====
    // Alat.jenis_alat masih kolom string (bukan foreign key) supaya data
    // lama & fitur yang sudah cocokkan berdasarkan nama (mis. cakupan_alat
    // di UnitKerja) tetap jalan tanpa migrasi ulang. Relasi di sini dicocokkan
    // lewat nama, bukan id.
    public function alat()
    {
        return $this->hasMany(Alat::class, 'jenis_alat', 'nama_jenis');
    }
}