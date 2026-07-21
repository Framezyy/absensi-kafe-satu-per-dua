import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../env/env.dart';

/// Singleton Dio client untuk komunikasi dengan Laravel API.
///
/// Token Sanctum disimpan di `FlutterSecureStorage` dan otomatis
/// di-attach ke setiap request via interceptor.
class DioClient {
  DioClient._();

  static final DioClient _instance = DioClient._();
  static DioClient get instance => _instance;

  static const _tokenKey = 'sanctum_token';
  static const _storage = FlutterSecureStorage();

  /// Callback dipanggil saat server balas 401 (token invalid/dihapus admin).
  /// Di-set oleh AuthController untuk memicu force-logout + redirect login.
  void Function()? onUnauthorized;

  late final Dio dio =
      Dio(
          BaseOptions(
            baseUrl: Env.apiBaseUrl,
            connectTimeout: const Duration(seconds: 10),
            receiveTimeout: const Duration(seconds: 10),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          ),
        )
        ..interceptors.add(
          InterceptorsWrapper(
            onRequest: (options, handler) async {
              final token = await _storage.read(key: _tokenKey);
              if (token != null) {
                options.headers['Authorization'] = 'Bearer $token';
              }
              return handler.next(options);
            },
            onError: (error, handler) async {
              if (error.response?.statusCode == 401) {
                // Token expired/invalid (mis. akun dihapus admin) — hapus token
                // lokal lalu picu force-logout supaya app redirect ke login.
                await _storage.delete(key: _tokenKey);
                onUnauthorized?.call();
              }
              return handler.next(error);
            },
          ),
        );

  /// Simpan token setelah login berhasil.
  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  /// Hapus token saat logout.
  Future<void> clearToken() => _storage.delete(key: _tokenKey);

  /// Ambil token yang tersimpan (null jika belum login).
  Future<String?> getToken() => _storage.read(key: _tokenKey);
}
