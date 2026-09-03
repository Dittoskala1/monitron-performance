@extends('layouts.app')

@section('title', 'Detail Pengajuan Idle')
@section('page-title', 'Detail Pengajuan Idle')

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
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h5 class="fw-bold mb-1">{{ $pengajuan->alat->nama_alat ?? '-' }}</h5>
                <span class="text-muted small">
                    <i class="bi bi-hash me-1"></i>
                    Kode Alat: {{ $pengajuan->alat->kode_alat ?? '-' }}
                </span>
                @if($pengajuan->nomor_aset)
                <br>
                <span class="text-muted small">
                    <i class="bi bi-upc-scan me-1"></i>
                    Nomor Aset: {{ $pengajuan->nomor_aset }}
                </span>
                @endif
                <br>
                <span class="text-muted small">
                    <i class="bi bi-building me-1"></i>
                    <strong>Pemilik:</strong> {{ $pengajuan->alat->bandara->nama_bandara ?? '-' }}
                    ({{ $pengajuan->alat->bandara->kode_bandara ?? '-' }})
                </span>
                <br>
                <span class="text-muted small">
                    <i class="bi bi-geo-alt me-1"></i>
                    <strong>Lokasi Asal:</strong> {{ $pengajuan->lokasiAsal->nama_lokasi ?? '-' }}
                </span>
                <br>
                <span class="text-muted small">
                    <i class="bi bi-arrow-right me-1"></i>
                    <strong>Lokasi Saat Ini:</strong> {{ $pengajuan->alat->lokasi->nama_lokasi ?? '-' }}
                    @if($pengajuan->alat->id_bandara != $pengajuan->alat->lokasi->bandara->id_bandara)
                        <span class="badge bg-warning text-dark ms-1">
                            <i class="bi bi-people me-1"></i> Dipinjam
                        </span>
                    @endif

                    @if(in_array($pengajuan->status, ['Waiting Approval Dep Head', 'Waiting Approval Admin AFET']))
                        <span class="badge bg-secondary ms-1">
                            <i class="bi bi-clock-history me-1"></i> Belum Pindah ke Unused
                        </span>
                    @endif
                </span>
                @if($pengajuan->tanggal_terbit_alat)
                <br>
                <span class="text-muted small">
                    <i class="bi bi-calendar-event me-1"></i>
                    Tanggal Terbit: {{ \Carbon\Carbon::parse($pengajuan->tanggal_terbit_alat)->format('d F Y') }}
                </span>
                @endif
                <br>
                <span class="text-muted small">
                    <i class="bi bi-clipboard me-1"></i>
                    Kondisi:
                    <span class="badge {{ $pengajuan->kondisi_alat == 'Baik' ? 'bg-success' : 'bg-warning text-dark' }}">
                        {{ $pengajuan->kondisi_alat }}
                    </span>
                </span>
                @if($pengajuan->penjelasan_kondisi)
                <br>
                <span class="text-muted small">
                    <i class="bi bi-chat-left-text me-1"></i>
                    Penjelasan: {{ $pengajuan->penjelasan_kondisi }}
                </span>
                @endif
            </div>
            <div class="text-end">
                @php
                    $statusBadge = match($pengajuan->status) {
                        'Approved' => 'bg-success',
                        'Rejected' => 'bg-danger',
                        'Waiting Approval Admin AFET' => 'bg-info text-dark',
                        default => 'bg-warning text-dark', // Waiting Approval Dep Head
                    };
                @endphp
                <span class="badge fs-6 {{ $statusBadge }}">
                    {{ $pengajuan->status }}
                </span>
                <br>
                @if($pengajuan->status_ketersediaan)
                    <span class="badge fs-6 mt-1
                        {{ $pengajuan->status_ketersediaan == 'available' ? 'bg-success' : 
                           ($pengajuan->status_ketersediaan == 'booked' ? 'bg-danger' : 
                           ($pengajuan->status_ketersediaan == 'pending_booking' ? 'bg-warning text-dark' : 
                           ($pengajuan->status_ketersediaan == 'pending_approval' ? 'bg-info' : 
                           ($pengajuan->status_ketersediaan == 'not_available' ? 'bg-secondary' : 'bg-secondary')))) }}">
                        {{ ucfirst(str_replace('_', ' ', $pengajuan->status_ketersediaan)) }}
                    </span>
                @endif
            </div>
        </div>

        <hr class="mb-4">

        <div class="row g-4">
            <div class="col-md-6">
                <p class="text-muted small fw-semibold text-uppercase mb-3">Informasi Pengajuan</p>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Nomor Aset</dt>
                    <dd class="col-7">{{ $pengajuan->nomor_aset ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Lokasi Asal</dt>
                    <dd class="col-7">{{ $pengajuan->lokasiAsal->nama_lokasi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Lokasi Unused</dt>
                    <dd class="col-7">{{ $pengajuan->lokasiUnused->nama_lokasi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Detail Lokasi</dt>
                    <dd class="col-7">{{ $pengajuan->detail_lokasi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Tanggal Terbit Alat</dt>
                    <dd class="col-7">
                        {{ $pengajuan->tanggal_terbit_alat ? \Carbon\Carbon::parse($pengajuan->tanggal_terbit_alat)->format('d F Y') : '-' }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Kondisi Alat</dt>
                    <dd class="col-7">
                        <span class="badge {{ $pengajuan->kondisi_alat == 'Baik' ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $pengajuan->kondisi_alat }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Penjelasan Kondisi</dt>
                    <dd class="col-7">{{ $pengajuan->penjelasan_kondisi ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Pemohon</dt>
                    <dd class="col-7">{{ $pengajuan->pemohon->nama ?? '-' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Tanggal Pengajuan</dt>
                    <dd class="col-7">{{ \Carbon\Carbon::parse($pengajuan->tanggal_pengajuan)->format('d F Y H:i') }}</dd>

                    <dt class="col-5 text-muted fw-normal">Alasan Pengajuan</dt>
                    <dd class="col-7">{{ $pengajuan->alasan_idle ?? '-' }}</dd>
                </dl>
            </div>

            <div class="col-md-6">
                <p class="text-muted small fw-semibold text-uppercase mb-3">Status Keputusan</p>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">
                        Disetujui {{ optional($pengajuan->approverDepHead)->role === 'div_head' ? 'Div Head' : 'Dep Head' }}
                    </dt>
                    <dd class="col-7">
                        {{ $pengajuan->approverDepHead->nama ?? '-' }}
                        @if($pengajuan->tanggal_approval_dep_head)
                            <br><small class="text-muted">{{ \Carbon\Carbon::parse($pengajuan->tanggal_approval_dep_head)->format('d F Y H:i') }}</small>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Status Ketersediaan</dt>
                    <dd class="col-7">
                        @if($pengajuan->status_ketersediaan)
                            <span class="badge 
                                {{ $pengajuan->status_ketersediaan == 'available' ? 'bg-success' : 
                                   ($pengajuan->status_ketersediaan == 'booked' ? 'bg-danger' : 
                                   ($pengajuan->status_ketersediaan == 'pending_booking' ? 'bg-warning text-dark' : 
                                   ($pengajuan->status_ketersediaan == 'pending_approval' ? 'bg-info' : 
                                   ($pengajuan->status_ketersediaan == 'not_available' ? 'bg-secondary' : 'bg-secondary')))) }}">
                                {{ ucfirst(str_replace('_', ' ', $pengajuan->status_ketersediaan)) }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Tanggal Keputusan Final</dt>
                    <dd class="col-7">
                        {{ $pengajuan->tanggal_keputusan ? \Carbon\Carbon::parse($pengajuan->tanggal_keputusan)->format('d F Y H:i') : '-' }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Disetujui Admin AFET</dt>
                    <dd class="col-7">{{ $pengajuan->approver->nama ?? '-' }}</dd>

                    @if($pengajuan->status == 'Rejected')
                    <dt class="col-5 text-muted fw-normal">Alasan Ditolak</dt>
                    <dd class="col-7 text-danger">{{ $pengajuan->alasan_reject }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <hr class="my-4">

        <p class="text-muted small fw-semibold text-uppercase mb-3">Dokumen Pendukung</p>

        @if($pengajuan->dokumen->isEmpty())
            <p class="text-muted small">Tidak ada dokumen yang dilampirkan.</p>
        @else
            <div class="row g-2 mb-2">
                @foreach($pengajuan->dokumen as $dok)
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
                            <span class="small text-truncate" title="{{ $dok->nama_file }}">
                                {{ $dok->nama_file }}
                            </span>
                        </div>
                        <div class="d-flex gap-1 ms-2 flex-shrink-0">
                            <a href="{{ route('admin.peralatan-idle.download-dokumen', $dok->id_dokumen) }}"
                               target="_blank"
                               class="btn btn-sm btn-outline-primary"
                               title="Lihat / Unduh">
                                <i class="bi bi-eye"></i>
                            </a>

                            @if(in_array($pengajuan->status, ['Waiting Approval Dep Head', 'Waiting Approval Admin AFET']))
                            <form method="POST"
                                  action="{{ route('admin.peralatan-idle.hapus-dokumen', [$pengajuan->id_pengajuan, $dok->id_dokumen]) }}"
                                  onsubmit="return confirm('Hapus dokumen \'{{ $dok->nama_file }}\'?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

        <hr class="my-4">

        @php
            $role = session('pengguna.role');
            $idBandara = session('pengguna.id_bandara');
            $idLokasi = session('pengguna.id_lokasi');

            $isPemilikAlat = $idBandara == ($pengajuan->alat->id_bandara ?? null) 
                            && $idLokasi == ($pengajuan->alat->id_lokasi ?? null);

            // ⚠️ DIUBAH: $isDepHeadBerwenang dikirim dari controller (bukan dihitung
            // di sini) karena butuh cek cakupan unit kerja (alatMasukCakupanUnit()).

            $isAfetRegionalBerwenang = $role === 'afet_regional'
                && $pengajuan->status === 'Waiting Approval Admin AFET';

            $lokasiAlatSesuai = ($pengajuan->alat->id_lokasi ?? null) == $pengajuan->id_lokasi_asal;

            $canTarikKembali = (
                $pengajuan->status == 'Approved' && 
                $pengajuan->status_ketersediaan == 'available' && 
                $pengajuan->alat && 
                $pengajuan->alat->id_lokasi == $pengajuan->id_lokasi_unused &&
                $role == 'afet_bandara' &&
                $idBandara == ($pengajuan->alat->id_bandara ?? null)
            );
        @endphp

        @if($tahap1Stuck ?? false)
            <div class="alert alert-danger py-2 px-3 small mb-3">
                <i class="bi bi-exclamation-octagon me-1"></i>
                Bandara ini belum punya akun <strong>Dep Head</strong> maupun <strong>Div Head</strong>, jadi pengajuan ini
                belum bisa diproses siapapun. Hubungi Admin AFET Regional untuk membuatkan salah satu akun tersebut
                terlebih dulu.
            </div>
        @endif

        @if(in_array($pengajuan->status, ['Waiting Approval Dep Head', 'Waiting Approval Admin AFET']))
            @if($isDepHeadBerwenang || $isAfetRegionalBerwenang)

                @unless($lokasiAlatSesuai)
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Lokasi alat saat ini (<strong>{{ $pengajuan->alat->lokasi->nama_lokasi ?? '-' }}</strong>)
                        sudah berbeda dari lokasi asal saat pengajuan dibuat
                        (<strong>{{ $pengajuan->lokasiAsal->nama_lokasi ?? '-' }}</strong>).
                        Approval kemungkinan akan ditolak sistem sampai data lokasi diperiksa kembali.
                    </div>
                @endunless

                <div class="text-muted small mb-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Alat masih berada di lokasi asal saat ini. Perpindahan ke lokasi Unused hanya terjadi
                    otomatis setelah pengajuan disetujui penuh (kedua tahap: Dep Head &amp; Admin AFET).
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.peralatan-idle.approve', $pengajuan->id_pengajuan) }}">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i>
                            @if($pengajuan->status === 'Waiting Approval Dep Head')
                                Approve ({{ $approverTahap1Role === 'div_head' ? 'Div Head' : 'Dep Head' }})
                            @else
                                Approve Final (Admin AFET)
                            @endif
                        </button>
                    </form>

                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalReject">
                        <i class="bi bi-x-lg me-1"></i> Reject
                    </button>
                </div>
            @else
                <div class="text-muted small">
                    <i class="bi bi-hourglass-split me-1"></i>
                    @if($pengajuan->status === 'Waiting Approval Dep Head')
                        Menunggu keputusan dari {{ $approverTahap1Role === 'div_head' ? 'Div Head' : 'Dep Head' }} yang berwenang.
                    @else
                        Menunggu keputusan dari Admin AFET Regional.
                    @endif
                    <br>
                    <i class="bi bi-info-circle me-1"></i>
                    Alat masih berada di lokasi asal (belum dipindahkan ke Unused). Alat akan otomatis
                    dipindahkan ke Unused setelah pengajuan disetujui penuh oleh Dep Head dan Admin AFET Regional.
                </div>
            @endif

        @elseif($pengajuan->status == 'Rejected')
            @if(session('pengguna.id') == $pengajuan->id_pengguna)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjukanUlang">
                    <i class="bi bi-arrow-repeat me-1"></i> Ajukan Ulang
                </button>
            @else
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Pengajuan ini ditolak. Hanya pemohon yang bisa mengajukan ulang.
                </div>
            @endif

        @elseif($pengajuan->status == 'Approved')
            <div class="d-flex gap-2 flex-wrap">
                @if($pengajuan->status_ketersediaan == 'available' && !$isPemilikAlat)
                <form action="{{ route('admin.peralatan-booking.store') }}" method="POST"
                        onsubmit="return confirm('Booking alat {{ $pengajuan->alat->nama_alat }}?')">
                    @csrf
                    <input type="hidden" name="id_pengajuan_idle" value="{{ $pengajuan->id_pengajuan }}">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-cart-plus me-1"></i> Booking
                    </button>
                </form>
                @endif

                @if($canTarikKembali)
                    <form action="{{ route('admin.peralatan-idle.tarik-kembali', $pengajuan->id_pengajuan) }}" 
                          method="POST" 
                          class="d-inline"
                          onsubmit="return confirm('Yakin ingin menarik kembali alat {{ $pengajuan->alat->nama_alat ?? '' }} dari Unused?');">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-arrow-return-left me-1"></i> Tarik Kembali dari Unused
                        </button>
                    </form>
                @elseif($pengajuan->status_ketersediaan == 'booked')
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Alat sedang di-booking, tidak bisa ditarik kembali.
                    </div>
                @elseif($pengajuan->alat && $pengajuan->alat->id_lokasi != $pengajuan->id_lokasi_unused)
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Alat sudah tidak berada di Unused.
                    </div>
                @elseif($isPemilikAlat && $pengajuan->status_ketersediaan == 'available')
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Anda adalah pemilik alat ini. Gunakan tombol "Tarik Kembali" jika ingin mengambil alat.
                    </div>
                @endif
            </div>
        @endif

    </div>
</div>

<div class="d-flex gap-2">
    <a href="{{ route('admin.peralatan-idle.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i> Kembali
    </a>
</div>

<div class="modal fade" id="modalReject" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.peralatan-idle.reject', $pengajuan->id_pengajuan) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tolak Pengajuan Idle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="alasan_reject" class="form-control" rows="4" required
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

<div class="modal fade" id="modalAjukanUlang" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST"
                  action="{{ route('admin.peralatan-idle.update', $pengajuan->id_pengajuan) }}"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Ajukan Ulang Idle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor Aset <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_aset" class="form-control" required
                               value="{{ $pengajuan->nomor_aset }}"
                               placeholder="Contoh: AST-2026-00123">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detail Lokasi</label>
                        <input type="text" name="detail_lokasi" class="form-control"
                               value="{{ $pengajuan->detail_lokasi }}"
                               placeholder="Contoh: Rak 3 Gudang Belakang">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Terbit Alat <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_terbit_alat" class="form-control" required
                               value="{{ $pengajuan->tanggal_terbit_alat ? \Carbon\Carbon::parse($pengajuan->tanggal_terbit_alat)->format('Y-m-d') : '' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kondisi Alat <span class="text-danger">*</span></label>
                        <select name="kondisi_alat" class="form-select" required>
                            @foreach(['Baik', 'Improvement'] as $kondisi)
                                <option value="{{ $kondisi }}" {{ $pengajuan->kondisi_alat == $kondisi ? 'selected' : '' }}>
                                    {{ $kondisi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Penjelasan Kondisi</label>
                        <textarea name="penjelasan_kondisi" class="form-control" rows="2">{{ $pengajuan->penjelasan_kondisi }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan / Catatan Pengajuan</label>
                        <textarea name="alasan_idle" class="form-control" rows="3">{{ $pengajuan->alasan_idle }}</textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Tambah Dokumen Pendukung</label>
                        <input type="file" name="dokumen[]" class="form-control" multiple
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        <div class="form-text">Dokumen lama tetap tersimpan. Format: PDF, JPG, PNG, Word, Excel. Maks 10MB/file.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Ajukan Ulang</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection