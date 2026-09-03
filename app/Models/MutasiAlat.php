<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiAlat extends Model
{
    protected $table = 'mutasi_alat';

    protected $primaryKey = 'id_mutasi_alat';

    public $timestamps = true;

    protected $fillable = [
        'id_pengajuan_mutasi',
        'id_booking',
        'id_alat',
        'id_lokasi_tujuan',
        'status_gm',
        'catatan_dikeluarkan',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    /**
     * Relasi ke Pengajuan Mutasi
     */
    public function pengajuanMutasi()
    {
        return $this->belongsTo(
            PengajuanMutasi::class,
            'id_pengajuan_mutasi',
            'id_pengajuan_mutasi'
        );
    }

    /**
     * Relasi ke Pengajuan Booking
     */
    public function booking()
    {
        return $this->belongsTo(
            PengajuanBooking::class,
            'id_booking',
            'id_booking'
        );
    }

    /**
     * Relasi ke Alat
     */
    public function alat()
    {
        return $this->belongsTo(
            Alat::class,
            'id_alat',
            'id_alat'
        );
    }

    /**
     * Relasi ke Lokasi Tujuan
     */
    public function lokasiTujuan()
    {
        return $this->belongsTo(
            Lokasi::class,
            'id_lokasi_tujuan',
            'id_lokasi'
        );
    }

    // ============================================================
    // HELPER STATUS
    // ============================================================

    /**
     * Mengecek apakah alat dikeluarkan oleh GM.
     *
     * @return bool
     */
    public function sudahDikeluarkan(): bool
    {
        return $this->status_gm === 'dikeluarkan';
    }

    /**
     * Mengecek apakah alat masih dilanjutkan untuk proses mutasi.
     *
     * @return bool
     */
    public function masihDilanjutkan(): bool
    {
        return $this->status_gm === 'lanjut';
    }
}