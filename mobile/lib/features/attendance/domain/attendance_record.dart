import '../../../shared/utils/attendance_time.dart';

class AttendanceSchedule {
  const AttendanceSchedule({
    this.id,
    this.name,
    this.date,
    this.startsAt,
    this.endsAt,
    this.locationName,
  });

  final int? id;
  final String? name;
  final DateTime? date;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final String? locationName;

  String get timeRange {
    final start = _formatTime(startsAt);
    final end = _formatTime(endsAt);
    if (start == '-' && end == '-') return '-';
    return '$start - $end';
  }

  factory AttendanceSchedule.fromJson(
    Map<String, dynamic> json, {
    DateTime? fallbackDate,
  }) {
    final date =
        _parseDateTime(
          json['tanggal_shift'] ?? json['date'] ?? json['tanggal'],
        ) ??
        fallbackDate;
    final shift = _asMap(json['shift']);
    final location = _asMap(
      json['location'] ?? json['lokasi'] ?? json['lokasi_kerja'],
    );
    return AttendanceSchedule(
      id: _parseInt(json['id'] ?? json['shift_id']),
      name: _parseString(
        shift?['name'] ??
            shift?['nama'] ??
            json['name'] ??
            json['nama'] ??
            json['shift_name'] ??
            json['nama_shift'],
      ),
      date: date,
      startsAt: _parseDateOrTime(
        json['starts_at'] ??
            json['start_at'] ??
            shift?['jam_mulai'] ??
            json['jam_mulai'] ??
            json['jam_masuk'],
        date,
      ),
      endsAt: _parseDateOrTime(
        json['ends_at'] ??
            json['end_at'] ??
            shift?['jam_selesai'] ??
            json['jam_selesai'] ??
            json['jam_pulang'],
        date,
        overnightFrom: _parseDateOrTime(
          json['starts_at'] ??
              json['start_at'] ??
              shift?['jam_mulai'] ??
              json['jam_mulai'] ??
              json['jam_masuk'],
          date,
        ),
      ),
      locationName: _parseString(
        location?['name'] ??
            location?['nama_lokasi'] ??
            json['location_name'] ??
            json['lokasi_nama'],
      ),
    );
  }
}

/// Data absensi satu shift. Nama getter lama dipertahankan selama rollout API.
class AttendanceRecord {
  const AttendanceRecord({
    this.id,
    required this.tanggal,
    this.jamMasuk,
    this.jamPulang,
    required this.terlambat,
    required this.lokasiNama,
    this.faceSimilarity,
    this.attendanceStatus,
    this.sessionStatus,
    this.lateMinutes,
    this.workedMinutes,
    this.paidMinutes,
    this.estimatedSalary,
    this.shift,
    this.schedule,
  });

  final int? id;
  final DateTime tanggal;
  final DateTime? jamMasuk;
  final DateTime? jamPulang;
  final bool terlambat;
  final String lokasiNama;
  final double? faceSimilarity;
  final String? attendanceStatus;
  final String? sessionStatus;
  final int? lateMinutes;
  final int? workedMinutes;
  final int? paidMinutes;
  final double? estimatedSalary;
  final AttendanceSchedule? shift;
  final AttendanceSchedule? schedule;

  DateTime get tanggalShift => tanggal;
  DateTime? get clockInAt => jamMasuk;
  DateTime? get clockOutAt => jamPulang;
  bool get hasMasuk => jamMasuk != null;
  bool get hasPulang => jamPulang != null;
  bool get hadir => hasMasuk;
  bool get isIncomplete {
    final session = sessionStatus?.toLowerCase();
    final attendance = attendanceStatus?.toLowerCase();
    return session == 'incomplete' ||
        session == 'tidak_lengkap' ||
        attendance == 'incomplete' ||
        attendance == 'tidak_lengkap';
  }

  bool get isCorrected {
    final session = sessionStatus?.toLowerCase();
    final attendance = attendanceStatus?.toLowerCase();
    return session == 'corrected' ||
        session == 'dikoreksi' ||
        attendance == 'corrected' ||
        attendance == 'dikoreksi';
  }

  String get jamMasukStr => _formatTime(jamMasuk);
  String get jamPulangStr => _formatTime(jamPulang);

