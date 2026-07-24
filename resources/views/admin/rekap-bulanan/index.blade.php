@extends('layouts.app')

@section('title', 'Rekap Bulanan - Monitoring Alat')
@section('page-title', 'Rekap Bulanan')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $roleSaya = session('pengguna.role');
@endphp

{{-- Filter & Generate --}}
<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">

            @if($roleSaya === 'afet_regional')
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

            <div class="col-md-2">
                <label class="form-label fw-semibold">Bulan</label>
                <select name="bulan" class="form-select">
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromFormat('m', $m)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Tahun</label>
                <select name="tahun" class="form-select">
                    @foreach(range(date('Y'), 2023) as $y)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search me-1"></i> Tampilkan
                </button>
                <button type="button" class="btn btn-warning flex-fill"
                    onclick="if(confirm('Generate rekap {{ $bulan }}/{{ $tahun }}?')) {
                        document.getElementById('form-generate').submit();
                    }">
                    <i class="bi bi-arrow-repeat me-1"></i> Generate
                </button>
                <a href="{{ route('admin.rekap-bulanan.export') }}?bulan={{ $bulan }}&tahun={{ $tahun }}&id_bandara={{ request('id_bandara') }}"
                    class="btn btn-success flex-fill">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>
            </div>
        </form>

        <form id="form-generate" action="{{ route('admin.rekap-bulanan.generate') }}" method="POST" class="d-none">
            @csrf
            <input type="hidden" name="bulan" value="{{ $bulan }}">
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <input type="hidden" name="id_bandara" value="{{ request('id_bandara') }}">
        </form>
    </div>
</div>

{{-- Tabel --}}
<div class="card stat-card">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">
            Rekap Bulan {{ \Carbon\Carbon::createFromFormat('m', $bulan)->format('F') }} {{ $tahun }}
        </h6>

        @if($rekap->isEmpty())
            <div class="text-center text-muted py-4">
                Belum ada rekap. Klik "Generate" untuk membuat rekap bulanan.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Peralatan</th>
                        <th>Lokasi</th>
                        <th>Detail Lokasi</th>
                        <th>Merek</th>
                        <th>Bandara</th>
                        <th>Total Jam Terputus</th>
                        <th>Total Jam Ops</th>
                        <th>Availability</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @foreach($rekap as $namaLokasi => $kategoriGroup)

                    {{-- Header Lokasi --}}
                    <tr style="background-color:#1e3a5f;">
                        <td colspan="10" class="fw-bold text-white ps-2">
                            <i class="bi bi-geo-alt-fill me-2"></i>{{ $namaLokasi }}
                        </td>
                    </tr>

                    @foreach($kategoriGroup as $namaKategori => $alatGroup)

                    {{-- Header Kategori --}}
                    <tr style="background-color:#e2e8f0;">
                        <td colspan="10" class="fw-bold text-uppercase ps-3">
                            {{ $loop->iteration }}. {{ $namaKategori }}
                        </td>
                    </tr>

                    @foreach($alatGroup as $namaAlat => $items)

                    {{-- Sub Header Nama Alat --}}
                    <tr style="background-color:#f1f5f9;">
                        <td colspan="10" class="fw-semibold ps-4 text-primary">
                            <i class="bi bi-chevron-right me-1"></i>{{ $namaAlat }}
                        </td>
                    </tr>

                    @foreach($items as $r)
                    @php
                        $jamOps       = $r->total_jam_operasional;
                        $jamTerputus  = $r->total_jam_terputus;
                        $availability = $jamOps > 0
                            ? round((($jamOps - $jamTerputus) / $jamOps) * 100, 2)
                            : $r->rata_performa;
                    @endphp
                    <tr>
                        <td class="ps-4">{{ $no++ }}</td>
                        <td class="fw-semibold">{{ $r->alat->nama_alat ?? '-' }}</td>
                        <td>{{ $r->alat->lokasi->nama_lokasi ?? '-' }}</td>
                        <td>{{ $r->detail_lokasi ?? '-' }}</td>
                        <td>{{ $r->alat->merek ?? '-' }}</td>
                        <td>
                            <span class="badge bg-primary">
                                {{ $r->alat->lokasi->bandara->kode_bandara ?? '-' }}
                            </span>
                        </td>
                        <td>{{ jamMenit($jamTerputus) }}</td>
                        <td>{{ jamMenit($jamOps) }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px; min-width:80px">
                                    <div class="progress-bar
                                        {{ $r->status == 'Baik' ? 'bg-success' : ($r->status == 'Warning' ? 'bg-warning' : 'bg-danger') }}"
                                        style="width:{{ min($availability, 100) }}%">
                                    </div>
                                </div>
                                <small class="fw-semibold">{{ $availability }}%</small>
                            </div>
                        </td>
                        <td>
                            <span class="badge
                                {{ $r->status == 'Baik' ? 'bg-success' : ($r->status == 'Warning' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $r->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                    @endforeach
                    @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection