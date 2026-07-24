{{--
    resources/views/components/notifikasi-navbar.blade.php
    Sertakan di navbar utama: <x-notifikasi-navbar />
--}}
<div class="nav-item dropdown" id="notifikasiDropdown">
    <a class="nav-link position-relative px-3" href="#" data-bs-toggle="dropdown" id="notifToggle">
        <i class="fas fa-bell fs-5"></i>
        <span id="notif-badge"
              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="display:none; font-size:.6rem;">
            0
        </span>
    </a>

    <div class="dropdown-menu dropdown-menu-end shadow" style="min-width:360px; max-height:480px; overflow-y:auto;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="fw-semibold">Notifikasi</span>
            <a href="{{ route('admin.notifikasi.index') }}" class="text-decoration-none small text-primary">Lihat Semua</a>
        </div>

        <div id="notif-list">
            <div class="text-center py-4 text-muted small">
                <i class="fas fa-spinner fa-spin me-1"></i> Memuat...
            </div>
        </div>

        <div class="border-top px-3 py-2 text-center">
            <form action="{{ route('admin.notifikasi.baca-semua') }}" method="POST" id="formBacaSemua">
                @csrf
                <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0">
                    <i class="fas fa-check-double me-1"></i> Tandai semua dibaca
                </button>
            </form>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    'use strict';

    const POLL_INTERVAL = 30_000; // 30 detik
    const urlTerbaru    = "{{ route('admin.notifikasi.api.terbaru') }}";
    const urlJumlah     = "{{ route('admin.notifikasi.api.jumlah') }}";

    const badge    = document.getElementById('notif-badge');
    const listEl   = document.getElementById('notif-list');
    const dropdown = document.getElementById('notifToggle');

    // ── Ambil & render notifikasi ────────────────────────────────────────────
    async function muatNotifikasi() {
        try {
            const res  = await fetch(urlTerbaru, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            renderBadge(json.jumlah);
            renderList(json.data);
        } catch (e) {
            console.warn('Gagal memuat notifikasi:', e);
        }
    }

    function renderBadge(jumlah) {
        if (jumlah > 0) {
            badge.textContent = jumlah > 99 ? '99+' : jumlah;
            badge.style.display = '';
            // Animasi getaran saat ada yang kritis
            badge.classList.add('animate-pulse');
        } else {
            badge.style.display = 'none';
        }
    }

    function renderList(data) {
        if (!data.length) {
            listEl.innerHTML = `
                <div class="text-center py-4 text-muted small">
                    <i class="fas fa-bell-slash opacity-50 d-block mb-2 fs-5"></i>
                    Tidak ada notifikasi baru
                </div>`;
            return;
        }

        listEl.innerHTML = data.map(n => `
            <div class="notif-item-navbar d-flex align-items-start px-3 py-2 border-bottom ${n.prioritas === 'kritis' ? 'bg-danger bg-opacity-10' : ''}">
                <span class="badge bg-${n.warna} me-2 mt-1 p-2 rounded-circle flex-shrink-0" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas ${n.ikon} small"></i>
                </span>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-semibold small text-dark text-truncate">${n.judul}</div>
                    <div class="text-muted" style="font-size:.78rem;">${n.pesan}</div>
                    <div class="text-muted" style="font-size:.72rem;">${n.alat ?? ''} ${n.bandara ? '&mdash; ' + n.bandara : ''}</div>
                    <div class="text-muted" style="font-size:.70rem;">${n.tanggal}</div>
                </div>
                <form action="${n.url_baca}" method="POST" class="ms-1 flex-shrink-0">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <button type="submit" class="btn btn-sm p-1 text-success border-0 bg-transparent" title="Tandai dibaca">
                        <i class="fas fa-check small"></i>
                    </button>
                </form>
            </div>
        `).join('');
    }

    // ── Broadcast (Pusher / Laravel Echo) ───────────────────────────────────
    if (typeof window.Echo !== 'undefined') {
        window.Echo.channel('notifikasi')
            .listen('.alat.baru',   () => muatNotifikasi())
            .listen('.alat.status', () => muatNotifikasi());
    }

    // ── Polling fallback ─────────────────────────────────────────────────────
    muatNotifikasi();
    setInterval(muatNotifikasi, POLL_INTERVAL);

    // Refresh saat dropdown dibuka
    dropdown.addEventListener('show.bs.dropdown', muatNotifikasi);
})();
</script>
<style>
@keyframes pulse { 0%,100%{transform:translate(-50%,-50%) scale(1)} 50%{transform:translate(-50%,-50%) scale(1.3)} }
.animate-pulse { animation: pulse 1s ease-in-out 3; }
.notif-item-navbar:hover { background-color: rgba(0,0,0,.03); }
</style>
@endpush
@endonce