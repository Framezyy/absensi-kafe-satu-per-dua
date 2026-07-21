import '../domain/app_user.dart';

/// Kontrak repository autentikasi.
///
/// Implementasi:
/// - [MockAuthRepository] di Phase 1 (skeleton, mock data)
/// - `ApiAuthRepository` di Phase 4 (Sanctum + REST)
abstract class AuthRepository {
  /// Login dengan username + password.
  ///
  /// Mengembalikan [AppUser] dengan flag `hasFaceEnrolled` yang menentukan
  /// alur conditional di router (akun baru → /enroll, akun lama → /home).
  ///
  /// Throw [InvalidCredentialsException] jika kredensial tidak cocok.
  Future<AppUser> login({required String username, required String password});

  /// Logout dan bersihkan token (Phase 4: hapus secure storage).
  Future<void> logout();

  /// Ambil user yang sedang login dari token tersimpan.
  /// Phase 1 selalu return null (tidak ada session persist).
  Future<AppUser?> getCurrentUser();
}
