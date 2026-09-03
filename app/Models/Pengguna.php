<?php
// app/Models/Pengguna.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';

    protected $fillable = [
        'nama',
        'username',
        'password',
        'role',
        'id_bandara',
        'id_lokasi',
        'id_unit',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
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

    public function unit()
    {
        return $this->belongsTo(UnitKerja::class, 'id_unit', 'id_unit');
    }

    /**
     * Relasi Many-to-Many ke tabel roles
     * 
     * ⚠️ PERHATIKAN: nama kolom di pivot sesuaikan dengan struktur Anda
     * - Jika pivot pakai 'user_id' → gunakan 'user_id'
     * - Jika pivot pakai 'pengguna_id' → gunakan 'pengguna_id'
     */
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,           // Model tujuan
            'user_has_roles',      // Nama tabel pivot
            'user_id',             // Foreign key di pivot (merujuk ke pengguna)
            'role_id'              // Foreign key di pivot (merujuk ke role)
        );
    }

    // ============================================================
    // CEK PERMISSION
    // ============================================================

    public function hasPermission(string $permissionName): bool
    {
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if ($permission->name === $permissionName) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hasAnyPermission(array $permissionNames): bool
    {
        foreach ($permissionNames as $permName) {
            if ($this->hasPermission($permName)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(array $permissionNames): bool
    {
        foreach ($permissionNames as $permName) {
            if (!$this->hasPermission($permName)) {
                return false;
            }
        }
        return true;
    }

    // ============================================================
    // CEK ROLE
    // ============================================================

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    // ============================================================
    // METHOD TAMBAHAN (UNTUK KEMUDAHAN)
    // ============================================================

    /**
     * Ambil semua permission yang dimiliki user (unique)
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->name;
            }
        }
        return array_unique($permissions);
    }

    /**
     * Cek apakah user adalah admin (AFET Regional, HO, atau CEO)
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['afet_regional', 'ho', 'ceo']);
    }

    /**
     * Cek apakah user bisa mengakses bandara tertentu
     */
    public function canAccessBandara($bandaraId): bool
    {
        // Admin bisa akses semua bandara
        if ($this->isAdmin()) {
            return true;
        }

        // User hanya bisa akses bandara miliknya
        return $this->id_bandara == $bandaraId;
    }

    /**
     * Cek apakah user bisa mengakses lokasi tertentu
     */
    public function canAccessLokasi($lokasiId): bool
    {
        // Admin bisa akses semua lokasi
        if ($this->isAdmin()) {
            return true;
        }

        // User hanya bisa akses lokasi miliknya
        return $this->id_lokasi == $lokasiId;
    }

    /**
     * Cek apakah user adalah teknisi
     */
    public function isTeknisi(): bool
    {
        return $this->hasRole('teknisi');
    }

    /**
     * Cek apakah user adalah AFET Bandara
     */
    public function isAfetBandara(): bool
    {
        return $this->hasRole('afet_bandara');
    }

    /**
     * Cek apakah user adalah Divisi Head
     * ⚠️ Div Head sekarang hanya "mengetahui" (idle.view), tidak approve.
     */
    public function isDivHead(): bool
    {
        return $this->hasRole('div_head');
    }

    /**
     * Cek apakah user adalah Dep Head (per unit kerja, mis. Dep Head SSES,
     * Dep Head BHS, Dep Head SSIT di CGK) — dialah yang approve tahap 1
     * pengajuan idle, menggantikan Div Head.
     */
    public function isDepHead(): bool
    {
        return $this->hasRole('dep_head');
    }

    /**
     * ⚠️ BARU: tentukan role yang berwenang approve TAHAP 1 pengajuan idle
     * untuk 1 bandara tertentu.
     *
     * Struktur "Dep Head per unit kerja" (BHS, CCIT, DANET, SSES, SSIT, dst)
     * saat ini cuma ada di CGK. Bandara lain tidak punya struktur itu, jadi
     * dideteksi otomatis (bukan hardcode kode bandara) supaya kalau suatu
     * saat bandara lain juga dikasih struktur Dep Head, ini tetap jalan
     * tanpa perlu ubah kode:
     *
     * - Kalau bandara ini punya minimal 1 akun 'dep_head' → dialah approver
     *   tahap 1 (perilaku lama, tidak berubah).
     * - Kalau tidak ada akun 'dep_head' sama sekali di bandara ini → 'div_head'
     *   yang mengambil alih approve tahap 1 (bukan cuma "mengetahui" lagi).
     * - Kalau bandara ini bahkan tidak punya akun 'div_head' juga → null,
     *   artinya pengajuan idle di bandara ini akan "nyangkut" di tahap 1
     *   sampai Admin AFET Regional membuatkan salah satu akun tsb.
     */
    public static function approverTahap1IdleRole(?int $idBandara): ?string
    {
        if (! $idBandara) {
            return null;
        }

        if (self::where('role', 'dep_head')->where('id_bandara', $idBandara)->exists()) {
            return 'dep_head';
        }

        if (self::where('role', 'div_head')->where('id_bandara', $idBandara)->exists()) {
            return 'div_head';
        }

        return null;
    }

    /**
     * Cek apakah user adalah GM KC
     */
    public function isGmKc(): bool
    {
        return $this->hasRole('gm_kc');
    }

    /**
     * Ambil nama role pertama user (untuk display)
     */
    public function getRoleNameAttribute(): ?string
    {
        $role = $this->roles()->first();
        return $role?->name ?? $this->role;
    }

    /**
     * Ambil slug role pertama user
     */
    public function getRoleSlugAttribute(): ?string
    {
        $role = $this->roles()->first();
        return $role?->slug ?? $this->role;
    }

    /**
     * Ambil nama bandara
     */
    public function getNamaBandaraAttribute(): ?string
    {
        return $this->bandara?->nama_bandara ?? null;
    }

    /**
     * Ambil nama lokasi
     */
    public function getNamaLokasiAttribute(): ?string
    {
        return $this->lokasi?->nama_lokasi ?? null;
    }

    /**
     * Ambil nama unit kerja (kalau ada)
     */
    public function getNamaUnitAttribute(): ?string
    {
        return $this->unit?->nama_unit ?? null;
    }

    /**
     * Cek apakah user bisa mengakses unit kerja tertentu
     */
    public function canAccessUnit($idUnit): bool
    {
        // Admin bisa akses semua unit
        if ($this->isAdmin()) {
            return true;
        }

        // Kalau user tidak terikat unit tertentu (id_unit null),
        // dia dianggap boleh akses semua unit di bandaranya.
        if (!$this->id_unit) {
            return true;
        }

        return $this->id_unit == $idUnit;
    }
}