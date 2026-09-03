@extends('layouts.app')

@section('title', 'Detail Pengajuan Mutasi')
@section('page-title', 'Detail Pengajuan Mutasi')

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

@php
    $role = session('pengguna.role');
    $idBandara = session('pengguna.id_bandara');

    $isCeo = $role === 'ceo';
    $isPemohon = $mutasi->id_pengguna_pemohon == session('pengguna.id');
    $isAfetRegional = $role === 'afet_regional';
    $isGmPemberiBerwenang = $role === 'gm_kc' && $idBandara == $mutasi->id_bandara_pemberi;
    $isGmPenerimaBerwenang = $role === 'gm_kc' && $idBandara == $mutasi->id_bandara_penerima;
    $isAfetBandaraPemberi = $role === 'afet_bandara' && $idBandara == $mutasi->id_bandara_pemberi;
    $isAfetBandaraPenerima = $role === 'afet_bandara' && $idBandara == $mutasi->id_bandara_penerima;

    $statusBadge = match($mutasi->status) {
        'Selesai' => 'bg-success',
        'Menunggu Sertifikasi' => 'bg-primary',
        'Menunggu Pemastian Fasilitas Idle' => 'bg-secondary',
        'Ditolak GM Pemberi' => 'bg-danger',
        default => 'bg-warning text-dark',
    };

    $dokumenGrouped = $mutasi->dokumen->groupBy('jenis_dokumen');
    $jenisDokumenLabel = [
        'mapping_kebutuhan' => 'Mapping Kebutuhan',
        'pemastian_idle'    => 'Pemastian Fasilitas Idle',
        'terima_barang'     => 'BA Penerimaan Barang',
        'sertifikasi'       => 'Sertifikasi',
        'dokumen_pendukung' => 'Dokumen Pendukung',
    ];

    $iconDokumen = function ($tipe) {
        return match(strtolower($tipe)) {
            'pdf'         => 'bi-file-earmark-pdf text-danger',
            'jpg', 'jpeg',
            'png'         => 'bi-file-earmark-image text-success',
            'doc', 'docx' => 'bi-file-earmark-word text-primary',
            'xls', 'xlsx' => 'bi-file-earmark-excel text-success',
            default       => 'bi-file-earmark text-secondary',
        };
    };
@endphp

{{-- ── Header Info ── --}}
<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                @php $daftarAlatHeader = $mutasi->detailAlat->pluck('alat')->filter(); @endphp
                @if($daftarAlatHeader->count() === 1)
                    <h5 class="fw-bold mb-1">{{ $daftarAlatHeader->first()->nama_alat ?? '-' }}</h5>
                    <span class="text-muted small">
                        <i class="bi bi-hash me-1"></i>
                        Kode Alat: {{ $daftarAlatHeader->first()->kode_alat ?? '-' }}
                    </span>
                @else
                    <h5 class="fw-bold mb-1">{{ $daftarAlatHeader->count() }} Alat</h5>
                    <span class="text-muted small d-block">
                        @foreach($mutasi->detailAlat as $detail)
                            @if($detail->alat)
                                <span class="badge {{ $detail->sudahDikeluarkan() ? 'bg-light text-muted border text-decoration-line-through' : 'bg-light text-dark border' }} me-1 mb-1">
                                    {{ $detail->alat->nama_alat }} ({{ $detail->alat->kode_alat }})
                                </span>
                            @endif
                        @endforeach
                    </span>
                @endif
                <br>
                <span class="text-muted small">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    <strong>Bandara Pemberi:</strong>
                    {{ $mutasi->bandaraPemberi->nama_bandara ?? '-' }}
                    ({{ $mutasi->bandaraPemberi->kode_bandara ?? '-' }})
                </span>
                <br>
                <span class="text-muted small">
                    <i class="bi bi-box-arrow-in-left me-1"></i>
                    <strong>Bandara Penerima:</strong>
                    {{ $mutasi->bandaraPenerima->nama_bandara ?? '-' }}
                    ({{ $mutasi->bandaraPenerima->kode_bandara ?? '-' }})
                </span>
                <br>
                <span class="text-muted small">
                    <i class="bi bi-person me-1"></i>
                    <strong>Pemohon:</strong> {{ $mutasi->pemohon->nama ?? '-' }}
                </span>
                <br>
                <span class="text-muted small">
                    <i class="bi bi-calendar-event me-1"></i>
                    Diajukan: {{ $mutasi->created_at->format('d F Y H:i') }}
                </span>
            </div>
            <div class="text-end">
                <span class="badge fs-6 {{ $statusBadge }}">{{ $mutasi->status }}</span>
            </div>
        </div>

        <hr class="mb-4">

        <p class="text-muted small fw-semibold text-uppercase mb-2">Keterangan Kebutuhan</p>
        <p class="mb-0">{{ $mutasi->keterangan_kebutuhan ?? '-' }}</p>
    </div>
</div>

