<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LogHarian;
use App\Models\Alat;
use App\Models\Bandara;

class DataHarianController extends Controller
{
    /**
     * Halaman utama Data Harian — defaultnya KALENDER.
     */
    public function index(Request $request)
    {
        $role      = session('pengguna.role');
        $isLocked  = $this->isBandaraLocked($role);
        $idBandara = session('pengguna.id_bandara');
        $unit      = $this->unitKerjaSaya();

        $bandara = $isLocked
            ? Bandara::where('id_bandara', $idBandara)->get()
            : Bandara::orderBy('nama_bandara')->get();

        $alat = Alat::with('lokasi.bandara')
            ->when($isLocked, function ($q) use ($idBandara) {
                $q->whereHas('lokasi', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q))
            ->orderBy('nama_alat')
            ->get();

        return view('admin.data-harian.calendar', compact('bandara', 'alat', 'isLocked'));
    }

    /**
     * Tampilan tabel (yang lama), di /data-harian/tabel.
     */
    public function table(Request $request)
    {
        $role      = session('pengguna.role');
        $isLocked  = $this->isBandaraLocked($role);
        $idBandara = session('pengguna.id_bandara');
        $unit      = $this->unitKerjaSaya();

        $logs = LogHarian::with(['alat.lokasi.bandara', 'pengguna'])
            ->when($isLocked, function ($q) use ($idBandara) {
                $q->whereHas('alat.lokasi', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when(!$isLocked && $request->id_bandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q2) => $q2->where('id_bandara', $request->id_bandara)
            ))
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
            ->when($request->id_alat, fn($q) => $q->where('id_alat', $request->id_alat))
            ->when($request->tanggal,  fn($q) => $q->whereDate('tanggal', $request->tanggal))
            ->when($request->kondisi,  fn($q) => $q->where('kondisi', $request->kondisi))
            ->orderByDesc('tanggal')
            ->paginate(15);

        $bandara = $isLocked
            ? Bandara::where('id_bandara', $idBandara)->get()
            : Bandara::orderBy('nama_bandara')->get();

        $alat = Alat::with('lokasi.bandara')
            ->when($isLocked, function ($q) use ($idBandara) {
                $q->whereHas('lokasi', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q))
            ->orderBy('nama_alat')
            ->get();

        return view('admin.data-harian.index', compact('logs', 'bandara', 'alat', 'isLocked'));
    }

    /**
     * Detail 1 log harian.
     */
    public function show($id)
    {
        $role      = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $log = LogHarian::with(['alat.lokasi.bandara', 'pengguna'])
            ->findOrFail($id);

        if ($this->isBandaraLocked($role) && ($log->alat->lokasi->id_bandara ?? null) != $idBandara) {
            abort(403, 'Anda tidak memiliki akses ke data log ini.');
        }

        if (! $this->alatMasukCakupanUnit($log->alat)) {
            abort(403, 'Alat ini bukan cakupan unit kerja Anda.');
        }

        return view('admin.data-harian.show', compact('log'));
    }

    /**
     * Endpoint JSON untuk FullCalendar.
     */
    public function events(Request $request)
    {
        $role      = session('pengguna.role');
        $isLocked  = $this->isBandaraLocked($role);
        $idBandara = session('pengguna.id_bandara');
        $unit      = $this->unitKerjaSaya();

        $logs = LogHarian::with(['alat.lokasi.bandara', 'pengguna'])
            ->when($isLocked, function ($q) use ($idBandara) {
                $q->whereHas('alat.lokasi', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when(!$isLocked && $request->id_bandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q2) => $q2->where('id_bandara', $request->id_bandara)
            ))
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q, 'alat'))
            ->when($request->id_alat, fn($q) => $q->where('id_alat', $request->id_alat))
            ->when($request->kondisi, fn($q) => $q->where('kondisi', $request->kondisi))
            ->when($request->start, fn($q) => $q->whereDate('tanggal', '>=', $request->start))
            ->when($request->end,   fn($q) => $q->whereDate('tanggal', '<=', $request->end))
            ->get();

        $events = $logs->map(function ($log) {
            $warna = $this->warnaKondisi($log->kondisi, $log->performa);

            return [
                'id'    => $log->id_log,
                'title' => ($log->alat->nama_alat ?? 'Alat') . ' - ' . $log->kondisi,
                'start' => \Carbon\Carbon::parse($log->tanggal)->format('Y-m-d'),
                'color' => $warna,
                'extendedProps' => [
                    'id_log'        => $log->id_log,
                    'alat'          => $log->alat->nama_alat ?? '-',
                    'lokasi'        => $log->alat->lokasi->nama_lokasi ?? '-',
                    'detail_lokasi' => $log->detail_lokasi ?? '-',
                    'bandara'       => $log->alat->lokasi->bandara->kode_bandara ?? '-',
                    'kondisi'       => $log->kondisi,
                    'performa'      => round($log->performa, 2),
                    'jam_operasional' => $log->jam_operasional,
                    'jam_terputus'  => $log->jam_terputus,
                    'teknisi'       => $log->pengguna->nama ?? '-',
                    'catatan'       => $log->catatan ?? '-',
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Tentukan warna event berdasarkan kondisi + performa.
     * Biru   = Normal & performa >= 90%
     * Orange = Gangguan, ATAU Normal tapi performa < 90%
     * Merah  = Rusak
     */
    private function warnaKondisi($kondisi, $performa)
    {
        if ($kondisi === 'Rusak') {
            return '#dc3545';
        }

        if ($kondisi === 'Gangguan') {
            return '#fd7e14';
        }

        if ($performa < 90) {
            return '#fd7e14';
        }

        return '#0d6efd';
    }
}