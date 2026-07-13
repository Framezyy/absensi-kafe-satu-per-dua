/// Model lokasi kerja (geofence) dari API Laravel.
///
/// Catatan: kolom decimal (latitude, longitude) dikembalikan Laravel
/// sebagai String di JSON, jadi parser menerima String maupun num.
class LokasiKerja {
  const LokasiKerja({
    required this.id,
    required this.namaLokasi,
    required this.latitude,
    required this.longitude,
    required this.radiusMeter,
    required this.jamMasukStandar,
    required this.toleransiMenit,
  });

  final int id;
  final String namaLokasi;
  final double latitude;
  final double longitude;
  final int radiusMeter;
  final String jamMasukStandar; // "08:00:00"
  final int toleransiMenit;

  factory LokasiKerja.fromJson(Map<String, dynamic> json) {
    double parseDouble(dynamic v) {
      if (v is num) return v.toDouble();
      if (v is String) return double.tryParse(v) ?? 0.0;
      return 0.0;
    }

    int parseInt(dynamic v) {
      if (v is num) return v.toInt();
      if (v is String) return int.tryParse(v) ?? 0;
      return 0;
    }

    return LokasiKerja(
      id: parseInt(json['id']),
      namaLokasi: json['nama_lokasi'] as String? ?? 'Lokasi Kerja',
      latitude: parseDouble(json['latitude']),
      longitude: parseDouble(json['longitude']),
      radiusMeter: parseInt(json['radius_meter']),
      jamMasukStandar: json['jam_masuk_standar'] as String? ?? '08:00:00',
      toleransiMenit: parseInt(json['toleransi_menit']),
    );
  }
}