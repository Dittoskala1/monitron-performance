<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pengguna;
use App\Models\Bandara;
use App\Models\Lokasi;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class PenggunaController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $allowedRoles = ['afet_bandara', 'afet_regional'];

        if (!in_array($role, $allowedRoles)) {
            abort(403, 'Anda tidak memiliki izin untuk melihat daftar pengguna.');
        }

        $pengguna = Pengguna::with(['bandara', 'lokasi', 'roles'])
            ->when($role === 'afet_bandara', function ($q) use ($idBandara, $request) {
                $q->where('role', 'teknisi')
                  ->where('id_bandara', $idBandara)
                  ->when($request->id_lokasi, fn($q2) => $q2->where('id_lokasi', $request->id_lokasi));
            })
            ->when($role === 'afet_regional', function ($q) use ($request) {
                $q->when($request->role, fn($q2) => $q2->where('role', $request->role))
                  ->when($request->id_bandara, fn($q2) => $q2->where('id_bandara', $request->id_bandara));
            })
            ->orderBy('nama')
            ->paginate(15);

        $bandara = Bandara::orderBy('nama_bandara')->get();

        $lokasi = Lokasi::with('bandara')
            ->when($role === 'afet_bandara', fn($q) => $q->where('id_bandara', $idBandara))
            ->orderBy('nama_lokasi')
            ->get();

        $roleList = Role::orderBy('name')->get();
        // ⚠️ DIHAPUS: $permissions = Permission::all(); — tidak lagi dipakai di view

        return view('admin.pengguna.index', compact(
            'pengguna',
            'bandara',
            'lokasi',
            'roleList'
        ));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $allowedRoles = ['afet_bandara', 'afet_regional', 'gm_kc'];

        if (!in_array($role, $allowedRoles)) {
            abort(403, 'Anda tidak memiliki izin untuk menambah pengguna.');
        }

        if (in_array($role, ['afet_bandara', 'gm_kc'])) {
            $request->merge([
                'role' => 'teknisi',
                'id_bandara' => $idBandara,
            ]);
        }

        $rules = [
            'nama' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:pengguna,username',
            'password' => 'required|string|min:6',
            'id_bandara' => 'nullable|exists:bandara,id_bandara',
            'id_lokasi' => 'nullable|exists:lokasi,id_lokasi',
            // ⚠️ DIHAPUS: 'permissions' => 'nullable|array', 'permissions.*' => 'exists:permissions,id',
        ];

        if ($role === 'afet_regional') {
            $rules['role'] = 'required|in:teknisi,afet_bandara,afet_regional,div_head,gm_kc,ho,ceo';
        } else {
            $rules['role'] = 'required|in:teknisi';
        }

        $request->validate($rules);

        if ($request->filled('id_lokasi')) {
            $lokasi = Lokasi::find($request->id_lokasi);
            $bandaraId = $request->id_bandara ?? $idBandara;

            if ($lokasi && $lokasi->id_bandara != $bandaraId) {
                return back()->withErrors([
                    'id_lokasi' => 'Lokasi tidak berada di bandara yang dipilih.'
                ])->withInput();
            }
        }

        // ==========================================
        // SIMPAN PENGGUNA
        // ==========================================
        $pengguna = Pengguna::create([
            'nama' => $request->nama,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'id_bandara' => $request->id_bandara,
            'id_lokasi' => $request->id_lokasi,
        ]);

        // ==========================================
        // 🔥 ASSIGN ROLE KE USER_HAS_ROLES
        // ⚠️ DIUBAH: sync permission dihapus — permission murni ikut role,
        // diatur terpusat lewat halaman Role & Permission.
        // ==========================================
        $roleModel = Role::where('slug', $request->role)->first();

        if ($roleModel) {
            $pengguna->roles()->attach($roleModel->id);
        }

        return redirect()->route('admin.pengguna.index')
            ->with('success', 'Pengguna berhasil ditambahkan!');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $pengguna = Pengguna::with('roles')->findOrFail($id);

        if (in_array($role, ['afet_bandara', 'gm_kc'])) {
            if ($pengguna->role !== 'teknisi' || $pengguna->id_bandara != $idBandara) {
                abort(403, 'Anda hanya dapat mengubah data teknisi di bandara Anda sendiri.');
            }

            $request->merge([
                'role' => 'teknisi',
                'id_bandara' => $idBandara,
            ]);
        }

        $rules = [
            'nama' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:pengguna,username,' . $id . ',id_pengguna',
            'password' => 'nullable|string|min:6',
            'id_bandara' => 'nullable|exists:bandara,id_bandara',
            'id_lokasi' => 'nullable|exists:lokasi,id_lokasi',
            // ⚠️ DIHAPUS: 'permissions' => 'nullable|array', 'permissions.*' => 'exists:permissions,id',
        ];

        if ($role === 'afet_regional') {
            $rules['role'] = 'required|in:teknisi,afet_bandara,afet_regional,div_head,gm_kc,ho,ceo';
        } else {
            $rules['role'] = 'required|in:teknisi';
        }

        $request->validate($rules);

        if ($request->filled('id_lokasi')) {
            $lokasi = Lokasi::find($request->id_lokasi);
            $bandaraId = $request->id_bandara ?? $idBandara;

            if ($lokasi && $lokasi->id_bandara != $bandaraId) {
                return back()->withErrors([
                    'id_lokasi' => 'Lokasi tidak berada di bandara yang dipilih.'
                ])->withInput();
            }
        }

        // ==========================================
        // UPDATE PENGGUNA
        // ==========================================
        $data = $request->only('nama', 'username', 'role', 'id_bandara', 'id_lokasi');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $pengguna->update($data);

        // ==========================================
        // 🔥 SYNC ROLE KE USER_HAS_ROLES
        // ⚠️ DIUBAH: sync permission dihapus — sama seperti store()
        // ==========================================
        $roleModel = Role::where('slug', $request->role)->first();

        if ($roleModel) {
            $pengguna->roles()->sync([$roleModel->id]);
        }

        return redirect()->route('admin.pengguna.index')
            ->with('success', 'Pengguna berhasil diupdate!');
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        $role = session('pengguna.role');
        $idBandara = session('pengguna.id_bandara');

        $pengguna = Pengguna::findOrFail($id);

        if ($pengguna->id_pengguna === session('pengguna.id')) {
            return redirect()->route('admin.pengguna.index')
                ->with('error', 'Tidak bisa menghapus akun sendiri!');
        }

        if (in_array($role, ['afet_bandara', 'gm_kc'])) {
            if ($pengguna->role !== 'teknisi' || $pengguna->id_bandara != $idBandara) {
                abort(403, 'Anda hanya dapat menghapus data teknisi di bandara Anda sendiri.');
            }
        }

        $pengguna->roles()->detach();
        $pengguna->delete();

        return redirect()->route('admin.pengguna.index')
            ->with('success', 'Pengguna berhasil dihapus!');
    }
}