<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenPengajuanIdle extends Model
{
    protected $table = 'dokumen_pengajuan_idle';
    protected $primaryKey = 'id_dokumen';

    protected $fillable = [
        'id_pengajuan',
        'nama_file',
        'path_file',
        'tipe_file',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(PengajuanIdle::class, 'id_pengajuan', 'id_pengajuan');
    }
}