<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alat extends Model
{
    protected $table = 'alat';
    protected $primaryKey = 'id_alat';

    protected $fillable = [
        'id_lokasi',
        'id_bandara', 
        'id_kategori',
        'kode_alat',
        'detail_lokasi',
        'unit_kerja',
        'barcode',
        'nama_alat',
        'merek',
        'ip_address',
        'buatan',
        'tahun_pembuatan',
        'kondisi_awal',
        'status'
    ];

    // ===== RELASI LOKASI (HANYA 1 KALI) =====
    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi', 'id_lokasi');
    }

    // ===== RELASI BANDARA (TAMBAHAN) =====
    public function bandara()
    {
        return $this->belongsTo(Bandara::class, 'id_bandara', 'id_bandara');
    }

    // ===== RELASI KATEGORI (HANYA 1 KALI) =====
    public function kategori()
    {
        return $this->belongsTo(KategoriAlat::class, 'id_kategori', 'id_kategori');
    }

    // ===== RELASI LOG HARIAN =====
    public function logHarian()
    {
        return $this->hasMany(LogHarian::class, 'id_alat', 'id_alat');
    }

    // ===== RELASI HASIL BULANAN =====
    public function hasilBulanan()
    {
        return $this->hasMany(HasilBulanan::class, 'id_alat', 'id_alat');
    }

    // ===== RELASI NOTIFIKASI =====
    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class, 'id_alat', 'id_alat');
    }
}