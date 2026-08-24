@extends('layouts.app')

@section('title', 'Data Harian - Monitoring Alat')
@section('page-title', 'Data Harian')

@section('styles')
<style>
    /* ============================================================
       FULLCALENDAR — TEMA PROFESIONAL
       Disesuaikan dengan warna sidebar (#0f1f3d / #1e40af)
    ============================================================ */
    #calendar {
        max-width: 100%;
        --fc-border-color: #e4e8ef;
        --fc-page-bg-color: #ffffff;
        --fc-neutral-bg-color: #f7f9fc;
        --fc-list-event-hover-bg-color: #f1f5f9;
        --fc-today-bg-color: #eef4ff;
        --fc-button-bg-color: #1e40af;
        --fc-button-border-color: #1e40af;
        --fc-button-hover-bg-color: #1c3a9e;
        --fc-button-hover-border-color: #1c3a9e;
        --fc-button-active-bg-color: #16317e;
        --fc-button-active-border-color: #16317e;
        font-family: system-ui, -apple-system, sans-serif;
    }

    #calendar .fc-toolbar.fc-header-toolbar {
        margin-bottom: 18px;
        flex-wrap: wrap;
        gap: 10px;
    }
    #calendar .fc-toolbar-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f1f3d;
    }
    #calendar .fc-button {
        text-transform: capitalize;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 6px 13px;
        border-radius: 7px !important;
        box-shadow: none !important;
    }
    #calendar .fc-button:focus {
        box-shadow: 0 0 0 2px rgba(30,64,175,0.25) !important;
    }
    #calendar .fc-button-group .fc-button {
        border-radius: 0 !important;
    }
    #calendar .fc-button-group .fc-button:first-child {
        border-top-left-radius: 7px !important;
        border-bottom-left-radius: 7px !important;
    }
    #calendar .fc-button-group .fc-button:last-child {
        border-top-right-radius: 7px !important;
        border-bottom-right-radius: 7px !important;
    }
    #calendar .fc-today-button {
        text-transform: capitalize;
    }

    #calendar .fc-col-header-cell {
        background: #f7f9fc;
        padding: 9px 0;
    }
    #calendar .fc-col-header-cell-cushion {
        font-size: 0.72rem;
        font-weight: 700;
        color: #6a7a8a;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        text-decoration: none;
    }

    #calendar .fc-daygrid-day-number {
        font-size: 0.8rem;
        font-weight: 600;
        color: #344256;
        padding: 6px 8px;
        text-decoration: none;
    }
    #calendar .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        color: #1e40af;
    }
    #calendar .fc-daygrid-day-frame {
        padding: 2px;
    }
    #calendar .fc-day-other .fc-daygrid-day-number {
        color: #c2cad6;
    }

    #calendar .fc-event {
        cursor: pointer;
        font-size: 0.74rem;
        font-weight: 600;
        border: none !important;
        border-radius: 5px;
        padding: 2px 6px;
        margin: 1.5px 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        transition: transform .1s ease, box-shadow .1s ease;
    }
    #calendar .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(0,0,0,0.14);
    }
    #calendar .fc-daygrid-event-dot { display: none; }
    #calendar .fc-event-time { font-weight: 600; }

    #calendar .fc-list-day-cushion {
        background: #f7f9fc !important;
    }
    #calendar .fc-list-event:hover td {
        background-color: #f1f5f9 !important;
    }
    #calendar .fc-list-event-dot {
        border-width: 5px !important;
    }

    #calendar .fc-scrollgrid {
        border-radius: 8px;
        overflow: hidden;
    }

    .legend-dot {
        display: inline-block;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        margin-right: 5px;
        vertical-align: middle;
    }
</style>
@endsection

@section('content')

