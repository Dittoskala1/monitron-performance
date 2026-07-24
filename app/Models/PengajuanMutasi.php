<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanMutasi extends Model
{
    protected $table = 'pengajuan_mutasi';
    protected $primaryKey = 'id_pengajuan_mutasi';

    protected $fillable = [
        'id_booking',
        'id_alat',
        'id_bandara_pemberi',
        'id_bandara_penerima',
        'id_pengguna_pemohon',
        'keterangan_kebutuhan',
        'status',

        'id_pengguna_ceo_approval',
        'tanggal_ceo_approval',
        'alasan_reject_ceo',

        'id_pengguna_gm_approval',
        'tanggal_gm_approval',
        'keputusan_gm',
        'catatan_gm',

        'id_pengguna_ceo_teruskan',
        'tanggal_ceo_teruskan',

        'id_pengguna_upload_ba_idle',
        'tanggal_upload_ba_idle',
        'id_pengguna_konfirmasi_idle',
        'tanggal_konfirmasi_idle',

        'id_pengguna_mobilisasi',
        'tanggal_mobilisasi',
        'catatan_mobilisasi',

        'id_pengguna_sertifikasi',
        'tanggal_sertifikasi',
    ];

    protected $casts = [
        'tanggal_ceo_approval'      => 'datetime',
        'tanggal_gm_approval'       => 'datetime',
        'tanggal_ceo_teruskan'      => 'datetime',
        'tanggal_upload_ba_idle'    => 'datetime',
        'tanggal_konfirmasi_idle'   => 'datetime',
        'tanggal_mobilisasi'        => 'datetime',
        'tanggal_sertifikasi'       => 'datetime',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function booking()
    {
        return $this->belongsTo(PengajuanBooking::class, 'id_booking', 'id_booking');
    }

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'id_alat', 'id_alat');
    }

    public function bandaraPemberi()
    {
        return $this->belongsTo(Bandara::class, 'id_bandara_pemberi', 'id_bandara');
    }

    public function bandaraPenerima()
    {
        return $this->belongsTo(Bandara::class, 'id_bandara_penerima', 'id_bandara');
    }

    public function pemohon()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_pemohon', 'id_pengguna');
    }

    public function ceoApprover()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_ceo_approval', 'id_pengguna');
    }

    public function gmApprover()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_gm_approval', 'id_pengguna');
    }

    public function ceoTeruskan()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_ceo_teruskan', 'id_pengguna');
    }

    public function uploaderBaIdle()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_upload_ba_idle', 'id_pengguna');
    }

    public function konfirmatorIdle()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_konfirmasi_idle', 'id_pengguna');
    }

    public function pelaksanaMobilisasi()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_mobilisasi', 'id_pengguna');
    }

    public function pelaksanaSertifikasi()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_sertifikasi', 'id_pengguna');
    }

    public function verifikasiMobilisasi()
    {
        return $this->hasOne(VerifikasiMobilisasiMutasi::class, 'id_pengajuan_mutasi', 'id_pengajuan_mutasi');
    }

    public function dokumen()
    {
    return $this->hasMany(DokumenMutasi::class, 'id_pengajuan_mutasi', 'id_pengajuan_mutasi');
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    /**
     * Cek apakah 3 tanda tangan digital verifikasi mobilisasi sudah lengkap
     * (semua 'Konfirmasi', tidak ada yang 'Pending' atau 'Tidak Sesuai').
     */
    public function verifikasiMobilisasiLengkap(): bool
    {
        $v = $this->verifikasiMobilisasi;

        if (! $v) {
            return false;
        }

        return $v->status_regional === 'Konfirmasi'
            && $v->status_penerima === 'Konfirmasi'
            && $v->status_pemberi === 'Konfirmasi';
    }
}