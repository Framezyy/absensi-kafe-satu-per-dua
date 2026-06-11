/// Result enum untuk operasi absensi (clock-in / clock-out).
enum ClockAction { clockIn, clockOut }

/// Status dari operasi clock.
enum ClockStatus { success, alreadyDone, outsideGeofence, faceMismatch, timeout }

class ClockResult {
  const ClockResult({
    required this.status,
    required this.action,
    this.jamServer,
    this.message,
  });

  final ClockStatus status;
  final ClockAction action;
  final DateTime? jamServer;
  final String? message;

  bool get isSuccess => status == ClockStatus.success;
}
