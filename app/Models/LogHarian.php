<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogHarian extends Model
{
    protected $table = 'log_harian';
    protected $primaryKey = 'id_log';

    protected $fillable = [
        'id_alat',
        'id_pengguna',
        'tanggal',
        'jam_operasional',
        'jam_terputus',
        'kondisi',
        'catatan',
        'detail_lokasi',  
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