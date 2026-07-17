import '../domain/app_user.dart';
import '../domain/auth_exceptions.dart';
import 'auth_repository.dart';

/// Mock implementation untuk Phase 1 — belum konek backend.
///
/// Menyediakan **dua user** untuk menguji kedua jalur login conditional
/// (sesuai keputusan #6e di plan):
///
/// | Username   | Password | hasFaceEnrolled | Alur setelah login            |
/// |------------|----------|-----------------|-------------------------------|
/// | karyawan1  | 123456   | false           | Login → Face Enrollment → Home|
/// | karyawan2  | 123456   | true            | Login → Home (langsung)       |
class MockAuthRepository implements AuthRepository {
  MockAuthRepository();

  static const _password = '123456';

  static final _users = <String, AppUser>{
    'karyawan1': AppUser(
      id: 1,
      username: 'karyawan1',
      nama: 'Andi Saputra',
      idKaryawan: 'KRY-001',
      jabatan: 'Barista',
      tanggalBergabung: DateTime(2025, 1, 15),
      statusAktif: true,
      hasFaceEnrolled: false, // akun baru
    ),
    'karyawan2': AppUser(
      id: 2,
      username: 'karyawan2',
      nama: 'Sari Pratiwi',
      idKaryawan: 'KRY-002',
      jabatan: 'Kasir',
      tanggalBergabung: DateTime(2024, 8, 1),
      statusAktif: true,
      hasFaceEnrolled: true, // akun lama, sudah enroll
    ),
  };

  @override
  Future<AppUser> login({
    required String username,
    required String password,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 800));
    // Username diperlakukan **case-insensitive** untuk pengalaman karyawan
    // yang lebih ramah (mis. "Karyawan1" tetap valid). Backend Laravel di
    // Phase 3 harus mengikuti kontrak yang sama — lihat plan §3 keputusan
    // username case-handling.
    final user = _users[username.trim().toLowerCase()];
    if (user == null || password != _password) {
      throw const InvalidCredentialsException();
    }
    return user;
  }

  @override
  Future<void> logout() async {
    await Future<void>.delayed(const Duration(milliseconds: 200));
  }

  @override
  Future<AppUser?> getCurrentUser() async => null;
}
