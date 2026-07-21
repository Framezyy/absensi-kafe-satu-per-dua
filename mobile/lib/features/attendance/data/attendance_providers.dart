import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../domain/attendance_record.dart';
import '../domain/attendance_session.dart';
import 'api_attendance_repository.dart';
import 'attendance_repository.dart';

final attendanceRepositoryProvider = Provider<AttendanceRepository>(
  (_) => ApiAttendanceRepository(),
);

final todayAttendanceProvider = FutureProvider.autoDispose<AttendanceSession>(
  (ref) => ref.watch(attendanceRepositoryProvider).getToday(),
);

final monthHistoryProvider = FutureProvider.autoDispose
    .family<List<AttendanceRecord>, ({int year, int month})>(
      (ref, value) => ref
          .watch(attendanceRepositoryProvider)
          .getHistory(year: value.year, month: value.month),
    );
