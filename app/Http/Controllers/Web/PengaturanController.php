<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bandara;
use App\Models\Lokasi;
use App\Models\KategoriAlat;
use App\Models\JenisAlat;
use App\Models\Threshold;
use App\Models\UnitKerja;

class PengaturanController extends Controller
{
    /**
     * ⚠️ BARU: daftar jenis alat sekarang diambil dari tabel jenis_alat
     * (dikelola admin lewat halaman Pengaturan), bukan hardcode di kode lagi.
     * Method ini menggantikan const JENIS_ALAT_OPTIONS yang lama — dipanggil
     * di tempat lain (mis. AlatController) yang butuh daftar nama jenis alat
     * untuk validasi/dropdown.
     */
    public static function jenisAlatOptions(): array
    {
        return JenisAlat::orderBy('nama_jenis')->pluck('nama_jenis')->all();
    }

    public function index()
    {
        $bandara   = Bandara::withCount('lokasi')->orderBy('nama_bandara')->get();
        $lokasi    = Lokasi::with('bandara')->orderBy('nama_lokasi')->get();
        $kategori  = KategoriAlat::withCount('alat')->orderBy('nama_kategori')->get();
        $jenis     = JenisAlat::withCount('alat')->orderBy('nama_jenis')->get();
        $threshold = Threshold::first();
        $unitKerja = UnitKerja::with(['bandara', 'lokasi'])
            ->withCount('pengguna')
            ->orderBy('id_bandara')
            ->orderBy('kode_unit')
            ->get();

        return view('admin.pengaturan.index', compact(
            'bandara', 'lokasi', 'kategori', 'jenis', 'threshold', 'unitKerja'
        ))->with('jenisAlatOptions', $jenis->pluck('nama_jenis')->all());
    }

    public function updateThreshold(Request $request)
    {
        $request->validate([
            'nilai_baik'    => 'required|numeric|min:0|max:100',
            'nilai_warning' => 'required|numeric|min:0|max:100',
            'nilai_buruk'   => 'required|numeric|min:0|max:100',
            'keterangan'    => 'nullable|string',
        ]);

        if (!($request->nilai_baik > $request->nilai_warning && $request->nilai_warning > $request->nilai_buruk)) {
            return back()->with('error', 'Urutan nilai harus: baik > warning > buruk!');
        }

        $threshold = Threshold::first();
        $threshold->update($request->only('nilai_baik', 'nilai_warning', 'nilai_buruk', 'keterangan'));

        return back()->with('success', 'Threshold berhasil diupdate!');
    }

    // Bandara
    public function storeBandara(Request $request)
    {
        $request->validate([
            'nama_bandara'    => 'required|string|max:100',
            'kode_bandara'    => 'required|string|max:10|unique:bandara,kode_bandara',
            'lokasi'          => 'nullable|string|max:255',
            'jam_operasional' => 'required|numeric|min:1|max:24',
        ]);

        Bandara::create($request->only('nama_bandara', 'kode_bandara', 'lokasi', 'jam_operasional'));

        return back()->with('success', 'Bandara berhasil ditambahkan!');
    }

    public function updateBandara(Request $request, $id)
    {
        $bandara = Bandara::findOrFail($id);

        $request->validate([
            'nama_bandara'    => 'required|string|max:100',
            'kode_bandara'    => 'required|string|max:10|unique:bandara,kode_bandara,' . $id . ',id_bandara',
            'lokasi'          => 'nullable|string|max:255',
            'jam_operasional' => 'required|numeric|min:1|max:24',
        ]);

        $bandara->update($request->only('nama_bandara', 'kode_bandara', 'lokasi', 'jam_operasional'));

        return back()->with('success', 'Bandara berhasil diupdate!');
    }

    public function deleteBandara($id)
    {
        $bandara = Bandara::findOrFail($id);

        if ($bandara->lokasi()->exists()) {
            return back()->with('error', 'Bandara tidak bisa dihapus karena masih memiliki lokasi!');
        }

        $bandara->delete();
        return back()->with('success', 'Bandara berhasil dihapus!');
    }

    // Lokasi
    public function storeLokasi(Request $request)
    {
        $request->validate([
            'id_bandara'  => 'required|exists:bandara,id_bandara',
            'nama_lokasi' => 'required|string|max:100',
            'keterangan'  => 'nullable|string',
        ]);

        Lokasi::create($request->only('id_bandara', 'nama_lokasi', 'keterangan'));

        return back()->with('success', 'Lokasi berhasil ditambahkan!');
    }

    public function updateLokasi(Request $request, $id)
    {
        $lokasi = Lokasi::findOrFail($id);

        $request->validate([
            'id_bandara'  => 'required|exists:bandara,id_bandara',
            'nama_lokasi' => 'required|string|max:100',
            'keterangan'  => 'nullable|string',
        ]);

        $lokasi->update($request->only('id_bandara', 'nama_lokasi', 'keterangan'));

        return back()->with('success', 'Lokasi berhasil diupdate!');
    }

    public function deleteLokasi($id)
    {
        $lokasi = Lokasi::findOrFail($id);

        if ($lokasi->alat()->exists()) {
            return back()->with('error', 'Lokasi tidak bisa dihapus karena masih memiliki alat!');
        }

        $lokasi->delete();
        return back()->with('success', 'Lokasi berhasil dihapus!');
    }

