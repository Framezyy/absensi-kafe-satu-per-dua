/// Konfigurasi environment aplikasi.
///
/// Ganti `apiBaseUrl` sesuai IP server Laravel saat development.
/// - Android emulator: `http://10.0.2.2:8000/api`
/// - Device fisik (HP): `http://<IP_LAPTOP>:8000/api`
///
/// Untuk mengetahui IP laptop, jalankan `ipconfig` di CMD.
class Env {
  Env._();

  /// Base URL API Laravel (tanpa trailing slash).
  ///
  /// Default: `http://10.0.2.2:8000/api` (Android emulator).
  /// Untuk HP fisik, ganti dengan IP laptop Anda, misal
  /// `http://192.168.1.100:8000/api`.
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://localhost:8000/api',
  );
}
