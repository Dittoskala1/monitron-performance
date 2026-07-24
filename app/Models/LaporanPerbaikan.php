<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LaporanPerbaikan;

class LaporanPerbaikan extends Model
{
    protected $table = 'laporan_perbaikan';
    protected $primaryKey = 'id_laporan';

    protected $fillable = [
        'id_alat',
        'id_pengguna',
        'nama_peralatan',
        'kategori_kerusakan',
        'bagian_kerusakan',
        'tindakan',
        'tanggal_kerusakan',
        'tanggal_selesai',
        'jam_terputus',
        'keterangan',
        'detail_lokasi',
        'status',
    ];

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'id_alat', 'id_alat');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}