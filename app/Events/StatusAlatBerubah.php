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
        public string $kondisiBaru,
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
        $prioritas = match ($this->kondisiBaru) {
            'Rusak'    => 'kritis',
            'Gangguan' => 'tinggi',
            default    => 'rendah',
        };

        return [
            // ⚠️ Sebelumnya $this->alat->id — selalu null karena primary
            // key Alat itu id_alat, bukan id. Dibenerin di sini.
            'id'           => $this->alat->id_alat,
            'nama_alat'    => $this->alat->nama_alat,
            'kondisi_baru' => $this->kondisiBaru,
            'lokasi'       => optional($this->alat->lokasi)->nama_lokasi,
            'bandara'      => optional(optional($this->alat->lokasi)->bandara)->nama_bandara,
            'tanggal'      => now()->toDateTimeString(),
            'jenis'        => 'kondisi_' . strtolower($this->kondisiBaru),
            'prioritas'    => $prioritas,
        ];
    }
}