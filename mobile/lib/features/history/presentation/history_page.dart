import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../attendance/data/api_attendance_repository.dart';
import '../../attendance/domain/attendance_record.dart';

/// Layar Riwayat Kehadiran — Wireframe 3.7.
///
/// Mengambil data dari API Laravel: `GET /attendance/history?month=YYYY-MM`.
class HistoryPage extends ConsumerStatefulWidget {
  const HistoryPage({super.key});

  @override
  ConsumerState<HistoryPage> createState() => _HistoryPageState();
}

class _HistoryPageState extends ConsumerState<HistoryPage> {
  late DateTime _selectedMonth;
  List<AttendanceRecord> _records = [];
  bool _loading = true;
  final _repo = ApiAttendanceRepository();

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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal memuat riwayat: $e')),
      );
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

    return Scaffold(
      appBar: AppBar(title: const Text('Riwayat Kehadiran')),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: AppColors.surface,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                IconButton(icon: const Icon(Icons.chevron_left), onPressed: _prevMonth),
                Text(df.format(_selectedMonth), style: Theme.of(context).textTheme.titleLarge),
                IconButton(icon: const Icon(Icons.chevron_right), onPressed: isCurrentMonth ? null : _nextMonth),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            child: Row(
              children: [
                _SummaryChip(label: 'Hadir', value: '$hadir', color: AppColors.success),
                const SizedBox(width: 8),
                _SummaryChip(label: 'Terlambat', value: '$terlambat', color: AppColors.warning),
                const SizedBox(width: 8),
                _SummaryChip(label: 'Total Hari', value: '${_records.length}', color: AppColors.info),
              ],
            ),
          ),
          const Divider(height: 1),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _records.isEmpty
                    ? const EmptyState(icon: Icons.calendar_month, message: 'Belum ada data kehadiran bulan ini')
                    : ListView.separated(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        itemCount: _records.length,
                        separatorBuilder: (_, _) => const Divider(height: 1),
                        itemBuilder: (context, i) => _HistoryTile(record: _records[i]),
                      ),
          ),
        ],
      ),
    );
  }
}

class _SummaryChip extends StatelessWidget {
  const _SummaryChip({required this.label, required this.value, required this.color});
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(color: color.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(10)),
        child: Column(
          children: [
            Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700, color: color)),
            Text(label, style: TextStyle(fontSize: 11, color: color)),
          ],
        ),
      ),
    );
  }
}

class _HistoryTile extends StatelessWidget {
  const _HistoryTile({required this.record});
  final AttendanceRecord record;

  @override
  Widget build(BuildContext context) {
    final df = DateFormat('E, d MMM', 'id_ID');
    return ListTile(
      leading: Container(
        width: 40, height: 40,
        decoration: BoxDecoration(
          color: record.terlambat ? AppColors.warning.withValues(alpha: 0.12) : AppColors.success.withValues(alpha: 0.12),
          shape: BoxShape.circle,
        ),
        child: Icon(record.terlambat ? Icons.schedule : Icons.check_circle_outline, color: record.terlambat ? AppColors.warning : AppColors.success, size: 22),
      ),
      title: Text(df.format(record.tanggal)),
      subtitle: Text('Masuk ${record.jamMasukStr}  •  Pulang ${record.jamPulangStr}', style: Theme.of(context).textTheme.bodySmall),
      trailing: record.terlambat
          ? Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(color: AppColors.warning.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(6)),
              child: const Text('Terlambat', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.warning)),
            )
          : null,
    );
  }
}
