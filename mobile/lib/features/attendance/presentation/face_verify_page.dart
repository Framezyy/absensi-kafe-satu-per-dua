import 'dart:async';
import 'dart:math';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/router/app_routes.dart';
import '../../../core/theme/app_colors.dart';
import '../../face_enroll/data/api_face_repository.dart';
import '../../face_enroll/data/camera_image_converter.dart';
import '../data/api_attendance_repository.dart';

/// Aksi liveness yang diminta saat verifikasi absensi.
enum VerifyAction {
  blink('Kedipkan mata Anda', Icons.visibility),
  leftYaw('Tolehkan kepala ke KIRI', Icons.arrow_back),
  rightYaw('Tolehkan kepala ke KANAN', Icons.arrow_forward);

  const VerifyAction(this.instruction, this.icon);
  final String instruction;
  final IconData icon;
}

/// Layar verifikasi wajah real-time saat absensi harian
/// — Plan §6.5.
///
/// Random 1 aksi dari [VerifyAction] → auto-capture → kirim ke
/// FastAPI → sukses/gagal.
class FaceVerifyPage extends ConsumerStatefulWidget {
  const FaceVerifyPage({
    super.key,
    this.action = 'in',
    this.latitude = 0,
    this.longitude = 0,
    this.isMocked = false,
  });

  /// Aksi absensi: 'in' (masuk) atau 'out' (pulang).
  final String action;

  /// Koordinat GPS yang sudah diambil di halaman absensi.
  final double latitude;
  final double longitude;

  /// Flag lokasi palsu (Fake GPS) terdeteksi di halaman absensi.
  final bool isMocked;

  @override
  ConsumerState<FaceVerifyPage> createState() => _FaceVerifyPageState();
}

enum _VerifyPhase {
  preparing,
  detecting,
  sending,
  success,
  failed,
}

