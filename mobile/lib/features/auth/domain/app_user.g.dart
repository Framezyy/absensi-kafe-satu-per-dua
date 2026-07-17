// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_user.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_AppUser _$AppUserFromJson(Map<String, dynamic> json) => _AppUser(
  id: (json['id'] as num).toInt(),
  username: json['username'] as String,
  nama: json['nama'] as String,
  idKaryawan: json['id_karyawan'] as String,
  jabatan: json['jabatan'] as String,
  tanggalBergabung: DateTime.parse(json['tanggal_bergabung'] as String),
  statusAktif: json['status_aktif'] as bool,
  hasFaceEnrolled: json['has_face_enrolled'] as bool,
);

Map<String, dynamic> _$AppUserToJson(_AppUser instance) => <String, dynamic>{
  'id': instance.id,
  'username': instance.username,
  'nama': instance.nama,
  'id_karyawan': instance.idKaryawan,
  'jabatan': instance.jabatan,
  'tanggal_bergabung': instance.tanggalBergabung.toIso8601String(),
  'status_aktif': instance.statusAktif,
  'has_face_enrolled': instance.hasFaceEnrolled,
};
