@extends('layouts.app')

@section('title', 'Pengaturan - Monitoring Alat')
@section('page-title', 'Pengaturan')

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

<div class="row g-4">

    {{-- Threshold --}}
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-sliders me-2 text-primary"></i>Threshold Performa
                </h6>
                <form action="{{ route('admin.pengaturan.threshold') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-success">Nilai Baik (%)</label>
                        <input type="number" name="nilai_baik" class="form-control"
                               value="{{ $threshold->nilai_baik }}" step="0.01" required>
                        <small class="text-muted">Performa ≥ nilai ini = Baik</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-warning">Nilai Warning (%)</label>
                        <input type="number" name="nilai_warning" class="form-control"
                               value="{{ $threshold->nilai_warning }}" step="0.01" required>
                        <small class="text-muted">Performa ≥ nilai ini = Warning</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-danger">Nilai Buruk (%)</label>
                        <input type="number" name="nilai_buruk" class="form-control"
                               value="{{ $threshold->nilai_buruk }}" step="0.01" required>
                        <small class="text-muted">Performa < nilai warning = Buruk</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control"
                               value="{{ $threshold->keterangan }}">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-1"></i> Simpan Threshold
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Bandara --}}
    <div class="col-md-8">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-airplane me-2 text-primary"></i>Data Bandara
                    </h6>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahBandara">
                        <i class="bi bi-plus-circle me-1"></i> Tambah
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Bandara</th>
                                <th>Lokasi</th>
                                <th>Jam Operasional</th>
                                <th>Terminal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bandara as $b)
                            <tr>
                                <td><span class="badge bg-primary">{{ $b->kode_bandara }}</span></td>
                                <td class="fw-semibold">{{ $b->nama_bandara }}</td>
                                <td>{{ $b->lokasi }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $b->jam_operasional }} jam</span>
                                </td>
                                <td>{{ $b->lokasi_count }} terminal</td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditBandara{{ $b->id_bandara }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.pengaturan.bandara.delete', $b->id_bandara) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Yakin hapus bandara ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Modal Edit Bandara --}}
                            <div class="modal fade" id="modalEditBandara{{ $b->id_bandara }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Bandara</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.pengaturan.bandara.update', $b->id_bandara) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Nama Bandara</label>
                                                    <input type="text" name="nama_bandara" class="form-control"
                                                           value="{{ $b->nama_bandara }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Kode Bandara</label>
                                                    <input type="text" name="kode_bandara" class="form-control"
                                                           value="{{ $b->kode_bandara }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Lokasi</label>
                                                    <input type="text" name="lokasi" class="form-control"
                                                           value="{{ $b->lokasi }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Jam Operasional</label>
                                                    <div class="input-group">
                                                        <input type="number" name="jam_operasional" class="form-control"
                                                               value="{{ $b->jam_operasional }}"
                                                               min="1" max="24" step="0.5" required>
                                                        <span class="input-group-text">jam</span>
                                                    </div>
                                                    <small class="text-muted">Contoh: CGK = 24 jam, KJT = 12 jam</small>
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Lokasi --}}
        <div class="card stat-card mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-geo-alt me-2 text-primary"></i>Data Lokasi/Terminal
                    </h6>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahLokasi">
                        <i class="bi bi-plus-circle me-1"></i> Tambah
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Bandara</th>
                                <th>Nama Lokasi</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lokasi as $l)
                            <tr>
                                <td><span class="badge bg-primary">{{ $l->bandara->kode_bandara ?? '-' }}</span></td>
                                <td class="fw-semibold">{{ $l->nama_lokasi }}</td>
                                <td>{{ $l->keterangan ?? '-' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditLokasi{{ $l->id_lokasi }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.pengaturan.lokasi.delete', $l->id_lokasi) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Yakin hapus lokasi ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Modal Edit Lokasi --}}
                            <div class="modal fade" id="modalEditLokasi{{ $l->id_lokasi }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Lokasi</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.pengaturan.lokasi.update', $l->id_lokasi) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Bandara</label>
                                                    <select name="id_bandara" class="form-select" required>
                                                        @foreach($bandara as $b)
                                                            <option value="{{ $b->id_bandara }}"
                                                                {{ $l->id_bandara == $b->id_bandara ? 'selected' : '' }}>
                                                                {{ $b->kode_bandara }} - {{ $b->nama_bandara }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Nama Lokasi</label>
                                                    <input type="text" name="nama_lokasi" class="form-control"
                                                           value="{{ $l->nama_lokasi }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Keterangan</label>
                                                    <input type="text" name="keterangan" class="form-control"
                                                           value="{{ $l->keterangan }}">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Kategori --}}
        <div class="card stat-card mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-tags me-2 text-primary"></i>Kategori Alat
                    </h6>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                        <i class="bi bi-plus-circle me-1"></i> Tambah
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th>Jumlah Alat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kategori as $k)
                            <tr>
                                <td class="fw-semibold">{{ $k->nama_kategori }}</td>
                                <td>{{ $k->deskripsi ?? '-' }}</td>
                                <td><span class="badge bg-info">{{ $k->alat_count }} alat</span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditKategori{{ $k->id_kategori }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.pengaturan.kategori.delete', $k->id_kategori) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Yakin hapus kategori ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Modal Edit Kategori --}}
                            <div class="modal fade" id="modalEditKategori{{ $k->id_kategori }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Kategori</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.pengaturan.kategori.update', $k->id_kategori) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Nama Kategori</label>
                                                    <input type="text" name="nama_kategori" class="form-control"
                                                           value="{{ $k->nama_kategori }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Deskripsi</label>
                                                    <textarea name="deskripsi" class="form-control" rows="3">{{ $k->deskripsi }}</textarea>
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah Bandara --}}
<div class="modal fade" id="modalTambahBandara" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Bandara</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.pengaturan.bandara.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Bandara</label>
                        <input type="text" name="nama_bandara" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Bandara</label>
                        <input type="text" name="kode_bandara" class="form-control"
                               placeholder="Contoh: CGK" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control"
                               placeholder="Contoh: Tangerang">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jam Operasional</label>
                        <div class="input-group">
                            <input type="number" name="jam_operasional" class="form-control"
                                   value="24" min="1" max="24" step="0.5" required>
                            <span class="input-group-text">jam</span>
                        </div>
                        <small class="text-muted">Contoh: CGK = 24 jam, KJT = 12 jam</small>
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

{{-- Modal Tambah Lokasi --}}
<div class="modal fade" id="modalTambahLokasi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Lokasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.pengaturan.lokasi.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bandara</label>
                        <select name="id_bandara" class="form-select" required>
                            <option value="">Pilih Bandara</option>
                            @foreach($bandara as $b)
                                <option value="{{ $b->id_bandara }}">
                                    {{ $b->kode_bandara }} - {{ $b->nama_bandara }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lokasi</label>
                        <input type="text" name="nama_lokasi" class="form-control"
                               placeholder="Contoh: Terminal 1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control"
                               placeholder="Opsional">
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

{{-- Modal Tambah Kategori --}}
<div class="modal fade" id="modalTambahKategori" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.pengaturan.kategori.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kategori</label>
                        <input type="text" name="nama_kategori" class="form-control"
                               placeholder="Contoh: X-Ray" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"
                                  placeholder="Deskripsi kategori"></textarea>
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