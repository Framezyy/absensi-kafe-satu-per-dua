# Progress: Fitur Cegah Duplikat Wajah

## Tujuan
Ketika 1 karyawan sudah mendaftarkan wajah, wajah yang sama TIDAK BOLEH
didaftarkan lagi ke akun karyawan lain (mencegah 1 orang punya banyak akun
absensi / titip absen).

## Cara Kerja
Saat enrollment, sebelum menyimpan embedding baru:
1. Generate embedding dari 3 frame (mean embedding) via FastAPI
2. Bandingkan embedding baru dengan SEMUA embedding karyawan lain yang aktif
3. Jika ada yang similarity >= 0.7 (threshold) -> TOLAK, wajah sudah terdaftar
4. Jika tidak ada yang mirip -> simpan embedding baru

## Threshold
- Cosine similarity >= 0.7 = wajah dianggap sama (Nusantoko & Prapanca, 2025)

---

## Checklist Langkah

- [x] STEP 1: SELESAI - cosineSimilarity() + findDuplicateFace() ditambahkan
      di web/app/Services/FaceRecognitionService.php
- [x] STEP 2: SELESAI - enroll() cek findDuplicateFace() sebelum simpan,
      return 422 + duplicate:true kalau wajah sudah terdaftar di akun lain
- [x] STEP 3: SELESAI - tambah field _errorMessage, _sendEnrollment simpan pesan
      backend, _buildBottom tampilkan pesan asli (bukan selalu 'Waktu habis')
- [ ] STEP 4: Test (wajah sama akun beda ditolak, wajah baru diterima)
- [ ] STEP 5: Rebuild + install APK

---

## Status Terakhir
- Tanggal mulai: 2026-07-07
- Sedang dikerjakan: STEP 4 (test fitur)
- Catatan: FastAPI (:8001) & Laravel (:8000) berjalan di background, adb reverse aktif.
  NumPy sudah 1.26.4 (FastAPI /embed & /verify berfungsi normal).

---

## File yang Terlibat
| File | Perubahan |
|------|-----------|
| web/app/Services/FaceRecognitionService.php | + method cek duplikat wajah |
| web/app/Http/Controllers/Api/FaceController.php | enroll() cek duplikat dulu |
| mobile/lib/features/face_enroll/presentation/face_enroll_page.dart | pesan error duplikat |

## Cara Test Setelah Selesai
1. Enroll wajah A ke akun andisaputra -> berhasil
2. Logout, login saripratiwi
3. Coba enroll wajah A (orang yang sama) ke saripratiwi -> HARUS DITOLAK
4. Enroll wajah B (orang beda) ke saripratiwi -> berhasil

---

## STEP 4 - TEMUAN PENTING (2026-07-07)

Test logika findDuplicateFace() via script standalone:
- TEST 1 Andi vs Andi: 1.0 (benar)
- TEST 2 Andi vs Dewi: 0.8983 -> MASALAH (2 orang beda harusnya < 0.7)
- TEST 3 Daftar wajah Andi: found=true (duplikat terdeteksi, benar)
- TEST 4 Wajah acak: found=false (benar)

LOGIKA KODE SUDAH BENAR. Masalahnya KUALITAS EMBEDDING dari kamera HP.

Akar masalah: _captureFrame() meng-capture foto saat kepala MENOLEH
kiri/kanan (yaw ekstrem). 2 dari 3 frame di sudut miring -> mean embedding
kabur -> semua orang mirip (similarity tinggi).

Bukti: foto bagus (Lena vs Messi) similarity -0.09 (beda jelas).

## SOLUSI (dipilih user): Perbaiki kualitas foto + re-enroll
- [ ] SUB-1: Rewrite state machine enrollment -> capture 3 frame FRONTAL
      (wajah lurus, |yaw|<12, mata terbuka, stabil). Blink tetap untuk liveness.
- [ ] SUB-2: Naikkan resolusi kamera enrollment ke high
- [ ] SUB-3: Hapus embedding lama Andi (karyawan_id=1) & Dewi (karyawan_id=4)
- [ ] SUB-4: Rebuild + install APK
- [ ] SUB-5: Re-enroll dengan pencahayaan bagus, test ulang duplikat

## Catatan karyawan_id (PENTING):
- Andi Saputra = karyawan_id 1 (embedding_id 2)
- Dewi Lestari = karyawan_id 4 (embedding_id 3)
