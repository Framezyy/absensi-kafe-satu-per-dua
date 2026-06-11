/// Konstanta global aplikasi.
class AppConstants {
  AppConstants._();

  static const String appName = 'Kafe Satu Per Dua Kopitiam';
  static const String appNameShort = 'Kafe 1/2 Kopitiam';

  // Geofence default (override per lokasi dari API)
  static const double defaultGeofenceRadiusMeter = 50;

  // Liveness thresholds (Google ML Kit)
  /// Probabilitas mata dianggap terbuka (≥) atau tertutup (<).
  /// Threshold disesuaikan untuk kamera depan HP Android entry-level
  /// (mis. Infinix) di mana eye probability sering fluktuatif.
  /// Menggunakan satu threshold (bukan dua terpisah) supaya transisi
  /// close → open lebih mudah terpicu.
  static const double eyeClosedThreshold = 0.40;
  static const double eyeOpenThreshold = 0.45;

  /// Cooldown setelah blink terdeteksi untuk mencegah trigger berulang.
  static const Duration blinkCooldown = Duration(milliseconds: 500);

  /// Sudut yaw (kepala menoleh kiri/kanan) dalam derajat.
  /// Negatif = kiri, positif = kanan.
  static const double yawTriggerAngle = 20.0;

  /// Sudut yaw maksimum saat blink (kepala harus relatif lurus).
  static const double yawNeutralMaxAngle = 10.0;

  /// Lama pose harus stabil sebelum auto-capture.
  static const Duration poseStableDuration = Duration(milliseconds: 500);

  /// Timeout per aksi liveness (per step di enrollment, atau 1 aksi di absensi).
  static const Duration livenessTimeout = Duration(seconds: 30);

  // Face recognition
  /// Threshold cosine similarity (Nusantoko & Prapanca, 2025).
  static const double faceSimilarityThreshold = 0.7;

  // GPS
  /// Akurasi GPS yang dianggap baik. Di atas nilai ini -> tampilkan warning.
  static const double gpsAccuracyWarningMeter = 30;
}
