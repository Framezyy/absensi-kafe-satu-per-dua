import 'package:flutter_test/flutter_test.dart';
import 'package:kafe_satuperdua/features/attendance/domain/attendance_correction.dart';
import 'package:kafe_satuperdua/features/attendance/domain/attendance_record.dart';
import 'package:kafe_satuperdua/features/attendance/domain/attendance_session.dart';
import 'package:kafe_satuperdua/features/attendance/domain/clock_result.dart';
import 'package:kafe_satuperdua/features/auth/domain/app_user.dart';
import 'package:kafe_satuperdua/features/leave/domain/leave_request.dart';

void main() {
  group('AttendanceSession', () {
    for (final entry in <Object, bool>{
      true: true,
      false: false,
      1: true,
      0: false,
      '1': true,
      '0': false,
      'TRUE': true,
      ' false ': false,
      'yes': true,
      'NO': false,
    }.entries) {
      test('parses boolean ${entry.key}', () {
        final session = AttendanceSession.fromJson({
          'can_clock_in': entry.key,
          'can_clock_out': entry.key,
        });
        expect(session.canClockIn, entry.value);
        expect(session.canClockOut, entry.value);
      });
    }

    test('supports action aliases, fallback flags, and blocked reasons', () {
      final leave = AttendanceSession.fromJson({
        'actions': {
          'can_clock_in': '0',
          'can_clock_out': '1',
          'blocked_reason': 'APPROVED_LEAVE',
        },
      });
      final fallback = AttendanceSession.fromJson({
        'data': {'tanggal': '2026-01-01', 'jam_masuk': '08:00'},
      });

      expect(leave.canClockIn, isFalse);
      expect(leave.canClockOut, isTrue);
      expect(leave.blockedReason, contains('izin'));
      expect(fallback.canClockIn, isFalse);
      expect(fallback.canClockOut, isTrue);
    });

    test('malformed nested values are safe', () {
      final session = AttendanceSession.fromJson({
        'data': 'invalid',
        'schedule': 42,
        'server_time': 'not-a-date',
        'can_clock_in': 'maybe',
      });
      expect(session.record, isNull);
      expect(session.schedule, isNull);
      expect(session.serverTime, isNull);
      expect(session.canClockIn, isTrue);
    });
  });

  group('AttendanceRecord and schedule', () {
    test('parses aliases, numeric strings, and nested location', () {
      final record = AttendanceRecord.fromJson({
        'attendance_id': '12',
        'tanggal': '2026-02-28',
        'jam_masuk': '23:30:00',
        'jam_pulang': '01:15:00',
        'menit_terlambat': '5',
        'face_similarity': '0.87',
        'estimated_salary': '25000.50',
        'jadwal': {
          'nama_shift': 'Malam',
          'lokasi': {'nama_lokasi': 'Cabang A'},
        },
      });
      expect(record.id, 12);
      expect(record.jamPulang, DateTime(2026, 3, 1, 1, 15));
      expect(record.terlambat, isTrue);
      expect(record.faceSimilarity, .87);
      expect(record.estimatedSalary, 25000.5);
      expect(record.schedule?.name, 'Malam');
    });

    test('overnight time crosses month and year safely', () {
      final month = AttendanceRecord.fromJson({
        'tanggal': '2024-02-29',
        'jam_masuk': '23:00',
        'jam_pulang': '01:00',
      });
      final year = AttendanceSchedule.fromJson({
        'tanggal': '2026-12-31',
        'jam_mulai': '22:00',
        'jam_selesai': '06:00',
      });
      expect(month.jamPulang, DateTime(2024, 3, 1, 1));
      expect(year.endsAt, DateTime(2027, 1, 1, 6));
    });

    test('explicit UTC crossing date converts to Jakarta', () {
      final record = AttendanceRecord.fromJson({
        'tanggal_shift': '2026-12-31',
        'clock_in_at': '2026-12-31T18:30:00Z',
        'clock_out_at': '2026-12-31T20:00:00Z',
      });
      expect(record.jamMasuk, DateTime(2027, 1, 1, 1, 30));
      expect(record.jamPulang, DateTime(2027, 1, 1, 3));
    });

    test('incomplete and corrected aliases are case insensitive', () {
      final incomplete = AttendanceRecord.fromJson({
        'tanggal': '2026-01-01',
        'attendance_status': 'INCOMPLETE',
      });
      final corrected = AttendanceRecord.fromJson({
        'tanggal': '2026-01-01',
        'session_status': 'Dikoreksi',
      });
      expect(incomplete.isIncomplete, isTrue);
      expect(corrected.isCorrected, isTrue);
    });

    test('malformed numbers, dates, maps, and times do not throw', () {
      final record = AttendanceRecord.fromJson({
        'id': 'x',
        'tanggal': 'invalid',
        'jam_masuk': '25:99',
        'shift': 'bad',
        'worked_minutes': {},
      });
      expect(record.id, isNull);
      expect(record.tanggal, DateTime(1970));
      expect(record.jamMasuk, isNull);
      expect(record.shift, isNull);
      expect(record.workedMinutes, isNull);
    });
  });

  group('ClockResult machine codes', () {
    final cases = <String, ClockStatus>{
      'OUTSIDE_GEOFENCE': ClockStatus.outsideGeofence,
      'outside-radius': ClockStatus.outsideGeofence,
      'MOCK_LOCATION': ClockStatus.mockedLocation,
      'fake_location': ClockStatus.mockedLocation,
      'NO_ACTIVE_SCHEDULE': ClockStatus.noSchedule,
      'schedule_not_found': ClockStatus.noSchedule,
      'NO_OPEN_SESSION': ClockStatus.noOpenSession,
      'not_clocked_in': ClockStatus.noOpenSession,
      'ALREADY_CLOCKED_IN': ClockStatus.alreadyDone,
      'completed': ClockStatus.alreadyDone,
      'FACE_MISMATCH': ClockStatus.faceMismatch,
      'FACE_VERIFICATION_REQUIRED': ClockStatus.faceMismatch,
      'VALIDATION_ERROR': ClockStatus.validation,
      'REQUEST_TIMEOUT': ClockStatus.timeout,
      'timed-out': ClockStatus.timeout,
      'UNKNOWN': ClockStatus.error,
    };
    for (final entry in cases.entries) {
      test('${entry.key} maps to ${entry.value.name}', () {
        final result = ClockResult.error({
          'error_code': entry.key,
        }, action: ClockAction.clockOut);
        expect(result.status, entry.value);
      });
    }

    test('HTTP validation and timeout status are mapped', () {
      expect(
        ClockResult.error(
          null,
          action: ClockAction.clockIn,
          statusCode: 422,
        ).status,
        ClockStatus.validation,
      );
      expect(
        ClockResult.error(
          null,
          action: ClockAction.clockIn,
          statusCode: 504,
        ).status,
        ClockStatus.timeout,
      );
    });

    test('success parses record and timestamp aliases', () {
      final result = ClockResult.fromJson({
        'attendance': {'tanggal': '2026-01-01', 'jam_masuk': '08:00'},
        'timestamp': '2026-01-01T01:00:00Z',
      }, action: ClockAction.clockIn);
      expect(result.isSuccess, isTrue);
      expect(result.record?.hasMasuk, isTrue);
      expect(result.serverTime, DateTime(2026, 1, 1, 8));
    });
  });

  group('AppUser', () {
    test('parses aliases and bool representations', () {
      final user = AppUser.fromJson({
        'id': '7',
        'email': 'user@example.test',
        'name': ' Budi ',
        'employee_code': 99,
        'role': 'Kasir',
        'joined_at': '2026-01-02',
        'is_active': 1,
        'face_enrolled': 'true',
      });
      expect(user.id, 7);
      expect(user.username, 'user@example.test');
      expect(user.nama, 'Budi');
      expect(user.idKaryawan, '99');
      expect(user.statusAktif, isTrue);
      expect(user.hasFaceEnrolled, isTrue);
    });

    test('null and malformed fields use safe defaults', () {
      final user = AppUser.fromJson({'tanggal_bergabung': 'bad'});
      expect(user.id, 0);
      expect(user.nama, isEmpty);
      expect(user.tanggalBergabung, DateTime(1970));
      expect(user.statusAktif, isFalse);
    });
  });

  group('LeaveRequest and AttendanceCorrection', () {
    test('leave parses aliases, rejection reason, and UTC time', () {
      final leave = LeaveRequest.fromJson({
        'id': '4',
        'start_date': '2026-12-31',
        'end_date': null,
        'reason': 'Keluarga',
        'leave_status': 'REJECTED',
        'rejection_reason': 'Dokumen kurang',
        'submitted_at': '2026-12-31T18:00:00Z',
      });
      expect(leave.id, 4);
      expect(leave.tanggalSelesai, leave.tanggalMulai);
      expect(leave.status, LeaveStatus.rejected);
      expect(leave.alasanPenolakan, 'Dokumen kurang');
      expect(leave.diajukanPada, DateTime(2027, 1, 1, 1));
    });

    test('leave null and malformed values are safe', () {
      final leave = LeaveRequest.fromJson({});
      expect(leave.id, 0);
      expect(leave.status, LeaveStatus.pending);
      expect(leave.tanggalMulai, DateTime(1970));
      expect(leave.alasanPenolakan, isNull);
    });

    test('correction parses aliases and Jakarta timezone', () {
      final correction = AttendanceCorrection.fromJson({
        'id': '8',
        'absensi_id': '9',
        'jam_pulang_diminta': '2026-07-20T18:30:00Z',
        'alasan': 'Lupa',
        'request_status': 'pending',
      });
      expect(correction.id, 8);
      expect(correction.attendanceId, 9);
      expect(correction.requestedClockOutAt, DateTime(2026, 7, 21, 1, 30));
      expect(correction.reason, 'Lupa');
      expect(correction.status, 'pending');
    });

    test('correction malformed values are safe', () {
      final correction = AttendanceCorrection.fromJson({
        'id': [],
        'requested_clock_out_at': 'bad',
      });
      expect(correction.id, isNull);
      expect(correction.requestedClockOutAt, isNull);
    });
  });
}
