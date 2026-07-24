<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenMutasi extends Model
{
    protected $table = 'dokumen_mutasi';
    protected $primaryKey = 'id_dokumen';

    protected $fillable = [
        'id_pengajuan_mutasi',
        'jenis_dokumen',
        'nama_file',
        'path_file',
        'tipe_file',
    ];

    const JENIS = [
        'mapping_kebutuhan' => 'Mapping Kebutuhan',
        'pemastian_idle'    => 'Pemastian Fasilitas Idle',
        'mobilisasi'        => 'Mobilisasi',
        'sertifikasi'       => 'Sertifikasi',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function pengajuanMutasi()
    {
        return $this->belongsTo(PengajuanMutasi::class, 'id_pengajuan_mutasi', 'id_pengajuan_mutasi');
    }

    // ─── Accessor ─────────────────────────────────────────────────────────────

    public function getLabelJenisAttribute(): string
    {
        return self::JENIS[$this->jenis_dokumen] ?? $this->jenis_dokumen;
    }
}