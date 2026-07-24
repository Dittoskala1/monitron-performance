@extends('layouts.app')

@section('title', 'Peralatan Booking - Monitoring Alat')
@section('page-title', 'Booking Alat Idle')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Booking Saya (Aktif) ── --}}
@if($bookingSaya->isNotEmpty())
<div class="card stat-card mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-bookmark-check me-1"></i> Booking Saya (Aktif)</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Alat</th>
                        <th>Bandara Pemilik</th>
                        <th>Tanggal Booking</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookingSaya as $b)
                    <tr>
                        <td class="fw-semibold">{{ $b->nama_alat_snapshot ?? optional($b->pengajuanIdle->alat)->nama_alat }}</td>
                        <td>{{ optional($b->pengajuanIdle->alat->bandara)->kode_bandara ?? '-' }}</td>
                        <td>{{ $b->tanggal_booking->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.peralatan-mutasi.create', $b->id_booking) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-arrow-right-circle"></i> Lanjut Mutasi
                                </a>
                                <form action="{{ route('admin.peralatan-booking.cancel', $b->id_booking) }}" method="POST"
                                      onsubmit="return confirm('Batalkan booking ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-x-circle"></i> Batalkan
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ── Daftar Alat Idle (Semua Bandara) ── --}}
<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Bandara</label>
                <select name="id_bandara" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Bandara</option>
                    @foreach($bandaraList as $b)
                        <option value="{{ $b->id_bandara }}" {{ request('id_bandara') == $b->id_bandara ? 'selected' : '' }}>
                            {{ $b->nama_bandara }} ({{ $b->kode_bandara }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card stat-card">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-1"></i> Alat Idle Tersedia (Semua Bandara)</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Alat</th>
                        <th>Kode Alat</th>
                        <th>Bandara Pemilik</th>
                        <th>Kondisi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alatIdle as $i => $p)
                    <tr>
                        <td>{{ $alatIdle->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $p->alat->nama_alat ?? '-' }}</td>
                        <td>{{ $p->alat->kode_alat ?? '-' }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $p->alat->bandara->kode_bandara ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $p->kondisi_alat == 'Baik' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $p->kondisi_alat }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.peralatan-booking.show', $p->id_pengajuan) }}"
                                   class="btn btn-sm btn-info text-white" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form action="{{ route('admin.peralatan-booking.store') }}" method="POST"
                                      onsubmit="return confirm('Booking alat {{ $p->alat->nama_alat }}?')">
                                    @csrf
                                    <input type="hidden" name="id_pengajuan_idle" value="{{ $p->id_pengajuan }}">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-bookmark-plus"></i> Booking
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tidak ada alat idle yang tersedia untuk di-booking saat ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $alatIdle->links() }}
    </div>
</div>

@endsection