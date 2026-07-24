Berikut dokumentasi lengkap fitur Peralatan Idle & Booking dalam format Markdown (.md):

---

# 📋 DOKUMENTASI FITUR PERALATAN IDLE & BOOKING

## PT Angkasa Pura II - Monitoring Alat

---

## 📑 DAFTAR ISI

1. [Pendahuluan](#1-pendahuluan)
2. [Aktor Sistem](#2-aktor-sistem)
3. [Fitur Peralatan Idle](#3-fitur-peralatan-idle)
4. [Fitur Peralatan Booking](#4-fitur-peralatan-booking)
5. [Alur Proses Bisnis](#5-alur-proses-bisnis)
6. [Status Ketersediaan Alat](#6-status-ketersediaan-alat)
7. [Notifikasi](#7-notifikasi)
8. [Struktur Database](#8-struktur-database)
9. [Hak Akses Berdasarkan Role](#9-hak-akses-berdasarkan-role)

---

## 1. PENDAHULUAN

### 1.1 Deskripsi Fitur

Fitur Peralatan Idle & Booking adalah modul dalam sistem Monitoring Alat yang memungkinkan:

1. **Idle Alat**: Proses menandai alat yang tidak terpakai dan memindahkannya ke lokasi **Unused**.
2. **Booking Alat**: Proses peminjaman alat antar bandara atau antar terminal dalam satu bandara.
3. **Pengembalian Alat**: Proses mengembalikan alat setelah selesai digunakan.

### 1.2 Tujuan

- Memaksimalkan utilisasi alat antar bandara
- Mencatat riwayat peminjaman alat
- Memastikan kepemilikan alat tetap jelas
- Memudahkan koordinasi antar bandara

---

## 2. AKTOR SISTEM

| Role | Deskripsi |
|------|-----------|
| **AFET Bandara** | Pengguna dari masing-masing bandara (CGK, SBY, KJT, HLP, BDO) yang dapat mengajukan idle, booking, dan pengembalian |
| **AFET Regional** | Pengguna pusat yang memiliki kewenangan untuk approve/reject pengajuan idle dan booking |

---

## 3. FITUR PERALATAN IDLE

### 3.1 Ajukan Idle

**Deskripsi**: AFET Bandara mengajukan alat yang tidak terpakai untuk dipindahkan ke Unused.

**Endpoint**: `POST /admin/peralatan-idle`

**Flow**:
1. User memilih alat yang akan di-idle-kan
2. Mengisi form (detail lokasi, kondisi alat, alasan idle)
3. Upload dokumen pendukung (opsional)
4. Sistem menyimpan pengajuan dengan status `Waiting Approval`

**Validasi**:
- Alat harus milik bandara yang login
- Alat tidak boleh memiliki pengajuan aktif
- Kondisi alat: `Baik`, `Rusak Ringan`, `Rusak Berat`

**Kondisi Khusus**:
- Jika kondisi **Rusak Berat**, pengajuan **langsung Approved** (tanpa approval)
- Status ketersediaan menjadi `not_available`
- Notifikasi dikirim ke AFET Regional

### 3.2 Approve Idle

**Deskripsi**: AFET Regional menyetujui pengajuan idle.

**Endpoint**: `POST /admin/peralatan-idle/{id}/approve`

**Flow**:
1. AFET Regional melihat detail pengajuan
2. Klik tombol Approve
3. Status pengajuan menjadi `Approved`
4. Status ketersediaan menjadi `available`
5. Alat siap di-booking oleh bandara lain

**Validasi**:
- Hanya AFET Regional yang bisa approve
- Status harus `Waiting Approval`
- Kondisi `Rusak Berat` tidak perlu approve (otomatis)

### 3.3 Reject Idle

**Deskripsi**: AFET Regional menolak pengajuan idle.

**Endpoint**: `POST /admin/peralatan-idle/{id}/reject`

**Flow**:
1. AFET Regional melihat detail pengajuan
2. Klik tombol Reject
3. Isi alasan penolakan
4. Status pengajuan menjadi `Rejected`
5. Status ketersediaan menjadi `not_available`
6. Alat dikembalikan ke lokasi asal

**Validasi**:
- Hanya AFET Regional yang bisa reject
- Status harus `Waiting Approval`
- Kondisi `Rusak Berat` tidak bisa di-reject

### 3.4 Tarik Kembali

**Deskripsi**: AFET Bandara menarik kembali alat dari Unused (tanpa approval).

**Endpoint**: `POST /admin/peralatan-idle/{id}/tarik-kembali`

**Flow**:
1. AFET Bandara melihat daftar idle
2. Klik tombol "Tarik Kembali"
3. Alat pindah ke lokasi asal
4. Status ketersediaan menjadi `not_available`

**Kondisi Muncul Tombol**:
- Status pengajuan = `Approved`
- Status ketersediaan = `available`
- Alat berada di Unused
- User adalah pemilik alat (berdasarkan `id_bandara`)

**Validasi**:
- Hanya AFET Bandara yang bisa tarik kembali
- Hanya pemilik alat yang bisa tarik kembali
- Alat tidak sedang di-booking

### 3.5 Ajukan Ulang Idle

**Deskripsi**: AFET Bandara mengajukan ulang pengajuan yang ditolak.

**Endpoint**: `PUT /admin/peralatan-idle/{id}`

**Flow**:
1. User memperbaiki data form
2. Klik Simpan
3. Status kembali menjadi `Waiting Approval`
4. Alat pindah ke Unused

**Validasi**:
- Hanya pemohon yang bisa ajukan ulang
- Status harus `Rejected`

---

## 4. FITUR PERALATAN BOOKING

### 4.1 Ajukan Booking

**Deskripsi**: AFET Bandara mengajukan peminjaman alat dari bandara lain.

**Endpoint**: `POST /admin/peralatan-booking`

**Flow**:
1. User memilih alat yang available dari daftar idle
2. Memilih lokasi tujuan (terminal di bandara sendiri)
3. Mengisi detail lokasi tujuan
4. Mengisi keperluan peminjaman
5. Upload dokumen pendukung (opsional)
6. Sistem menyimpan pengajuan dengan status `Waiting Approval`

**Validasi**:
- Alat harus status `Approved` dan `available`
- Alat tidak boleh `Rusak Berat`
- Lokasi tujuan harus di bandara yang login
- Pemilik alat tidak bisa booking sendiri
- Satu bandara beda terminal BISA booking
- Satu bandara sama terminal TIDAK BISA booking

**Auto-Select Alat**:
- Jika user klik tombol Booking dari halaman Peralatan Idle, alat sudah terpilih otomatis
- Dropdown alat disabled (tidak bisa diubah)

### 4.2 Approve Booking

**Deskripsi**: AFET Regional menyetujui pengajuan booking.

**Endpoint**: `POST /admin/peralatan-booking/{id}/approve`

**Flow**:
1. AFET Regional melihat detail booking
2. Klik tombol Approve
3. Status booking menjadi `Approved`
4. Status ketersediaan alat menjadi `booked`
5. Alat dipindahkan ke lokasi tujuan

**Validasi**:
- Hanya AFET Regional yang bisa approve
- Status harus `Waiting Approval`

### 4.3 Reject Booking

**Deskripsi**: AFET Regional menolak pengajuan booking.

**Endpoint**: `POST /admin/peralatan-booking/{id}/reject`

**Flow**:
1. AFET Regional melihat detail booking
2. Klik tombol Reject
3. Isi alasan penolakan
4. Status booking menjadi `Rejected`
5. Status ketersediaan alat kembali `available`

**Validasi**:
- Hanya AFET Regional yang bisa reject
- Status harus `Waiting Approval`

### 4.4 Ajukan Pengembalian

**Deskripsi**: AFET Bandara mengajukan pengembalian alat setelah selesai digunakan.

**Endpoint**: `POST /admin/peralatan-booking/{id}/ajukan-pengembalian`

**Flow**:
1. User melihat daftar booking yang sudah Approved
2. Klik tombol "Ajukan Pengembalian"
3. Status pengembalian menjadi `Waiting Approval`

**Validasi**:
- Hanya peminjam yang bisa ajukan pengembalian
- Status booking harus `Approved`
- Status pengembalian harus null

### 4.5 Approve Pengembalian

**Deskripsi**: AFET Regional menyetujui pengembalian alat.

**Endpoint**: `POST /admin/peralatan-booking/{id}/approve-pengembalian`

**Flow**:
1. AFET Regional melihat detail booking
2. Klik tombol Approve Pengembalian
3. Status pengembalian menjadi `Approved`
4. Status ketersediaan alat menjadi `available`
5. Alat kembali ke Unused

**Validasi**:
- Hanya AFET Regional yang bisa approve
- Status pengembalian harus `Waiting Approval`

### 4.6 Reject Pengembalian

**Deskripsi**: AFET Regional menolak pengajuan pengembalian.

**Endpoint**: `POST /admin/peralatan-booking/{id}/reject-pengembalian`

**Flow**:
1. AFET Regional melihat detail booking
2. Klik tombol Reject Pengembalian
3. Isi alasan penolakan
4. Status pengembalian menjadi `Rejected`
5. Alat tetap `booked`

**Validasi**:
- Hanya AFET Regional yang bisa reject
- Status pengembalian harus `Waiting Approval`

### 4.7 Ajukan Ulang Booking

**Deskripsi**: AFET Bandara mengajukan ulang booking yang ditolak.

**Endpoint**: `GET/POST /admin/peralatan-booking/{id}/resubmit`

**Flow**:
1. User melihat booking dengan status `Rejected`
2. Klik tombol "Ajukan Ulang"
3. Perbaiki data form
4. Status kembali menjadi `Waiting Approval`
5. Status ketersediaan alat menjadi `pending_booking`

**Validasi**:
- Hanya pemohon yang bisa ajukan ulang
- Status harus `Rejected`

---

## 5. ALUR PROSES BISNIS

### 5.1 Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           ALUR IDLE & BOOKING                               │
└─────────────────────────────────────────────────────────────────────────────┘

   AFET BANDARA (CGK)               AFET REGIONAL               AFET BANDARA (SBY)
        │                                │                              │
        │  1. Ajukan Idle               │                              │
        │──────────────────────────────►│                              │
        │  (pilih alat, isi form)       │                              │
        │                                │                              │
        │                                │  2. Approve/Reject Idle      │
        │                                │                              │
        │  3. Notifikasi Hasil          │                              │
        │◄──────────────────────────────│                              │
        │                                │                              │
        │                                │                              │
        │  4. Alat di Unused (available)│                              │
        │                                │                              │
        │                                │  5. Lihat Daftar Idle       │
        │                                │◄─────────────────────────────│
        │                                │                              │
        │                                │  6. Ajukan Booking          │
        │                                │─────────────────────────────►│
        │                                │  (pilih alat, pilih lokasi)  │
        │                                │                              │
        │                                │  7. Approve/Reject Booking   │
        │                                │                              │
        │                                │  8. Notifikasi Hasil        │
        │                                │◄─────────────────────────────│
        │                                │                              │
        │                                │  9. Alat di SBY (booked)    │
        │                                │                              │
        │                                │  10. Ajukan Pengembalian    │
        │                                │─────────────────────────────►│
        │                                │                              │
        │                                │  11. Approve/Reject          │
        │                                │      Pengembalian            │
        │                                │                              │
        │                                │  12. Alat ke Unused CGK     │
        │                                │      (available)             │
```

### 5.2 Keterangan Alur

| Step | Deskripsi |
|------|-----------|
| 1-3 | Proses Idle: CGK mengajukan idle → Regional approve → Alat di Unused (available) |
| 4-9 | Proses Booking: SBY melihat daftar idle → Mengajukan booking → Regional approve → Alat pindah ke SBY (booked) |
| 10-12 | Proses Pengembalian: SBY ajukan pengembalian → Regional approve → Alat kembali ke Unused CGK (available) |

---

## 6. STATUS KETERSEDIAAN ALAT

### 6.1 Daftar Status

| Status | Deskripsi | Kapan Digunakan |
|--------|-----------|-----------------|
| `available` | Alat tersedia di Unused, siap di-booking | Setelah idle di-approve |
| `booked` | Alat sedang dipinjam/di-booking | Setelah booking di-approve |
| `pending_booking` | Alat menunggu approval booking | Saat booking diajukan |
| `pending_approval` | Alat menunggu approval idle | Saat idle diajukan |
| `not_available` | Alat tidak tersedia | Saat idle di-reject atau ditarik kembali |

### 6.2 Diagram Transisi Status

```
                    ┌─────────────────────────────────────────────────────────┐
                    │              STATUS KETERSEDIAAN ALAT                    │
                    └─────────────────────────────────────────────────────────┘

                    ┌─────────────────────────────────────────────────────────┐
                    │               Alat di Lokasi Asal                       │
                    └────────────────────┬────────────────────────────────────┘
                                         │
                                         ▼
                    ┌─────────────────────────────────────────────────────────┐
                    │               Ajukan Idle                              │
                    └────────────────────┬────────────────────────────────────┘
                                         │
                                         ▼
                    ┌─────────────────────────────────────────────────────────┐
                    │               pending_approval                          │
                    │         (Menunggu approval idle)                        │
                    └────────────────────┬────────────────────────────────────┘
                                         │
                    ┌────────────────────┴────────────────────┐
                    ▼                                         ▼
     ┌────────────────────────────┐       ┌────────────────────────────┐
     │         available          │       │       not_available        │
     │    (Idle disetujui)        │       │    (Idle ditolak/ditarik)  │
     │     Alat di Unused         │       │    Alat kembali ke asal    │
     └────────────┬───────────────┘       └────────────────────────────┘
                  │
                  ▼
     ┌───────────────────────────────────────────────────────────────────────┐
     │                    Ajukan Booking                                    │
     └────────────────────────────────┬─────────────────────────────────────┘
                                      │
                                      ▼
     ┌───────────────────────────────────────────────────────────────────────┐
     │                    pending_booking                                    │
     │              (Menunggu approval booking)                              │
     └────────────────────────────────┬─────────────────────────────────────┘
                                      │
                  ┌───────────────────┴───────────────────┐
                  ▼                                       ▼
     ┌─────────────────────────┐       ┌─────────────────────────┐
     │         booked          │       │       available         │
     │   (Booking Approved)    │       │  (Booking Rejected)     │
     │    Alat di SBY          │       │    Alat di Unused       │
     └────────────┬────────────┘       └─────────────────────────┘
                  │
                  ▼
     ┌───────────────────────────────────────────────────────────────────────┐
     │              Ajukan Pengembalian                                      │
     └────────────────────────────────┬─────────────────────────────────────┘
                                      │
                                      ▼
     ┌───────────────────────────────────────────────────────────────────────┐
     │            status_pengembalian = Waiting Approval                    │
     └────────────────────────────────┬─────────────────────────────────────┘
                                      │
                  ┌───────────────────┴───────────────────┐
                  ▼                                       ▼
     ┌─────────────────────────┐       ┌─────────────────────────┐
     │       available         │       │         booked          │
     │  (Pengembalian Approved)│       │  (Pengembalian Rejected)│
     │    Alat ke Unused       │       │    Alat tetap di SBY    │
     └─────────────────────────┘       └─────────────────────────┘
```

---

## 7. NOTIFIKASI

### 7.1 Daftar Notifikasi

| Event | Penerima | Pesan |
|-------|----------|-------|
| Idle diajukan | AFET Regional | "Ada pengajuan idle baru dari [Bandara] untuk alat [Nama Alat]" |
| Idle Approved | AFET Pemohon | "Pengajuan idle untuk [Nama Alat] telah disetujui. Alat tersedia untuk booking." |
| Idle Rejected | AFET Pemohon | "Pengajuan idle untuk [Nama Alat] ditolak. Alasan: [alasan]" |
| Idle Otomatis (Rusak Berat) | AFET Regional + Pemohon | "Alat [Nama Alat] (Rusak Berat) otomatis di-idle-kan." |
| Booking diajukan | AFET Regional | "Ada pengajuan booking baru dari [Bandara] untuk alat [Nama Alat]" |
| Booking Approved | AFET Peminjam | "Booking untuk [Nama Alat] disetujui. Alat dipindahkan ke [Lokasi Tujuan]" |
| Booking Rejected | AFET Peminjam | "Booking untuk [Nama Alat] ditolak. Alasan: [alasan]" |
| Pengembalian diajukan | AFET Regional | "Ada pengajuan pengembalian dari [Bandara] untuk alat [Nama Alat]" |
| Pengembalian Approved | AFET Peminjam | "Pengembalian [Nama Alat] disetujui. Alat kembali ke Unused." |
| Pengembalian Rejected | AFET Peminjam | "Pengembalian [Nama Alat] ditolak. Alasan: [alasan]" |

---

## 8. STRUKTUR DATABASE

### 8.1 Tabel Alat

```sql
CREATE TABLE alat (
    id_alat BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_lokasi BIGINT UNSIGNED NOT NULL,
    id_bandara BIGINT UNSIGNED NOT NULL,  -- Kepemilikan alat
    id_kategori BIGINT UNSIGNED NULL,
    kode_alat VARCHAR(255) NULL,
    unit_kerja VARCHAR(255) NULL,
    nama_alat VARCHAR(255) NOT NULL,
    merek VARCHAR(255) NULL,
    ip_address VARCHAR(255) NULL,
    buatan VARCHAR(255) NULL,
    tahun_pembuatan YEAR NULL,
    kondisi_awal VARCHAR(255) NULL,
    status ENUM('Aktif', 'Tidak') DEFAULT 'Aktif',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 8.2 Tabel Pengajuan Idle

```sql
CREATE TABLE pengajuan_idle (
    id_pengajuan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_alat BIGINT UNSIGNED NOT NULL,
    id_lokasi_asal BIGINT UNSIGNED NOT NULL,
    detail_lokasi VARCHAR(255) NULL,
    kondisi_alat ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') DEFAULT 'Baik',
    id_lokasi_unused BIGINT UNSIGNED NOT NULL,
    id_pengguna BIGINT UNSIGNED NOT NULL,
    alasan_idle TEXT NULL,
    status ENUM('Waiting Approval', 'Approved', 'Rejected') DEFAULT 'Waiting Approval',
    status_ketersediaan ENUM('available', 'booked', 'pending_booking', 'pending_approval', 'not_available') NULL,
    tanggal_pengajuan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_keputusan TIMESTAMP NULL,
    id_pengguna_approval BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 8.3 Tabel Pengajuan Booking

```sql
CREATE TABLE pengajuan_booking (
    id_booking BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pengajuan_idle BIGINT UNSIGNED NOT NULL,
    id_lokasi_tujuan BIGINT UNSIGNED NULL,
    detail_lokasi_tujuan VARCHAR(255) NULL,
    id_pengguna_peminjam BIGINT UNSIGNED NOT NULL,
    keperluan TEXT NULL,
    status ENUM('Waiting Approval', 'Approved', 'Rejected') DEFAULT 'Waiting Approval',
    status_pengembalian ENUM('Waiting Approval', 'Approved', 'Rejected') NULL,
    tanggal_pengajuan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_keputusan TIMESTAMP NULL,
    tanggal_pengajuan_pengembalian TIMESTAMP NULL,
    tanggal_keputusan_pengembalian TIMESTAMP NULL,
    id_pengguna_approval BIGINT UNSIGNED NULL,
    id_pengguna_approval_pengembalian BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## 9. HAK AKSES BERDASARKAN ROLE

### 9.1 Matrix Akses

| Fitur | AFET Bandara | AFET Regional |
|-------|--------------|---------------|
| **Peralatan Idle** | | |
| Ajukan Idle | ✅ (alat sendiri) | ❌ |
| Lihat Daftar Idle | ✅ (semua) | ✅ (semua) |
| Approve Idle | ❌ | ✅ |
| Reject Idle | ❌ | ✅ |
| Tarik Kembali | ✅ (milik sendiri) | ❌ |
| Ajukan Ulang Idle | ✅ (milik sendiri) | ❌ |
| **Peralatan Booking** | | |
| Ajukan Booking | ✅ (alat available) | ❌ |
| Lihat Daftar Booking | ✅ (terkait) | ✅ (semua) |
| Approve Booking | ❌ | ✅ |
| Reject Booking | ❌ | ✅ |
| Ajukan Pengembalian | ✅ (milik sendiri) | ❌ |
| Approve Pengembalian | ❌ | ✅ |
| Reject Pengembalian | ❌ | ✅ |
| Ajukan Ulang Booking | ✅ (milik sendiri) | ❌ |

### 9.2 Aturan Kepemilikan Alat

| Kondisi | Bisa Booking? | Keterangan |
|---------|---------------|------------|
| Sama bandara, SAMA terminal | ❌ Tidak | Milik sendiri |
| Sama bandara, BEDA terminal | ✅ Bisa | Beda lokasi |
| BEDA bandara | ✅ Bisa | Lintas bandara |

---

## 10. LAMPIRAN

### 10.1 URL / Endpoint

| Method | URL | Deskripsi |
|--------|-----|-----------|
| **Peralatan Idle** | | |
| GET | `/admin/peralatan-idle` | Daftar pengajuan idle |
| GET | `/admin/peralatan-idle/create` | Form ajukan idle |
| POST | `/admin/peralatan-idle` | Simpan pengajuan idle |
| GET | `/admin/peralatan-idle/{id}` | Detail pengajuan idle |
| POST | `/admin/peralatan-idle/{id}/approve` | Approve idle |
| POST | `/admin/peralatan-idle/{id}/reject` | Reject idle |
| PUT | `/admin/peralatan-idle/{id}` | Update/ajukan ulang idle |
| POST | `/admin/peralatan-idle/{id}/tarik-kembali` | Tarik kembali dari Unused |
| **Peralatan Booking** | | |
| GET | `/admin/peralatan-booking` | Daftar pengajuan booking |
| GET | `/admin/peralatan-booking/create` | Form ajukan booking |
| POST | `/admin/peralatan-booking` | Simpan pengajuan booking |
| GET | `/admin/peralatan-booking/{id}` | Detail pengajuan booking |
| POST | `/admin/peralatan-booking/{id}/approve` | Approve booking |
| POST | `/admin/peralatan-booking/{id}/reject` | Reject booking |
| POST | `/admin/peralatan-booking/{id}/ajukan-pengembalian` | Ajukan pengembalian |
| POST | `/admin/peralatan-booking/{id}/approve-pengembalian` | Approve pengembalian |
| POST | `/admin/peralatan-booking/{id}/reject-pengembalian` | Reject pengembalian |
| GET | `/admin/peralatan-booking/{id}/resubmit` | Form ajukan ulang booking |
| POST | `/admin/peralatan-booking/{id}/resubmit` | Proses ajukan ulang booking |

---

**Dokumentasi ini dibuat berdasarkan Manual Book PT Angkasa Pura II dan implementasi sistem Monitoring Alat.**

---

*Terakhir diupdate: Juli 2026*