    // Kategori
    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategori_alat,nama_kategori',
            'deskripsi'     => 'nullable|string',
        ]);

        KategoriAlat::create($request->only('nama_kategori', 'deskripsi'));

        return back()->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function updateKategori(Request $request, $id)
    {
        $kategori = KategoriAlat::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategori_alat,nama_kategori,' . $id . ',id_kategori',
            'deskripsi'     => 'nullable|string',
        ]);

        $kategori->update($request->only('nama_kategori', 'deskripsi'));

        return back()->with('success', 'Kategori berhasil diupdate!');
    }

    public function deleteKategori($id)
    {
        $kategori = KategoriAlat::findOrFail($id);

        if ($kategori->alat()->exists()) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih digunakan alat!');
        }

        $kategori->delete();
        return back()->with('success', 'Kategori berhasil dihapus!');
    }

    // ==========================================
    // JENIS ALAT
    // ==========================================
    public function storeJenis(Request $request)
    {
        $request->validate([
            'nama_jenis' => 'required|string|max:100|unique:jenis_alat,nama_jenis',
            'deskripsi'  => 'nullable|string',
        ]);

        JenisAlat::create($request->only('nama_jenis', 'deskripsi'));

        return back()->with('success', 'Jenis alat berhasil ditambahkan! Langsung tersedia di form tambah/edit alat.');
    }

    public function updateJenis(Request $request, $id)
    {
        $jenis = JenisAlat::findOrFail($id);

        $request->validate([
            'nama_jenis' => 'required|string|max:100|unique:jenis_alat,nama_jenis,' . $id . ',id_jenis',
            'deskripsi'  => 'nullable|string',
        ]);

        $namaLama = $jenis->nama_jenis;
        $jenis->update($request->only('nama_jenis', 'deskripsi'));

        // Kalau nama jenis diubah, ikut update alat & cakupan_alat unit kerja
        // yang masih mereferensikan nama lama (supaya tidak "putus").
        if ($namaLama !== $jenis->nama_jenis) {
            \App\Models\Alat::where('jenis_alat', $namaLama)->update(['jenis_alat' => $jenis->nama_jenis]);

            UnitKerja::whereJsonContains('cakupan_alat', $namaLama)->get()->each(function ($unit) use ($namaLama, $jenis) {
                $cakupan = collect($unit->cakupan_alat ?? [])
                    ->map(fn ($j) => $j === $namaLama ? $jenis->nama_jenis : $j)
                    ->values()->all();
                $unit->update(['cakupan_alat' => $cakupan]);
            });
        }

        return back()->with('success', 'Jenis alat berhasil diupdate!');
    }

    public function deleteJenis($id)
    {
        $jenis = JenisAlat::findOrFail($id);

        if ($jenis->alat()->exists()) {
            return back()->with('error', 'Jenis alat tidak bisa dihapus karena masih digunakan alat!');
        }

        $jenis->delete();
        return back()->with('success', 'Jenis alat berhasil dihapus!');
    }

    // ==========================================
    // UNIT KERJA
    // ==========================================
    public function storeUnit(Request $request)
    {
        $request->validate([
            'id_bandara'   => 'required|exists:bandara,id_bandara',
            'id_lokasi'    => 'nullable|exists:lokasi,id_lokasi',
            'kode_unit'    => 'required|string|max:30',
            'nama_unit'    => 'required|string|max:150',
            'keterangan'   => 'nullable|string',
            'cakupan_alat' => 'nullable|array',
            'cakupan_alat.*' => 'string|in:' . implode(',', self::jenisAlatOptions()),
        ]);

        if ($request->filled('id_lokasi')) {
            $lokasi = Lokasi::find($request->id_lokasi);
            if ($lokasi && $lokasi->id_bandara != $request->id_bandara) {
                return back()->withErrors([
                    'id_lokasi' => 'Lokasi tidak berada di bandara yang dipilih.'
                ])->withInput();
            }
        }

        UnitKerja::create([
            'id_bandara'   => $request->id_bandara,
            'id_lokasi'    => $request->id_lokasi,
            'kode_unit'    => $request->kode_unit,
            'nama_unit'    => $request->nama_unit,
            'keterangan'   => $request->keterangan,
            'cakupan_alat' => $request->cakupan_alat ?? [],
        ]);

        return back()->with('success', 'Unit kerja berhasil ditambahkan!');
    }

    public function updateUnit(Request $request, $id)
    {
        $unit = UnitKerja::findOrFail($id);

        $request->validate([
            'id_bandara'   => 'required|exists:bandara,id_bandara',
            'id_lokasi'    => 'nullable|exists:lokasi,id_lokasi',
            'kode_unit'    => 'required|string|max:30',
            'nama_unit'    => 'required|string|max:150',
            'keterangan'   => 'nullable|string',
            'cakupan_alat' => 'nullable|array',
            'cakupan_alat.*' => 'string|in:' . implode(',', self::jenisAlatOptions()),
        ]);

        if ($request->filled('id_lokasi')) {
            $lokasi = Lokasi::find($request->id_lokasi);
            if ($lokasi && $lokasi->id_bandara != $request->id_bandara) {
                return back()->withErrors([
                    'id_lokasi' => 'Lokasi tidak berada di bandara yang dipilih.'
                ])->withInput();
            }
        }

        $unit->update([
            'id_bandara'   => $request->id_bandara,
            'id_lokasi'    => $request->id_lokasi,
            'kode_unit'    => $request->kode_unit,
            'nama_unit'    => $request->nama_unit,
            'keterangan'   => $request->keterangan,
            'cakupan_alat' => $request->cakupan_alat ?? [],
        ]);

        return back()->with('success', 'Unit kerja berhasil diupdate!');
    }

    public function deleteUnit($id)
    {
        $unit = UnitKerja::findOrFail($id);

        if ($unit->pengguna()->exists()) {
            return back()->with('error', 'Unit tidak bisa dihapus karena masih ada pengguna yang terhubung ke unit ini!');
        }

        $unit->delete();
        return back()->with('success', 'Unit kerja berhasil dihapus!');
    }
}