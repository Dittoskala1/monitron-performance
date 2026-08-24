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
     * Notifikasi saat kondisi kesehatan alat berubah (Normal / Gangguan /
     * Rusak). Nilai ini di-set OTOMATIS oleh LogHarianObserver berdasarkan
     * entri log_harian paling baru — bukan diedit manual dari form Kelola
     * Alat.
     */
    public function updated(Alat $alat): void
    {
        if (! $alat->isDirty('kondisi_terkini')) {
            return;
        }

        $kondisiLama = $alat->getOriginal('kondisi_terkini');
        $kondisiBaru = $alat->kondisi_terkini;

        match ($kondisiBaru) {
            'Rusak'    => $this->handleRusak($alat, $kondisiLama),
            'Gangguan' => $this->handleGangguan($alat, $kondisiLama),
            'Normal'   => $this->handleNormal($alat, $kondisiLama),
            default    => null,
        };
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function handleRusak(Alat $alat, ?string $kondisiLama): void
    {
        Notifikasi::buatUntukKondisiRusak($alat, ['kondisi_sebelumnya' => $kondisiLama]);
        event(new \App\Events\StatusAlatBerubah($alat, 'Rusak'));
    }

    private function handleGangguan(Alat $alat, ?string $kondisiLama): void
    {
        Notifikasi::buatUntukKondisiGangguan($alat, ['kondisi_sebelumnya' => $kondisiLama]);
        event(new \App\Events\StatusAlatBerubah($alat, 'Gangguan'));
    }

    private function handleNormal(Alat $alat, ?string $kondisiLama): void
    {
        // Hanya buat notif "pulih" kalau sebelumnya memang Gangguan/Rusak
        // (bukan waktu alat baru dibuat & kondisi_terkini default 'Normal').
        if (in_array($kondisiLama, ['Gangguan', 'Rusak'], true)) {
            Notifikasi::buatUntukKondisiNormal($alat, ['kondisi_sebelumnya' => $kondisiLama]);
            event(new \App\Events\StatusAlatBerubah($alat, 'Normal'));
        }
    }
}