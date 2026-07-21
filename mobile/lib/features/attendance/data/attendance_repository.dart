import '../domain/attendance_correction.dart';
import '../domain/attendance_record.dart';
import '../domain/attendance_session.dart';
import '../domain/clock_result.dart';

abstract class AttendanceRepository {
  Future<AttendanceSession> getToday();

  Future<List<AttendanceRecord>> getHistory({
    required int year,
    required int month,
  });

  Future<ClockResult> clockIn({
    required double latitude,
    required double longitude,
    required String faceVerificationToken,
    bool isMocked = false,
  });

  Future<ClockResult> clockOut({
    required double latitude,
    required double longitude,
    required String faceVerificationToken,
    bool isMocked = false,
  });

  Future<AttendanceCorrection> submitCorrection({
    required int attendanceId,
    required DateTime clockOutAt,
    required String reason,
  });

  Future<List<AttendanceCorrection>> getCorrections();
}
