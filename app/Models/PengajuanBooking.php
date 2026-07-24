<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanBooking extends Model
{
    protected $table = 'pengajuan_booking';
    protected $primaryKey = 'id_booking';

    protected $fillable = [
        'id_pengajuan_idle',
        'kode_alat_snapshot',
        'nama_alat_snapshot',
        'id_pengguna_pemesan',
        'id_bandara_penerima',
        'status',
        'tanggal_booking',
    ];

    protected $casts = [
        'tanggal_booking' => 'datetime',
    ];

    public function pengajuanIdle()
    {
        return $this->belongsTo(PengajuanIdle::class, 'id_pengajuan_idle', 'id_pengajuan');
    }

    public function pemesan()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_pemesan', 'id_pengguna');
    }

    public function bandaraPenerima()
    {
        return $this->belongsTo(Bandara::class, 'id_bandara_penerima', 'id_bandara');
    }

    public function mutasi()
    {
        return $this->hasOne(PengajuanMutasi::class, 'id_booking', 'id_booking');
    }
}