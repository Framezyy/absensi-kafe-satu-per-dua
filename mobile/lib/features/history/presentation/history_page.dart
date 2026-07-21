import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../attendance/data/attendance_providers.dart';
import '../../attendance/domain/attendance_record.dart';

/// Layar Riwayat Kehadiran — Wireframe 3.7.
class HistoryPage extends ConsumerStatefulWidget {
  const HistoryPage({super.key});

  @override
  ConsumerState<HistoryPage> createState() => _HistoryPageState();
}

class _HistoryPageState extends ConsumerState<HistoryPage> {
  late DateTime _selectedMonth;
  List<AttendanceRecord> _records = [];
  bool _loading = true;
  late final _repo = ref.read(attendanceRepositoryProvider);

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _selectedMonth = DateTime(now.year, now.month);
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _loading = true);
    try {
      final data = await _repo.getHistory(
        year: _selectedMonth.year,
        month: _selectedMonth.month,
      );
      if (!mounted) return;
      setState(() {
        _records = data;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Gagal memuat riwayat: $e')));
    }
  }

  void _prevMonth() {
    setState(() {
      _selectedMonth = DateTime(_selectedMonth.year, _selectedMonth.month - 1);
    });
    _loadData();
  }

  void _nextMonth() {
    final now = DateTime.now();
    final next = DateTime(_selectedMonth.year, _selectedMonth.month + 1);
    if (next.isAfter(DateTime(now.year, now.month))) return;
    setState(() => _selectedMonth = next);
    _loadData();
  }

  @override
  Widget build(BuildContext context) {
    final df = DateFormat('MMMM yyyy', 'id_ID');
    final hadir = _records.where((r) => r.hadir).length;
    final terlambat = _records.where((r) => r.terlambat).length;
    final now = DateTime.now();
    final isCurrentMonth =
        _selectedMonth.year == now.year && _selectedMonth.month == now.month;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light.copyWith(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
      ),
      child: Scaffold(
        backgroundColor: const Color(0xFFF7F5F2),
        body: Column(
          children: [
            // Header gradient
            Container(
              padding: EdgeInsets.fromLTRB(
                20,
                MediaQuery.paddingOf(context).top + 16,
                20,
                20,
              ),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFF3D2314), Color(0xFF6F4E37)],
                ),
                borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(24),
                  bottomRight: Radius.circular(24),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Riwayat Kehadiran',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 18),
                  // Month picker
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        IconButton(
                          icon: const Icon(
                            Icons.chevron_left_rounded,
                            color: Colors.white,
                          ),
                          onPressed: _prevMonth,
                        ),
                        Text(
                          df.format(_selectedMonth),
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                        IconButton(
                          icon: Icon(
                            Icons.chevron_right_rounded,
                            color: isCurrentMonth
                                ? Colors.white30
                                : Colors.white,
                          ),
                          onPressed: isCurrentMonth ? null : _nextMonth,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            // Summary cards
            Transform.translate(
              offset: const Offset(0, -12),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Row(
                  children: [
                    _SummaryCard(
                      label: 'Hadir',
                      value: '$hadir',
                      icon: Icons.check_circle_rounded,
                      color: AppColors.success,
                    ),
                    const SizedBox(width: 10),
                    _SummaryCard(
                      label: 'Terlambat',
                      value: '$terlambat',
                      icon: Icons.schedule_rounded,
                      color: AppColors.warning,
                    ),
                    const SizedBox(width: 10),
                    _SummaryCard(
                      label: 'Total',
                      value: '${_records.length}',
                      icon: Icons.calendar_month_rounded,
                      color: AppColors.info,
                    ),
                  ],
                ),
              ),
            ),
            // List
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _records.isEmpty
                  ? const EmptyState(
                      icon: Icons.calendar_month,
                      message: 'Belum ada data kehadiran bulan ini',
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                      itemCount: _records.length,
                      itemBuilder: (context, i) => _HistoryTile(
                        record: _records[i],
                        onCorrection:
                            _records[i].isIncomplete && _records[i].id != null
                            ? () => _showCorrectionDialog(_records[i])
                            : null,
                      ),
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showCorrectionDialog(AttendanceRecord record) async {
    var selected =
        record.jamMasuk?.add(const Duration(hours: 8)) ??
        record.tanggal.add(const Duration(hours: 17));
    final reasonController = TextEditingController();
    final submitted = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Koreksi Absen Pulang'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Pilih tanggal dan waktu pulang. Tanggal berikutnya dapat digunakan untuk shift malam.',
                ),
                const SizedBox(height: 12),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.event_rounded),
                  title: Text(
                    DateFormat('d MMMM yyyy, HH:mm', 'id_ID').format(selected),
                  ),
                  onTap: () async {
                    final date = await showDatePicker(
                      context: context,
                      initialDate: selected,
                      firstDate: record.tanggal,
                      lastDate: record.tanggal.add(const Duration(days: 1)),
                    );
                    if (date == null || !context.mounted) return;
                    final time = await showTimePicker(
                      context: context,
                      initialTime: TimeOfDay.fromDateTime(selected),
                    );
                    if (time == null) return;
                    setDialogState(() {
                      selected = DateTime(
                        date.year,
                        date.month,
                        date.day,
                        time.hour,
                        time.minute,
                      );
                    });
                  },
                ),
                TextField(
                  controller: reasonController,
                  minLines: 2,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    labelText: 'Alasan',
                    hintText: 'Contoh: lupa absen pulang',
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: () async {
                final reason = reasonController.text.trim();
                if (reason.isEmpty ||
                    !selected.isAfter(record.jamMasuk ?? record.tanggal)) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text(
                        'Waktu harus setelah jam masuk dan alasan wajib diisi.',
                      ),
                    ),
                  );
                  return;
                }
                try {
                  await _repo.submitCorrection(
                    attendanceId: record.id!,
                    clockOutAt: selected,
                    reason: reason,
                  );
                  if (dialogContext.mounted) Navigator.pop(dialogContext, true);
                } catch (error) {
                  if (!context.mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('Gagal mengirim koreksi: $error')),
                  );
                }
              },
              child: const Text('Kirim'),
            ),
          ],
        ),
      ),
    );
    reasonController.dispose();
    if (submitted == true && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pengajuan koreksi berhasil dikirim.')),
      );
      await _loadData();
    }
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });
  final String label;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.06),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 22),
            const SizedBox(height: 6),
            Text(
              value,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: color,
              ),
            ),
            Text(
              label,
              style: const TextStyle(
                fontSize: 11,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _HistoryTile extends StatelessWidget {
  const _HistoryTile({required this.record, this.onCorrection});
  final AttendanceRecord record;
  final VoidCallback? onCorrection;

  @override
  Widget build(BuildContext context) {
    final df = DateFormat('EEEE, d MMM', 'id_ID');
    final statusColor = record.isIncomplete
        ? AppColors.error
        : record.isCorrected
        ? AppColors.info
        : record.terlambat
        ? AppColors.warning
        : AppColors.success;
    final statusLabel = record.isIncomplete
        ? 'Tidak lengkap'
        : record.isCorrected
        ? 'Dikoreksi'
        : record.terlambat
        ? 'Telat'
        : null;
    final duration = record.workedMinutes == null
        ? null
        : '${record.workedMinutes! ~/ 60}j ${record.workedMinutes! % 60}m';
    final salary = record.estimatedSalary == null
        ? null
        : NumberFormat.currency(
            locale: 'id_ID',
            symbol: 'Rp',
            decimalDigits: 0,
          ).format(record.estimatedSalary);
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  record.isIncomplete
                      ? Icons.warning_amber_rounded
                      : record.isCorrected
                      ? Icons.edit_note_rounded
                      : record.terlambat
                      ? Icons.schedule_rounded
                      : Icons.check_circle_rounded,
                  color: statusColor,
                  size: 24,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      df.format(record.tanggal),
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(
                          Icons.login_rounded,
                          size: 13,
                          color: AppColors.textSecondary,
                        ),
                        const SizedBox(width: 3),
                        Text(
                          record.jamMasukStr,
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                            fontFamily: 'monospace',
                          ),
                        ),
                        const SizedBox(width: 12),
                        Icon(
                          Icons.logout_rounded,
                          size: 13,
                          color: AppColors.textSecondary,
                        ),
                        const SizedBox(width: 3),
                        Text(
                          record.jamPulangStr,
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                            fontFamily: 'monospace',
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              if (statusLabel != null)
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    statusLabel,
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: statusColor,
                    ),
                  ),
                ),
            ],
          ),
          if (duration != null || salary != null || onCorrection != null) ...[
            const SizedBox(height: 10),
            Row(
              children: [
                if (duration != null)
                  Text(
                    'Durasi $duration',
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                  ),
                if (duration != null && salary != null)
                  const Text(
                    ' • ',
                    style: TextStyle(color: AppColors.textSecondary),
                  ),
                if (salary != null)
                  Expanded(
                    child: Text(
                      salary,
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                if (onCorrection != null)
                  TextButton.icon(
                    onPressed: onCorrection,
                    icon: const Icon(Icons.edit_calendar_rounded, size: 16),
                    label: const Text('Ajukan koreksi'),
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
