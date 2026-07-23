import 'package:dio/dio.dart';

import '../../../core/http/dio_client.dart';
import '../domain/attendance_correction.dart';
import '../domain/attendance_record.dart';
import '../domain/attendance_session.dart';
import '../domain/clock_result.dart';
import 'attendance_repository.dart';

class ApiAttendanceRepository implements AttendanceRepository {
  ApiAttendanceRepository({Dio? dio}) : _dio = dio ?? DioClient.instance.dio;

  final Dio _dio;

  @override
  Future<AttendanceSession> getToday() async {
    final response = await _dio.get('/attendance/today');
    return AttendanceSession.fromJson(_map(response.data));
  }

  @override
  Future<List<AttendanceRecord>> getHistory({
    required int year,
    required int month,
  }) async {
    final response = await _dio.get(
      '/attendance/history',
      queryParameters: {
        'month': '${year.toString()}-${month.toString().padLeft(2, '0')}',
      },
    );
    final body = _map(response.data);
    final data = body['data'];
    if (data is! List) return const [];
    return data.map((item) => AttendanceRecord.fromJson(_map(item))).toList();
  }

  @override
  Future<ClockResult> clockIn({
    required double latitude,
    required double longitude,
    required String faceVerificationToken,
    bool isMocked = false,
  }) => _clock('/attendance/clock-in', ClockAction.clockIn, {
    'latitude': latitude,
    'longitude': longitude,
    'face_verification_token': faceVerificationToken,
    'is_mocked': isMocked,
  });

  @override
  Future<ClockResult> clockOut({
    required double latitude,
    required double longitude,
    required String faceVerificationToken,
    bool isMocked = false,
  }) => _clock('/attendance/clock-out', ClockAction.clockOut, {
    'latitude': latitude,
    'longitude': longitude,
    'face_verification_token': faceVerificationToken,
    'is_mocked': isMocked,
  });

  Future<ClockResult> _clock(
    String path,
    ClockAction action,
    Map<String, dynamic> payload,
  ) async {
    try {
      final response = await _dio.post(path, data: payload);
      return ClockResult.fromJson(_map(response.data), action: action);
    } on DioException catch (error) {
      if (error.response != null) {
        return ClockResult.error(
          error.response!.data,
          action: action,
          statusCode: error.response!.statusCode,
        );
      }
      if (error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.sendTimeout ||
          error.type == DioExceptionType.receiveTimeout) {
        return ClockResult.error(
          const {'code': 'timeout', 'message': 'Koneksi ke server timeout.'},
          action: action,
          statusCode: 408,
        );
      }
      rethrow;
    }
  }

  @override
  Future<AttendanceCorrection> submitCorrection({
    required int attendanceId,
    required DateTime clockOutAt,
    required String reason,
  }) async {
    final response = await _dio.post(
      '/attendance/corrections',
      data: {
        'attendance_id': attendanceId,
        'clock_out_at': clockOutAt.toIso8601String(),
        'reason': reason,
      },
    );
    final body = _map(response.data);
    return AttendanceCorrection.fromJson(_map(body['data'] ?? body));
  }

  @override
  Future<List<AttendanceCorrection>> getCorrections() async {
    final response = await _dio.get('/attendance/corrections');
    final body = _map(response.data);
    final data = body['data'];
    if (data is! List) return const [];
    return data
        .map((item) => AttendanceCorrection.fromJson(_map(item)))
        .toList();
  }

  static Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return <String, dynamic>{};
  }
}
