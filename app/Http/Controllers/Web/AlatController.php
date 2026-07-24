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

class AlatController extends Controller
{
    /**
     * - afet_regional : lihat semua alat, semua bandara
     * - afet_bandara  : HANYA lihat alat di bandaranya sendiri
     */
    public function index(Request $request)
    {
        $role      = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $alat = Alat::with(['lokasi.bandara', 'kategori'])
            ->when($role === 'afet_bandara', function ($q) use ($idBandara) {
                $q->whereHas('lokasi', fn($q2) => $q2->where('id_bandara', $idBandara));
            })
            ->when($role === 'afet_regional' && $request->id_bandara, fn($q) => $q->whereHas('lokasi',
                fn($q2) => $q2->where('id_bandara', $request->id_bandara)
            ))
            ->when($request->id_lokasi, fn($q) => $q->where('id_lokasi', $request->id_lokasi))
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->orderBy('id_lokasi')
            ->paginate(15);

        $bandara = Bandara::orderBy('nama_bandara')->get();

        $lokasi = Lokasi::with('bandara')
            ->when($role === 'afet_bandara', fn($q) => $q->where('id_bandara', $idBandara))
            ->when($role === 'afet_regional' && $request->id_bandara, fn($q) => $q->where('id_bandara', $request->id_bandara))
            ->orderBy('nama_lokasi')
            ->get();

        $allLokasi = Lokasi::with('bandara')
            ->when($role === 'afet_bandara', fn($q) => $q->where('id_bandara', $idBandara))
            ->orderBy('nama_lokasi')
            ->get();

        $kategori = KategoriAlat::orderBy('nama_kategori')->get();

        // ⚠️ BARU: alat yang sedang punya pengajuan idle aktif (belum diputuskan),
        // dipakai untuk disable tombol "Ajukan Idle" di tabel.
        $idAlatPengajuanPending = PengajuanIdle::whereIn('status', [
                'Waiting Approval Div Head',
                'Waiting Approval Admin AFET',
            ])->pluck('id_alat')->toArray();

        return view('admin.alat.index', compact(
            'alat', 'bandara', 'lokasi', 'allLokasi', 'kategori', 'idAlatPengajuanPending'
        ));
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
            'id_lokasi', 'id_kategori', 'kode_alat', 'detail_lokasi', 'nama_alat', 'unit_kerja',
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
            'id_lokasi', 'id_kategori', 'kode_alat', 'nama_alat', 'unit_kerja', 'detail_lokasi',
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
     * AFET Bandara yang sedang login. AFET Regional selalu lolos.
     */
    private function pastikanLokasiBolehDiakses($idLokasi)
    {
        $role = session('pengguna.role');

        if ($role !== 'afet_bandara') {
            return;
        }

        $idBandara = session('pengguna.id_bandara');

        $lokasiValid = Lokasi::where('id_lokasi', $idLokasi)
            ->where('id_bandara', $idBandara)
            ->exists();

        if (! $lokasiValid) {
            abort(403, 'Anda hanya dapat mengakses data alat di bandara Anda sendiri.');
        }
    }
}