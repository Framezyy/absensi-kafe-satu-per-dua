import 'package:freezed_annotation/freezed_annotation.dart';

part 'app_user.freezed.dart';
part 'app_user.g.dart';

/// Entity karyawan yang sedang login.
///
/// Dibuat dengan Freezed 3.x supaya `==`, `hashCode`, `copyWith`, dan
/// JSON serialization (untuk Phase 4) di-generate otomatis. Field
/// `hasFaceEnrolled` adalah flag krusial yang menentukan alur
/// conditional di router (lihat plan keputusan #6e).
@freezed
abstract class AppUser with _$AppUser {
  const factory AppUser({
    required int id,
    required String username,
    @JsonKey(name: 'nama') required String nama,
    @JsonKey(name: 'id_karyawan') required String idKaryawan,
    required String jabatan,
    @JsonKey(name: 'tanggal_bergabung') required DateTime tanggalBergabung,
    @JsonKey(name: 'status_aktif') required bool statusAktif,

    /// Flag dari backend: TRUE bila ada `face_embeddings.is_aktif = TRUE`.
    @JsonKey(name: 'has_face_enrolled') required bool hasFaceEnrolled,
  }) = _AppUser;

  factory AppUser.fromJson(Map<String, dynamic> json) {
    String string(dynamic value, [String fallback = '']) {
      final text = value?.toString().trim() ?? '';
      return text.isEmpty ? fallback : text;
    }

    bool boolean(dynamic value) {
      if (value is bool) return value;
      if (value is num) return value != 0;
      return const {
        '1',
        'true',
        'yes',
        'active',
      }.contains(value?.toString().trim().toLowerCase());
    }

    final joined = DateTime.tryParse(
      string(
        json['tanggal_bergabung'] ?? json['joined_at'] ?? json['join_date'],
      ),
    );

    return _$AppUserFromJson({
      'id': int.tryParse(json['id']?.toString() ?? '') ?? 0,
      'username': string(json['username'] ?? json['email']),
      'nama': string(json['nama'] ?? json['name']),
      'id_karyawan': string(
        json['id_karyawan'] ?? json['employee_id'] ?? json['employee_code'],
      ),
      'jabatan': string(json['jabatan'] ?? json['position'] ?? json['role']),
      'tanggal_bergabung': (joined ?? DateTime(1970)).toIso8601String(),
      'status_aktif': boolean(json['status_aktif'] ?? json['is_active']),
      'has_face_enrolled': boolean(
        json['has_face_enrolled'] ?? json['face_enrolled'],
      ),
    });
  }
}
