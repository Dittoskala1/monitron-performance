<?php

namespace App\Events;

use App\Models\Alat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StatusAlatBerubah implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Alat   $alat,
        public string $statusBaru,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('notifikasi'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alat.status';
    }

    public function broadcastWith(): array
    {
        $prioritas = match ($this->statusBaru) {
            'error'   => 'kritis',
            'offline' => 'tinggi',
            default   => 'rendah',
        };

        return [
            'id'          => $this->alat->id,
            'nama_alat'   => $this->alat->nama_alat,
            'status_baru' => $this->statusBaru,
            'lokasi'      => optional($this->alat->lokasi)->nama_lokasi,
            'bandara'     => optional(optional($this->alat->lokasi)->bandara)->nama_bandara,
            'tanggal'     => now()->toDateTimeString(),
            'jenis'       => 'status_' . $this->statusBaru,
            'prioritas'   => $prioritas,
        ];
    }
}