  factory AttendanceRecord.fromJson(Map<String, dynamic> json) {
    final tanggal =
        _parseDateTime(json['tanggal_shift'] ?? json['tanggal']) ??
        _parseDateTime(json['clock_in_at'] ?? json['jam_masuk']) ??
        DateTime.fromMillisecondsSinceEpoch(0);
    final shiftJson = _asMap(json['shift']);
    final scheduleJson = _asMap(
      json['schedule'] ?? json['jadwal'] ?? json['jadwal_kerja'],
    );
    final location =
        _asMap(json['location'] ?? json['lokasi']) ??
        _asMap(shiftJson?['location'] ?? scheduleJson?['location']);
    final attendanceStatus = _parseString(
      json['attendance_status'] ?? json['status_kehadiran'],
    );
    final lateMinutes = _parseInt(
      json['late_minutes'] ?? json['menit_terlambat'],
    );

    return AttendanceRecord(
      id: _parseInt(json['id'] ?? json['attendance_id']),
      tanggal: DateTime(tanggal.year, tanggal.month, tanggal.day),
      jamMasuk: _parseDateOrTime(
        json['clock_in_at'] ?? json['jam_masuk'],
        tanggal,
      ),
      jamPulang: _parseDateOrTime(
        json['clock_out_at'] ?? json['jam_pulang'],
        tanggal,
        overnightFrom: _parseDateOrTime(
          json['clock_in_at'] ?? json['jam_masuk'],
          tanggal,
        ),
      ),
      terlambat: (lateMinutes ?? 0) > 0 || attendanceStatus == 'terlambat',
      lokasiNama:
          _parseString(
            location?['name'] ??
                location?['nama_lokasi'] ??
                json['location_name'] ??
                json['lokasi_nama'],
          ) ??
          '',
      faceSimilarity: _parseDouble(
        json['face_similarity_score'] ?? json['face_similarity'],
      ),
      attendanceStatus: attendanceStatus,
      sessionStatus: _parseString(json['session_status']),
      lateMinutes: lateMinutes,
      workedMinutes: _parseInt(json['worked_minutes']),
      paidMinutes: _parseInt(json['paid_minutes']),
      estimatedSalary: _parseDouble(json['estimated_salary']),
      shift: shiftJson == null
          ? null
          : AttendanceSchedule.fromJson(shiftJson, fallbackDate: tanggal),
      schedule: scheduleJson == null
          ? null
          : AttendanceSchedule.fromJson(scheduleJson, fallbackDate: tanggal),
    );
  }
}

Map<String, dynamic>? _asMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return null;
}

String? _parseString(dynamic value) {
  if (value == null) return null;
  final result = value.toString().trim();
  return result.isEmpty ? null : result;
}

int? _parseInt(dynamic value) {
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value);
  return null;
}

double? _parseDouble(dynamic value) {
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value);
  return null;
}

DateTime? _parseDateTime(dynamic value) {
  return parseAttendanceTime(value);
}

DateTime? _parseDateOrTime(
  dynamic value,
  DateTime? date, {
  DateTime? overnightFrom,
}) {
  final text = value?.toString().trim() ?? '';
  final hasFullDate = text.contains('-') || text.contains('T');
  if (hasFullDate) {
    final parsed = _parseDateTime(value);
    if (parsed != null) return parsed;
  }
  if (value is! String || date == null) return null;
  final parts = value.split(':');
  if (parts.length < 2) return null;
  final hour = int.tryParse(parts[0]);
  final minute = int.tryParse(parts[1]);
  final second = parts.length > 2 ? int.tryParse(parts[2].split('.').first) : 0;
  if (hour == null || minute == null || second == null) return null;
  if (hour < 0 ||
      hour > 23 ||
      minute < 0 ||
      minute > 59 ||
      second < 0 ||
      second > 59) {
    return null;
  }
  var result = DateTime(date.year, date.month, date.day, hour, minute, second);
  if (overnightFrom != null && result.isBefore(overnightFrom)) {
    result = result.add(const Duration(days: 1));
  }
  return result;
}

String _formatTime(DateTime? value) => formatAttendanceTime(value);
