import 'package:dio/dio.dart';

import '../../../core/http/dio_client.dart';
import '../domain/attendance_record.dart';
import '../domain/clock_result.dart';
import 'attendance_repository.dart';

/// Implementasi [AttendanceRepository] yang berkomunikasi dengan Laravel API.
///
/// Endpoint:
/// - `GET /attendance/today`
/// - `POST /attendance/clock-in`
/// - `POST /attendance/clock-out`
/// - `GET /attendance/history?month=YYYY-MM`
class ApiAttendanceRepository implements AttendanceRepository {
  final _dio = DioClient.instance.dio;

  @override
  Future<AttendanceRecord?> getToday() async {
    try {
      final response = await _dio.get('/attendance/today');
      final data = response.data['data'];
      if (data == null) return null;
      return AttendanceRecord.fromJson(data as Map<String, dynamic>);
    } on DioException catch (e) {
      if (e.response?.statusCode == 404) return null;
      rethrow;
    }
  }

  @override
  Future<List<AttendanceRecord>> getHistory({
    required int year,
    required int month,
  }) async {
    final response = await _dio.get('/attendance/history', queryParameters: {
      'month': '${year.toString()}-${month.toString().padLeft(2, '0')}',
    });
    final data = response.data['data'] as List;
    return data
        .map((e) => AttendanceRecord.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<ClockResult> clockIn({
    required double latitude,
    required double longitude,
    double? faceSimilarityScore,
  }) async {
    try {
      final response = await _dio.post('/attendance/clock-in', data: {
        'latitude': latitude,
        'longitude': longitude,
        'face_similarity_score': faceSimilarityScore,
      });
      return ClockResult(
        status: ClockStatus.success,
        action: ClockAction.clockIn,
        message: response.data['message'] as String?,
      );
    } on DioException catch (e) {
      if (e.response?.statusCode == 422) {
        return ClockResult(
          status: ClockStatus.outsideGeofence,
          action: ClockAction.clockIn,
          message: e.response?.data['message'] as String?,
        );
      }
      rethrow;
    }
  }

  Future<ClockResult> clockOut({
    required double latitude,
    required double longitude,
  }) async {
    try {
      final response = await _dio.post('/attendance/clock-out', data: {
        'latitude': latitude,
        'longitude': longitude,
      });
      return ClockResult(
        status: ClockStatus.success,
        action: ClockAction.clockOut,
        message: response.data['message'] as String?,
      );
    } on DioException catch (e) {
      if (e.response?.statusCode == 422) {
        return ClockResult(
          status: ClockStatus.outsideGeofence,
          action: ClockAction.clockOut,
          message: e.response?.data['message'] as String?,
        );
      }
      rethrow;
    }
  }
}
