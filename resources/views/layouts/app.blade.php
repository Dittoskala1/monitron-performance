<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Monitoring Alat')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ============================================================
           DESIGN TOKENS — diselaraskan dengan palet TailAdmin
           ============================================================ */
        :root {
            --brand-25:  #f2f7ff;
            --brand-50:  #ecf3ff;
            --brand-100: #dde9ff;
            --brand-400: #7592ff;
            --brand-500: #465fff;
            --brand-600: #3641f5;

            --gray-25:  #fcfcfd;
            --gray-50:  #f9fafb;
            --gray-100: #f2f4f7;
            --gray-200: #e4e7ec;
            --gray-300: #d0d5dd;
            --gray-400: #98a2b3;
            --gray-500: #667085;
            --gray-600: #475467;
            --gray-700: #344054;
            --gray-800: #1d2939;
            --gray-900: #101828;

            --success-50: #ecfdf3;
            --success-500: #12b76a;
            --success-600: #039855;
            --success-700: #027a48;

            --warning-50: #fffaeb;
            --warning-500: #f79009;
            --warning-600: #dc6803;
            --warning-700: #b54708;

            --error-50: #fef3f2;
            --error-500: #f04438;
            --error-600: #d92d20;
            --error-700: #b42318;

            --shadow-xs: 0px 1px 2px 0px rgba(16,24,40,0.05);
            --shadow-sm: 0px 1px 3px 0px rgba(16,24,40,0.1), 0px 1px 2px 0px rgba(16,24,40,0.06);
            --shadow-md: 0px 4px 8px -2px rgba(16,24,40,0.1), 0px 2px 4px -2px rgba(16,24,40,0.06);
            --shadow-lg: 0px 12px 16px -4px rgba(16,24,40,0.08), 0px 4px 6px -2px rgba(16,24,40,0.03);

            --sidebar-w: 260px;
            --sidebar-w-collapsed: 84px;
            --topbar-h: 72px;
        }

        * { box-sizing: border-box; }
        body {
            background: var(--gray-50);
            margin: 0;
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
            color: var(--gray-700);
        }
        a { text-decoration: none; }

        /* ============================================================
           SIDEBAR
           ============================================================ */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid var(--gray-200);
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1040;
            transition: width .25s ease, transform .25s ease;
        }

        .sidebar-logo {
            padding: 20px 16px;
            border-bottom: 1px solid var(--gray-100);
        }
        .sidebar-logo-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-logo-icon {
            width: 38px;
            height: 38px;
            background: var(--brand-50);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-logo-icon i {
            color: var(--brand-500);
            font-size: 17px;
        }
        .sidebar-brand {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--gray-900);
            letter-spacing: .2px;
            line-height: 1.3;
            white-space: nowrap;
        }
        .sidebar-sub {
            font-size: 11px;
            color: var(--gray-400);
            margin-top: 1px;
            white-space: nowrap;
        }

        .sidebar-nav {
            padding: 12px 0 8px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 4px; }

        .sb-section {
            padding: 16px 18px 6px;
            font-size: 10.5px;
            font-weight: 700;
            color: var(--gray-400);
            letter-spacing: .8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .sb-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 2px 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--gray-600);
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            transition: background .15s, color .15s;
        }
        .sb-item:hover {
            color: var(--gray-800);
            background: var(--gray-100);
            text-decoration: none;
        }
        .sb-item.active {
            color: var(--brand-500);
            background: var(--brand-50);
        }
        .sb-item i {
            font-size: 16px;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            color: var(--gray-400);
        }
        .sb-item:hover i { color: var(--gray-600); }
        .sb-item.active i { color: var(--brand-500); }

        .sb-badge {
            margin-left: auto;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
            padding: 2px 8px;
            border-radius: 20px;
            background: var(--brand-50);
            color: var(--brand-500);
        }
        .sb-item.active .sb-badge { background: var(--brand-100); }

        .sb-logout {
            margin-top: auto;
            padding: 14px 16px;
            border-top: 1px solid var(--gray-100);
        }
        .sb-logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--error-500);
            font-size: 13.5px;
            font-weight: 500;
            background: none;
            border: none;
            padding: 9px 12px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            white-space: nowrap;
        }
        .sb-logout-btn:hover { background: var(--error-50); }
        .sb-logout-btn i { font-size: 15px; flex-shrink: 0; }

        /* Collapsed (desktop, icon-only) state */
        body.sidebar-collapsed .sidebar { width: var(--sidebar-w-collapsed); }
        body.sidebar-collapsed .sidebar-brand,
        body.sidebar-collapsed .sidebar-sub,
        body.sidebar-collapsed .sb-section,
        body.sidebar-collapsed .sb-item span,
        body.sidebar-collapsed .sb-badge,
        body.sidebar-collapsed .sb-logout-btn span {
            display: none;
        }
        body.sidebar-collapsed .sb-item { justify-content: center; }
        body.sidebar-collapsed .sb-logout-btn { justify-content: center; }
        body.sidebar-collapsed .sidebar-logo-row { justify-content: center; }
        body.sidebar-collapsed .main-content { margin-left: var(--sidebar-w-collapsed); }

        /* ============================================================
           MAIN / TOPBAR
           ============================================================ */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left .25s ease;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
            padding: 0 22px;
            height: var(--topbar-h);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .sidebar-toggle-btn {
            width: 38px; height: 38px;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            color: var(--gray-500);
            cursor: pointer;
            flex-shrink: 0;
        }
        .sidebar-toggle-btn:hover { background: var(--gray-100); color: var(--gray-700); }
        .topbar-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-900);
        }
        .topbar-right { display: flex; align-items: center; gap: 10px; }

        .icon-btn {
            position: relative;
            width: 40px; height: 40px;
            border-radius: 50%;
            border: 1px solid var(--gray-200);
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            color: var(--gray-500);
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }
        .icon-btn:hover { background: var(--gray-100); color: var(--gray-700); }
        .notif-dot {
            position: absolute;
            top: 8px; right: 9px;
            width: 8px; height: 8px;
            background: var(--warning-500);
            border-radius: 50%;
            box-shadow: 0 0 0 2px #fff;
        }

        /* Dropdown panels (built on Bootstrap's dropdown JS) */
        .no-caret::after { display: none !important; }
        .dropdown-menu.panel {
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 14px;
            margin-top: 10px !important;
        }
        .notif-panel { width: 360px; }
        .notif-panel-hd {
            display: flex; justify-content: space-between; align-items: center;
            padding-bottom: 12px; margin-bottom: 6px;
            border-bottom: 1px solid var(--gray-100);
        }
        .notif-panel-title { font-size: 15px; font-weight: 700; color: var(--gray-900); }
        .notif-panel-count {
            font-size: 10.5px; font-weight: 700; color: var(--error-600);
            background: var(--error-50); padding: 2px 9px; border-radius: 20px;
        }
        .notif-list { max-height: 340px; overflow-y: auto; }
        .notif-list-item {
            display: flex; gap: 10px; padding: 10px; border-radius: 10px;
            color: inherit; text-decoration: none;
        }
        .notif-list-item:hover { background: var(--gray-100); color: inherit; }
        .notif-ic {
            width: 38px; height: 38px; border-radius: 50%;
            background: var(--brand-50); color: var(--brand-500);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 15px;
        }
        .notif-txt-title { font-size: 12.5px; font-weight: 600; color: var(--gray-800); margin-bottom: 2px; }
        .notif-txt-msg { font-size: 12px; color: var(--gray-500); margin-bottom: 3px; line-height: 1.4; }
        .notif-txt-time { font-size: 11px; color: var(--gray-400); }
        .notif-viewall {
            display: block; text-align: center; margin-top: 10px; padding: 9px;
            border-radius: 8px; border: 1px solid var(--gray-200);
            font-size: 12.5px; font-weight: 600; color: var(--gray-700);
        }
        .notif-viewall:hover { background: var(--gray-50); color: var(--gray-800); }
        .notif-empty { text-align: center; padding: 30px 10px; color: var(--gray-400); }
        .notif-empty i { font-size: 26px; }

        .user-btn {
            display: flex; align-items: center; gap: 8px;
            background: none; border: none;
            padding: 4px 10px 4px 4px;
            border-radius: 24px;
            cursor: pointer;
        }
        .user-btn:hover { background: var(--gray-100); }
        .user-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--brand-500); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
            flex-shrink: 0;
        }
        .user-name { font-size: 13px; font-weight: 600; color: var(--gray-700); }
        .user-panel { width: 250px; }
        .user-panel-name { font-size: 13.5px; font-weight: 700; color: var(--gray-800); }
        .user-panel-email { font-size: 11.5px; color: var(--gray-400); margin-top: 2px; }
        .user-panel-role {
            display: inline-block; margin-top: 8px;
            font-size: 10.5px; font-weight: 600; text-transform: capitalize;
            padding: 3px 10px; border-radius: 20px;
            background: var(--brand-50); color: var(--brand-500);
        }
        .user-panel-logout {
            display: flex; align-items: center; gap: 8px; width: 100%;
            border: none; background: none;
            padding: 9px 10px; border-radius: 8px;
            color: var(--error-500); font-size: 13px; font-weight: 500;
            margin-top: 12px; padding-top: 12px;
            border-top: 1px solid var(--gray-100);
            cursor: pointer;
        }
        .user-panel-logout:hover { background: var(--error-50); }

        .page-content {
            padding: 22px;
            flex: 1;
        }

        /* ============================================================
           SHARED CARD / STAT / TABLE TOKENS (dipakai halaman lain juga)
           ============================================================ */
        .dash-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            box-shadow: var(--shadow-xs);
        }
        .dash-card .card-body { padding: 16px 18px; }

        .stat-card { border-radius: 16px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-xs); }
        .stat-label { font-size: 11px; color: var(--gray-400); margin-bottom: 5px; }
        .stat-value { font-size: 26px; font-weight: 700; line-height: 1; color: var(--gray-900); }
        .stat-sub { font-size: 11px; color: var(--gray-400); margin-top: 4px; }
        .stat-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }

        .pagination { font-size: 0.8rem !important; gap: 2px; margin-bottom: 0; }
        .pagination .page-link {
            padding: 0.2rem 0.45rem !important;
            font-size: 0.8rem !important;
            line-height: 1.4 !important;
            border-radius: 6px !important;
        }

        /* ============================================================
           BACKDROP (mobile off-canvas)
           ============================================================ */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(16,24,40,0.45);
            z-index: 1030;
        }

        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            body.sidebar-mobile-open .sidebar { transform: translateX(0); }
            .main-content, body.sidebar-collapsed .main-content { margin-left: 0; }
            body.sidebar-mobile-open .sidebar-backdrop { display: block; }
            .topbar-title { font-size: 14.5px; }
        }
        @media (max-width: 575.98px) {
            .user-name { display: none; }
            .page-content { padding: 16px; }
            .topbar { padding: 0 14px; }
        }
    </style>
    @yield('styles')
