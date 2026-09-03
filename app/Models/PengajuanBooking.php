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

    // FK id_booking sekarang ada di mutasi_alat (1 pengajuan mutasi bisa mencakup
    // banyak alat/booking sekaligus), bukan lagi langsung di pengajuan_mutasi.
    public function detailMutasi()
    {
        return $this->hasOne(MutasiAlat::class, 'id_booking', 'id_booking');
    }

    public function pengajuanMutasi()
    {
        return $this->hasOneThrough(
            PengajuanMutasi::class,
            MutasiAlat::class,
            'id_booking',              // FK di mutasi_alat yang mengarah ke booking ini
            'id_pengajuan_mutasi',     // FK di pengajuan_mutasi
            'id_booking',              // local key di pengajuan_booking
            'id_pengajuan_mutasi'      // local key di mutasi_alat
        );
    }
}