{{-- ── Progres Pengajuan (Tracker) ── --}}
@php
    $trackerSteps = [
        ['label' => 'Diajukan',                  'icon' => 'bi-file-earmark-plus', 'date' => $mutasi->created_at],
        ['label' => 'Approval CEO',              'icon' => 'bi-person-check',      'date' => $mutasi->tanggal_ceo_approval],
        ['label' => 'Approval GM Pemberi',       'icon' => 'bi-person-badge',      'date' => $mutasi->tanggal_gm_approval],
        ['label' => 'Pemastian Fasilitas Idle',  'icon' => 'bi-clipboard-check',   'date' => $mutasi->tanggal_konfirmasi_idle],
        ['label' => 'Sertifikasi',               'icon' => 'bi-patch-check',       'date' => $mutasi->tanggal_sertifikasi],
        ['label' => 'Selesai',                   'icon' => 'bi-flag-fill',         'date' => $mutasi->status === 'Selesai' ? $mutasi->tanggal_sertifikasi : null],
    ];

    $statusToIndex = [
        'Waiting Approval CEO'              => 1,
        'Waiting Approval GM Pemberi'       => 2,
        'Ditolak GM Pemberi'                => 2,
        'Menunggu Pemastian Fasilitas Idle' => 3,
        'Menunggu Sertifikasi'              => 4,
        'Selesai'                           => 5,
    ];

    $currentIndex = $statusToIndex[$mutasi->status] ?? 0;
    $isSelesai    = $mutasi->status === 'Selesai';
    $doneUpTo     = $isSelesai ? 5 : $currentIndex - 1;

    // Tahap yang butuh perhatian (revisi/penolakan) — ditandai warna warning, bukan hijau/biru
    $isRevisiCeo    = $mutasi->status === 'Waiting Approval CEO' && $mutasi->alasan_reject_ceo;
    $isDitolakGm    = $mutasi->status === 'Ditolak GM Pemberi';
    $isBaIdleDitolak = $mutasi->status === 'Waiting Approval GM Pemberi' && $mutasi->alasan_reject_idle;

    $needsAttention = $isRevisiCeo || $isDitolakGm || $isBaIdleDitolak;

    $attentionText = $isRevisiCeo
        ? 'CEO menolak pengajuan awal — menunggu revisi dari pemohon'
        : ($isDitolakGm
            ? 'GM Pemberi menolak — pemohon perlu mengajukan ulang (CEO hanya menerima info)'
            : ($isBaIdleDitolak
                ? 'AFET Regional menolak Pemastian Fasilitas Idle — dikembalikan ke tahap approval GM Pemberi'
                : null));

    $progressPercent = $isSelesai ? 100 : (int) round((($doneUpTo + 1) / 6) * 100);

    // Sub-label khusus tahap Menunggu Sertifikasi.
    $trackerSubLabel = null;
    if ($mutasi->status === 'Menunggu Sertifikasi') {
        $trackerSubLabel = 'Menunggu AFET Bandara Penerima melengkapi BA dan menyelesaikan sertifikasi';
    }
