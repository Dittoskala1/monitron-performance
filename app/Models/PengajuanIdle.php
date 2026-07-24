<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanIdle extends Model
{
    protected $table = 'pengajuan_idle';
    protected $primaryKey = 'id_pengajuan';

    protected $fillable = [
        'id_alat',
        'nomor_aset',              // ⚠️ BARU
        'id_lokasi_asal',
        'detail_lokasi',
        'tanggal_terbit_alat',     // ⚠️ BARU
        'kondisi_alat',
        'penjelasan_kondisi',      // ⚠️ BARU
        'id_lokasi_unused',
        'id_pengguna',
        'alasan_idle',
        'status',
        'alasan_reject',
        'status_ketersediaan',
        'tanggal_pengajuan',
        'tanggal_keputusan',
        'id_pengguna_approval',
        'id_pengguna_approval_div_head', // ⚠️ BARU
        'tanggal_approval_div_head',      // ⚠️ BARU
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'datetime',
        'tanggal_keputusan' => 'datetime',
        'tanggal_approval_div_head' => 'datetime', // ⚠️ BARU
        'tanggal_terbit_alat' => 'date',            // ⚠️ BARU
    ];

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'id_alat', 'id_alat');
    }

    public function lokasiAsal()
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi_asal', 'id_lokasi');
    }

    public function lokasiUnused()
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi_unused', 'id_lokasi');
    }

    public function pemohon()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function approver()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_approval', 'id_pengguna');
    }

    public function approverDivHead()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_approval_div_head', 'id_pengguna');
    }

    public function dokumen()
    {
        return $this->hasMany(DokumenPengajuanIdle::class, 'id_pengajuan', 'id_pengajuan');
    }
}