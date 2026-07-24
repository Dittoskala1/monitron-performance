@extends('layouts.app')

@section('title', 'Detail Log Harian')
@section('page-title', 'Detail Log Harian')

@section('content')

<div class="card stat-card mb-4">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h5 class="fw-bold mb-1">{{ $log->alat->nama_alat ?? '-' }}</h5>
                <span class="text-muted small">
                    <i class="bi bi-geo-alt me-1"></i>
                    {{ $log->alat->lokasi->bandara->nama_bandara ?? '-' }} —
                    {{ $log->alat->lokasi->nama_lokasi ?? '-' }}
                </span>
            </div>
            @php
                $kondisi = $log->kondisi;
                $badgeKondisi = match($kondisi) {
                    'Normal'    => 'bg-success',
                    'Gangguan'  => 'bg-warning text-dark',
                    'Rusak'     => 'bg-danger',
                    default     => 'bg-secondary',
                };
            @endphp
            <span class="badge fs-6 {{ $badgeKondisi }}">{{ $kondisi }}</span>
        </div>

        <hr class="mb-4">

        <div class="row g-4">
            <div class="col-md-6">
                <p class="text-muted small fw-semibold text-uppercase mb-3">Informasi Alat</p>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Kode Alat</dt>
                    <dd class="col-7">{{ $log->alat->kode_alat ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Kategori</dt>
                    <dd class="col-7">{{ $log->alat->kategori->nama_kategori ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Lokasi</dt>
                    <dd class="col-7">{{ $log->alat->lokasi->nama_lokasi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Detail Lokasi</dt>
                    <dd class="col-7">{{ $log->detail_lokasi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Bandara</dt>
                    <dd class="col-7">
                        <span class="badge bg-primary">
                            {{ $log->alat->lokasi->bandara->kode_bandara ?? '-' }}
                        </span>
                    </dd>
                </dl>
            </div>

            <div class="col-md-6">
                <p class="text-muted small fw-semibold text-uppercase mb-3">Data Log</p>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Tanggal</dt>
                    <dd class="col-7">{{ \Carbon\Carbon::parse($log->tanggal)->format('d F Y') }}</dd>

                    <dt class="col-5 text-muted fw-normal">Performa</dt>
                    <dd class="col-7">
                        @php
                            $performa = round($log->performa, 2);
                            $perfBadge = $performa >= 90 ? 'bg-success' : ($performa >= 70 ? 'bg-warning text-dark' : 'bg-danger');
                        @endphp
                        <span class="badge {{ $perfBadge }}">{{ $performa }}%</span>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Jam Operasional</dt>
                    <dd class="col-7">{{ $log->jam_operasional ?? '-' }} jam</dd>

                    <dt class="col-5 text-muted fw-normal">Jam Terputus</dt>
                    <dd class="col-7">{{ $log->jam_terputus ?? '-' }} jam</dd>

                    <dt class="col-5 text-muted fw-normal">Diinput Oleh</dt>
                    <dd class="col-7">{{ $log->pengguna->nama ?? '-' }}</dd>
                </dl>
            </div>
        </div>

        @if($log->catatan)
        <hr class="my-4">
        <p class="text-muted small fw-semibold text-uppercase mb-2">Catatan</p>
        <p class="mb-0">{{ $log->catatan }}</p>
        @endif

    </div>
</div>

<a href="{{ route('admin.data-harian.table') }}" class="btn btn-secondary">
    <i class="bi bi-arrow-left me-2"></i> Kembali
</a>

@endsection