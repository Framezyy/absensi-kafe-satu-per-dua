import '../../../shared/utils/attendance_time.dart';

/// Status pengajuan izin.
enum LeaveStatus { pending, approved, rejected }

/// Model pengajuan izin.
class LeaveRequest {
  const LeaveRequest({
    required this.id,
    required this.tanggalMulai,
    required this.tanggalSelesai,
    required this.alasan,
    required this.status,
    this.alasanPenolakan,
    required this.diajukanPada,
  });

  final int id;
  final DateTime tanggalMulai;
  final DateTime tanggalSelesai;
  final String alasan;
  final LeaveStatus status;
  final String? alasanPenolakan;
  final DateTime diajukanPada;

  String get statusLabel {
    switch (status) {
      case LeaveStatus.pending:
        return 'Menunggu';
      case LeaveStatus.approved:
        return 'Disetujui';
      case LeaveStatus.rejected:
        return 'Ditolak';
    }
  }

  factory LeaveRequest.fromJson(Map<String, dynamic> json) {
    int integer(dynamic value) => value is num
        ? value.toInt()
        : int.tryParse(value?.toString() ?? '') ?? 0;
    String string(dynamic value, [String fallback = '']) {
      final text = value?.toString().trim() ?? '';
      return text.isEmpty ? fallback : text;
    }

    DateTime date(dynamic value, [DateTime? fallback]) =>
        parseAttendanceTime(value) ?? fallback ?? DateTime(1970);

    final statusStr = string(
      json['status'] ?? json['leave_status'],
      'pending',
    ).toLowerCase();
    final status = LeaveStatus.values.firstWhere(
      (e) => e.name == statusStr,
      orElse: () => LeaveStatus.pending,
    );
    final start = date(json['tanggal_mulai'] ?? json['start_date']);
    return LeaveRequest(
      id: integer(json['id']),
      tanggalMulai: start,
      tanggalSelesai: date(json['tanggal_selesai'] ?? json['end_date'], start),
      alasan: string(json['alasan'] ?? json['reason']),
      status: status,
      alasanPenolakan: switch (string(
        json['alasan_penolakan'] ??
            json['rejection_reason'] ??
            json['catatan_penolakan'],
      )) {
        '' => null,
        final value => value,
      },
      diajukanPada: date(
        json['created_at'] ?? json['diajukan_pada'] ?? json['submitted_at'],
      ),
    );
  }
}