@endphp
<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted small fw-semibold text-uppercase mb-0">Progres Pengajuan</p>
            <span class="small fw-bold" style="color: {{ $needsAttention ? 'var(--warning-600)' : ($isSelesai ? 'var(--success-600)' : 'var(--brand-600)') }}">
                {{ $progressPercent }}%
            </span>
        </div>

        @if($needsAttention)
        <div class="d-flex align-items-center gap-2 small fw-semibold mb-3 p-2 px-3 rounded"
             style="background: var(--warning-50); color: var(--warning-700);">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ $attentionText }}</span>
        </div>
        @endif

        <div class="mutasi-tracker">
            @foreach($trackerSteps as $i => $step)
                @php
                    $state = $i <= $doneUpTo ? 'done' : ($i === $currentIndex && ! $isSelesai ? 'current' : 'pending');
                    if ($state === 'current' && $needsAttention) { $state = 'warning'; }

                    $gapFilled = fn($gapIndex) => $isSelesai ? true : ($gapIndex < $currentIndex);
                    $leftFilled  = $i > 0 ? $gapFilled($i - 1) : null;
                    $rightFilled = $i < 5 ? $gapFilled($i) : null;
                @endphp
                <div class="tracker-step tracker-step--{{ $state }}">
                    <div class="tracker-connector">
                        <span class="tracker-seg {{ $i === 0 ? 'tracker-seg--invisible' : ($leftFilled ? 'tracker-seg--filled' : '') }}"></span>
                        <span class="tracker-node">
                            @if($state === 'done')
                                <i class="bi bi-check-lg"></i>
                            @elseif($state === 'warning')
                                <i class="bi bi-exclamation-lg"></i>
                            @else
                                <i class="bi {{ $step['icon'] }}"></i>
                            @endif
                        </span>
                        <span class="tracker-seg {{ $i === 5 ? 'tracker-seg--invisible' : ($rightFilled ? 'tracker-seg--filled' : '') }}"></span>
                    </div>
                    <div class="tracker-label">
                        <span class="tracker-label-text">{{ $step['label'] }}</span>
                        @if($state === 'done' && $step['date'])
                            <span class="tracker-label-date">{{ $step['date']->format('d/m/y H:i') }}</span>
                        @elseif($state === 'current')
                            <span class="tracker-label-date tracker-label-date--active">{{ $trackerSubLabel ?? 'Sedang diproses' }}</span>
                        @elseif($state === 'warning')
                            <span class="tracker-label-date tracker-label-date--warning">Perlu tindakan</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .mutasi-tracker {
        display: flex;
        align-items: flex-start;
        overflow-x: auto;
        padding-bottom: 4px;
    }
    .tracker-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1 1 0;
        min-width: 92px;
    }
    .tracker-connector {
        display: flex;
        align-items: center;
        width: 100%;
    }
    .tracker-seg {
        flex: 1 1 auto;
        height: 3px;
        background: var(--gray-200);
        border-radius: 2px;
        transition: background .25s ease;
    }
    .tracker-seg--filled { background: var(--success-500); }
    .tracker-seg--invisible { background: transparent; }
    .tracker-node {
        flex-shrink: 0;
        width: 34px;
        height: 34px;
        margin: 0 4px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        background: var(--gray-100);
        color: var(--gray-400);
        border: 2px solid var(--gray-200);
        transition: all .25s ease;
    }
    .tracker-step--done .tracker-node {
        background: var(--success-500);
        border-color: var(--success-500);
        color: #fff;
    }
    .tracker-step--current .tracker-node {
        background: var(--brand-500);
        border-color: var(--brand-500);
        color: #fff;
        box-shadow: 0 0 0 4px var(--brand-100);
        animation: trackerPulse 1.6s ease-in-out infinite;
    }
    .tracker-step--warning .tracker-node {
        background: var(--warning-500);
        border-color: var(--warning-500);
        color: #fff;
        box-shadow: 0 0 0 4px var(--warning-50);
    }
    @keyframes trackerPulse {
        0%, 100% { box-shadow: 0 0 0 4px var(--brand-100); }
        50% { box-shadow: 0 0 0 7px var(--brand-50); }
    }
    .tracker-label {
        margin-top: 8px;
        text-align: center;
        padding: 0 2px;
    }
    .tracker-label-text {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: var(--gray-700);
        line-height: 1.3;
    }
    .tracker-step--pending .tracker-label-text {
        color: var(--gray-400);
        font-weight: 500;
    }
    .tracker-label-date {
        display: block;
        font-size: 10.5px;
        color: var(--gray-400);
        margin-top: 2px;
    }
    .tracker-label-date--active { color: var(--brand-600); font-weight: 600; }
    .tracker-label-date--warning { color: var(--warning-600); font-weight: 600; }
    @media (max-width: 767px) {
        .tracker-step { min-width: 80px; }
        .tracker-label-text { font-size: 10.5px; }
    }
</style>

{{-- ── Alat Dikeluarkan GM Pemberi (kalau ada) ── --}}
@if($mutasi->jumlahAlatDikeluarkan() > 0)
<div class="card stat-card mb-4 border-warning">
    <div class="card-body p-4">
        <p class="text-muted small fw-semibold text-uppercase mb-3">
            <i class="bi bi-exclamation-triangle text-warning me-1"></i>
            Alat Dikeluarkan dari Pengajuan ({{ $mutasi->jumlahAlatDikeluarkan() }})
        </p>
        <div class="form-text mb-2">
            GM Pemberi menilai alat berikut belum/tidak bisa dimutasikan meski statusnya Unused/idle.
            Alat ini sudah otomatis kembali berstatus Available di bandara pemberi dan tidak ikut lanjut ke tahap berikutnya.
        </div>
        <ul class="list-unstyled mb-0 small">
            @foreach($mutasi->detailAlat->where('status_gm', 'dikeluarkan') as $detail)
                <li class="mb-1">
                    <strong>{{ $detail->alat->nama_alat ?? '-' }}</strong> ({{ $detail->alat->kode_alat ?? '-' }})
                    @if($detail->catatan_dikeluarkan)
                        — <span class="text-muted">{{ $detail->catatan_dikeluarkan }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- ── Riwayat Tahapan ── --}}
