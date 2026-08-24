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
    $isAfetRegional = $role === 'afet_regional';
    $isGmPemberiBerwenang = $role === 'gm_kc' && $idBandara == $mutasi->id_bandara_pemberi;
    $isAfetBandaraPemberi = $role === 'afet_bandara' && $idBandara == $mutasi->id_bandara_pemberi;
    $isAfetBandaraPenerima = $role === 'afet_bandara' && $idBandara == $mutasi->id_bandara_penerima;

    $statusBadge = match($mutasi->status) {
        'Selesai' => 'bg-success',
        'Siap Mobilisasi', 'Menunggu Sertifikasi' => 'bg-primary',
        'Waiting Konfirmasi CEO', 'Menunggu Verifikasi Mobilisasi' => 'bg-info text-dark',
        'Menunggu Pemastian Fasilitas Idle' => 'bg-secondary',
        default => 'bg-warning text-dark',
    };

    $dokumenGrouped = $mutasi->dokumen->groupBy('jenis_dokumen');
    $jenisDokumenLabel = [
        'mapping_kebutuhan' => 'Mapping Kebutuhan',
        'pemastian_idle'    => 'Pemastian Fasilitas Idle',
        'mobilisasi'        => 'Mobilisasi',
        'sertifikasi'       => 'Sertifikasi',
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
                <h5 class="fw-bold mb-1">{{ $mutasi->alat->nama_alat ?? '-' }}</h5>
                <span class="text-muted small">
                    <i class="bi bi-hash me-1"></i>
                    Kode Alat: {{ $mutasi->alat->kode_alat ?? '-' }}
                </span>
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

                    <dt class="col-6 text-muted fw-normal">Diteruskan CEO</dt>
                    <dd class="col-6">
                        {{ $mutasi->ceoTeruskan->nama ?? '-' }}
                        @if($mutasi->tanggal_ceo_teruskan)
                            <br><small class="text-muted">{{ $mutasi->tanggal_ceo_teruskan->format('d/m/Y H:i') }}</small>
                        @endif
                    </dd>
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

                    <dt class="col-6 text-muted fw-normal">Mobilisasi</dt>
                    <dd class="col-6">
                        {{ $mutasi->pelaksanaMobilisasi->nama ?? '-' }}
                        @if($mutasi->tanggal_mobilisasi)
                            <br><small class="text-muted">{{ $mutasi->tanggal_mobilisasi->format('d/m/Y H:i') }}</small>
                        @endif
                        @if($mutasi->catatan_mobilisasi)
                            <br><small class="text-muted">Catatan: {{ $mutasi->catatan_mobilisasi }}</small>
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

{{-- ── Verifikasi Mobilisasi (3 tanda tangan) ── --}}
@if($mutasi->verifikasiMobilisasi)
@php
    $v = $mutasi->verifikasiMobilisasi;
    $vBadge = fn($status) => match($status) {
        'Konfirmasi'   => 'bg-success',
        'Tidak Sesuai' => 'bg-danger',
        default        => 'bg-warning text-dark',
    };
    $penggunaRegional = \App\Models\Pengguna::find($v->id_pengguna_regional);
    $penggunaPenerima = \App\Models\Pengguna::find($v->id_pengguna_penerima);
    $penggunaPemberi  = \App\Models\Pengguna::find($v->id_pengguna_pemberi);
@endphp
<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <p class="text-muted small fw-semibold text-uppercase mb-3">Verifikasi Mobilisasi</p>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small">AFET Regional</span>
                        <span class="badge {{ $vBadge($v->status_regional) }}">{{ $v->status_regional }}</span>
                    </div>
                    <small class="text-muted d-block">{{ $penggunaRegional->nama ?? '-' }}</small>
                    @if($v->tanggal_regional)
                        <small class="text-muted d-block">{{ \Carbon\Carbon::parse($v->tanggal_regional)->format('d/m/Y H:i') }}</small>
                    @endif
                    @if($v->catatan_regional)
                        <small class="text-danger d-block mt-1">{{ $v->catatan_regional }}</small>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small">AFET Bandara Penerima</span>
                        <span class="badge {{ $vBadge($v->status_penerima) }}">{{ $v->status_penerima }}</span>
                    </div>
                    <small class="text-muted d-block">{{ $penggunaPenerima->nama ?? '-' }}</small>
                    @if($v->tanggal_penerima)
                        <small class="text-muted d-block">{{ \Carbon\Carbon::parse($v->tanggal_penerima)->format('d/m/Y H:i') }}</small>
                    @endif
                    @if($v->catatan_penerima)
                        <small class="text-danger d-block mt-1">{{ $v->catatan_penerima }}</small>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small">AFET Bandara Pemberi</span>
                        <span class="badge {{ $vBadge($v->status_pemberi) }}">{{ $v->status_pemberi }}</span>
                    </div>
                    <small class="text-muted d-block">{{ $penggunaPemberi->nama ?? '-' }}</small>
                    @if($v->tanggal_pemberi)
                        <small class="text-muted d-block">{{ \Carbon\Carbon::parse($v->tanggal_pemberi)->format('d/m/Y H:i') }}</small>
                    @endif
                    @if($v->catatan_pemberi)
                        <small class="text-danger d-block mt-1">{{ $v->catatan_pemberi }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

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
                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.peralatan-mutasi.approve-gm', $mutasi->id_pengajuan_mutasi) }}"
                          onsubmit="return confirm('Setujui mutasi alat ini dari sisi GM Pemberi?')">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Approve
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalRejectGm">
                        <i class="bi bi-x-lg me-1"></i> Reject
                    </button>
                </div>
            @else
                <div class="text-muted small">
                    <i class="bi bi-hourglass-split me-1"></i> Menunggu keputusan GM Bandara Pemberi.
                </div>
            @endif

        @elseif($mutasi->status === 'Waiting Konfirmasi CEO')
            @if($isCeo)
                <div class="alert alert-light border py-2 px-3 small mb-3">
                    Keputusan GM Pemberi:
                    <span class="badge {{ $mutasi->keputusan_gm === 'Approve' ? 'bg-success' : 'bg-danger' }}">
                        {{ $mutasi->keputusan_gm }}
                    </span>
                    @if($mutasi->catatan_gm)
                        <br>Catatan: {{ $mutasi->catatan_gm }}
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.peralatan-mutasi.teruskan-ceo', $mutasi->id_pengajuan_mutasi) }}"
                      onsubmit="return confirm('Teruskan keputusan GM Pemberi ini ke tahap berikutnya?')">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Teruskan Keputusan
                    </button>
                </form>
            @else
                <div class="text-muted small">
                    <i class="bi bi-hourglass-split me-1"></i> Menunggu CEO meneruskan keputusan GM Pemberi.
                </div>
            @endif

        @elseif($mutasi->status === 'Menunggu Pemastian Fasilitas Idle')
            @if($isAfetBandaraPemberi && ! $mutasi->id_pengguna_upload_ba_idle)
                <form method="POST" action="{{ route('admin.peralatan-mutasi.upload-ba-idle', $mutasi->id_pengajuan_mutasi) }}"
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
                <form method="POST" action="{{ route('admin.peralatan-mutasi.konfirmasi-idle', $mutasi->id_pengajuan_mutasi) }}"
                      onsubmit="return confirm('Konfirmasi fasilitas idle sudah sesuai?')">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Konfirmasi Fasilitas Idle
                    </button>
                </form>
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

        @elseif($mutasi->status === 'Siap Mobilisasi')
            @if($isAfetBandaraPenerima)
                <form method="POST" action="{{ route('admin.peralatan-mutasi.mobilisasi', $mutasi->id_pengajuan_mutasi) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Mobilisasi</label>
                        <textarea name="catatan_mobilisasi" class="form-control" rows="3"
                            placeholder="Catatan proses mobilisasi (opsional)..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dokumen Mobilisasi <span class="text-danger">*</span></label>
                        <input type="file" name="dokumen[]" class="form-control" multiple required
                            accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">Format PDF, JPG, PNG. Maksimal 10MB per file.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-truck me-1"></i> Selesaikan Mobilisasi
                    </button>
                </form>
            @else
                <div class="text-muted small">
                    <i class="bi bi-hourglass-split me-1"></i> Menunggu Admin AFET Bandara Penerima melaksanakan mobilisasi.
                </div>
            @endif

        @elseif($mutasi->status === 'Menunggu Verifikasi Mobilisasi')
            @php
                $v = $mutasi->verifikasiMobilisasi;
            @endphp
            <div class="row g-3">
                @if($isAfetRegional && $v && $v->status_regional === 'Pending')
                <div class="col-md-4">
                    <form method="POST" action="{{ route('admin.peralatan-mutasi.verifikasi-regional', $mutasi->id_pengajuan_mutasi) }}"
                          class="border rounded p-3 h-100">
                        @csrf
                        <p class="small fw-semibold mb-2">Verifikasi AFET Regional</p>
                        <div class="mb-2">
                            <select name="keputusan" class="form-select form-select-sm" required onchange="document.getElementById('catatanRegional').style.display = this.value === 'Tidak Sesuai' ? 'block' : 'none'">
                                <option value="Konfirmasi">Konfirmasi</option>
                                <option value="Tidak Sesuai">Tidak Sesuai</option>
                            </select>
                        </div>
                        <div id="catatanRegional" style="display:none" class="mb-2">
                            <textarea name="catatan" class="form-control form-control-sm" rows="2" placeholder="Alasan tidak sesuai..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Kirim Verifikasi</button>
                    </form>
                </div>
                @endif

                @if($isAfetBandaraPenerima && $v && $v->status_penerima === 'Pending')
                <div class="col-md-4">
                    <form method="POST" action="{{ route('admin.peralatan-mutasi.verifikasi-penerima', $mutasi->id_pengajuan_mutasi) }}"
                          class="border rounded p-3 h-100">
                        @csrf
                        <p class="small fw-semibold mb-2">Verifikasi AFET Bandara Penerima</p>
                        <div class="mb-2">
                            <select name="keputusan" class="form-select form-select-sm" required onchange="document.getElementById('catatanPenerima').style.display = this.value === 'Tidak Sesuai' ? 'block' : 'none'">
                                <option value="Konfirmasi">Konfirmasi</option>
                                <option value="Tidak Sesuai">Tidak Sesuai</option>
                            </select>
                        </div>
                        <div id="catatanPenerima" style="display:none" class="mb-2">
                            <textarea name="catatan" class="form-control form-control-sm" rows="2" placeholder="Alasan tidak sesuai..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Kirim Verifikasi</button>
                    </form>
                </div>
                @endif

                @if($isAfetBandaraPemberi && $v && $v->status_pemberi === 'Pending')
                <div class="col-md-4">
                    <form method="POST" action="{{ route('admin.peralatan-mutasi.verifikasi-pemberi', $mutasi->id_pengajuan_mutasi) }}"
                          class="border rounded p-3 h-100">
                        @csrf
                        <p class="small fw-semibold mb-2">Verifikasi AFET Bandara Pemberi</p>
                        <div class="mb-2">
                            <select name="keputusan" class="form-select form-select-sm" required onchange="document.getElementById('catatanPemberi').style.display = this.value === 'Tidak Sesuai' ? 'block' : 'none'">
                                <option value="Konfirmasi">Konfirmasi</option>
                                <option value="Tidak Sesuai">Tidak Sesuai</option>
                            </select>
                        </div>
                        <div id="catatanPemberi" style="display:none" class="mb-2">
                            <textarea name="catatan" class="form-control form-control-sm" rows="2" placeholder="Alasan tidak sesuai..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Kirim Verifikasi</button>
                    </form>
                </div>
                @endif

                @if(! (($isAfetRegional && $v && $v->status_regional === 'Pending') || ($isAfetBandaraPenerima && $v && $v->status_penerima === 'Pending') || ($isAfetBandaraPemberi && $v && $v->status_pemberi === 'Pending')))
                <div class="col-12">
                    <div class="text-muted small">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Menunggu pihak lain melengkapi verifikasi mobilisasi, atau Anda sudah mengirim verifikasi.
                    </div>
                </div>
                @endif
            </div>

        @elseif($mutasi->status === 'Menunggu Sertifikasi')
            @if($isAfetBandaraPenerima)
                @php
                    $lokasiPenerima = \App\Models\Lokasi::where('id_bandara', $mutasi->id_bandara_penerima)->get();
                @endphp
                <form method="POST" action="{{ route('admin.peralatan-mutasi.sertifikasi', $mutasi->id_pengajuan_mutasi) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lokasi Tujuan Akhir <span class="text-danger">*</span></label>
                        <select name="id_lokasi_tujuan" class="form-select" required>
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach($lokasiPenerima as $lok)
                                <option value="{{ $lok->id_lokasi }}">{{ $lok->nama_lokasi }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Lokasi harus berada di bandara penerima.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dokumen Sertifikasi <span class="text-danger">*</span></label>
                        <input type="file" name="dokumen[]" class="form-control" multiple required
                            accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">Format PDF, JPG, PNG. Maksimal 10MB per file.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-patch-check me-1"></i> Selesaikan Sertifikasi
                    </button>
                </form>
            @else
                <div class="text-muted small">
                    <i class="bi bi-hourglass-split me-1"></i> Menunggu Admin AFET Bandara Penerima melakukan sertifikasi.
                </div>
            @endif

        @elseif($mutasi->status === 'Selesai')
            <div class="text-success small">
                <i class="bi bi-check-circle me-1"></i> Proses mutasi sudah tuntas.
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

@endsection