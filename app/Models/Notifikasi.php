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
        'status_error'         => 'Error',
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

    public static function buatPengajuanIdle(Alat $alat, string $pemohon, ?int $idLokasiAsal = null): void
    {
        $divHeadQuery = Pengguna::whereHas('roles', fn($q) => $q->where('slug', 'div_head'))
            ->where('id_bandara', $alat->id_bandara);

        // Kalau ada Div Head per-terminal di bandara ini, filter lebih spesifik ke terminal asal
        if ($idLokasiAsal !== null && (clone $divHeadQuery)->whereNotNull('id_lokasi')->exists()) {
            $divHeadQuery->where('id_lokasi', $idLokasiAsal);
        }

        $divHeads = $divHeadQuery->get();

        foreach ($divHeads as $dh) {
            self::kirimKeUser($dh->id_pengguna, [
                'alat_id'   => $alat->id_alat,
                'jenis'     => 'pengajuan_idle',
                'judul'     => 'Pengajuan Idle Masuk',
                'pesan'     => "Alat \"{$alat->nama_alat}\" diajukan idle oleh {$pemohon}. Menunggu persetujuan Div Head.",
                'prioritas' => 'sedang',
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
     * Regional setelah Div Head approve tahap 1. Dipisah dari
     * buatPengajuanIdle() karena method itu cuma menyasar role div_head.
     */
    public static function buatMenungguApprovalAfetRegional(Alat $alat, string $pemohon): void
    {
        self::kirimKeRole('afet_regional', [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'pengajuan_idle',
            'judul'     => 'Pengajuan Idle Menunggu Approval Final',
            'pesan'     => "Alat \"{$alat->nama_alat}\" diajukan idle oleh {$pemohon}, sudah disetujui Div Head. Menunggu approval Anda.",
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
    }

    // ==========================================
    // MUTASI NOTIFIKASI
    // ==========================================
    public static function buatPengajuanMutasi(Alat $alat, string $pemohon, int $penerimaId): self
    {
        return self::kirimKeUser($penerimaId, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'pengajuan_mutasi',
            'judul'     => 'Pengajuan Mutasi Masuk',
            'pesan'     => "Alat \"{$alat->nama_alat}\" diajukan mutasi oleh {$pemohon}. Menunggu persetujuan Regional.",
            'prioritas' => 'sedang',
            'meta'      => [
                'nama_alat' => $alat->nama_alat,
                'pemohon'   => $pemohon,
                'waktu'     => now()->toDateTimeString(),
            ],
        ]);
    }

    public static function buatKeputusanMutasi(Alat $alat, string $status, string $approver, int $penerimaId, ?string $alasan = null): self
    {
        $approved = $status === 'Approved';
        return self::kirimKeUser($penerimaId, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => $approved ? 'approve_mutasi' : 'reject_mutasi',
            'judul'     => $approved ? 'Pengajuan Mutasi Disetujui' : 'Pengajuan Mutasi Ditolak',
            'pesan'     => $approved
                ? "Pengajuan mutasi alat \"{$alat->nama_alat}\" disetujui oleh {$approver}."
                : "Pengajuan mutasi alat \"{$alat->nama_alat}\" ditolak oleh {$approver}. Alasan: {$alasan}",
            'prioritas' => $approved ? 'sedang' : 'tinggi',
            'meta'      => [
                'nama_alat' => $alat->nama_alat,
                'status'    => $status,
                'approver'  => $approver,
                'alasan'    => $alasan,
                'waktu'     => now()->toDateTimeString(),
            ],
        ]);
    }

    public static function buatMobilisasiMutasi(Alat $alat, array $penerimaIds): void
    {
        self::kirimKeBanyakUser($penerimaIds, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'mobilisasi_mutasi',
            'judul'     => 'Mobilisasi Fasilitas Dimulai',
            'pesan'     => "Fasilitas \"{$alat->nama_alat}\" sedang dimobilisasi ke lokasi tujuan.",
            'prioritas' => 'tinggi',
            'meta'      => [
                'nama_alat' => $alat->nama_alat,
                'waktu'     => now()->toDateTimeString(),
            ],
        ]);
    }

    public static function buatSertifikasiMutasi(Alat $alat, int $penerimaId): self
    {
        return self::kirimKeUser($penerimaId, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'sertifikasi_mutasi',
            'judul'     => 'Sertifikasi Mutasi Selesai',
            'pesan'     => "Sertifikasi mutasi untuk alat \"{$alat->nama_alat}\" telah selesai dan didokumentasikan.",
            'prioritas' => 'sedang',
            'meta'      => [
                'nama_alat' => $alat->nama_alat,
                'waktu'     => now()->toDateTimeString(),
            ],
        ]);
    }

    public static function buatUntukStatusError(Alat $alat, int $penerimaId, array $detail = []): self
    {
        return self::kirimKeUser($penerimaId, [
            'alat_id'   => $alat->id_alat,
            'jenis'     => 'status_error',
            'judul'     => 'Alat Mengalami Error',
            'pesan'     => "Alat \"{$alat->nama_alat}\" terdeteksi mengalami error dan membutuhkan perhatian segera.",
            'prioritas' => 'kritis',
            'meta'      => array_merge([
                'nama_alat' => $alat->nama_alat,
                'waktu'     => now()->toDateTimeString(),
            ], $detail),
        ]);
    }
}