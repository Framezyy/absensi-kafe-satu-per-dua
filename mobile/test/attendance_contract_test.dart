import 'package:flutter_test/flutter_test.dart';
import 'package:kafe_satuperdua/features/attendance/domain/attendance_record.dart';
import 'package:kafe_satuperdua/features/attendance/domain/attendance_session.dart';
import 'package:kafe_satuperdua/features/attendance/domain/clock_result.dart';

void main() {
  test('today session reads action flags and nested shift response', () {
    final session = AttendanceSession.fromJson({
      'data': null,
      'schedule': {
        'id': 5,
        'tanggal_shift': '2026-07-20',
        'starts_at': '2026-07-20T20:00:00+07:00',
        'ends_at': '2026-07-21T04:00:00+07:00',
        'shift': {'id': 2, 'nama': 'Malam'},
        'location': {'nama_lokasi': 'Kafe Utama'},
      },
      'actions': {'can_clock_in': true, 'can_clock_out': false},
    });

    expect(session.canClockIn, isTrue);
    expect(session.canClockOut, isFalse);
    expect(session.schedule?.name, 'Malam');
    expect(session.schedule?.locationName, 'Kafe Utama');
    expect(session.schedule?.endsAt?.day, 21);
  });

  test('parses new overnight attendance session contract', () {
    final session = AttendanceSession.fromJson({
      'data': {
        'id': 12,
        'tanggal_shift': '2026-07-20',
        'clock_in_at': '2026-07-20T22:00:00+07:00',
        'clock_out_at': '2026-07-21T06:00:00+07:00',
        'attendance_status': 'hadir',
        'session_status': 'completed',
        'worked_minutes': 480,
        'paid_minutes': 450,
        'estimated_salary': '125000.50',
      },
      'schedule': {
        'name': 'Shift Malam',
        'starts_at': '2026-07-20T22:00:00+07:00',
        'ends_at': '2026-07-21T06:00:00+07:00',
      },
      'server_time': '2026-07-21T06:00:01+07:00',
      'timezone': 'Asia/Jakarta',
      'can_clock_in': false,
      'can_clock_out': false,
    });

    expect(session.record?.id, 12);
    expect(session.record?.workedMinutes, 480);
    expect(session.record?.estimatedSalary, 125000.5);
    expect(
      session.record!.jamPulang!.difference(session.record!.jamMasuk!),
      const Duration(hours: 8),
    );
    expect(session.schedule?.name, 'Shift Malam');
    expect(session.canClockOut, isFalse);
  });

  test('parses legacy date and time fields', () {
    final record = AttendanceRecord.fromJson({
      'tanggal': '2026-07-20',
      'jam_masuk': '22:00:00',
      'jam_pulang': '06:00:00',
      'status_kehadiran': 'terlambat',
    });

    expect(record.jamMasukStr, '22:00');
    expect(record.jamPulang?.day, 21);
    expect(record.terlambat, isTrue);
  });

  test('maps machine-readable clock errors', () {
    final mocked = ClockResult.error(
      {'code': 'mocked_location', 'message': 'Fake GPS'},
      action: ClockAction.clockIn,
      statusCode: 422,
    );
    final session = ClockResult.error(
      {'code': 'no_open_session'},
      action: ClockAction.clockOut,
      statusCode: 409,
    );

    expect(mocked.status, ClockStatus.mockedLocation);
    expect(session.status, ClockStatus.noOpenSession);
  });
}