class _FaceVerifyPageState extends ConsumerState<FaceVerifyPage>
    with SingleTickerProviderStateMixin {
  CameraController? _cameraCtrl;
  late final FaceDetector _faceDetector;
  bool _isProcessing = false;
  late int _sensorOrientation;

  // State.
  late VerifyAction _action;
  _VerifyPhase _phase = _VerifyPhase.preparing;
  bool _blinkSeenClosed = false;
  DateTime? _lastBlinkCapture; // cooldown anti double-trigger.
  DateTime? _poseStableStart;
  Timer? _timeoutTimer;
  DateTime? _clockTime; // jam server (mock: DateTime.now()).
  String? _failMessage;
  bool _initializing = true;
  String? _initError;

  late final AnimationController _pulseAnim;

  static final _rng = Random.secure();

  @override
  void initState() {
    super.initState();
    _pulseAnim = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 1),
    )..repeat(reverse: true);
    _faceDetector = FaceDetector(
      options: FaceDetectorOptions(
        enableClassification: true,
        enableLandmarks: false,
        performanceMode: FaceDetectorMode.accurate,
      ),
    );
    // Pilih 1 aksi random sesuai plan §6.5.
    _action = VerifyAction.values[_rng.nextInt(VerifyAction.values.length)];
    _initCamera();
  }

  Future<void> _initCamera() async {
    try {
      final cameras = await availableCameras();
      final front = cameras.firstWhere(
        (c) => c.lensDirection == CameraLensDirection.front,
        orElse: () => cameras.first,
      );
      _sensorOrientation = front.sensorOrientation;

      final ctrl = CameraController(
        front,
        ResolutionPreset.medium,
        enableAudio: false,
        imageFormatGroup: ImageFormatGroup.nv21,
      );
      await ctrl.initialize();
      if (!mounted) {
        await ctrl.dispose();
        return;
      }

      setState(() {
        _cameraCtrl = ctrl;
        _initializing = false;
        _phase = _VerifyPhase.detecting;
      });

      _cameraCtrl!.startImageStream((image) {
        if (_isProcessing || _phase != _VerifyPhase.detecting) return;
        _isProcessing = true;
        _processFrame(image).whenComplete(() => _isProcessing = false);
      });

      _timeoutTimer = Timer(AppConstants.livenessTimeout, _onTimeout);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _initializing = false;
        _initError = 'Gagal menginisialisasi kamera: $e';
      });
    }
  }

  Future<void> _processFrame(CameraImage image) async {
    final inputImage =
        cameraImageToInputImage(image, _cameraCtrl!.description, _sensorOrientation);
    if (inputImage == null) return;

    final faces = await _faceDetector.processImage(inputImage);
    if (!mounted || faces.isEmpty) return;

    final face = faces.first;
    switch (_action) {
      case VerifyAction.blink:
        _detectBlink(face);
      case VerifyAction.leftYaw:
        _detectYaw(face, isLeft: true);
      case VerifyAction.rightYaw:
        _detectYaw(face, isLeft: false);
    }
  }

  void _detectBlink(Face face) {
    // Cooldown: cegah trigger berulang dalam 500ms.
    if (_lastBlinkCapture != null &&
        DateTime.now().difference(_lastBlinkCapture!) < AppConstants.blinkCooldown) {
      return;
    }

    final leftOpen = face.leftEyeOpenProbability;
    final rightOpen = face.rightEyeOpenProbability;

    // Ambil probabilitas mata yang PALING tertutup (min) — lebih sensitif.
    double? eyeProb;
    if (leftOpen != null && rightOpen != null) {
      eyeProb = leftOpen < rightOpen ? leftOpen : rightOpen;
    } else if (leftOpen != null) {
      eyeProb = leftOpen;
    } else if (rightOpen != null) {
      eyeProb = rightOpen;
    } else {
      return;
    }

    if (eyeProb < AppConstants.eyeClosedThreshold) _blinkSeenClosed = true;
    if (_blinkSeenClosed && eyeProb > AppConstants.eyeOpenThreshold) {
      _blinkSeenClosed = false;
      _lastBlinkCapture = DateTime.now();
      _onActionDetected();
    }
  }

  void _detectYaw(Face face, {required bool isLeft}) {
    final yaw = face.headEulerAngleY ?? 0.0;
    final met = isLeft
        ? yaw < -AppConstants.yawTriggerAngle
        : yaw > AppConstants.yawTriggerAngle;

    if (met) {
      _poseStableStart ??= DateTime.now();
      if (DateTime.now().difference(_poseStableStart!) >= AppConstants.poseStableDuration) {
        _onActionDetected();
      }
    } else {
      _poseStableStart = null;
    }
  }

  Future<void> _onActionDetected() async {
    HapticFeedback.lightImpact();
    _timeoutTimer?.cancel();

    setState(() => _phase = _VerifyPhase.sending);

    try {
      // 1. Capture foto JPEG dari kamera untuk verifikasi.
      final file = await _cameraCtrl!.takePicture();
      final frameBytes = await file.readAsBytes();
      if (!mounted) return;

      // 2. Kirim foto ke API /face/verify untuk bandingkan dengan embedding tersimpan.
      final faceRepo = ApiFaceRepository();
      final verifyResult = await faceRepo.verify(
        frame: frameBytes,
        karyawanId: 0, // karyawan_id diambil dari token di backend
      );
      if (!mounted) return;

      // 3. Cek hasil verifikasi wajah.
      if (!verifyResult.success) {
        setState(() {
          _phase = _VerifyPhase.failed;
          _failMessage = verifyResult.message ?? 'Wajah tidak cocok dengan data terdaftar';
        });
        return;
      }

      // 4. Wajah cocok → clock-in / clock-out sesuai action.
      //    Koordinat sudah diambil di halaman absensi (widget.latitude/longitude).
      final attRepo = ApiAttendanceRepository();
      final isClockIn = widget.action != 'out';
      final result = isClockIn
          ? await attRepo.clockIn(
              latitude: widget.latitude,
              longitude: widget.longitude,
              faceSimilarityScore: verifyResult.similarity ?? 0.0,
              isMocked: widget.isMocked,
            )
          : await attRepo.clockOut(
              latitude: widget.latitude,
              longitude: widget.longitude,
              isMocked: widget.isMocked,
            );

      if (!mounted) return;
      if (result.isSuccess) {
        _clockTime = DateTime.now();
        setState(() => _phase = _VerifyPhase.success);
        HapticFeedback.mediumImpact();

        await Future<void>.delayed(const Duration(seconds: 2));
        if (!mounted) return;
        context.go(AppRoutes.home);
      } else {
        setState(() {
          _phase = _VerifyPhase.failed;
          _failMessage = result.message ?? 'Gagal absen';
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _phase = _VerifyPhase.failed;
        _failMessage = 'Gagal: $e';
      });
    }
  }

  void _retry() {
    setState(() {
      _action = VerifyAction.values[_rng.nextInt(VerifyAction.values.length)];
      _phase = _VerifyPhase.detecting;
      _blinkSeenClosed = false;
      _lastBlinkCapture = null;
      _poseStableStart = null;
      _failMessage = null;
    });
    _timeoutTimer?.cancel();
    _timeoutTimer = Timer(AppConstants.livenessTimeout, _onTimeout);
  }

  void _onTimeout() {
    if (!mounted || _phase != _VerifyPhase.detecting) return;
    setState(() {
      _phase = _VerifyPhase.failed;
      _failMessage = 'Verifikasi gagal — waktu habis';
    });
  }

  @override
  void dispose() {
    _timeoutTimer?.cancel();
    _cameraCtrl?.stopImageStream();
    _cameraCtrl?.dispose();
    _faceDetector.close();
    _pulseAnim.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light.copyWith(statusBarColor: Colors.black),
      child: Scaffold(
        backgroundColor: Colors.black,
        body: SafeArea(top: false, child: _buildBody()),
      ),
    );
  }

  Widget _buildBody() {
    if (_initializing) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(color: Colors.white),
            SizedBox(height: 16),
            Text('Menginisialisasi kamera...',
                style: TextStyle(color: Colors.white70)),
          ],
        ),
      );
    }

    if (_initError != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, color: Colors.red, size: 56),
              const SizedBox(height: 16),
              Text(_initError!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.white70)),
              const SizedBox(height: 24),
              FilledButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Kembali'),
              ),
            ],
          ),
        ),
      );
    }

    // SUKSES.
    if (_phase == _VerifyPhase.success) {
      final hh = _clockTime!.hour.toString().padLeft(2, '0');
      final mm = _clockTime!.minute.toString().padLeft(2, '0');
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TweenAnimationBuilder<double>(
              tween: Tween(begin: 0.0, end: 1.0),
              duration: const Duration(milliseconds: 500),
              builder: (ctx, v, child) => Transform.scale(scale: v, child: child),
              child: const Icon(Icons.check_circle, color: AppColors.success, size: 96),
            ),
            const SizedBox(height: 20),
            Text(
              '${widget.action == "out" ? "Sudah Pulang" : "Sudah Masuk"} — Pukul $hh:$mm',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 22,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Verifikasi wajah berhasil',
              style: TextStyle(color: Colors.white70),
            ),
          ],
        ),
      );
    }

    // SENDING.
    if (_phase == _VerifyPhase.sending) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(color: Colors.white, strokeWidth: 3),
            SizedBox(height: 20),
            Text('Memverifikasi...', style: TextStyle(color: Colors.white70, fontSize: 16)),
          ],
        ),
      );
    }

    // FAILED.
    if (_phase == _VerifyPhase.failed) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, color: AppColors.error, size: 72),
              const SizedBox(height: 20),
              Text(
                _failMessage ?? 'Verifikasi gagal',
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: _retry,
                icon: const Icon(Icons.refresh),
                label: const Text('Coba Lagi'),
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: () => context.go(AppRoutes.home),
                child: const Text('Kembali ke Beranda',
                    style: TextStyle(color: Colors.white60)),
              ),
            ],
          ),
        ),
      );
    }

    // DETECTING — camera preview + oval guide + instruksi.
    return Stack(
      children: [
        Positioned.fill(
          child: _cameraCtrl != null && _cameraCtrl!.value.isInitialized
              ? CameraPreview(_cameraCtrl!)
              : Container(color: Colors.black),
        ),
        Positioned.fill(child: CustomPaint(painter: _OvalOverlayPainter())),
        Positioned(
          top: MediaQuery.of(context).padding.top + 20,
          left: 20,
          right: 20,
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                decoration: BoxDecoration(
                  color: Colors.black54,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(_action.icon, color: Colors.white, size: 28),
                    const SizedBox(width: 12),
                    Text(
                      _action.instruction,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        Positioned(
          bottom: MediaQuery.of(context).padding.bottom + 24,
          left: 20,
          right: 20,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.black38,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                FadeTransition(
                  opacity: _pulseAnim,
                  child: const Icon(Icons.circle, color: AppColors.success, size: 10),
                ),
                const SizedBox(width: 8),
                const Text('Kamera aktif — ikuti instruksi',
                    style: TextStyle(color: Colors.white70, fontSize: 13)),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _OvalOverlayPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final rect = Offset.zero & size;
    final ovalRect = Rect.fromCenter(
      center: Offset(size.width / 2, size.height * 0.42),
      width: size.width * 0.65,
      height: size.height * 0.38,
    );
    final overlayPath = Path()
      ..addRect(rect)
      ..addOval(ovalRect)
      ..fillType = PathFillType.evenOdd;
    canvas.drawPath(
      overlayPath,
      Paint()..color = Colors.black.withValues(alpha: 0.55),
    );
    canvas.drawOval(
      ovalRect,
      Paint()
        ..color = Colors.white.withValues(alpha: 0.5)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2.5,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
