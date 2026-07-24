<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifikasiMobilisasiMutasi extends Model
{
    protected $table = 'verifikasi_mobilisasi_mutasi';
    protected $primaryKey = 'id_verifikasi';

    protected $fillable = [
        'id_pengajuan_mutasi',

        'status_regional',
        'catatan_regional',
        'tanggal_regional',
        'id_pengguna_regional',

        'status_penerima',
        'catatan_penerima',
        'tanggal_penerima',
        'id_pengguna_penerima',

        'status_pemberi',
        'catatan_pemberi',
        'tanggal_pemberi',
        'id_pengguna_pemberi',
    ];

    protected $casts = [
        'tanggal_regional' => 'datetime',
        'tanggal_penerima'  => 'datetime',
        'tanggal_pemberi'   => 'datetime',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function pengajuanMutasi()
    {
        return $this->belongsTo(PengajuanMutasi::class, 'id_pengajuan_mutasi', 'id_pengajuan_mutasi');
    }

    public function pengujiRegional()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_regional', 'id_pengguna');
    }

    public function pengujiPenerima()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_penerima', 'id_pengguna');
    }

    public function pengujiPemberi()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_pemberi', 'id_pengguna');
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    public function adaYangTidakSesuai(): bool
    {
        return in_array('Tidak Sesuai', [
            $this->status_regional,
            $this->status_penerima,
            $this->status_pemberi,
        ]);
    }

    public function semuaKonfirmasi(): bool
    {
        return $this->status_regional === 'Konfirmasi'
            && $this->status_penerima === 'Konfirmasi'
            && $this->status_pemberi === 'Konfirmasi';
    }
}