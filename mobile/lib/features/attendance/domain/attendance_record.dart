/// Model data absensi per hari.
///
/// Mendukung `fromJson` untuk parsing dari API Laravel.
class AttendanceRecord {
  const AttendanceRecord({
    required this.tanggal,
    this.jamMasuk,
    this.jamPulang,
    required this.terlambat,
    required this.lokasiNama,
    this.faceSimilarity,
  });

  final DateTime tanggal;
  final DateTime? jamMasuk;
  final DateTime? jamPulang;
  final bool terlambat;
  final String lokasiNama;
  final double? faceSimilarity;

  bool get hasMasuk => jamMasuk != null;
  bool get hasPulang => jamPulang != null;
  bool get hadir => hasMasuk;

  String get jamMasukStr =>
      jamMasuk != null
          ? '${jamMasuk!.hour.toString().padLeft(2, '0')}:${jamMasuk!.minute.toString().padLeft(2, '0')}'
          : '-';
  String get jamPulangStr =>
      jamPulang != null
          ? '${jamPulang!.hour.toString().padLeft(2, '0')}:${jamPulang!.minute.toString().padLeft(2, '0')}'
          : '-';

  factory AttendanceRecord.fromJson(Map<String, dynamic> json) {
    final tanggal = DateTime.parse(json['tanggal'] as String);
    DateTime? parseTime(String? timeStr) {
      if (timeStr == null) return null;
      final parts = timeStr.split(':');
      return DateTime(
        tanggal.year,
        tanggal.month,
        tanggal.day,
        int.parse(parts[0]),
        int.parse(parts[1]),
        parts.length > 2 ? int.parse(parts[2]) : 0,
      );
    }

    // Laravel mengembalikan kolom decimal sebagai String (mis. "0.8700")
    // untuk menjaga presisi. Parser ini menerima String maupun num.
    double? parseDouble(dynamic value) {
      if (value == null) return null;
      if (value is num) return value.toDouble();
      if (value is String) return double.tryParse(value);
      return null;
    }

    return AttendanceRecord(
      tanggal: tanggal,
      jamMasuk: parseTime(json['jam_masuk'] as String?),
      jamPulang: parseTime(json['jam_pulang'] as String?),
      terlambat: json['status_kehadiran'] == 'terlambat',
      lokasiNama: '',
      faceSimilarity: parseDouble(json['face_similarity_score']),
    );
  }
}
