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

  test('clock success keeps Asia Jakarta time for offset and UTC inputs', () {
    final offsetResult = ClockResult.fromJson({
      'server_time': '2026-07-21T17:24:00+07:00',
    }, action: ClockAction.clockIn);
    final utcResult = ClockResult.fromJson({
      'server_time': '2026-07-21T10:24:00Z',
    }, action: ClockAction.clockOut);

    expect(offsetResult.serverTime?.hour, 17);
    expect(offsetResult.serverTime?.minute, 24);
    expect(utcResult.serverTime?.hour, 17);
    expect(utcResult.serverTime?.minute, 24);
  });

  test('attendance record displays equivalent timestamps as 17:24 WIB', () {
    final offsetRecord = AttendanceRecord.fromJson({
      'tanggal_shift': '2026-07-21',
      'clock_in_at': '2026-07-21T17:24:00+07:00',
      'clock_out_at': '2026-07-21T17:24:00+07:00',
    });
    final utcRecord = AttendanceRecord.fromJson({
      'tanggal_shift': '2026-07-21',
      'clock_in_at': '2026-07-21T10:24:00Z',
      'clock_out_at': '2026-07-21T10:24:00Z',
    });

    expect(offsetRecord.jamMasukStr, '17:24');
    expect(offsetRecord.jamPulangStr, '17:24');
    expect(utcRecord.jamMasukStr, '17:24');
    expect(utcRecord.jamPulangStr, '17:24');
  });
}