</head>
<body>

{{-- SIDEBAR --}}
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-row">
            <div class="sidebar-logo-icon">
                <i class="bi bi-bar-chart-line-fill"></i>
            </div>
            <div>
                <div class="sidebar-brand">Monitoring Alat</div>
                <div class="sidebar-sub">Fasilitasi Penerbangan</div>
            </div>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- MENU OTOMATIS (DARI CONFIG)               --}}
    {{-- ========================================== --}}
    <nav class="sidebar-nav">
        @php
            $menus = getMenus();
        @endphp

        @foreach($menus as $groupName => $items)
            <div class="sb-section">{{ $groupName }}</div>
            @foreach($items as $menu)
                @php
                    $route = isset($menu['route']) ? route($menu['route']) : ($menu['url'] ?? '#');
                    $active = isset($menu['route']) && request()->routeIs($menu['route'] . '*') ? 'active' : '';
                    $isSpecial = $menu['label'] === 'Role & Permission';
                @endphp
                <a href="{{ $route }}" class="sb-item {{ $active }}">
                    <i class="{{ $menu['icon'] }}"></i>
                    <span>{{ $menu['label'] }}</span>
                    @if($isSpecial)
                        <span class="sb-badge">Admin</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="sb-logout">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="sb-logout-btn">
                <i class="bi bi-box-arrow-left"></i> <span>Logout</span>
            </button>
        </form>
    </div>
