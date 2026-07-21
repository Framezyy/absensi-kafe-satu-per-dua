/// Exception kredensial salah saat login.
class InvalidCredentialsException implements Exception {
  const InvalidCredentialsException([
    this.message = 'Username atau password salah',
  ]);
  final String message;
  @override
  String toString() => 'InvalidCredentialsException: $message';
}
