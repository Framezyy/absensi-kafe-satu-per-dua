import 'package:dio/dio.dart';

import '../../../core/http/dio_client.dart';
import '../domain/lokasi_kerja.dart';

/// Repository untuk mengambil lokasi kerja aktif dari API.
///
/// Endpoint: `GET /locations/active`
class ApiLocationRepository {
  final _dio = DioClient.instance.dio;

  /// Ambil lokasi kerja aktif. Null jika admin belum menetapkan lokasi.
  Future<LokasiKerja?> getActiveLocation() async {
    try {
      final response = await _dio.get('/locations/active');
      final data = response.data['data'];
      if (data == null) return null;
      return LokasiKerja.fromJson(data as Map<String, dynamic>);
    } on DioException catch (error) {
      if (error.response?.statusCode == 404) return null;
      rethrow;
    }
  }
}
