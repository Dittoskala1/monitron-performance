@extends('layouts.app')

@section('title', 'Detail Alat Idle - Booking')
@section('page-title', 'Detail Alat Idle')

@section('content')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card stat-card mb-4">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h5 class="fw-bold mb-1">{{ $pengajuanIdle->alat->nama_alat ?? '-' }}</h5>
                <span class="text-muted small">
                    <i class="bi bi-hash me-1"></i>
                    Kode Alat: {{ $pengajuanIdle->alat->kode_alat ?? '-' }}
                </span>
                @if($pengajuanIdle->nomor_aset)
                <br>
                <span class="text-muted small">
                    <i class="bi bi-upc-scan me-1"></i>
                    Nomor Aset: {{ $pengajuanIdle->nomor_aset }}
                </span>
                @endif
                <br>
                <span class="text-muted small">
                    <i class="bi bi-building me-1"></i>
                    <strong>Bandara Pemilik:</strong> {{ $pengajuanIdle->alat->bandara->nama_bandara ?? '-' }}
                    ({{ $pengajuanIdle->alat->bandara->kode_bandara ?? '-' }})
                </span>
                <br>
                <span class="text-muted small">
                    <i class="bi bi-geo-alt me-1"></i>
                    <strong>Lokasi Saat Ini:</strong> {{ $pengajuanIdle->alat->lokasi->nama_lokasi ?? '-' }} (Unused)
                </span>
                @if($pengajuanIdle->tanggal_terbit_alat)
                <br>
                <span class="text-muted small">
                    <i class="bi bi-calendar-event me-1"></i>
                    Tanggal Terbit: {{ \Carbon\Carbon::parse($pengajuanIdle->tanggal_terbit_alat)->format('d F Y') }}
                </span>
                @endif
            </div>
            <div class="text-end">
                <span class="badge fs-6 bg-success">Available</span>
            </div>
        </div>

        <hr class="mb-4">

        <div class="row g-4">
            <div class="col-md-6">
                <p class="text-muted small fw-semibold text-uppercase mb-3">Informasi Alat</p>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Kondisi Alat</dt>
                    <dd class="col-7">
                        <span class="badge {{ $pengajuanIdle->kondisi_alat == 'Baik' ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $pengajuanIdle->kondisi_alat }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Penjelasan Kondisi</dt>
                    <dd class="col-7">{{ $pengajuanIdle->penjelasan_kondisi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Detail Lokasi</dt>
                    <dd class="col-7">{{ $pengajuanIdle->detail_lokasi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Lokasi Asal</dt>
                    <dd class="col-7">{{ $pengajuanIdle->lokasiAsal->nama_lokasi ?? '-' }}</dd>
                </dl>
            </div>

            <div class="col-md-6">
                <p class="text-muted small fw-semibold text-uppercase mb-3">Riwayat Idle</p>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Alasan Idle</dt>
                    <dd class="col-7">{{ $pengajuanIdle->alasan_idle ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Tanggal Disetujui</dt>
                    <dd class="col-7">
                        {{ $pengajuanIdle->tanggal_keputusan ? \Carbon\Carbon::parse($pengajuanIdle->tanggal_keputusan)->format('d F Y H:i') : '-' }}
                    </dd>
                </dl>
            </div>
        </div>

        @if($pengajuanIdle->dokumen->isNotEmpty())
        <hr class="my-4">
        <p class="text-muted small fw-semibold text-uppercase mb-3">Dokumen Pendukung</p>
        <div class="row g-2 mb-2">
            @foreach($pengajuanIdle->dokumen as $dok)
            <div class="col-md-4">
                <div class="d-flex align-items-center justify-content-between border rounded p-2">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        @php
                            $iconClass = match(strtolower($dok->tipe_file)) {
                                'pdf'           => 'bi-file-earmark-pdf text-danger',
                                'jpg', 'jpeg',
                                'png'           => 'bi-file-earmark-image text-success',
                                'doc', 'docx'   => 'bi-file-earmark-word text-primary',
                                'xls', 'xlsx'   => 'bi-file-earmark-excel text-success',
                                default         => 'bi-file-earmark text-secondary',
                            };
                        @endphp
                        <i class="bi {{ $iconClass }} fs-5"></i>
                        <span class="small text-truncate" title="{{ $dok->nama_file }}">{{ $dok->nama_file }}</span>
                    </div>
                    <a href="{{ asset('storage/' . $dok->path_file) }}" target="_blank"
                       class="btn btn-sm btn-outline-primary" title="Lihat / Unduh">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <hr class="my-4">

        <form action="{{ route('admin.peralatan-booking.store') }}" method="POST"
              onsubmit="return confirm('Booking alat {{ $pengajuanIdle->alat->nama_alat }}?')">
            @csrf
            <input type="hidden" name="id_pengajuan_idle" value="{{ $pengajuanIdle->id_pengajuan }}">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-bookmark-plus me-1"></i> Booking Alat Ini
            </button>
        </form>
    </div>
</div>

<div class="d-flex gap-2">
    <a href="{{ route('admin.peralatan-booking.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i> Kembali
    </a>
</div>

@endsection