import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../../../core/http/dio_client.dart';

/// Result dari operasi face enrollment/verify.
class FaceResult {
  const FaceResult({
    required this.success,
    this.similarity,
    this.verificationToken,
    this.expiresAt,
    this.message,
    this.code,
  });

  final bool success;
  final double? similarity;
  final String? verificationToken;
  final DateTime? expiresAt;
  final String? message;
  final String? code;

  factory FaceResult.fromJson(Map<String, dynamic> data) {
    final success = data['match'] == true;
    final token = data['verification_token']?.toString();
    return FaceResult(
      success: success && token != null && token.isNotEmpty,
      similarity: ApiFaceRepository._double(data['similarity']),
      verificationToken: token,
      expiresAt: DateTime.tryParse(data['expires_at']?.toString() ?? ''),
      message: data['message']?.toString(),
      code: data['code']?.toString(),
    );
  }
}

/// Repository face recognition yang berkomunikasi dengan Laravel API.
///
/// Endpoint:
/// - `POST /face/enroll` — kirim 3 frame JPEG → FastAPI → simpan embedding
/// - `POST /face/verify` — kirim 1 frame JPEG → FastAPI → cosine similarity
class ApiFaceRepository {
  ApiFaceRepository({Dio? dio}) : _dio = dio ?? DioClient.instance.dio;

  final Dio _dio;

  /// Kirim 3 frame JPEG ke API untuk enrollment.
  ///
  /// Laravel akan meneruskan ke FastAPI untuk generate mean embedding
  /// dari 3 frame, lalu menyimpannya di `face_embeddings` table.
  Future<FaceResult> enroll({required List<Uint8List> frames}) async {
    try {
      final formData = FormData.fromMap({
        'frames[]': frames.asMap().entries.map((entry) {
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
        message:
            e.response?.data['message'] as String? ??
            'Gagal mendaftarkan wajah.',
      );
    }
  }

  /// Kirim 1 frame JPEG ke API untuk verifikasi.
  ///
  /// Mengembalikan [FaceResult] dengan `success=true` jika similarity ≥ 0.7.
  Future<FaceResult> verify({
    required Uint8List frame,
    required String action,
  }) async {
    try {
      final formData = FormData.fromMap({
        'frame': MultipartFile.fromBytes(frame, filename: 'verify.jpg'),
        'action': action,
      });

      final response = await _dio.post(
        '/face/verify',
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );

      final data = _map(response.data);
      return FaceResult.fromJson(data);
    } on DioException catch (e) {
      return FaceResult(
        success: false,
        message:
            _map(e.response?.data)['message']?.toString() ??
            'Gagal verifikasi wajah.',
        code: _map(e.response?.data)['code']?.toString(),
      );
    } catch (_) {
      return const FaceResult(
        success: false,
        message: 'Respons verifikasi wajah tidak valid.',
        code: 'INVALID_FACE_RESPONSE',
      );
    }
  }

  static Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return <String, dynamic>{};
  }

  static double? _double(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse(value?.toString() ?? '');
  }
}
