{{-- resources/views/admin/roles/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Manajemen Role & Permission')
@section('page-title', 'Manajemen Role & Permission')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 d-flex align-items-center">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 d-flex align-items-center">
        <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Header ringkas --}}
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>Role & Permission</h4>
        <p class="text-muted mb-0">Atur apa saja yang boleh diakses setiap role. Perubahan berlaku setelah klik <strong>Simpan</strong> di masing-masing role.</p>
    </div>
    @if(hasPermission('user.change-role'))
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahRole">
        <i class="bi bi-plus-circle me-1"></i>Tambah Role
    </button>
    @endif
</div>

{{-- Daftar Role — bentuk Accordion supaya tidak semua terbuka sekaligus --}}
<div class="accordion" id="roleAccordion">
    @forelse($roles as $index => $role)
    @php
        $groups = $permissions->groupBy('group');
        $selectedCount = count($rolePermissions[$role->id] ?? []);
        $totalCount = $permissions->count();
    @endphp
    <div class="accordion-item border rounded-3 mb-3 shadow-sm overflow-hidden">
        <h2 class="accordion-header">
            <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }} bg-white"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse{{ $role->id }}">
                <div class="d-flex align-items-center justify-content-between w-100 me-3 flex-wrap gap-2">
                    <div>
                        <span class="fw-semibold fs-6">{{ $role->name }}</span>
                        <span class="badge bg-light text-secondary border ms-2">{{ $role->slug }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                            <i class="bi bi-people-fill me-1"></i>{{ $role->users->count() }} pengguna
                        </span>
                        <span class="badge permission-count-badge bg-primary-subtle text-primary-emphasis border border-primary-subtle"
                              data-role="{{ $role->id }}">
                            <i class="bi bi-key-fill me-1"></i>{{ $selectedCount }}/{{ $totalCount }} izin
                        </span>
                    </div>
                </div>
            </button>
        </h2>
        <div id="collapse{{ $role->id }}"
             class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
             data-bs-parent="#roleAccordion">
            <div class="accordion-body bg-light-subtle">

                @if($role->description)
                <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>{{ $role->description }}</p>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 permission-search"
                               data-role="{{ $role->id }}"
                               placeholder="Cari permission...">
                    </div>

                    @if(hasPermission('user.change-role'))
                    <div>
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditRole{{ $role->id }}">
                            <i class="bi bi-pencil me-1"></i>Edit Role
                        </button>
                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Yakin hapus role {{ $role->name }}? Tindakan ini tidak bisa dibatalkan.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Hapus
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                <form action="{{ route('admin.roles.update', $role->id) }}" method="POST" class="role-permission-form" data-role="{{ $role->id }}">
                    @csrf @method('PUT')

                    <div class="row g-3">
                        @foreach($groups as $groupName => $perms)
                        @php
                            $groupIcons = [
                                'data_alat' => 'bi-cpu',
                                'data_harian' => 'bi-calendar-check',
                                'peralatan_idle' => 'bi-pause-circle',
                                'mutasi' => 'bi-arrow-left-right',
                                'peralatan_booking' => 'bi-calendar2-week',
                                'manajemen_pengguna' => 'bi-people',
                                'notifikasi' => 'bi-bell',
                                'laporan' => 'bi-file-earmark-bar-graph',
                            ];
                            $groupIcon = $groupIcons[$groupName] ?? 'bi-folder';
                        @endphp
                        <div class="col-md-4 permission-group" data-group="{{ $groupName }}">
                            <div class="border rounded-3 p-3 bg-white h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-semibold text-primary mb-0">
                                        <i class="bi {{ $groupIcon }} me-1"></i>
                                        {{ str_replace('_', ' ', ucwords($groupName)) }}
                                    </h6>
                                    <div class="form-check form-switch mb-0">
                                        <input type="checkbox" role="switch" class="form-check-input select-all"
                                               data-group="{{ $groupName }}" data-role="{{ $role->id }}">
                                    </div>
                                </div>
                                <hr class="mt-1 mb-2">
                                @foreach($perms as $perm)
                                <div class="form-check permission-item mb-1"
                                     data-label="{{ strtolower($perm->display_name) }}">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="{{ $perm->id }}"
                                           id="perm-{{ $role->id }}-{{ $perm->id }}"
                                           class="form-check-input permission-checkbox"
                                           data-group="{{ $groupName }}"
                                           data-role="{{ $role->id }}"
                                           {{ in_array($perm->id, $rolePermissions[$role->id] ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label small d-block" for="perm-{{ $role->id }}-{{ $perm->id }}">
                                        {{ $perm->display_name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <span class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>Perubahan tidak tersimpan sampai Anda klik tombol Simpan.
                        </span>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Simpan Permission
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-5">
        <i class="bi bi-shield-slash fs-1 d-block mb-2"></i>
        Belum ada role yang dibuat.
    </div>
    @endforelse
</div>

{{-- MODAL TAMBAH ROLE --}}
<div class="modal fade" id="modalTambahRole" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Role Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Role</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: AFET Bandara" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Slug</label>
                        <input type="text" name="slug" class="form-control" placeholder="Contoh: afet_bandara" required>
                        <small class="text-muted">Dipakai sebagai kode internal sistem. Huruf kecil dan garis bawah saja.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Untuk apa role ini digunakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT ROLE --}}
@foreach($roles as $role)
<div class="modal fade" id="modalEditRole{{ $role->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Role: {{ $role->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Role</label>
                        <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ $role->slug }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2">{{ $role->description }}</textarea>
                    </div>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Mengubah nama/slug akan berdampak ke semua {{ $role->users->count() }} pengguna dengan role ini.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection

@push('styles')
<style>
    .accordion-button:not(.collapsed) {
        background-color: #f8f9fc;
        box-shadow: none;
    }
    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0,0,0,.125);
    }
    .permission-item.d-none {
        display: none !important;
    }
    .permission-group.d-none {
        display: none !important;
    }
</style>
@endpush

@push('scripts')
<script>
    // ==========================================
    // Select All / Unselect All per group
    // ==========================================
    document.querySelectorAll('.select-all').forEach(function (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const group = this.dataset.group;
            const role = this.dataset.role;
            const checkboxes = document.querySelectorAll(
                `.permission-checkbox[data-group="${group}"][data-role="${role}"]`
            );
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBadge(role);
        });
    });

    // ==========================================
    // Sinkronisasi Select All + update badge counter
    // ==========================================
    document.querySelectorAll('.permission-checkbox').forEach(function (cb) {
        cb.addEventListener('change', function () {
            const group = this.dataset.group;
            const role = this.dataset.role;
            const checkboxes = document.querySelectorAll(
                `.permission-checkbox[data-group="${group}"][data-role="${role}"]`
            );
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            const selectAll = document.querySelector(
                `.select-all[data-group="${group}"][data-role="${role}"]`
            );
            if (selectAll) selectAll.checked = allChecked;
            updateBadge(role);
        });
    });

    function updateBadge(role) {
        const allCheckboxes = document.querySelectorAll(`.permission-checkbox[data-role="${role}"]`);
        const checkedCount = document.querySelectorAll(`.permission-checkbox[data-role="${role}"]:checked`).length;
        const badge = document.querySelector(`.permission-count-badge[data-role="${role}"]`);
        if (badge) {
            badge.innerHTML = `<i class="bi bi-key-fill me-1"></i>${checkedCount}/${allCheckboxes.length} izin`;
        }
    }

    // ==========================================
    // Search / filter permission dalam satu role
    // ==========================================
    document.querySelectorAll('.permission-search').forEach(function (input) {
        input.addEventListener('input', function () {
            const role = this.dataset.role;
            const keyword = this.value.trim().toLowerCase();
            const form = document.querySelector(`.role-permission-form[data-role="${role}"]`);
            if (!form) return;

            form.querySelectorAll('.permission-group').forEach(function (groupEl) {
                let anyVisible = false;
                groupEl.querySelectorAll('.permission-item').forEach(function (itemEl) {
                    const match = !keyword || itemEl.dataset.label.includes(keyword);
                    itemEl.classList.toggle('d-none', !match);
                    if (match) anyVisible = true;
                });
                groupEl.classList.toggle('d-none', !anyVisible);
            });
        });
    });
</script>
@endpush