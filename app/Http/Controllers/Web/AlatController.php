<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alat;
use App\Models\Bandara;
use App\Models\Lokasi;
use App\Models\KategoriAlat;
use App\Models\Notifikasi;
use App\Models\PengajuanIdle; // ⚠️ BARU
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AlatController extends Controller
{
    /**
     * - afet_regional : lihat semua alat, semua bandara
     * - afet_bandara  : HANYA lihat alat di bandaranya sendiri
     */
    public function index(Request $request)
    {
        $role      = session('pengguna.role');
        $isLocked  = $this->isBandaraLocked($role);
        $idBandara = session('pengguna.id_bandara');
        $idUnit    = session('pengguna.id_unit');
        $unit      = $idUnit ? \App\Models\UnitKerja::find($idUnit) : null;

        $alat = Alat::with(['lokasi.bandara', 'kategori'])
            ->when($isLocked, function ($q) use ($idBandara) {
                $q->whereHas('lokasi', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when(!$isLocked && $request->id_bandara, fn($q) => $q->whereHas('lokasi',
                fn($q2) => $q2->where('id_bandara', $request->id_bandara)
            ))
            // ⚠️ BARU: kalau akun ini terikat ke 1 unit kerja spesifik (mis. SSES T1 di CGK),
            // batasi data alat cuma ke lokasi unit itu + jenis alat yang jadi cakupannya
            // (kalau cakupan_alat masih kosong, dibiarkan tanpa filter jenis dulu).
            ->when($unit, function ($q) use ($unit) {
                if ($unit->id_lokasi) {
                    // ⚠️ BARU: sama seperti Controller::scopeByUnitKerja() —
                    // alat yang barusan diidle-kan (sekarang di lokasi
                    // "Unused") tetap dianggap milik unit ini kalau riwayat
                    // idle-nya berasal dari lokasi unit ini.
                    $q->where(function ($qq) use ($unit) {
                        $qq->where('id_lokasi', $unit->id_lokasi)
                           ->orWhereHas('pengajuanIdle', function ($q2) use ($unit) {
                                $q2->where('id_lokasi_asal', $unit->id_lokasi)
                                   ->where('status', 'Approved');
                            });
                    });
                }
                if (!empty($unit->cakupan_alat)) {
                    // ⚠️ Pencocokan case-insensitive: data lama dari seeder ada yang
                    // ditulis 'X-RAY' (kapital semua) & ada yang 'X-Ray' (Title Case).
                    // Biar keduanya tetap ke-detect sebagai jenis yang sama.
                    $cakupanLower = array_map('strtolower', $unit->cakupan_alat);
                    $q->whereIn(DB::raw('LOWER(jenis_alat)'), $cakupanLower);
                }
            })
            ->when($request->id_lokasi, fn($q) => $q->where('id_lokasi', $request->id_lokasi))
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->orderBy('id_lokasi')
            ->paginate(15);

        $bandara = $isLocked
            ? Bandara::where('id_bandara', $idBandara)->get()
            : Bandara::orderBy('nama_bandara')->get();

        $lokasi = Lokasi::with('bandara')
            ->when($isLocked, fn($q) => $q->where('id_bandara', $idBandara))
            ->when(!$isLocked && $request->id_bandara, fn($q) => $q->where('id_bandara', $request->id_bandara))
            // ⚠️ BARU: kalau akun ini terikat ke unit kerja yang punya lokasi spesifik
            // (mis. SSES T2), dropdown filter lokasi cuma nampilin lokasi unit itu
            // + lokasi "Unused" di bandara yang sama (supaya alat yang baru
            // diidle-kan tetap bisa difilter/ditemukan).
            ->when($unit && $unit->id_lokasi, function ($q) use ($unit) {
                $q->where(function ($qq) use ($unit) {
                    $qq->where('id_lokasi', $unit->id_lokasi)
                       ->orWhere(function ($qqq) use ($unit) {
                            $qqq->where('id_bandara', $unit->id_bandara)
                                ->where('nama_lokasi', 'Unused');
                        });
                });
            })
            ->orderBy('nama_lokasi')
            ->get();

        $allLokasi = Lokasi::with('bandara')
            ->when($isLocked, fn($q) => $q->where('id_bandara', $idBandara))
            // ⚠️ BARU: dropdown "Tambah/Edit Alat" juga harus dibatasi ke lokasi
            // unit kerja user, biar konsisten sama validasi backend di
            // pastikanLokasiBolehDiakses(). Kalau unit tidak punya id_lokasi
            // spesifik (mis. BHS/CCIT yang cakupannya se-bandara), tidak difilter.
            ->when($unit && $unit->id_lokasi, fn($q) => $q->where('id_lokasi', $unit->id_lokasi))
            ->orderBy('nama_lokasi')
            ->get();

        $kategori = KategoriAlat::orderBy('nama_kategori')->get();

        // ⚠️ BARU: alat yang sedang punya pengajuan idle aktif (belum diputuskan),
        // dipakai untuk disable tombol "Ajukan Idle" di tabel.
        $idAlatPengajuanPending = PengajuanIdle::whereIn('status', [
                'Waiting Approval Dep Head',
                'Waiting Approval Admin AFET',
            ])->pluck('id_alat')->toArray();

        return view('admin.alat.index', compact(
            'alat', 'bandara', 'lokasi', 'allLokasi', 'kategori', 'idAlatPengajuanPending', 'isLocked'
        ))->with('jenisAlatOptions', \App\Http\Controllers\Web\PengaturanController::JENIS_ALAT_OPTIONS);
    }

    /**
     * - afet_bandara : hanya bisa tambah alat di lokasi yang ada di bandaranya sendiri
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_lokasi'       => 'required|exists:lokasi,id_lokasi',
            'id_kategori'     => 'required|exists:kategori_alat,id_kategori',
            'kode_alat'       => 'required|string|max:100',
            'detail_lokasi'   => 'nullable|string|max:255',
            'nama_alat'       => 'required|string|max:100',
            'unit_kerja'      => 'nullable|string|max:100',
            'jenis_alat'      => 'nullable|string|in:' . implode(',', PengaturanController::JENIS_ALAT_OPTIONS),
            'barcode'         => 'nullable|string|max:100|unique:alat,barcode',
            'merek'           => 'nullable|string|max:100',
            'ip_address'      => 'nullable|ip',
            'buatan'          => 'nullable|string|max:100',
            'tahun_pembuatan' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kondisi_awal'    => 'nullable|string|max:100',
            'status'          => 'nullable|in:Aktif,Tidak',
        ]);

        $this->pastikanLokasiBolehDiakses($request->id_lokasi);

        $lokasi = Lokasi::findOrFail($request->id_lokasi);
        $idBandara = $lokasi->id_bandara;

        $data = $request->only([
            'id_lokasi', 'id_kategori', 'kode_alat', 'detail_lokasi', 'nama_alat', 'unit_kerja', 'jenis_alat',
            'barcode', 'merek', 'ip_address', 'buatan', 'tahun_pembuatan', 'kondisi_awal', 'status'
        ]);

        $data['id_bandara'] = $idBandara;

        if (empty($data['barcode'])) {
            do {
                $barcode = 'ALT-' . strtoupper(Str::random(8));
            } while (Alat::where('barcode', $barcode)->exists());

            $data['barcode'] = $barcode;
        }

        $alat = Alat::create($data);

        $alatDenganRelasi = Alat::with('lokasi.bandara')->find($alat->id_alat);
        Notifikasi::buatUntukAlatBaru($alatDenganRelasi);

        return redirect()->route('admin.alat.index')
            ->with('success', 'Alat berhasil ditambahkan!');
    }

    /**
     * - afet_bandara : hanya bisa edit alat yang SEKARANG ada di bandaranya,
     *                  dan tidak bisa pindahkan alat itu ke lokasi di bandara lain.
     */
    public function update(Request $request, $id)
    {
        $alat = Alat::with('lokasi')->findOrFail($id);

        $this->pastikanLokasiBolehDiakses($alat->id_lokasi);

        $request->validate([
            'id_lokasi'       => 'required|exists:lokasi,id_lokasi',
            'id_kategori'     => 'required|exists:kategori_alat,id_kategori',
            'kode_alat'       => 'required|string|max:100',
            'detail_lokasi'   => 'nullable|string|max:255',
            'nama_alat'       => 'required|string|max:100',
            'unit_kerja'      => 'nullable|string|max:100',
            'jenis_alat'      => 'nullable|string|in:' . implode(',', PengaturanController::JENIS_ALAT_OPTIONS),
            'barcode'         => 'nullable|string|max:100|unique:alat,barcode,' . $id . ',id_alat',
            'merek'           => 'nullable|string|max:100',
            'ip_address'      => 'nullable|ip',
            'buatan'          => 'nullable|string|max:100',
            'tahun_pembuatan' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kondisi_awal'    => 'nullable|string|max:100',
            'status'          => 'nullable|in:Aktif,Tidak',
        ]);

        $this->pastikanLokasiBolehDiakses($request->id_lokasi);

        $lokasi = Lokasi::findOrFail($request->id_lokasi);
        $idBandara = $lokasi->id_bandara;

        $data = $request->only([
            'id_lokasi', 'id_kategori', 'kode_alat', 'nama_alat', 'unit_kerja', 'jenis_alat', 'detail_lokasi',
            'barcode', 'merek', 'ip_address', 'buatan', 'tahun_pembuatan', 'kondisi_awal', 'status'
        ]);

        $data['id_bandara'] = $idBandara;

        if (empty($data['barcode'])) {
            unset($data['barcode']);
        }

        $alat->update($data);

        return redirect()->route('admin.alat.index')
            ->with('success', 'Alat berhasil diupdate!');
    }

    /**
     * - afet_bandara : hanya bisa hapus alat di bandaranya sendiri
     */
    public function destroy($id)
    {
        $alat = Alat::with('lokasi')->findOrFail($id);

        $this->pastikanLokasiBolehDiakses($alat->id_lokasi);

        if ($alat->logHarian()->exists()) {
            return redirect()->route('admin.alat.index')
                ->with('error', 'Alat tidak bisa dihapus karena sudah memiliki data log!');
        }

        $alat->delete();
        return redirect()->route('admin.alat.index')
            ->with('success', 'Alat berhasil dihapus!');
    }

    /**
     * - afet_bandara : hanya bisa download QR alat di bandaranya sendiri
     */
    public function downloadQr($id)
    {
        $alat = Alat::with(['lokasi.bandara'])->findOrFail($id);

        $this->pastikanLokasiBolehDiakses($alat->id_lokasi);

        if (!$alat->barcode) {
            return redirect()->route('admin.alat.index')
                ->with('error', 'Alat ini belum memiliki barcode!');
        }

        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($alat->barcode);

        $filename = 'QR-' . $alat->barcode . '.svg';

        return response($qrCode, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Helper: pastikan id_lokasi yang dimaksud ada di bandara milik
     * role yang terkunci (afet_bandara, div_head, dep_head, gm_kc). Role bebas
     * (afet_regional, ho, ceo) selalu lolos.
     */
    private function pastikanLokasiBolehDiakses($idLokasi)
    {
        $role = session('pengguna.role');

        if (! $this->isBandaraLocked($role)) {
            return;
        }

        $idBandara = session('pengguna.id_bandara');

        $lokasiValid = Lokasi::where('id_lokasi', $idLokasi)
            ->where('id_bandara', $idBandara)
            ->exists();

        if (! $lokasiValid) {
            abort(403, 'Anda hanya dapat mengakses data alat di bandara Anda sendiri.');
        }

        // ⚠️ BARU: kalau akun ini terikat ke unit kerja tertentu yang punya
        // lokasi spesifik (mis. SSES T1), pastikan lokasi alat sama dengan
        // lokasi unit tersebut.
        $idUnit = session('pengguna.id_unit');
        if ($idUnit) {
            $unit = \App\Models\UnitKerja::find($idUnit);
            if ($unit && $unit->id_lokasi && $unit->id_lokasi != $idLokasi) {
                abort(403, 'Anda hanya dapat mengakses data alat di lokasi unit kerja Anda sendiri.');
            }
        }
    }
}