<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bandara;
use App\Models\Lokasi;
use App\Models\KategoriAlat;
use App\Models\Threshold;

class PengaturanController extends Controller
{
    public function index()
    {
        $bandara   = Bandara::withCount('lokasi')->orderBy('nama_bandara')->get();
        $lokasi    = Lokasi::with('bandara')->orderBy('nama_lokasi')->get();
        $kategori  = KategoriAlat::withCount('alat')->orderBy('nama_kategori')->get();
        $threshold = Threshold::first();

        return view('admin.pengaturan.index', compact('bandara', 'lokasi', 'kategori', 'threshold'));
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
}