<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <p class="text-muted small fw-semibold text-uppercase mb-3">Riwayat Tahapan</p>

        <div class="row g-4">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-6 text-muted fw-normal">Approval CEO</dt>
                    <dd class="col-6">
                        {{ $mutasi->ceoApprover->nama ?? '-' }}
                        @if($mutasi->tanggal_ceo_approval)
                            <br><small class="text-muted">{{ $mutasi->tanggal_ceo_approval->format('d/m/Y H:i') }}</small>
                        @endif
                    </dd>

                    @if($mutasi->alasan_reject_ceo)
                    <dt class="col-6 text-muted fw-normal">Catatan Reject CEO</dt>
                    <dd class="col-6 text-danger">{{ $mutasi->alasan_reject_ceo }}</dd>
                    @endif

                    <dt class="col-6 text-muted fw-normal">Keputusan GM Pemberi</dt>
                    <dd class="col-6">
                        @if($mutasi->keputusan_gm)
                            <span class="badge {{ $mutasi->keputusan_gm === 'Approve' ? 'bg-success' : 'bg-danger' }}">
                                {{ $mutasi->keputusan_gm }}
                            </span>
                            <br>
                            <small class="text-muted">
                                {{ $mutasi->gmApprover->nama ?? '-' }}
                                @if($mutasi->tanggal_gm_approval)
                                    — {{ $mutasi->tanggal_gm_approval->format('d/m/Y H:i') }}
                                @endif
                            </small>
                            @if($mutasi->catatan_gm)
                                <br><small class="text-muted">Catatan: {{ $mutasi->catatan_gm }}</small>
                            @endif
                        @else
                            -
                        @endif
                    </dd>

                    @if($mutasi->jumlah_ajukan_ulang > 0)
                    <dt class="col-6 text-muted fw-normal">Ajukan Ulang</dt>
                    <dd class="col-6">
                        <span class="badge bg-secondary">{{ $mutasi->jumlah_ajukan_ulang }}x</span>
                        <br>
                        <small class="text-muted">
                            {{ $mutasi->pengajuUlang->nama ?? '-' }}
                            @if($mutasi->tanggal_ajukan_ulang)
                                — {{ $mutasi->tanggal_ajukan_ulang->format('d/m/Y H:i') }}
                            @endif
                        </small>
                    </dd>
                    @endif
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-6 text-muted fw-normal">Upload BA Idle</dt>
                    <dd class="col-6">
                        {{ $mutasi->uploaderBaIdle->nama ?? '-' }}
                        @if($mutasi->tanggal_upload_ba_idle)
                            <br><small class="text-muted">{{ $mutasi->tanggal_upload_ba_idle->format('d/m/Y H:i') }}</small>
                        @endif
                    </dd>

                    <dt class="col-6 text-muted fw-normal">Konfirmasi Idle</dt>
                    <dd class="col-6">
                        {{ $mutasi->konfirmatorIdle->nama ?? '-' }}
                        @if($mutasi->tanggal_konfirmasi_idle)
                            <br><small class="text-muted">{{ $mutasi->tanggal_konfirmasi_idle->format('d/m/Y H:i') }}</small>
                        @endif
                    </dd>

                    @if($mutasi->alasan_reject_idle)
                    <dt class="col-6 text-muted fw-normal">Catatan Reject Idle</dt>
                    <dd class="col-6 text-danger">{{ $mutasi->alasan_reject_idle }}</dd>
                    @endif

                    <dt class="col-6 text-muted fw-normal">BA Penerimaan Barang</dt>
                    <dd class="col-6">
                        {{ $mutasi->penerimaBarang->nama ?? '-' }}
                        @if($mutasi->tanggal_terima_barang)
                            <br><small class="text-muted">{{ $mutasi->tanggal_terima_barang->format('d/m/Y H:i') }}</small>
                        @endif
                        @if($mutasi->catatan_terima_barang)
                            <br><small class="text-muted">Catatan: {{ $mutasi->catatan_terima_barang }}</small>
                        @endif
                    </dd>

                    <dt class="col-6 text-muted fw-normal">Sertifikasi</dt>
                    <dd class="col-6">
                        {{ $mutasi->pelaksanaSertifikasi->nama ?? '-' }}
                        @if($mutasi->tanggal_sertifikasi)
                            <br><small class="text-muted">{{ $mutasi->tanggal_sertifikasi->format('d/m/Y H:i') }}</small>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>



{{-- ── Dokumen ── --}}
<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <p class="text-muted small fw-semibold text-uppercase mb-3">Dokumen</p>

        @if($mutasi->dokumen->isEmpty())
            <p class="text-muted small mb-0">Belum ada dokumen yang dilampirkan.</p>
        @else
            @foreach($dokumenGrouped as $jenis => $dokumenList)
            <p class="small fw-semibold mb-2">{{ $jenisDokumenLabel[$jenis] ?? ucfirst(str_replace('_', ' ', $jenis)) }}</p>
            <div class="row g-2 mb-3">
                @foreach($dokumenList as $dok)
                <div class="col-md-4">
                    <div class="d-flex align-items-center justify-content-between border rounded p-2">
                        <div class="d-flex align-items-center gap-2 text-truncate">
                            <i class="bi {{ $iconDokumen($dok->tipe_file) }} fs-5"></i>
                            <span class="small text-truncate" title="{{ $dok->nama_file }}">{{ $dok->nama_file }}</span>
                        </div>
                        <a href="{{ route('admin.peralatan-mutasi.download-dokumen', $dok->id_dokumen) }}"
                            target="_blank"
                                class="btn btn-sm btn-outline-primary"
                                    title="Lihat / Unduh">
                                        <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
        @endif
    </div>
