@extends('layouts.app')

@section('title', 'Pengguna - Monitoring Alat')
@section('page-title', 'Pengguna')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $roleSaya = session('pengguna.role');

    // Palet warna "clean": soft background + text senada, bukan warna solid mencolok
    $badgeColors = [
        'teknisi'       => 'role-badge role-slate',
        'afet_bandara'  => 'role-badge role-amber',
        'afet_regional' => 'role-badge role-indigo',
        'div_head'      => 'role-badge role-blue',
        'dep_head'      => 'role-badge role-cyan',
        'gm_kc'         => 'role-badge role-teal',
        'ho'            => 'role-badge role-gray',
        'ceo'           => 'role-badge role-navy',
    ];

    $badgeLabels = [
        'teknisi'       => 'Teknisi',
        'afet_bandara'  => 'AFET Bandara',
        'afet_regional' => 'AFET Regional',
        'div_head'      => 'Divisi Head',
        'dep_head'      => 'Dep Head',
        'gm_kc'         => 'GM KC',
        'ho'            => 'HO',
        'ceo'           => 'CEO',
    ];

    $canCreateUser = hasPermission('user.create');
    $canEditUser   = hasPermission('user.edit');
    $canDeleteUser = hasPermission('user.delete');
    $canChangeRole = hasPermission('user.change-role');
    $canViewAll = ($roleSaya === 'afet_regional');
@endphp

<style>
    /* ===== Role badges: clean & konsisten ===== */
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .65rem;
        border-radius: 6px;
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .01em;
        border: 1px solid transparent;
    }
    .role-slate  { background: #eef1f5; color: #47566b; border-color: #dde3ea; }
    .role-amber  { background: #fdf3e2; color: #9a6a00; border-color: #f5e3bd; }
    .role-indigo { background: #eef0fd; color: #4b4fd6; border-color: #dcdffa; }
    .role-blue   { background: #e8f1fc; color: #1f5fa8; border-color: #d3e5f7; }
    .role-cyan   { background: #e3f7fb; color: #0e7490; border-color: #cdeef5; }
    .role-teal   { background: #e5f6f3; color: #147d6f; border-color: #c9ede6; }
    .role-gray   { background: #f0f1f2; color: #5b6166; border-color: #e2e4e6; }
    .role-navy   { background: #e9ecf3; color: #2a3556; border-color: #d7dcea; }

    /* ===== Layout & komponen umum: rapi & profesional ===== */
    .page-card {
        border: 1px solid #eceff2;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .page-card .card-body { padding: 1.25rem 1.5rem; }

    .form-label.fw-semibold { font-size: .85rem; color: #4a5568; }

    .table-clean thead th {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6b7280;
        font-weight: 600;
        border-bottom: 1px solid #eceff2;
        background: #fafbfc;
    }
    .table-clean tbody td {
        vertical-align: middle;
        font-size: .9rem;
        border-bottom: 1px solid #f1f3f5;
    }
    .table-clean tbody tr:hover { background: #fafbfc; }

    .btn-icon-sm {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 7px;
    }

    .modal-content { border: none; border-radius: 14px; overflow: hidden; }
    .modal-header { background: #fafbfc; border-bottom: 1px solid #eceff2; }
    .modal-title { font-size: 1.05rem; font-weight: 600; color: #1f2937; }
    .modal-footer { border-top: 1px solid #eceff2; }
</style>

{{-- FILTER --}}
<div class="card page-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            @if($canViewAll)
            <div class="col-md-3">
                <label class="form-label fw-semibold">Bandara</label>
                <select name="id_bandara" class="form-select">
                    <option value="">Semua Bandara</option>
                    @foreach($bandara as $b)
                        <option value="{{ $b->id_bandara }}" {{ request('id_bandara') == $b->id_bandara ? 'selected' : '' }}>
                            {{ $b->kode_bandara }} - {{ $b->nama_bandara }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Role</label>
                <select name="role" class="form-select">
                    <option value="">Semua Role</option>
                    <option value="teknisi"       {{ request('role') == 'teknisi' ? 'selected' : '' }}>Teknisi</option>
                    <option value="afet_bandara"  {{ request('role') == 'afet_bandara' ? 'selected' : '' }}>AFET Bandara</option>
                    <option value="afet_regional" {{ request('role') == 'afet_regional' ? 'selected' : '' }}>AFET Regional</option>
                    <option value="div_head"      {{ request('role') == 'div_head' ? 'selected' : '' }}>Divisi Head</option>
                    <option value="dep_head"      {{ request('role') == 'dep_head' ? 'selected' : '' }}>Dep Head</option>
                    <option value="gm_kc"         {{ request('role') == 'gm_kc' ? 'selected' : '' }}>GM KC</option>
                    <option value="ho"            {{ request('role') == 'ho' ? 'selected' : '' }}>HO</option>
                    <option value="ceo"           {{ request('role') == 'ceo' ? 'selected' : '' }}>CEO</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-4 text-end">
                @if($canCreateUser)
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
                </button>
                @endif
            </div>
            @else
            <div class="col-md-6">
                <label class="form-label fw-semibold">Lokasi</label>
                <select name="id_lokasi" class="form-select">
                    <option value="">Semua Lokasi</option>
                    @foreach($lokasi as $l)
                        <option value="{{ $l->id_lokasi }}" {{ request('id_lokasi') == $l->id_lokasi ? 'selected' : '' }}>
                            {{ $l->nama_lokasi }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-4 text-end">
                @if($canCreateUser)
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
                </button>
                @endif
            </div>
            @endif
        </form>
    </div>
</div>

{{-- TABEL PENGGUNA --}}
<div class="card page-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-clean align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Bandara</th>
                        <th>Lokasi</th>
                        <th>Unit</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pengguna as $i => $p)
                    <tr>
                        <td class="text-muted">{{ $pengguna->firstItem() + $i }}</td>
                        <td class="fw-semibold text-dark">{{ $p->nama }}</td>
                        <td class="text-muted">{{ $p->username }}</td>
                        <td>
                            <span class="{{ $badgeColors[$p->role] ?? 'role-badge role-gray' }}">
                                {{ $badgeLabels[$p->role] ?? $p->role }}
                            </span>
                        </td>
                        <td>{{ $p->bandara->kode_bandara ?? '-' }}</td>
                        <td>{{ $p->lokasi->nama_lokasi ?? '-' }}</td>
                        <td>
                            @if($p->unit)
                                <span class="role-badge role-slate">{{ $p->unit->kode_unit }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($canEditUser)
                                <button class="btn btn-icon-sm btn-outline-secondary me-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEdit{{ $p->id_pengguna }}"
                                    title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endif
                            @if($canDeleteUser && $p->id_pengguna !== session('pengguna.id'))
                                <form action="{{ route('admin.pengguna.destroy', $p->id_pengguna) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Yakin hapus pengguna ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-icon-sm btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                            @if(!$canEditUser && !$canDeleteUser)
                                <span class="text-muted small">Tidak ada aksi</span>
                            @endif
                        </td>
                    </tr>

                    {{-- MODAL EDIT --}}
                    @if($canEditUser)
                    <div class="modal fade" id="modalEdit{{ $p->id_pengguna }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="bi bi-pencil-square me-2 text-muted"></i>Edit Pengguna
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('admin.pengguna.update', $p->id_pengguna) }}" method="POST">
                                    @csrf @method('PUT')
                                    <div class="modal-body">

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Nama</label>
                                                    <input type="text" name="nama" class="form-control"
                                                           value="{{ $p->nama }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Username</label>
                                                    <input type="text" name="username" class="form-control"
                                                           value="{{ $p->username }}" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Password Baru</label>
                                                    <input type="password" name="password" class="form-control"
                                                           placeholder="Kosongkan jika tidak diubah">
                                                    <small class="text-muted">Kosongkan jika tidak ingin mengganti password.</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                @if($canChangeRole)
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                                                    <select name="role" class="form-select" required>
                                                        <option value="teknisi"       {{ $p->role == 'teknisi' ? 'selected' : '' }}>Teknisi</option>
                                                        <option value="afet_bandara"  {{ $p->role == 'afet_bandara' ? 'selected' : '' }}>AFET Bandara</option>
                                                        <option value="afet_regional" {{ $p->role == 'afet_regional' ? 'selected' : '' }}>AFET Regional</option>
                                                        <option value="div_head"      {{ $p->role == 'div_head' ? 'selected' : '' }}>Divisi Head</option>
                                                        <option value="dep_head"      {{ $p->role == 'dep_head' ? 'selected' : '' }}>Dep Head</option>
                                                        <option value="gm_kc"         {{ $p->role == 'gm_kc' ? 'selected' : '' }}>General Manager KC</option>
                                                        <option value="ho"            {{ $p->role == 'ho' ? 'selected' : '' }}>Head Office</option>
                                                        <option value="ceo"           {{ $p->role == 'ceo' ? 'selected' : '' }}>CEO</option>
                                                    </select>
                                                </div>
                                                @else
                                                    <input type="hidden" name="role" value="teknisi">
                                                    <input type="hidden" name="id_bandara" value="{{ $p->id_bandara }}">
                                                    <div class="alert alert-light border small mb-0">
                                                        <i class="bi bi-info-circle me-1 text-muted"></i>
                                                        Anda hanya dapat mengubah data <strong>Teknisi</strong> di bandara Anda sendiri.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                @if($canChangeRole)
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Bandara</label>
                                                    <select name="id_bandara" class="form-select">
                                                        <option value="">- Tidak Ada -</option>
                                                        @foreach($bandara as $b)
                                                            <option value="{{ $b->id_bandara }}"
                                                                {{ $p->id_bandara == $b->id_bandara ? 'selected' : '' }}>
                                                                {{ $b->kode_bandara }} - {{ $b->nama_bandara }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">CEO, AFET Regional, dan HO tidak perlu isi bandara.</small>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Lokasi</label>
                                                    <select name="id_lokasi" class="form-select">
                                                        <option value="">- Tidak Ada -</option>
                                                        @foreach($lokasi as $l)
                                                            <option value="{{ $l->id_lokasi }}"
                                                                {{ $p->id_lokasi == $l->id_lokasi ? 'selected' : '' }}>
                                                                {{ $l->bandara->kode_bandara }} - {{ $l->nama_lokasi }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Khusus untuk role Teknisi.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Unit Kerja</label>
                                                    <select name="id_unit" class="form-select">
                                                        <option value="">- Tidak Ada -</option>
                                                        @foreach($unitKerja as $u)
                                                            <option value="{{ $u->id_unit }}"
                                                                {{ $p->id_unit == $u->id_unit ? 'selected' : '' }}>
                                                                {{ $u->kode_unit }} - {{ $u->nama_unit }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Isi kalau akun ini cuma menangani 1 unit spesifik (mis. SSES T1 di CGK). Kosongkan kalau berlaku untuk seluruh bandara/lokasi.</small>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                            Batal
                                        </button>
                                        <button type="submit" class="btn btn-dark">
                                            <i class="bi bi-save me-1"></i>Simpan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif

                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-people fs-2 d-block mb-2 opacity-50"></i>
                            Belum ada pengguna
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $pengguna->withQueryString()->links() }}
        </div>
    </div>
</div>

{{-- MODAL TAMBAH --}}
@if($canCreateUser)
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2 text-muted"></i>Tambah Pengguna
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.pengguna.store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama</label>
                                <input type="text" name="nama" class="form-control" placeholder="Nama lengkap" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Username unik" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            @if($canChangeRole)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="teknisi">Teknisi</option>
                                    <option value="afet_bandara">AFET Bandara</option>
                                    <option value="afet_regional">AFET Regional</option>
                                    <option value="div_head">Divisi Head</option>
                                    <option value="dep_head">Dep Head</option>
                                    <option value="gm_kc">General Manager KC</option>
                                    <option value="ho">Head Office</option>
                                    <option value="ceo">CEO</option>
                                </select>
                            </div>
                            @else
                                <input type="hidden" name="role" value="teknisi">
                                <div class="alert alert-light border small mb-0">
                                    <i class="bi bi-info-circle me-1 text-muted"></i>
                                    Anda hanya dapat menambahkan pengguna dengan role <strong>Teknisi</strong> di bandara Anda sendiri.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            @if($canChangeRole)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Bandara</label>
                                <select name="id_bandara" class="form-select">
                                    <option value="">- Tidak Ada -</option>
                                    @foreach($bandara as $b)
                                        <option value="{{ $b->id_bandara }}">
                                            {{ $b->kode_bandara }} - {{ $b->nama_bandara }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">CEO, AFET Regional, dan HO tidak perlu isi bandara.</small>
                            </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lokasi</label>
                                <select name="id_lokasi" class="form-select">
                                    <option value="">- Tidak Ada -</option>
                                    @foreach($lokasi as $l)
                                        <option value="{{ $l->id_lokasi }}">
                                            {{ $l->bandara->kode_bandara }} - {{ $l->nama_lokasi }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Khusus untuk role Teknisi.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Unit Kerja</label>
                                <select name="id_unit" class="form-select">
                                    <option value="">- Tidak Ada -</option>
                                    @foreach($unitKerja as $u)
                                        <option value="{{ $u->id_unit }}">
                                            {{ $u->kode_unit }} - {{ $u->nama_unit }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Isi kalau akun ini cuma menangani 1 unit spesifik (mis. SSES T1 di CGK). Kosongkan kalau berlaku untuk seluruh bandara/lokasi.</small>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-plus-lg me-1"></i>Tambah
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection