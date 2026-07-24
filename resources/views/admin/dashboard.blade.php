@extends('layouts.app')

@section('title', 'Dashboard - Monitoring Alat')
@section('page-title', 'Dashboard')

@section('styles')
<style>
/* ============================================================
   Halaman ini HANYA menambahkan style spesifik dashboard.
   Semua warna/radius/shadow dasar (var(--brand-*), var(--gray-*),
   var(--success-*), var(--warning-*), var(--error-*), var(--shadow-*))
   diambil dari token yang sudah didefinisikan di layouts/app.blade.php
   supaya konsisten dengan tampilan TailAdmin.
   ============================================================ */

/* --- Filter bar --- */
.filter-bar { padding: 16px 18px; margin-bottom: 20px; }
.filter-bar .form-label {
    font-size: 12.5px; font-weight: 500; color: var(--gray-700); margin-bottom: 6px;
}
.filter-bar .form-select {
    font-size: 13.5px; border: 1px solid var(--gray-300); border-radius: 8px;
    padding: 8px 12px; background: #fff; color: var(--gray-700); box-shadow: var(--shadow-xs);
}
.filter-bar .form-select:focus {
    border-color: var(--brand-300); box-shadow: 0 0 0 3px rgba(70,95,255,0.10); outline: none;
}
.filter-bar .btn-primary {
    background: var(--brand-500); border: none; border-radius: 8px; font-size: 13.5px;
    font-weight: 500; padding: 8px 18px; box-shadow: var(--shadow-xs);
}
.filter-bar .btn-primary:hover { background: var(--brand-600); }
.filter-bar .btn-outline-secondary {
    border-radius: 8px; font-size: 13.5px; font-weight: 500; padding: 8px 16px;
    color: var(--gray-700); border-color: var(--gray-300); background: #fff;
}
.filter-bar .btn-outline-secondary:hover { background: var(--gray-50); color: var(--gray-800); }

/* --- Stat cards (mengikuti pola EcommerceMetrics: icon box + label + value + badge) --- */
.stat-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 20px; }
.sc { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: var(--shadow-xs); padding: 18px 20px; }
.sc-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 14px; }
.sc-icon-brand   { background: var(--brand-50);   color: var(--brand-500); }
.sc-icon-success { background: var(--success-50); color: var(--success-600); }
.sc-icon-warning { background: var(--warning-50); color: var(--warning-600); }
.sc-icon-error   { background: var(--error-50);   color: var(--error-600); }
.sc-label { font-size: 12.5px; color: var(--gray-500); margin-bottom: 6px; }
.sc-val { font-size: 26px; font-weight: 700; line-height: 1; color: var(--gray-900); margin-bottom: 6px; }
.sc-sub { font-size: 12px; color: var(--gray-400); }

/* --- Kartu umum di halaman ini (header + isi) --- */
.page-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: var(--shadow-xs); padding: 18px 20px; }
.card-hd { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 16px; }
.card-title-dash { font-size: 15px; font-weight: 600; color: var(--gray-800); display: flex; align-items: center; gap: 8px; }
.card-title-dash i { color: var(--brand-500); font-size: 16px; }
.card-subtitle { font-size: 12px; color: var(--gray-400); margin-top: 2px; }

/* --- Badge / pill, mengikuti varian ui/Badge.vue (light) --- */
.pill { font-size: 11px; border-radius: 20px; padding: 3px 10px; font-weight: 500; white-space: nowrap; }
.pill-brand   { background: var(--brand-50);   color: var(--brand-500); }
.pill-success { background: var(--success-50); color: var(--success-600); }
.pill-warning { background: var(--warning-50); color: var(--warning-600); }
.pill-error   { background: var(--error-50);   color: var(--error-600); }

.legend-row { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--gray-100); }
.legend-item { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--gray-500); }
.legend-box { width: 9px; height: 9px; border-radius: 3px; display: inline-block; }

/* --- Notifikasi (pola list-item TailAdmin: icon bulat + teks) --- */
.notif-item {
    display: flex; gap: 10px; padding: 10px; border-radius: 12px; margin-bottom: 4px;
}
.notif-item:hover { background: var(--gray-50); }
.notif-ic {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    background: var(--warning-50); color: var(--warning-600);
    display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.notif-title { font-size: 12.5px; font-weight: 600; color: var(--gray-800); margin-bottom: 2px; }
.notif-msg { font-size: 12px; color: var(--gray-500); margin-bottom: 3px; line-height: 1.4; }
.notif-time { font-size: 11px; color: var(--gray-400); }
.notif-viewall {
    display: block; text-align: center; margin-top: 8px; padding: 9px;
    border-radius: 8px; border: 1px solid var(--gray-200);
    font-size: 12.5px; font-weight: 500; color: var(--gray-700);
}
.notif-viewall:hover { background: var(--gray-50); color: var(--gray-800); }

/* --- Top alat terbaik --- */
.top-alat-item { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-top: 1px solid var(--gray-100); }
.top-alat-item:first-child { border-top: none; padding-top: 0; }
.top-medal { font-size: 16px; min-width: 24px; text-align: center; }
.top-info { flex: 1; min-width: 0; }
.top-name { font-size: 12.5px; font-weight: 600; color: var(--gray-800); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-sub { font-size: 11px; color: var(--gray-400); }
.top-score { font-size: 14px; font-weight: 700; }

/* --- Ringkasan mini grid --- */
.mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.mini-sc { background: var(--gray-50); border: 1px solid var(--gray-100); border-radius: 12px; padding: 10px 12px; }
.mini-lbl { font-size: 11px; color: var(--gray-500); margin-bottom: 3px; }
.mini-val { font-size: 18px; font-weight: 700; }

/* --- Performa per bandara: progress bar --- */
.bnd-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.bnd-row:last-child { margin-bottom: 0; }
.bnd-lbl { font-size: 12px; font-weight: 600; color: var(--gray-700); min-width: 38px; }
.bnd-track { flex: 1; height: 8px; background: var(--gray-100); border-radius: 20px; overflow: hidden; }
.bnd-fill { height: 100%; border-radius: 20px; }
.bnd-val { font-size: 12px; font-weight: 600; min-width: 38px; text-align: right; }

/* --- Tabel performa per alat (pola RecentOrders: border-top per baris) --- */
.tbl-dash { width: 100%; border-collapse: collapse; font-size: 13px; }
.tbl-dash thead tr { border-top: 1px solid var(--gray-100); }
.tbl-dash th {
    font-size: 11.5px; font-weight: 500; color: var(--gray-500);
    padding: 10px 8px; text-align: left;
}
.tbl-dash tbody tr { border-top: 1px solid var(--gray-100); }
.tbl-dash td { padding: 10px 8px; vertical-align: middle; color: var(--gray-700); }
.rk { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; }
.rk-1 { background: var(--warning-50); color: var(--warning-600); }
.rk-2 { background: var(--brand-50); color: var(--brand-500); }
.rk-3 { background: var(--success-50); color: var(--success-600); }
.rk-n { background: var(--gray-100); color: var(--gray-500); }
.bnd-badge { font-size: 11px; background: var(--brand-50); color: var(--brand-500); border-radius: 6px; padding: 3px 9px; font-weight: 500; }
.bs { font-size: 11px; border-radius: 20px; padding: 3px 9px; font-weight: 500; }
.bs-g { background: var(--success-50); color: var(--success-600); }
.bs-a { background: var(--warning-50); color: var(--warning-600); }
.bs-r { background: var(--error-50); color: var(--error-600); }
.pbar { height: 6px; border-radius: 20px; background: var(--gray-100); overflow: hidden; width: 68px; display: inline-block; vertical-align: middle; }
.pfill { height: 100%; border-radius: 20px; }

.chart-wrap { position: relative; width: 100%; }
.chart-wrap-md { height: 190px; }
.chart-wrap-sm { height: 170px; }
.chart-wrap-lg { height: 210px; }

/* --- Tambahan kecil: kartu setinggi baris agar rapi sejajar --- */
.page-card.h-100 { display: flex; flex-direction: column; }
</style>
@endsection


@section('content')

{{-- FILTER --}}
<div class="page-card filter-bar">
    <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-2 align-items-end">
        @if(! $isLocked)
        <div class="col-md-3">
            <label class="form-label">Bandara</label>
            <select name="id_bandara" class="form-select">
                <option value="">Semua Bandara</option>
                @foreach($bandara as $b)
                    <option value="{{ $b->id_bandara }}" {{ request('id_bandara') == $b->id_bandara ? 'selected' : '' }}>
                        {{ $b->kode_bandara }} - {{ $b->nama_bandara }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="{{ $isLocked ? 'col-md-3' : 'col-md-2' }}">
            <label class="form-label">Bulan</label>
            <select name="bulan" class="form-select">
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month((int)$m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="{{ $isLocked ? 'col-md-3' : 'col-md-2' }}">
            <label class="form-label">Tahun</label>
            <select name="tahun" class="form-select">
                @foreach(range(date('Y'), 2023) as $y)
                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="{{ $isLocked ? 'col-md-3' : 'col-md-2' }}">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search me-1"></i> Tampilkan
            </button>
        </div>
        <div class="{{ $isLocked ? 'col-md-3' : 'col-md-2' }}">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </a>
        </div>
    </form>
</div>

{{-- STAT CARDS --}}
<div class="stat-row">
    <div class="sc">
        <div class="sc-icon sc-icon-brand"><i class="bi bi-graph-up"></i></div>
        <div class="sc-label">Rata-rata Performa</div>
        <div class="sc-val">{{ $rataPerforma }}%</div>
        <div class="sc-sub">Dari {{ $totalAlat }} alat aktif</div>
    </div>
    <div class="sc">
        <div class="sc-icon sc-icon-success"><i class="bi bi-check-circle"></i></div>
        <div class="sc-label">Baik (&ge; 90%)</div>
        <div class="sc-val" style="color:var(--success-600)">{{ $totalBaik }}</div>
        <div class="sc-sub">{{ $totalAlat ? round($totalBaik/$totalAlat*100,1) : 0 }}% dari total</div>
    </div>
    <div class="sc">
        <div class="sc-icon sc-icon-warning"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="sc-label">Warning (80&ndash;89%)</div>
        <div class="sc-val" style="color:var(--warning-600)">{{ $totalWarning }}</div>
        <div class="sc-sub">{{ $totalAlat ? round($totalWarning/$totalAlat*100,1) : 0 }}% dari total</div>
    </div>
    <div class="sc">
        <div class="sc-icon sc-icon-error"><i class="bi bi-x-circle"></i></div>
        <div class="sc-label">Buruk (&lt; 80%)</div>
        <div class="sc-val" style="color:var(--error-600)">{{ $totalBuruk }}</div>
        <div class="sc-sub">{{ $totalAlat ? round($totalBuruk/$totalAlat*100,1) : 0 }}% dari total</div>
    </div>
</div>

{{-- GRAFIK FASKAMPEN --}}
<div class="row g-3 mb-3 align-items-stretch">
    <div class="col-md-6">
        <div class="page-card h-100">
            <div class="card-hd">
                <div>
                    <div class="card-title-dash"><i class="bi bi-shield-check"></i> Faskampen &mdash; Keamanan Penerbangan</div>
                    <div class="card-subtitle">Rata-rata performa per jenis alat bulan ini</div>
                </div>
                <span class="pill pill-brand">Threshold 90%</span>
            </div>
            <div class="chart-wrap chart-wrap-md">
                <canvas id="chartKeamanan"></canvas>
            </div>
            <div class="legend-row">
                <div class="legend-item"><span class="legend-box" style="background:var(--success-500)"></span>&ge; 90%</div>
                <div class="legend-item"><span class="legend-box" style="background:var(--warning-500)"></span>80&ndash;89%</div>
                <div class="legend-item"><span class="legend-box" style="background:var(--error-500)"></span>&lt; 80%</div>
                <div class="legend-item"><span style="display:inline-block;width:16px;height:0;border-top:2px dashed var(--error-500)"></span> Threshold</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="page-card h-100">
            <div class="card-hd">
                <div>
                    <div class="card-title-dash"><i class="bi bi-broadcast"></i> Faskampen &mdash; Operasional</div>
                    <div class="card-subtitle">Rata-rata performa per jenis alat bulan ini</div>
                </div>
                <span class="pill pill-brand">Threshold 90%</span>
            </div>
            <div class="chart-wrap chart-wrap-md">
                <canvas id="chartOperasional"></canvas>
            </div>
            <div class="legend-row">
                <div class="legend-item"><span class="legend-box" style="background:var(--success-500)"></span>&ge; 90%</div>
                <div class="legend-item"><span class="legend-box" style="background:var(--warning-500)"></span>80&ndash;89%</div>
                <div class="legend-item"><span class="legend-box" style="background:var(--error-500)"></span>&lt; 80%</div>
                <div class="legend-item"><span style="display:inline-block;width:16px;height:0;border-top:2px dashed var(--error-500)"></span> Threshold</div>
            </div>
        </div>
    </div>
</div>

{{-- TREN HARIAN --}}
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="page-card">
            <div class="card-hd">
                <div>
                    <div class="card-title-dash"><i class="bi bi-activity"></i> Tren Performa Harian</div>
                    <div class="card-subtitle">Rata-rata performa seluruh alat per hari</div>
                </div>
                <span class="pill pill-brand">{{ \Carbon\Carbon::create()->month((int)$bulan)->translatedFormat('F') }} {{ $tahun }}</span>
            </div>
            <div class="chart-wrap chart-wrap-lg">
                <canvas id="chartHarian"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- DISTRIBUSI + BANDARA + NOTIFIKASI --}}
<div class="row g-3 mb-3 align-items-stretch">
    <div class="col-md-4">
        <div class="page-card h-100">
            <div class="card-hd">
                <div class="card-title-dash"><i class="bi bi-pie-chart"></i> Distribusi Status</div>
            </div>
            <div class="chart-wrap chart-wrap-sm">
                <canvas id="chartDistribusi"></canvas>
            </div>
            <div class="mt-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="d-flex align-items-center gap-2" style="font-size:12.5px;color:var(--gray-600)">
                        <span style="width:8px;height:8px;border-radius:50%;background:var(--success-500);display:inline-block"></span> Baik
                    </span>
                    <span class="fw-bold" style="font-size:12.5px;color:var(--success-600)">{{ $totalBaik }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="d-flex align-items-center gap-2" style="font-size:12.5px;color:var(--gray-600)">
                        <span style="width:8px;height:8px;border-radius:50%;background:var(--warning-500);display:inline-block"></span> Warning
                    </span>
                    <span class="fw-bold" style="font-size:12.5px;color:var(--warning-600)">{{ $totalWarning }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="d-flex align-items-center gap-2" style="font-size:12.5px;color:var(--gray-600)">
                        <span style="width:8px;height:8px;border-radius:50%;background:var(--error-500);display:inline-block"></span> Buruk
                    </span>
                    <span class="fw-bold" style="font-size:12.5px;color:var(--error-600)">{{ $totalBuruk }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="page-card h-100">
            <div class="card-hd">
                <div>
                    <div class="card-title-dash"><i class="bi bi-building"></i> {{ $isLocked ? 'Performa Bandara Anda' : 'Performa Per Bandara' }}</div>
                    <div class="card-subtitle">Rata-rata performa seluruh alat per bandara</div>
                </div>
                @if(($performaBandara ?? collect())->count() > 0)
                <span class="pill pill-brand">Threshold 90%</span>
                @endif
            </div>
            @if(($performaBandara ?? collect())->count() > 0)
            <div class="chart-wrap chart-wrap-sm">
                <canvas id="chartBandara"></canvas>
            </div>
            <div class="legend-row">
                <div class="legend-item"><span class="legend-box" style="background:var(--success-500)"></span>&ge; 90%</div>
                <div class="legend-item"><span class="legend-box" style="background:var(--warning-500)"></span>80&ndash;89%</div>
                <div class="legend-item"><span class="legend-box" style="background:var(--error-500)"></span>&lt; 80%</div>
                <div class="legend-item"><span style="display:inline-block;width:16px;height:0;border-top:2px dashed var(--error-500)"></span> Threshold</div>
            </div>
            @else
            <div class="text-center py-4">
                <i class="bi bi-building" style="font-size:22px;color:var(--gray-300)"></i>
                <p class="mt-2 mb-0" style="font-size:12.5px;color:var(--gray-400)">Belum ada data</p>
            </div>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="page-card h-100">
            <div class="card-hd">
                <div class="card-title-dash"><i class="bi bi-bell"></i> Notifikasi Terbaru</div>
                @if($notifikasi->count() > 0)
                    <span class="pill pill-error">{{ $notifikasi->count() }} baru</span>
                @endif
            </div>
            @forelse($notifikasi as $n)
            <div class="notif-item">
                <span class="notif-ic"><i class="bi bi-exclamation-triangle"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="notif-title">
                        {{ $n->alat->nama_alat ?? '-' }}
                        <span style="color:var(--gray-400);font-weight:400"> &mdash; {{ $n->alat->lokasi->bandara->kode_bandara ?? '-' }}</span>
                    </div>
                    <div class="notif-msg">{{ $n->pesan }}</div>
                    <div class="notif-time"><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($n->tanggal)->diffForHumans() }}</div>
                </div>
            </div>
            @empty
            <div class="text-center py-4">
                <i class="bi bi-bell-slash" style="font-size:22px;color:var(--gray-300)"></i>
                <p class="mt-2 mb-0" style="font-size:12.5px;color:var(--gray-400)">Tidak ada notifikasi</p>
            </div>
            @endforelse
            @if($notifikasi->count() > 0)
            <a href="{{ route('admin.notifikasi.index') }}" class="notif-viewall">Lihat semua notifikasi &rarr;</a>
            @endif
        </div>
    </div>
</div>

{{-- TABEL PERFORMA + RINGKASAN --}}
<div class="row g-3 mb-3 align-items-stretch">
    <div class="col-md-8">
        <div class="page-card h-100">
            <div class="card-hd">
                <div class="card-title-dash"><i class="bi bi-table"></i> Performa Per Alat &mdash; Rata-rata Bulanan</div>
                <span class="pill pill-brand">{{ \Carbon\Carbon::create()->month((int)$bulan)->translatedFormat('F') }} {{ $tahun }}</span>
            </div>
            <div class="table-responsive">
                <table class="tbl-dash">
                    <thead>
                        <tr>
                            <th style="width:32px">#</th>
                            <th>Nama Alat</th>
                            <th>Lokasi</th>
                            <th>Bandara</th>
                            <th style="width:130px">Performa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hasilBulanan as $i => $h)
                        @php
                            $pColor = $h->status == 'Baik' ? 'var(--success-500)' : ($h->status == 'Warning' ? 'var(--warning-500)' : 'var(--error-500)');
                            $vColor = $h->status == 'Baik' ? 'var(--success-600)' : ($h->status == 'Warning' ? 'var(--warning-600)' : 'var(--error-600)');
                        @endphp
                        <tr>
                            <td>
                                @if($i==0) <span class="rk rk-1">1</span>
                                @elseif($i==1) <span class="rk rk-2">2</span>
                                @elseif($i==2) <span class="rk rk-3">3</span>
                                @else <span class="rk rk-n">{{ $i+1 }}</span>
                                @endif
                            </td>
                            <td style="font-weight:600;color:var(--gray-800)">{{ $h->alat->nama_alat ?? '-' }}</td>
                            <td style="color:var(--gray-500);font-size:12px">{{ $h->alat->lokasi->nama_lokasi ?? '-' }}</td>
                            <td><span class="bnd-badge">{{ $h->alat->lokasi->bandara->kode_bandara ?? '-' }}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="pbar"><div class="pfill" style="width:{{ $h->rata_performa }}%;background:{{ $pColor }}"></div></div>
                                    <span style="font-size:12px;font-weight:600;color:{{ $vColor }}">{{ $h->rata_performa }}%</span>
                                </div>
                            </td>
                            <td>
                                <span class="bs {{ $h->status=='Baik' ? 'bs-g' : ($h->status=='Warning' ? 'bs-a' : 'bs-r') }}">
                                    {{ $h->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4" style="color:var(--gray-400)">
                                <i class="bi bi-inbox" style="font-size:22px"></i>
                                <p class="mt-2 mb-0" style="font-size:12.5px">Belum ada data bulan ini</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="page-card h-100">
            <div class="card-hd">
                <div class="card-title-dash"><i class="bi bi-calendar-check"></i> Ringkasan Bulanan</div>
            </div>
            <div class="mini-grid">
                <div class="mini-sc">
                    <div class="mini-lbl">Rata-rata</div>
                    <div class="mini-val" style="color:var(--brand-500)">{{ $rataPerforma }}%</div>
                </div>
                <div class="mini-sc">
                    <div class="mini-lbl">Capai Target</div>
                    <div class="mini-val" style="color:var(--success-600)">{{ $totalBaik }}</div>
                </div>
                <div class="mini-sc">
                    <div class="mini-lbl">Warning</div>
                    <div class="mini-val" style="color:var(--warning-600)">{{ $totalWarning }}</div>
                </div>
                <div class="mini-sc">
                    <div class="mini-lbl">Perlu Perhatian</div>
                    <div class="mini-val" style="color:var(--error-600)">{{ $totalBuruk }}</div>
                </div>
            </div>
            <div class="card-title-dash mb-2" style="font-size:13px">
                <i class="bi bi-trophy" style="color:var(--warning-500)"></i> Top 5 Alat Terbaik
            </div>
            @foreach($hasilBulanan->take(5) as $i => $h)
            <div class="top-alat-item">
                <span class="top-medal">
                    @if($i==0) 🥇
                    @elseif($i==1) 🥈
                    @elseif($i==2) 🥉
                    @else <span style="font-size:12px;color:var(--gray-400);min-width:24px;text-align:center;display:inline-block">{{ $i+1 }}</span>
                    @endif
                </span>
                <div class="top-info">
                    <div class="top-name">{{ $h->alat->nama_alat ?? '-' }}</div>
                    <div class="top-sub">{{ $h->alat->lokasi->bandara->kode_bandara ?? '-' }} &middot; {{ $h->alat->lokasi->nama_lokasi ?? '-' }}</div>
                </div>
                <span class="top-score" style="color:{{ $h->status=='Baik' ? 'var(--success-600)' : ($h->status=='Warning' ? 'var(--warning-600)' : 'var(--error-600)') }}">
                    {{ $h->rata_performa }}%
                </span>
            </div>
            @endforeach
            @if($hasilBulanan->isEmpty())
            <p class="text-center py-3" style="font-size:12.5px;color:var(--gray-400)">Belum ada data</p>
            @endif
        </div>
    </div>
</div>

@endsection


@push('scripts')
<script>
// Palet warna mengikuti token brand/success/warning/error TailAdmin
const COLOR_BRAND   = '#465FFF';
const COLOR_BRAND_2 = '#9CB9FF';
const COLOR_SUCCESS = '#12b76a';
const COLOR_WARNING = '#f79009';
const COLOR_ERROR   = '#f04438';

function barColor(val) {
    if (val >= 90) return COLOR_SUCCESS;
    if (val >= 80) return COLOR_WARNING;
    return COLOR_ERROR;
}

const THRESHOLD_LINE = {
    type: 'line',
    yMin: 90, yMax: 90,
    borderColor: COLOR_ERROR,
    borderWidth: 1.5,
    borderDash: [5, 4],
    label: {
        content: '90%', display: true, position: 'end',
        color: COLOR_ERROR, font: { size: 10 }, backgroundColor: 'transparent', padding: 2
    }
};

// Chart Keamanan (warna per-bar mengikuti status performa, selaras dgn badge Baik/Warning/Buruk)
const dataK = {!! json_encode($performaPerJenisKeamanan) !!};

new Chart(document.getElementById('chartKeamanan'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($jenisKeamanan) !!},
        datasets: [{ data: dataK, backgroundColor: dataK.map(barColor), borderRadius: 6, borderSkipped: false, maxBarThickness: 42 }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            annotation: { annotations: { threshold: THRESHOLD_LINE } },
            tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y}%` } }
        },
        scales: {
            y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: 'rgba(16,24,40,0.04)' } },
            x: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});

// Chart Operasional
const dataO = {!! json_encode($performaPerJenisOperasional) !!};

new Chart(document.getElementById('chartOperasional'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($jenisOperasional) !!},
        datasets: [{ data: dataO, backgroundColor: dataO.map(barColor), borderRadius: 6, borderSkipped: false, maxBarThickness: 42 }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            annotation: { annotations: { threshold: THRESHOLD_LINE } },
            tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y}%` } }
        },
        scales: {
            y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: 'rgba(16,24,40,0.04)' } },
            x: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});

// Chart Harian (area chart, gaya sama seperti Statistics Chart TailAdmin)
const ctxH = document.getElementById('chartHarian').getContext('2d');
const gradH = ctxH.createLinearGradient(0, 0, 0, 210);
gradH.addColorStop(0, 'rgba(70,95,255,0.20)');
gradH.addColorStop(1, 'rgba(70,95,255,0)');
const harianCount = {!! json_encode($performaHarian->count()) !!};

new Chart(ctxH, {
    type: 'line',
    data: {
        labels: {!! json_encode($performaHarian->pluck('tanggal')) !!},
        datasets: [
            {
                label: 'Performa (%)',
                data: {!! json_encode($performaHarian->pluck('rata_performa')) !!},
                borderColor: COLOR_BRAND,
                backgroundColor: gradH,
                fill: true, tension: 0.4,
                pointRadius: 3, pointHoverRadius: 6,
                pointBackgroundColor: COLOR_BRAND, borderWidth: 2,
            },
            {
                label: 'Threshold 90%',
                data: Array(harianCount).fill(90),
                borderColor: COLOR_ERROR, borderDash: [5,4],
                borderWidth: 1.5, pointRadius: 0, fill: false,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: true, position: 'bottom', labels: { font: { size: 11 }, boxWidth: 14, padding: 14 } },
            tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y}%` } }
        },
        scales: {
            y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } }, grid: { color: 'rgba(16,24,40,0.04)' } },
            x: { ticks: { font: { size: 10 }, maxRotation: 45 }, grid: { display: false } }
        }
    }
});

// Chart Distribusi
new Chart(document.getElementById('chartDistribusi'), {
    type: 'doughnut',
    data: {
        labels: ['Baik', 'Warning', 'Buruk'],
        datasets: [{
            data: [{{ $totalBaik }}, {{ $totalWarning }}, {{ $totalBuruk }}],
            backgroundColor: [COLOR_SUCCESS, COLOR_WARNING, COLOR_ERROR],
            borderWidth: 0, hoverOffset: 5,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} alat` } }
        }
    }
});
</script>
@endpush