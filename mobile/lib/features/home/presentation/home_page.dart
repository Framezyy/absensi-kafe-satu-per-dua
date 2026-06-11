import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/router/app_routes.dart';
import '../../../core/theme/app_colors.dart';
import '../../attendance/data/api_attendance_repository.dart';
import '../../attendance/domain/attendance_record.dart';
import '../../auth/presentation/auth_controller.dart';

/// Provider untuk absensi hari ini (dari API).
final todayAttendanceProvider = FutureProvider<AttendanceRecord?>((ref) async {
  final user = ref.watch(currentUserProvider);
  if (user == null || !user.hasFaceEnrolled) return null;
  final repo = ApiAttendanceRepository();
  return repo.getToday();
});

/// Layar Beranda — Wireframe 3.5.
class HomePage extends ConsumerWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    final todayAsync = ref.watch(todayAttendanceProvider);
    final df = DateFormat('EEEE, d MMMM yyyy', 'id_ID');
    final now = DateTime.now();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Beranda'),
        actions: [
          IconButton(
            icon: const Icon(Icons.person_outline),
            onPressed: () => context.push(AppRoutes.profile),
            tooltip: 'Profil',
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          Text(
            'Selamat ${now.hour < 12 ? 'Pagi' : now.hour < 17 ? 'Sore' : 'Malam'}',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppColors.textSecondary,
                ),
          ),
          const SizedBox(height: 2),
          Text(user?.nama ?? '-', style: Theme.of(context).textTheme.headlineMedium),
          Text(df.format(now), style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: 16),
          todayAsync.when(
            data: (record) => _AttendanceCard(
              record: record,
              hasEnrolled: user?.hasFaceEnrolled ?? false,
            ),
            loading: () => const Card(
              child: Padding(
                padding: EdgeInsets.all(20),
                child: Center(child: CircularProgressIndicator()),
              ),
            ),
            error: (e, _) => _AttendanceCard(record: null, hasEnrolled: user?.hasFaceEnrolled ?? false),
          ),
          const SizedBox(height: 20),
          Text('Menu Cepat', style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 10),
          _QuickGrid(hasEnrolled: user?.hasFaceEnrolled ?? false),
        ],
      ),
    );
  }
}

class _AttendanceCard extends StatelessWidget {
  const _AttendanceCard({required this.record, required this.hasEnrolled});
  final AttendanceRecord? record;
  final bool hasEnrolled;

  @override
  Widget build(BuildContext context) {
    final noRecord = record == null;
    return Card(
      elevation: 2,
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: noRecord
                ? [AppColors.surfaceVariant, AppColors.surface]
                : [AppColors.primary.withValues(alpha: 0.06), AppColors.surface],
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 44, height: 44,
                  decoration: BoxDecoration(
                    color: noRecord ? AppColors.border : AppColors.success.withValues(alpha: 0.12),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    noRecord ? Icons.access_time : Icons.check_circle,
                    color: noRecord ? AppColors.textSecondary : AppColors.success,
                    size: 26,
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        noRecord ? 'Belum Absen' : 'Sudah Masuk',
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              color: noRecord ? AppColors.textSecondary : AppColors.success,
                            ),
                      ),
                      if (noRecord && !hasEnrolled)
                        const Text('Daftarkan wajah terlebih dahulu',
                            style: TextStyle(fontSize: 12, color: AppColors.error)),
                    ],
                  ),
                ),
              ],
            ),
            if (record != null) ...[
              const SizedBox(height: 16),
              const Divider(height: 1),
              const SizedBox(height: 14),
              Row(
                children: [
                  _TimeDisplay(label: 'Jam Masuk', time: record!.jamMasukStr, isLate: record!.terlambat),
                  const SizedBox(width: 24),
                  _TimeDisplay(label: 'Jam Pulang', time: record!.jamPulangStr),
                  const Spacer(),
                  if (record!.terlambat)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.warning.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text('Terlambat', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.warning)),
                    ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _TimeDisplay extends StatelessWidget {
  const _TimeDisplay({required this.label, required this.time, this.isLate = false});
  final String label;
  final String time;
  final bool isLate;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: Theme.of(context).textTheme.bodySmall),
        const SizedBox(height: 2),
        Text(time, style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700, fontFamily: 'monospace', color: isLate ? AppColors.warning : AppColors.textPrimary)),
      ],
    );
  }
}

class _QuickGrid extends StatelessWidget {
  const _QuickGrid({required this.hasEnrolled});
  final bool hasEnrolled;

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 4, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 10, crossAxisSpacing: 10, childAspectRatio: 0.85,
      children: [
        _QuickButton(icon: Icons.fingerprint, label: 'Absen', color: hasEnrolled ? AppColors.primary : AppColors.border, onTap: () => context.push(AppRoutes.attendance)),
        _QuickButton(icon: Icons.history, label: 'Riwayat', color: AppColors.info, onTap: () => context.push(AppRoutes.history)),
        _QuickButton(icon: Icons.event_note, label: 'Izin', color: AppColors.warning, onTap: () => context.push(AppRoutes.leave)),
        _QuickButton(icon: Icons.person, label: 'Profil', color: AppColors.textSecondary, onTap: () => context.push(AppRoutes.profile)),
      ],
    );
  }
}

class _QuickButton extends StatelessWidget {
  const _QuickButton({required this.icon, required this.label, required this.color, required this.onTap});
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(width: 48, height: 48, decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(14)), child: Icon(icon, color: color, size: 26)),
          const SizedBox(height: 6),
          Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: color)),
        ],
      ),
    );
  }
}
