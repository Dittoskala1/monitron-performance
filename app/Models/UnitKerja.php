<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitKerja extends Model
{
    protected $table = 'unit_kerja';
    protected $primaryKey = 'id_unit';

    protected $fillable = [
        'id_bandara',
        'id_lokasi',
        'kode_unit',
        'nama_unit',
        'keterangan',
        'cakupan_alat',
    ];

    protected $casts = [
        'cakupan_alat' => 'array',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    public function bandara()
    {
        return $this->belongsTo(Bandara::class, 'id_bandara', 'id_bandara');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi', 'id_lokasi');
    }

    public function pengguna()
    {
        return $this->hasMany(Pengguna::class, 'id_unit', 'id_unit');
    }

    // ============================================================
    // HELPER
    // ============================================================

    /**
     * Apakah unit ini sudah punya cakupan jenis alat yang diisi?
     */
    public function sudahAdaCakupan(): bool
    {
        return !empty($this->cakupan_alat);
    }

    /**
     * Label ringkas "KODE - Nama Lokasi" (kalau lokasi diisi) untuk dropdown.
     */
    public function getLabelAttribute(): string
    {
        $lokasi = $this->lokasi?->nama_lokasi;
        return $lokasi ? "{$this->kode_unit} ({$lokasi})" : $this->kode_unit;
    }
}