</div>

{{-- ── Aksi Sesuai Tahap & Role ── --}}
<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <p class="text-muted small fw-semibold text-uppercase mb-3">Tindakan</p>

        @if($mutasi->status === 'Waiting Approval CEO')
            @if($isCeo)
                @if($mutasi->alasan_reject_ceo)
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Pengajuan ini sebelumnya sudah ditolak dengan catatan di atas. Approve jika sudah direvisi/sesuai.
                    </div>
                @endif
                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.peralatan-mutasi.approve-ceo', $mutasi->id_pengajuan_mutasi) }}"
                          onsubmit="return confirm('Setujui pengajuan mutasi ini?')">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Approve
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalRejectCeo">
                        <i class="bi bi-x-lg me-1"></i> Reject
                    </button>
                </div>
            @else
                <div class="text-muted small">
                    <i class="bi bi-hourglass-split me-1"></i> Menunggu keputusan CEO.
                </div>
            @endif

            @elseif($mutasi->status === 'Waiting Approval GM Pemberi')
    @if($isGmPemberiBerwenang)
        <form method="POST" action="{{ route('admin.peralatan-mutasi.approve-gm', $mutasi->id_pengajuan_mutasi) }}"
              enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Alat yang Boleh Lanjut Dimutasikan</label>
                <div class="form-text mb-2">
                    Status "Unused" di sistem cuma menandakan alat sedang idle — belum tentu boleh dimutasikan.
                    Centang alat yang secara bisnis memang boleh lanjut. Alat yang tidak dicentang akan dikeluarkan
                    dari pengajuan ini dan otomatis kembali berstatus Available di bandara pemberi.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:1%"></th>
                                <th>Alat</th>
                                <th>Alasan Dikeluarkan (kalau tidak dicentang)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mutasi->detailAlat as $detail)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input" name="alat_lanjut[]"
                                               value="{{ $detail->id_mutasi_alat }}" checked>
                                    </td>
                                    <td>{{ $detail->alat->nama_alat ?? '-' }} <span class="text-muted small">({{ $detail->alat->kode_alat ?? '-' }})</span></td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm"
                                               name="catatan_dikeluarkan[{{ $detail->id_mutasi_alat }}]"
                                               placeholder="Opsional, mis. masih dibutuhkan cadangan">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Dokumen Pendukung (opsional)</label>
                <input type="file" name="dokumen_pendukung[]" class="form-control" multiple>
                <div class="form-text">Tipe file bebas. Maksimal 10MB per file. Boleh lebih dari satu file, boleh dikosongkan.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Catatan (opsional)</label>
                <textarea name="catatan_gm" class="form-control" rows="2"></textarea>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-success"
                        onclick="return confirm('Setujui mutasi ini? Alat yang tidak dicentang akan dikeluarkan dari pengajuan.')">
                    <i class="bi bi-check-lg me-1"></i> Approve
                </button>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalRejectGm">
                    <i class="bi bi-x-lg me-1"></i> Reject Semua
                </button>
            </div>
        </form>
    @else
        <div class="text-muted small">
            <i class="bi bi-hourglass-split me-1"></i> Menunggu keputusan GM Bandara Pemberi.
        </div>
    @endif

        @elseif($mutasi->status === 'Menunggu Pemastian Fasilitas Idle')
            @if($mutasi->alasan_reject_idle && ! $mutasi->id_pengguna_upload_ba_idle)
            <div class="d-flex align-items-start gap-2 small mb-3 p-2 px-3 rounded"
                 style="background: var(--error-50); color: var(--error-700);">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <span><strong>BA sebelumnya ditolak AFET Regional:</strong> {{ $mutasi->alasan_reject_idle }}</span>
            </div>
            @endif

            @if(($isAfetBandaraPemberi || $isGmPemberiBerwenang) && ! $mutasi->id_pengguna_upload_ba_idle)
                <form method="POST" action="{{ route('admin.peralatan-mutasi.upload-dokumen-idle', $mutasi->id_pengajuan_mutasi) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dokumen BA Pemastian Idle <span class="text-danger">*</span></label>
                        <input type="file" name="dokumen[]" class="form-control" multiple required
                            accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">Format PDF, JPG, PNG. Maksimal 10MB per file.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Upload Dokumen BA
                    </button>
                </form>
            @elseif($isAfetRegional && $mutasi->id_pengguna_upload_ba_idle)
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('admin.peralatan-mutasi.konfirmasi-idle', $mutasi->id_pengajuan_mutasi) }}"
                          onsubmit="return confirm('Konfirmasi fasilitas idle sudah sesuai?')">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Konfirmasi Fasilitas Idle
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalRejectIdle">
                        <i class="bi bi-x-lg me-1"></i> Tolak
                    </button>
                </div>
            @else
                <div class="text-muted small">
                    <i class="bi bi-hourglass-split me-1"></i>
                    @if(! $mutasi->id_pengguna_upload_ba_idle)
                        Menunggu Admin AFET Bandara Pemberi mengupload dokumen BA pemastian idle.
                    @else
                        Menunggu konfirmasi Admin AFET Regional.
                    @endif
                </div>
            @endif

            @elseif($mutasi->status === 'Menunggu Sertifikasi')

