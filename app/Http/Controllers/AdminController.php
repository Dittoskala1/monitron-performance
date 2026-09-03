<?php
// app/Http/Controllers/AdminController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alat;
use App\Models\Bandara;
use App\Models\HasilBulanan;
use App\Models\KategoriAlat;
use App\Models\Lokasi;
use App\Models\LogHarian;
use App\Models\Notifikasi;
use App\Models\Pengguna;
use App\Models\Threshold;
use App\Models\UserRequest;
use App\Models\Role;
use Carbon\Carbon;

class AdminController extends Controller
{
    // ================================================================
    // DASHBOARD
    // ================================================================

    public function dashboard(Request $request)
    {
        $request->validate([
            'id_bandara' => 'nullable|exists:bandara,id_bandara',
            'id_alat'    => 'nullable|exists:alat,id_alat',
            'bulan'      => 'nullable|integer|min:1|max:12',
            'tahun'      => 'nullable|integer|min:2000|max:2100',
        ]);

        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);

        $alatQuery = Alat::with(['lokasi.bandara', 'kategori'])
            ->when($request->id_bandara, fn($q) => $q->whereHas('lokasi',
                fn($q) => $q->where('id_bandara', $request->id_bandara)
            ));

        $totalAlat = $alatQuery->count();

        $hasilBulanan = HasilBulanan::with('alat')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($request->id_bandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $request->id_bandara)
            ))
            ->when($request->id_alat, fn($q) => $q->where('id_alat', $request->id_alat))
            ->get();

        $rataPerforma = $hasilBulanan->avg('rata_performa') ?? 0;
        $totalBaik    = $hasilBulanan->where('status', 'Baik')->count();
        $totalWarning = $hasilBulanan->where('status', 'Warning')->count();
        $totalBuruk   = $hasilBulanan->where('status', 'Buruk')->count();

        $performaHarian = LogHarian::selectRaw('tanggal, AVG(performa) as rata_performa')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->when($request->id_bandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $request->id_bandara)
            ))
            ->when($request->id_alat, fn($q) => $q->where('id_alat', $request->id_alat))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $performaPerAlat = $hasilBulanan->map(fn($h) => [
            'nama_alat'     => $h->alat->nama_alat ?? '-',
            'rata_performa' => round($h->rata_performa, 2),
            'status'        => $h->status,
        ])->sortByDesc('rata_performa')->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'periode'           => ['bulan' => $bulan, 'tahun' => $tahun],
                'total_alat'        => $totalAlat,
                'rata_performa'     => round($rataPerforma, 2),
                'distribusi_status' => [
                    'baik'    => ['jumlah' => $totalBaik,    'persen' => $totalAlat ? round($totalBaik    / $totalAlat * 100, 1) : 0],
                    'warning' => ['jumlah' => $totalWarning, 'persen' => $totalAlat ? round($totalWarning / $totalAlat * 100, 1) : 0],
                    'buruk'   => ['jumlah' => $totalBuruk,   'persen' => $totalAlat ? round($totalBuruk   / $totalAlat * 100, 1) : 0],
                ],
                'performa_harian'   => $performaHarian,
                'performa_per_alat' => $performaPerAlat,
            ]
        ]);
    }

    // ================================================================
    // BANDARA
    // ================================================================

    public function getBandara()
    {
        $bandara = Bandara::withCount('lokasi')->orderBy('nama_bandara')->get();
        return response()->json(['success' => true, 'data' => $bandara]);
    }

    public function storeBandara(Request $request)
    {
        $request->validate([
            'nama_bandara' => 'required|string|max:100',
            'kode_bandara' => 'required|string|max:10|unique:bandara,kode_bandara',
            'lokasi'       => 'nullable|string|max:255',
        ]);

        $bandara = Bandara::create($request->only('nama_bandara', 'kode_bandara', 'lokasi'));

        return response()->json([
            'success' => true,
            'message' => 'Bandara berhasil ditambahkan',
            'data'    => $bandara
        ], 201);
    }

    public function updateBandara(Request $request, $id)
    {
        $bandara = Bandara::findOrFail($id);

        $request->validate([
            'nama_bandara' => 'required|string|max:100',
            'kode_bandara' => 'required|string|max:10|unique:bandara,kode_bandara,' . $id . ',id_bandara',
            'lokasi'       => 'nullable|string|max:255',
        ]);

        $bandara->update($request->only('nama_bandara', 'kode_bandara', 'lokasi'));

        return response()->json([
            'success' => true,
            'message' => 'Bandara berhasil diupdate',
            'data'    => $bandara
        ]);
    }

    public function deleteBandara($id)
    {
        $bandara = Bandara::findOrFail($id);

        if ($bandara->lokasi()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bandara tidak bisa dihapus karena masih memiliki lokasi'
            ], 422);
        }

        $bandara->delete();
        return response()->json(['success' => true, 'message' => 'Bandara berhasil dihapus']);
    }

    // ================================================================
    // LOKASI
    // ================================================================

    public function getLokasi(Request $request)
    {
        $lokasi = Lokasi::with('bandara')
            ->when($request->id_bandara, fn($q) => $q->where('id_bandara', $request->id_bandara))
            ->orderBy('nama_lokasi')
            ->get();

        return response()->json(['success' => true, 'data' => $lokasi]);
    }

    public function storeLokasi(Request $request)
    {
        $request->validate([
            'id_bandara'  => 'required|exists:bandara,id_bandara',
            'nama_lokasi' => 'required|string|max:100',
            'keterangan'  => 'nullable|string',
        ]);

        $lokasi = Lokasi::create($request->only('id_bandara', 'nama_lokasi', 'keterangan'));

        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil ditambahkan',
            'data'    => $lokasi->load('bandara')
        ], 201);
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

        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil diupdate',
            'data'    => $lokasi->load('bandara')
        ]);
    }

    public function deleteLokasi($id)
    {
        $lokasi = Lokasi::findOrFail($id);

        if ($lokasi->alat()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi tidak bisa dihapus karena masih memiliki alat'
            ], 422);
        }

        $lokasi->delete();
        return response()->json(['success' => true, 'message' => 'Lokasi berhasil dihapus']);
    }

    // ================================================================
    // KATEGORI ALAT
    // ================================================================

    public function getKategori()
    {
        $kategori = KategoriAlat::withCount('alat')->orderBy('nama_kategori')->get();
        return response()->json(['success' => true, 'data' => $kategori]);
    }

    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategori_alat,nama_kategori',
            'deskripsi'     => 'nullable|string',
        ]);

        $kategori = KategoriAlat::create($request->only('nama_kategori', 'deskripsi'));

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan',
            'data'    => $kategori
        ], 201);
    }

    public function updateKategori(Request $request, $id)
    {
        $kategori = KategoriAlat::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategori_alat,nama_kategori,' . $id . ',id_kategori',
            'deskripsi'     => 'nullable|string',
        ]);

        $kategori->update($request->only('nama_kategori', 'deskripsi'));

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diupdate',
            'data'    => $kategori
        ]);
    }

    public function deleteKategori($id)
    {
        $kategori = KategoriAlat::findOrFail($id);

        if ($kategori->alat()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak bisa dihapus karena masih digunakan alat'
            ], 422);
        }

        $kategori->delete();
        return response()->json(['success' => true, 'message' => 'Kategori berhasil dihapus']);
    }

    // ================================================================
    // ALAT
    // ================================================================

    public function getAlat(Request $request)
    {
        $alat = Alat::with(['lokasi.bandara', 'kategori'])
            ->when($request->id_bandara, fn($q) => $q->whereHas('lokasi',
                fn($q) => $q->where('id_bandara', $request->id_bandara)
            ))
            ->when($request->id_lokasi, fn($q) => $q->where('id_lokasi', $request->id_lokasi))
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->when($request->search,    fn($q) => $q->where('nama_alat', 'like', "%{$request->search}%"))
            ->orderBy('nama_alat')
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $alat]);
    }

    public function storeAlat(Request $request)
    {
        $request->validate([
            'id_lokasi'       => 'required|exists:lokasi,id_lokasi',
            'id_kategori'     => 'required|exists:kategori_alat,id_kategori',
            'nama_alat'       => 'required|string|max:100',
            'merek'           => 'nullable|string|max:100',
            'ip_address'      => 'nullable|ip',
            'buatan'          => 'nullable|string|max:100',
            'tahun_pembuatan' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kondisi_awal'    => 'nullable|string|max:100',
            'status'          => 'nullable|in:Aktif,Tidak Aktif',
        ]);

        $alat = Alat::create($request->only([
            'id_lokasi', 'id_kategori', 'nama_alat', 'merek',
            'ip_address', 'buatan', 'tahun_pembuatan', 'kondisi_awal', 'status'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Alat berhasil ditambahkan',
            'data'    => $alat->load(['lokasi.bandara', 'kategori'])
        ], 201);
    }

    public function updateAlat(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'id_lokasi'       => 'required|exists:lokasi,id_lokasi',
            'id_kategori'     => 'required|exists:kategori_alat,id_kategori',
            'nama_alat'       => 'required|string|max:100',
            'merek'           => 'nullable|string|max:100',
            'ip_address'      => 'nullable|ip',
            'buatan'          => 'nullable|string|max:100',
            'tahun_pembuatan' => 'nullable|integer|min:1900|max:' . date('Y'),
            'kondisi_awal'    => 'nullable|string|max:100',
            'status'          => 'nullable|in:Aktif,Tidak Aktif',
        ]);

        $alat->update($request->only([
            'id_lokasi', 'id_kategori', 'nama_alat', 'merek',
            'ip_address', 'buatan', 'tahun_pembuatan', 'kondisi_awal', 'status'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Alat berhasil diupdate',
            'data'    => $alat->load(['lokasi.bandara', 'kategori'])
        ]);
    }

    public function deleteAlat($id)
    {
        $alat = Alat::findOrFail($id);

        if ($alat->logHarian()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Alat tidak bisa dihapus karena sudah memiliki data log'
            ], 422);
        }

        $alat->delete();
        return response()->json(['success' => true, 'message' => 'Alat berhasil dihapus']);
    }

    // ================================================================
    // PENGGUNA
    // ================================================================

    public function getPengguna(Request $request)
    {
        $pengguna = Pengguna::with(['bandara', 'lokasi', 'roles'])
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->id_bandara, fn($q) => $q->where('id_bandara', $request->id_bandara))
            ->when($request->search, fn($q) => $q->where(function($query) use ($request) {
                $query->where('nama', 'like', "%{$request->search}%")
                      ->orWhere('username', 'like', "%{$request->search}%");
            }))
            ->orderBy('nama')
            ->paginate($request->get('per_page', 15));

        $pengguna->getCollection()->transform(function ($user) {
            $role = $user->roles()->first();
            $user->role_name = $role->name ?? $user->role;
            $user->role_slug = $role->slug ?? $user->role;
            return $user;
        });

        return response()->json(['success' => true, 'data' => $pengguna]);
    }

    public function storePengguna(Request $request)
    {
        // ⚠️ FIX: 'dep_head' ditambahkan (sebelumnya hilang dari whitelist,
        // jadi konsumer API gak bisa bikin akun Dep Head sama sekali —
        // persis bug yang sama seperti di PenggunaController versi web).
        // 'id_unit' juga ditambahkan: sebelumnya field ini gak ada di
        // endpoint API ini sama sekali, padahal Dep Head itu per unit kerja
        // (lihat PenggunaSeeder::seedDepHeadPerUnitCgk()) — tanpa ini, akun
        // Dep Head yang dibuat lewat API akan selalu "tanpa unit".
        $rules = [
            'nama'       => 'required|string|max:100',
            'username'   => 'required|string|max:50|unique:pengguna,username',
            'password'   => 'required|string|min:6',
            'role'       => 'required|in:teknisi,afet_bandara,afet_regional,div_head,dep_head,gm_kc,ho,ceo',
            'id_bandara' => 'nullable|exists:bandara,id_bandara',
            'id_lokasi'  => 'nullable|exists:lokasi,id_lokasi',
            'id_unit'    => 'nullable|exists:unit_kerja,id_unit',
        ];

        // Dep Head wajib terikat ke 1 unit kerja spesifik, supaya cakupan
        // approve-nya jelas (sama seperti aturan di PenggunaController web).
        if ($request->input('role') === 'dep_head') {
            $rules['id_unit'] = 'required|exists:unit_kerja,id_unit';
        }

        $request->validate($rules);

        $pengguna = Pengguna::create([
            'nama'       => $request->nama,
            'username'   => $request->username,
            'password'   => bcrypt($request->password),
            'role'       => $request->role,
            'id_bandara' => $request->id_bandara,
            'id_lokasi'  => $request->id_lokasi,
            'id_unit'    => $request->id_unit,
        ]);

        $role = Role::where('slug', $request->role)->first();
        if ($role) {
            $pengguna->roles()->attach($role->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil ditambahkan',
            'data'    => $pengguna->load(['bandara', 'lokasi', 'roles'])
        ], 201);
    }

    public function updatePengguna(Request $request, $id)
    {
        $pengguna = Pengguna::findOrFail($id);

        // ⚠️ FIX: sama seperti storePengguna() di atas — 'dep_head' dan
        // 'id_unit' ditambahkan.
        $rules = [
            'nama'       => 'required|string|max:100',
            'username'   => 'required|string|max:50|unique:pengguna,username,' . $id . ',id_pengguna',
            'password'   => 'nullable|string|min:6',
            'role'       => 'required|in:teknisi,afet_bandara,afet_regional,div_head,dep_head,gm_kc,ho,ceo',
            'id_bandara' => 'nullable|exists:bandara,id_bandara',
            'id_lokasi'  => 'nullable|exists:lokasi,id_lokasi',
            'id_unit'    => 'nullable|exists:unit_kerja,id_unit',
        ];

        if ($request->input('role') === 'dep_head') {
            $rules['id_unit'] = 'required|exists:unit_kerja,id_unit';
        }

        $request->validate($rules);

        $data = $request->only('nama', 'username', 'role', 'id_bandara', 'id_lokasi', 'id_unit');
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $pengguna->update($data);

        $role = Role::where('slug', $request->role)->first();
        if ($role) {
            $pengguna->roles()->sync([$role->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil diupdate',
            'data'    => $pengguna->load(['bandara', 'lokasi', 'roles'])
        ]);
    }

    public function deletePengguna(Request $request, $id)
    {
        $pengguna = Pengguna::findOrFail($id);

        if ($pengguna->id_pengguna === $request->user()->id_pengguna) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa menghapus akun sendiri'
            ], 422);
        }

        $pengguna->delete();
        return response()->json(['success' => true, 'message' => 'Pengguna berhasil dihapus']);
    }

    // ================================================================
    // USER REQUESTS (REGISTER APPROVAL) - TAMBAHAN BARU
    // ================================================================

    public function getUserRequests(Request $request)
    {
        $requests = UserRequest::with(['bandara', 'lokasi'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function approveUserRequest(Request $request, $id)
    {
        $userRequest = UserRequest::find($id);

        if (!$userRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request tidak ditemukan'
            ], 404);
        }

        if ($userRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request sudah diproses sebelumnya'
            ], 422);
        }

        // ⚠️ FIX: 'dep_head' ditambahkan ke whitelist (sebelumnya hilang, sama seperti
        // bug di storePengguna/updatePengguna sebelum diperbaiki). Karena user_requests
        // tidak punya kolom id_unit (pendaftar mandiri gak pernah mengisi unit kerja),
        // 'id_unit' diterima di sini sebagai input tambahan dari admin saat approve,
        // dan wajib diisi kalau admin memilih role dep_head — supaya gak ada akun
        // Dep Head yang lolos tanpa unit lewat jalur approval ini.
        $rules = [
            'role' => 'required|in:teknisi,afet_bandara,afet_regional,div_head,dep_head,gm_kc,ho,ceo',
        ];

        if ($request->input('role') === 'dep_head') {
            $rules['id_unit'] = 'required|exists:unit_kerja,id_unit';
        }

        $request->validate($rules);

        $user = Pengguna::create([
            'nama' => $userRequest->nama,
            'username' => $userRequest->username,
            'password' => $userRequest->password,
            'role' => $request->role,
            'id_bandara' => $userRequest->id_bandara,
            'id_lokasi' => $userRequest->id_lokasi,
            'id_unit' => $request->input('id_unit'),
        ]);

        $role = Role::where('slug', $request->role)->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        $userRequest->update([
            'status' => 'approved',
            'approved_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil di-approve',
            'data' => [
                'user' => $user->load(['bandara', 'lokasi', 'roles']),
                'request' => $userRequest
            ]
        ]);
    }

    public function rejectUserRequest(Request $request, $id)
    {
        $userRequest = UserRequest::find($id);

        if (!$userRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request tidak ditemukan'
            ], 404);
        }

        if ($userRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request sudah diproses sebelumnya'
            ], 422);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $userRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason ?? 'Ditolak oleh admin',
            'rejected_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request berhasil ditolak',
            'data' => $userRequest
        ]);
    }

    // ================================================================
    // ROLES - TAMBAHAN BARU
    // ================================================================

    public function getRoles()
    {
        $roles = Role::all();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    // ================================================================
    // THRESHOLD
    // ================================================================

    public function getThreshold()
    {
        $threshold = Threshold::first();
        return response()->json(['success' => true, 'data' => $threshold]);
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
            return response()->json([
                'success' => false,
                'message' => 'Urutan nilai harus: baik > warning > buruk'
            ], 422);
        }

        $threshold = Threshold::first();
        if ($threshold) {
            $threshold->update($request->only('nilai_baik', 'nilai_warning', 'nilai_buruk', 'keterangan'));
        } else {
            $threshold = Threshold::create($request->only('nilai_baik', 'nilai_warning', 'nilai_buruk', 'keterangan'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Threshold berhasil diupdate',
            'data'    => $threshold
        ]);
    }

    // ================================================================
    // REKAP BULANAN
    // ================================================================

    public function rekapBulanan(Request $request)
    {
        $request->validate([
            'bulan'      => 'required|integer|min:1|max:12',
            'tahun'      => 'required|integer|min:2000|max:2100',
            'id_bandara' => 'nullable|exists:bandara,id_bandara',
            'id_alat'    => 'nullable|exists:alat,id_alat',
        ]);

        $rekap = HasilBulanan::with(['alat.lokasi.bandara'])
            ->where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->when($request->id_bandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $request->id_bandara)
            ))
            ->when($request->id_alat, fn($q) => $q->where('id_alat', $request->id_alat))
            ->orderByDesc('rata_performa')
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $rekap]);
    }

    // ================================================================
    // LOG HARIAN
    // ================================================================

    public function getLogHarian(Request $request)
    {
        $request->validate([
            'id_alat'    => 'nullable|exists:alat,id_alat',
            'id_bandara' => 'nullable|exists:bandara,id_bandara',
            'bulan'      => 'nullable|integer|min:1|max:12',
            'tahun'      => 'nullable|integer|min:2000|max:2100',
            'kondisi'    => 'nullable|in:Normal,Gangguan,Rusak',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ]);

        $logs = LogHarian::with(['alat.lokasi.bandara', 'pengguna'])
            ->when($request->id_alat,    fn($q) => $q->where('id_alat', $request->id_alat))
            ->when($request->id_bandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $request->id_bandara)
            ))
            ->when($request->bulan, fn($q) => $q->whereMonth('tanggal', $request->bulan))
            ->when($request->tahun, fn($q) => $q->whereYear('tanggal',  $request->tahun))
            ->when($request->kondisi,    fn($q) => $q->where('kondisi', $request->kondisi))
            ->orderByDesc('tanggal')
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $logs]);
    } 

    public function getDetailLogHarian($id)
    {
        $log = LogHarian::with(['alat.lokasi.bandara', 'pengguna'])
            ->where('id_log', $id)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log tidak ditemukan'
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $log]);
    }

    // ================================================================
    // NOTIFIKASI
    // ================================================================

    public function getNotifikasi(Request $request)
    {
        $notifikasi = Notifikasi::with('alat.lokasi.bandara')
            ->when($request->status,     fn($q) => $q->where('status', $request->status))
            ->when($request->id_bandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q) => $q->where('id_bandara', $request->id_bandara)
            ))
            ->orderByDesc('tanggal')
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $notifikasi]);
    }

    public function bacaNotifikasi($id)
    {
        $notifikasi = Notifikasi::findOrFail($id);
        $notifikasi->update(['status' => 'Dibaca']);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sebagai dibaca'
        ]);
    }
}