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
     */
    public function isDivHead(): bool
    {
        return $this->hasRole('div_head');
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
}