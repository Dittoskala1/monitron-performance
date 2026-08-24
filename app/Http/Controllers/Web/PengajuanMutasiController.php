<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PengajuanMutasi;
use App\Models\VerifikasiMobilisasiMutasi;
use App\Models\DokumenMutasi;
use App\Models\PengajuanBooking;
use App\Models\Alat;
use App\Models\Notifikasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PengajuanMutasiController extends Controller
{
    public function index(Request $request)
    {
        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $mutasi = PengajuanMutasi::with(['alat', 'bandaraPemberi', 'bandaraPenerima', 'pemohon'])
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
     * Form Input Mapping Kebutuhan — data auto-fill dari booking
     */
    public function create($idBooking)
    {
        $booking = PengajuanBooking::with(['pengajuanIdle.alat.bandara', 'pengajuanIdle.alat.lokasi'])
            ->where('id_pengguna_pemesan', session('pengguna.id'))
            ->where('status', 'Aktif')
            ->findOrFail($idBooking);

        $sudahAdaMutasi = PengajuanMutasi::where('id_booking', $booking->id_booking)->exists();
        if ($sudahAdaMutasi) {
            return redirect()->route('admin.peralatan-booking.index')
                ->withErrors(['status' => 'Booking ini sudah memiliki pengajuan mutasi.']);
        }

        return view('admin.peralatan-mutasi.create', compact('booking'));
    }

    /**
     * Submit Mapping Kebutuhan + dokumen pendukung
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_booking'            => 'required|exists:pengajuan_booking,id_booking',
            'keterangan_kebutuhan'  => 'required|string',
            'dokumen'               => 'required|array|min:1',
            'dokumen.*'             => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        $booking = PengajuanBooking::with('pengajuanIdle.alat')
            ->where('id_pengguna_pemesan', session('pengguna.id'))
            ->where('status', 'Aktif')
            ->findOrFail($request->id_booking);

        $alat = $booking->pengajuanIdle->alat;

        DB::transaction(function () use ($booking, $alat, $request) {
            $mutasi = PengajuanMutasi::create([
                'id_booking'            => $booking->id_booking,
                'id_alat'               => $alat->id_alat,
                'id_bandara_pemberi'    => $alat->id_bandara,
                'id_bandara_penerima'   => session('pengguna.id_bandara'),
                'id_pengguna_pemohon'   => session('pengguna.id'),
                'keterangan_kebutuhan'  => $request->keterangan_kebutuhan,
                'status'                => 'Waiting Approval CEO',
            ]);        

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
        
            $booking->update(['status' => 'Lanjut Mutasi']);

            Notifikasi::mutasiDiajukan($mutasi, $alat, session('pengguna.nama'));
        });

        return redirect()->route('admin.peralatan-mutasi.index')
            ->with('success', 'Pengajuan mutasi berhasil dibuat dan menunggu approval CEO.');
    }

    public function show($id)
    {
        $mutasi = PengajuanMutasi::with([
                'alat.lokasi.bandara', 'bandaraPemberi', 'bandaraPenerima', 'pemohon',
                'ceoApprover', 'gmApprover', 'ceoTeruskan',
                'uploaderBaIdle', 'konfirmatorIdle',
                'pelaksanaMobilisasi', 'pelaksanaSertifikasi',
                'verifikasiMobilisasi', 'dokumen',
            ])
            ->findOrFail($id);

        $this->pastikanAksesMutasi($mutasi);

        return view('admin.peralatan-mutasi.show', compact('mutasi'));
    }

    // ── Tahap: CEO approve/reject pertama ──

    public function approveCeo($id)
    {
        $mutasi = PengajuanMutasi::findOrFail($id);
        $this->pastikanRole('ceo');
        $this->pastikanStatus($mutasi, 'Waiting Approval CEO');

        $mutasi->update([
            'status'                    => 'Waiting Approval GM Pemberi',
            'id_pengguna_ceo_approval'  => session('pengguna.id'),
            'tanggal_ceo_approval'      => now(),
            'alasan_reject_ceo'         => null,
        ]);

        // TODO: Notifikasi ke GM Pemberi (approve)

        Notifikasi::mutasiDisetujuiCeo($mutasi, $mutasi->alat, session('pengguna.nama'));

        return back()->with('success', 'Disetujui CEO. Menunggu approval GM Pemberi.');
    }

    public function rejectCeo(Request $request, $id)
    {
        $request->validate(['alasan_reject_ceo' => 'required|string']);

        $mutasi = PengajuanMutasi::findOrFail($id);
        $this->pastikanRole('ceo');
        $this->pastikanStatus($mutasi, 'Waiting Approval CEO');

        $mutasi->update([
            'alasan_reject_ceo' => $request->alasan_reject_ceo,
            // status tetap 'Waiting Approval CEO' — pemohon revisi & submit ulang
        ]);

        // TODO: Notifikasi ke pemohon (revisi diperlukan)

        Notifikasi::mutasiDitolakCeoRevisi($mutasi, $mutasi->alat, $request->alasan_reject_ceo);

        return back()->with('success', 'Pengajuan ditolak CEO. Pemohon perlu merevisi.');
    }

    // ── Tahap: GM Pemberi approve/reject ──

    public function approveGm(Request $request, $id)
    {
        $mutasi = PengajuanMutasi::findOrFail($id);
        $this->pastikanRoleGmPemberi($mutasi);
        $this->pastikanStatus($mutasi, 'Waiting Approval GM Pemberi');

        $mutasi->update([
            'status'                  => 'Waiting Konfirmasi CEO',
            'id_pengguna_gm_approval' => session('pengguna.id'),
            'tanggal_gm_approval'     => now(),
            'keputusan_gm'            => 'Approve',
            'catatan_gm'              => $request->catatan_gm,
        ]);

        // TODO: Notifikasi ke CEO (perlu teruskan keputusan)

        Notifikasi::mutasiKeputusanGm($mutasi, $mutasi->alat, 'Approve', session('pengguna.nama'));

        return back()->with('success', 'Disetujui GM Pemberi. Menunggu CEO meneruskan keputusan.');
    }

    public function rejectGm(Request $request, $id)
    {
        $request->validate(['catatan_gm' => 'required|string']);

        $mutasi = PengajuanMutasi::findOrFail($id);
        $this->pastikanRoleGmPemberi($mutasi);
        $this->pastikanStatus($mutasi, 'Waiting Approval GM Pemberi');

        $mutasi->update([
            'status'                  => 'Waiting Konfirmasi CEO',
            'id_pengguna_gm_approval' => session('pengguna.id'),
            'tanggal_gm_approval'     => now(),
            'keputusan_gm'            => 'Reject',
            'catatan_gm'              => $request->catatan_gm,
        ]);

        // TODO: Notifikasi ke CEO (perlu teruskan penolakan)

        Notifikasi::mutasiKeputusanGm($mutasi, $mutasi->alat, 'Reject', session('pengguna.nama'));

        return back()->with('success', 'Ditolak GM Pemberi. Menunggu CEO meneruskan keputusan.');
    }

    // ── Tahap: CEO teruskan keputusan GM ──

    public function teruskanCeo($id)
    {
        $mutasi = PengajuanMutasi::findOrFail($id);
        $this->pastikanRole('ceo');
        $this->pastikanStatus($mutasi, 'Waiting Konfirmasi CEO');

        if ($mutasi->keputusan_gm === 'Reject') {
            $mutasi->update([
                'status'                     => 'Waiting Approval CEO',
                'id_pengguna_ceo_teruskan'   => session('pengguna.id'),
                'tanggal_ceo_teruskan'       => now(),
                // reset supaya bersih untuk submit ulang
                'id_pengguna_gm_approval'    => null,
                'tanggal_gm_approval'        => null,
                'keputusan_gm'               => null,
                'catatan_gm'                 => null,
            ]);

            // TODO: Notifikasi ke pemohon (GM menolak, revisi diperlukan)

            Notifikasi::mutasiGmDitolakDiteruskan($mutasi, $mutasi->alat);

            return back()->with('success', 'Penolakan GM Pemberi diteruskan ke pemohon.');
        }

        $mutasi->update([
            'status'                    => 'Menunggu Pemastian Fasilitas Idle',
            'id_pengguna_ceo_teruskan'  => session('pengguna.id'),
            'tanggal_ceo_teruskan'      => now(),
        ]);

        // TODO: Notifikasi ke Admin AFET Bandara Pemberi (wajib upload BA)

        Notifikasi::mutasiSiapUploadBaIdle($mutasi, $mutasi->alat);

        return back()->with('success', 'Approval GM Pemberi diteruskan. Menunggu upload BA Pemastian Fasilitas Idle.');
    }

    // ── Tahap: Pemastian Fasilitas Idle (sebelum mobilisasi) ──

    public function uploadBaIdle(Request $request, $id)
    {
        $request->validate([
            'dokumen'   => 'required|array|min:1',
            'dokumen.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        $mutasi = PengajuanMutasi::with('alat')->findOrFail($id);
        $this->pastikanRoleAfetBandara($mutasi, 'pemberi');
        $this->pastikanStatus($mutasi, 'Menunggu Pemastian Fasilitas Idle');

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

        // TODO: Notifikasi ke AFET Regional (review & konfirmasi)

        Notifikasi::mutasiBaIdlePerluDikonfirmasi($mutasi, $mutasi->alat);

        return back()->with('success', 'Dokumen BA berhasil diupload. Menunggu konfirmasi AFET Regional.');
    }

    public function konfirmasiIdle($id)
    {
        $mutasi = PengajuanMutasi::findOrFail($id);
        $this->pastikanRole('afet_regional');
        $this->pastikanStatus($mutasi, 'Menunggu Pemastian Fasilitas Idle');

        if (! $mutasi->id_pengguna_upload_ba_idle) {
            return back()->withErrors(['status' => 'Dokumen BA belum diupload oleh Admin AFET Bandara Pemberi.']);
        }

        $mutasi->update([
            'status'                        => 'Siap Mobilisasi',
            'id_pengguna_konfirmasi_idle'   => session('pengguna.id'),
            'tanggal_konfirmasi_idle'       => now(),
        ]);

        // TODO: Notifikasi konfirmasi fasilitas idle terkirim

        Notifikasi::mutasiSiapMobilisasi($mutasi, $mutasi->alat);

        return back()->with('success', 'Fasilitas idle dikonfirmasi. Alat siap dimobilisasi.');
    }

    // ── Tahap: Mobilisasi ──

    public function mobilisasi(Request $request, $id)
    {
        $request->validate([
            'catatan_mobilisasi' => 'nullable|string',
            'dokumen'            => 'required|array|min:1',
            'dokumen.*'          => 'file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        $mutasi = PengajuanMutasi::with('alat')->findOrFail($id);
        $this->pastikanRoleAfetBandara($mutasi, 'penerima');
        $this->pastikanStatus($mutasi, 'Siap Mobilisasi');

        DB::transaction(function () use ($mutasi, $request) {
            foreach ($request->file('dokumen') as $file) {
                $path = $file->store('pengajuan-mutasi', 'public');
                DokumenMutasi::create([
                    'id_pengajuan_mutasi' => $mutasi->id_pengajuan_mutasi,
                    'jenis_dokumen'       => 'mobilisasi',
                    'nama_file'           => $file->getClientOriginalName(),
                    'path_file'           => $path,
                    'tipe_file'           => $file->getClientOriginalExtension(),
                ]);
            }

            $mutasi->update([
                'status'                => 'Menunggu Verifikasi Mobilisasi',
                'id_pengguna_mobilisasi'=> session('pengguna.id'),
                'tanggal_mobilisasi'    => now(),
                'catatan_mobilisasi'    => $request->catatan_mobilisasi,
            ]);

            // Perpindahan fisik & data — id_bandara alat berubah di sini
            $mutasi->alat->update(['id_bandara' => $mutasi->id_bandara_penerima]);

            // Buat/reset baris verifikasi 3 tanda tangan
            VerifikasiMobilisasiMutasi::updateOrCreate(
                ['id_pengajuan_mutasi' => $mutasi->id_pengajuan_mutasi],
                [
                    'status_regional' => 'Pending', 'catatan_regional' => null, 'tanggal_regional' => null, 'id_pengguna_regional' => null,
                    'status_penerima' => 'Pending', 'catatan_penerima' => null, 'tanggal_penerima' => null, 'id_pengguna_penerima' => null,
                    'status_pemberi'  => 'Pending', 'catatan_pemberi'  => null, 'tanggal_pemberi'  => null, 'id_pengguna_pemberi'  => null,
                ]
            );
        });

        // TODO: Notifikasi massal — KC Penerima, KC Pemberi, GM Pemberi, GM Penerima, CEO

        Notifikasi::mutasiMobilisasiSelesai($mutasi, $mutasi->alat);

        return back()->with('success', 'Mobilisasi selesai. Menunggu verifikasi dari Regional, Penerima, dan Pemberi.');
    }

    // ── Tahap: Verifikasi Mobilisasi (3 tanda tangan digital) ──

    public function verifikasiRegional(Request $request, $id)
    {
        return $this->prosesVerifikasi($request, $id, 'afet_regional', 'regional');
    }

    public function verifikasiPenerima(Request $request, $id)
    {
        return $this->prosesVerifikasiAfetBandara($request, $id, 'penerima');
    }

    public function verifikasiPemberi(Request $request, $id)
    {
        return $this->prosesVerifikasiAfetBandara($request, $id, 'pemberi');
    }

    private function prosesVerifikasi(Request $request, $id, string $roleSlug, string $kolomPrefix)
    {
        $request->validate([
            'keputusan' => 'required|in:Konfirmasi,Tidak Sesuai',
            'catatan'   => 'required_if:keputusan,Tidak Sesuai|nullable|string',
        ]);

        $mutasi = PengajuanMutasi::with('verifikasiMobilisasi')->findOrFail($id);
        $this->pastikanRole($roleSlug);
        $this->pastikanStatus($mutasi, 'Menunggu Verifikasi Mobilisasi');

        $verifikasi = $mutasi->verifikasiMobilisasi;
        if (! $verifikasi) {
            return back()->withErrors(['status' => 'Data verifikasi mobilisasi tidak ditemukan.']);
        }

        $verifikasi->update([
            "status_{$kolomPrefix}"      => $request->keputusan,
            "catatan_{$kolomPrefix}"     => $request->catatan,
            "tanggal_{$kolomPrefix}"     => now(),
            "id_pengguna_{$kolomPrefix}" => session('pengguna.id'),
        ]);

        return $this->evaluasiVerifikasi($mutasi, $verifikasi);
    }

    private function prosesVerifikasiAfetBandara(Request $request, $id, string $sisi)
    {
        $request->validate([
            'keputusan' => 'required|in:Konfirmasi,Tidak Sesuai',
            'catatan'   => 'required_if:keputusan,Tidak Sesuai|nullable|string',
        ]);

        $mutasi = PengajuanMutasi::with('verifikasiMobilisasi')->findOrFail($id);
        $this->pastikanRoleAfetBandara($mutasi, $sisi);
        $this->pastikanStatus($mutasi, 'Menunggu Verifikasi Mobilisasi');

        $verifikasi = $mutasi->verifikasiMobilisasi;
        if (! $verifikasi) {
            return back()->withErrors(['status' => 'Data verifikasi mobilisasi tidak ditemukan.']);
        }

        $verifikasi->update([
            "status_{$sisi}"      => $request->keputusan,
            "catatan_{$sisi}"     => $request->catatan,
            "tanggal_{$sisi}"     => now(),
            "id_pengguna_{$sisi}" => session('pengguna.id'),
        ]);

        return $this->evaluasiVerifikasi($mutasi, $verifikasi);
    }

    /**
     * Setelah salah satu pihak submit verifikasi, cek apakah:
     * - Ada yang "Tidak Sesuai" → balik ke tahap Mobilisasi (upload ulang)
     * - Semua "Konfirmasi" → lanjut ke Menunggu Sertifikasi
     */
    private function evaluasiVerifikasi(PengajuanMutasi $mutasi, VerifikasiMobilisasiMutasi $verifikasi)
    {
        $verifikasi->refresh();

        if ($verifikasi->adaYangTidakSesuai()) {
            DB::transaction(function () use ($mutasi, $verifikasi) {
                $mutasi->update(['status' => 'Siap Mobilisasi']);

                $verifikasi->update([
                    'status_regional' => 'Pending', 'catatan_regional' => null, 'tanggal_regional' => null, 'id_pengguna_regional' => null,
                    'status_penerima' => 'Pending', 'catatan_penerima' => null, 'tanggal_penerima' => null, 'id_pengguna_penerima' => null,
                    'status_pemberi'  => 'Pending', 'catatan_pemberi'  => null, 'tanggal_pemberi'  => null, 'id_pengguna_pemberi'  => null,
                ]);
            });

            // TODO: Notifikasi ke Admin AFET Bandara Penerima — perlu upload ulang mobilisasi

            Notifikasi::mutasiVerifikasiTidakSesuai($mutasi, $mutasi->alat);

            return back()->with('success', 'Verifikasi menyatakan tidak sesuai. Mobilisasi perlu diulang.');
        }

        if ($verifikasi->semuaKonfirmasi()) {
            $mutasi->update(['status' => 'Menunggu Sertifikasi']);

            // TODO: Notifikasi ke Admin AFET Bandara Penerima — lanjut upload sertifikasi

            Notifikasi::mutasiSiapSertifikasi($mutasi, $mutasi->alat);

            return back()->with('success', 'Semua pihak sudah konfirmasi. Menunggu sertifikasi.');
        }

        return back()->with('success', 'Verifikasi tersimpan. Menunggu pihak lain melengkapi verifikasi.');
    }

    // ── Tahap: Sertifikasi ──

    public function sertifikasi(Request $request, $id)
{
    $request->validate([
        'id_lokasi_tujuan' => 'required|exists:lokasi,id_lokasi',
        'dokumen'          => 'required|array|min:1',
        'dokumen.*'        => 'file|max:10240|mimes:pdf,jpg,jpeg,png',
    ]);

    $mutasi = PengajuanMutasi::with('alat')->findOrFail($id);
    $this->pastikanRoleAfetBandara($mutasi, 'penerima');
    $this->pastikanStatus($mutasi, 'Menunggu Sertifikasi');

    // Pastikan lokasi tujuan memang milik bandara penerima
    $lokasiValid = \App\Models\Lokasi::where('id_lokasi', $request->id_lokasi_tujuan)
        ->where('id_bandara', $mutasi->id_bandara_penerima)
        ->exists();

    if (! $lokasiValid) {
        return back()->withErrors(['id_lokasi_tujuan' => 'Lokasi tujuan harus berada di bandara penerima.']);
    }

    DB::transaction(function () use ($mutasi, $request) {
        foreach ($request->file('dokumen') as $file) {
            $path = $file->store('pengajuan-mutasi', 'public');
            DokumenMutasi::create([
                'id_pengajuan_mutasi' => $mutasi->id_pengajuan_mutasi,
                'jenis_dokumen'       => 'sertifikasi',
                'nama_file'           => $file->getClientOriginalName(),
                'path_file'           => $path,
                'tipe_file'           => $file->getClientOriginalExtension(),
            ]);
        }

        $mutasi->update([
            'status'                  => 'Selesai',
            'id_pengguna_sertifikasi' => session('pengguna.id'),
            'tanggal_sertifikasi'     => now(),
        ]);

        // Update lokasi final alat sesuai hasil sertifikasi
        $mutasi->alat->update(['id_lokasi' => $request->id_lokasi_tujuan]);
    });

    // TODO: Notifikasi arsip ke CEO, HO, Admin AFET

    Notifikasi::mutasiSelesai($mutasi, $mutasi->alat);

    return back()->with('success', 'Sertifikasi selesai. Proses mutasi tuntas.');
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