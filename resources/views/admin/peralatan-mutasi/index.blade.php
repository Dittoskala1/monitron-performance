@extends('layouts.app')

@section('title', 'Peralatan Mutasi - Monitoring Alat')
@section('page-title', 'Peralatan Mutasi')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card stat-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="Waiting Approval CEO" {{ request('status') == 'Waiting Approval CEO' ? 'selected' : '' }}>Waiting Approval CEO</option>
                    <option value="Waiting Approval GM Pemberi" {{ request('status') == 'Waiting Approval GM Pemberi' ? 'selected' : '' }}>Waiting Approval GM Pemberi</option>
                    <option value="Waiting Konfirmasi CEO" {{ request('status') == 'Waiting Konfirmasi CEO' ? 'selected' : '' }}>Waiting Konfirmasi CEO</option>
                    <option value="Menunggu Pemastian Fasilitas Idle" {{ request('status') == 'Menunggu Pemastian Fasilitas Idle' ? 'selected' : '' }}>Menunggu Pemastian Fasilitas Idle</option>
                    <option value="Siap Mobilisasi" {{ request('status') == 'Siap Mobilisasi' ? 'selected' : '' }}>Siap Mobilisasi</option>
                    <option value="Menunggu Verifikasi Mobilisasi" {{ request('status') == 'Menunggu Verifikasi Mobilisasi' ? 'selected' : '' }}>Menunggu Verifikasi Mobilisasi</option>
                    <option value="Menunggu Sertifikasi" {{ request('status') == 'Menunggu Sertifikasi' ? 'selected' : '' }}>Menunggu Sertifikasi</option>
                    <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card stat-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Alat</th>
                        <th>Bandara Pemberi</th>
                        <th>Bandara Penerima</th>
                        <th>Pemohon</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutasi as $i => $m)
                    <tr>
                        <td>{{ $mutasi->firstItem() + $i }}</td>
                        <td class="fw-semibold">
                            {{ $m->alat->nama_alat ?? '-' }}
                            <br>
                            <small class="text-muted">{{ $m->alat->kode_alat ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                {{ $m->bandaraPemberi->kode_bandara ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">
                                {{ $m->bandaraPenerima->kode_bandara ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $m->pemohon->nama ?? '-' }}</td>
                        <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @php
                                $statusBadge = match($m->status) {
                                    'Selesai' => 'bg-success',
                                    'Siap Mobilisasi', 'Menunggu Sertifikasi' => 'bg-primary',
                                    'Waiting Konfirmasi CEO', 'Menunggu Verifikasi Mobilisasi' => 'bg-info text-dark',
                                    'Menunggu Pemastian Fasilitas Idle' => 'bg-secondary',
                                    default => 'bg-warning text-dark', // Waiting Approval CEO / GM Pemberi
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">
                                {{ $m->status }}
                            </span>
                            @if($m->status === 'Waiting Approval CEO' && $m->alasan_reject_ceo)
                                <br>
                                <span class="badge bg-danger mt-1">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Perlu Revisi
                                </span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.peralatan-mutasi.show', $m->id_pengajuan_mutasi) }}"
                               class="btn btn-sm btn-info text-white">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada pengajuan mutasi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $mutasi->withQueryString()->links() }}
    </div>
</div>

@endsection