<?php

namespace App\Events;

use App\Models\Alat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlatBaruDitambahkan implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Alat $alat) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('notifikasi'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alat.baru';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->alat->id,
            'nama_alat'  => $this->alat->nama_alat,
            'lokasi'     => optional($this->alat->lokasi)->nama_lokasi,
            'bandara'    => optional(optional($this->alat->lokasi)->bandara)->nama_bandara,
            'tanggal'    => now()->toDateTimeString(),
            'jenis'      => 'alat_baru',
            'prioritas'  => 'sedang',
        ];
    }
}