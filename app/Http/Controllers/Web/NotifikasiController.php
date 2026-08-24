<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\Bandara;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $role      = session('pengguna.role');
        $isLocked  = $this->isBandaraLocked($role);
        $idBandara = session('pengguna.id_bandara');
        $userId    = session('pengguna.id');

        // ==========================================
        // QUERY NOTIFIKASI (HANYA MILIK USER YANG LOGIN)
        // ==========================================
        $notifikasi = Notifikasi::with(['alat.lokasi.bandara', 'pengguna'])
            ->untukPengguna($userId)  // ← FILTER PER USER
            ->when($isLocked, fn($q) => $q->byBandara($idBandara))
            ->when(!$isLocked && $request->id_bandara, fn($q) => $q->byBandara($request->id_bandara))
            ->when($request->status,          fn($q) => $q->where('status', $request->status))
            ->when($request->jenis,           fn($q) => $q->where('jenis', $request->jenis))
            ->when($request->prioritas,       fn($q) => $q->where('prioritas', $request->prioritas))
            ->when($request->tanggal_dari,    fn($q) => $q->where('tanggal', '>=', $request->tanggal_dari))
            ->when($request->tanggal_sampai,  fn($q) => $q->where('tanggal', '<=', $request->tanggal_sampai . ' 23:59:59'))
            ->orderByRaw("FIELD(prioritas, 'kritis', 'tinggi', 'sedang', 'rendah')")
            ->orderByDesc('tanggal')
            ->paginate(15)
            ->withQueryString();

        // ==========================================
        // FILTER BANDARA (hanya untuk role yang tidak dikunci)
        // ==========================================
        $bandara = !$isLocked
            ? Bandara::orderBy('nama_bandara')->get()
            : collect();

        // ==========================================
        // STATISTIK (HANYA MILIK USER YANG LOGIN)
        // ==========================================
        $jumlahBelumDibaca = Notifikasi::belumDibaca()
            ->untukPengguna($userId)
            ->when($isLocked, fn($q) => $q->byBandara($idBandara))
            ->count();

        $jumlahTotal = Notifikasi::untukPengguna($userId)->count();
        $jumlahDibaca = Notifikasi::sudahDibaca()
            ->untukPengguna($userId)
            ->when($isLocked, fn($q) => $q->byBandara($idBandara))
            ->count();
        $jumlahKritis = Notifikasi::kritis()
            ->untukPengguna($userId)
            ->when($isLocked, fn($q) => $q->byBandara($idBandara))
            ->count();

        $statistik = [
            'total' => $jumlahTotal,
            'belum_dibaca' => $jumlahBelumDibaca,
            'dibaca' => $jumlahDibaca,
            'kritis' => $jumlahKritis,
        ];

        // ==========================================
        // DATA UNTUK DROPDOWN FILTER
        // ==========================================
        $jenisOptions     = Notifikasi::JENIS;
        $prioritasOptions = Notifikasi::PRIORITAS;

        return view('admin.notifikasi.index', compact(
            'notifikasi',
            'bandara',
            'statistik',
            'jumlahBelumDibaca',
            'jenisOptions',
            'prioritasOptions',
        ));
    }

    /**
     * Tandai notifikasi sudah dibaca.
     */
    public function baca($id)
    {
        $userId = session('pengguna.id');

        // Hanya bisa baca notifikasi milik sendiri
        $notifikasi = Notifikasi::untukPengguna($userId)
            ->where('id', $id)
            ->firstOrFail();

        $notifikasi->tandaiDibaca();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'id' => $id]);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Tandai semua notifikasi sudah dibaca.
     */
    public function bacaSemua(Request $request)
    {
        $role      = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');
        $userId    = session('pengguna.id');
        $isLocked  = $this->isBandaraLocked($role);

        // Hanya untuk notifikasi milik sendiri
        $query = Notifikasi::belumDibaca()
            ->untukPengguna($userId)
            ->when($isLocked, fn($q) => $q->byBandara($idBandara))
            ->when(!$isLocked && $request->id_bandara, fn($q) => $q->byBandara($request->id_bandara));

        $jumlah = $query->count();
        $query->update([
            'status'      => 'Dibaca',
            'dibaca_pada' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'jumlah' => $jumlah]);
        }

        return back()->with('success', "{$jumlah} notifikasi ditandai sudah dibaca.");
    }

    /**
     * Hapus notifikasi.
     */
    public function hapus($id)
    {
        $userId = session('pengguna.id');

        // Hanya bisa hapus notifikasi milik sendiri
        $notifikasi = Notifikasi::untukPengguna($userId)
            ->where('id', $id)
            ->firstOrFail();

        $notifikasi->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notifikasi dihapus.');
    }

    /**
     * API: Jumlah notifikasi belum dibaca (untuk badge).
     */
    public function jumlahBelumDibaca(): JsonResponse
    {
        $role      = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');
        $userId    = session('pengguna.id');

        $query = Notifikasi::belumDibaca()
            ->untukPengguna($userId)
            ->when($this->isBandaraLocked($role), fn($q) => $q->byBandara($idBandara));

        return response()->json([
            'jumlah' => $query->count(),
            'kritis' => (clone $query)->kritis()->count(),
        ]);
    }

    /**
     * API: Notifikasi terbaru (untuk dropdown).
     */
    public function terbaru(): JsonResponse
    {
        $role      = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');
        $userId    = session('pengguna.id');
        $isLocked  = $this->isBandaraLocked($role);

        $notifikasi = Notifikasi::with(['alat.lokasi.bandara'])
            ->belumDibaca()
            ->untukPengguna($userId)
            ->when($isLocked, fn($q) => $q->byBandara($idBandara))
            ->orderByRaw("FIELD(prioritas, 'kritis', 'tinggi', 'sedang', 'rendah')")
            ->orderByDesc('tanggal')
            ->limit(5)
            ->get()
            ->map(fn($n) => [
                'id'          => $n->id,
                'judul'       => $n->judul,
                'pesan'       => $n->pesan,
                'jenis'       => $n->jenis,
                'label_jenis' => $n->label_jenis,
                'prioritas'   => $n->prioritas,
                'warna'       => $n->warna_prioritas,
                'ikon'        => $n->ikon,
                'alat'        => optional($n->alat)->nama_alat,
                'bandara'     => optional(optional(optional($n->alat)->lokasi)->bandara)->nama_bandara,
                'tanggal'     => $n->tanggal->diffForHumans(),
                'url_baca'    => route('admin.notifikasi.baca', $n->id),
            ]);

        $jumlah = Notifikasi::belumDibaca()
            ->untukPengguna($userId)
            ->when($isLocked, fn($q) => $q->byBandara($idBandara))
            ->count();

        return response()->json([
            'data'   => $notifikasi,
            'jumlah' => $jumlah,
        ]);
    }
}