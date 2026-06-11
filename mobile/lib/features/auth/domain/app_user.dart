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
    required String nik,
    required String jabatan,
    @JsonKey(name: 'tanggal_bergabung') required DateTime tanggalBergabung,
    @JsonKey(name: 'status_aktif') required bool statusAktif,

    /// Flag dari backend: TRUE bila ada `face_embeddings.is_aktif = TRUE`.
    @JsonKey(name: 'has_face_enrolled') required bool hasFaceEnrolled,
  }) = _AppUser;

  factory AppUser.fromJson(Map<String, dynamic> json) =>
      _$AppUserFromJson(json);
}
