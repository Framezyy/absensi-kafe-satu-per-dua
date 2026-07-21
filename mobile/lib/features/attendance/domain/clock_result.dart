import 'attendance_record.dart';

enum ClockAction { clockIn, clockOut }

enum ClockStatus {
  success,
  alreadyDone,
  outsideGeofence,
  mockedLocation,
  noSchedule,
  noOpenSession,
  faceMismatch,
  validation,
  timeout,
  error,
}

class ClockResult {
  const ClockResult({
    required this.status,
    required this.action,
    this.record,
    this.serverTime,
    this.code,
    this.message,
  });

  final ClockStatus status;
  final ClockAction action;
  final AttendanceRecord? record;
  final DateTime? serverTime;
  final String? code;
  final String? message;

  DateTime? get jamServer => serverTime;
  bool get isSuccess => status == ClockStatus.success;

  factory ClockResult.fromJson(
    Map<String, dynamic> json, {
    required ClockAction action,
  }) {
    final data = _map(json['data'] ?? json['attendance'] ?? json['record']);
    final code = (json['code'] ?? json['error_code'])?.toString();
    return ClockResult(
      status: _status(code, success: true),
      action: action,
      record: data == null ? null : AttendanceRecord.fromJson(data),
      serverTime: _date(json['server_time'] ?? json['timestamp']),
      code: code,
      message: json['message']?.toString(),
    );
  }

  factory ClockResult.error(
    dynamic body, {
    required ClockAction action,
    int? statusCode,
  }) {
    final json = _map(body) ?? const <String, dynamic>{};
    final code = (json['code'] ?? json['error_code'])?.toString();
    return ClockResult(
      status: _status(code, statusCode: statusCode),
      action: action,
      serverTime: _date(json['server_time'] ?? json['timestamp']),
      code: code,
      message: json['message']?.toString() ?? json['error']?.toString(),
    );
  }

  static ClockStatus _status(
    String? raw, {
    bool success = false,
    int? statusCode,
  }) {
    if (success) return ClockStatus.success;
    final code = raw?.toLowerCase().replaceAll('-', '_') ?? '';
    if (code.contains('geofence') || code.contains('outside_radius')) {
      return ClockStatus.outsideGeofence;
    }
    if (code.contains('mock') || code.contains('fake_location')) {
      return ClockStatus.mockedLocation;
    }
    if (code.contains('no_schedule') || code.contains('schedule_not_found')) {
      return ClockStatus.noSchedule;
    }
    if (code.contains('no_open_session') || code.contains('not_clocked_in')) {
      return ClockStatus.noOpenSession;
    }
    if (code.contains('already') || code.contains('completed')) {
      return ClockStatus.alreadyDone;
    }
    if (code.contains('face')) return ClockStatus.faceMismatch;
    if (statusCode == 422 || code.contains('validation')) {
      return ClockStatus.validation;
    }
    return ClockStatus.error;
  }

  static Map<String, dynamic>? _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return null;
  }

  static DateTime? _date(dynamic value) =>
      value is String ? DateTime.tryParse(value) : null;
}
