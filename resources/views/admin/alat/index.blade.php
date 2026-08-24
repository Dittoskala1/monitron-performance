@extends('layouts.app')

@section('title', 'Data Alat - Monitoring Alat')
@section('page-title', 'Data Alat')

@section('content')

{{-- Alert --}}
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

{{-- Filter --}}
<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">

            @if(!$isLocked)
            <div class="col-md-3">
                <label class="form-label fw-semibold">Bandara</label>
                <select name="id_bandara" class="form-select" id="filterBandara">
                    <option value="">Semua Bandara</option>
                    @foreach($bandara as $b)
                        <option value="{{ $b->id_bandara }}" {{ request('id_bandara') == $b->id_bandara ? 'selected' : '' }}>
                            {{ $b->kode_bandara }} - {{ $b->nama_bandara }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Lokasi</label>
                <select name="id_lokasi" class="form-select" id="filterLokasi">
                    <option value="">Semua Lokasi</option>
                    @foreach($allLokasi as $l)
                        @php
                            $isCurrentBandara = !request('id_bandara') || $l->id_bandara == request('id_bandara');
                        @endphp
                        <option value="{{ $l->id_lokasi }}"
                            data-bandara="{{ $l->id_bandara }}"
                            {{ request('id_lokasi') == $l->id_lokasi ? 'selected' : '' }}
                            {{ !$isCurrentBandara ? 'disabled' : '' }}
                            style="{{ !$isCurrentBandara ? 'color:#ccc' : '' }}">
                            {{ $l->bandara->kode_bandara }} - {{ $l->nama_lokasi }}
                        </option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="col-md-6">
                <label class="form-label fw-semibold">Lokasi</label>
                <select name="id_lokasi" class="form-select">
                    <option value="">Semua Lokasi</option>
                    @foreach($allLokasi as $l)
                        <option value="{{ $l->id_lokasi }}" {{ request('id_lokasi') == $l->id_lokasi ? 'selected' : '' }}>
                            {{ $l->nama_lokasi }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-2">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="Aktif" {{ request('status') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="Tidak" {{ request('status') == 'Tidak' ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
            @if(hasPermission('alat.create'))
            <div class="col-md-2">
                <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Alat
                </button>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- Tabel --}}
