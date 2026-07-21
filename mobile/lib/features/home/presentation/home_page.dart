import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/router/app_routes.dart';
import '../../../core/theme/app_colors.dart';
import '../../attendance/data/attendance_providers.dart';
import '../../attendance/data/api_location_repository.dart';
import '../../attendance/domain/attendance_record.dart';
import '../../attendance/domain/attendance_session.dart';
import '../../attendance/domain/lokasi_kerja.dart';
import '../../auth/presentation/auth_controller.dart';

/// Lokasi kerja aktif (untuk jadwal jam masuk & toleransi).
final activeLocationProvider = FutureProvider.autoDispose<LokasiKerja?>((
  ref,
) async {
  return ApiLocationRepository().getActiveLocation();
});

/// Riwayat bulan berjalan (untuk ringkasan + 3 terakhir).
class HomePage extends ConsumerStatefulWidget {
  const HomePage({super.key});

  @override
  ConsumerState<HomePage> createState() => _HomePageState();
}

class _HomePageState extends ConsumerState<HomePage> {
  late Timer _clockTimer;
  DateTime _now = DateTime.now();

  @override
  void initState() {
    super.initState();
    _clockTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) setState(() => _now = DateTime.now());
    });
  }

  @override
  void dispose() {
    _clockTimer.cancel();
    super.dispose();
  }

  Future<void> _refresh() async {
    final key = (year: _now.year, month: _now.month);
    ref.invalidate(todayAttendanceProvider);
    ref.invalidate(activeLocationProvider);
    ref.invalidate(monthHistoryProvider(key));
    await Future.wait([
      ref.read(todayAttendanceProvider.future),
      ref.read(activeLocationProvider.future),
      ref.read(monthHistoryProvider(key).future),
    ]);
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(currentUserProvider);
    final today = ref.watch(todayAttendanceProvider);
    final lokasi = ref.watch(activeLocationProvider);
    final history = ref.watch(
      monthHistoryProvider((year: _now.year, month: _now.month)),
    );
    final greeting = _now.hour < 12
        ? 'Pagi'
        : _now.hour < 15
        ? 'Siang'
        : _now.hour < 18
        ? 'Sore'
        : 'Malam';
    final hasEnrolled = user?.hasFaceEnrolled ?? false;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light.copyWith(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
      ),
      child: Scaffold(
        backgroundColor: const Color(0xFFF7F5F2),
        body: RefreshIndicator(
          onRefresh: _refresh,
          child: ListView(
            padding: EdgeInsets.zero,
            children: [
              _header(greeting, user?.nama ?? '-'),
              Transform.translate(
                offset: const Offset(0, -28),
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Column(
                    children: [
                      _clockCard(),
                      const SizedBox(height: 16),
                      today.when(
                        data: (session) => _statusCard(session, hasEnrolled),
                        loading: () => _loadingCard(),
                        error: (error, _) =>
                            _errorCard('Status absensi gagal dimuat', error),
                      ),
                      const SizedBox(height: 16),
                      _absenButton(today.value, hasEnrolled),
                      const SizedBox(height: 16),
                      lokasi.when(
                        data: (l) {
                          final schedule = today.value?.schedule;
                          if (schedule != null) {
                            return _serverScheduleCard(schedule);
                          }
                          return l != null
                              ? _scheduleCard(l)
                              : const SizedBox.shrink();
                        },
                        loading: () => const SizedBox.shrink(),
                        error: (_, _) => const SizedBox.shrink(),
                      ),
                      const SizedBox(height: 16),
                      history.when(
                        data: (list) => _summaryCard(list),
                        loading: () => _loadingCard(),
                        error: (_, _) => _summaryCard(const []),
                      ),
                      const SizedBox(height: 16),
                      history.when(
                        data: (list) => _recentCard(list),
                        loading: () => const SizedBox.shrink(),
                        error: (_, _) => const SizedBox.shrink(),
                      ),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ── Header dengan greeting ──
  Widget _header(String greeting, String nama) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        20,
        MediaQuery.paddingOf(context).top + 20,
        20,
        44,
      ),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF3D2314), Color(0xFF6F4E37)],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(28),
          bottomRight: Radius.circular(28),
        ),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Selamat $greeting,',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.white.withValues(alpha: 0.7),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  nama,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white.withValues(alpha: 0.3)),
            ),
            child: Center(
              child: Text(
                nama.isNotEmpty ? nama[0].toUpperCase() : '?',
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── Kartu jam live ──
  Widget _clockCard() {
    final jam = DateFormat('HH:mm:ss').format(_now);
    final tanggal = DateFormat('EEEE, d MMMM yyyy', 'id_ID').format(_now);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                jam,
                style: const TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary,
                  fontFeatures: [FontFeature.tabularFigures()],
                ),
              ),
              const SizedBox(height: 2),
              Text(
                tanggal,
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(
              Icons.access_time_filled_rounded,
              color: AppColors.primary,
              size: 26,
            ),
          ),
        ],
      ),
    );
  }

  // ── Kartu status hari ini ──
  Widget _statusCard(AttendanceSession session, bool hasEnrolled) {
    final rec = session.record;
    final hasMasuk = rec?.hasMasuk ?? false;
    final hasPulang = rec?.hasPulang ?? false;
    final status = rec?.sessionStatus;
    final (bg, icon, title, sub) = rec?.isIncomplete == true
        ? (
            AppColors.warning,
            Icons.warning_amber_rounded,
            'Absensi Tidak Lengkap',
            'Ajukan koreksi bila lupa absen pulang',
          )
        : !hasMasuk
        ? (
            AppColors.textSecondary,
            Icons.schedule_rounded,
            'Belum Absen',
            session.blockedReason ??
                (hasEnrolled
                    ? 'Silakan absen masuk hari ini'
                    : 'Daftarkan wajah terlebih dahulu'),
          )
        : hasPulang
        ? (
            const Color(0xFF2E7D32),
            Icons.task_alt_rounded,
            'Absensi Selesai',
            'Masuk ${rec!.jamMasukStr} • Pulang ${rec.jamPulangStr}',
          )
        : (
            const Color(0xFF2E7D32),
            Icons.check_circle_rounded,
            status == null ? 'Sudah Masuk' : 'Sesi ${_statusLabel(status)}',
            session.blockedReason ?? 'Jangan lupa absen pulang nanti',
          );

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: bg.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(15),
                ),
                child: Icon(icon, color: bg, size: 28),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: bg,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      sub,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              if (rec?.terlambat ?? false)
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.warning.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Text(
                    'Telat',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: AppColors.warning,
                    ),
                  ),
                ),
            ],
          ),
          if (hasMasuk) ...[
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAF8),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _timeCol(
                    'Masuk',
                    rec!.jamMasukStr,
                    Icons.login_rounded,
                    rec.terlambat,
                  ),
                  Container(width: 1, height: 32, color: AppColors.divider),
                  _timeCol(
                    'Pulang',
                    rec.jamPulangStr,
                    Icons.logout_rounded,
                    false,
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _timeCol(String label, String time, IconData icon, bool late) {
    return Column(
      children: [
        Row(
          children: [
            Icon(icon, size: 14, color: AppColors.textSecondary),
            const SizedBox(width: 4),
            Text(
              label,
              style: const TextStyle(
                fontSize: 11,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        const SizedBox(height: 2),
        Text(
          time,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w700,
            fontFamily: 'monospace',
            color: late ? AppColors.warning : AppColors.textPrimary,
          ),
        ),
      ],
    );
  }

  // ── Tombol Absen besar ──
  Widget _absenButton(AttendanceSession? session, bool hasEnrolled) {
    final rec = session?.record;
    final hasPulang = rec?.hasPulang ?? false;
    final done = session != null && !session.canClockIn && !session.canClockOut;
    final enabled =
        hasEnrolled &&
        session != null &&
        (session.canClockIn || session.canClockOut);

    return GestureDetector(
      onTap: enabled ? () => context.push(AppRoutes.attendance) : null,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 20),
        decoration: BoxDecoration(
          gradient: done
              ? null
              : const LinearGradient(
                  colors: [AppColors.primary, Color(0xFF8B6B4F)],
                ),
          color: done ? Colors.white : null,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
              color: AppColors.primary.withValues(alpha: done ? 0.0 : 0.3),
              blurRadius: 16,
              offset: const Offset(0, 6),
            ),
          ],
          border: done ? Border.all(color: AppColors.divider) : null,
        ),
        child: Row(
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: done
                    ? AppColors.primary.withValues(alpha: 0.1)
                    : Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(
                Icons.fingerprint_rounded,
                color: done ? AppColors.primary : Colors.white,
                size: 28,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    done
                        ? 'Absensi Tidak Tersedia'
                        : (session?.canClockOut == true
                              ? 'Absen Pulang'
                              : 'Absen Masuk Sekarang'),
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: done ? AppColors.textPrimary : Colors.white,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    done
                        ? (session.blockedReason ??
                              (hasPulang
                                  ? 'Sampai jumpa besok!'
                                  : 'Menunggu jadwal server'))
                        : 'Verifikasi wajah + lokasi',
                    style: TextStyle(
                      fontSize: 12,
                      color: done
                          ? AppColors.textSecondary
                          : Colors.white.withValues(alpha: 0.8),
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              Icons.arrow_forward_ios_rounded,
              color: done ? AppColors.textSecondary : Colors.white,
              size: 18,
            ),
          ],
        ),
      ),
    );
  }

  // ── Kartu jadwal kerja ──
  Widget _scheduleCard(LokasiKerja l) {
    final jamMasuk = l.jamMasukStandar.length >= 5
        ? l.jamMasukStandar.substring(0, 5)
        : l.jamMasukStandar;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: AppColors.info.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.schedule_rounded,
              color: AppColors.info,
              size: 24,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Jadwal Kerja',
                  style: TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  'Masuk $jamMasuk  •  Toleransi ${l.toleransiMenit} menit',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _serverScheduleCard(AttendanceSchedule schedule) {
    final title = schedule.name ?? 'Jadwal Kerja';
    final location = schedule.locationName;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: AppColors.info.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.event_available_rounded,
              color: AppColors.info,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                  ),
                ),
                Text(
                  '${schedule.timeRange}${location == null ? '' : ' • $location'}',
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _statusLabel(String value) => value.replaceAll('_', ' ');

  // ── Ringkasan bulan ini ──
  Widget _summaryCard(List<AttendanceRecord> list) {
    final hadir = list.where((r) => r.hadir).length;
    final terlambat = list.where((r) => r.terlambat).length;
    final bulan = DateFormat('MMMM yyyy', 'id_ID').format(_now);
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.bar_chart_rounded,
                size: 18,
                color: AppColors.primary,
              ),
              const SizedBox(width: 8),
              Text(
                'Ringkasan $bulan',
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textPrimary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _stat(
                'Hadir',
                '$hadir',
                Icons.check_circle_rounded,
                AppColors.success,
              ),
              _stat(
                'Terlambat',
                '$terlambat',
                Icons.schedule_rounded,
                AppColors.warning,
              ),
              _stat(
                'Total Hari',
                '${list.length}',
                Icons.calendar_month_rounded,
                AppColors.info,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _stat(String label, String value, IconData icon, Color color) {
    return Expanded(
      child: Column(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(height: 8),
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
    );
  }

  // ── Riwayat 3 terakhir ──
  Widget _recentCard(List<AttendanceRecord> list) {
    if (list.isEmpty) return const SizedBox.shrink();
    final recent = list.take(3).toList();
    final df = DateFormat('E, d MMM', 'id_ID');
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(
                    Icons.history_rounded,
                    size: 18,
                    color: AppColors.primary,
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    'Riwayat Terakhir',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ],
              ),
              GestureDetector(
                onTap: () => context.go(AppRoutes.history),
                child: const Text(
                  'Lihat semua',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ...recent.map(
            (r) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color:
                          (r.terlambat ? AppColors.warning : AppColors.success)
                              .withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(
                      r.terlambat
                          ? Icons.schedule_rounded
                          : Icons.check_circle_rounded,
                      size: 18,
                      color: r.terlambat
                          ? AppColors.warning
                          : AppColors.success,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      df.format(r.tanggal),
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                  Text(
                    '${r.jamMasukStr} - ${r.jamPulangStr}',
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                      fontFamily: 'monospace',
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _loadingCard() {
    return Container(
      height: 90,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
      ),
      child: const Center(child: CircularProgressIndicator()),
    );
  }

  Widget _errorCard(String title, Object error) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        children: [
          const Icon(Icons.cloud_off_rounded, color: AppColors.error),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              '$title. Tarik layar untuk mencoba lagi.',
              style: const TextStyle(color: AppColors.error),
            ),
          ),
        ],
      ),
    );
  }
}
