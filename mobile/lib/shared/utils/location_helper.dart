import 'dart:math' as math;
import 'package:geolocator/geolocator.dart';

/// Status izin & ketersediaan GPS.
enum LocationStatus {
  ok,
  serviceDisabled,
  permissionDenied,
  permissionDeniedForever,
}

/// Hasil pengecekan izin lokasi.
class LocationPermissionResult {
  const LocationPermissionResult(this.status, [this.position]);
  final LocationStatus status;
  final Position? position;
  bool get isOk => status == LocationStatus.ok;
}

/// Helper untuk GPS: izin, posisi sekali, live stream, dan Haversine.
class LocationHelper {
  LocationHelper._();

  /// Pastikan service GPS nyala + izin diberikan. Return status detail.
  static Future<LocationStatus> ensurePermission() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) return LocationStatus.serviceDisabled;

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return LocationStatus.permissionDenied;
      }
    }
    if (permission == LocationPermission.deniedForever) {
      return LocationStatus.permissionDeniedForever;
    }
    return LocationStatus.ok;
  }

  /// Ambil posisi GPS sekali (dengan cek izin). Null jika gagal/ditolak.
  static Future<Position?> getCurrentPosition() async {
    final status = await ensurePermission();
    if (status != LocationStatus.ok) return null;
    try {
      return await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 15),
        ),
      );
    } catch (_) {
      return null;
    }
  }

  /// Stream posisi GPS live (update tiap pergerakan >= [distanceFilter] meter).
  /// Dipakai untuk live tracking di peta absensi.
  static Stream<Position> positionStream({int distanceFilter = 3}) {
    return Geolocator.getPositionStream(
      locationSettings: LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: distanceFilter,
      ),
    );
  }

  /// Buka pengaturan lokasi HP (jika user tolak permanen / GPS mati).
  static Future<void> openLocationSettings() =>
      Geolocator.openLocationSettings();
  static Future<void> openAppSettings() => Geolocator.openAppSettings();

  /// Jarak Haversine (meter) antara dua koordinat.
  /// Konsisten dengan GeofenceService di backend Laravel.
  static double haversineMeters(
    double lat1,
    double lng1,
    double lat2,
    double lng2,
  ) {
    const earthRadius = 6371000.0; // meter
    final dLat = _deg2rad(lat2 - lat1);
    final dLng = _deg2rad(lng2 - lng1);
    final a =
        math.sin(dLat / 2) * math.sin(dLat / 2) +
        math.cos(_deg2rad(lat1)) *
            math.cos(_deg2rad(lat2)) *
            math.sin(dLng / 2) *
            math.sin(dLng / 2);
    final c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a));
    return earthRadius * c;
  }

  static double _deg2rad(double deg) => deg * (math.pi / 180.0);
}
