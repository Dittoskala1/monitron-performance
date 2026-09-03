@extends('layouts.app')

@section('title', 'Ajukan Mutasi - Monitoring Alat')
@section('page-title', 'Input Mapping Kebutuhan Mutasi')

@section('content')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php
    $first = $bookings->first();
    $bandaraPenerima = \App\Models\Bandara::find($first->id_bandara_penerima);
    $pemesan = \App\Models\Pengguna::find($first->id_pengguna_pemesan);
@endphp

<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <p class="text-muted small fw-semibold text-uppercase mb-3">
            Data Booking ({{ $bookings->count() }} alat)
        </p>

        <div class="table-responsive mb-4">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Alat</th>
                        <th>Kode Alat</th>
                        <th>Bandara Pemberi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $b)
                        @php $a = $b->pengajuanIdle->alat ?? null; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $a->nama_alat ?? $b->nama_alat_snapshot ?? '-' }}</td>
                            <td>{{ $a->kode_alat ?? $b->kode_alat_snapshot ?? '-' }}</td>
                            <td>
                                <span class="badge bg-primary">{{ $a->bandara->kode_bandara ?? '-' }}</span>
                                {{ $a->bandara->nama_bandara ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Bandara Penerima</dt>
                    <dd class="col-7">
                        {{ $bandaraPenerima->nama_bandara ?? '-' }}
                        ({{ $bandaraPenerima->kode_bandara ?? '-' }})
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Pemesan</dt>
                    <dd class="col-7">{{ $pemesan->nama ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Tanggal Booking</dt>
                    <dd class="col-7">
                        {{ $first->tanggal_booking ? \Carbon\Carbon::parse($first->tanggal_booking)->format('d F Y H:i') : '-' }}
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card stat-card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.peralatan-mutasi.store') }}" enctype="multipart/form-data">
            @csrf

            @foreach($bookings as $b)
                <input type="hidden" name="bookings[]" value="{{ $b->id_booking }}">
            @endforeach

            <div class="mb-3">
                <label class="form-label fw-semibold">Keterangan Kebutuhan <span class="text-danger">*</span></label>
                <textarea name="keterangan_kebutuhan" class="form-control" rows="4" required
                    placeholder="Jelaskan kebutuhan mutasi alat ini, mis. untuk keperluan operasional di bandara penerima...">{{ old('keterangan_kebutuhan') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Dokumen Mapping Kebutuhan <span class="text-danger">*</span></label>
                <input type="file" name="dokumen[]" class="form-control" multiple required
                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                <div class="form-text">
                    Bisa unggah lebih dari satu file. Format yang didukung: PDF, JPG, PNG, Word, Excel. Maksimal 10MB per file. Dokumen ini berlaku untuk seluruh alat di atas (1 pengajuan mutasi = 1 set dokumen).
                </div>
            </div>

            <div class="alert alert-light border small mb-4">
                <i class="bi bi-info-circle me-1"></i>
                Setelah disimpan, pengajuan mutasi untuk <strong>{{ $bookings->count() }} alat</strong> ini akan masuk ke tahap <strong>Waiting Approval CEO</strong> dan diproses/di-approve bersamaan dalam satu aksi.
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i> Simpan Pengajuan Mutasi
                </button>
                <a href="{{ route('admin.peralatan-booking.index') }}" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection