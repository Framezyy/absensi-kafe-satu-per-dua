import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

import '../../../core/router/app_routes.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/utils/location_helper.dart';
import '../../auth/presentation/auth_controller.dart';
import '../data/api_attendance_repository.dart';
import '../data/api_location_repository.dart';
import '../domain/attendance_record.dart';
import '../domain/lokasi_kerja.dart';

/// Layar Absensi — peta lokasi karyawan + geofence + tombol absen.
///
/// Logic:
/// 1. Ambil lokasi kerja aktif dari API
/// 2. Minta izin GPS + live tracking posisi karyawan
/// 3. Hitung jarak Haversine (client-side untuk UX)
/// 4. Tombol aktif jika: enrolled + dalam radius + status hari ini sesuai
/// 5. Server validasi ulang geofence saat clock-in/out
class AttendancePage extends ConsumerStatefulWidget {
  const AttendancePage({super.key});

  @override
  ConsumerState<AttendancePage> createState() => _AttendancePageState();
}

class _AttendancePageState extends ConsumerState<AttendancePage> {
  final _locationRepo = ApiLocationRepository();
  final _attendanceRepo = ApiAttendanceRepository();
  final _mapController = MapController();

  LokasiKerja? _lokasi;
  AttendanceRecord? _today;
  Position? _currentPos;
  LocationStatus _locStatus = LocationStatus.ok;
  StreamSubscription<Position>? _posSub;

  bool _loading = true;
  String? _error;
  bool _mapReady = false;

  @override
  void initState() {
    super.initState();
    _init();
  }

  @override
  void dispose() {
    _posSub?.cancel();
    super.dispose();
  }

  Future<void> _init() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    // 1. Ambil lokasi kerja aktif.
    final lokasi = await _locationRepo.getActiveLocation();
    if (!mounted) return;
    if (lokasi == null) {
      setState(() {
        _loading = false;
        _error = 'Lokasi kerja belum ditetapkan oleh admin.';
      });
      return;
    }

    // 2. Ambil status absensi hari ini.
    AttendanceRecord? today;
    try {
      today = await _attendanceRepo.getToday();
    } catch (_) {
      today = null;
    }
    if (!mounted) return;

    // 3. Cek izin GPS.
    final status = await LocationHelper.ensurePermission();
    if (!mounted) return;

    setState(() {
      _lokasi = lokasi;
      _today = today;
      _locStatus = status;
      _loading = false;
    });

