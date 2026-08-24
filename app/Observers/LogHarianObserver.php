<?php

namespace App\Observers;

use App\Models\LogHarian;

class LogHarianObserver
{
    /**
     * Log harian baru diinput (mis. dari TeknisiController::inputLog,
     * atau LogHarian::updateOrCreate di LaporanPerbaikanController).
     */
    public function created(LogHarian $log): void
    {
        $this->sinkronKondisiAlat($log);
    }

    /**
     * Log harian diedit (mis. TeknisiController::updateLog, atau
     * LaporanPerbaikanController pas nge-update log yang sudah ada lewat
     * updateOrCreate).
     */
    public function updated(LogHarian $log): void
    {
        if (! $log->isDirty('kondisi')) {
            return;
        }

        $this->sinkronKondisiAlat($log);
    }

    /**
     * Ambil entri log_harian PALING BARU (by tanggal, bukan yang baru saja
     * disimpan — supaya kalau teknisi input/edit log utk tanggal lampau,
     * kondisi_terkini alat tetap merefleksikan hari paling akhir, bukan
     * ketimpa data lama) untuk alat ini, lalu simpan kondisinya ke
     * Alat::kondisi_terkini. Update ini yang men-trigger AlatObserver.
     */
    private function sinkronKondisiAlat(LogHarian $log): void
    {
        $alat = $log->alat;

        if (! $alat) {
            return;
        }

        $logTerbaru = LogHarian::where('id_alat', $alat->id_alat)
            ->orderByDesc('tanggal')
            ->orderByDesc('id_log')
            ->first();

        if (! $logTerbaru) {
            return;
        }

        if ($alat->kondisi_terkini === $logTerbaru->kondisi) {
            return;
        }

        $alat->update([
            'kondisi_terkini'    => $logTerbaru->kondisi,
            'kondisi_terkini_at' => now(),
        ]);
    }
}