</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

{{-- MAIN --}}
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                <i class="bi bi-list" style="font-size:18px"></i>
            </button>
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
        </div>

        <div class="topbar-right">
            {{-- NOTIFIKASI --}}
            @php
                $notifCount = \App\Models\Notifikasi::where('id_pengguna', session('pengguna.id'))
                                ->where('status', 'Belum Dibaca')
                                ->count();
                $notifTerbaru = \App\Models\Notifikasi::with('alat.lokasi.bandara')
                                ->where('id_pengguna', session('pengguna.id'))
                                ->orderByDesc('tanggal')
                                ->take(5)
                                ->get();
            @endphp
            <div class="dropdown">
                <a href="#" class="icon-btn no-caret" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    @if($notifCount > 0)
                        <span class="notif-dot"></span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end panel notif-panel">
                    <div class="notif-panel-hd">
                        <span class="notif-panel-title">Notifikasi</span>
                        @if($notifCount > 0)
                            <span class="notif-panel-count">{{ $notifCount }} baru</span>
                        @endif
                    </div>
                    <div class="notif-list">
                        @forelse($notifTerbuk = $notifTerbuku ?? $notifTerbaru as $n)
                            <a href="{{ route('admin.notifikasi.index') }}" class="notif-list-item">
                                <span class="notif-ic"><i class="bi bi-exclamation-triangle"></i></span>
                                <span>
                                    <span class="notif-txt-title">
                                        {{ $n->alat->nama_alat ?? '-' }}
                                        <span class="text-muted fw-normal">— {{ $n->alat->lokasi->bandara->kode_bandara ?? '-' }}</span>
                                    </span>
                                    <span class="notif-txt-msg d-block">{{ $n->pesan }}</span>
                                    <span class="notif-txt-time">{{ \Carbon\Carbon::parse($n->tanggal)->diffForHumans() }}</span>
                                </span>
                            </a>
                        @empty
                            <div class="notif-empty">
                                <i class="bi bi-bell-slash d-block mb-2"></i>
                                <span style="font-size:12px">Tidak ada notifikasi</span>
                            </div>
                        @endforelse
                    </div>
                    <a href="{{ route('admin.notifikasi.index') }}" class="notif-viewall">Lihat semua notifikasi</a>
                </div>
            </div>

            {{-- USER MENU --}}
            <div class="dropdown">
                <button class="user-btn no-caret" data-bs-toggle="dropdown" aria-expanded="false" type="button">
                    <span class="user-avatar">{{ strtoupper(substr(session('pengguna.nama', 'U'), 0, 1)) }}</span>
                    <span class="user-name">{{ session('pengguna.nama') }}</span>
                    <i class="bi bi-chevron-down" style="font-size:11px;color:var(--gray-400)"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end panel user-panel">
                    <div class="user-panel-name">{{ session('pengguna.nama') }}</div>
                    <div class="user-panel-email">{{ session('pengguna.email', '') }}</div>
                    <span class="user-panel-role">{{ session('pengguna.role') }}</span>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="user-panel-logout">
                            <i class="bi bi-box-arrow-left"></i> Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3/dist/chartjs-plugin-annotation.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/locales-all.global.min.js"></script>
<script>
(function () {
    var body = document.body;
    var toggleBtn = document.getElementById('sidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');

    function isDesktop() { return window.innerWidth >= 992; }

    // Pulihkan preferensi collapse (desktop) dari sesi browser sebelumnya
    if (isDesktop() && localStorage.getItem('sidebarCollapsed') === '1') {
        body.classList.add('sidebar-collapsed');
    }

    toggleBtn.addEventListener('click', function () {
        if (isDesktop()) {
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed') ? '1' : '0');
        } else {
            body.classList.toggle('sidebar-mobile-open');
        }
    });

    backdrop.addEventListener('click', function () {
        body.classList.remove('sidebar-mobile-open');
    });

    window.addEventListener('resize', function () {
        if (isDesktop()) {
            body.classList.remove('sidebar-mobile-open');
        }
    });
})();
</script>
@stack('scripts')
</body>
</html>