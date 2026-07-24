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

        $pengajuan = PengajuanIdle::with([
                'alat.lokasi.bandara', 'alat.bandara',
                'lokasiAsal', 'lokasiUnused', 'pemohon', 'approver', 'approverDivHead'
            ])
            ->when($role === 'afet_bandara', function ($q) use ($idBandara) {
                $q->whereHas('alat', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when($role === 'div_head', function ($q) use ($idBandara, $idLokasi) {
                $q->whereHas('alat', fn($q2) => $q2->where('id_bandara', $idBandara));
                if ($idLokasi !== null) {
                    $q->where('id_lokasi_asal', $idLokasi);
                }
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
        $idBandara = session('pengguna.id_bandara');

        $idAlatSedangDiajukan = PengajuanIdle::whereIn('status', ['Waiting Approval Div Head', 'Waiting Approval Admin AFET'])
            ->pluck('id_alat')->toArray();

        $selectedAlat = null;

        if ($request->filled('id_alat')) {
            $selectedAlat = Alat::with('lokasi.bandara')->find($request->id_alat);

            if ($selectedAlat) {
                // Validasi akses: afet_bandara cuma boleh ajukan alat di bandaranya sendiri
                if ($role === 'afet_bandara' && ($selectedAlat->id_bandara ?? null) != $idBandara) {
                    abort(403, 'Anda hanya bisa mengajukan idle untuk alat di bandara Anda sendiri.');
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
            ->when($role === 'afet_bandara', function ($q) use ($idBandara) {
                $q->whereHas('lokasi', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
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

        if ($role === 'afet_bandara' && ($alat->id_bandara ?? null) != $idBandara) {
            abort(403, 'Anda hanya bisa mengajukan idle untuk alat di bandara Anda sendiri.');
        }

        $sudahDiajukan = PengajuanIdle::where('id_alat', $alat->id_alat)
            ->whereIn('status', ['Waiting Approval Div Head', 'Waiting Approval Admin AFET'])
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
                'status'              => 'Waiting Approval Div Head',
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
            Notifikasi::buatPengajuanIdle($alatDenganRelasi, session('pengguna.nama'), $idLokasiAsal);
        });

        return redirect()->route('admin.peralatan-idle.index')
            ->with('success', 'Pengajuan idle berhasil dibuat dan menunggu approval Div Head.');
    }

    public function show($id)
    {
        $pengajuan = PengajuanIdle::with([
                'alat.lokasi.bandara', 'alat.bandara',
                'lokasiAsal', 'lokasiUnused', 'pemohon', 'approver', 'approverDivHead', 'dokumen'
            ])
            ->findOrFail($id);

        return view('admin.peralatan-idle.show', compact('pengajuan'));
    }

    public function approve($id)
    {
        $pengajuan = PengajuanIdle::with('alat.lokasi.bandara')->findOrFail($id);
        $idBandara = session('pengguna.id_bandara');
        $idLokasi = session('pengguna.id_lokasi');
        $role = session('pengguna.role');

        if ($pengajuan->status === 'Waiting Approval Div Head') {
            if ($role !== 'div_head' || $idBandara != $pengajuan->alat->id_bandara ||
                ($idLokasi !== null && $idLokasi != $pengajuan->id_lokasi_asal)) {
                abort(403, 'Anda tidak berwenang memproses pengajuan ini.');
            }

            if (($pengajuan->alat->id_lokasi ?? null) != $pengajuan->id_lokasi_asal) {
                return back()->withErrors(['status' => 'Lokasi alat saat ini sudah berbeda dari lokasi asal pengajuan. Approval dibatalkan, mohon periksa kembali data lokasi alat.']);
            }

            $pengajuan->update([
                'status'                        => 'Waiting Approval Admin AFET',
                'id_pengguna_approval_div_head' => session('pengguna.id'),
                'tanggal_approval_div_head'     => now(),
            ]);

            Notifikasi::buatMenungguApprovalAfetRegional($pengajuan->alat, session('pengguna.nama'));

            return redirect()->route('admin.peralatan-idle.index')
                ->with('success', 'Disetujui Div Head. Menunggu approval final Admin AFET Regional.');
        }

        if ($pengajuan->status === 'Waiting Approval Admin AFET') {
            if ($role !== 'afet_regional') {
                abort(403, 'Hanya Admin AFET Regional yang bisa memberi approval final.');
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

        if (!in_array($pengajuan->status, ['Waiting Approval Div Head', 'Waiting Approval Admin AFET'])) {
            return back()->withErrors(['status' => 'Pengajuan ini sudah diproses sebelumnya.']);
        }

        if ($pengajuan->status === 'Waiting Approval Div Head') {
            if ($role !== 'div_head' || $idBandara != $pengajuan->alat->id_bandara ||
                ($idLokasi !== null && $idLokasi != $pengajuan->id_lokasi_asal)) {
                abort(403, 'Anda tidak berwenang memproses pengajuan ini.');
            }
        } else {
            if ($role !== 'afet_regional') {
                abort(403, 'Hanya Admin AFET Regional yang bisa memberi keputusan final.');
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
                'status'                        => 'Waiting Approval Div Head',
                'alasan_reject'                 => null,
                'tanggal_pengajuan'             => now(),
                'tanggal_keputusan'             => null,
                'id_pengguna_approval'          => null,
                'id_pengguna_approval_div_head' => null,
                'tanggal_approval_div_head'     => null,
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
            Notifikasi::buatPengajuanIdle($alatDenganRelasi, session('pengguna.nama'), $pengajuan->id_lokasi_asal);
        });

        return redirect()->route('admin.peralatan-idle.index')
            ->with('success', 'Pengajuan idle diajukan ulang dan menunggu approval Div Head.');
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