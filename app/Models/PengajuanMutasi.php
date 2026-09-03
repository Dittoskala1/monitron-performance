<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanMutasi extends Model
{
    protected $table = 'pengajuan_mutasi';

    protected $primaryKey = 'id_pengajuan_mutasi';

    protected $fillable = [
        // Data utama pengajuan
        'id_bandara_pemberi',
        'id_bandara_penerima',
        'id_pengguna_pemohon',
        'keterangan_kebutuhan',
        'status',

        // Approval CEO
        'id_pengguna_ceo_approval',
        'tanggal_ceo_approval',
        'alasan_reject_ceo',

        // Approval GM
        'id_pengguna_gm_approval',
        'tanggal_gm_approval',
        'keputusan_gm',
        'catatan_gm',

        // Pengajuan ulang
        'id_pengguna_ajukan_ulang',
        'tanggal_ajukan_ulang',
        'jumlah_ajukan_ulang',

        // Berita Acara Idle
        'id_pengguna_upload_ba_idle',
        'tanggal_upload_ba_idle',

        // Konfirmasi Idle
        'id_pengguna_konfirmasi_idle',
        'tanggal_konfirmasi_idle',
        'alasan_reject_idle',

        // Penerimaan barang
        'id_pengguna_terima_barang',
        'tanggal_terima_barang',
        'catatan_terima_barang',

        // Sertifikasi
        'id_pengguna_sertifikasi',
        'tanggal_sertifikasi',
    ];

    protected $casts = [
        'tanggal_ceo_approval'      => 'datetime',
        'tanggal_gm_approval'       => 'datetime',
        'tanggal_ajukan_ulang'      => 'datetime',
        'tanggal_upload_ba_idle'    => 'datetime',
        'tanggal_konfirmasi_idle'   => 'datetime',
        'tanggal_terima_barang'     => 'datetime',
        'tanggal_sertifikasi'       => 'datetime',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    /**
     * Detail alat yang diajukan untuk mutasi.
     *
     * Satu pengajuan dapat memiliki banyak alat.
     */
    public function detailAlat()
    {
        return $this->hasMany(
            MutasiAlat::class,
            'id_pengajuan_mutasi',
            'id_pengajuan_mutasi'
        );
    }

    /**
     * Bandara pemberi alat.
     */
    public function bandaraPemberi()
    {
        return $this->belongsTo(
            Bandara::class,
            'id_bandara_pemberi',
            'id_bandara'
        );
    }

    /**
     * Bandara penerima alat.
     */
    public function bandaraPenerima()
    {
        return $this->belongsTo(
            Bandara::class,
            'id_bandara_penerima',
            'id_bandara'
        );
    }

    /**
     * Pengguna yang mengajukan mutasi.
     */
    public function pemohon()
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna_pemohon',
            'id_pengguna'
        );
    }

    /**
     * Pengguna yang melakukan approval CEO.
     */
    public function ceoApprover()
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna_ceo_approval',
            'id_pengguna'
        );
    }

    /**
     * Pengguna yang melakukan approval GM.
     */
    public function gmApprover()
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna_gm_approval',
            'id_pengguna'
        );
    }

    /**
     * Pengguna yang mengajukan ulang.
     */
    public function pengajuUlang()
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna_ajukan_ulang',
            'id_pengguna'
        );
    }

    /**
     * Pengguna yang mengupload BA Idle.
     */
    public function uploaderBaIdle()
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna_upload_ba_idle',
            'id_pengguna'
        );
    }

    /**
     * Pengguna yang mengonfirmasi Idle.
     */
    public function konfirmatorIdle()
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna_konfirmasi_idle',
            'id_pengguna'
        );
    }

    /**
     * Pengguna yang menerima barang.
     */
    public function penerimaBarang()
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna_terima_barang',
            'id_pengguna'
        );
    }

    /**
     * Pengguna yang melakukan sertifikasi.
     */
    public function pelaksanaSertifikasi()
    {
        return $this->belongsTo(
            Pengguna::class,
            'id_pengguna_sertifikasi',
            'id_pengguna'
        );
    }

    /**
     * Dokumen yang berkaitan dengan pengajuan mutasi.
     */
    public function dokumen()
    {
        return $this->hasMany(
            DokumenMutasi::class,
            'id_pengajuan_mutasi',
            'id_pengajuan_mutasi'
        );
    }

    // ============================================================
    // HELPER STATUS
    // ============================================================

    /**
     * Mengecek apakah pengajuan ditolak oleh GM Pemberi.
     */
    public function sudahDitolakGm(): bool
    {
        return $this->status === 'Ditolak GM Pemberi';
    }

    /**
     * Mengecek apakah barang sudah diterima.
     */
    public function sudahTerimaBarang(): bool
    {
        return !is_null($this->id_pengguna_terima_barang);
    }

    /**
     * Mengecek apakah alat sudah disertifikasi.
     */
    public function sudahSertifikasi(): bool
    {
        return !is_null($this->id_pengguna_sertifikasi);
    }

    // ============================================================
    // HELPER JUMLAH ALAT
    // ============================================================

    /**
     * Menghitung seluruh alat dalam pengajuan.
     */
    public function jumlahAlat(): int
    {
        return $this->relationLoaded('detailAlat')
            ? $this->detailAlat->count()
            : $this->detailAlat()->count();
    }

    /**
     * Menghitung jumlah alat yang dikeluarkan oleh GM.
     *
     * status_gm:
     * - lanjut       = alat tetap mengikuti proses mutasi
     * - dikeluarkan  = alat dikeluarkan dari proses mutasi
     */
    public function jumlahAlatDikeluarkan(): int
    {
        return $this->relationLoaded('detailAlat')
            ? $this->detailAlat
                ->where('status_gm', 'dikeluarkan')
                ->count()
            : $this->detailAlat()
                ->where('status_gm', 'dikeluarkan')
                ->count();
    }

    /**
     * Menghitung jumlah alat yang masih dilanjutkan
     * dalam proses mutasi.
     */
    public function jumlahAlatDilanjutkan(): int
    {
        return $this->relationLoaded('detailAlat')
            ? $this->detailAlat
                ->where('status_gm', 'lanjut')
                ->count()
            : $this->detailAlat()
                ->where('status_gm', 'lanjut')
                ->count();
    }
}