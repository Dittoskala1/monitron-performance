# Dokumentasi Proses Bisnis Peralatan Idle dan Mutasi Fasilitas & Peralatan

## 1. Pendahuluan

Proses bisnis ini merupakan sistem manajemen siklus hidup aset yang diterapkan di lingkungan bandara. Sistem bertujuan untuk memastikan bahwa setiap peralatan dan fasilitas yang sudah tidak digunakan (idle) dapat dikelola secara optimal dan didistribusikan ke bandara lain yang membutuhkan melalui proses mutasi yang terstruktur, terdokumentasi, dan memiliki mekanisme persetujuan yang jelas.

Secara umum, proses bisnis terdiri dari dua alur utama yang saling berkaitan:

1. **Proses Pengajuan Peralatan Idle**
2. **Proses Mutasi Fasilitas dan Peralatan**

Sistem ini menerapkan prinsip transparansi, akuntabilitas, dan pengawasan berjenjang sehingga seluruh aktivitas pengelolaan aset dapat dipertanggungjawabkan.

---

# 2. Alur Pengajuan Peralatan Idle

## 2.1 Tujuan

Proses ini bertujuan untuk mengidentifikasi dan menetapkan peralatan atau fasilitas yang sudah tidak digunakan agar dapat dimanfaatkan kembali melalui mekanisme mutasi ke unit atau bandara lain yang membutuhkan.

---

## 2.2 Aktor yang Terlibat

| Aktor | Peran |
|---------|---------|
| Admin AFET KC Bandara | Mengajukan peralatan idle |
| Divisi Head | Melakukan verifikasi dan persetujuan |
| General Manager (GM) | Menerima notifikasi informasi |
| Sistem | Mengelola status dan notifikasi |

---

## 2.3 Proses Pengajuan

### Langkah 1 - Login Sistem

Admin AFET KC Bandara melakukan login menggunakan akun yang telah terdaftar dan disetujui oleh Admin Regional.

**Ketentuan:**
- Hanya dapat mengakses data aset milik bandara sendiri.
- Tidak dapat mengakses aset milik bandara lain.

---

### Langkah 2 - Membuka Menu Peralatan Idle

Admin membuka:

```
Sidebar → Peralatan Idle → Form Pengajuan
```

---

### Langkah 3 - Mengisi Form Pengajuan

Admin melengkapi informasi berikut:

#### Data Peralatan
- Nama Peralatan
- Nomor Seri
- Spesifikasi

#### Detail Lokasi
- Lokasi Saat Ini
- Unit Penanggung Jawab

#### Kondisi Peralatan
- Baik
- Rusak Ringan
- Rusak Berat

#### Alasan Idle
Contoh:
- Digantikan peralatan baru
- Tidak ada kebutuhan operasional
- Efisiensi penggunaan aset

#### Dokumen Pendukung (Opsional)
- Foto peralatan
- Berita acara
- Laporan teknis

---

### Validasi Sistem

Sistem akan memeriksa bahwa:

- Peralatan berasal dari bandara yang sedang login.
- Tidak terdapat pengajuan aktif lainnya untuk peralatan yang sama.

Jika validasi berhasil:

```text
Status = Waiting Approval
```

---

## 2.4 Proses Approval

### Aktor

**Divisi Head**

### Tugas

- Memeriksa kondisi aset.
- Memverifikasi alasan idle.
- Memastikan kelayakan pengajuan.

### Keputusan

#### Approve

```text
Status = Approved
```

#### Reject

```text
Status = Rejected
```

Divisi Head wajib mengisi alasan penolakan.

---

## 2.5 Notifikasi Hasil

Sistem mengirimkan notifikasi kepada:

1. General Manager (GM)
2. Admin AFET KC Bandara Pemohon

---

## 2.6 Pengajuan Ulang

Jika status:

```text
Rejected
```

Maka Admin dapat:

1. Membuka daftar pengajuan idle.
2. Memilih tombol **Ajukan Ulang**.
3. Melakukan perbaikan data.
4. Mengirim ulang pengajuan.

Status akan kembali menjadi:

```text
Waiting Approval
```

dan proses approval berulang.

---

## 2.7 Penyelesaian Pengajuan Idle

Jika pengajuan disetujui:

```text
Status = Approved
```

Maka sistem akan:

1. Mengubah status aset menjadi:

```text
Available
```

2. Memindahkan aset ke menu:

```text
Unused
```

3. Menandakan aset siap untuk proses mutasi.

---

# 3. Alur Mutasi Fasilitas dan Peralatan

## 3.1 Tujuan

Memindahkan fasilitas atau peralatan idle dari bandara pemberi ke bandara penerima yang membutuhkan.

---

## 3.2 Aktor yang Terlibat

| Aktor | Peran |
|---------|---------|
| Admin AFET KC Penerima | Mengajukan kebutuhan mutasi |
| Admin AFET Regional | Melakukan verifikasi dan persetujuan |
| KC Pemberi | Mengonfirmasi ketersediaan aset |
| GM Penerima | Menerima notifikasi |
| GM Pemberi | Menerima notifikasi |
| CEO | Menerima notifikasi |
| Sistem | Mengelola alur dan notifikasi |

---

## 3.3 Input Mapping Kebutuhan

Admin AFET KC Penerima menginput kebutuhan fasilitas/peralatan yang diperlukan.

