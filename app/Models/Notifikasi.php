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
     * ⚠️ DIUBAH: yang approve tahap 1 biasanya Dep Head (per unit kerja),
     * tapi struktur itu cuma ada di bandara yang punya akun dep_head (mis.
     * CGK). Untuk bandara yang tidak punya struktur Dep Head, Div Head yang
     * mengambil alih approve tahap 1 (bukan cuma "mengetahui" lagi) — lihat
     * Pengguna::approverTahap1IdleRole(). Kalau bandara ini bahkan tidak
     * punya akun Div Head juga, afet_regional ikut dikabari supaya ada yang
     * sadar dan membuatkan akun approver yang hilang.
     */
    public static function buatPengajuanIdle(Alat $alat, string $pemohon, ?int $idLokasiAsal = null, ?int $idUnit = null): void
    {
        $approverRole = Pengguna::approverTahap1IdleRole($alat->id_bandara);

        // ---- 1. Dep Head ----
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

        // ---- 2. Div Head: "mengetahui" saja kalau Dep Head yang approve di
        // bandara ini, TAPI jadi approver beneran (wajib bertindak) kalau
        // bandara ini tidak punya struktur Dep Head sama sekali.
        $divHeadQuery = Pengguna::whereHas('roles', fn($q) => $q->where('slug', 'div_head'))
            ->where('id_bandara', $alat->id_bandara);

        if ($idLokasiAsal !== null && (clone $divHeadQuery)->whereNotNull('id_lokasi')->exists()) {
            $divHeadQuery->where('id_lokasi', $idLokasiAsal);
        }

        $divHeadIsApprover = $approverRole === 'div_head';

        foreach ($divHeadQuery->get() as $dh) {
            self::kirimKeUser($dh->id_pengguna, [
                'alat_id'   => $alat->id_alat,
                'jenis'     => 'pengajuan_idle',
                'judul'     => $divHeadIsApprover ? 'Pengajuan Idle Masuk' : 'Info: Pengajuan Idle Masuk',
                'pesan'     => $divHeadIsApprover
                    ? "Alat \"{$alat->nama_alat}\" diajukan idle oleh {$pemohon}. Bandara ini belum punya Dep Head, jadi Anda yang perlu memproses persetujuan tahap 1."
                    : "Alat \"{$alat->nama_alat}\" diajukan idle oleh {$pemohon}. Untuk diketahui — menunggu persetujuan Dep Head.",
                'prioritas' => $divHeadIsApprover ? 'sedang' : 'rendah',
                'meta'      => [
                    'nama_alat' => $alat->nama_alat,
                    'pemohon'   => $pemohon,
                    'waktu'     => now()->toDateTimeString(),
                ],
            ]);
        }

        // ---- 3. Bandara ini bahkan tidak punya Dep Head maupun Div Head ----
        // sama sekali — kabari afet_regional supaya ada yang sadar & bikinkan
        // salah satu akun approver tsb, karena pengajuan ini akan nyangkut.
        if ($approverRole === null) {
            self::kirimKeRole('afet_regional', [
                'alat_id'   => $alat->id_alat,
                'jenis'     => 'pengajuan_idle',
                'judul'     => 'Pengajuan Idle Nyangkut — Approver Tahap 1 Tidak Ada',
                'pesan'     => "Alat \"{$alat->nama_alat}\" diajukan idle oleh {$pemohon}, tapi bandaranya belum punya akun Dep Head maupun Div Head. Mohon buatkan salah satu akun tsb supaya pengajuan ini bisa diproses.",
                'prioritas' => 'tinggi',
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

    /**
     * ⚠️ BARU: 1 pengajuan mutasi sekarang bisa mencakup beberapa alat
     * sekaligus (lihat PengajuanMutasi::detailAlat()). Semua method notif
     * mutasi di bawah menerima koleksi alat (bukan 1 Alat lagi), dan
     * method ini merangkumnya jadi 1 baris teks + 1 alat_id representatif
     * (null kalau lebih dari 1 alat, karena kolom alat_id di tabel
     * notifikasi cuma bisa nampung 1 FK).
     *
     * @param iterable<Alat> $alatList
     */
    private static function ringkasAlat(iterable $alatList): array
    {
        $alatList = collect($alatList)->filter();
        $jumlah   = $alatList->count();

        if ($jumlah === 0) {
            return ['alat_id' => null, 'teks' => 'alat'];
        }

        if ($jumlah === 1) {
            $alat = $alatList->first();
            return ['alat_id' => $alat->id_alat, 'teks' => "alat \"{$alat->nama_alat}\""];
        }

        $namaDitampilkan = $alatList->pluck('nama_alat')->take(3)->map(fn($n) => "\"{$n}\"")->implode(', ');
        $sisa = $jumlah - min(3, $jumlah);
        $teks = "{$jumlah} alat ({$namaDitampilkan}" . ($sisa > 0 ? ", dan {$sisa} lainnya" : '') . ')';

        return ['alat_id' => null, 'teks' => $teks];
    }

    /** Tahap 1: pemohon submit → CEO approve, Regional & HO sekadar tahu */
    public static function mutasiDiajukan(PengajuanMutasi $mutasi, iterable $alatList, string $pemohon): void
    {
        $ringkas = self::ringkasAlat($alatList);

        $data = [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'Pengajuan Mutasi Menunggu Approval CEO',
            'pesan'     => "{$ringkas['teks']} diajukan mutasi oleh {$pemohon}. Menunggu approval CEO.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi, ['pemohon' => $pemohon]),
        ];

        self::kirimKeRole('ceo', $data);
        self::kirimKeRole('afet_regional', $data);
        self::kirimKeRole('ho', $data);
    }

    /** Tahap 2: CEO approve → giliran GM Bandara Pemberi */
    public static function mutasiDisetujuiCeo(PengajuanMutasi $mutasi, iterable $alatList, string $approver): void
    {
        $ringkas = self::ringkasAlat($alatList);

        $data = [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'approve_mutasi',
            'judul'     => 'Mutasi Disetujui CEO — Menunggu GM Pemberi',
            'pesan'     => "Pengajuan mutasi {$ringkas['teks']} disetujui CEO ({$approver}). Menunggu approval GM Bandara Pemberi.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi, ['approver' => $approver]),
        ];

        self::kirimKeRoleBandara('gm_kc', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRole('ho', $data);
    }

    /** CEO reject di tahap awal → balik ke pemohon buat revisi (status tidak berubah) */
    public static function mutasiDitolakCeoRevisi(PengajuanMutasi $mutasi, iterable $alatList, string $alasan): void
    {
        $ringkas = self::ringkasAlat($alatList);

        self::kirimKeUser($mutasi->id_pengguna_pemohon, [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'reject_mutasi',
            'judul'     => 'Pengajuan Mutasi Ditolak CEO — Perlu Revisi',
            'pesan'     => "Pengajuan mutasi {$ringkas['teks']} ditolak CEO. Alasan: {$alasan}. Mohon revisi dan ajukan ulang.",
            'prioritas' => 'tinggi',
            'meta'      => self::metaMutasi($mutasi, ['alasan' => $alasan]),
        ]);
    }

    /** GM Pemberi approve → giliran Pemastian Fasilitas Idle. CEO & Regional cuma info. */
    public static function mutasiDisetujuiGm(PengajuanMutasi $mutasi, iterable $alatLanjut, iterable $alatDikeluarkan, string $approver): void
    {
        $ringkas = self::ringkasAlat($alatLanjut);
    
        $jumlahLanjut      = collect($alatLanjut)->filter()->count();
        $jumlahDikeluarkan = collect($alatDikeluarkan)->filter()->count();
    
        $infoJumlah = $jumlahDikeluarkan > 0
            ? "{$jumlahLanjut} alat tersedia & lanjut mutasi, {$jumlahDikeluarkan} alat tidak tersedia & dikeluarkan dari pengajuan ini."
            : "Semua {$jumlahLanjut} alat tersedia & lanjut mutasi.";
    
        $data = [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'approve_mutasi',
            'judul'     => 'Mutasi Disetujui GM Pemberi',
            'pesan'     => "GM Bandara Pemberi ({$approver}) menyetujui mutasi {$ringkas['teks']}. {$infoJumlah} Menunggu Pemastian Fasilitas Idle.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi, [
                'approver'           => $approver,
                'jumlah_lanjut'      => $jumlahLanjut,
                'jumlah_dikeluarkan' => $jumlahDikeluarkan,
            ]),
        ];
    
        self::kirimKeRoleBandara('gm_kc', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRoleBandara('afet_bandara', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRole('afet_regional', $data);
        self::kirimKeRole('ceo', array_merge($data, ['judul' => 'Info: Mutasi Disetujui GM Pemberi']));
        self::kirimKeRole('ho', $data);
    }

    /** GM Pemberi reject → status Ditolak GM Pemberi, pemohon perlu ajukan ulang */
    public static function mutasiDitolakGm(PengajuanMutasi $mutasi, iterable $alatList, string $alasan, string $approver): void
    {
        $ringkas = self::ringkasAlat($alatList);

        $data = [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'reject_mutasi',
            'judul'     => 'Mutasi Ditolak GM Pemberi',
            'pesan'     => "GM Bandara Pemberi ({$approver}) menolak mutasi {$ringkas['teks']}. Alasan: {$alasan}. Mohon ajukan ulang.",
            'prioritas' => 'tinggi',
            'meta'      => self::metaMutasi($mutasi, ['alasan' => $alasan, 'approver' => $approver]),
        ];

        self::kirimKeUser($mutasi->id_pengguna_pemohon, $data);
        self::kirimKeRole('ceo', array_merge($data, ['judul' => 'Info: Mutasi Ditolak GM Pemberi']));
        self::kirimKeRole('ho', $data);
    }

    /** Pemohon ajukan ulang setelah Ditolak GM Pemberi (skip CEO, CEO cuma notif) */
    public static function mutasiDiajukanUlangGm(PengajuanMutasi $mutasi, iterable $alatList): void
    {
        $ringkas = self::ringkasAlat($alatList);

        $data = [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'Mutasi Diajukan Ulang — Menunggu GM Pemberi',
            'pesan'     => "Pemohon mengajukan ulang mutasi {$ringkas['teks']} setelah ditolak. Menunggu approval GM Bandara Pemberi.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi),
        ];

        self::kirimKeRoleBandara('gm_kc', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRole('ceo', array_merge($data, ['judul' => 'Info: Mutasi Diajukan Ulang ke GM Pemberi']));
        self::kirimKeRole('ho', $data);
    }

    /** Dokumen BA Pemastian Fasilitas Idle diupload (GM Pemberi/Admin AFET Pemberi) → AFET Regional review & konfirmasi */
    public static function mutasiDokumenIdleDiupload(PengajuanMutasi $mutasi, iterable $alatList): void
    {
        $ringkas = self::ringkasAlat($alatList);

        self::kirimKeRole('afet_regional', [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'BA Pemastian Fasilitas Idle Perlu Dikonfirmasi',
            'pesan'     => "Dokumen BA Pemastian Fasilitas Idle untuk {$ringkas['teks']} sudah diupload. Mohon direview & dikonfirmasi.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi),
        ]);
    }

    /** AFET Regional menolak Pemastian Fasilitas Idle → balik ke Waiting Approval GM Pemberi */
    public static function mutasiIdleDitolak(PengajuanMutasi $mutasi, iterable $alatList, string $alasan): void
    {
        $ringkas = self::ringkasAlat($alatList);

        $data = [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'reject_mutasi',
            'judul'     => 'Pemastian Fasilitas Idle Ditolak AFET Regional',
            'pesan'     => "AFET Regional menolak pemastian fasilitas idle {$ringkas['teks']}. Alasan: {$alasan}. Dikembalikan ke tahap approval GM Pemberi.",
            'prioritas' => 'tinggi',
            'meta'      => self::metaMutasi($mutasi, ['alasan' => $alasan]),
        ];

        self::kirimKeRoleBandara('gm_kc', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRoleBandara('afet_bandara', $mutasi->id_bandara_pemberi, $data);
        self::kirimKeRole('ho', $data);
    }

    /** AFET Regional konfirmasi fasilitas idle → kode alat & lokasi auto-update, giliran Menunggu Sertifikasi */
    public static function mutasiIdleDikonfirmasi(PengajuanMutasi $mutasi, iterable $alatList): void
    {
        $ringkas = self::ringkasAlat($alatList);

        $data = [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'Fasilitas Idle Dikonfirmasi — Menunggu Sertifikasi',
            'pesan'     => "Fasilitas idle untuk {$ringkas['teks']} sudah dikonfirmasi. Kode alat & bandara sudah diperbarui otomatis. Admin AFET Bandara Penerima bisa lanjut sertifikasi.",
            'prioritas' => 'sedang',
            'meta'      => self::metaMutasi($mutasi),
        ];

        self::kirimKeRoleBandara('afet_bandara', $mutasi->id_bandara_penerima, $data);
        self::kirimKeRoleBandara('gm_kc', $mutasi->id_bandara_penerima, $data);
        self::kirimKeRole('ho', $data);
    }

    /** KC Bandara Penerima konfirmasi BA Penerimaan Barang (tidak wajib bareng sertifikasi) */
    public static function mutasiTerimaBarangDikonfirmasi(PengajuanMutasi $mutasi, iterable $alatList, string $approver): void
    {
        $ringkas = self::ringkasAlat($alatList);

        $data = [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'BA Penerimaan Barang Dikonfirmasi',
            'pesan'     => "KC Bandara Penerima ({$approver}) mengonfirmasi penerimaan barang untuk {$ringkas['teks']}.",
            'prioritas' => 'rendah',
            'meta'      => self::metaMutasi($mutasi, ['approver' => $approver]),
        ];

        self::kirimKeUser($mutasi->id_pengguna_pemohon, $data);
        self::kirimKeRole('ho', $data);
    }

    /** Sertifikasi selesai → arsip akhir ke CEO, HO, Regional, dan pemohon */
    public static function mutasiSelesai(PengajuanMutasi $mutasi, iterable $alatList): void
    {
        $ringkas = self::ringkasAlat($alatList);

        $data = [
            'alat_id'   => $ringkas['alat_id'],
            'jenis'     => 'sertifikasi_mutasi',
            'judul'     => 'Mutasi Selesai',
            'pesan'     => "Proses mutasi {$ringkas['teks']} telah tuntas. Sertifikasi selesai dan didokumentasikan.",
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