    if (status == LocationStatus.ok) {
      _startLiveTracking();
    }
  }

  void _startLiveTracking() {
    _posSub?.cancel();
    _posSub = LocationHelper.positionStream().listen((pos) {
      if (!mounted) return;
      setState(() => _currentPos = pos);
      // Auto-center ke posisi karyawan saat pertama kali dapat fix.
      if (_mapReady) {
        _mapController.move(LatLng(pos.latitude, pos.longitude), _mapController.camera.zoom);
      }
    });
  }

  // ── Computed ──
  double? get _distance {
    if (_currentPos == null || _lokasi == null) return null;
    return LocationHelper.haversineMeters(
      _currentPos!.latitude, _currentPos!.longitude,
      _lokasi!.latitude, _lokasi!.longitude,
    );
  }

  bool get _insideRadius {
    final d = _distance;
    if (d == null || _lokasi == null) return false;
    return d <= _lokasi!.radiusMeter;
  }

  bool get _hasEnrolled => ref.read(currentUserProvider)?.hasFaceEnrolled ?? false;
  bool get _hasMasuk => _today?.hasMasuk ?? false;
  bool get _hasPulang => _today?.hasPulang ?? false;
  bool get _gpsAccurate => (_currentPos?.accuracy ?? 999) <= 50;

  Future<void> _goVerify(String action) async {
    // Refresh posisi terbaru sebelum verifikasi.
    final pos = await LocationHelper.getCurrentPosition();
    if (!mounted) return;
    if (pos != null) setState(() => _currentPos = pos);

    if (!_insideRadius) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Anda di luar radius lokasi kerja.')),
      );
      return;
    }
    // Kirim action + koordinat ke halaman verifikasi wajah.
    context.push('${AppRoutes.verify}?action=$action&lat=${pos?.latitude ?? 0}&lng=${pos?.longitude ?? 0}');
  }

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light.copyWith(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
      ),
      child: Scaffold(
        backgroundColor: const Color(0xFFF7F5F2),
        body: Column(
          children: [
            _header(context),
            Expanded(child: _body()),
          ],
        ),
      ),
    );
  }

  Widget _header(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(20, MediaQuery.paddingOf(context).top + 16, 20, 20),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft, end: Alignment.bottomRight,
          colors: [Color(0xFF3D2314), Color(0xFF6F4E37)],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(24), bottomRight: Radius.circular(24),
        ),
      ),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => Navigator.of(context).pop(),
            child: Container(
              width: 40, height: 40,
              decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.arrow_back_rounded, color: Colors.white, size: 20),
            ),
          ),
          const Spacer(),
          const Text('Absensi', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600, color: Colors.white)),
          const Spacer(),
          const SizedBox(width: 40),
        ],
      ),
    );
  }

  Widget _body() {
    if (_loading) return const Center(child: CircularProgressIndicator());

    if (_error != null) {
      return _infoState(Icons.location_off_rounded, _error!, showRetry: true);
    }

    // GPS tidak siap.
    if (_locStatus == LocationStatus.serviceDisabled) {
      return _infoState(
        Icons.gps_off_rounded,
        'GPS tidak aktif. Aktifkan lokasi di pengaturan HP.',
        actionLabel: 'Buka Pengaturan',
        onAction: () async { await LocationHelper.openLocationSettings(); },
      );
    }
    if (_locStatus == LocationStatus.permissionDenied) {
      return _infoState(
        Icons.location_disabled_rounded,
        'Izin lokasi ditolak. Aplikasi butuh akses GPS untuk absensi.',
        actionLabel: 'Minta Izin',
        onAction: _init,
      );
    }
    if (_locStatus == LocationStatus.permissionDeniedForever) {
      return _infoState(
        Icons.location_disabled_rounded,
        'Izin lokasi diblokir permanen. Aktifkan manual di pengaturan aplikasi.',
        actionLabel: 'Buka Pengaturan',
        onAction: () async { await LocationHelper.openAppSettings(); },
      );
    }

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        _mapCard(),
        const SizedBox(height: 16),
        _statusChip(),
        const SizedBox(height: 16),
        _todayCard(),
        const SizedBox(height: 16),
        if (!_hasEnrolled)
          _banner('Anda harus registrasi wajah terlebih dahulu', onTap: () => context.push(AppRoutes.enroll)),
        if (_hasEnrolled && _currentPos != null && !_insideRadius)
          _banner('Anda di luar radius lokasi kerja', color: AppColors.error),
        if (_hasEnrolled && _currentPos != null && _insideRadius && !_gpsAccurate)
          _banner('Akurasi GPS rendah (${_currentPos!.accuracy.toStringAsFixed(0)}m). Coba di area terbuka.', color: AppColors.warning),
        if (_hasEnrolled) ...[
          const SizedBox(height: 8),
          _clockButton(
            label: _hasMasuk ? 'Sudah Absen Masuk' : 'Absen Masuk',
            icon: Icons.login_rounded,
            color: AppColors.success,
            enabled: !_hasMasuk && _insideRadius,
            onTap: () => _goVerify('in'),
          ),
          const SizedBox(height: 12),
          _clockButton(
            label: _hasPulang ? 'Sudah Absen Pulang' : 'Absen Pulang',
            icon: Icons.logout_rounded,
            color: AppColors.primary,
            outlined: true,
            enabled: _hasMasuk && !_hasPulang && _insideRadius,
            onTap: () => _goVerify('out'),
          ),
        ],
      ],
    );
  }

  // ── Peta ──
  Widget _mapCard() {
    final lokasi = _lokasi!;
    final cafe = LatLng(lokasi.latitude, lokasi.longitude);
    final me = _currentPos != null
        ? LatLng(_currentPos!.latitude, _currentPos!.longitude)
        : null;

    return ClipRRect(
      borderRadius: BorderRadius.circular(20),
      child: SizedBox(
        height: 280,
        child: Stack(
          children: [
            FlutterMap(
              mapController: _mapController,
              options: MapOptions(
                initialCenter: me ?? cafe,
                initialZoom: 17,
                minZoom: 5,
                maxZoom: 19,
                onMapReady: () {
                  _mapReady = true;
                  if (me != null) _mapController.move(me, 17);
                },
                interactionOptions: const InteractionOptions(
                  flags: InteractiveFlag.pinchZoom | InteractiveFlag.drag,
                ),
              ),
              children: [
                TileLayer(
                  urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                  userAgentPackageName: 'id.ac.polnep.kafe_satuperdua',
                ),
                // Circle radius geofence.
                CircleLayer(
                  circles: [
                    CircleMarker(
                      point: cafe,
                      radius: lokasi.radiusMeter.toDouble(),
                      useRadiusInMeter: true,
                      color: AppColors.primary.withValues(alpha: 0.12),
                      borderColor: AppColors.primary.withValues(alpha: 0.5),
                      borderStrokeWidth: 2,
                    ),
                  ],
                ),
                MarkerLayer(
                  markers: [
                    // Marker kafe (lokasi kerja).
                    Marker(
                      point: cafe,
                      width: 44, height: 44,
                      child: const Icon(Icons.storefront_rounded, color: AppColors.primary, size: 40),
                    ),
                    // Marker karyawan (live).
                    if (me != null)
                      Marker(
                        point: me,
                        width: 30, height: 30,
                        child: Container(
                          decoration: BoxDecoration(
                            color: Colors.blue,
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white, width: 3),
                            boxShadow: [BoxShadow(color: Colors.blue.withValues(alpha: 0.4), blurRadius: 8)],
                          ),
                        ),
                      ),
                  ],
                ),
              ],
            ),
            // Tombol re-center ke posisi karyawan.
            Positioned(
              bottom: 12, right: 12,
              child: GestureDetector(
                onTap: () {
                  if (me != null) _mapController.move(me, 17);
                },
                child: Container(
                  width: 40, height: 40,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.15), blurRadius: 6)],
                  ),
                  child: const Icon(Icons.my_location_rounded, color: Colors.blue, size: 22),
                ),
              ),
            ),
            // Loading GPS pertama.
            if (me == null)
              Positioned(
                top: 12, left: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10)),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2)),
                      SizedBox(width: 8),
                      Text('Mencari lokasi...', style: TextStyle(fontSize: 12)),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  // ── Status jarak chip ──
  Widget _statusChip() {
    final d = _distance;
    final inside = _insideRadius;
    final color = _currentPos == null
        ? AppColors.textSecondary
        : (inside ? AppColors.success : AppColors.error);
    final text = _currentPos == null
        ? 'Menunggu lokasi GPS...'
        : (inside
            ? 'Dalam radius (${d!.toStringAsFixed(0)} m dari kafe)'
            : 'Luar radius (${d!.toStringAsFixed(0)} m dari kafe)');
    final icon = _currentPos == null
        ? Icons.gps_not_fixed_rounded
        : (inside ? Icons.check_circle_rounded : Icons.location_off_rounded);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(child: Text(text, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: color))),
        ],
      ),
    );
  }

  // ── Kartu status hari ini ──
  Widget _todayCard() {
    final noRecord = _today == null || !_hasMasuk;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Row(
        children: [
          Container(
            width: 48, height: 48,
            decoration: BoxDecoration(
              gradient: noRecord ? null : const LinearGradient(colors: [Color(0xFF4CAF50), Color(0xFF66BB6A)]),
              color: noRecord ? AppColors.surfaceVariant : null,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(noRecord ? Icons.schedule_rounded : Icons.check_circle_rounded, color: noRecord ? AppColors.textSecondary : Colors.white, size: 26),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  noRecord ? 'Belum Absen' : (_hasPulang ? 'Sudah Absen Pulang' : 'Sudah Absen Masuk'),
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: noRecord ? AppColors.textSecondary : const Color(0xFF2E7D32)),
                ),
                if (_today != null && _hasMasuk)
                  Text('Masuk ${_today!.jamMasukStr}  •  Pulang ${_today!.jamPulangStr}', style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _clockButton({
    required String label,
    required IconData icon,
    required Color color,
    required bool enabled,
    required VoidCallback onTap,
    bool outlined = false,
  }) {
    return SizedBox(
      height: 54,
      child: outlined
          ? OutlinedButton.icon(
              onPressed: enabled ? onTap : null,
              style: OutlinedButton.styleFrom(
                foregroundColor: color,
                side: BorderSide(color: color.withValues(alpha: 0.4)),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              icon: Icon(icon),
              label: Text(label, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600)),
            )
          : FilledButton.icon(
              onPressed: enabled ? onTap : null,
              style: FilledButton.styleFrom(
                backgroundColor: color,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              icon: Icon(icon),
              label: Text(label, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600)),
            ),
    );
  }

  Widget _banner(String msg, {Color color = AppColors.error, VoidCallback? onTap}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          Icon(Icons.warning_amber_rounded, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(child: Text(msg, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w500, color: color))),
          if (onTap != null) TextButton(onPressed: onTap, child: const Text('Daftar')),
        ],
      ),
    );
  }

  Widget _infoState(IconData icon, String msg, {String? actionLabel, VoidCallback? onAction, bool showRetry = false}) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 64, color: AppColors.textSecondary.withValues(alpha: 0.5)),
            const SizedBox(height: 16),
            Text(msg, textAlign: TextAlign.center, style: const TextStyle(fontSize: 14, color: AppColors.textSecondary)),
            const SizedBox(height: 20),
            if (actionLabel != null)
              FilledButton(onPressed: onAction, child: Text(actionLabel))
            else if (showRetry)
              FilledButton.icon(onPressed: _init, icon: const Icon(Icons.refresh), label: const Text('Coba Lagi')),
          ],
        ),
      ),
    );
  }
}