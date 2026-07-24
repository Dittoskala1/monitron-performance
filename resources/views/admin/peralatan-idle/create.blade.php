@extends('layouts.app')

@section('title', 'Ajukan Idle - Monitoring Alat')
@section('page-title', 'Ajukan Peralatan Idle')

@section('content')

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="card stat-card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.peralatan-idle.store') }}" enctype="multipart/form-data">
            @csrf

            @if($selectedAlat)
                {{-- ⚠️ BARU: alat sudah ditentukan dari tombol "Ajukan Idle" di Data Alat.
                     Tidak perlu dropdown, langsung kirim id_alat via hidden input. --}}
                <input type="hidden" name="id_alat" value="{{ $selectedAlat->id_alat }}">

                <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <div class="fw-semibold">{{ $selectedAlat->nama_alat }}</div>
                        <div class="small text-muted">
                            {{ $selectedAlat->lokasi->nama_lokasi ?? '-' }}
                            ({{ $selectedAlat->lokasi->bandara->kode_bandara ?? '-' }})
                        </div>
                    </div>
                    <a href="{{ route('admin.peralatan-idle.create') }}" class="btn btn-sm btn-outline-secondary">
                        Ganti Alat
                    </a>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kode Alat</label>
                        <input type="text" class="form-control" readonly tabindex="-1"
                               value="{{ $selectedAlat->kode_alat ?? '-' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cabang</label>
                        <input type="text" class="form-control" readonly tabindex="-1"
                               value="{{ ($selectedAlat->lokasi->bandara->nama_bandara ?? '-') }} ({{ $selectedAlat->lokasi->bandara->kode_bandara ?? '-' }})">
                    </div>
                </div>
            @else
                {{-- Fallback: halaman dibuka langsung tanpa id_alat, tampilkan dropdown seperti biasa --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Alat <span class="text-danger">*</span></label>
                    <select name="id_alat" id="pilihAlat" class="form-select" required>
                        <option value="">-- Pilih Alat --</option>
                        @foreach($alat as $a)
                            <option value="{{ $a->id_alat }}"
                                data-kode-alat="{{ $a->kode_alat ?? '-' }}"
                                data-cabang="{{ $a->lokasi->bandara->nama_bandara ?? '-' }} ({{ $a->lokasi->bandara->kode_bandara ?? '-' }})">
                                {{ $a->nama_alat }} — {{ $a->lokasi->nama_lokasi ?? '-' }}
                                ({{ $a->lokasi->bandara->kode_bandara ?? '-' }})
                                @if(in_array($a->id_alat, $idAlatSedangDiajukan))
                                    — (Sudah diajukan, menunggu approval)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        Alat yang berlabel "Sudah diajukan, menunggu approval" tidak dapat diajukan lagi sampai pengajuan sebelumnya diproses.
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kode Alat</label>
                        <input type="text" id="displayKodeAlat" class="form-control" readonly
                               placeholder="Otomatis terisi setelah pilih alat" tabindex="-1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cabang</label>
                        <input type="text" id="displayCabang" class="form-control" readonly
                               placeholder="Otomatis terisi setelah pilih alat" tabindex="-1">
                    </div>
                </div>
            @endif

            {{-- Nomor Aset & Detail Lokasi --}}
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nomor Aset <span class="text-danger">*</span></label>
                    <input type="text" name="nomor_aset" class="form-control" required
                           placeholder="Contoh: AST-2026-00123">
                    <div class="form-text">Nomor aset khusus untuk pengajuan ini.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Detail Lokasi</label>
                    <input type="text" name="detail_lokasi" class="form-control"
                        value="{{ $selectedAlat->detail_lokasi ?? '' }}"
                        placeholder="Contoh: Rak 3 Gudang Belakang">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tanggal Terbit Alat <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_terbit_alat" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kondisi Alat <span class="text-danger">*</span></label>
                    <select name="kondisi_alat" class="form-select" required>
                        <option value="Baik">Baik</option>
                        <option value="Improvement">Improvement</option>
                    </select>
                    <div class="form-text">Alat rusak berat tidak melalui alur ini — masuk ke gudang.</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Penjelasan Kondisi Alat</label>
                <textarea name="penjelasan_kondisi" class="form-control" rows="2"
                    placeholder="Jelaskan detail kondisi alat saat ini..."></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Alasan / Catatan Pengajuan</label>
                <textarea name="alasan_idle" class="form-control" rows="4"
                    placeholder="Contoh: Alat sudah tidak digunakan di lokasi ini, dialihkan jadi idle untuk dapat dipinjam unit lain."></textarea>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Dokumen Pendukung</label>
                <input type="file" name="dokumen[]" class="form-control" multiple
                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                <div class="form-text">
                    Bisa unggah lebih dari satu file. Format yang didukung: PDF, JPG, PNG, Word, Excel. Maksimal 10MB per file.
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i> Simpan Pengajuan
                </button>
                <a href="{{ route('admin.peralatan-idle.index') }}" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const pilihAlatEl = document.getElementById('pilihAlat');
    if (pilihAlatEl) {
        pilihAlatEl.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            document.getElementById('displayKodeAlat').value = selected.dataset.kodeAlat || '';
            document.getElementById('displayCabang').value = selected.dataset.cabang || '';
        });
    }
</script>
@endpush