# Aplikasi Absensi dan Penggajian Karyawan
## Kafe Satu Per Dua Kopitiam

Sistem informasi absensi dan penggajian karyawan berbasis mobile menggunakan geolocation dan face recognition.

### Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Mobile | Flutter 3.41, Dart 3.11, Riverpod, GoRouter, Google ML Kit |
| Backend | Laravel 13, PHP 8.3, MySQL 8.4, Sanctum |
| Web Admin | Blade, Tailwind CSS 4, Alpine.js, Leaflet.js |
| AI Service | Python 3.10, FastAPI, MTCNN, FaceNet (VGGFace2) |
| Database | MySQL 8.4 (Laragon) |

### Struktur Folder

```
├── mobile/         # Flutter app (karyawan)
├── web/            # Laravel (backend + admin dashboard)
├── ai-service/     # FastAPI (face recognition)
├── docs/           # Dokumentasi pengujian
└── start-web.bat   # Script jalankan server
```

### Cara Menjalankan

#### Prerequisites
- Laragon (MySQL)
- PHP 8.3+
- Node.js 18+
- Flutter 3.41+
- Python 3.10+

#### 1. Database
```bash
# Buka Laragon → Start All
cd web
php artisan migrate:fresh --seed
```

#### 2. Backend Laravel
```bash
cd web
php artisan serve --host=0.0.0.0
# Server: http://localhost:8000
```

#### 3. FastAPI (Face Recognition)
```bash
cd ai-service
python -m uvicorn main:app --host 0.0.0.0 --port 8001
# Server: http://localhost:8001
```

#### 4. Mobile App
```bash
cd mobile
adb reverse tcp:8000 tcp:8000
adb reverse tcp:8001 tcp:8001
flutter run
```

#### 5. Web Admin
Buka http://localhost:8000 di browser.

### Akun Uji

| Username | Password | Role |
|----------|----------|------|
| admin | password | Admin web |
| andisaputra | 123456 | Karyawan |
| saripratiwi | 123456 | Karyawan |
| budisantoso | 123456 | Karyawan |
| dewilestari | 123456 | Karyawan |
| rizkypratama | 123456 | Karyawan (nonaktif) |

### API Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | /api/auth/login | Login karyawan |
| GET | /api/auth/me | Data user + status enrollment |
| POST | /api/auth/logout | Logout |
| GET | /api/attendance/today | Absensi hari ini |
| POST | /api/attendance/clock-in | Absen masuk (validasi geofence) |
| POST | /api/attendance/clock-out | Absen pulang |
| GET | /api/attendance/history | Riwayat per bulan |
| GET | /api/leaves | List izin |
| POST | /api/leaves | Ajukan izin |
| GET | /api/locations/active | Lokasi kerja aktif |
| POST | /api/face/enroll | Daftarkan wajah (3 frame) |
| POST | /api/face/verify | Verifikasi wajah |

### Fitur Utama

**Mobile (Karyawan):**
- Login dengan Sanctum token
- Registrasi wajah 3-tahap (blink, noleh kiri, noleh kanan) via ML Kit
- Absensi masuk/pulang dengan validasi GPS geofence + verifikasi wajah
- Riwayat kehadiran per bulan
- Pengajuan izin
- Profil karyawan

**Web Admin:**
- Dashboard ringkasan harian
- Manajemen karyawan (CRUD)
- Konfigurasi lokasi geofence (Leaflet map picker)
- Monitor absensi real-time
- Approval izin
- Manajemen bonus
- Rekap payroll otomatis: (Hadir x Tarif) + Bonus
- Audit log verifikasi wajah

**Face Recognition:**
- MTCNN untuk deteksi wajah
- FaceNet (VGGFace2) untuk embedding 512-dimensi
- Cosine similarity threshold 0.7
- Liveness detection (blink + head yaw)
- Mean embedding dari 3 frame untuk stabilitas

### Penulis

Muhammad Ichsan Firdaus (3202316004)
Politeknik Negeri Pontianak