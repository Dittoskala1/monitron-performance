@extends('layouts.app')

@section('title', 'Peralatan Idle - Monitoring Alat')
@section('page-title', 'Peralatan Idle')

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
            <div class="col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="Waiting Approval Dep Head" {{ request('status') == 'Waiting Approval Dep Head' ? 'selected' : '' }}>Menunggu Dep Head</option>
                    <option value="Waiting Approval Admin AFET" {{ request('status') == 'Waiting Approval Admin AFET' ? 'selected' : '' }}>Menunggu Admin AFET</option>
                    <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved (Idle)</option>
                    <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-9 text-end">
                <a href="{{ route('admin.peralatan-idle.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Ajukan Idle
                </a>
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
                        <th>Bandara</th>
                        <th>Lokasi Asal</th>
                        <th>Lokasi Unused</th>
                        <th>Pemohon</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Status</th>
                        <th>Ketersediaan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pengajuan as $i => $p)
                    <tr>
                        <td>{{ $pengajuan->firstItem() + $i }}</td>
                        <td class="fw-semibold">
                            {{ $p->alat->nama_alat ?? '-' }}
                            <br>
                            <small class="text-muted">{{ $p->alat->kode_alat ?? '-' }}</small>
                            @if($p->nomor_aset)
                                <br>
                                <small class="text-muted">No. Aset: {{ $p->nomor_aset }}</small>
                            @endif
                            <br>
                            <small class="text-muted">
                                <i class="bi bi-building me-1"></i>
                                <strong>Pemilik:</strong> {{ $p->alat->bandara->kode_bandara ?? '-' }}
                                ({{ $p->lokasiAsal->nama_lokasi ?? '-' }})
                            </small>
                            @if($p->alat->id_bandara != $p->alat->lokasi->bandara->id_bandara)
                                <br>
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-arrow-right me-1"></i> Sedang Dipinjam
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                {{ $p->alat->lokasi->bandara->kode_bandara ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $p->lokasiAsal->nama_lokasi ?? '-' }}</td>
                        <td>{{ $p->lokasiUnused->nama_lokasi ?? '-' }}</td>
                        <td>{{ $p->pemohon->nama ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->format('d/m/Y H:i') }}</td>
                        <td>
                            @php
                                $statusBadge = match($p->status) {
                                    'Approved' => 'bg-success',
                                    'Rejected' => 'bg-danger',
                                    'Waiting Approval Admin AFET' => 'bg-info text-dark',
                                    default => 'bg-warning text-dark', // Waiting Approval Dep Head
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">
                                {{ $p->status }}
                            </span>
                        </td>
                        <td>
                            @if($p->status_ketersediaan)
                                <span class="badge 
                                    {{ $p->status_ketersediaan == 'available' ? 'bg-success' : 
                                       ($p->status_ketersediaan == 'booked' ? 'bg-danger' : 
                                       ($p->status_ketersediaan == 'pending_booking' ? 'bg-warning text-dark' : 
                                       ($p->status_ketersediaan == 'pending_approval' ? 'bg-info' : 
                                       ($p->status_ketersediaan == 'not_available' ? 'bg-secondary' : 'bg-secondary')))) }}">
                                    {{ ucfirst(str_replace('_', ' ', $p->status_ketersediaan)) }}
                                </span>
                                @if($p->kondisi_alat == 'Improvement')
                                    <br>
                                    <span class="badge bg-warning text-dark mt-1">
                                        <i class="bi bi-tools me-1"></i> Improvement
                                    </span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('admin.peralatan-idle.show', $p->id_pengajuan) }}"
                                   class="btn btn-sm btn-info text-white">
                                    <i class="bi bi-eye"></i>
                                </a>

                                @php
                                    // ⚠️ DIPERBAIKI: sebelumnya juga membandingkan id_lokasi,
                                    // yang jadi salah begitu alat pindah ke lokasi "Unused"
                                    // (id_lokasi-nya beda dari lokasi asal user). Aturan yang
                                    // sebenarnya berlaku di server (PengajuanBookingController)
                                    // cuma soal bandara — "tidak bisa booking alat milik
                                    // bandara sendiri" — jadi cukup cek id_bandara saja.
                                    $isPemilikAlat = session('pengguna.id_bandara') == ($p->alat->id_bandara ?? null);
                                @endphp

                                @if($p->status == 'Approved' && $p->status_ketersediaan == 'available' && !$isPemilikAlat)
                                    <form action="{{ route('admin.peralatan-booking.store') }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Booking alat {{ $p->alat->nama_alat ?? '' }}?')">
                                        @csrf
                                        <input type="hidden" name="id_pengajuan_idle" value="{{ $p->id_pengajuan }}">
                                        <button type="submit" class="btn btn-sm btn-success" title="Booking Alat">
                                            <i class="bi bi-cart-plus"></i> Booking
                                        </button>
                                    </form>
                                @elseif($isPemilikAlat && $p->status == 'Approved' && $p->status_ketersediaan == 'available')
                                    <span class="text-muted small" title="Anda adalah pemilik alat ini, tidak bisa booking sendiri">
                                        <i class="bi bi-info-circle"></i> 
                                    </span>
                                @endif

                                @php
                                    $canTarikKembali = (
                                        $p->status == 'Approved' && 
                                        $p->status_ketersediaan == 'available' && 
                                        $p->alat && 
                                        $p->alat->id_lokasi == $p->id_lokasi_unused &&
                                        session('pengguna.role') == 'afet_bandara' &&
                                        session('pengguna.id_bandara') == ($p->alat->id_bandara ?? null)
                                    );
                                @endphp

                                @if($canTarikKembali)
                                    <form action="{{ route('admin.peralatan-idle.tarik-kembali', $p->id_pengajuan) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Yakin ingin menarik kembali alat {{ $p->alat->nama_alat ?? '' }} dari Unused?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning" title="Tarik Kembali dari Unused">
                                            <i class="bi bi-arrow-return-left"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada pengajuan idle
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $pengajuan->withQueryString()->links() }}
    </div>
</div>

@endsection