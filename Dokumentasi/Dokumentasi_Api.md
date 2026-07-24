# 📱 Panduan Mobile App — Monitron
**Untuk Developer Mobile (Flutter)**

---

## 📋 Daftar Isi
- [Informasi API](#informasi-api)
- [Setup Flutter Project](#setup-flutter-project)
- [Struktur Folder](#struktur-folder)
- [Dependencies](#dependencies)
- [API Endpoint Teknisi](#api-endpoint-teknisi)
- [Contoh Implementasi](#contoh-implementasi)
- [Alur Aplikasi](#alur-aplikasi)

---

## 🌐 Informasi API

```
Base URL  : http://127.0.0.1:8000/api
Auth      : Bearer Token (Sanctum)
Format    : JSON
```

> ⚠️ Untuk production, ganti Base URL dengan IP server yang bisa diakses device Android.
> Contoh: `http://192.168.1.100:8000/api`

**Header wajib di setiap request (kecuali login):**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## 🛠️ Setup Flutter Project

### 1. Buat project baru
```bash
flutter create monitron_mobile --org com.monitron --platforms android
cd monitron_mobile
```

### 2. Tambah dependencies di `pubspec.yaml`
```yaml
dependencies:
  flutter:
    sdk: flutter

  # HTTP
  http: ^1.2.1

  # State Management
  provider: ^6.1.2

  # Local Storage (simpan token)
  shared_preferences: ^2.3.2

  # Navigation
  go_router: ^14.2.7

  # UI
  google_fonts: ^6.2.1
  fl_chart: ^0.69.0         # untuk grafik performa
  intl: ^0.19.0              # format tanggal & angka
  shimmer: ^3.0.0            # loading skeleton

dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^4.0.0
```

### 3. Install dependencies
```bash
flutter pub get
```

### 4. Konfigurasi Android (izin internet)
Buka `android/app/src/main/AndroidManifest.xml`, tambahkan sebelum `<application`:
```xml
<uses-permission android:name="android.permission.INTERNET"/>
```

Untuk koneksi ke localhost (HTTP), tambahkan di dalam tag `<application`:
```xml
android:usesCleartextTraffic="true"
```

---

## 📁 Struktur Folder

```
lib/
├── main.dart
├── core/
│   ├── constants/
│   │   └── api_constants.dart      # Base URL & endpoint
│   ├── services/
│   │   └── api_service.dart        # HTTP client
│   └── utils/
│       └── auth_helper.dart        # Simpan/ambil token
├── models/
│   ├── user_model.dart
│   ├── alat_model.dart
│   ├── log_harian_model.dart
│   └── notifikasi_model.dart
├── providers/
│   ├── auth_provider.dart
│   ├── alat_provider.dart
│   ├── log_provider.dart
│   └── notifikasi_provider.dart
└── screens/
    ├── login/
    │   └── login_screen.dart
    ├── home/
    │   └── home_screen.dart        # Daftar alat
    ├── input_log/
    │   └── input_log_screen.dart
    ├── history/
    │   └── history_screen.dart
    ├── notifikasi/
    │   └── notifikasi_screen.dart
    └── widgets/
        ├── alat_card.dart
        ├── performa_badge.dart
        └── loading_shimmer.dart
```

---

## 📦 Dependencies

### `api_constants.dart`
```dart
class ApiConstants {
  // Ganti dengan IP server saat testing di device fisik
  static const String baseUrl = 'http://10.0.2.2:8000/api'; // emulator
  // static const String baseUrl = 'http://192.168.1.100:8000/api'; // device fisik

  // Auth
  static const String login  = '$baseUrl/login';
  static const String logout = '$baseUrl/logout';
  static const String me     = '$baseUrl/me';

  // Teknisi
  static const String alat         = '$baseUrl/teknisi/alat';
  static const String inputLog     = '$baseUrl/teknisi/log';
  static const String notifikasi   = '$baseUrl/teknisi/notifikasi';

  static String history(int idAlat) => '$baseUrl/teknisi/log/$idAlat/history';
  static String detailLog(int idLog) => '$baseUrl/teknisi/log/detail/$idLog';
  static String bacaNotifikasi(int id) => '$baseUrl/teknisi/notifikasi/$id/baca';
}
```

---

## 📡 API Endpoint Teknisi

### 🔐 Login
```
POST /login
```
**Body:**
```json
{
    "username": "teknisi_cgk1",
    "password": "teknisi123"
}
```
**Response sukses:**
```json
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "token": "1|xxxxxxxxxxxxxxxx",
        "pengguna": {
            "id": 2,
            "nama": "Teknisi CGK 1",
            "username": "teknisi_cgk1",
            "role": "teknisi",
            "id_bandara": 1,
            "nama_bandara": "Soekarno-Hatta"
        }
    }
}
```
**Response gagal:**
```json
{
    "success": false,
    "message": "Username atau password salah"
}
```

---

### 🚪 Logout
```
POST /logout
Header: Authorization: Bearer {token}
```
**Response:**
```json
{
    "success": true,
    "message": "Logout berhasil"
}
```

---

### 🔧 Get Daftar Alat
```
GET /teknisi/alat
Header: Authorization: Bearer {token}
```
**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id_alat": 1,
            "nama_alat": "X-Ray",
            "merek": "Smiths Detection",
            "ip_address": "192.168.1.10",
            "status": "Aktif",
            "lokasi": {
                "id_lokasi": 1,
                "nama_lokasi": "Terminal 1",
                "bandara": {
                    "nama_bandara": "Soekarno-Hatta"
                }
            },
            "kategori": {
                "nama_kategori": "Security"
            }
        }
    ]
}
```

---

### 📝 Input Log Harian
```
POST /teknisi/log
Header: Authorization: Bearer {token}
```
**Body:**
```json
{
    "id_alat": 1,
    "tanggal": "2026-05-06",
    "jam_terputus": 2,
    "kondisi": "Normal",
    "catatan": "Gangguan jaringan sesaat"
}
```

| Field | Tipe | Required | Keterangan |
|---|---|---|---|
| id_alat | integer | ✅ | ID alat |
| tanggal | date | ✅ | Format: YYYY-MM-DD |
| jam_terputus | decimal | ✅ | 0.0 - 24.0 |
| kondisi | string | ✅ | `Normal`, `Gangguan`, `Rusak` |
| catatan | string | ❌ | Maks 500 karakter |

**Response sukses:**
```json
{
    "success": true,
    "message": "Data berhasil disimpan",
    "data": {
        "log": {
            "id_log": 1,
            "tanggal": "2026-05-06",
            "jam_operasional": 24,
            "jam_terputus": 2,
            "performa": 91.67,
            "kondisi": "Normal"
        },
        "performa": "91.67%"
    }
}
```

**Response error duplikat (422):**
```json
{
    "success": false,
    "message": "Data untuk alat ini pada tanggal tersebut sudah ada"
}
```

**Response error alat tidak ditemukan (403):**
```json
{
    "success": false,
    "message": "Alat tidak ditemukan atau tidak dalam bandara Anda"
}
```

---

### 📜 History Log Per Alat
```
GET /teknisi/log/{id_alat}/history
Header: Authorization: Bearer {token}
```
**Query Params:**

| Param | Tipe | Keterangan |
|---|---|---|
| bulan | integer | 1 - 12 |
| tahun | integer | Contoh: 2026 |
| per_page | integer | Default: 10 |

**Contoh:**
```
GET /teknisi/log/1/history?bulan=5&tahun=2026
```

**Response:**
```json
{
    "success": true,
    "alat": {
        "id_alat": 1,
        "nama_alat": "X-Ray",
        "lokasi": "Terminal 1"
    },
    "data": {
        "current_page": 1,
        "data": [
            {
                "id_log": 5,
                "tanggal": "2026-05-06",
                "jam_operasional": 24,
                "jam_terputus": 2,
                "performa": 91.67,
                "kondisi": "Normal",
                "catatan": null
            }
        ],
        "last_page": 1,
        "per_page": 10,
        "total": 5
    }
}
```

---

### 🔔 Get Notifikasi
```
GET /teknisi/notifikasi
Header: Authorization: Bearer {token}
```
**Query Params:**

| Param | Keterangan |
|---|---|
| per_page | Default: 15 |

**Response:**
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id_notifikasi": 1,
                "id_alat": 1,
                "tanggal": "2026-05-06 08:00:00",
                "pesan": "Performa alat X-Ray Warning: 75.00% pada tanggal 2026-05-06",
                "status": "Belum Dibaca"
            }
        ]
    }
}
```

---

### ✅ Tandai Notifikasi Dibaca
```
PATCH /teknisi/notifikasi/{id}/baca
Header: Authorization: Bearer {token}
```
**Response:**
```json
{
    "success": true,
    "message": "Notifikasi ditandai sebagai dibaca"
}
```

---

## 💡 Contoh Implementasi

### `api_service.dart`
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../core/constants/api_constants.dart';

class ApiService {
  // Ambil token dari storage
  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token');
  }

  // Header dengan token
  static Future<Map<String, String>> _headers() async {
    final token = await getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // GET request
  static Future<Map<String, dynamic>> get(String url) async {
    final response = await http.get(
      Uri.parse(url),
      headers: await _headers(),
    );
    return jsonDecode(response.body);
  }

  // POST request
  static Future<Map<String, dynamic>> post(
    String url,
    Map<String, dynamic> body,
  ) async {
    final response = await http.post(
      Uri.parse(url),
      headers: await _headers(),
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  // PATCH request
  static Future<Map<String, dynamic>> patch(String url) async {
    final response = await http.patch(
      Uri.parse(url),
      headers: await _headers(),
    );
    return jsonDecode(response.body);
  }
}
```

---

### `auth_provider.dart`
```dart
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../core/services/api_service.dart';
import '../core/constants/api_constants.dart';

class AuthProvider extends ChangeNotifier {
  bool _isLoading = false;
  String? _errorMessage;
  Map<String, dynamic>? _user;

  bool get isLoading    => _isLoading;
  String? get error     => _errorMessage;
  Map<String, dynamic>? get user => _user;
  bool get isLoggedIn   => _user != null;

  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final data = await ApiService.post(ApiConstants.login, {
        'username': username,
        'password': password,
      });

      if (data['success'] == true) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', data['data']['token']);

        _user = data['data']['pengguna'];
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = data['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = 'Terjadi kesalahan koneksi';
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    await ApiService.post(ApiConstants.logout, {});
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    _user = null;
    notifyListeners();
  }
}
```

---

### `alat_provider.dart`
```dart
import 'package:flutter/material.dart';
import '../core/services/api_service.dart';
import '../core/constants/api_constants.dart';

class AlatProvider extends ChangeNotifier {
  List<dynamic> _alat = [];
  bool _isLoading = false;

  List<dynamic> get alat  => _alat;
  bool get isLoading       => _isLoading;

  Future<void> fetchAlat() async {
    _isLoading = true;
    notifyListeners();

    try {
      final data = await ApiService.get(ApiConstants.alat);
      if (data['success'] == true) {
        _alat = data['data'];
      }
    } catch (e) {
      debugPrint('Error fetch alat: $e');
    }

    _isLoading = false;
    notifyListeners();
  }
}
```

---

### `main.dart`
```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'providers/auth_provider.dart';
import 'providers/alat_provider.dart';
import 'providers/log_provider.dart';
import 'providers/notifikasi_provider.dart';
import 'screens/login/login_screen.dart';
import 'screens/home/home_screen.dart';

void main() {
  runApp(const MonitronApp());
}

class MonitronApp extends StatelessWidget {
  const MonitronApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => AlatProvider()),
        ChangeNotifierProvider(create: (_) => LogProvider()),
        ChangeNotifierProvider(create: (_) => NotifikasiProvider()),
      ],
      child: MaterialApp(
        title: 'Monitron',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1a56db)),
          useMaterial3: true,
          fontFamily: 'Plus Jakarta Sans',
        ),
        home: const AuthWrapper(),
      ),
    );
  }
}

