@extends('layouts.app')

@section('title', 'Data Harian - Monitoring Alat')
@section('page-title', 'Data Harian')

@section('content')

{{-- Toggle ke tampilan kalender --}}
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.data-harian.index') }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-calendar3 me-1"></i> Tampilan Kalender
    </a>
</div>

{{-- Filter --}}
<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.data-harian.table') }}" class="row g-3 align-items-end">

            @if(!$isLocked)
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
            @endif

            <div class="col-md-3">
                <label class="form-label fw-semibold">Alat</label>
                <select name="id_alat" class="form-select">
                    <option value="">Semua Alat</option>
                    @foreach($alat as $a)
                        <option value="{{ $a->id_alat }}" {{ request('id_alat') == $a->id_alat ? 'selected' : '' }}>
                            {{ $a->nama_alat }} - {{ $a->lokasi->bandara->kode_bandara ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ request('tanggal') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Kondisi</label>
                <select name="kondisi" class="form-select">
                    <option value="">Semua</option>
                    <option value="Normal"   {{ request('kondisi') == 'Normal'   ? 'selected' : '' }}>Normal</option>
                    <option value="Gangguan" {{ request('kondisi') == 'Gangguan' ? 'selected' : '' }}>Gangguan</option>
                    <option value="Rusak"    {{ request('kondisi') == 'Rusak'    ? 'selected' : '' }}>Rusak</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
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
                        <th>Tanggal</th>
                        <th>Alat</th>
                        <th>Lokasi</th>
                        <th>Detail Lokasi</th>
                        <th>Bandara</th>
                        <th>Jam Terputus</th>
                        <th>Kondisi</th>
                        <th>Teknisi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grouped = $logs->getCollection()->groupBy(fn($log) => $log->alat->nama_alat ?? 'Unknown');
                        $no = $logs->firstItem();
                    @endphp

                    @forelse($grouped as $namaAlat => $items)
                    {{-- Sub Header Nama Alat --}}
                    <tr style="background-color:#f1f5f9;">
                        <td colspan="10" class="fw-semibold ps-3 text-primary">
                            <i class="bi bi-chevron-right me-1"></i>{{ $namaAlat }}
                        </td>
                    </tr>

                    @foreach($items as $log)
                    <tr>
                        <td class="ps-3">{{ $no++ }}</td>
                        <td>{{ \Carbon\Carbon::parse($log->tanggal)->format('d/m/Y') }}</td>
                        <td class="fw-semibold">{{ $log->alat->nama_alat ?? '-' }}</td>
                        <td>{{ $log->alat->lokasi->nama_lokasi ?? '-' }}</td>
                        <td>{{ $log->detail_lokasi ?? '-' }}</td>
                        <td>
                            <span class="badge bg-primary">
                                {{ $log->alat->lokasi->bandara->kode_bandara ?? '-' }}
                            </span>
                        </td>
                        <td>
                            @if($log->kondisi == 'Normal')
                                <span class="text-muted">-</span>
                            @else
                                {{ jamMenit($log->jam_terputus) }}
                            @endif
                        </td>
                        <td>
                            <span class="badge
                                {{ $log->kondisi == 'Normal' ? 'bg-success' : ($log->kondisi == 'Gangguan' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $log->kondisi }}
                            </span>
                        </td>
                        <td>{{ $log->pengguna->nama ?? '-' }}</td>
                        <td>
                            <a href="{{ route('admin.data-harian.show', $log->id_log) }}"
                               class="btn btn-sm btn-info text-white">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach

                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">Belum ada data harian</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->withQueryString()->links() }}
    </div>
</div>

@endsection