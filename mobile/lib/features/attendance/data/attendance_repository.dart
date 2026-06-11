import '../domain/attendance_record.dart';

/// Kontrak repository absensi (Phase 4: ganti dengan API client).
abstract class AttendanceRepository {
  /// Ambil data absensi hari ini. Null = belum ada record.
  Future<AttendanceRecord?> getToday();

  /// Ambil riwayat per bulan tertentu.
  Future<List<AttendanceRecord>> getHistory({
    required int year,
    required int month,
  });
}
