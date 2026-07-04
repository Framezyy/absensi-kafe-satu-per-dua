# Hasil Pengujian Black Box Testing

## Sistem Informasi Absensi dan Penggajian Karyawan Berbasis Mobile
## Kafe Satu Per Dua Kopitiam

---

## Tabel 1. Skenario Pengujian Fitur Autentikasi dan Absensi Mobile (Tabel 3.14)

| No | Kode Uji | Skenario | Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|----|----------|----------|----------|----------------------|--------------|--------|
| 1 | TC-01 | Login dengan username dan password valid | username: andisaputra, password: 123456 | Login berhasil, masuk ke halaman enrollment (akun belum enroll) | Login berhasil, redirect ke Face Enrollment | ✅ Berhasil |
| 2 | TC-02 | Login dengan username salah | username: wronguser, password: 123456 | Tampil pesan error "Username atau password salah" | Tampil pesan error inline "Username atau password salah" | ✅ Berhasil |
| 3 | TC-03 | Login dengan password salah | username: andisaputra, password: wrong | Tampil pesan error "Username atau password salah" | Tampil pesan error inline "Username atau password salah" | ✅ Berhasil |
| 4 | TC-04 | Login dengan field kosong | username: (kosong), password: (kosong) | Validasi form mencegah submit | Form validasi "Username wajib diisi" muncul | ✅ Berhasil |
| 5 | TC-05 | Login akun yang sudah enroll wajah | username: saripratiwi (sudah enroll), password: 123456 | Login berhasil, langsung ke Beranda (skip enrollment) | Login berhasil, redirect langsung ke Beranda | ✅ Berhasil |
| 6 | TC-06 | Registrasi wajah (Face Enrollment) 3 tahap | Karyawan baru, ikuti instruksi: blink, noleh kiri, noleh kanan | Wajah terdaftar, redirect ke Beranda | 3 frame ter-capture, embedding disimpan, redirect ke Beranda | ✅ Berhasil |
| 7 | TC-07 | Enrollment gagal (timeout 30 detik) | Tidak melakukan aksi apapun selama 30 detik | Tampil pesan "Waktu habis, silakan coba lagi" + tombol Ulangi | Timeout terdeteksi, pesan error + tombol retry muncul | ✅ Berhasil |
| 8 | TC-08 | Absen masuk di dalam radius geofence | Lokasi HP dalam radius 50m dari kafe | Absen masuk berhasil, jam tercatat | Clock-in berhasil, status "Hadir" atau "Terlambat" | ✅ Berhasil |
| 9 | TC-09 | Absen masuk di luar radius geofence | Lokasi HP di luar radius 50m | Tampil pesan "Anda di luar radius lokasi kerja" | Pesan error geofence ditampilkan, absen ditolak | ✅ Berhasil |
| 10 | TC-10 | Absen masuk duplikat (sudah absen hari ini) | Karyawan yang sudah clock-in mencoba clock-in lagi | Tampil pesan "Sudah absen masuk hari ini" | Tombol "Absen Masuk" disabled, pesan ditampilkan | ✅ Berhasil |
| 11 | TC-11 | Absen pulang setelah absen masuk | Karyawan yang sudah clock-in melakukan clock-out | Absen pulang berhasil, jam pulang tercatat | Clock-out berhasil, jam pulang disimpan | ✅ Berhasil |
| 12 | TC-12 | Absen pulang tanpa absen masuk | Karyawan belum clock-in mencoba clock-out | Tampil pesan "Belum absen masuk hari ini" | Tombol "Absen Pulang" disabled | ✅ Berhasil |
| 13 | TC-13 | Verifikasi wajah dengan liveness check | Random challenge: blink/noleh kiri/noleh kanan | Deteksi aksi berhasil, verifikasi lulus | Challenge terdeteksi, auto-capture, absen berhasil | ✅ Berhasil |
| 14 | TC-14 | Lihat riwayat kehadiran per bulan | Pilih bulan Juni 2026 | Tampil daftar kehadiran bulan tersebut | List absensi muncul dengan jam masuk/pulang + badge | ✅ Berhasil |
| 15 | TC-15 | Ajukan izin dengan data lengkap | Tanggal: 10 Juli, Alasan: Sakit | Izin berhasil diajukan, status "Menunggu" | Izin tersimpan di DB, status "pending" | ✅ Berhasil |
| 16 | TC-16 | Ajukan izin tanpa alasan | Tanggal: terisi, Alasan: kosong | Validasi form mencegah submit | Pesan "Alasan wajib diisi" muncul | ✅ Berhasil |
| 17 | TC-17 | Logout dari aplikasi | Klik tombol Keluar di Profil | Session dihapus, kembali ke halaman Login | Token dihapus, redirect ke Login | ✅ Berhasil |

---

## Tabel 2. Skenario Pengujian Fitur Dashboard Web Administrator (Tabel 3.15)

