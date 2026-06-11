import '../domain/attendance_record.dart';
import 'attendance_repository.dart';

/// Mock data absensi untuk Phase 1.
///
/// `todayStatus` menentukan state awal layar Beranda dan Tab Absensi
/// saat app dijalankan. Ubah nilainya untuk menguji skenario berbeda.
enum TodayStatus { none, clockedIn, clockedOut }

class MockAttendanceRepository implements AttendanceRepository {
  MockAttendanceRepository({this.todayStatus = TodayStatus.clockedIn});

  final TodayStatus todayStatus;

  static final _lokasi = 'Kafe Satu Per Dua Kopitiam';

  @override
  Future<AttendanceRecord?> getToday() async {
    await Future<void>.delayed(const Duration(milliseconds: 300));
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    switch (todayStatus) {
      case TodayStatus.none:
        return null;
      case TodayStatus.clockedIn:
        return AttendanceRecord(
          tanggal: today,
          jamMasuk: today.add(const Duration(hours: 8, minutes: 3)),
          terlambat: true, // jam_masuk_standar 08:00 + toleransi 15 menit
          lokasiNama: _lokasi,
          faceSimilarity: 0.87,
        );
      case TodayStatus.clockedOut:
        return AttendanceRecord(
          tanggal: today,
          jamMasuk: today.add(const Duration(hours: 7, minutes: 52)),
          jamPulang: today.add(const Duration(hours: 17, minutes: 2)),
          terlambat: false,
          lokasiNama: _lokasi,
          faceSimilarity: 0.91,
        );
    }
  }

  @override
  Future<List<AttendanceRecord>> getHistory({
    required int year,
    required int month,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 400));
    return _generateMonthData(year, month);
  }

  /// Generate mock data satu bulan: 26 hari hadir, 2 hari izin, sisanya libur.
  static List<AttendanceRecord> _generateMonthData(int year, int month) {
    final daysInMonth = DateTime(year, month + 1, 0).day;
    final records = <AttendanceRecord>[];
    final rng = year * 100 + month; // seed deterministik sederhana

    for (var d = 1; d <= daysInMonth; d++) {
      final date = DateTime(year, month, d);
      final weekday = date.weekday;
      // Minggu libur.
      if (weekday == DateTime.sunday) continue;

      // 2 hari izin (tanggal 15 dan 23 — deterministic).
      if (d == 15 || d == 23) continue;

      final isLate = ((rng + d) % 7 == 0); // ~14% terlambat
      final masukH = isLate ? 8 : 7;
      final masukM = isLate ? (5 + (d % 10)) : (50 + (d % 10));
      final pulangH = 17;
      final pulangM = d % 15;

      records.add(AttendanceRecord(
        tanggal: date,
        jamMasuk: date.add(Duration(hours: masukH, minutes: masukM)),
        jamPulang: date.add(Duration(hours: pulangH, minutes: pulangM)),
        terlambat: isLate,
        lokasiNama: _lokasi,
        faceSimilarity: 0.75 + ((d % 20) * 0.01),
      ));
    }
    return records;
  }
}