@php
    /*
     * ==========================================================
     * DATA ALAT YANG BOLEH MASUK SERTIFIKASI
     * ==========================================================
     *
     * HANYA alat dengan status_gm = lanjut.
     *
     * Alat yang sudah dikeluarkan GM tidak akan ditampilkan.
     */
    $alatLanjut = $mutasi->detailAlat
        ->where('status_gm', 'lanjut');

    /*
     * ==========================================================
     * DOKUMEN YANG SUDAH ADA
     * ==========================================================
     */

    $dokumenBa = $mutasi->dokumen
        ->where('jenis_dokumen', 'terima_barang');

    $dokumenSertifikasi = $mutasi->dokumen
        ->where('jenis_dokumen', 'sertifikasi');

    $dokumenPendukungSertifikasi = $mutasi->dokumen
        ->where('jenis_dokumen', 'dokumen_pendukung');

    /*
     * ==========================================================
     * LOKASI BANDARA PENERIMA
     * ==========================================================
     */

    $lokasiPenerima = \App\Models\Lokasi::where(
        'id_bandara',
        $mutasi->id_bandara_penerima
    )->get();
@endphp


{{-- ==========================================================
     HANYA AFET BANDARA PENERIMA YANG BISA MEMPROSES
     ========================================================== --}}

