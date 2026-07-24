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

<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <p class="text-muted small fw-semibold text-uppercase mb-3">Data Booking</p>

        <div class="row g-4">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Alat</dt>
                    <dd class="col-7 fw-semibold">
                        {{ $booking->pengajuanIdle->alat->nama_alat ?? $booking->nama_alat_snapshot ?? '-' }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Kode Alat</dt>
                    <dd class="col-7">
                        {{ $booking->pengajuanIdle->alat->kode_alat ?? $booking->kode_alat_snapshot ?? '-' }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Bandara Pemberi</dt>
                    <dd class="col-7">
                        <span class="badge bg-primary">
                            {{ $booking->pengajuanIdle->alat->bandara->kode_bandara ?? '-' }}
                        </span>
                        {{ $booking->pengajuanIdle->alat->bandara->nama_bandara ?? '-' }}
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                @php
                    $bandaraPenerima = \App\Models\Bandara::find($booking->id_bandara_penerima);
                    $pemesan = \App\Models\Pengguna::find($booking->id_pengguna_pemesan);
                @endphp
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Bandara Penerima</dt>
                    <dd class="col-7">
                        {{ $bandaraPenerima->nama_bandara ?? '-' }}
                        ({{ $bandaraPenerima->kode_bandara ?? '-' }})
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Pemesan</dt>
                    <dd class="col-7">{{ $pemesan->nama ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Tanggal Booking</dt>
                    <dd class="col-7">
                        {{ $booking->tanggal_booking ? \Carbon\Carbon::parse($booking->tanggal_booking)->format('d F Y H:i') : '-' }}
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
            <input type="hidden" name="id_booking" value="{{ $booking->id_booking }}">

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
                    Bisa unggah lebih dari satu file. Format yang didukung: PDF, JPG, PNG, Word, Excel. Maksimal 10MB per file.
                </div>
            </div>

            <div class="alert alert-light border small mb-4">
                <i class="bi bi-info-circle me-1"></i>
                Setelah disimpan, pengajuan mutasi akan masuk ke tahap <strong>Waiting Approval CEO</strong>.
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