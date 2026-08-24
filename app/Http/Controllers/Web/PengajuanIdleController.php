<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PengajuanIdle;
use App\Models\DokumenPengajuanIdle;
use App\Models\Alat;
use App\Models\Lokasi;
use App\Models\Notifikasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PengajuanIdleController extends Controller
{
    public function index(Request $request)
    {
        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');
        $idLokasi = session('pengguna.id_lokasi');
        // ⚠️ BARU: afet_bandara yang terikat ke 1 unit kerja (mis. SSES-T1 di
        // CGK) cuma boleh lihat pengajuan idle untuk alat cakupan unitnya.
        $unit = $this->unitKerjaSaya();

        $pengajuan = PengajuanIdle::with([
                'alat.lokasi.bandara', 'alat.bandara',
                'lokasiAsal', 'lokasiUnused', 'pemohon', 'approver', 'approverDepHead'
            ])
            ->when($role === 'afet_bandara', function ($q) use ($idBandara, $unit) {
                $q->whereHas('alat', fn($q2) => $q2->where('id_bandara', $idBandara));
                if ($unit) {
                    $this->scopeByUnitKerja($q, 'alat');
                }
            })
            // ⚠️ Div Head sekarang cuma "mengetahui" (view only, tidak approve),
            // tapi tetap dibatasi ke bandara + lokasinya sendiri seperti dulu.
            ->when($role === 'div_head', function ($q) use ($idBandara, $idLokasi) {
                $q->whereHas('alat', fn($q2) => $q2->where('id_bandara', $idBandara));
                if ($idLokasi !== null) {
                    $q->where('id_lokasi_asal', $idLokasi);
                }
            })
            // ⚠️ BARU: Dep Head — approver tahap 1 pengajuan idle, dibatasi ke
            // bandara + lokasinya (kalau ada) DAN ke unit kerjanya (kalau
            // akunnya terikat ke 1 unit spesifik, mis. Dep Head SSES-T1 CGK).
            ->when($role === 'dep_head', function ($q) use ($idBandara, $idLokasi, $unit) {
                $q->whereHas('alat', fn($q2) => $q2->where('id_bandara', $idBandara));
                if ($idLokasi !== null) {
                    $q->where('id_lokasi_asal', $idLokasi);
                }
                if ($unit) {
                    $this->scopeByUnitKerja($q, 'alat');
                }
            })
            // ⚠️ BARU: gm_kc sebelumnya kelewatan — dia juga role terkunci ke
            // bandaranya sendiri (samain sama DashboardController).
            ->when($role === 'gm_kc', function ($q) use ($idBandara) {
                $q->whereHas('alat', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('tanggal_pengajuan')
            ->paginate(15);

        return view('admin.peralatan-idle.index', compact('pengajuan'));
    }

    /**
     * ⚠️ DIUBAH: sekarang menerima query param opsional `id_alat`.
     * - Kalau `id_alat` dikirim (misalnya dari tombol "Ajukan Idle" di Data Alat),
     *   form langsung terkunci ke alat itu tanpa dropdown pilih ulang.
     * - Kalau tidak ada `id_alat`, tampilkan dropdown seperti biasa (fallback,
     *   misal kalau halaman ini diakses langsung dari menu).
     */
    public function create(Request $request)
    {
        $role = session('pengguna.role');
        $isLocked = $this->isBandaraLocked($role);
        $idBandara = session('pengguna.id_bandara');
        $unit = $this->unitKerjaSaya();

        $idAlatSedangDiajukan = PengajuanIdle::whereIn('status', ['Waiting Approval Dep Head', 'Waiting Approval Admin AFET'])
            ->pluck('id_alat')->toArray();

        $selectedAlat = null;

        if ($request->filled('id_alat')) {
            $selectedAlat = Alat::with('lokasi.bandara')->find($request->id_alat);

            if ($selectedAlat) {
                // Validasi akses: role terkunci (afet_bandara, div_head, gm_kc) cuma
                // boleh ajukan alat di bandaranya sendiri
                if ($isLocked && ($selectedAlat->id_bandara ?? null) != $idBandara) {
                    abort(403, 'Anda hanya bisa mengajukan idle untuk alat di bandara Anda sendiri.');
                }

                // ⚠️ BARU: kalau akun ini terikat ke 1 unit kerja, alat yang
                // diajukan idle juga harus masuk cakupan unit itu.
                if (! $this->alatMasukCakupanUnit($selectedAlat)) {
                    abort(403, 'Anda hanya bisa mengajukan idle untuk alat yang menjadi cakupan unit kerja Anda.');
                }

                // Kalau alat ini sudah di Unused atau sudah ada pengajuan pending,
                // batalkan pre-select-nya supaya jatuh ke dropdown biasa dengan pesan error.
                if (optional($selectedAlat->lokasi)->nama_lokasi === 'Unused') {
                    return redirect()->route('admin.alat.index')
                        ->with('error', 'Alat ini sudah berada di lokasi Unused.');
                }

                if (in_array($selectedAlat->id_alat, $idAlatSedangDiajukan)) {
                    return redirect()->route('admin.alat.index')
                        ->with('error', 'Alat ini sudah memiliki pengajuan idle yang sedang diproses.');
                }
            }
        }

        // Dropdown tetap disiapkan untuk fallback (kalau $selectedAlat null / id_alat tidak dikirim)
        $alat = Alat::with('lokasi.bandara')
            ->when($isLocked, function ($q) use ($idBandara) {
                $q->whereHas('lokasi', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when($unit, fn($q) => $this->scopeByUnitKerja($q))
            ->orderBy('nama_alat')
            ->get();

        return view('admin.peralatan-idle.create', compact('alat', 'idAlatSedangDiajukan', 'selectedAlat'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_alat'             => 'required|exists:alat,id_alat',
            'nomor_aset'          => 'required|string|max:100',
            'detail_lokasi'       => 'nullable|string|max:255',
            'tanggal_terbit_alat' => 'required|date',
            'kondisi_alat'        => 'required|in:Baik,Improvement',
            'penjelasan_kondisi'  => 'nullable|string',
            'alasan_idle'         => 'nullable|string',
            'dokumen'             => 'nullable|array',
            'dokumen.*'           => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $alat = Alat::with('lokasi')->findOrFail($request->id_alat);

        if ($this->isBandaraLocked($role) && ($alat->id_bandara ?? null) != $idBandara) {
            abort(403, 'Anda hanya bisa mengajukan idle untuk alat di bandara Anda sendiri.');
        }

        // ⚠️ BARU: validasi cakupan unit kerja juga di sisi server pas submit
        // (bukan cuma di dropdown), supaya tidak bisa dibobol lewat POST
        // langsung dengan id_alat milik unit lain.
        if (! $this->alatMasukCakupanUnit($alat)) {
            abort(403, 'Anda hanya bisa mengajukan idle untuk alat yang menjadi cakupan unit kerja Anda.');
        }

        $sudahDiajukan = PengajuanIdle::where('id_alat', $alat->id_alat)
            ->whereIn('status', ['Waiting Approval Dep Head', 'Waiting Approval Admin AFET'])
            ->exists();

        if ($sudahDiajukan) {
            return back()->withErrors(['id_alat' => 'Alat ini sudah memiliki pengajuan idle yang sedang diproses.'])->withInput();
        }

        $idLokasiAsal = $alat->id_lokasi;
        $idBandaraAsal = $alat->id_bandara;

        $lokasiUnused = Lokasi::where('id_bandara', $idBandaraAsal)->where('nama_lokasi', 'Unused')->first();

        if (! $lokasiUnused) {
            return back()->withErrors(['id_alat' => 'Lokasi "Unused" belum tersedia untuk bandara ini.'])->withInput();
        }

        DB::transaction(function () use ($alat, $idLokasiAsal, $lokasiUnused, $request) {
            $pengajuan = PengajuanIdle::create([
                'id_alat'             => $alat->id_alat,
                'nomor_aset'          => $request->nomor_aset,
                'id_lokasi_asal'      => $idLokasiAsal,
                'detail_lokasi'       => $request->detail_lokasi,
                'tanggal_terbit_alat' => $request->tanggal_terbit_alat,
                'kondisi_alat'        => $request->kondisi_alat,
                'penjelasan_kondisi'  => $request->penjelasan_kondisi,
                'id_lokasi_unused'    => $lokasiUnused->id_lokasi,
                'id_pengguna'         => session('pengguna.id'),
                'alasan_idle'         => $request->alasan_idle,
                'status'              => 'Waiting Approval Dep Head',
                'tanggal_pengajuan'   => now(),
            ]);

            if ($request->hasFile('dokumen')) {
                foreach ($request->file('dokumen') as $file) {
                    $path = $file->store('pengajuan-idle', 'public');
                    DokumenPengajuanIdle::create([
                        'id_pengajuan' => $pengajuan->id_pengajuan,
                        'nama_file'    => $file->getClientOriginalName(),
                        'path_file'    => $path,
                        'tipe_file'    => $file->getClientOriginalExtension(),
                    ]);
                }
            }

            $alatDenganRelasi = Alat::with('lokasi.bandara')->find($alat->id_alat);
            Notifikasi::buatPengajuanIdle($alatDenganRelasi, session('pengguna.nama'), $idLokasiAsal, session('pengguna.id_unit'));
        });

        return redirect()->route('admin.peralatan-idle.index')
            ->with('success', 'Pengajuan idle berhasil dibuat dan menunggu approval Dep Head.');
    }

    public function show($id)
    {
        $pengajuan = PengajuanIdle::with([
                'alat.lokasi.bandara', 'alat.bandara',
                'lokasiAsal', 'lokasiUnused', 'pemohon', 'approver', 'approverDepHead', 'dokumen'
            ])
            ->findOrFail($id);

        // ⚠️ BARU: dihitung di controller (bukan di blade) karena butuh
        // alatMasukCakupanUnit() untuk cek apakah Dep Head yang login ini
        // memang membawahi unit kerja alat yang diajukan idle-nya.
        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');
        $idLokasi = session('pengguna.id_lokasi');

        $isDepHeadBerwenang = $role === 'dep_head'
            && $pengajuan->status === 'Waiting Approval Dep Head'
            && $idBandara == ($pengajuan->alat->id_bandara ?? null)
            && ($idLokasi === null || $idLokasi == $pengajuan->id_lokasi_asal)
            && $this->alatMasukCakupanUnit($pengajuan->alat);

        return view('admin.peralatan-idle.show', compact('pengajuan', 'isDepHeadBerwenang'));
    }

    public function approve($id)
    {
        $pengajuan = PengajuanIdle::with('alat.lokasi.bandara')->findOrFail($id);
        $idBandara = session('pengguna.id_bandara');
        $idLokasi = session('pengguna.id_lokasi');
        $role = session('pengguna.role');

        if ($pengajuan->status === 'Waiting Approval Dep Head') {
            // ⚠️ DIUBAH: yang berwenang sekarang Dep Head (bukan Div Head lagi).
            // Dibatasi ke bandara + lokasinya (kalau ada), DAN kalau akun Dep
            // Head ini terikat ke 1 unit kerja spesifik, alat pengajuan ini
            // juga harus masuk cakupan unit itu (mis. Dep Head SSES tidak
            // boleh approve pengajuan idle alat milik unit BHS).
            if ($role !== 'dep_head' || $idBandara != $pengajuan->alat->id_bandara ||
                ($idLokasi !== null && $idLokasi != $pengajuan->id_lokasi_asal) ||
                ! $this->alatMasukCakupanUnit($pengajuan->alat)) {
                abort(403, 'Anda tidak berwenang memproses pengajuan ini.');
            }

            if (($pengajuan->alat->id_lokasi ?? null) != $pengajuan->id_lokasi_asal) {
                return back()->withErrors(['status' => 'Lokasi alat saat ini sudah berbeda dari lokasi asal pengajuan. Approval dibatalkan, mohon periksa kembali data lokasi alat.']);
            }

            $pengajuan->update([
                'status'                        => 'Waiting Approval Admin AFET',
                'id_pengguna_approval_dep_head' => session('pengguna.id'),
                'tanggal_approval_dep_head'     => now(),
            ]);

            Notifikasi::buatMenungguApprovalAfetRegional($pengajuan->alat, session('pengguna.nama'));

            return redirect()->route('admin.peralatan-idle.index')
                ->with('success', 'Disetujui Dep Head. Menunggu approval final Admin AFET Regional.');
        }

        if ($pengajuan->status === 'Waiting Approval Admin AFET') {
            if (! in_array($role, ['afet_regional', 'ho', 'ceo'], true)) {
                abort(403, 'Hanya Admin AFET Regional, HO, atau CEO yang bisa memberi approval final.');
            }

            if (($pengajuan->alat->id_lokasi ?? null) != $pengajuan->id_lokasi_asal) {
                return back()->withErrors(['status' => 'Lokasi alat saat ini sudah berbeda dari lokasi asal pengajuan. Approval dibatalkan, mohon periksa kembali data lokasi alat.']);
            }

            DB::transaction(function () use ($pengajuan) {
                $pengajuan->update([
                    'status'               => 'Approved',
                    'status_ketersediaan'  => 'available',
                    'tanggal_keputusan'    => now(),
                    'id_pengguna_approval' => session('pengguna.id'),
                ]);

                $pengajuan->alat->update(['id_lokasi' => $pengajuan->id_lokasi_unused]);
            });

            Notifikasi::buatKeputusanIdle($pengajuan->alat, 'Approved', session('pengguna.nama'), $pengajuan->id_pengguna);

            return redirect()->route('admin.peralatan-idle.index')
                ->with('success', 'Pengajuan idle disetujui penuh. Alat berpindah ke Unused.');
        }

        return back()->withErrors(['status' => 'Pengajuan ini sudah diproses sebelumnya.']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['alasan_reject' => 'required|string']);

        $pengajuan = PengajuanIdle::with('alat.lokasi.bandara')->findOrFail($id);
        $idBandara = session('pengguna.id_bandara');
        $idLokasi = session('pengguna.id_lokasi');
        $role = session('pengguna.role');

        if (!in_array($pengajuan->status, ['Waiting Approval Dep Head', 'Waiting Approval Admin AFET'])) {
            return back()->withErrors(['status' => 'Pengajuan ini sudah diproses sebelumnya.']);
        }

        if ($pengajuan->status === 'Waiting Approval Dep Head') {
            // ⚠️ DIUBAH: Dep Head yang berwenang reject tahap 1 (bukan Div Head lagi).
            if ($role !== 'dep_head' || $idBandara != $pengajuan->alat->id_bandara ||
                ($idLokasi !== null && $idLokasi != $pengajuan->id_lokasi_asal) ||
                ! $this->alatMasukCakupanUnit($pengajuan->alat)) {
                abort(403, 'Anda tidak berwenang memproses pengajuan ini.');
            }
        } else {
            if (! in_array($role, ['afet_regional', 'ho', 'ceo'], true)) {
                abort(403, 'Hanya Admin AFET Regional, HO, atau CEO yang bisa memberi keputusan final.');
            }
        }

        $pengajuan->update([
            'status'               => 'Rejected',
            'alasan_reject'        => $request->alasan_reject,
            'tanggal_keputusan'    => now(),
            'id_pengguna_approval' => session('pengguna.id'),
        ]);

        Notifikasi::buatKeputusanIdle($pengajuan->alat, 'Rejected', session('pengguna.nama'), $pengajuan->id_pengguna, $request->alasan_reject);

        return redirect()->route('admin.peralatan-idle.index')
            ->with('success', 'Pengajuan idle ditolak.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nomor_aset'          => 'required|string|max:100',
            'detail_lokasi'       => 'nullable|string|max:255',
            'tanggal_terbit_alat' => 'required|date',
            'kondisi_alat'        => 'required|in:Baik,Improvement',
            'penjelasan_kondisi'  => 'nullable|string',
            'alasan_idle'         => 'nullable|string',
            'dokumen'             => 'nullable|array',
            'dokumen.*'           => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        $pengajuan = PengajuanIdle::with('alat.lokasi')->findOrFail($id);

        if (session('pengguna.id') != $pengajuan->id_pengguna) {
            abort(403, 'Hanya pemohon yang bisa mengajukan ulang pengajuan ini.');
        }

        if ($pengajuan->status !== 'Rejected') {
            return back()->withErrors(['status' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.']);
        }

        DB::transaction(function () use ($pengajuan, $request) {
            $pengajuan->update([
                'nomor_aset'                    => $request->nomor_aset,
                'detail_lokasi'                 => $request->detail_lokasi,
                'tanggal_terbit_alat'           => $request->tanggal_terbit_alat,
                'kondisi_alat'                  => $request->kondisi_alat,
                'penjelasan_kondisi'            => $request->penjelasan_kondisi,
                'alasan_idle'                   => $request->alasan_idle,
                'status'                        => 'Waiting Approval Dep Head',
                'alasan_reject'                 => null,
                'tanggal_pengajuan'             => now(),
                'tanggal_keputusan'             => null,
                'id_pengguna_approval'          => null,
                'id_pengguna_approval_dep_head' => null,
                'tanggal_approval_dep_head'     => null,
            ]);

            if ($request->hasFile('dokumen')) {
                foreach ($request->file('dokumen') as $file) {
                    $path = $file->store('pengajuan-idle', 'public');
                    DokumenPengajuanIdle::create([
                        'id_pengajuan' => $pengajuan->id_pengajuan,
                        'nama_file'    => $file->getClientOriginalName(),
                        'path_file'    => $path,
                        'tipe_file'    => $file->getClientOriginalExtension(),
                    ]);
                }
            }

            $alatDenganRelasi = Alat::with('lokasi.bandara')->find($pengajuan->alat->id_alat);
            Notifikasi::buatPengajuanIdle($alatDenganRelasi, session('pengguna.nama'), $pengajuan->id_lokasi_asal, session('pengguna.id_unit'));
        });

        return redirect()->route('admin.peralatan-idle.index')
            ->with('success', 'Pengajuan idle diajukan ulang dan menunggu approval Dep Head.');
    }

    public function hapusDokumen($idPengajuan, $idDokumen)
    {
        $dokumen = DokumenPengajuanIdle::where('id_pengajuan', $idPengajuan)->where('id_dokumen', $idDokumen)->firstOrFail();
        Storage::disk('public')->delete($dokumen->path_file);
        $dokumen->delete();
        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public function tarikKembali($id)
    {
        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        if ($role !== 'afet_bandara') {
            abort(403, 'Hanya AFET Bandara yang dapat menarik kembali alat dari Unused.');
        }

        $pengajuanIdle = PengajuanIdle::with(['alat', 'lokasiAsal', 'alat.lokasi', 'alat.bandara'])->findOrFail($id);

        if ($pengajuanIdle->alat->id_bandara != $idBandara) {
            abort(403, 'Anda hanya bisa menarik kembali alat milik bandara Anda sendiri.');
        }

        if ($pengajuanIdle->status !== 'Approved' || $pengajuanIdle->status_ketersediaan !== 'available') {
            return redirect()->back()->with('error', 'Alat tidak dalam status yang bisa ditarik kembali.');
        }

        $alat = Alat::find($pengajuanIdle->id_alat);
        if ($alat->id_lokasi != $pengajuanIdle->id_lokasi_unused) {
            return redirect()->back()->with('error', 'Alat tidak berada di lokasi Unused.');
        }

        DB::transaction(function () use ($pengajuanIdle, $alat) {
            $alat->update(['id_lokasi' => $pengajuanIdle->id_lokasi_asal]);
            $pengajuanIdle->update(['status_ketersediaan' => 'not_available']);
        });

        return redirect()->route('admin.peralatan-idle.index')
            ->with('success', 'Alat berhasil ditarik kembali dari Unused ke lokasi asal.');
    }
}