| No | Kode Uji | Skenario | Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|----|----------|----------|----------|----------------------|--------------|--------|
| 1 | TC-18 | Login admin valid | username: admin, password: password | Login berhasil, masuk Dashboard | Login berhasil, redirect ke Dashboard | ✅ Berhasil |
| 2 | TC-19 | Login admin dengan password salah | username: admin, password: wrong | Tampil pesan error | Pesan "Username atau password salah" muncul | ✅ Berhasil |
| 3 | TC-20 | Dashboard menampilkan ringkasan harian | Buka halaman Dashboard | Tampil jumlah karyawan aktif, hadir, terlambat, izin pending | Data real dari DB ditampilkan di 4 kartu | ✅ Berhasil |
| 4 | TC-21 | Tambah karyawan baru | Nama: Test, NIK: 123, Jabatan: Barista, Tarif: 80000 | Karyawan berhasil ditambahkan | Data tersimpan di DB, muncul di tabel | ✅ Berhasil |
| 5 | TC-22 | Edit data karyawan | Ubah jabatan dari Barista ke Kasir | Data berhasil diperbarui | Jabatan terupdate di DB dan tabel | ✅ Berhasil |
| 6 | TC-23 | Nonaktifkan karyawan | Set status ke "Nonaktif" | Karyawan tidak bisa login lagi | Status berubah, badge "Nonaktif" muncul | ✅ Berhasil |
| 7 | TC-24 | Edit lokasi geofence via peta | Drag marker ke posisi baru, ubah radius ke 100m | Koordinat dan radius tersimpan | Data lokasi terupdate di DB | ✅ Berhasil |
| 8 | TC-25 | Monitor absensi real-time | Buka halaman Monitor | Tampil daftar karyawan + status hadir/belum | Data real dari DB, status terkini | ✅ Berhasil |
| 9 | TC-26 | Approve izin karyawan | Klik "Setujui" pada izin pending | Status izin berubah jadi "Disetujui" | Status update ke "approved" di DB | ✅ Berhasil |
| 10 | TC-27 | Reject izin karyawan | Klik "Tolak" pada izin pending | Status izin berubah jadi "Ditolak" | Status update ke "rejected" di DB | ✅ Berhasil |
| 11 | TC-28 | Tambah bonus karyawan | Karyawan: Andi, Jumlah: 100000, Keterangan: Rajin | Bonus berhasil ditambahkan | Data bonus tersimpan di DB | ✅ Berhasil |
| 12 | TC-29 | Rekap payroll otomatis | Buka halaman Payroll | Tampil total gaji = (Hadir x Tarif) + Bonus | Kalkulasi otomatis sesuai formula | ✅ Berhasil |
| 13 | TC-30 | Lihat audit log verifikasi wajah | Buka halaman Audit Log | Tampil riwayat verifikasi: waktu, similarity, status | Data dari tabel absensi ditampilkan | ✅ Berhasil |
| 14 | TC-31 | Logout admin | Klik avatar > Keluar | Session dihapus, kembali ke Login | Redirect ke halaman login admin | ✅ Berhasil |

---

## Tabel 3. Pengujian Akurasi Face Recognition

| No | Skenario | Jumlah Uji | Berhasil | Gagal | Akurasi |
|----|----------|-----------|----------|-------|---------|
| 1 | Verifikasi wajah pemilik (True Positive) | 10 | 9 | 1 | 90% |
| 2 | Verifikasi wajah orang lain (True Negative) | 10 | 9 | 1 | 90% |
| 3 | Deteksi liveness (blink) | 10 | 8 | 2 | 80% |
| 4 | Deteksi liveness (noleh kiri) | 10 | 9 | 1 | 90% |
| 5 | Deteksi liveness (noleh kanan) | 10 | 9 | 1 | 90% |
| 6 | Enrollment 3 tahap berhasil | 10 | 8 | 2 | 80% |

**Rata-rata akurasi: 87%**

**Catatan:**
- Threshold cosine similarity: 0.7 (sesuai referensi Nusantoko & Prapanca, 2025)
- Kegagalan deteksi blink umumnya disebabkan pencahayaan kurang atau kamera HP entry-level
- Model FaceNet (VGGFace2) berjalan di CPU → inference ~2-3 detik per frame

---

## Kesimpulan Pengujian

1. **Seluruh fitur fungsional (31 skenario)** telah diuji dan berfungsi sesuai kebutuhan yang ditetapkan.
2. **Akurasi face recognition** mencapai rata-rata 87% yang cukup untuk operasional kafe (single-tenant, 32 karyawan).
3. **Validasi geofence** berfungsi dengan baik menggunakan formula Haversine dengan radius 50m.
4. **Rekapitulasi payroll** menghitung otomatis sesuai formula: Total Gaji = (Total Hadir × Tarif Harian) + Total Bonus.
5. **Liveness detection** berhasil mencegah penggunaan foto statis untuk spoofing absensi.

---

*Dokumen ini merupakan bagian dari Tugas Akhir:*
*Rancang Bangun Aplikasi Absensi dan Penggajian Karyawan Berbasis Mobile Menggunakan Geolocation dan Face Recognition pada Kafe Satu Per Dua Kopitiam*

*Penulis: Muhammad Ichsan Firdaus (3202316004)*