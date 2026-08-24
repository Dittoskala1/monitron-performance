<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $fillable = [
        'alat_id',
        'id_pengguna',
        'jenis',
        'judul',
        'pesan',
        'meta',
        'prioritas',
        'status',
        'dibaca_pada',
        'tanggal',
    ];

    protected $casts = [
        'meta'        => 'array',
        'tanggal'     => 'datetime',
        'dibaca_pada' => 'datetime',
    ];

    // ─── Konstanta ────────────────────────────────────────────────────────────

    const JENIS = [
        'alat_baru'            => 'Alat Baru',
        'performa_rendah'      => 'Performa Rendah',
        'pengajuan_idle'       => 'Pengajuan Idle',
        'approve_idle'         => 'Idle Disetujui',
        'reject_idle'          => 'Idle Ditolak',
        'pengajuan_booking'    => 'Pengajuan Booking',
        'approve_booking'      => 'Booking Disetujui',
        'reject_booking'       => 'Booking Ditolak',
        'pengajuan_mutasi'     => 'Pengajuan Mutasi',
        'approve_mutasi'       => 'Mutasi Disetujui',
        'reject_mutasi'        => 'Mutasi Ditolak',
        'konfirmasi_mutasi'    => 'Konfirmasi Mutasi',
        'mobilisasi_mutasi'    => 'Mobilisasi Mutasi',
        'sertifikasi_mutasi'   => 'Sertifikasi Mutasi',
        'status_error'         => 'Alat Rusak',
        'status_offline'       => 'Alat Gangguan',
        'status_online'        => 'Alat Kembali Normal',
        'sistem'               => 'Sistem',
    ];

    const PRIORITAS = [
        'rendah' => 'Rendah',
        'sedang' => 'Sedang',
        'tinggi' => 'Tinggi',
        'kritis' => 'Kritis',
    ];

    const IKON = [
        'alat_baru'            => 'fa-plus-circle',
        'performa_rendah'      => 'fa-chart-line',
        'pengajuan_idle'       => 'fa-pause-circle',
        'approve_idle'         => 'fa-check-circle',
        'reject_idle'          => 'fa-times-circle',
        'pengajuan_booking'    => 'fa-calendar-plus',
        'approve_booking'      => 'fa-check-circle',
        'reject_booking'       => 'fa-times-circle',
        'pengajuan_mutasi'     => 'fa-arrow-right-arrow-left',
        'approve_mutasi'       => 'fa-check-circle',
        'reject_mutasi'        => 'fa-times-circle',
        'konfirmasi_mutasi'    => 'fa-clipboard-check',
        'mobilisasi_mutasi'    => 'fa-truck',
        'sertifikasi_mutasi'   => 'fa-certificate',
        'status_error'         => 'fa-exclamation-triangle',
        'status_offline'       => 'fa-plug-circle-xmark',
        'status_online'        => 'fa-circle-check',
        'sistem'               => 'fa-bell',
    ];

    const WARNA_PRIORITAS = [
        'rendah' => 'success',
        'sedang' => 'info',
        'tinggi' => 'warning',
        'kritis' => 'danger',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────────────

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'alat_id', 'id_alat');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeBelumDibaca($query)
    {
        return $query->where('status', 'Belum Dibaca');
    }

    public function scopeSudahDibaca($query)
    {
        return $query->where('status', 'Dibaca');
    }

    public function scopeUntukPengguna($query, $userId)
    {
        return $query->where('id_pengguna', $userId);
    }

    public function scopeByBandara($query, $idBandara)
    {
        return $query->whereHas('alat.lokasi', fn($q) => $q->where('id_bandara', $idBandara));
    }

    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    public function scopeKritis($query)
    {
        return $query->whereIn('prioritas', ['kritis', 'tinggi']);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getIkonAttribute(): string
    {
        return self::IKON[$this->jenis] ?? 'fa-bell';
    }

    public function getWarnaPrioritasAttribute(): string
    {
        return self::WARNA_PRIORITAS[$this->prioritas] ?? 'secondary';
    }

    public function getLabelJenisAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    // ─── Methods ──────────────────────────────────────────────────────────────

    public function tandaiDibaca(): void
    {
        $this->update([
            'status'      => 'Dibaca',
            'dibaca_pada' => now(),
        ]);
    }

    public function tandaiBelumDibaca(): void
    {
        $this->update([
            'status'      => 'Belum Dibaca',
            'dibaca_pada' => null,
        ]);
    }

    public function isSudahDibaca(): bool
    {
        return $this->status === 'Dibaca';
    }

    public function isBelumDibaca(): bool
    {
        return $this->status === 'Belum Dibaca';
    }

    // ─── Static Factories ─────────────────────────────────────────────────────

    public static function kirimKeUser(int $userId, array $data): self
    {
        return self::create([
            'id_pengguna' => $userId,
            'alat_id'     => $data['alat_id'] ?? null,
            'jenis'       => $data['jenis'] ?? 'sistem',
            'judul'       => $data['judul'],
            'pesan'       => $data['pesan'],
            'prioritas'   => $data['prioritas'] ?? 'sedang',
            'status'      => 'Belum Dibaca',
            'tanggal'     => now(),
            'meta'        => $data['meta'] ?? null,
        ]);
    }

    public static function kirimKeBanyakUser(array $userIds, array $data): void
    {
        foreach ($userIds as $userId) {
            self::kirimKeUser($userId, $data);
        }
    }

    public static function kirimKeRole(string $roleSlug, array $data): void
    {
        $users = Pengguna::whereHas('roles', function ($query) use ($roleSlug) {
            $query->where('slug', $roleSlug);
        })->get();

        foreach ($users as $user) {
            self::kirimKeUser($user->id_pengguna, $data);
        }
    }

    /**
     * Kirim ke role tertentu tapi cuma yang id_bandara-nya cocok
     * (mis. gm_kc bandara pemberi doang, bukan semua gm_kc).
     *
     * @param int|null $idUnit ⚠️ BARU: kalau diisi, notifikasi cuma dikirim
     *   ke user yang terikat ke unit kerja itu (mis. cuma admin SSES-T1 di
     *   CGK, bukan semua afet_bandara se-CGK). Bandara yang tidak punya
     *   unit_kerja (HLP, KJT, BDO) — parameter ini diabaikan otomatis
     *   karena tidak akan ada user dengan id_unit di bandara itu.
     */
    public static function kirimKeRoleBandara(string $roleSlug, ?int $idBandara, array $data, ?int $idUnit = null): void
    {
        if (! $idBandara) {
            return;
        }

        $query = Pengguna::whereHas('roles', fn($q) => $q->where('slug', $roleSlug))
            ->where('id_bandara', $idBandara);

        if ($idUnit) {
            $query->where('id_unit', $idUnit);
        }

        $users = $query->get();

        foreach ($users as $user) {
            self::kirimKeUser($user->id_pengguna, $data);
        }
    }

    /**
     * ⚠️ BARU: cari unit_kerja yang cakupannya cocok dengan 1 alat
     * (dipakai supaya notifikasi soal alat itu cuma nyasar ke admin
     * unitnya, bukan seluruh admin bandara). Null kalau bandara alat ini
     * tidak punya pemisahan unit (HLP, KJT, BDO), atau tidak ada unit yang
     * cocok.
     */
    protected static function cariUnitUntukAlat(Alat $alat): ?int
    {
        $unit = UnitKerja::where('id_bandara', $alat->id_bandara)
            ->where(function ($q) use ($alat) {
                $q->whereNull('id_lokasi')->orWhere('id_lokasi', $alat->id_lokasi);
            })
            ->get()
            ->first(function ($unit) use ($alat) {
                if (empty($unit->cakupan_alat)) {
                    return false;
                }
                $cakupanLower = array_map('strtolower', $unit->cakupan_alat);
                return in_array(strtolower($alat->jenis_alat ?? ''), $cakupanLower, true);
            });

        return $unit?->id_unit;
    }

    // ─── Factory Methods ──────────────────────────────────────────────────────

    public static function buatUntukAlatBaru(Alat $alat, ?int $userId = null): self
    {
        return self::kirimKeUser($userId ?? 1, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'alat_baru',
            'judul'     => 'Alat Baru Ditambahkan',
            'pesan'     => "Alat \"{$alat->nama_alat}\" telah didaftarkan ke sistem.",
            'prioritas' => 'sedang',
            'meta'      => [
                'nama_alat' => $alat->nama_alat,
                'lokasi'    => optional($alat->lokasi)->nama_lokasi,
                'bandara'   => optional(optional($alat->lokasi)->bandara)->nama_bandara,
            ],
        ]);
    }

    public static function buatPerformaRendah(Alat $alat, float $performa, ?int $userId = null): self
    {
        return self::kirimKeUser($userId ?? 1, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'performa_rendah',
            'judul'     => 'Performa Alat Di Bawah Threshold',
            'pesan'     => "Alat \"{$alat->nama_alat}\" memiliki performa {$performa}%, di bawah batas minimum.",
            'prioritas' => $performa < 70 ? 'kritis' : 'tinggi',
            'meta'      => [
                'nama_alat' => $alat->nama_alat,
                'performa'  => $performa,
                'waktu'     => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * ⚠️ DIUBAH: yang approve tahap 1 sekarang Dep Head (per unit kerja),
     * bukan Div Head lagi. Div Head tetap dikirimi notifikasi, tapi
     * sifatnya cuma info ("mengetahui") — dia tidak perlu bertindak apa-apa.
     */
    public static function buatPengajuanIdle(Alat $alat, string $pemohon, ?int $idLokasiAsal = null, ?int $idUnit = null): void
    {
        // ---- 1. Dep Head: pihak yang harus approve tahap 1 ----
        $depHeadQuery = Pengguna::whereHas('roles', fn($q) => $q->where('slug', 'dep_head'))
            ->where('id_bandara', $alat->id_bandara);

        // Kalau ada Dep Head per-terminal di bandara ini, filter ke terminal asal
        if ($idLokasiAsal !== null && (clone $depHeadQuery)->whereNotNull('id_lokasi')->exists()) {
            $depHeadQuery->where('id_lokasi', $idLokasiAsal);
        }

        // Kalau ada Dep Head per-unit kerja (mis. Dep Head SSES-T1 di CGK),
        // filter lebih spesifik lagi ke unit yang mengajukan.
        if ($idUnit !== null && (clone $depHeadQuery)->whereNotNull('id_unit')->exists()) {
            $depHeadQuery->where('id_unit', $idUnit);
        }

        foreach ($depHeadQuery->get() as $dh) {
            self::kirimKeUser($dh->id_pengguna, [
                'alat_id'   => $alat->id_alat,
                'jenis'     => 'pengajuan_idle',
                'judul'     => 'Pengajuan Idle Masuk',
                'pesan'     => "Alat \"{$alat->nama_alat}\" diajukan idle oleh {$pemohon}. Menunggu persetujuan Dep Head.",
                'prioritas' => 'sedang',
                'meta'      => [
                    'nama_alat' => $alat->nama_alat,
                    'pemohon'   => $pemohon,
                    'waktu'     => now()->toDateTimeString(),
                ],
            ]);
        }

        // ---- 2. Div Head: sekadar "mengetahui" (info only, tidak approve) ----
        $divHeadQuery = Pengguna::whereHas('roles', fn($q) => $q->where('slug', 'div_head'))
            ->where('id_bandara', $alat->id_bandara);

        if ($idLokasiAsal !== null && (clone $divHeadQuery)->whereNotNull('id_lokasi')->exists()) {
            $divHeadQuery->where('id_lokasi', $idLokasiAsal);
        }

        foreach ($divHeadQuery->get() as $dh) {
            self::kirimKeUser($dh->id_pengguna, [
                'alat_id'   => $alat->id_alat,
                'jenis'     => 'pengajuan_idle',
                'judul'     => 'Info: Pengajuan Idle Masuk',
                'pesan'     => "Alat \"{$alat->nama_alat}\" diajukan idle oleh {$pemohon}. Untuk diketahui — menunggu persetujuan Dep Head.",
                'prioritas' => 'rendah',
                'meta'      => [
                    'nama_alat' => $alat->nama_alat,
                    'pemohon'   => $pemohon,
                    'waktu'     => now()->toDateTimeString(),
                ],
            ]);
        }
    }

    /**
     * Notifikasi tahap 2 (approval bertingkat Idle) — dikirim ke Admin AFET
     * Regional setelah Dep Head approve tahap 1. Dipisah dari
     * buatPengajuanIdle() karena method itu cuma menyasar role dep_head/div_head.
     */
    public static function buatMenungguApprovalAfetRegional(Alat $alat, string $pemohon): void
    {
        self::kirimKeRole('afet_regional', [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'pengajuan_idle',
            'judul'     => 'Pengajuan Idle Menunggu Approval Final',
            'pesan'     => "Alat \"{$alat->nama_alat}\" diajukan idle oleh {$pemohon}, sudah disetujui Dep Head. Menunggu approval Anda.",
            'prioritas' => 'sedang',
            'meta'      => [
                'nama_alat' => $alat->nama_alat,
                'pemohon'   => $pemohon,
                'waktu'     => now()->toDateTimeString(),
            ],
        ]);
    }

    public static function buatKeputusanIdle(Alat $alat, string $status, string $approver, int $pemohonId, ?string $alasan = null): void
    {
        $approved = str_starts_with($status, 'Approved');

        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => $approved ? 'approve_idle' : 'reject_idle',
            'judul'     => $approved ? 'Pengajuan Idle Disetujui' : 'Pengajuan Idle Ditolak',
            'pesan'     => $approved
                ? "Pengajuan idle alat \"{$alat->nama_alat}\" disetujui oleh {$approver}."
                : "Pengajuan idle alat \"{$alat->nama_alat}\" ditolak oleh {$approver}. Alasan: {$alasan}",
            'prioritas' => $approved ? 'sedang' : 'tinggi',
            'meta'      => [
                'nama_alat' => $alat->nama_alat,
                'status'    => $status,
                'approver'  => $approver,
                'alasan'    => $alasan,
                'waktu'     => now()->toDateTimeString(),
            ],
        ];

        // Ke pemohon
        self::kirimKeUser($pemohonId, $data);

        // Ke GM sesuai bandara pemilik alat saat ini
        $gmUsers = Pengguna::whereHas('roles', fn($q) => $q->where('slug', 'gm_kc'))
            ->where('id_bandara', $alat->id_bandara)
            ->get();

        foreach ($gmUsers as $gm) {
            self::kirimKeUser($gm->id_pengguna, $data);
        }

        // Ke HO dan CEO (sekadar mengetahui, bukan approver di alur idle)
        self::kirimKeRole('ho', $data);
        self::kirimKeRole('ceo', $data);
    }

    // ==========================================
    // MUTASI NOTIFIKASI
    // Aturan: (1) tiap approval → notif ke pihak yang berikutnya harus
    // bertindak; (2) tiap perubahan status → HO ikut dinotif (sekadar tahu).
    // ==========================================

    private static function metaMutasi(PengajuanMutasi $mutasi, array $tambahan = []): array
    {
        return array_merge([
            'id_pengajuan_mutasi' => $mutasi->id_pengajuan_mutasi,
            'status'              => $mutasi->status,
            'waktu'               => now()->toDateTimeString(),
        ], $tambahan);
    }

    /** Tahap 1: pemohon submit → CEO approve, Regional & HO sekadar tahu */
    public static function mutasiDiajukan(PengajuanMutasi $mutasi, Alat $alat, string $pemohon): void
    {
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'Pengajuan Mutasi Menunggu Approval CEO',
            'pesan'     => "Alat \"{$alat->nama_alat}\" diajukan mutasi oleh {$pemohon}. Menunggu approval CEO.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi, ['pemohon' => $pemohon]),
        ];

        self::kirimKeRole('ceo', $data);
        self::kirimKeRole('afet_regional', $data);
        self::kirimKeRole('ho', $data);
    }

    /** Tahap 2: CEO approve → giliran GM Bandara Pemberi */
    public static function mutasiDisetujuiCeo(PengajuanMutasi $mutasi, Alat $alat, string $approver): void
    {
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'approve_mutasi',
            'judul'     => 'Mutasi Disetujui CEO — Menunggu GM Pemberi',
            'pesan'     => "Pengajuan mutasi alat \"{$alat->nama_alat}\" disetujui CEO ({$approver}). Menunggu approval GM Bandara Pemberi.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi, ['approver' => $approver]),
        ];

        self::kirimKeRoleBandara('gm_kc', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRole('ho', $data);
    }

    /** CEO reject di tahap awal → balik ke pemohon buat revisi (status tidak berubah) */
    public static function mutasiDitolakCeoRevisi(PengajuanMutasi $mutasi, Alat $alat, string $alasan): void
    {
        self::kirimKeUser($mutasi->id_pengguna_pemohon, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'reject_mutasi',
            'judul'     => 'Pengajuan Mutasi Ditolak CEO — Perlu Revisi',
            'pesan'     => "Pengajuan mutasi alat \"{$alat->nama_alat}\" ditolak CEO. Alasan: {$alasan}. Mohon revisi dan ajukan ulang.",
            'prioritas' => 'tinggi',
            'meta'      => self::metaMutasi($mutasi, ['alasan' => $alasan]),
        ]);
    }

    /** Tahap 3: GM Pemberi kasih keputusan (approve/reject) → giliran CEO meneruskan */
    public static function mutasiKeputusanGm(PengajuanMutasi $mutasi, Alat $alat, string $keputusan, string $approver): void
    {
        $approved = $keputusan === 'Approve';
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => $approved ? 'approve_mutasi' : 'reject_mutasi',
            'judul'     => 'GM Pemberi Sudah Memutuskan — Menunggu CEO Teruskan',
            'pesan'     => $approved
                ? "GM Bandara Pemberi ({$approver}) menyetujui mutasi alat \"{$alat->nama_alat}\". Menunggu CEO meneruskan keputusan."
                : "GM Bandara Pemberi ({$approver}) menolak mutasi alat \"{$alat->nama_alat}\". Menunggu CEO meneruskan penolakan.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi, ['keputusan' => $keputusan, 'approver' => $approver]),
        ];

        self::kirimKeRole('ceo', $data);
        self::kirimKeRole('ho', $data);
    }

    /** CEO teruskan penolakan GM → balik ke pemohon buat revisi */
    public static function mutasiGmDitolakDiteruskan(PengajuanMutasi $mutasi, Alat $alat): void
    {
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'reject_mutasi',
            'judul'     => 'Mutasi Ditolak GM Pemberi — Perlu Revisi',
            'pesan'     => "GM Bandara Pemberi menolak mutasi alat \"{$alat->nama_alat}\". Mohon revisi dan ajukan ulang.",
            'prioritas' => 'tinggi',
            'meta'      => self::metaMutasi($mutasi),
        ];

        self::kirimKeUser($mutasi->id_pengguna_pemohon, $data);
        self::kirimKeRole('ho', $data);
    }

    /** CEO teruskan approval GM → giliran AFET Bandara Pemberi upload BA Idle */
    public static function mutasiSiapUploadBaIdle(PengajuanMutasi $mutasi, Alat $alat): void
    {
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'Menunggu Upload BA Pemastian Fasilitas Idle',
            'pesan'     => "Mutasi alat \"{$alat->nama_alat}\" sudah disetujui berjenjang. Admin AFET Bandara Pemberi wajib upload BA Pemastian Fasilitas Idle.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi),
        ];

        self::kirimKeRoleBandara('afet_bandara', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRole('ho', $data);
    }

    /** BA Idle sudah diupload → giliran AFET Regional review & konfirmasi (status belum berubah) */
    public static function mutasiBaIdlePerluDikonfirmasi(PengajuanMutasi $mutasi, Alat $alat): void
    {
        self::kirimKeRole('afet_regional', [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'BA Pemastian Fasilitas Idle Perlu Dikonfirmasi',
            'pesan'     => "Dokumen BA Pemastian Fasilitas Idle untuk alat \"{$alat->nama_alat}\" sudah diupload. Mohon direview & dikonfirmasi.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi),
        ]);
    }

    /** AFET Regional konfirmasi fasilitas idle → giliran AFET Bandara Penerima mobilisasi */
    public static function mutasiSiapMobilisasi(PengajuanMutasi $mutasi, Alat $alat): void
    {
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'Fasilitas Idle Dikonfirmasi — Siap Mobilisasi',
            'pesan'     => "Fasilitas idle untuk alat \"{$alat->nama_alat}\" sudah dikonfirmasi. Admin AFET Bandara Penerima bisa mulai mobilisasi.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi),
        ];

        self::kirimKeRoleBandara('afet_bandara', $mutasi->id_bandara_penerima, $data);
        self::kirimKeRole('ho', $data);
    }

    /** Mobilisasi selesai → giliran Regional + AFET Penerima + AFET Pemberi verifikasi (3 tanda tangan) */
    public static function mutasiMobilisasiSelesai(PengajuanMutasi $mutasi, Alat $alat): void
    {
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'mobilisasi_mutasi',
            'judul'     => 'Mobilisasi Selesai — Menunggu Verifikasi 3 Pihak',
            'pesan'     => "Mobilisasi alat \"{$alat->nama_alat}\" selesai. Menunggu verifikasi dari AFET Regional, Bandara Penerima, dan Bandara Pemberi.",
            'prioritas' => 'tinggi',
            'meta'      => self::metaMutasi($mutasi),
        ];

        self::kirimKeRole('afet_regional', $data);
        self::kirimKeRoleBandara('afet_bandara', $mutasi->id_bandara_penerima, $data);
        self::kirimKeRoleBandara('afet_bandara', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRoleBandara('gm_kc', $mutasi->id_bandara_penerima, $data);
        self::kirimKeRoleBandara('gm_kc', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRole('ceo', $data);
        self::kirimKeRole('ho', $data);
    }

    /** Salah satu verifikasi "Tidak Sesuai" → balik ke AFET Bandara Penerima, upload ulang mobilisasi */
    public static function mutasiVerifikasiTidakSesuai(PengajuanMutasi $mutasi, Alat $alat): void
    {
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'reject_mutasi',
            'judul'     => 'Verifikasi Mobilisasi: Tidak Sesuai',
            'pesan'     => "Verifikasi mobilisasi alat \"{$alat->nama_alat}\" menyatakan tidak sesuai. Admin AFET Bandara Penerima perlu upload ulang.",
            'prioritas' => 'tinggi',
            'meta'      => self::metaMutasi($mutasi),
        ];

        self::kirimKeRoleBandara('afet_bandara', $mutasi->id_bandara_penerima, $data);
        self::kirimKeRole('ho', $data);
    }

    /** Semua verifikasi "Konfirmasi" → giliran AFET Bandara Penerima upload sertifikasi */
    public static function mutasiSiapSertifikasi(PengajuanMutasi $mutasi, Alat $alat): void
    {
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'Verifikasi Lengkap — Siap Sertifikasi',
            'pesan'     => "Semua pihak sudah konfirmasi mobilisasi alat \"{$alat->nama_alat}\". Admin AFET Bandara Penerima bisa upload sertifikasi.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi),
        ];

        self::kirimKeRoleBandara('afet_bandara', $mutasi->id_bandara_penerima, $data);
        self::kirimKeRole('ho', $data);
    }

    /** Sertifikasi selesai → arsip akhir ke CEO, HO, Regional, dan pemohon */
    public static function mutasiSelesai(PengajuanMutasi $mutasi, Alat $alat): void
    {
        $data = [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'sertifikasi_mutasi',
            'judul'     => 'Mutasi Selesai',
            'pesan'     => "Proses mutasi alat \"{$alat->nama_alat}\" telah tuntas. Sertifikasi selesai dan didokumentasikan.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi),
        ];

        self::kirimKeUser($mutasi->id_pengguna_pemohon, $data);
        self::kirimKeRole('ceo', $data);
        self::kirimKeRole('afet_regional', $data);
        self::kirimKeRole('ho', $data);
    }

    /**
     * ⚠️ DIUBAH: dipanggil dari AlatObserver saat kondisi_terkini alat
     * (diturunkan otomatis dari log_harian terbaru, lihat LogHarianObserver)
     * berubah jadi 'Rusak'.
     */
    public static function buatUntukKondisiRusak(Alat $alat, array $detail = []): void
    {
        self::kirimKeRoleBandara('afet_bandara', $alat->id_bandara, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'status_error',
            'judul'     => 'Alat Rusak',
            'pesan'     => "Alat \"{$alat->nama_alat}\" dilaporkan dalam kondisi Rusak pada input log harian terbaru.",
            'prioritas' => 'kritis',
            'meta'      => array_merge([
                'nama_alat' => $alat->nama_alat,
                'waktu'     => now()->toDateTimeString(),
            ], $detail),
        ], self::cariUnitUntukAlat($alat));
    }

    /**
     * ⚠️ DIUBAH: dipanggil dari AlatObserver saat kondisi_terkini alat
     * berubah jadi 'Gangguan'.
     */
    public static function buatUntukKondisiGangguan(Alat $alat, array $detail = []): void
    {
        self::kirimKeRoleBandara('afet_bandara', $alat->id_bandara, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'status_offline',
            'judul'     => 'Alat Mengalami Gangguan',
            'pesan'     => "Alat \"{$alat->nama_alat}\" dilaporkan mengalami Gangguan pada input log harian terbaru.",
            'prioritas' => 'tinggi',
            'meta'      => array_merge([
                'nama_alat' => $alat->nama_alat,
                'waktu'     => now()->toDateTimeString(),
            ], $detail),
        ], self::cariUnitUntukAlat($alat));
    }

    /**
     * ⚠️ DIUBAH: dipanggil dari AlatObserver saat kondisi_terkini alat balik
     * jadi 'Normal' setelah sebelumnya Gangguan/Rusak.
     */
    public static function buatUntukKondisiNormal(Alat $alat, array $detail = []): void
    {
        self::kirimKeRoleBandara('afet_bandara', $alat->id_bandara, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'status_online',
            'judul'     => 'Alat Kembali Normal',
            'pesan'     => "Alat \"{$alat->nama_alat}\" sudah kembali dalam kondisi Normal.",
            'prioritas' => 'sedang',
            'meta'      => array_merge([
                'nama_alat' => $alat->nama_alat,
                'waktu'     => now()->toDateTimeString(),
            ], $detail),
        ], self::cariUnitUntukAlat($alat));
    }
}