import 'attendance_record.dart';

class AttendanceSession {
  const AttendanceSession({
    this.record,
    this.schedule,
    this.serverTime,
    this.timezone,
    required this.canClockIn,
    required this.canClockOut,
    this.blockedReason,
  });

  final AttendanceRecord? record;
  final AttendanceSchedule? schedule;
  final DateTime? serverTime;
  final String? timezone;
  final bool canClockIn;
  final bool canClockOut;
  final String? blockedReason;

  factory AttendanceSession.fromJson(Map<String, dynamic> json) {
    final data = _map(json['data']);
    final scheduleData = _map(json['schedule']);
    final actions = _map(json['actions']);
    final record = data == null ? null : AttendanceRecord.fromJson(data);
    return AttendanceSession(
      record: record,
      schedule: scheduleData == null
          ? record?.schedule
          : AttendanceSchedule.fromJson(
              scheduleData,
              fallbackDate: record?.tanggalShift,
            ),
      serverTime: _date(json['server_time']),
      timezone: json['timezone']?.toString(),
      canClockIn:
          _bool(json['can_clock_in'] ?? actions?['can_clock_in']) ??
          (record?.hasMasuk != true),
      canClockOut:
          _bool(json['can_clock_out'] ?? actions?['can_clock_out']) ??
          (record?.hasMasuk == true && record?.hasPulang != true),
      blockedReason: _blockedReason(
        (json['blocked_reason'] ?? actions?['blocked_reason'])?.toString(),
      ),
    );
  }

  static Map<String, dynamic>? _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return null;
  }

  static DateTime? _date(dynamic value) =>
      value is String ? DateTime.tryParse(value) : null;

  static bool? _bool(dynamic value) {
    if (value is bool) return value;
    if (value is num) return value != 0;
    if (value is String) {
      if (value == '1' || value.toLowerCase() == 'true') return true;
      if (value == '0' || value.toLowerCase() == 'false') return false;
    }
    return null;
  }

  static String? _blockedReason(String? code) {
    return switch (code) {
      'APPROVED_LEAVE' =>
        'Absensi dinonaktifkan karena izin Anda telah disetujui.',
      'NO_ACTIVE_SCHEDULE' => 'Tidak ada jadwal kerja aktif saat ini.',
      null || '' => null,
      _ => code,
    };
  }
}