@if($isAfetBandaraPenerima)

    <div class="mb-4">

        <div class="mb-4">

            <p class="small fw-semibold mb-1">
                <i class="bi bi-patch-check me-1"></i>
                Sertifikasi Mutasi
            </p>

            <div class="text-muted small">
                Lengkapi BA Penerimaan Barang,
                Dokumen Pendukung Sertifikasi,
                dan Lokasi Tujuan Akhir Alat.
            </div>

        </div>


        {{-- ==================================================
             FORM UTAMA SERTIFIKASI
             ================================================== --}}

        <form
            method="POST"
            action="{{ route(
                'admin.peralatan-mutasi.sertifikasi',
                $mutasi->id_pengajuan_mutasi
            ) }}"
            enctype="multipart/form-data"
        >

            @csrf


            {{-- ==================================================
                 1. BA PENERIMAAN BARANG
                 ================================================== --}}

            <div class="card border mb-4">

                <div class="card-header bg-light">

                    <strong>
                        <i class="bi bi-file-earmark-text me-1"></i>
                        1. BA Penerimaan Barang
                    </strong>

                </div>

                <div class="card-body">

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Upload BA Penerimaan Barang

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="file"
                            name="dokumen_ba[]"
                            class="form-control"
                            multiple
                            required
                            accept=".pdf,.jpg,.jpeg,.png"
                        >

                        <div class="form-text">

                            Wajib diupload.

                            Format PDF, JPG, JPEG, PNG.
                            Maksimal 10MB per file.

                        </div>

                    </div>


                    <div class="mb-0">

                        <label class="form-label fw-semibold">

                            Catatan Penerimaan Barang

                        </label>

                        <textarea
                            name="catatan_terima_barang"
                            class="form-control"
                            rows="2"
                            placeholder="Catatan penerimaan barang (opsional)..."
                        >{{ old(
                            'catatan_terima_barang',
                            $mutasi->catatan_terima_barang
                        ) }}</textarea>

                    </div>


                    {{-- ==========================================
                         BA YANG SUDAH ADA
                         ========================================== --}}

                    @if($dokumenBa->count() > 0)

                        <div class="mt-3">

                            <div class="small fw-semibold mb-2">

                                BA yang sudah tersimpan:

                            </div>

                            @foreach($dokumenBa as $dok)

                                <div
                                    class="d-flex align-items-center gap-2 mb-2"
                                >

                                    <i
                                        class="{{ $iconDokumen($dok->tipe_file) }}"
                                    ></i>

                                    <a
                                        href="{{ route(
                                            'admin.peralatan-mutasi.download-dokumen',
                                            $dok->id_dokumen
                                        ) }}"
                                        target="_blank"
                                        class="small"
                                    >

                                        {{ $dok->nama_file }}

                                    </a>

                                </div>

                            @endforeach

                        </div>

                    @endif

                </div>

            </div>


            {{-- ==================================================
                 2. DOKUMEN PENDUKUNG (OPSIONAL, TIPE FILE BEBAS)
                 ================================================== --}}

            <div class="card border mb-4">

                <div class="card-header bg-light">

                    <strong>
                        <i class="bi bi-paperclip me-1"></i>
                        2. Dokumen Pendukung (opsional)
                    </strong>

                </div>

                <div class="card-body">

                    <div class="mb-0">

                        <label class="form-label fw-semibold">
                            Upload Dokumen Pendukung
                        </label>

                        <input
                            type="file"
                            name="dokumen_pendukung[]"
                            class="form-control"
                            multiple
                        >

                        <div class="form-text">
                            Opsional. Tipe file bebas. Maksimal 10MB per file.
                            Boleh lebih dari satu file, boleh dikosongkan.
                        </div>

                        {{-- ==========================================
                             DOKUMEN PENDUKUNG YANG SUDAH ADA
                             ========================================== --}}

                        @if($dokumenPendukungSertifikasi->count() > 0)

                            <div class="mt-3">

                                <div class="small fw-semibold mb-2">
                                    Dokumen pendukung yang sudah tersimpan:
                                </div>

                                @foreach($dokumenPendukungSertifikasi as $dok)

                                    <div
                                        class="d-flex align-items-center gap-2 mb-2"
                                    >

                                        <i
                                            class="{{ $iconDokumen($dok->tipe_file) }}"
                                        ></i>

                                        <a
                                            href="{{ route(
                                                'admin.peralatan-mutasi.download-dokumen',
                                                $dok->id_dokumen
                                            ) }}"
                                            target="_blank"
                                            class="small"
                                        >
                                            {{ $dok->nama_file }}
                                        </a>

                                    </div>

                                @endforeach

                            </div>

                        @endif

                    </div>

                </div>

            </div>


            {{-- ==================================================
                 INFORMASI DOKUMEN SERTIFIKASI
                 ================================================== --}}

            <div class="alert alert-info mb-4">

                <div class="d-flex gap-2">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        <div class="fw-semibold">Dokumen Sertifikasi</div>
                        <div class="small">
                            Dokumen Sertifikasi resmi tidak wajib diupload pada tahap ini.
                            Dokumen tersebut dapat diupload setelah proses mutasi berstatus
                            <strong>Selesai</strong>. Dokumen Pendukung (file bebas) di atas
                            bisa diupload sekarang, bersamaan dengan proses ini.
                        </div>
                    </div>
                </div>

            </div>


            {{-- ==================================================
                 3. LOKASI TUJUAN AKHIR ALAT
                 ================================================== --}}

            <div class="card border mb-4">

                <div class="card-header bg-light">

                    <strong>

                        <i class="bi bi-geo-alt me-1"></i>

                        3. Lokasi Tujuan Akhir Alat

                    </strong>

                </div>


                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-sm align-middle mb-0">

                            <thead class="table-light">

                                <tr>

                                    <th>
                                        Alat
                                    </th>

                                    <th>
                                        Status GM
                                    </th>

                                    <th>
                                        Lokasi Tujuan
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse(
                                    $alatLanjut
                                    as $detail
                                )

                                    <tr>

                                        <td>

                                            <div class="fw-semibold">

                                                {{ $detail->alat->nama_alat ?? '-' }}

                                            </div>

                                            <small class="text-muted">

                                                {{ $detail->alat->kode_alat ?? '-' }}

                                            </small>

                                        </td>


                                        <td>

                                            <span class="badge bg-success">

                                                Lanjut

                                            </span>

                                        </td>


                                        <td>

                                            <select
                                                name="lokasi_tujuan[{{ $detail->id_mutasi_alat }}]"
                                                class="form-select form-select-sm"
                                            >

                                                <option value="">

                                                    -- Tetap di Unused --

                                                </option>


                                                @foreach(
                                                    $lokasiPenerima
                                                    as $lok
                                                )

                                                    <option
                                                        value="{{ $lok->id_lokasi }}"
                                                        @selected(
                                                            old(
                                                                "lokasi_tujuan.{$detail->id_mutasi_alat}",
                                                                $detail->id_lokasi_tujuan
                                                            ) == $lok->id_lokasi
                                                        )
                                                    >

                                                        {{ $lok->nama_lokasi }}

                                                    </option>

                                                @endforeach

                                            </select>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td
                                            colspan="3"
                                            class="text-center text-muted"
                                        >

                                            Tidak ada alat yang dapat
                                            dilanjutkan ke sertifikasi.

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    <div class="form-text mt-2">

                        Hanya alat yang dinyatakan
                        <strong>lanjut</strong> oleh GM Pemberi
                        yang ditampilkan.

                        Alat yang dikeluarkan GM tidak ikut
                        dalam proses sertifikasi.

                        Jika lokasi tidak dipilih,
                        alat tetap berada di lokasi
                        <strong>Unused</strong> bandara penerima.

                    </div>

                </div>

            </div>


            {{-- ==================================================
                 TOMBOL SELESAIKAN SERTIFIKASI
                 ================================================== --}}

            <div class="d-flex justify-content-end">

                <button
                    type="submit"
                    class="btn btn-primary"
                    onclick="return confirm(
                        'Pastikan BA Penerimaan Barang dan lokasi alat sudah benar. Selesaikan sertifikasi?'
                    )"
                >

                    <i class="bi bi-patch-check me-1"></i>

                    Selesaikan Sertifikasi

                </button>

            </div>

        </form>

    </div>


