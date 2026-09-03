<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PengajuanMutasi;
use App\Models\MutasiAlat;
use App\Models\DokumenMutasi;
use App\Models\PengajuanBooking;
use App\Models\Alat;
use App\Models\Lokasi;
use App\Models\Notifikasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PengajuanMutasiController extends Controller
{
    public function index(Request $request)
    {
        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $mutasi = PengajuanMutasi::with(['detailAlat.alat', 'bandaraPemberi', 'bandaraPenerima', 'pemohon'])
            ->when($role === 'afet_bandara' || $role === 'div_head', function ($q) use ($idBandara) {
                $q->where(function ($q2) use ($idBandara) {
                    $q2->where('id_bandara_pemberi', $idBandara)
                       ->orWhere('id_bandara_penerima', $idBandara);
                });
            })
            ->when($role === 'gm_kc', function ($q) use ($idBandara) {
                $q->where('id_bandara_pemberi', $idBandara);
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.peralatan-mutasi.index', compact('mutasi'));
    }

    /**
     * Form Input Mapping Kebutuhan — data auto-fill dari 1 atau beberapa booking
     * sekaligus (?bookings[]=1&bookings[]=2&...), selama semuanya dari 1 bandara
     * pemberi yang sama (dicek juga di JS halaman booking, ini safety net server-side).
     */
    public function create(Request $request)
    {
        $idBookings = array_map('intval', (array) $request->input('bookings', []));

        if (empty($idBookings)) {
            return redirect()->route('admin.peralatan-booking.index')
                ->withErrors(['status' => 'Pilih minimal satu alat yang mau diajukan mutasi.']);
        }

        $bookings = PengajuanBooking::with(['pengajuanIdle.alat.bandara', 'pengajuanIdle.alat.lokasi'])
            ->where('id_pengguna_pemesan', session('pengguna.id'))
            ->where('status', 'Aktif')
            ->whereIn('id_booking', $idBookings)
            ->get();

        if ($bookings->count() !== count($idBookings)) {
            return redirect()->route('admin.peralatan-booking.index')
                ->withErrors(['status' => 'Salah satu booking tidak valid, bukan milik Anda, atau sudah tidak aktif.']);
        }

        $sudahAdaMutasi = MutasiAlat::whereIn('id_booking', $idBookings)->exists();
        if ($sudahAdaMutasi) {
            return redirect()->route('admin.peralatan-booking.index')
                ->withErrors(['status' => 'Salah satu booking ini sudah memiliki pengajuan mutasi.']);
        }

        $idBandaraPemberi = $bookings->map(fn($b) => $b->pengajuanIdle->alat->id_bandara ?? null)->unique()->filter();
        if ($idBandaraPemberi->count() !== 1) {
            return redirect()->route('admin.peralatan-booking.index')
                ->withErrors(['status' => 'Semua alat yang diajukan mutasi bersamaan harus berasal dari 1 bandara pemberi yang sama.']);
        }

        return view('admin.peralatan-mutasi.create', compact('bookings'));
    }

    /**
     * Submit Mapping Kebutuhan + dokumen pendukung, untuk banyak alat sekaligus.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bookings'              => 'required|array|min:1',
            'bookings.*'            => 'integer|exists:pengajuan_booking,id_booking',
            'keterangan_kebutuhan'  => 'required|string',
            'dokumen'               => 'required|array|min:1',
            'dokumen.*'             => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        $idBookings = array_map('intval', $request->input('bookings'));

        $bookings = PengajuanBooking::with('pengajuanIdle.alat')
            ->where('id_pengguna_pemesan', session('pengguna.id'))
            ->where('status', 'Aktif')
            ->whereIn('id_booking', $idBookings)
            ->get();

        if ($bookings->count() !== count($idBookings)) {
            return back()->withErrors(['status' => 'Salah satu booking tidak valid, bukan milik Anda, atau sudah tidak aktif.'])->withInput();
        }

        $sudahAdaMutasi = MutasiAlat::whereIn('id_booking', $idBookings)->exists();
        if ($sudahAdaMutasi) {
            return back()->withErrors(['status' => 'Salah satu booking ini sudah memiliki pengajuan mutasi.'])->withInput();
        }

        $alatList = $bookings->map(fn($b) => $b->pengajuanIdle->alat);

        $idBandaraPemberi = $alatList->pluck('id_bandara')->unique()->filter();
        if ($idBandaraPemberi->count() !== 1) {
            return back()->withErrors(['status' => 'Semua alat yang diajukan mutasi bersamaan harus berasal dari 1 bandara pemberi yang sama.'])->withInput();
        }

        $mutasi = DB::transaction(function () use ($bookings, $alatList, $idBandaraPemberi, $request) {
            $mutasi = PengajuanMutasi::create([
                'id_bandara_pemberi'    => $idBandaraPemberi->first(),
                'id_bandara_penerima'   => session('pengguna.id_bandara'),
                'id_pengguna_pemohon'   => session('pengguna.id'),
                'keterangan_kebutuhan'  => $request->keterangan_kebutuhan,
                'status'                => 'Waiting Approval CEO',
            ]);

            foreach ($bookings as $booking) {
                MutasiAlat::create([
                    'id_pengajuan_mutasi' => $mutasi->id_pengajuan_mutasi,
                    'id_booking'          => $booking->id_booking,
                    'id_alat'             => $booking->pengajuanIdle->alat->id_alat,
                ]);

                $booking->update(['status' => 'Lanjut Mutasi']);
            }

            foreach ($request->file('dokumen') as $file) {
                $path = $file->store('pengajuan-mutasi', 'public');
                DokumenMutasi::create([
                    'id_pengajuan_mutasi' => $mutasi->id_pengajuan_mutasi,
                    'jenis_dokumen'       => 'mapping_kebutuhan',
                    'nama_file'           => $file->getClientOriginalName(),
                    'path_file'           => $path,
                    'tipe_file'           => $file->getClientOriginalExtension(),
                ]);
            }

            Notifikasi::mutasiDiajukan($mutasi, $alatList, session('pengguna.nama'));

            return $mutasi;
        });

        return redirect()->route('admin.peralatan-mutasi.index')
            ->with('success', 'Pengajuan mutasi berhasil dibuat dan menunggu approval CEO.');
    }

    public function show($id)
    {
        $mutasi = PengajuanMutasi::with([
                'detailAlat.alat.lokasi.bandara', 'detailAlat.booking', 'detailAlat.lokasiTujuan',
                'bandaraPemberi', 'bandaraPenerima', 'pemohon',
                'ceoApprover', 'gmApprover', 'pengajuUlang',
                'uploaderBaIdle', 'konfirmatorIdle',
                'penerimaBarang', 'pelaksanaSertifikasi',
                'dokumen',
            ])
            ->findOrFail($id);

        $this->pastikanAksesMutasi($mutasi);

        return view('admin.peralatan-mutasi.show', compact('mutasi'));
    }

    // ── Tahap: CEO approve/reject pertama ──

    public function approveCeo($id)
    {
        $mutasi = PengajuanMutasi::with('detailAlat.alat')->findOrFail($id);
        $this->pastikanRole('ceo');
        $this->pastikanStatus($mutasi, 'Waiting Approval CEO');

        $mutasi->update([
            'status'                    => 'Waiting Approval GM Pemberi',
            'id_pengguna_ceo_approval'  => session('pengguna.id'),
            'tanggal_ceo_approval'      => now(),
            'alasan_reject_ceo'         => null,
        ]);

        Notifikasi::mutasiDisetujuiCeo($mutasi, $mutasi->detailAlat->pluck('alat'), session('pengguna.nama'));

        return back()->with('success', 'Disetujui CEO. Menunggu approval GM Pemberi.');
    }

    public function rejectCeo(Request $request, $id)
    {
        $request->validate(['alasan_reject_ceo' => 'required|string']);

        $mutasi = PengajuanMutasi::with('detailAlat.alat')->findOrFail($id);
        $this->pastikanRole('ceo');
        $this->pastikanStatus($mutasi, 'Waiting Approval CEO');

        $mutasi->update([
            'alasan_reject_ceo' => $request->alasan_reject_ceo,
            // status tetap 'Waiting Approval CEO' — pemohon revisi & submit ulang
        ]);

        Notifikasi::mutasiDitolakCeoRevisi($mutasi, $mutasi->detailAlat->pluck('alat'), $request->alasan_reject_ceo);

        return back()->with('success', 'Pengajuan ditolak CEO. Pemohon perlu merevisi.');
    }

    // ── Tahap: GM Pemberi approve/reject ──

    /**
     * GM Pemberi approve — sekarang per-alat, bukan seluruh pengajuan sekaligus.
     * Status "Unused/idle" di sistem cuma menandakan alat sedang tidak dipakai;
     * belum tentu secara bisnis boleh dimutasikan. Keputusan itu ada di sini,
     * di tangan GM Pemberi yang tahu kondisi lapangan — bukan belakangan di
     * tahap Pemastian Fasilitas Idle (yang cuma verifikasi dokumen BA).
     *
     * Form mengirim `alat_lanjut[]` = daftar id_mutasi_alat yang GM Pemberi
     * pilih untuk lanjut. Sisanya (yang ada di pengajuan tapi tidak dicentang)
     * otomatis dianggap dikeluarkan.
     */
    public function approveGm(Request $request, $id)
{
    $request->validate([
        'alat_lanjut'   => 'nullable|array',
        'alat_lanjut.*' => 'integer',
        'catatan_dikeluarkan'   => 'nullable|array',
        'catatan_dikeluarkan.*' => 'nullable|string',
        // Dokumen pendukung bersifat opsional, tipe file bebas (tidak dibatasi mimes).
        'dokumen_pendukung'   => 'nullable|array',
        'dokumen_pendukung.*' => 'file|max:10240',
    ]);

    $mutasi = PengajuanMutasi::with('detailAlat.alat', 'detailAlat.booking.pengajuanIdle')->findOrFail($id);
    $this->pastikanRoleGmPemberi($mutasi);
    $this->pastikanStatus($mutasi, 'Waiting Approval GM Pemberi');

    $idAlatLanjut = array_map('intval', $request->input('alat_lanjut', []));
    $catatanDikeluarkan = $request->input('catatan_dikeluarkan', []);

    $jumlahLanjut = $mutasi->detailAlat->whereIn('id_mutasi_alat', $idAlatLanjut)->count();
    if ($jumlahLanjut === 0) {
        return back()->withErrors([
            'alat_lanjut' => 'Pilih minimal 1 alat yang lanjut dimutasikan. Kalau semua alat tidak bisa dimutasikan, gunakan tombol Reject.',
        ])->withInput();
    }

    DB::transaction(function () use ($mutasi, $idAlatLanjut, $catatanDikeluarkan, $request) {
        foreach ($mutasi->detailAlat->where('status_gm', 'lanjut') as $detail) {
            $lanjut = in_array($detail->id_mutasi_alat, $idAlatLanjut, true);

            if ($lanjut) {
                $detail->update(['status_gm' => 'lanjut', 'catatan_dikeluarkan' => null]);
                continue;
            }

            // Alat dikeluarkan: batalkan booking-nya & kembalikan alat jadi
            // Available lagi di bandara pemberi.
            $detail->update([
                'status_gm'            => 'dikeluarkan',
                'catatan_dikeluarkan'  => $catatanDikeluarkan[$detail->id_mutasi_alat] ?? null,
            ]);

            if ($detail->booking) {
                $detail->booking->update(['status' => 'Dibatalkan']);
                $detail->booking->pengajuanIdle?->update(['status_ketersediaan' => 'available']);
            }
        }

        $mutasi->update([
            'status'                  => 'Menunggu Pemastian Fasilitas Idle',
            'id_pengguna_gm_approval' => session('pengguna.id'),
            'tanggal_gm_approval'     => now(),
            'keputusan_gm'            => 'Approve',
            'catatan_gm'              => $request->catatan_gm,
        ]);

        // Dokumen pendukung opsional (tipe file bebas) dari GM Pemberi saat approve.
        foreach ($request->file('dokumen_pendukung', []) as $file) {
            $path = $file->store('pengajuan-mutasi', 'public');
            DokumenMutasi::create([
                'id_pengajuan_mutasi' => $mutasi->id_pengajuan_mutasi,
                'jenis_dokumen'       => 'dokumen_pendukung',
                'nama_file'           => $file->getClientOriginalName(),
                'path_file'           => $path,
                'tipe_file'           => $file->getClientOriginalExtension(),
            ]);
        }
    });

    $mutasi->refresh()->load('detailAlat.alat');

    Notifikasi::mutasiDisetujuiGm(
        $mutasi,
        $mutasi->detailAlat->where('status_gm', 'lanjut')->pluck('alat'),
        $mutasi->detailAlat->where('status_gm', 'dikeluarkan')->pluck('alat'),
        session('pengguna.nama')
    );

    $jumlahDikeluarkan = $mutasi->detailAlat->where('status_gm', 'dikeluarkan')->count();
    $pesan = $jumlahDikeluarkan > 0
        ? "Disetujui GM Pemberi. {$jumlahLanjut} alat lanjut, {$jumlahDikeluarkan} alat dikeluarkan (kembali Available). Menunggu Pemastian Fasilitas Idle."
        : 'Disetujui GM Pemberi. Menunggu Pemastian Fasilitas Idle.';

    return back()->with('success', $pesan);
}

    // ── Tahap: Pemastian Fasilitas Idle ──
    // Dokumen BA bisa diupload GM Pemberi ATAU Admin AFET Bandara Pemberi, sifatnya opsional
    // sebelum AFET Regional konfirmasi.

    public function uploadDokumenIdle(Request $request, $id)
    {
        $request->validate([
            'dokumen'   => 'required|array|min:1',
            'dokumen.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        $mutasi = PengajuanMutasi::with('detailAlat.alat')->findOrFail($id);
        $this->pastikanStatus($mutasi, 'Menunggu Pemastian Fasilitas Idle');

        $role = session('pengguna.role');
        $bolehAkses = ($role === 'gm_kc' && session('pengguna.id_bandara') == $mutasi->id_bandara_pemberi)
            || ($role === 'afet_bandara' && session('pengguna.id_bandara') == $mutasi->id_bandara_pemberi);

        if (! $bolehAkses) {
            abort(403, 'Hanya GM Pemberi atau Admin AFET Bandara Pemberi yang bisa upload dokumen ini.');
        }

        DB::transaction(function () use ($mutasi, $request) {
            foreach ($request->file('dokumen') as $file) {
                $path = $file->store('pengajuan-mutasi', 'public');
                DokumenMutasi::create([
                    'id_pengajuan_mutasi' => $mutasi->id_pengajuan_mutasi,
                    'jenis_dokumen'       => 'pemastian_idle',
                    'nama_file'           => $file->getClientOriginalName(),
                    'path_file'           => $path,
                    'tipe_file'           => $file->getClientOriginalExtension(),
                ]);
            }

            $mutasi->update([
                'id_pengguna_upload_ba_idle' => session('pengguna.id'),
                'tanggal_upload_ba_idle'     => now(),
            ]);
        });

        Notifikasi::mutasiDokumenIdleDiupload($mutasi, $mutasi->detailAlat->where('status_gm', 'lanjut')->pluck('alat'));

        return back()->with('success', 'Dokumen BA Pemastian Fasilitas Idle berhasil diupload.');
    }

    public function konfirmasiIdle($id)
    {
        $mutasi = PengajuanMutasi::with(['detailAlat.alat', 'bandaraPemberi', 'bandaraPenerima'])->findOrFail($id);
        $this->pastikanRole('afet_regional');
        $this->pastikanStatus($mutasi, 'Menunggu Pemastian Fasilitas Idle');
    
        $lokasiUnusedPenerima = Lokasi::where('id_bandara', $mutasi->id_bandara_penerima)
            ->where('nama_lokasi', 'Unused')
            ->first();
    
        if (! $lokasiUnusedPenerima) {
            return back()->withErrors([
                'status' => 'Lokasi "Unused" belum tersedia untuk bandara penerima. Hubungi admin untuk menambahkannya sebelum konfirmasi idle bisa dilanjutkan.',
            ]);
        }
    
        DB::transaction(function () use ($mutasi, $lokasiUnusedPenerima) {
            $mutasi->update([
                'status'                      => 'Menunggu Sertifikasi',
                'id_pengguna_konfirmasi_idle' => session('pengguna.id'),
                'tanggal_konfirmasi_idle'     => now(),
            ]);
    
            // ⬇️⬇️⬇️ GANTI BARIS INI ⬇️⬇️⬇️
            foreach ($mutasi->detailAlat->where('status_gm', 'lanjut') as $detail) {
            // ⬆️⬆️⬆️ (baris aslinya: foreach ($mutasi->detailAlat as $detail) { ) ⬆️⬆️⬆️
                $alat = $detail->alat;
                if (! $alat) {
                    continue;
                }
    
                $alat->id_bandara = $mutasi->id_bandara_penerima;
                $alat->id_lokasi  = $lokasiUnusedPenerima->id_lokasi;
    
                if ($mutasi->bandaraPemberi && $mutasi->bandaraPenerima) {
                    $alat->updateKodeBandara(
                        $mutasi->bandaraPemberi->kode_bandara,
                        $mutasi->bandaraPenerima->kode_bandara
                    );
                }
    
                $alat->save();
            }
        });
    
        // ⬇️⬇️⬇️ GANTI BARIS INI JUGA ⬇️⬇️⬇️
        Notifikasi::mutasiIdleDikonfirmasi($mutasi, $mutasi->detailAlat->where('status_gm', 'lanjut')->pluck('alat'));
        // (baris aslinya: Notifikasi::mutasiIdleDikonfirmasi($mutasi, $mutasi->detailAlat->pluck('alat')); )
    
        return back()->with('success', 'Fasilitas idle dikonfirmasi. Kode alat & lokasi semua alat sudah diperbarui otomatis. Menunggu sertifikasi.');
    }

    public function rejectIdle(Request $request, $id)
    {
        $request->validate(['alasan_reject_idle' => 'required|string']);

        $mutasi = PengajuanMutasi::with('detailAlat.alat')->findOrFail($id);
        $this->pastikanRole('afet_regional');
        $this->pastikanStatus($mutasi, 'Menunggu Pemastian Fasilitas Idle');

        $mutasi->update([
            'status'                     => 'Waiting Approval GM Pemberi',
            'alasan_reject_idle'         => $request->alasan_reject_idle,
            // reset supaya GM Pemberi & Admin AFET Bandara Pemberi mengulang dari awal
            'id_pengguna_upload_ba_idle' => null,
            'tanggal_upload_ba_idle'     => null,
            'id_pengguna_gm_approval'    => null,
            'tanggal_gm_approval'        => null,
            'keputusan_gm'               => null,
            'catatan_gm'                 => null,
        ]);

        Notifikasi::mutasiIdleDitolak($mutasi, $mutasi->detailAlat->where('status_gm', 'lanjut')->pluck('alat'), $request->alasan_reject_idle);

        return back()->with('success', 'Fasilitas idle ditolak AFET Regional. Pengajuan dikembalikan ke tahap approval GM Pemberi.');
    }

// ── Tahap: Menunggu Sertifikasi ──
//
// AFET Bandara Penerima menyelesaikan seluruh proses:
// 1. Upload BA Penerimaan Barang
// 2. Upload Dokumen Pendukung Sertifikasi
// 3. Menentukan lokasi tujuan akhir per alat
// 4. Menyelesaikan sertifikasi
//
// Hanya alat dengan status_gm = lanjut yang boleh ikut proses.

    // ── Tahap: Menunggu Sertifikasi ──
//
// AFET Bandara Penerima menyelesaikan:
// 1. Upload BA Penerimaan Barang
// 2. Menentukan lokasi tujuan alat
// 3. Menyelesaikan sertifikasi
//
// Dokumen Sertifikasi TIDAK wajib pada tahap ini.
// Dokumen Sertifikasi dapat diupload setelah status menjadi Selesai.
//
// Hanya alat dengan status_gm = "lanjut" yang boleh ikut proses.
// Alat dengan status_gm = "dikeluarkan" tidak boleh ikut sertifikasi.

public function sertifikasi(Request $request, $id)
{
    /*
     * ============================================================
     * VALIDASI INPUT
     * ============================================================
     */

    $request->validate([
        /*
         * Catatan BA bersifat opsional.
         */
        'catatan_terima_barang' => 'nullable|string',

        /*
         * BA Penerimaan Barang WAJIB diupload
         * ketika sertifikasi diselesaikan.
         */
        'dokumen_ba' => 'required|array|min:1',

        'dokumen_ba.*' =>
            'file|max:10240|mimes:pdf,jpg,jpeg,png',

        /*
         * Dokumen Sertifikasi TIDAK divalidasi di sini.
         *
         * Dokumen ini akan diupload melalui method
         * uploadDokumenSertifikasi() setelah status Selesai.
         */

        /*
         * Lokasi tujuan alat bersifat opsional.
         */
        'lokasi_tujuan' => 'nullable|array',

        'lokasi_tujuan.*' =>
            'nullable|exists:lokasi,id_lokasi',

        /*
         * Dokumen pendukung bersifat opsional,
         * tipe file bebas (tidak dibatasi mimes).
         */
        'dokumen_pendukung' => 'nullable|array',

        'dokumen_pendukung.*' => 'file|max:10240',
    ]);


    /*
     * ============================================================
     * AMBIL DATA MUTASI
     * ============================================================
     */

    $mutasi = PengajuanMutasi::with([
        'detailAlat.alat',
        'bandaraPemberi',
        'bandaraPenerima',
    ])->findOrFail($id);


    /*
     * ============================================================
     * CEK ROLE
     * ============================================================
     *
     * Hanya AFET Bandara Penerima yang boleh
     * menyelesaikan sertifikasi.
     */

    $this->pastikanRoleAfetBandara(
        $mutasi,
        'penerima'
    );


    /*
     * ============================================================
     * CEK STATUS
     * ============================================================
     *
     * Sertifikasi hanya dapat diselesaikan ketika
     * status masih Menunggu Sertifikasi.
     */

    $this->pastikanStatus(
        $mutasi,
        'Menunggu Sertifikasi'
    );


    /*
     * ============================================================
     * AMBIL ALAT YANG DILANJUTKAN GM
     * ============================================================
     *
     * HANYA:
     *
     * status_gm = lanjut
     *
     * yang boleh diproses.
     *
     * Alat:
     *
     * status_gm = dikeluarkan
     *
     * tidak boleh diproses.
     */

    $alatLanjut = $mutasi->detailAlat
        ->where('status_gm', 'lanjut');


    /*
     * Pastikan masih ada alat yang dapat disertifikasi.
     */

    if ($alatLanjut->isEmpty()) {
        return back()->withErrors([
            'status' =>
                'Tidak ada alat yang dapat dilanjutkan ke proses sertifikasi.',
        ])->withInput();
    }


    /*
     * ============================================================
     * DATA LOKASI
     * ============================================================
     */

    $lokasiTujuan = $request->input(
        'lokasi_tujuan',
        []
    );


    /*
     * ============================================================
     * VALIDASI LOKASI
     * ============================================================
     *
     * Jika lokasi dipilih:
     *
     * 1. Alat harus status_gm = lanjut.
     * 2. Alat harus berasal dari pengajuan ini.
     * 3. Lokasi harus berada di bandara penerima.
     */

    foreach ($lokasiTujuan as $idMutasiAlat => $idLokasi) {

        /*
         * Lokasi kosong diperbolehkan.
         */

        if ($idLokasi === null || $idLokasi === '') {
            continue;
        }


        /*
         * Pastikan alat tersebut termasuk
         * alat yang dilanjutkan GM.
         */

        $detailValid = $alatLanjut->firstWhere(
            'id_mutasi_alat',
            (int) $idMutasiAlat
        );


        if (! $detailValid) {

            return back()->withErrors([
                'lokasi_tujuan' =>
                    'Alat yang dipilih tidak valid atau sudah dikeluarkan oleh GM Pemberi.',
            ])->withInput();

        }


        /*
         * Pastikan lokasi berada di bandara penerima.
         */

        $lokasiValid = Lokasi::where(
            'id_lokasi',
            $idLokasi
        )
            ->where(
                'id_bandara',
                $mutasi->id_bandara_penerima
            )
            ->exists();


        if (! $lokasiValid) {

            return back()->withErrors([
                'lokasi_tujuan' =>
                    'Lokasi tujuan harus berada di bandara penerima.',
            ])->withInput();

        }
    }


    /*
     * ============================================================
     * SIMPAN PROSES SERTIFIKASI
     * ============================================================
     */

    DB::transaction(function () use (
        $mutasi,
        $request,
        $lokasiTujuan
    ) {

        /*
         * ========================================================
         * 1. SIMPAN BA PENERIMAAN BARANG
         * ========================================================
         */

        foreach (
            $request->file('dokumen_ba', [])
            as $file
        ) {

            $path = $file->store(
                'pengajuan-mutasi',
                'public'
            );


            DokumenMutasi::create([
                'id_pengajuan_mutasi' =>
                    $mutasi->id_pengajuan_mutasi,

                'jenis_dokumen' =>
                    'terima_barang',

                'nama_file' =>
                    $file->getClientOriginalName(),

                'path_file' =>
                    $path,

                'tipe_file' =>
                    $file->getClientOriginalExtension(),
            ]);
        }


        /*
         * Catat penerimaan barang.
         */

        $mutasi->update([
            'id_pengguna_terima_barang' =>
                session('pengguna.id'),

            'tanggal_terima_barang' =>
                now(),

            'catatan_terima_barang' =>
                $request->catatan_terima_barang,
        ]);


        /*
         * ========================================================
         * 2. UPDATE LOKASI ALAT
         * ========================================================
         *
         * HANYA alat status_gm = lanjut.
         */

        foreach (
            $mutasi->detailAlat
                ->where('status_gm', 'lanjut')
            as $detail
        ) {

            $idLokasi =
                $lokasiTujuan[
                    $detail->id_mutasi_alat
                ] ?? null;


            /*
             * Jika tidak memilih lokasi,
             * alat tetap berada pada lokasi Unused
             * yang sudah ditentukan sebelumnya.
             */

            if (! $idLokasi) {
                continue;
            }


            /*
             * Simpan lokasi tujuan pada detail mutasi.
             */

            $detail->update([
                'id_lokasi_tujuan' =>
                    $idLokasi,
            ]);


            /*
             * Update lokasi alat.
             */

            if ($detail->alat) {

                $detail->alat->update([
                    'id_lokasi' =>
                        $idLokasi,
                ]);

            }
        }


        /*
         * ========================================================
         * 3. SELESAIKAN SERTIFIKASI
         * ========================================================
         *
         * Dokumen Sertifikasi TIDAK diperlukan di sini.
         */

        $mutasi->update([
            'status' =>
                'Selesai',

            'id_pengguna_sertifikasi' =>
                session('pengguna.id'),

            'tanggal_sertifikasi' =>
                now(),
        ]);


        /*
         * ========================================================
         * 4. SIMPAN DOKUMEN PENDUKUNG (OPSIONAL, TIPE FILE BEBAS)
         * ========================================================
         *
         * Diupload oleh AFET Bandara Penerima bersamaan
         * dengan proses sertifikasi ini.
         */

        foreach (
            $request->file('dokumen_pendukung', [])
            as $file
        ) {

            $path = $file->store(
                'pengajuan-mutasi',
                'public'
            );


            DokumenMutasi::create([
                'id_pengajuan_mutasi' =>
                    $mutasi->id_pengajuan_mutasi,

                'jenis_dokumen' =>
                    'dokumen_pendukung',

                'nama_file' =>
                    $file->getClientOriginalName(),

                'path_file' =>
                    $path,

                'tipe_file' =>
                    $file->getClientOriginalExtension(),
            ]);
        }
    });


    /*
     * ============================================================
     * NOTIFIKASI
     * ============================================================
     *
     * Hanya alat status_gm = lanjut.
     */

    Notifikasi::mutasiSelesai(
        $mutasi,
        $mutasi->detailAlat
            ->where('status_gm', 'lanjut')
            ->pluck('alat')
    );


    /*
     * ============================================================
     * RESPONSE
     * ============================================================
     */

    return back()->with(
        'success',
        'BA Penerimaan Barang dan sertifikasi berhasil diproses. Proses mutasi selesai.'
    );
}

    // ── Upload Dokumen Sertifikasi Setelah Selesai ──
//
// Dokumen Sertifikasi dapat diupload setelah
// pengajuan sudah berstatus Selesai.
//
// Upload dokumen ini TIDAK mengubah status mutasi.

public function uploadDokumenSertifikasi(
    Request $request,
    $id
) {
    /*
     * ============================================================
     * VALIDASI
     * ============================================================
     */

    $request->validate([
        'dokumen_sertifikasi' =>
            'required|array|min:1',

        'dokumen_sertifikasi.*' =>
            'file|max:10240|mimes:pdf,jpg,jpeg,png',
    ]);


    /*
     * ============================================================
     * AMBIL MUTASI
     * ============================================================
     */

    $mutasi = PengajuanMutasi::findOrFail($id);


    /*
     * ============================================================
     * CEK ROLE
     * ============================================================
     *
     * Hanya AFET Bandara Penerima.
     */

    $this->pastikanRoleAfetBandara(
        $mutasi,
        'penerima'
    );


    /*
     * ============================================================
     * CEK STATUS
     * ============================================================
     *
     * Dokumen ini hanya dapat ditambahkan
     * setelah proses mutasi selesai.
     */

    $this->pastikanStatus(
        $mutasi,
        'Selesai'
    );


    /*
     * ============================================================
     * SIMPAN DOKUMEN
     * ============================================================
     */

    foreach (
        $request->file('dokumen_sertifikasi', [])
        as $file
    ) {

        $path = $file->store(
            'pengajuan-mutasi',
            'public'
        );


        DokumenMutasi::create([
            'id_pengajuan_mutasi' =>
                $mutasi->id_pengajuan_mutasi,

            'jenis_dokumen' =>
                'sertifikasi',

            'nama_file' =>
                $file->getClientOriginalName(),

            'path_file' =>
                $path,

            'tipe_file' =>
                $file->getClientOriginalExtension(),
        ]);
    }


    /*
     * ============================================================
     * RESPONSE
     * ============================================================
     */

    return back()->with(
        'success',
        'Dokumen Sertifikasi berhasil diupload.'
    );
}

    // ── Dokumen ──

    public function deleteDokumen($idMutasi, $idDokumen)
    {
        $dokumen = DokumenMutasi::where('id_pengajuan_mutasi', $idMutasi)->where('id_dokumen', $idDokumen)->firstOrFail();

        $mutasi = PengajuanMutasi::findOrFail($idMutasi);
        $this->pastikanAksesMutasi($mutasi);

        Storage::disk('public')->delete($dokumen->path_file);
        $dokumen->delete();
        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public function downloadDokumen($idDokumen)
    {
        $dokumen = DokumenMutasi::findOrFail($idDokumen);

        // Ambil data pengajuan mutasi
        $mutasi = PengajuanMutasi::findOrFail(
            $dokumen->id_pengajuan_mutasi
        );

        // Pastikan user memang memiliki akses
        // terhadap pengajuan mutasi tersebut
        $this->pastikanAksesMutasi($mutasi);

        // Pastikan file benar-benar tersedia
        $disk = Storage::disk('public');

        if (! $disk->exists($dokumen->path_file)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        // Ambil lokasi fisik file
        $path = $disk->path($dokumen->path_file);

        // Tentukan MIME type file
        $mimeType = $disk->mimeType($dokumen->path_file);

        // Tampilkan file langsung di browser
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $dokumen->nama_file . '"',
        ]);
    }

    // ── Helper otorisasi ──

    private function pastikanRole(string $slug): void
    {
        if (session('pengguna.role') !== $slug) {
            abort(403, 'Anda tidak berwenang melakukan aksi ini.');
        }
    }

    private function pastikanRoleGmPemberi(PengajuanMutasi $mutasi): void
    {
        if (session('pengguna.role') !== 'gm_kc' || session('pengguna.id_bandara') != $mutasi->id_bandara_pemberi) {
            abort(403, 'Hanya GM Bandara Pemberi yang bisa memproses tahap ini.');
        }
    }

    private function pastikanRoleGmKc(PengajuanMutasi $mutasi, string $sisi): void
    {
        $idBandaraTarget = $sisi === 'pemberi' ? $mutasi->id_bandara_pemberi : $mutasi->id_bandara_penerima;

        if (session('pengguna.role') !== 'gm_kc' || session('pengguna.id_bandara') != $idBandaraTarget) {
            abort(403, 'Hanya KC Bandara ' . ($sisi === 'pemberi' ? 'Pemberi' : 'Penerima') . ' yang bisa memproses tahap ini.');
        }
    }

    private function pastikanRoleAfetBandara(PengajuanMutasi $mutasi, string $sisi): void
    {
        $idBandaraTarget = $sisi === 'pemberi' ? $mutasi->id_bandara_pemberi : $mutasi->id_bandara_penerima;

        if (session('pengguna.role') !== 'afet_bandara' || session('pengguna.id_bandara') != $idBandaraTarget) {
            abort(403, 'Anda tidak berwenang memproses tahap ini.');
        }
    }

    private function pastikanStatus(PengajuanMutasi $mutasi, string $statusYangDiharapkan): void
    {
        if ($mutasi->status !== $statusYangDiharapkan) {
            abort(403, 'Pengajuan ini sudah tidak berada di tahap ini.');
        }
    }

    /**
     * Guard untuk show()/deleteDokumen()/downloadDokumen(): role terkunci
     * (afet_bandara, div_head, gm_kc) hanya boleh akses mutasi yang
     * bandaranya terlibat sebagai pemberi ATAU penerima. Role bebas
     * (afet_regional, ho, ceo) selalu lolos.
     */
    private function pastikanAksesMutasi(PengajuanMutasi $mutasi): void
    {
        $role = session('pengguna.role');

        if (! $this->isBandaraLocked($role)) {
            return;
        }

        $idBandara = session('pengguna.id_bandara');

        if ($idBandara != $mutasi->id_bandara_pemberi && $idBandara != $mutasi->id_bandara_penerima) {
            abort(403, 'Anda tidak berwenang mengakses data mutasi ini.');
        }
    }
}