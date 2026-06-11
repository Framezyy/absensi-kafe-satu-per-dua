import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../../../core/http/dio_client.dart';

/// Result dari operasi face enrollment/verify.
class FaceResult {
  const FaceResult({
    required this.success,
    this.similarity,
    this.message,
  });

  final bool success;
  final double? similarity;
  final String? message;
}

/// Repository face recognition yang berkomunikasi dengan Laravel API.
///
/// Endpoint:
/// - `POST /face/enroll` — kirim 3 frame JPEG → FastAPI → simpan embedding
/// - `POST /face/verify` — kirim 1 frame JPEG → FastAPI → cosine similarity
class ApiFaceRepository {
  final _dio = DioClient.instance.dio;

  /// Kirim 3 frame JPEG ke API untuk enrollment.
  ///
  /// Laravel akan meneruskan ke FastAPI untuk generate mean embedding
  /// dari 3 frame, lalu menyimpannya di `face_embeddings` table.
  Future<FaceResult> enroll({
    required List<Uint8List> frames,
  }) async {
    try {
      final formData = FormData.fromMap({
        'frames': frames.asMap().entries.map((entry) {
          return MultipartFile.fromBytes(
            entry.value,
            filename: 'frame_${entry.key}.jpg',
          );
        }).toList(),
      });

      final response = await _dio.post(
        '/face/enroll',
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );

      return FaceResult(
        success: true,
        message: response.data['message'] as String?,
      );
    } on DioException catch (e) {
      return FaceResult(
        success: false,
        message: e.response?.data['message'] as String? ?? 'Gagal mendaftarkan wajah.',
      );
    }
  }

  /// Kirim 1 frame JPEG ke API untuk verifikasi.
  ///
  /// Mengembalikan [FaceResult] dengan `success=true` jika similarity ≥ 0.7.
  Future<FaceResult> verify({
    required Uint8List frame,
    required int karyawanId,
  }) async {
    try {
      final formData = FormData.fromMap({
        'frame': MultipartFile.fromBytes(frame, filename: 'verify.jpg'),
      });

      final response = await _dio.post(
        '/face/verify',
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );

      final data = response.data;
      return FaceResult(
        success: data['match'] as bool,
        similarity: (data['similarity'] as num).toDouble(),
        message: data['message'] as String?,
      );
    } on DioException catch (e) {
      return FaceResult(
        success: false,
        message: e.response?.data['message'] as String? ?? 'Gagal verifikasi wajah.',
      );
    }
  }
}
