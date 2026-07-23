import '../../../shared/utils/attendance_time.dart';

class AttendanceCorrection {
  const AttendanceCorrection({
    this.id,
    this.attendanceId,
    this.requestedClockOutAt,
    this.reason,
    this.status,
  });

  final int? id;
  final int? attendanceId;
  final DateTime? requestedClockOutAt;
  final String? reason;
  final String? status;

  factory AttendanceCorrection.fromJson(Map<String, dynamic> json) {
    int? integer(dynamic value) => value is num
        ? value.toInt()
        : value is String
        ? int.tryParse(value)
        : null;

    return AttendanceCorrection(
      id: integer(json['id']),
      attendanceId: integer(json['attendance_id'] ?? json['absensi_id']),
      requestedClockOutAt: parseAttendanceTime(
        json['requested_clock_out_at'] ??
            json['clock_out_at'] ??
            json['jam_pulang_diminta'],
      ),
      reason: (json['reason'] ?? json['alasan'])?.toString(),
      status: (json['status'] ?? json['request_status'])?.toString(),
    );
  }
}