class AuthWrapper extends StatelessWidget {
  const AuthWrapper({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return auth.isLoggedIn ? const HomeScreen() : const LoginScreen();
  }
}
```

---

## 🔄 Alur Aplikasi

```
App Launch
    └── Cek token di SharedPreferences
            ├── Ada token → HomeScreen (Daftar Alat)
            └── Tidak ada → LoginScreen

LoginScreen
    └── POST /login
            ├── Sukses → Simpan token → HomeScreen
            └── Gagal  → Tampilkan error

HomeScreen (Daftar Alat)
    └── GET /teknisi/alat
            └── Tap alat → InputLogScreen atau HistoryScreen

InputLogScreen
    └── POST /teknisi/log
            ├── Sukses → Tampilkan performa → Kembali
            └── Gagal  → Tampilkan error (duplikat/validasi)

HistoryScreen
    └── GET /teknisi/log/{id_alat}/history?bulan=X&tahun=Y
            └── Filter bulan/tahun

NotifikasiScreen
    └── GET /teknisi/notifikasi
            └── Tap notifikasi → PATCH /teknisi/notifikasi/{id}/baca
```

---

## ⚠️ Catatan Penting

1. **Base URL** — Saat testing di emulator gunakan `10.0.2.2`, di device fisik gunakan IP laptop (cek dengan `ipconfig`)
2. **Token expired** — Jika response `401`, redirect ke login dan hapus token dari SharedPreferences
3. **Format tanggal** — Selalu kirim format `YYYY-MM-DD` (gunakan package `intl`)
4. **Jam terputus** — Input dalam jam, bisa decimal (contoh: 1.5 = 1 jam 30 menit)
5. **Role** — Pastikan hanya login dengan role `teknisi`, bukan `admin`

---

*Dokumentasi ini dibuat untuk kolaborasi project Monitron.*
*Backend & Web Dashboard: [nama kamu]*
*Mobile App: [nama teman kamu]*