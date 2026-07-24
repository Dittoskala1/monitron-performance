@extends('layouts.app')

@section('title', 'Detail Laporan Perbaikan')
@section('page-title', 'Detail Laporan Perbaikan')

@section('content')

<div class="card stat-card mb-4">
    <div class="card-body p-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h5 class="fw-bold mb-1">{{ $laporan->nama_peralatan }}</h5>
                <span class="text-muted small">
                    <i class="bi bi-geo-alt me-1"></i>
                    {{ $laporan->alat->lokasi->nama_lokasi ?? '-' }} &mdash;
                    {{ $laporan->alat->lokasi->bandara->nama_bandara ?? '-' }}
                </span>
            </div>
            <div class="d-flex gap-2">
                <span class="badge fs-6
                    {{ $laporan->kategori_kerusakan == 'I' ? 'bg-success' :
                       ($laporan->kategori_kerusakan == 'II' ? 'bg-warning text-dark' : 'bg-danger') }}">
                    Kategori {{ $laporan->kategori_kerusakan }}
                </span>
                <span class="badge fs-6 {{ $laporan->status == 'Selesai' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $laporan->status }}
                </span>
            </div>
        </div>

        <hr class="mb-4">

        <div class="row g-4">

            {{-- Kolom Kiri: Informasi Alat --}}
            <div class="col-md-6">
                <p class="text-muted small fw-semibold text-uppercase mb-3">Informasi Alat</p>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Nama Alat</dt>
                    <dd class="col-7 fw-semibold">{{ $laporan->alat->nama_alat ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Lokasi</dt>
                    <dd class="col-7">{{ $laporan->alat->lokasi->nama_lokasi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Bandara</dt>
                    <dd class="col-7">
                        <span class="badge bg-primary">
                            {{ $laporan->alat->lokasi->bandara->kode_bandara ?? '-' }}
                            - {{ $laporan->alat->lokasi->bandara->nama_bandara ?? '-' }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Detail Lokasi</dt>
                    <dd class="col-7">{{ $laporan->detail_lokasi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Dilaporkan Oleh</dt>
                    <dd class="col-7">{{ $laporan->pengguna->nama ?? '-' }}</dd>
                </dl>
            </div>

            {{-- Kolom Kanan: Detail Kerusakan --}}
            <div class="col-md-6">
                <p class="text-muted small fw-semibold text-uppercase mb-3">Detail Kerusakan</p>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Bagian Rusak</dt>
                    <dd class="col-7 fw-semibold">{{ $laporan->bagian_kerusakan ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Tindakan</dt>
                    <dd class="col-7">{{ ($laporan->tindakan && $laporan->tindakan != '-') ? $laporan->tindakan : '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Tgl Kerusakan</dt>
                    <dd class="col-7">
                        {{ \Carbon\Carbon::parse($laporan->tanggal_kerusakan)->format('d F Y H:i') }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Tgl Selesai</dt>
                    <dd class="col-7">
                        {{ $laporan->tanggal_selesai
                            ? \Carbon\Carbon::parse($laporan->tanggal_selesai)->format('d F Y H:i')
                            : '-' }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Durasi Terputus</dt>
                    <dd class="col-7">
                        @php
                            $start = \Carbon\Carbon::parse($laporan->tanggal_kerusakan);
                            $end   = $laporan->tanggal_selesai
                                        ? \Carbon\Carbon::parse($laporan->tanggal_selesai)
                                        : \Carbon\Carbon::now();
                            $diff  = $start->diffInMinutes($end);
                            $jam   = floor($diff / 60);
                            $menit = $diff % 60;
                        @endphp
                        @if($laporan->status == 'Proses')
                            <span class="text-danger fw-semibold" id="durasi-timer">
                                {{ $jam }} jam {{ $menit }} menit
                            </span>
                            <span class="badge bg-danger ms-1 small">Live</span>
                        @else
                            <span class="fw-semibold">{{ $jam }} jam {{ $menit }} menit</span>
                        @endif
                    </dd>

                    @if($laporan->keterangan)
                    <dt class="col-5 text-muted fw-normal">Keterangan</dt>
                    <dd class="col-7">{{ $laporan->keterangan }}</dd>
                    @endif
                </dl>
            </div>

        </div>

    </div>
</div>

<div class="mt-3">
    <a href="{{ route('admin.laporan-perbaikan.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i> Kembali
    </a>
</div>

@endsection

@section('scripts')
@if($laporan->status == 'Proses')
<script>
    const start = new Date("{{ $laporan->tanggal_kerusakan }}");
    function updateTimer() {
        const now   = new Date();
        const diff  = Math.floor((now - start) / 60000);
        const jam   = Math.floor(diff / 60);
        const menit = diff % 60;
        document.getElementById('durasi-timer').textContent = jam + ' jam ' + menit + ' menit';
    }
    setInterval(updateTimer, 60000);
</script>
@endif
@endsection