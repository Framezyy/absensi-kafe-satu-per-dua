import 'package:dio/dio.dart';

import '../../../core/http/dio_client.dart';
import '../domain/app_user.dart';
import '../domain/auth_exceptions.dart';
import 'auth_repository.dart';

/// Implementasi [AuthRepository] yang berkomunikasi dengan Laravel API.
///
/// Endpoint:
/// - `POST /auth/login`
/// - `GET /auth/me`
/// - `POST /auth/logout`
class ApiAuthRepository implements AuthRepository {
  final _dio = DioClient.instance.dio;

  @override
  Future<AppUser> login({
    required String username,
    required String password,
  }) async {
    try {
      final response = await _dio.post('/auth/login', data: {
        'username': username,
        'password': password,
      });

      final data = response.data;
      await DioClient.instance.saveToken(data['token']);

      return AppUser.fromJson(data['user'] as Map<String, dynamic>);
    } on Exception catch (e) {
      if (e is DioException && e.response?.statusCode == 422) {
        throw const InvalidCredentialsException();
      }
      rethrow;
    }
  }

  @override
  Future<void> logout() async {
    try {
      await _dio.post('/auth/logout');
    } finally {
      await DioClient.instance.clearToken();
    }
  }

  @override
  Future<AppUser?> getCurrentUser() async {
    final token = await DioClient.instance.getToken();
    if (token == null) return null;

    try {
      final response = await _dio.get('/auth/me');
      return AppUser.fromJson(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        await DioClient.instance.clearToken();
        return null;
      }
      rethrow;
    }
  }
}