{{-- Legend warna + Toggle ke tampilan tabel --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-4" style="font-size:0.85rem; color:#344256;">
        <span><span class="legend-dot" style="background:#0d6efd;"></span>Normal</span>
        <span><span class="legend-dot" style="background:#fd7e14;"></span>Warning / Gangguan</span>
        <span><span class="legend-dot" style="background:#dc3545;"></span>Rusak</span>
    </div>
    <a href="{{ route('admin.data-harian.table') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-table me-1"></i> Tampilan Tabel
    </a>
</div>

{{-- Filter --}}
<div class="card stat-card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">

            @if(!$isLocked)
            <div class="col-md-4">
                <label class="form-label fw-semibold">Bandara</label>
                <select id="filterBandara" class="form-select">
                    <option value="">Semua Bandara</option>
                    @foreach($bandara as $b)
                        <option value="{{ $b->id_bandara }}">{{ $b->kode_bandara }} - {{ $b->nama_bandara }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-4">
                <label class="form-label fw-semibold">Alat</label>
                <select id="filterAlat" class="form-select">
                    <option value="">Semua Alat</option>
                    @foreach($alat as $a)
                        <option value="{{ $a->id_alat }}" data-bandara="{{ $a->lokasi->bandara->id_bandara ?? '' }}">
                            {{ $a->nama_alat }} - {{ $a->lokasi->bandara->kode_bandara ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Kondisi</label>
                <select id="filterKondisi" class="form-select">
                    <option value="">Semua</option>
                    <option value="Normal">Normal</option>
                    <option value="Gangguan">Gangguan</option>
                    <option value="Rusak">Rusak</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- Kalender --}}
<div class="card stat-card">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

{{-- Modal Detail Log --}}
<div class="modal fade" id="modalDetailLog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none; overflow:hidden;">
            <div class="modal-header" style="background:#0f1f3d; color:#fff; border:none;">
                <h5 class="modal-title fw-bold" id="modalAlatNama">-</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-4">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Tanggal</dt>
                    <dd class="col-7 fw-semibold" id="modalTanggal">-</dd>

                    <dt class="col-5 text-muted fw-normal">Kondisi</dt>
                    <dd class="col-7">
                        <span class="badge" id="modalKondisiBadge">-</span>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Performa</dt>
                    <dd class="col-7" id="modalPerforma">-</dd>

                    <dt class="col-5 text-muted fw-normal">Lokasi</dt>
                    <dd class="col-7" id="modalLokasi">-</dd>

                    <dt class="col-5 text-muted fw-normal">Detail Lokasi</dt>
                    <dd class="col-7" id="modalDetailLokasi">-</dd>

                    <dt class="col-5 text-muted fw-normal">Bandara</dt>
                    <dd class="col-7" id="modalBandara">-</dd>

                    <dt class="col-5 text-muted fw-normal">Jam Operasional</dt>
                    <dd class="col-7" id="modalJamOperasional">-</dd>

                    <dt class="col-5 text-muted fw-normal">Jam Terputus</dt>
                    <dd class="col-7" id="modalJamTerputus">-</dd>

                    <dt class="col-5 text-muted fw-normal">Teknisi</dt>
                    <dd class="col-7" id="modalTeknisi">-</dd>

                    <dt class="col-5 text-muted fw-normal">Catatan</dt>
                    <dd class="col-7" id="modalCatatan">-</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <a href="#" id="modalLinkDetail" class="btn btn-primary">
                    <i class="bi bi-eye me-1"></i> Lihat Detail Lengkap
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl    = document.getElementById('calendar');
    const filterBandara  = document.getElementById('filterBandara'); // null untuk afet_bandara
    const filterAlat     = document.getElementById('filterAlat');
    const filterKondisi  = document.getElementById('filterKondisi');

    const eventsUrl   = "{{ route('admin.data-harian.events') }}";
    const showBaseUrl = "{{ url('admin/data-harian') }}";

    const kondisiBadgeClass = {
        'Normal':   'bg-success',
        'Gangguan': 'bg-warning text-dark',
        'Rusak':    'bg-danger',
    };

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'id',
        height: 'auto',
        dayMaxEventRows: 3,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek,listMonth'
        },
        buttonText: {
            today: 'Hari Ini',
            month: 'Bulan',
            week:  'Minggu',
            list:  'Daftar'
        },

        events: function (fetchInfo, successCallback, failureCallback) {
            const params = new URLSearchParams({
                start: fetchInfo.startStr,
                end: fetchInfo.endStr,
                id_bandara: filterBandara ? filterBandara.value : '',
                id_alat: filterAlat.value,
                kondisi: filterKondisi.value,
            });

            fetch(eventsUrl + '?' + params.toString())
                .then(res => res.json())
                .then(data => successCallback(data))
                .catch(err => failureCallback(err));
        },

        eventClick: function (info) {
            const props = info.event.extendedProps;

            document.getElementById('modalAlatNama').textContent = props.alat;
            document.getElementById('modalTanggal').textContent = new Date(info.event.startStr).toLocaleDateString('id-ID', {
                day: 'numeric', month: 'long', year: 'numeric'
            });

            const badge = document.getElementById('modalKondisiBadge');
            badge.textContent = props.kondisi;
            badge.className = 'badge ' + (kondisiBadgeClass[props.kondisi] || 'bg-secondary');

            document.getElementById('modalPerforma').textContent = props.performa + '%';
            document.getElementById('modalLokasi').textContent = props.lokasi;
            document.getElementById('modalDetailLokasi').textContent = props.detail_lokasi;
            document.getElementById('modalBandara').textContent = props.bandara;
            document.getElementById('modalJamOperasional').textContent = props.jam_operasional + ' jam';
            document.getElementById('modalJamTerputus').textContent = props.jam_terputus + ' jam';
            document.getElementById('modalTeknisi').textContent = props.teknisi;
            document.getElementById('modalCatatan').textContent = props.catatan;

            document.getElementById('modalLinkDetail').href = showBaseUrl + '/' + props.id_log;

            const modal = new bootstrap.Modal(document.getElementById('modalDetailLog'));
            modal.show();
        }
    });

    calendar.render();

    // Re-fetch saat filter berubah
    [filterBandara, filterAlat, filterKondisi].forEach(el => {
        if (el) el.addEventListener('change', () => calendar.refetchEvents());
    });

    // Saat bandara dipilih, otomatis filter dropdown alat sesuai bandara itu
    // (hanya berlaku untuk afet_regional, karena filterBandara tidak ada untuk afet_bandara)
    if (filterBandara) {
        filterBandara.addEventListener('change', function () {
            const selectedBandara = this.value;
            const options = filterAlat.querySelectorAll('option');

            options.forEach(opt => {
                if (!opt.value) return;
                const matches = !selectedBandara || opt.dataset.bandara === selectedBandara;
                opt.hidden = !matches;
            });

            const currentOpt = filterAlat.options[filterAlat.selectedIndex];
            if (currentOpt && currentOpt.hidden) {
                filterAlat.value = '';
            }
        });
    }
});
</script>
@endpush