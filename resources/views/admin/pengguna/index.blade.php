@extends('layouts.app')

@section('title', 'Pengguna - Monitoring Alat')
@section('page-title', 'Pengguna')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $roleSaya = session('pengguna.role');

    $badgeColors = [
        'teknisi'       => 'bg-info',
        'afet_bandara'  => 'bg-warning text-dark',
        'afet_regional' => 'bg-danger',
        'div_head'      => 'bg-primary',
        'gm_kc'         => 'bg-success',
        'ho'            => 'bg-secondary',
        'ceo'           => 'bg-dark',
    ];

    $badgeLabels = [
        'teknisi'       => '🔧 Teknisi',
        'afet_bandara'  => '🏢 AFET Bandara',
        'afet_regional' => '🌐 AFET Regional',
        'div_head'      => '📋 Divisi Head',
        'gm_kc'         => '👔 GM KC',
        'ho'            => '🏛️ HO',
        'ceo'           => '👑 CEO',
    ];

    $canCreateUser = hasPermission('user.create');
    $canEditUser   = hasPermission('user.edit');
    $canDeleteUser = hasPermission('user.delete');
    $canChangeRole = hasPermission('user.change-role');
    $canViewAll = ($roleSaya === 'afet_regional');
    // ⚠️ DIHAPUS: $permissions, $groups, $rolePermissions — tidak lagi dipakai
@endphp

{{-- FILTER --}}
<div class="card stat-card mb-4">
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
                    <option value="teknisi"       {{ request('role') == 'teknisi' ? 'selected' : '' }}>🔧 Teknisi</option>
                    <option value="afet_bandara"  {{ request('role') == 'afet_bandara' ? 'selected' : '' }}>🏢 AFET Bandara</option>
                    <option value="afet_regional" {{ request('role') == 'afet_regional' ? 'selected' : '' }}>🌐 AFET Regional</option>
                    <option value="div_head"      {{ request('role') == 'div_head' ? 'selected' : '' }}>📋 Divisi Head</option>
                    <option value="gm_kc"         {{ request('role') == 'gm_kc' ? 'selected' : '' }}>👔 GM KC</option>
                    <option value="ho"            {{ request('role') == 'ho' ? 'selected' : '' }}>🏛️ HO</option>
                    <option value="ceo"           {{ request('role') == 'ceo' ? 'selected' : '' }}>👑 CEO</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-4 text-end">
                @if($canCreateUser)
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Pengguna
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
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-4 text-end">
                @if($canCreateUser)
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Pengguna
                </button>
                @endif
            </div>
            @endif
        </form>
    </div>
</div>

{{-- TABEL PENGGUNA --}}
<div class="card stat-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Bandara</th>
                        <th>Lokasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pengguna as $i => $p)
                    <tr>
                        <td>{{ $pengguna->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $p->nama }}</td>
                        <td>{{ $p->username }}</td>
                        <td>
                            <span class="badge {{ $badgeColors[$p->role] ?? 'bg-secondary' }}">
                                {{ $badgeLabels[$p->role] ?? $p->role }}
                            </span>
                        </td>
                        <td>{{ $p->bandara->kode_bandara ?? '-' }}</td>
                        <td>{{ $p->lokasi->nama_lokasi ?? '-' }}</td>
                        <td>
                            @if($canEditUser)
                                <button class="btn btn-sm btn-warning me-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEdit{{ $p->id_pengguna }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endif
                            @if($canDeleteUser && $p->id_pengguna !== session('pengguna.id'))
                                <form action="{{ route('admin.pengguna.destroy', $p->id_pengguna) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Yakin hapus pengguna ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                            @if(!$canEditUser && !$canDeleteUser)
                                <span class="text-muted small">Tidak ada aksi</span>
                            @endif
                        </td>
                    </tr>

                    {{-- MODAL EDIT (tanpa checklist permission) --}}
                    @if($canEditUser)
                    <div class="modal fade" id="modalEdit{{ $p->id_pengguna }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="bi bi-pencil-square me-2"></i>Edit Pengguna
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
                                                        <option value="teknisi"       {{ $p->role == 'teknisi' ? 'selected' : '' }}>🔧 Teknisi</option>
                                                        <option value="afet_bandara"  {{ $p->role == 'afet_bandara' ? 'selected' : '' }}>🏢 AFET Bandara</option>
                                                        <option value="afet_regional" {{ $p->role == 'afet_regional' ? 'selected' : '' }}>🌐 AFET Regional</option>
                                                        <option value="div_head"      {{ $p->role == 'div_head' ? 'selected' : '' }}>📋 Divisi Head</option>
                                                        <option value="gm_kc"         {{ $p->role == 'gm_kc' ? 'selected' : '' }}>👔 General Manager KC</option>
                                                        <option value="ho"            {{ $p->role == 'ho' ? 'selected' : '' }}>🏛️ Head Office</option>
                                                        <option value="ceo"           {{ $p->role == 'ceo' ? 'selected' : '' }}>👑 CEO</option>
                                                    </select>
                                                </div>
                                                @else
                                                    <input type="hidden" name="role" value="teknisi">
                                                    <input type="hidden" name="id_bandara" value="{{ $p->id_bandara }}">
                                                    <div class="alert alert-info">
                                                        <i class="bi bi-info-circle me-2"></i>
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

                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x-circle me-1"></i>Batal
                                        </button>
                                        <button type="submit" class="btn btn-primary">
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
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-people fs-2 d-block mb-2"></i>
                            Belum ada pengguna
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $pengguna->withQueryString()->links() }}
    </div>
</div>

{{-- MODAL TAMBAH (tanpa checklist permission) --}}
@if($canCreateUser)
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2"></i>Tambah Pengguna
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
                                    <option value="teknisi">🔧 Teknisi</option>
                                    <option value="afet_bandara">🏢 AFET Bandara</option>
                                    <option value="afet_regional">🌐 AFET Regional</option>
                                    <option value="div_head">📋 Divisi Head</option>
                                    <option value="gm_kc">👔 General Manager KC</option>
                                    <option value="ho">🏛️ Head Office</option>
                                    <option value="ceo">👑 CEO</option>
                                </select>
                            </div>
                            @else
                                <input type="hidden" name="role" value="teknisi">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
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

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-circle me-1"></i>Tambah
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection