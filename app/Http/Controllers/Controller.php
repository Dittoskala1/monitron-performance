<?php

namespace App\Http\Controllers;

use App\Models\UnitKerja;
use Illuminate\Support\Facades\DB;

abstract class Controller
{
    /**
     * Role yang aksesnya DIKUNCI ke bandaranya sendiri — tidak boleh
     * override bandara lewat query string (?id_bandara=X).
     * Role di luar daftar ini (afet_regional, ho, ceo) bebas lihat/pilih
     * bandara manapun (atau "Semua Bandara").
     */
    protected const ROLES_BANDARA_ONLY = ['afet_bandara', 'div_head', 'dep_head', 'gm_kc'];

    /**
     * Apakah role ini terkunci ke 1 bandara (bandaranya sendiri)?
     */
    protected function isBandaraLocked(?string $role): bool
    {
        return in_array($role, static::ROLES_BANDARA_ONLY, true);
    }

    /**
     * ⚠️ BARU: unit kerja user yang sedang login, kalau akunnya terikat
     * ke 1 unit spesifik (mis. SSES T1 di CGK, lewat afet_cgk_sses_t1).
     * Null kalau user tidak terikat unit tertentu (id_unit null) — berarti
     * dia boleh lihat semua unit di bandaranya (perilaku lama, tidak
     * berubah).
     */
    protected function unitKerjaSaya(): ?UnitKerja
    {
        $idUnit = session('pengguna.id_unit');

        if (!$idUnit) {
            return null;
        }

        return UnitKerja::find($idUnit);
    }

    /**
     * ⚠️ BARU: batasi query ke lokasi + jenis alat yang jadi cakupan unit
     * kerja user (dipanggil setelah unitKerjaSaya() mengembalikan unit).
     *
     * - Kalau $query langsung ke tabel yang punya kolom id_lokasi/jenis_alat
     *   (mis. Alat), panggil tanpa $relasi: scopeByUnitKerja($query).
     * - Kalau $query ke model lain yang berelasi ke Alat (mis. LogHarian,
     *   LaporanPerbaikan, PengajuanIdle), kasih nama relasinya:
     *   scopeByUnitKerja($query, 'alat').
     */
    protected function scopeByUnitKerja($query, ?string $relasi = null)
    {
        $unit = $this->unitKerjaSaya();

        if (!$unit) {
            return $query;
        }

        $terapkan = function ($q) use ($unit) {
            if ($unit->id_lokasi) {
                // ⚠️ BARU: alat tetap dianggap "milik" unit ini kalau lokasinya
                // sekarang masih lokasi unit ITU SENDIRI, ATAU alat ini baru
                // saja diidle-kan dari lokasi unit ini (sekarang duduk di
                // lokasi "Unused" bandaranya) — supaya alat idle tidak
                // "menghilang" dari pandangan unit yang mengajukannya, dan
                // tetap bisa ditarik kembali.
                $q->where(function ($qq) use ($unit) {
                    $qq->where('id_lokasi', $unit->id_lokasi)
                       ->orWhereHas('pengajuanIdle', function ($q2) use ($unit) {
                            $q2->where('id_lokasi_asal', $unit->id_lokasi)
                               ->where('status', 'Approved');
                        });
                });
            }

            if (!empty($unit->cakupan_alat)) {
                // Case-insensitive: data lama ada yang 'X-RAY', ada yang 'X-Ray'.
                $cakupanLower = array_map('strtolower', $unit->cakupan_alat);
                $q->whereIn(DB::raw('LOWER(jenis_alat)'), $cakupanLower);
            }
        };

        if ($relasi) {
            return $query->whereHas($relasi, $terapkan);
        }

        $terapkan($query);

        return $query;
    }

    /**
     * ⚠️ BARU: versi single-record dari scopeByUnitKerja() — dipakai saat
     * kita sudah punya 1 objek Alat (bukan query builder) dan perlu cek
     * apakah alat itu masuk cakupan unit kerja user yang sedang login.
     *
     * - User tidak terikat unit tertentu (unitKerjaSaya() null) → selalu
     *   dianggap masuk cakupan (perilaku lama, tidak berubah).
     * - User terikat unit → alat harus cocok id_lokasi (kalau unit
     *   mensyaratkan lokasi tertentu) DAN jenis_alat termasuk cakupan_alat
     *   unit itu (case-insensitive, kalau cakupan_alat diisi).
     */
    protected function alatMasukCakupanUnit(?\App\Models\Alat $alat): bool
    {
        $unit = $this->unitKerjaSaya();

        if (!$unit) {
            return true;
        }

        if (!$alat) {
            return false;
        }

        if ($unit->id_lokasi && $alat->id_lokasi != $unit->id_lokasi) {
            // ⚠️ BARU: lolos juga kalau alat ini lokasinya sekarang beda
            // (mis. sudah pindah ke "Unused") TAPI riwayat idle-nya
            // menunjukkan alat ini berasal dari lokasi unit ini.
            $pernahIdleDariUnitIni = $alat->pengajuanIdle()
                ->where('id_lokasi_asal', $unit->id_lokasi)
                ->where('status', 'Approved')
                ->exists();

            if (! $pernahIdleDariUnitIni) {
                return false;
            }
        }

        if (!empty($unit->cakupan_alat)) {
            $cakupanLower = array_map('strtolower', $unit->cakupan_alat);
            if (!in_array(strtolower((string) $alat->jenis_alat), $cakupanLower, true)) {
                return false;
            }
        }

        return true;
    }
}