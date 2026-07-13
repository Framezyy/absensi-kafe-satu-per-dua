import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/api_auth_repository.dart';
import '../data/auth_repository.dart';
import '../data/mock_auth_repository.dart';
import '../domain/app_user.dart';

/// Flag untuk switching antara mock dan API.
///
/// `true` = pakai API Laravel (Phase 4+).
/// `false` = pakai mock data (Phase 1 development).
const _useApi = true;

/// Provider untuk repository autentikasi.
///
/// Override di test: `ProviderContainer(overrides: [authRepositoryProvider.overrideWithValue(MockAuthRepository())])`
final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return _useApi ? ApiAuthRepository() : MockAuthRepository();
});

/// State autentikasi (sumber kebenaran user yang login).
///
/// `null` = belum login, `AppUser` = sudah login.
class AuthController extends AsyncNotifier<AppUser?> {
  @override
  Future<AppUser?> build() async {
    // Sengaja `ref.read` supaya override repository tidak me-reset session.
    final repo = ref.read(authRepositoryProvider);
    return repo.getCurrentUser();
  }

  /// Login dengan username + password.
  Future<void> login({
    required String username,
    required String password,
  }) async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() async {
      final repo = ref.read(authRepositoryProvider);
      return repo.login(username: username, password: password);
    });
  }

  /// Logout user dan kembali ke layar login.
  ///
  /// Logout selalu dianggap sukses dari sisi aplikasi: token lokal
  /// dihapus di repository (best-effort ke server). State langsung
  /// di-set `null` supaya router redirect ke /login dalam sekali tekan
  /// dan tidak memunculkan error palsu.
  Future<void> logout() async {
    try {
      await ref.read(authRepositoryProvider).logout();
    } catch (_) {
      // Abaikan error server — token lokal sudah dihapus,
      // user tetap dianggap keluar.
    }
    state = const AsyncValue.data(null);
  }

  /// Tandai user sebagai sudah enroll wajah.
  ///
  /// Dipanggil oleh enrollment flow saat enrollment sukses.
  void markFaceEnrolled() {
    final user = state.value;
    if (user != null) {
      state = AsyncValue.data(user.copyWith(hasFaceEnrolled: true));
    }
  }
}

final authControllerProvider =
    AsyncNotifierProvider<AuthController, AppUser?>(AuthController.new);

/// User yang sedang login (null jika belum login atau masih loading).
final currentUserProvider = Provider<AppUser?>((ref) {
  return ref.watch(authControllerProvider).value;
});

/// Apakah user sudah enroll wajah.
final faceEnrollmentStatusProvider = Provider<bool>((ref) {
  return ref.watch(currentUserProvider)?.hasFaceEnrolled ?? false;
});
