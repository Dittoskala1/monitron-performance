<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilBulanan extends Model
{
    protected $table = 'hasil_bulanan';
    protected $primaryKey = 'id_hasil_bulanan';

    protected $fillable = [
        'id_alat',
        'bulan',
        'tahun',
        'detail_lokasi', 
        'rata_performa',
        'total_jam_operasional',
        'total_jam_terputus',
        'status'
    ];
    public function alat()
    {
        return $this->belongsTo(Alat::class, 'id_alat', 'id_alat');
    }
}