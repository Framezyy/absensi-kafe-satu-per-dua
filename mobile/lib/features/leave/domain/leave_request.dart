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
    final statusStr = json['status'] as String? ?? 'pending';
    final status = LeaveStatus.values.firstWhere(
      (e) => e.name == statusStr,
      orElse: () => LeaveStatus.pending,
    );
    return LeaveRequest(
      id: json['id'] as int,
      tanggalMulai: DateTime.parse(json['tanggal_mulai'] as String),
      tanggalSelesai: DateTime.parse(
          (json['tanggal_selesai'] ?? json['tanggal_mulai']) as String),
      alasan: json['alasan'] as String,
      status: status,
      diajukanPada: DateTime.parse(json['created_at'] as String),
    );
  }
}