@else

    {{-- ==========================================================
         USER SELAIN AFET BANDARA PENERIMA
         ========================================================== --}}

    <div class="text-muted small">

        <i class="bi bi-hourglass-split me-1"></i>

        Menunggu Admin AFET Bandara Penerima
        melakukan sertifikasi.

    </div>

@endif

        @elseif($mutasi->status === 'Selesai')

            @php
                $dokumenSertifikasi = $mutasi->dokumen
                    ->where('jenis_dokumen', 'sertifikasi');
            @endphp

            <div class="mb-4">

                <div class="text-success small mb-4">
                    <i class="bi bi-check-circle me-1"></i>
                    Proses mutasi sudah tuntas.
                </div>

                @if($isAfetBandaraPenerima)
                    <div class="card border">
                        <div class="card-header bg-light">
                            <strong>
                                <i class="bi bi-file-earmark-check me-1"></i>
                                Dokumen Sertifikasi
                            </strong>
                        </div>

                        <div class="card-body">
                            <div class="text-muted small mb-3">
                                Dokumen Sertifikasi/Pendukung dapat diupload menyusul setelah proses mutasi selesai.
                            </div>

                            <form
                                method="POST"
                                action="{{ route('admin.peralatan-mutasi.dokumen-sertifikasi', $mutasi->id_pengajuan_mutasi) }}"
                                enctype="multipart/form-data"
                            >
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        Upload Dokumen Sertifikasi
                                    </label>

                                    <input
                                        type="file"
                                        name="dokumen_sertifikasi[]"
                                        class="form-control"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png"
                                    >

                                    <div class="form-text">
                                        Opsional. Format PDF, JPG, JPEG, atau PNG. Maksimal 10MB per file.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-upload me-1"></i>
                                        Upload Dokumen Sertifikasi
                                    </button>
                                </div>
                            </form>

                            @if($dokumenSertifikasi->count() > 0)
                                <hr class="my-4">
                                <div class="small fw-semibold mb-2">
                                    Dokumen Sertifikasi yang sudah diupload:
                                </div>

                                @foreach($dokumenSertifikasi as $dok)
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="{{ $iconDokumen($dok->tipe_file) }}"></i>
                                        <a
                                            href="{{ route('admin.peralatan-mutasi.download-dokumen', $dok->id_dokumen) }}"
                                            target="_blank"
                                            class="small"
                                        >
                                            {{ $dok->nama_file }}
                                        </a>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-muted small mt-3">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Belum ada Dokumen Sertifikasi yang diupload.
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    @if($dokumenSertifikasi->count() > 0)
                        <div class="card border">
                            <div class="card-header bg-light">
                                <strong>
                                    <i class="bi bi-file-earmark-check me-1"></i>
                                    Dokumen Sertifikasi
                                </strong>
                            </div>
                            <div class="card-body">
                                @foreach($dokumenSertifikasi as $dok)
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="{{ $iconDokumen($dok->tipe_file) }}"></i>
                                        <a
                                            href="{{ route('admin.peralatan-mutasi.download-dokumen', $dok->id_dokumen) }}"
                                            target="_blank"
                                            class="small"
                                        >
                                            {{ $dok->nama_file }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

            </div>
        @endif
    </div>
</div>

<div class="d-flex gap-2">
    <a href="{{ route('admin.peralatan-mutasi.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i> Kembali
    </a>
</div>

{{-- ── Modal Reject CEO ── --}}
<div class="modal fade" id="modalRejectCeo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.peralatan-mutasi.reject-ceo', $mutasi->id_pengajuan_mutasi) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tolak Pengajuan Mutasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="alasan_reject_ceo" class="form-control" rows="4" required
                        placeholder="Jelaskan alasan menolak pengajuan ini..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal Reject GM Pemberi ── --}}
<div class="modal fade" id="modalRejectGm" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.peralatan-mutasi.reject-gm', $mutasi->id_pengajuan_mutasi) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tolak Mutasi (GM Pemberi)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Catatan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="catatan_gm" class="form-control" rows="4" required
                        placeholder="Jelaskan alasan menolak mutasi alat ini..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Mutasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal Reject Pemastian Fasilitas Idle (AFET Regional) ── --}}
<div class="modal fade" id="modalRejectIdle" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.peralatan-mutasi.reject-idle', $mutasi->id_pengajuan_mutasi) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tolak BA Pemastian Fasilitas Idle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="alasan_reject_idle" class="form-control" rows="4" required
                        placeholder="Jelaskan alasan menolak dokumen BA ini..."></textarea>
                    <div class="form-text">Admin AFET Bandara Pemberi akan diminta upload ulang dokumen.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Dokumen</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection