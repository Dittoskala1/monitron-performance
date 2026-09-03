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
        'jenis_alat',
        'barcode',
        'nama_alat',
        'merek',
        'ip_address',
        'buatan',
        'tahun_pembuatan',
        'kondisi_awal',
        'status',
        // ⚠️ BARU: kondisi kesehatan alat saat ini (Normal/Gangguan/Rusak),
        // diisi OTOMATIS oleh LogHarianObserver — jangan diisi manual dari
        // form Kelola Alat.
        'kondisi_terkini',
        'kondisi_terkini_at',
    ];

    protected $casts = [
        'kondisi_terkini_at' => 'datetime',
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

    // ===== RELASI PENGAJUAN IDLE =====
    // ⚠️ BARU: dipakai untuk melacak unit kerja ASAL sebuah alat lewat
    // riwayat pengajuan idle-nya, supaya alat yang sudah pindah ke lokasi
    // "Unused" tetap bisa dikenali sebagai milik unit yang mengajukan idle.
    public function pengajuanIdle()
    {
        return $this->hasMany(PengajuanIdle::class, 'id_alat', 'id_alat');
    }

    /**
     * ⚠️ BARU: Update kode_alat mengikuti pindah bandara saat mutasi selesai.
     *
     * Format kode_alat standar: 14.01.01.04.CGK.T1.S1.XX.001 (dipisah titik),
     * dengan segmen kode bandara ada di antara segmen-segmen tsb (mis. "CGK").
     *
     * Strategi:
     * 1. Cari segmen yang PERSIS sama dengan kode bandara lama (case-insensitive).
     * 2. Kalau tidak ketemu (format kode_alat tidak baku / sudah beda),
     *    fallback ke posisi standar (segmen ke-5 / index 4) supaya kode
     *    tetap otomatis ke-update mengikuti bandara tujuan, bukan malah
     *    dibiarkan nyangkut ke bandara lama.
     *
     * Tidak melakukan apa pun kalau kode_alat kosong atau kode bandara
     * lama & baru sama (alat tidak pindah bandara).
     */
    public function updateKodeBandara(string $kodeBandaraLama, string $kodeBandaraBaru): void
    {
        if (empty($this->kode_alat) || empty($kodeBandaraBaru)) {
            return;
        }

        $kodeBandaraLama = strtoupper(trim($kodeBandaraLama));
        $kodeBandaraBaru = strtoupper(trim($kodeBandaraBaru));

        if ($kodeBandaraLama === $kodeBandaraBaru) {
            return;
        }

        $segmen = explode('.', $this->kode_alat);

        $idxDitemukan = null;
        foreach ($segmen as $i => $s) {
            if (strcasecmp(trim($s), $kodeBandaraLama) === 0) {
                $idxDitemukan = $i;
                break;
            }
        }

        // Fallback: posisi standar kode bandara ada di segmen ke-5 (index 4)
        if ($idxDitemukan === null && array_key_exists(4, $segmen)) {
            $idxDitemukan = 4;
        }

        if ($idxDitemukan !== null) {
            $segmen[$idxDitemukan] = $kodeBandaraBaru;
            $this->kode_alat = implode('.', $segmen);
        }
    }
}