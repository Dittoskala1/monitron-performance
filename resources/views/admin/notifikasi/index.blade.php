{{-- resources/views/admin/notifikasi/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Notifikasi Sistem')

@section('content')
<div class="container-fluid">

    {{-- ── Header ── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-bell me-2 text-primary"></i> Notifikasi Sistem
            </h4>
            @if($jumlahBelumDibaca > 0)
                <small class="text-muted">{{ $jumlahBelumDibaca }} belum dibaca</small>
            @else
                <small class="text-muted">Semua notifikasi sudah dibaca</small>
            @endif
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.notifikasi.baca-semua') }}" method="POST">
                @csrf
                @if(request('id_bandara'))
                    <input type="hidden" name="id_bandara" value="{{ request('id_bandara') }}">
                @endif
                <button type="submit" class="btn btn-outline-primary btn-sm"
                    onclick="return confirm('Tandai semua notifikasi sebagai dibaca?')"
                    @disabled($jumlahBelumDibaca === 0)>
                    <i class="fas fa-check-double me-1"></i> Baca Semua
                </button>
            </form>
        </div>
    </div>

    {{-- ── Alert ── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Filter ── --}}
    <div class="card shadow-sm mb-4 border-0" style="border-radius: 12px;">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.notifikasi.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted">Jenis</label>
                    <select name="jenis" class="form-select form-select-sm">
                        <option value="">Semua Jenis</option>
                        @foreach($jenisOptions as $key => $label)
                            <option value="{{ $key }}" @selected(request('jenis') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="Belum Dibaca" @selected(request('status') === 'Belum Dibaca')>Belum Dibaca</option>
                        <option value="Dibaca"       @selected(request('status') === 'Dibaca')>Dibaca</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('admin.notifikasi.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── List Notifikasi (card style, klik untuk detail) ── --}}
    <div class="d-flex flex-column gap-2">
        @forelse($notifikasi as $notif)
            @php
                $metaJson = htmlspecialchars(json_encode($notif->meta ?? []), ENT_QUOTES, 'UTF-8');
            @endphp
            <div class="card notif-card shadow-sm border-0 {{ $notif->status === 'Belum Dibaca' ? 'notif-unread' : '' }}"
                 style="cursor:pointer; border-radius: 12px; transition: box-shadow .15s ease, transform .15s ease;"
                 data-id="{{ $notif->id }}"
                 data-judul="{{ $notif->judul }}"
                 data-pesan="{{ $notif->pesan }}"
                 data-jenis="{{ $notif->label_jenis }}"
                 data-prioritas="{{ ucfirst($notif->prioritas) }}"
                 data-warna="{{ $notif->warna_prioritas }}"
                 data-ikon="{{ $notif->ikon }}"
                 data-tanggal="{{ $notif->tanggal->format('d F Y, H:i') }}"
                 data-tanggal-relatif="{{ $notif->tanggal->diffForHumans() }}"
                 data-alat="{{ optional($notif->alat)->nama_alat }}"
                 data-bandara="{{ optional(optional(optional($notif->alat)->lokasi)->bandara)->nama_bandara }}"
                 data-status="{{ $notif->status }}"
                 data-meta="{{ $metaJson }}"
                 data-url-baca="{{ route('admin.notifikasi.baca', $notif->id) }}"
                 onclick="bukaDetailNotifikasi(this)">
                <div class="card-body d-flex align-items-start gap-3 py-3">

                    <span class="d-flex align-items-center justify-content-center bg-{{ $notif->warna_prioritas }} bg-opacity-10 text-{{ $notif->warna_prioritas }} flex-shrink-0"
                          style="width:44px;height:44px;border-radius:50%;">
                        <i class="fas {{ $notif->ikon }}"></i>
                    </span>

                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <span class="fw-semibold {{ $notif->status === 'Belum Dibaca' ? 'text-dark' : 'text-muted' }} text-truncate">
                                {{ $notif->judul }}
                                @if($notif->status === 'Belum Dibaca')
                                    <span class="dot-unread ms-1"></span>
                                @endif
                            </span>
                            <small class="text-muted text-nowrap">{{ $notif->tanggal->diffForHumans() }}</small>
                        </div>
                        <p class="mb-1 mt-1 text-muted small text-truncate">{{ $notif->pesan }}</p>
                        <div class="d-flex gap-1 flex-wrap">
                            <span class="badge bg-{{ $notif->warna_prioritas }} bg-opacity-75">{{ ucfirst($notif->prioritas) }}</span>
                            <span class="badge bg-light text-dark border">{{ $notif->label_jenis }}</span>
                            @if($notif->alat)
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-microchip me-1"></i>{{ $notif->alat->nama_alat }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Aksi cepat, klik tidak ikut buka modal --}}
                    <div class="d-flex gap-1 flex-shrink-0" onclick="event.stopPropagation()">
                        <form action="{{ route('admin.notifikasi.hapus', $notif->id) }}" method="POST"
                              onsubmit="return confirm('Hapus notifikasi ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 text-muted">
                <i class="fas fa-bell-slash fa-3x mb-3 opacity-25"></i>
                <p>Tidak ada notifikasi ditemukan.</p>
            </div>
        @endforelse
    </div>

    @if($notifikasi->hasPages())
        <div class="mt-3">
            {{ $notifikasi->links() }}
        </div>
    @endif

</div>

{{-- ── Modal Detail Notifikasi (1 modal dipakai ulang untuk semua item) ── --}}
<div class="modal fade" id="modalDetailNotifikasi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span id="dn-ikon-wrap" class="d-flex align-items-center justify-content-center flex-shrink-0"
                          style="width:52px;height:52px;border-radius:50%;">
                        <i id="dn-ikon" class="fas"></i>
                    </span>
                    <div>
                        <h5 id="dn-judul" class="mb-1 fw-bold"></h5>
                        <div class="d-flex gap-1">
                            <span id="dn-prioritas" class="badge"></span>
                            <span id="dn-jenis" class="badge bg-light text-dark border"></span>
                        </div>
                    </div>
                </div>

                <p id="dn-pesan" class="mb-3"></p>

                <div class="border rounded-3 p-3 bg-light small" id="dn-info-tambahan">
                    <div class="d-flex justify-content-between py-1" id="dn-row-alat" style="display:none !important;">
                        <span class="text-muted">Alat</span>
                        <span id="dn-alat" class="fw-semibold text-end"></span>
                    </div>
                    <div class="d-flex justify-content-between py-1" id="dn-row-bandara" style="display:none !important;">
                        <span class="text-muted">Bandara</span>
                        <span id="dn-bandara" class="fw-semibold text-end"></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Waktu</span>
                        <span id="dn-tanggal" class="fw-semibold text-end"></span>
                    </div>
                    <div id="dn-meta-extra"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
.notif-card:hover {
    box-shadow: 0 4px 14px rgba(0,0,0,0.08) !important;
    transform: translateY(-1px);
}
.notif-unread {
    background: #f5f8ff;
    border-left: 3px solid #3b82f6 !important;
}
.dot-unread {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #3b82f6;
    vertical-align: middle;
}
</style>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';

// Label ramah untuk key meta yang umum dipakai (sisanya ditampilkan apa adanya)
const LABEL_META = {
    pemohon: 'Pemohon',
    approver: 'Diproses Oleh',
    status: 'Status',
    alasan: 'Alasan',
    nama_alat: 'Nama Alat',
    waktu: 'Waktu Kejadian',
    lokasi: 'Lokasi',
    bandara: 'Bandara',
    performa: 'Performa (%)',
};

function bukaDetailNotifikasi(el) {
    const data = el.dataset;

    document.getElementById('dn-judul').textContent = data.judul;
    document.getElementById('dn-pesan').textContent = data.pesan;
    document.getElementById('dn-tanggal').textContent = data.tanggal;

    const ikonEl = document.getElementById('dn-ikon');
    ikonEl.className = 'fas ' + data.ikon;

    const ikonWrap = document.getElementById('dn-ikon-wrap');
    ikonWrap.className = 'd-flex align-items-center justify-content-center flex-shrink-0 bg-' + data.warna + ' bg-opacity-10 text-' + data.warna;
    ikonWrap.style.width = '52px';
    ikonWrap.style.height = '52px';
    ikonWrap.style.borderRadius = '50%';

    const prioritasEl = document.getElementById('dn-prioritas');
    prioritasEl.textContent = data.prioritas;
    prioritasEl.className = 'badge bg-' + data.warna;

    document.getElementById('dn-jenis').textContent = data.jenis;

    const rowAlat = document.getElementById('dn-row-alat');
    if (data.alat) {
        document.getElementById('dn-alat').textContent = data.alat;
        rowAlat.style.setProperty('display', 'flex', 'important');
    } else {
        rowAlat.style.setProperty('display', 'none', 'important');
    }

    const rowBandara = document.getElementById('dn-row-bandara');
    if (data.bandara) {
        document.getElementById('dn-bandara').textContent = data.bandara;
        rowBandara.style.setProperty('display', 'flex', 'important');
    } else {
        rowBandara.style.setProperty('display', 'none', 'important');
    }

    // Info tambahan dari meta (alasan, pemohon, approver, dll — selain yang sudah ditampilkan di atas)
    const metaExtra = document.getElementById('dn-meta-extra');
    metaExtra.innerHTML = '';
    try {
        const meta = JSON.parse(data.meta || '{}');
        const skipKeys = ['nama_alat', 'bandara', 'waktu'];
        Object.keys(meta).forEach(key => {
            if (skipKeys.includes(key) || meta[key] === null || meta[key] === '') return;
            const label = LABEL_META[key] || (key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' '));
            const row = document.createElement('div');
            row.className = 'd-flex justify-content-between py-1';
            row.innerHTML = `<span class="text-muted">${label}</span><span class="fw-semibold text-end ms-2">${meta[key]}</span>`;
            metaExtra.appendChild(row);
        });
    } catch (e) {
        // meta tidak valid, abaikan
    }

    // Tandai dibaca otomatis (tanpa reload) kalau masih "Belum Dibaca"
    if (data.status === 'Belum Dibaca') {
        fetch(data.urlBaca, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            }
        }).then(() => {
            el.classList.remove('notif-unread');
            el.dataset.status = 'Dibaca';
            const dot = el.querySelector('.dot-unread');
            if (dot) dot.remove();
        }).catch(() => {});
    }

    new bootstrap.Modal(document.getElementById('modalDetailNotifikasi')).show();
}
</script>
@endsection