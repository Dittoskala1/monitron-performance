<?php
// app/Models/UserRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    protected $table = 'user_requests';

    protected $fillable = [
        'nama',
        'username',
        'email',
        'password',
        'role_requested',
        'id_bandara',
        'id_lokasi',
        'reason',
        'status',
        'rejection_reason',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // ==========================================
    // RELASI
    // ==========================================

    public function bandara()
    {
        return $this->belongsTo(Bandara::class, 'id_bandara', 'id_bandara');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi', 'id_lokasi');
    }

    // ==========================================
    // CEK STATUS
    // ==========================================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    // ==========================================
    // ACTION
    // ==========================================

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function reject(string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);
    }
}