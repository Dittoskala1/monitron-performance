@extends('layouts.app')

@section('title', 'Laporan Perbaikan - Monitoring Alat')
@section('page-title', 'Laporan Perbaikan')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $roleSaya = session('pengguna.role');

    // Query string filter yang sedang aktif, dipakai juga untuk link export
    $queryExport = http_build_query([
        'id_bandara'         => request('id_bandara'),
        'status'             => request('status'),
        'kategori_kerusakan' => request('kategori_kerusakan'),
        'tanggal_dari'       => request('tanggal_dari'),
        'tanggal_sampai'     => request('tanggal_sampai'),
    ]);
@endphp

{{-- Filter --}}
<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">

            @if(!$isLocked)
            <div class="col-md-2">
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
            @endif

            <div class="col-md-2">
                <label class="form-label fw-semibold">Kategori</label>
                <select name="kategori_kerusakan" class="form-select">
                    <option value="">Semua</option>
                    <option value="I"   {{ request('kategori_kerusakan') == 'I'   ? 'selected' : '' }}>Kategori I</option>
                    <option value="II"  {{ request('kategori_kerusakan') == 'II'  ? 'selected' : '' }}>Kategori II</option>
                    <option value="III" {{ request('kategori_kerusakan') == 'III' ? 'selected' : '' }}>Kategori III</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="Proses"  {{ request('status') == 'Proses'  ? 'selected' : '' }}>Proses</option>
                    <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Tanggal Dari</label>
                <input type="date" name="tanggal_dari" class="form-control" value="{{ request('tanggal_dari') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Tanggal Sampai</label>
                <input type="date" name="tanggal_sampai" class="form-control" value="{{ request('tanggal_sampai') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
        </form>

        <hr>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.laporan-perbaikan.export-excel') }}?{{ $queryExport }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </a>
            <a href="{{ route('admin.laporan-perbaikan.export-pdf') }}?{{ $queryExport }}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </a>
        </div>
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
                        <th>Nama Peralatan</th>
                        <th>Bandara</th>
                        <th>Lokasi</th>
                        <th>Kategori</th>
                        <th>Bagian Rusak</th>
                        <th>Tgl Kerusakan</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laporan as $i => $l)
                    @php
                        $start = \Carbon\Carbon::parse($l->tanggal_kerusakan);
                        $end   = $l->tanggal_selesai
                                    ? \Carbon\Carbon::parse($l->tanggal_selesai)
                                    : \Carbon\Carbon::now();
                        $diff  = $start->diffInMinutes($end);
                        $jam   = floor($diff / 60);
                        $menit = $diff % 60;
                    @endphp
                    <tr>
                        <td>{{ $laporan->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $l->nama_peralatan }}</td>
                        <td>
                            <span class="badge bg-primary">
                                {{ $l->alat->lokasi->bandara->kode_bandara ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $l->alat->lokasi->nama_lokasi ?? '-' }}</td>
                        <td>
                            <span class="badge
                                {{ $l->kategori_kerusakan == 'I' ? 'bg-success' :
                                   ($l->kategori_kerusakan == 'II' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                Kat. {{ $l->kategori_kerusakan }}
                            </span>
                        </td>
                        <td>{{ $l->bagian_kerusakan }}</td>
                        <td>{{ \Carbon\Carbon::parse($l->tanggal_kerusakan)->format('d/m/Y H:i') }}</td>
                        <td>
                            {{ $jam }} jam {{ $menit }} menit
                            @if($l->status == 'Proses')
                                <span class="badge bg-danger ms-1 small">Live</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $l->status == 'Selesai' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $l->status }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.laporan-perbaikan.show', $l->id_laporan) }}"
                               class="btn btn-sm btn-info text-white">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">Belum ada laporan perbaikan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $laporan->withQueryString()->links() }}
    </div>
</div>

@endsection