<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PengajuanBooking;
use App\Models\PengajuanIdle;
use Illuminate\Support\Facades\DB;

class PengajuanBookingController extends Controller
{
    /**
     * Daftar alat idle dari SELURUH bandara yang bisa di-booking
     *
     * ⚠️ DIPERBAIKI: filter `id_bandara != $idBandara` dibungkus ->when(),
     * karena untuk role tanpa id_bandara spesifik (CEO, HO) nilai
     * session('pengguna.id_bandara') adalah NULL. Kondisi SQL
     * "id_bandara != NULL" selalu bernilai UNKNOWN (bukan true),
     * sehingga sebelumnya seluruh data ikut ter-filter habis / kosong
     * untuk role tersebut. Sekarang filter itu HANYA diterapkan kalau
     * user memang punya id_bandara (mis. afet_bandara).
     */
    public function index(Request $request)
    {
        $idBandara = session('pengguna.id_bandara');

        $alatIdle = PengajuanIdle::with(['alat.lokasi.bandara', 'alat.bandara'])
            ->where('status', 'Approved')
            ->where('status_ketersediaan', 'available')
            ->when($idBandara, function ($q) use ($idBandara) {
                $q->whereHas('alat', fn($q2) => $q2->where('id_bandara', '!=', $idBandara));
            })
            ->when($request->filled('id_bandara'), function ($q) use ($request) {
                $q->whereHas('alat', fn($q2) => $q2->where('id_bandara', $request->id_bandara));
            })
            ->orderBy('tanggal_keputusan', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Booking aktif milik user yang login. Untuk role tanpa id_pengguna
        // yang relevan (CEO/HO cuma lihat, tidak pernah booking), ini
        // otomatis kosong dan itu memang perilaku yang diharapkan.
        $bookingSaya = PengajuanBooking::with(['pengajuanIdle.alat.bandara'])
            ->where('id_pengguna_pemesan', session('pengguna.id'))
            ->where('status', 'Aktif')
            ->get();

        $bandaraList = \App\Models\Bandara::when($idBandara, function ($q) use ($idBandara) {
                $q->where('id_bandara', '!=', $idBandara);
            })
            ->orderBy('nama_bandara')
            ->get();

        return view('admin.peralatan-booking.index', compact('alatIdle', 'bookingSaya', 'bandaraList'));
    }

    /**
     * Detail alat idle yang tersedia untuk di-booking
     *
     * Catatan: untuk CEO/HO, $idBandara bernilai NULL. Kondisi
     * "alat->id_bandara == null" akan selalu FALSE (karena
     * alat->id_bandara pasti terisi), jadi mereka tidak ter-block oleh
     * pengecekan ini — behaviour ini sudah benar tanpa perlu diubah.
     */
    public function show($id)
    {
        $idBandara = session('pengguna.id_bandara');

        $pengajuanIdle = PengajuanIdle::with(['alat.lokasi.bandara', 'alat.bandara', 'lokasiAsal', 'dokumen'])
            ->where('status', 'Approved')
            ->where('status_ketersediaan', 'available')
            ->findOrFail($id);

        if ($idBandara && $pengajuanIdle->alat->id_bandara == $idBandara) {
            abort(403, 'Anda tidak bisa booking alat milik bandara sendiri.');
        }

        return view('admin.peralatan-booking.show', compact('pengajuanIdle'));
    }

    /**
     * Booking alat — cuma lock, tanpa approval
     *
     * Route ini dikunci middleware permission:booking.create di
     * routes/web.php. Secara desain hanya AFET Bandara yang diberi
     * permission ini lewat halaman Role & Permission, jadi $idBandara
     * di sini seharusnya selalu terisi — tapi tetap divalidasi di bawah
     * untuk jaga-jaga kalau suatu saat role lain diberi permission ini juga.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_pengajuan_idle' => 'required|exists:pengajuan_idle,id_pengajuan',
        ]);

        $idBandara = session('pengguna.id_bandara');

        $pengajuanIdle = PengajuanIdle::with('alat')->findOrFail($request->id_pengajuan_idle);

        if ($pengajuanIdle->status !== 'Approved' || $pengajuanIdle->status_ketersediaan !== 'available') {
            return back()->withErrors(['id_pengajuan_idle' => 'Alat ini tidak tersedia untuk di-booking saat ini.']);
        }

        if ($pengajuanIdle->alat->id_bandara == $idBandara) {
            return back()->withErrors(['id_pengajuan_idle' => 'Tidak bisa booking alat milik bandara sendiri.']);
        }

        DB::transaction(function () use ($pengajuanIdle, $idBandara) {
            PengajuanBooking::create([
                'id_pengajuan_idle'   => $pengajuanIdle->id_pengajuan,
                'kode_alat_snapshot'  => $pengajuanIdle->alat->kode_alat,
                'nama_alat_snapshot'  => $pengajuanIdle->alat->nama_alat,
                'id_pengguna_pemesan' => session('pengguna.id'),
                'id_bandara_penerima' => $idBandara,
                'status'              => 'Aktif',
                'tanggal_booking'     => now(),
            ]);

            // Lock alat supaya tidak bisa di-booking KC lain
            $pengajuanIdle->update(['status_ketersediaan' => 'booked']);
        });

        return redirect()->route('admin.peralatan-booking.index')
            ->with('success', 'Alat berhasil di-booking. Lanjutkan dengan Input Mapping Kebutuhan.');
    }

    /**
     * Batalkan booking (sebelum lanjut ke mapping kebutuhan)
     *
     * Route ini dikunci middleware permission:booking.cancel di
     * routes/web.php.
     */
    public function cancel($id)
    {
        $booking = PengajuanBooking::with('pengajuanIdle')->findOrFail($id);

        if ($booking->id_pengguna_pemesan != session('pengguna.id')) {
            abort(403, 'Anda tidak memiliki akses untuk membatalkan booking ini.');
        }

        if ($booking->status !== 'Aktif') {
            return back()->withErrors(['status' => 'Booking ini sudah tidak aktif / sudah lanjut ke proses mutasi.']);
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'Dibatalkan']);
            $booking->pengajuanIdle->update(['status_ketersediaan' => 'available']);
        });

        return redirect()->route('admin.peralatan-booking.index')
            ->with('success', 'Booking dibatalkan. Alat kembali berstatus Available.');
    }
}