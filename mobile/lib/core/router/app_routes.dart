/// Daftar path rute aplikasi terpusat di sini supaya tidak ada
/// magic-string yang tersebar di seluruh codebase.
class AppRoutes {
  AppRoutes._();

  static const String login = '/login';
  static const String enroll = '/enroll';
  static const String home = '/home';
  static const String attendance = '/attendance';
  static const String verify = '/verify';
  static const String history = '/history';
  static const String leave = '/leave';
  static const String profile = '/profile';

  /// Nama rute (untuk `context.goNamed` jika dibutuhkan).
  static const String loginName = 'login';
  static const String enrollName = 'enroll';
  static const String homeName = 'home';
  static const String attendanceName = 'attendance';
  static const String verifyName = 'verify';
  static const String historyName = 'history';
  static const String leaveName = 'leave';
  static const String profileName = 'profile';
}
