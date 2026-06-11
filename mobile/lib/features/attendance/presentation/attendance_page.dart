import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/router/app_routes.dart';
import '../../../core/theme/app_colors.dart';
import '../../attendance/domain/attendance_record.dart';
import '../../auth/presentation/auth_controller.dart';

/// Layar Tab Absensi — Wireframe 3.6.
///
/// Phase 1: placeholder peta + status card + tombol masuk/pulang.
/// Peta Google Maps + geofence circle overlay akan diimplementasi di
/// Phase 4 saat integrasi backend (atau Phase 1.3 jika diminta lebih awal).
class AttendancePage extends ConsumerStatefulWidget {
  const AttendancePage({super.key});

  @override
  ConsumerState<AttendancePage> createState() => _AttendancePageState();
}

class _AttendancePageState extends ConsumerState<AttendancePage> {
  // Mock state.
  AttendanceRecord? _today;
  bool _loading = true;
  double _distanceToLocation = 42.5; // meter (mock: dalam radius 50m)

  @override
  void initState() {
    super.initState();
    _loadToday();
  }

  Future<void> _loadToday() async {
    await Future<void>.delayed(const Duration(milliseconds: 200));
    if (!mounted) return;
    final user = ref.read(currentUserProvider);
    final hasEnrolled = user?.hasFaceEnrolled ?? false;
    final now = DateTime.now();

    setState(() {
      // Mock: karyawan2 (sudah enroll) = sudah masuk, karyawan1 = belum.
      _today = hasEnrolled
          ? AttendanceRecord(
              tanggal: now,
              jamMasuk: DateTime(now.year, now.month, now.day, 8, 3),
              terlambat: true,
              lokasiNama: 'Kafe Satu Per Dua Kopitiam',
              faceSimilarity: 0.87,
            )
          : null;
      _loading = false;
    });
  }

  bool get _insideRadius => _distanceToLocation <= AppConstants.defaultGeofenceRadiusMeter;
  bool get _hasEnrolled => ref.read(currentUserProvider)?.hasFaceEnrolled ?? false;
  bool get _hasMasuk => _today?.hasMasuk ?? false;
  bool get _hasPulang => _today?.hasPulang ?? false;

  void _toggleMockLocation() {
    setState(() {
      _distanceToLocation = _insideRadius ? 120.0 : 42.5;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Absensi')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Peta placeholder
                _MapPlaceholder(
                  insideRadius: _insideRadius,
                  distance: _distanceToLocation,
                  onToggleMock: _toggleMockLocation,
                ),
                const SizedBox(height: 16),

                // Status card hari ini
                _TodayStatusCard(record: _today),
                const SizedBox(height: 16),

                // Gating banner
                if (!_hasEnrolled)
                  _GateBanner(
                    message: 'Anda harus registrasi wajah terlebih dahulu',
                    onTap: () => context.push(AppRoutes.enroll),
                  ),

                if (!_insideRadius && _hasEnrolled)
                  _GateBanner(
                    message:
                        'Anda di luar radius lokasi (${_distanceToLocation.toStringAsFixed(0)} m)',
                    onTap: null, // tidak ada aksi, hanya info
                  ),

                if (_hasEnrolled) ...[
                  const SizedBox(height: 8),
                  // Tombol Masuk
                  FilledButton.icon(
                    onPressed: (_hasMasuk || !_insideRadius)
                        ? null
                        : () => context.push(AppRoutes.verify),
                    icon: const Icon(Icons.login),
                    label: Text(_hasMasuk ? 'Sudah Masuk' : 'Absen Masuk'),
                  ),
                  const SizedBox(height: 10),
                  // Tombol Pulang
                  OutlinedButton.icon(
                    onPressed: (!_hasMasuk || _hasPulang || !_insideRadius)
                        ? null
                        : () => context.push(AppRoutes.verify),
                    icon: const Icon(Icons.logout),
                    label: Text(_hasPulang ? 'Sudah Pulang' : 'Absen Pulang'),
                  ),
                ],
              ],
            ),
    );
  }
}

/// Placeholder peta dengan info radius dan mock toggle.
class _MapPlaceholder extends StatelessWidget {
  const _MapPlaceholder({
    required this.insideRadius,
    required this.distance,
    required this.onToggleMock,
  });
  final bool insideRadius;
  final double distance;
  final VoidCallback onToggleMock;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: Container(
        height: 180,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              AppColors.info.withValues(alpha: 0.08),
              AppColors.surface,
            ],
          ),
        ),
        child: Stack(
          children: [
            Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.map,
                    size: 56,
                    color: AppColors.info.withValues(alpha: 0.3),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Peta & Geofence',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  Text(
                    'Fase 4: peta interaktif + circle radius',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ),
            Positioned(
              bottom: 8,
              left: 8,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: insideRadius
                      ? AppColors.success.withValues(alpha: 0.1)
                      : AppColors.error.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(
                    color: insideRadius
                        ? AppColors.success.withValues(alpha: 0.3)
                        : AppColors.error.withValues(alpha: 0.3),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      insideRadius
                          ? Icons.location_on
                          : Icons.location_off,
                      size: 16,
                      color: insideRadius
                          ? AppColors.success
                          : AppColors.error,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      '${distance.toStringAsFixed(0)} m · ${insideRadius ? 'Dalam radius' : 'Luar radius'}',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: insideRadius
                            ? AppColors.success
                            : AppColors.error,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Positioned(
              bottom: 8,
              right: 8,
              child: GestureDetector(
                onTap: onToggleMock,
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.textSecondary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text(
                    'Toggle mock',
                    style: TextStyle(fontSize: 10, color: AppColors.textSecondary),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Kartu status absensi hari ini.
class _TodayStatusCard extends StatelessWidget {
  const _TodayStatusCard({required this.record});
  final AttendanceRecord? record;

  @override
  Widget build(BuildContext context) {
    final noRecord = record == null;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: noRecord
                    ? AppColors.border
                    : AppColors.success.withValues(alpha: 0.12),
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
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          color: noRecord
                              ? AppColors.textSecondary
                              : AppColors.success,
                        ),
                  ),
                  if (record != null)
                    Text(
                      'Masuk ${record!.jamMasukStr}  •  Pulang ${record!.jamPulangStr}',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Banner gating: belum enroll atau di luar radius.
class _GateBanner extends StatelessWidget {
  const _GateBanner({required this.message, this.onTap});
  final String message;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.error.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.error.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          const Icon(Icons.warning_amber, color: AppColors.error, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: AppColors.error,
              ),
            ),
          ),
          if (onTap != null)
            TextButton(
              onPressed: onTap,
              child: const Text('Daftar'),
            ),
        ],
      ),
    );
  }
}
