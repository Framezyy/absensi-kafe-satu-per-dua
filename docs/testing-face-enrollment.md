# Panduan Testing Face Enrollment & Verifikasi

Tujuan: memastikan data wajah akurat, tidak ada mismatch, dan tidak ada bug
saat enrollment maupun verifikasi absensi untuk setiap akun.

Status awal: data wajah (face_embeddings) SUDAH DIHAPUS. Semua akun belum enroll.

---

## PERSIAPAN (WAJIB sebelum test)

Jalankan 3 hal ini dulu, biarkan tetap nyala selama testing:

1. Laragon: buka Laragon > Start All (MySQL harus nyala)

2. Terminal 1 - Laravel:
   cd "D:\Project TA Absensi\web"
   php artisan serve --host=0.0.0.0

3. Terminal 2 - FastAPI (WAJIB nyala, ini otak pengenalan wajah):
   cd "D:\Project TA Absensi\ai-service"
   python -m uvicorn main:app --host 0.0.0.0 --port 8001

4. Terminal 3 - hubungkan HP (colok USB), lalu:
   adb reverse tcp:8000 tcp:8000
   adb reverse tcp:8001 tcp:8001

Cek FastAPI siap: buka http://localhost:8001/health di browser,
harus muncul {"status":"ok","models_loaded":true}.

TIPS AKURASI: enroll di tempat TERANG, wajah menghadap lurus ke kamera,
tidak backlight (membelakangi jendela/lampu), tidak pakai masker.

---

## AKUN UJI

| Username     | Password | Nama          |
|--------------|----------|---------------|
| andisaputra  | 123456   | Andi Saputra  |
| saripratiwi  | 123456   | Sari Pratiwi  |
| budisantoso  | 123456   | Budi Santoso  |
| dewilestari  | 123456   | Dewi Lestari  |

Siapkan minimal 2 orang berbeda untuk test (mis. Orang A dan Orang B).

---

## TEST 1 - Enrollment Wajah Pertama (Akun Andi, Orang A)

1. Buka aplikasi > login: andisaputra / 123456
2. Karena belum enroll, otomatis masuk halaman Registrasi Wajah
3. Ikuti instruksi:
   - Kedipkan mata (liveness check)
   - Hadapkan wajah lurus ke kamera (auto-capture)
4. Tunggu proses kirim (~3-5 detik)

HASIL DIHARAPKAN:
- Muncul "Wajah berhasil terdaftar"
- Langsung pindah ke Beranda (sekali proses, tidak stuck)

CATAT: berhasil / gagal, berapa detik prosesnya.

---

## TEST 2 - Cegah Duplikat Wajah (Akun Sari, Orang A yang SAMA)

1. Dari Andi: buka tab Profil > Keluar dari Akun
2. Login: saripratiwi / 123456
3. Masuk halaman Registrasi Wajah
4. Daftarkan wajah ORANG A (orang yang sama dengan Andi tadi)
5. Tunggu proses

HASIL DIHARAPKAN:
- DITOLAK dengan pesan: "Wajah ini sudah terdaftar pada akun karyawan
  lain (Andi Saputra)..."
- Muncul tombol "Kembali" > menekan langsung ke halaman login

INI MEMBUKTIKAN: satu wajah tidak bisa dipakai untuk titip absen di 2 akun.

---

## TEST 3 - Enrollment Wajah Berbeda (Akun Sari, Orang B)

1. Login lagi: saripratiwi / 123456
2. Masuk halaman Registrasi Wajah
3. Daftarkan wajah ORANG B (orang berbeda dari Andi)
4. Tunggu proses

HASIL DIHARAPKAN:
- Berhasil (karena wajah beda), pindah ke Beranda

---

## TEST 4 - Verifikasi Absen Wajah BENAR (Akun Andi, Orang A)

1. Login: andisaputra / 123456
2. Tab Beranda > tombol "Absen Masuk Sekarang" (atau menu Absen)
3. Pastikan status "Dalam radius" (kalau luar radius, set lokasi kafe
   di web admin ke lokasi Anda - lihat catatan bawah)
4. Tekan Absen Masuk > ikuti instruksi liveness (kedip/toleh)
5. Verifikasi wajah pakai ORANG A (pemilik akun)

HASIL DIHARAPKAN:
- Wajah COCOK, absen masuk berhasil, kembali ke beranda
- Status berubah jadi "Sudah Masuk"

---

## TEST 5 - Verifikasi Absen Wajah SALAH (Akun Andi, Orang B)

1. Login: andisaputra / 123456 (kalau sudah absen masuk, coba Absen Pulang)
2. Tekan tombol absen > ikuti liveness
3. Verifikasi wajah pakai ORANG B (BUKAN pemilik akun)

HASIL DIHARAPKAN:
- Wajah TIDAK COCOK, muncul "Wajah tidak cocok", absen DITOLAK

INI MEMBUKTIKAN: orang lain tidak bisa absen di akun bukan miliknya.

---

## TEST 6 - Verifikasi Geofence (Luar Radius)

1. Di halaman Absen, kalau posisi di luar radius > tombol absen disabled
   + banner "Anda di luar radius lokasi kerja"

HASIL DIHARAPKAN:
- Tidak bisa absen dari luar radius lokasi kerja.

---

## CEK DATA DI DATABASE (opsional, untuk verifikasi TA)

Buka phpMyAdmin: http://localhost/phpmyadmin6 (root, password kosong)
Database kafe_satuperdua > tabel face_embeddings:
- Setiap wajah tersimpan sebagai vektor 512 angka
- Kolom is_aktif = 1 berarti wajah valid & aktif

Atau lewat query cepat (Terminal):
  SELECT fe.karyawan_id, k.nama_lengkap, fe.is_aktif,
         JSON_LENGTH(fe.embedding_vector) AS dimensi
  FROM face_embeddings fe JOIN karyawan k ON k.id = fe.karyawan_id;

Dimensi harus 512. Kalau 0 = embedding gagal (cek FastAPI nyala).

---

## CATATAN: Set Lokasi Kafe ke Lokasi Anda (untuk test geofence)

Kalau mau test "dalam radius", ubah koordinat kafe ke lokasi Anda:
1. Buka web admin: http://localhost:8000 (admin / password)
2. Menu Lokasi Kerja > Edit
3. Klik "Lokasi Saya" di peta (atau drag marker ke posisi Anda)
4. Atur radius (mis. 50m) > Simpan

---

## RINGKASAN HASIL (isi saat testing)

| Test | Skenario                          | Hasil (OK/Gagal) | Catatan |
|------|-----------------------------------|------------------|---------|
| 1    | Enroll Andi (Orang A)             |                  |         |
| 2    | Duplikat: Sari pakai Orang A      |                  |         |
| 3    | Enroll Sari (Orang B)             |                  |         |
| 4    | Absen Andi wajah benar (Orang A)  |                  |         |
| 5    | Absen Andi wajah salah (Orang B)  |                  |         |
| 6    | Geofence luar radius              |                  |         |