### Data yang Diinput

- Nama fasilitas/peralatan
- Jumlah kebutuhan
- Lokasi tujuan
- Alasan kebutuhan
- Dokumen pendukung

Setelah disimpan, data mapping masuk ke sistem.

---

## 3.4 Notifikasi Awal

Sistem mengirimkan notifikasi kepada:

1. Admin AFET Regional
2. General Manager KC Penerima
3. General Manager KC Pemberi
4. CEO

Tujuannya untuk memberikan informasi bahwa terdapat pengajuan mutasi baru.

---

## 3.5 Verifikasi oleh Admin Regional

### Tugas

Admin Regional melakukan:

- Verifikasi kebutuhan.
- Pemeriksaan dokumen.
- Evaluasi kelayakan mutasi.

---

### Keputusan

#### Approve

```text
Status = Approved
```

Pengajuan dilanjutkan ke tahap berikutnya.

---

#### Reject

```text
Status = Rejected
```

Pengajuan dikembalikan kepada KC Penerima.

Admin dapat memperbaiki data dan mengajukan kembali.

---

## 3.6 Notifikasi Hasil Verifikasi

### Jika Approve

Notifikasi dikirim ke:

- KC Penerima
- KC Pemberi

### Jika Reject

Notifikasi hanya dikirim ke:

- KC Penerima

KC Pemberi tidak menerima notifikasi karena proses berhenti pada tahap permintaan.

---

## 3.7 Konfirmasi Fasilitas Idle oleh KC Pemberi

Setelah pengajuan disetujui, KC Pemberi melakukan verifikasi akhir.

### Tujuan

Memastikan bahwa:

- Fasilitas benar-benar idle.
- Fasilitas tersedia.
- Tidak ada kendala operasional maupun administratif.

Jika dikonfirmasi, proses dilanjutkan ke tahap mobilisasi.

---

## 3.8 Mobilisasi

Mobilisasi adalah proses pemindahan fisik fasilitas atau peralatan dari KC Pemberi ke KC Penerima.

### Aktivitas

- Pengiriman aset
- Serah terima aset
- Dokumentasi pemindahan

---

### Notifikasi Mobilisasi

Sistem mengirimkan notifikasi kepada:

1. KC Penerima
2. KC Pemberi
3. GM Pemberi
4. GM Penerima
5. CEO

Tujuannya untuk memastikan seluruh pihak mengetahui bahwa proses mutasi sedang berlangsung.

---

## 3.9 Sertifikasi

Setelah mobilisasi selesai, dilakukan penyusunan dokumen sertifikasi.

### Dokumen Sertifikasi Berisi

- Bukti mobilisasi
- Dokumen mutasi
- Data aset
- Dokumentasi pendukung

---

### Distribusi Dokumen

Dokumen sertifikasi dikirim kepada:

1. HO Regional
2. KC Penerima
3. KC Pemberi

---

### Catatan

Tahap sertifikasi **bukan proses approval**, melainkan proses dokumentasi dan arsip resmi untuk:

- Audit
- Kepatuhan administrasi
- Pelacakan riwayat aset

---

# 4. Ringkasan Status

## Status Peralatan Idle

| Status | Keterangan |
|----------|----------|
| Waiting Approval | Menunggu persetujuan Divisi Head |
| Approved | Pengajuan disetujui |
| Rejected | Pengajuan ditolak |
| Available | Siap dimutasi |
| Unused | Tersedia dalam daftar aset idle |

---

## Status Mutasi

| Status | Keterangan |
|----------|----------|
| Submitted | Pengajuan kebutuhan telah dibuat |
| Waiting Approval | Menunggu verifikasi Regional |
| Approved | Disetujui Regional |
| Rejected | Ditolak Regional |
| Confirmed | Dikonfirmasi KC Pemberi |
| Mobilization | Dalam proses pemindahan |
| Certified | Mutasi selesai dan terdokumentasi |

---

# 5. Ringkasan Peran dan Tanggung Jawab

| Peran | Tanggung Jawab |
|---------|---------|
| Admin AFET KC Bandara | Mengajukan idle dan kebutuhan mutasi |
| Divisi Head | Menyetujui atau menolak pengajuan idle |
| Admin AFET Regional | Menyetujui atau menolak mutasi |
| KC Pemberi | Mengonfirmasi ketersediaan aset |
| KC Penerima | Mengajukan kebutuhan aset |
| General Manager | Monitoring dan pengawasan |
| CEO | Monitoring strategis |
| HO Regional | Penyimpanan arsip sertifikasi |

---

# 6. Kesimpulan

Proses bisnis Peralatan Idle dan Mutasi Fasilitas & Peralatan dirancang untuk memastikan seluruh aset bandara dapat dimanfaatkan secara optimal. Setiap aset yang tidak digunakan harus melalui proses identifikasi, verifikasi, persetujuan, dan dokumentasi yang jelas sebelum dapat dipindahkan ke lokasi lain.

Melalui mekanisme notifikasi, approval berjenjang, validasi data, serta sertifikasi akhir, sistem mampu menciptakan pengelolaan aset yang transparan, akuntabel, dan terdokumentasi dengan baik. Pendekatan ini mendukung prinsip efisiensi pemanfaatan aset sekaligus meminimalkan risiko kesalahan administrasi maupun penyalahgunaan wewenang.