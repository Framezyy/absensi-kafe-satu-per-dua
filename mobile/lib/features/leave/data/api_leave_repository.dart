import '../../../core/http/dio_client.dart';
import '../../leave/domain/leave_request.dart';

/// Repository izin yang berkomunikasi dengan Laravel API.
///
/// Endpoint:
/// - `GET /leaves`
/// - `POST /leaves`
class ApiLeaveRepository {
  final _dio = DioClient.instance.dio;

  Future<List<LeaveRequest>> getMyLeaves() async {
    final response = await _dio.get('/leaves');
    final data = response.data['data'] as List;
    return data
        .map((e) => LeaveRequest.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<LeaveRequest> submit({
    required DateTime tanggalMulai,
    DateTime? tanggalSelesai,
    required String alasan,
  }) async {
    final response = await _dio.post(
      '/leaves',
      data: {
        'tanggal_mulai': tanggalMulai.toIso8601String().split('T').first,
        'tanggal_selesai': tanggalSelesai?.toIso8601String().split('T').first,
        'alasan': alasan,
      },
    );
    return LeaveRequest.fromJson(response.data['data'] as Map<String, dynamic>);
  }
}
