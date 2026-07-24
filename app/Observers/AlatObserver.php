<?php

namespace App\Observers;

use App\Models\Alat;
use App\Models\Notifikasi;

class AlatObserver
{
    /**
     * Notifikasi saat alat baru dibuat.
     */
    public function created(Alat $alat): void
    {
        // Eager load relasi agar factory bisa mengambil lokasi & bandara
        $alat->load('lokasi.bandara');

        Notifikasi::buatUntukAlatBaru($alat);

        // Kirim push notification via event
        event(new \App\Events\AlatBaruDitambahkan($alat));
    }

    /**
     * Notifikasi saat status alat berubah.
     */
    public function updated(Alat $alat): void
    {
        if (! $alat->isDirty('status')) {
            return;
        }

        $statusLama = $alat->getOriginal('status');
        $statusBaru = $alat->status;

        match ($statusBaru) {
            'error'   => $this->handleError($alat, $statusLama),
            'offline' => $this->handleOffline($alat, $statusLama),
            'aktif'   => $this->handleOnline($alat, $statusLama),
            default   => null,
        };
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function handleError(Alat $alat, ?string $statusLama): void
    {
        Notifikasi::buatUntukStatusError($alat, ['status_sebelumnya' => $statusLama]);
        event(new \App\Events\StatusAlatBerubah($alat, 'error'));
    }

    private function handleOffline(Alat $alat, ?string $statusLama): void
    {
        Notifikasi::buatUntukStatusOffline($alat, ['status_sebelumnya' => $statusLama]);
        event(new \App\Events\StatusAlatBerubah($alat, 'offline'));
    }

    private function handleOnline(Alat $alat, ?string $statusLama): void
    {
        // Hanya buat notif online jika sebelumnya offline/error
        if (in_array($statusLama, ['offline', 'error'])) {
            Notifikasi::buatUntukStatusOnline($alat);
            event(new \App\Events\StatusAlatBerubah($alat, 'aktif'));
        }
    }
}