// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'app_user.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$AppUser {

 int get id; String get username;@JsonKey(name: 'nama') String get nama;@JsonKey(name: 'id_karyawan') String get idKaryawan; String get jabatan;@JsonKey(name: 'tanggal_bergabung') DateTime get tanggalBergabung;@JsonKey(name: 'status_aktif') bool get statusAktif;/// Flag dari backend: TRUE bila ada `face_embeddings.is_aktif = TRUE`.
@JsonKey(name: 'has_face_enrolled') bool get hasFaceEnrolled;
/// Create a copy of AppUser
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$AppUserCopyWith<AppUser> get copyWith => _$AppUserCopyWithImpl<AppUser>(this as AppUser, _$identity);

  /// Serializes this AppUser to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is AppUser&&(identical(other.id, id) || other.id == id)&&(identical(other.username, username) || other.username == username)&&(identical(other.nama, nama) || other.nama == nama)&&(identical(other.idKaryawan, idKaryawan) || other.idKaryawan == idKaryawan)&&(identical(other.jabatan, jabatan) || other.jabatan == jabatan)&&(identical(other.tanggalBergabung, tanggalBergabung) || other.tanggalBergabung == tanggalBergabung)&&(identical(other.statusAktif, statusAktif) || other.statusAktif == statusAktif)&&(identical(other.hasFaceEnrolled, hasFaceEnrolled) || other.hasFaceEnrolled == hasFaceEnrolled));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,username,nama,idKaryawan,jabatan,tanggalBergabung,statusAktif,hasFaceEnrolled);

@override
String toString() {
  return 'AppUser(id: $id, username: $username, nama: $nama, idKaryawan: $idKaryawan, jabatan: $jabatan, tanggalBergabung: $tanggalBergabung, statusAktif: $statusAktif, hasFaceEnrolled: $hasFaceEnrolled)';
}


}

/// @nodoc
abstract mixin class $AppUserCopyWith<$Res>  {
  factory $AppUserCopyWith(AppUser value, $Res Function(AppUser) _then) = _$AppUserCopyWithImpl;
@useResult
$Res call({
 int id, String username,@JsonKey(name: 'nama') String nama,@JsonKey(name: 'id_karyawan') String idKaryawan, String jabatan,@JsonKey(name: 'tanggal_bergabung') DateTime tanggalBergabung,@JsonKey(name: 'status_aktif') bool statusAktif,@JsonKey(name: 'has_face_enrolled') bool hasFaceEnrolled
});




}
/// @nodoc
class _$AppUserCopyWithImpl<$Res>
    implements $AppUserCopyWith<$Res> {
  _$AppUserCopyWithImpl(this._self, this._then);

  final AppUser _self;
  final $Res Function(AppUser) _then;

/// Create a copy of AppUser
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? username = null,Object? nama = null,Object? idKaryawan = null,Object? jabatan = null,Object? tanggalBergabung = null,Object? statusAktif = null,Object? hasFaceEnrolled = null,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,username: null == username ? _self.username : username // ignore: cast_nullable_to_non_nullable
as String,nama: null == nama ? _self.nama : nama // ignore: cast_nullable_to_non_nullable
as String,idKaryawan: null == idKaryawan ? _self.idKaryawan : idKaryawan // ignore: cast_nullable_to_non_nullable
as String,jabatan: null == jabatan ? _self.jabatan : jabatan // ignore: cast_nullable_to_non_nullable
as String,tanggalBergabung: null == tanggalBergabung ? _self.tanggalBergabung : tanggalBergabung // ignore: cast_nullable_to_non_nullable
as DateTime,statusAktif: null == statusAktif ? _self.statusAktif : statusAktif // ignore: cast_nullable_to_non_nullable
as bool,hasFaceEnrolled: null == hasFaceEnrolled ? _self.hasFaceEnrolled : hasFaceEnrolled // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}

}


/// Adds pattern-matching-related methods to [AppUser].
extension AppUserPatterns on AppUser {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _AppUser value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _AppUser() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _AppUser value)  $default,){
final _that = this;
switch (_that) {
case _AppUser():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _AppUser value)?  $default,){
final _that = this;
switch (_that) {
case _AppUser() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( int id,  String username, @JsonKey(name: 'nama')  String nama, @JsonKey(name: 'id_karyawan')  String idKaryawan,  String jabatan, @JsonKey(name: 'tanggal_bergabung')  DateTime tanggalBergabung, @JsonKey(name: 'status_aktif')  bool statusAktif, @JsonKey(name: 'has_face_enrolled')  bool hasFaceEnrolled)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _AppUser() when $default != null:
return $default(_that.id,_that.username,_that.nama,_that.idKaryawan,_that.jabatan,_that.tanggalBergabung,_that.statusAktif,_that.hasFaceEnrolled);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( int id,  String username, @JsonKey(name: 'nama')  String nama, @JsonKey(name: 'id_karyawan')  String idKaryawan,  String jabatan, @JsonKey(name: 'tanggal_bergabung')  DateTime tanggalBergabung, @JsonKey(name: 'status_aktif')  bool statusAktif, @JsonKey(name: 'has_face_enrolled')  bool hasFaceEnrolled)  $default,) {final _that = this;
switch (_that) {
case _AppUser():
return $default(_that.id,_that.username,_that.nama,_that.idKaryawan,_that.jabatan,_that.tanggalBergabung,_that.statusAktif,_that.hasFaceEnrolled);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( int id,  String username, @JsonKey(name: 'nama')  String nama, @JsonKey(name: 'id_karyawan')  String idKaryawan,  String jabatan, @JsonKey(name: 'tanggal_bergabung')  DateTime tanggalBergabung, @JsonKey(name: 'status_aktif')  bool statusAktif, @JsonKey(name: 'has_face_enrolled')  bool hasFaceEnrolled)?  $default,) {final _that = this;
switch (_that) {
case _AppUser() when $default != null:
return $default(_that.id,_that.username,_that.nama,_that.idKaryawan,_that.jabatan,_that.tanggalBergabung,_that.statusAktif,_that.hasFaceEnrolled);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _AppUser implements AppUser {
  const _AppUser({required this.id, required this.username, @JsonKey(name: 'nama') required this.nama, @JsonKey(name: 'id_karyawan') required this.idKaryawan, required this.jabatan, @JsonKey(name: 'tanggal_bergabung') required this.tanggalBergabung, @JsonKey(name: 'status_aktif') required this.statusAktif, @JsonKey(name: 'has_face_enrolled') required this.hasFaceEnrolled});
  factory _AppUser.fromJson(Map<String, dynamic> json) => _$AppUserFromJson(json);

@override final  int id;
@override final  String username;
@override@JsonKey(name: 'nama') final  String nama;
@override@JsonKey(name: 'id_karyawan') final  String idKaryawan;
@override final  String jabatan;
@override@JsonKey(name: 'tanggal_bergabung') final  DateTime tanggalBergabung;
@override@JsonKey(name: 'status_aktif') final  bool statusAktif;
/// Flag dari backend: TRUE bila ada `face_embeddings.is_aktif = TRUE`.
@override@JsonKey(name: 'has_face_enrolled') final  bool hasFaceEnrolled;

/// Create a copy of AppUser
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$AppUserCopyWith<_AppUser> get copyWith => __$AppUserCopyWithImpl<_AppUser>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$AppUserToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _AppUser&&(identical(other.id, id) || other.id == id)&&(identical(other.username, username) || other.username == username)&&(identical(other.nama, nama) || other.nama == nama)&&(identical(other.idKaryawan, idKaryawan) || other.idKaryawan == idKaryawan)&&(identical(other.jabatan, jabatan) || other.jabatan == jabatan)&&(identical(other.tanggalBergabung, tanggalBergabung) || other.tanggalBergabung == tanggalBergabung)&&(identical(other.statusAktif, statusAktif) || other.statusAktif == statusAktif)&&(identical(other.hasFaceEnrolled, hasFaceEnrolled) || other.hasFaceEnrolled == hasFaceEnrolled));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,username,nama,idKaryawan,jabatan,tanggalBergabung,statusAktif,hasFaceEnrolled);

@override
String toString() {
  return 'AppUser(id: $id, username: $username, nama: $nama, idKaryawan: $idKaryawan, jabatan: $jabatan, tanggalBergabung: $tanggalBergabung, statusAktif: $statusAktif, hasFaceEnrolled: $hasFaceEnrolled)';
}


}

/// @nodoc
abstract mixin class _$AppUserCopyWith<$Res> implements $AppUserCopyWith<$Res> {
  factory _$AppUserCopyWith(_AppUser value, $Res Function(_AppUser) _then) = __$AppUserCopyWithImpl;
@override @useResult
$Res call({
 int id, String username,@JsonKey(name: 'nama') String nama,@JsonKey(name: 'id_karyawan') String idKaryawan, String jabatan,@JsonKey(name: 'tanggal_bergabung') DateTime tanggalBergabung,@JsonKey(name: 'status_aktif') bool statusAktif,@JsonKey(name: 'has_face_enrolled') bool hasFaceEnrolled
});




}
/// @nodoc
class __$AppUserCopyWithImpl<$Res>
    implements _$AppUserCopyWith<$Res> {
  __$AppUserCopyWithImpl(this._self, this._then);

  final _AppUser _self;
  final $Res Function(_AppUser) _then;

/// Create a copy of AppUser
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? username = null,Object? nama = null,Object? idKaryawan = null,Object? jabatan = null,Object? tanggalBergabung = null,Object? statusAktif = null,Object? hasFaceEnrolled = null,}) {
  return _then(_AppUser(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,username: null == username ? _self.username : username // ignore: cast_nullable_to_non_nullable
as String,nama: null == nama ? _self.nama : nama // ignore: cast_nullable_to_non_nullable
as String,idKaryawan: null == idKaryawan ? _self.idKaryawan : idKaryawan // ignore: cast_nullable_to_non_nullable
as String,jabatan: null == jabatan ? _self.jabatan : jabatan // ignore: cast_nullable_to_non_nullable
as String,tanggalBergabung: null == tanggalBergabung ? _self.tanggalBergabung : tanggalBergabung // ignore: cast_nullable_to_non_nullable
as DateTime,statusAktif: null == statusAktif ? _self.statusAktif : statusAktif // ignore: cast_nullable_to_non_nullable
as bool,hasFaceEnrolled: null == hasFaceEnrolled ? _self.hasFaceEnrolled : hasFaceEnrolled // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}


}

// dart format on