<div class="card stat-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Alat</th>
                        <th>Jenis Alat</th>
                        <th>Kode Alat</th>
                        <th>Detail Lokasi</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Bandara</th>
                        <th>Merek</th>
                        <th>Barcode</th>
                        <th>Kondisi Terkini</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alat as $i => $a)
                    <tr>
                        <td>{{ $alat->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $a->nama_alat }}</td>
                        <td>
                            @if($a->jenis_alat)
                                <span class="badge bg-light text-dark border">{{ $a->jenis_alat }}</span>
                            @else
                                <span class="text-muted small fst-italic">Belum diisi</span>
                            @endif
                        </td>
                        <td>
                            @if($a->kode_alat)
                                <code class="small">{{ $a->kode_alat }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($a->detail_lokasi)
                                {{ $a->detail_lokasi }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $a->kategori->nama_kategori ?? '-' }}</td>
                        <td>{{ $a->lokasi->nama_lokasi ?? '-' }}</td>
                        <td>
                            <span class="badge bg-primary">
                                {{ $a->lokasi->bandara->kode_bandara ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $a->merek ?? '-' }}</td>
                        <td>
                            @if($a->barcode)
                                <code class="small">{{ $a->barcode }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $a->warna_kondisi_terkini }}">
                                {{ $a->kondisi_terkini ?? 'Normal' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $a->status == 'Aktif' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $a->status }}
                            </span>
                        </td>
                        <td>
                            {{-- Edit --}}
                            @if(hasPermission('alat.edit'))
                            <button class="btn btn-sm btn-warning me-1"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEdit{{ $a->id_alat }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endif

                            {{-- Ajukan Idle --}}
                            @if(hasPermission('idle.create'))
                                @if(optional($a->lokasi)->nama_lokasi === 'Unused')
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" disabled
                                        title="Alat ini sudah berada di lokasi Unused">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </button>
                                @elseif(in_array($a->id_alat, $idAlatPengajuanPending))
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" disabled
                                        title="Sudah ada pengajuan idle untuk alat ini, sedang menunggu approval">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </button>
                                @else
                                    <a href="{{ route('admin.peralatan-idle.create', ['id_alat' => $a->id_alat]) }}"
                                       class="btn btn-sm btn-outline-warning me-1" title="Ajukan Idle">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                @endif
                            @endif

                            {{-- Download QR --}}
                            @if($a->barcode)
                                <a href="{{ route('admin.alat.qr', $a->id_alat) }}"
                                   class="btn btn-sm btn-info me-1" title="Download QR Code">
                                    <i class="bi bi-qr-code"></i>
                                </a>
                            @endif

                            {{-- Hapus --}}
                            @if(hasPermission('alat.delete'))
                            <form action="{{ route('admin.alat.destroy', $a->id_alat) }}"
                                    method="POST" class="d-inline"
                                    onsubmit="return confirm('Yakin hapus alat ini?')">
                                    @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>

                    {{-- Modal Edit --}}
                    <div class="modal fade" id="modalEdit{{ $a->id_alat }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Alat</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('admin.alat.update', $a->id_alat) }}" method="POST">
                                    @csrf @method('PUT')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Lokasi</label>
                                            <select name="id_lokasi" class="form-select" required>
                                                @foreach($allLokasi as $l)
                                                    <option value="{{ $l->id_lokasi }}"
                                                        {{ $a->id_lokasi == $l->id_lokasi ? 'selected' : '' }}>
                                                        {{ $l->bandara->kode_bandara }} - {{ $l->nama_lokasi }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Kategori</label>
                                            <select name="id_kategori" class="form-select" required>
                                                @foreach($kategori as $k)
                                                    <option value="{{ $k->id_kategori }}"
                                                        {{ $a->id_kategori == $k->id_kategori ? 'selected' : '' }}>
                                                        {{ $k->nama_kategori }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Kode Alat</label>
                                            <input type="text" name="kode_alat" class="form-control"
                                                   value="{{ $a->kode_alat }}"
                                                   placeholder="Contoh: 14.01.01.01.CGK.TL.S1">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Detail Lokasi</label>
                                            <input type="text" name="detail_lokasi" class="form-control"
                                                   value="{{ $a->detail_lokasi }}"
                                                   placeholder="Contoh: Rak 3 Gudang Belakang">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nama Alat</label>
                                            <input type="text" name="nama_alat" class="form-control"
                                                   value="{{ $a->nama_alat }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Jenis Alat</label>
                                            <select name="jenis_alat" class="form-select">
                                                <option value="">- Belum Diisi -</option>
                                                @foreach($jenisAlatOptions as $jenis)
                                                    <option value="{{ $jenis }}"
                                                        {{ strtolower($a->jenis_alat) == strtolower($jenis) ? 'selected' : '' }}>
                                                        {{ $jenis }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Dipakai buat cocokin alat ini ke Unit Kerja yang menanganinya.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Unit Kerja</label>
                                            <input type="text" name="unit_kerja" class="form-control"
                                                   value="{{ $a->unit_kerja }}"
                                                   placeholder="Contoh: ELECTRONIC FACILITY & IT">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Barcode</label>
                                            <input type="text" name="barcode" class="form-control"
                                                   value="{{ $a->barcode }}"
                                                   placeholder="Kosongkan untuk tidak mengubah">
                                            <div class="form-text">Isi manual atau kosongkan untuk tetap pakai barcode lama.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Merek</label>
                                            <input type="text" name="merek" class="form-control"
                                                   value="{{ $a->merek }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="Aktif" {{ $a->status == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                                <option value="Tidak" {{ $a->status == 'Tidak' ? 'selected' : '' }}>Tidak Aktif</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted py-4">Belum ada data alat</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $alat->withQueryString()->links() }}
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Alat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.alat.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lokasi</label>
                        <select name="id_lokasi" class="form-select" required>
                            <option value="">Pilih Lokasi</option>
                            @foreach($allLokasi as $l)
                                <option value="{{ $l->id_lokasi }}">
                                    {{ $l->bandara->kode_bandara }} - {{ $l->nama_lokasi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select name="id_kategori" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            @foreach($kategori as $k)
                                <option value="{{ $k->id_kategori }}">{{ $k->nama_kategori }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Alat</label>
                        <input type="text" name="kode_alat" class="form-control"
                               placeholder="Contoh: 14.01.01.01.CGK.TL.S1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detail Lokasi</label>
                        <input type="text" name="detail_lokasi" class="form-control"
                               placeholder="Contoh: Rak 3 Gudang Belakang">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Alat</label>
                        <input type="text" name="nama_alat" class="form-control"
                               placeholder="Contoh: X-Ray Bagasi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Alat</label>
                        <select name="jenis_alat" class="form-select">
                            <option value="">- Belum Diisi -</option>
                            @foreach($jenisAlatOptions as $jenis)
                                <option value="{{ $jenis }}">{{ $jenis }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Dipakai buat cocokin alat ini ke Unit Kerja yang menanganinya.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unit Kerja</label>
                        <input type="text" name="unit_kerja" class="form-control"
                               placeholder="Contoh: ELECTRONIC FACILITY & IT">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Barcode</label>
                        <input type="text" name="barcode" class="form-control"
                               placeholder="Kosongkan untuk auto-generate">
                        <div class="form-text">Jika dikosongkan, barcode akan otomatis dibuat.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Merek</label>
                        <input type="text" name="merek" class="form-control"
                               placeholder="Contoh: Smiths Detection">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="Aktif">Aktif</option>
                            <option value="Tidak">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const filterBandaraEl = document.getElementById('filterBandara');
    if (filterBandaraEl) {
        filterBandaraEl.addEventListener('change', function() {
            const id_bandara = this.value;
            const lokasiSelect = document.getElementById('filterLokasi');

            lokasiSelect.querySelectorAll('option').forEach(opt => {
                if (!opt.value) return;
                const dataBandara = opt.getAttribute('data-bandara');
                if (!id_bandara) {
                    opt.disabled = false;
                    opt.style.color = '';
                } else {
                    const isMatch = dataBandara == id_bandara;
                    opt.disabled = !isMatch;
                    opt.style.color = !isMatch ? '#ccc' : '';
                }
            });

            lokasiSelect.value = '';
        });
    }
</script>
@endsection