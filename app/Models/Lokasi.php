<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    protected $table = 'lokasi';
    protected $primaryKey = 'id_lokasi';

    protected $fillable = [
        'id_bandara',
        'nama_lokasi',
        'keterangan'
    ];

    public function bandara()
    {
        return $this->belongsTo(Bandara::class, 'id_bandara', 'id_bandara');
    }

    public function alat()
    {
        return $this->hasMany(Alat::class, 'id_lokasi', 'id_lokasi');
    }
}