<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bandara extends Model
{
    protected $table = 'bandara';
    protected $primaryKey = 'id_bandara';

    protected $fillable = [
        'nama_bandara',
        'kode_bandara',
        'lokasi',
        'jam_operasional'
    ];

    public function lokasi()
    {
        return $this->hasMany(Lokasi::class, 'id_bandara', 'id_bandara');
    }

    public function pengguna()
    {
        return $this->hasMany(Pengguna::class, 'id_bandara', 'id_bandara');
    }
}