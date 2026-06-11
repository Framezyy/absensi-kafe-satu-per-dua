import 'dart:async';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../auth/presentation/auth_controller.dart';
import '../data/api_face_repository.dart';
import '../data/camera_image_converter.dart';

/// Tahapan enrollment wajah.
enum _EnrollStep {
  /// Step 1: Kedipkan mata.
  blink,

  /// Step 2: Tolehkan kepala ke kiri (yaw < -20°).
  leftYaw,

  /// Step 3: Tolehkan kepala ke kanan (yaw > +20°).
  rightYaw,

  /// Mengirim 3 frame ke API / FastAPI.
  sending,

  /// Selesai — wajah terdaftar.
  done,
}

/// Layar Face Enrollment — Wireframe KF-07, Plan §6.4.
///
/// Auto-capture 3-tahap menggunakan Google ML Kit Face Detector:
/// 1. Kedipkan mata (eye probability > 0.5 → < 0.3 → > 0.5)
/// 2. Tolehkan kepala ke kiri (headEulerAngleY < -20° stabil ≥ 500 ms)
/// 3. Tolehkan kepala ke kanan (headEulerAngleY > +20° stabil ≥ 500 ms)
///
/// Setelah sukses → `markFaceEnrolled()` → router auto-redirect ke /home.
class FaceEnrollPage extends ConsumerStatefulWidget {
  const FaceEnrollPage({super.key});

  @override
  ConsumerState<FaceEnrollPage> createState() => _FaceEnrollPageState();
}

class _FaceEnrollPageState extends ConsumerState<FaceEnrollPage>
    with SingleTickerProviderStateMixin {
  // Kamera & ML Kit.
  CameraController? _cameraCtrl;
  late final FaceDetector _faceDetector;
  bool _isProcessing = false;

  // State machine.
  _EnrollStep _step = _EnrollStep.blink;
  int _frameIndex = 0; // 0, 1, 2
  final _frames = <Uint8List>[]; // 3 frame JPEG yang ditangkap.

  // Blink tracking: per-mata, perlu transisi open → closed → open.
  bool _blinkSeenClosed = false;
  DateTime? _lastBlinkCapture; // cooldown anti double-trigger.

  // Pose stabil tracking.
  DateTime? _poseStableStart;

  // Timeout per step.
  Timer? _timeoutTimer;
  bool _timedOut = false;

  // UI.
  late final AnimationController _pulseAnim;
  bool _initializing = true;
  String? _initError;

  // Sensor orientation kamera depan (dibaca dari CameraDescription).
  late int _sensorOrientation;

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
      });

      _startImageStream();
      _startTimeout();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _initializing = false;
        _initError = 'Gagal menginisialisasi kamera: $e';
      });
    }
  }

  void _startImageStream() {
    _cameraCtrl?.startImageStream((image) {
      if (_isProcessing || _step == _EnrollStep.sending || _step == _EnrollStep.done) return;
      _isProcessing = true;
      _processFrame(image).whenComplete(() => _isProcessing = false);
    });
  }

  Future<void> _processFrame(CameraImage image) async {
    final inputImage =
        cameraImageToInputImage(image, _cameraCtrl!.description, _sensorOrientation);
    if (inputImage == null) return;

    final faces = await _faceDetector.processImage(inputImage);
    if (!mounted || faces.isEmpty) return;

    final face = faces.first;
    switch (_step) {
      case _EnrollStep.blink:
        _detectBlink(face, image);
      case _EnrollStep.leftYaw:
        _detectYaw(face, image, isLeft: true);
      case _EnrollStep.rightYaw:
        _detectYaw(face, image, isLeft: false);
      case _EnrollStep.sending:
      case _EnrollStep.done:
        break;
    }
  }

  /// Step 1: Deteksi kedipan mata.
  /// Transisi: mata terbuka → tertutup → terbuka (per-mata, bukan rata-rata).
  /// Menggunakan threshold yang lebih rendah untuk kamera depan HP entry-level.
  void _detectBlink(Face face, CameraImage image) {
    // Cooldown: cegah trigger berulang dalam 500ms.
    if (_lastBlinkCapture != null &&
        DateTime.now().difference(_lastBlinkCapture!) < AppConstants.blinkCooldown) {
      return;
    }

    final leftOpen = face.leftEyeOpenProbability;
    final rightOpen = face.rightEyeOpenProbability;

    // Ambil probabilitas mata yang PALING tertutup (min) — lebih sensitif
    // karena satu mata yang cukup tertutup sudah mendeteksi kedipan.
    double? eyeProb;
    if (leftOpen != null && rightOpen != null) {
      eyeProb = leftOpen < rightOpen ? leftOpen : rightOpen;
    } else if (leftOpen != null) {
      eyeProb = leftOpen;
    } else if (rightOpen != null) {
      eyeProb = rightOpen;
    } else {
      return; // Tidak ada data mata.
    }

    // Transisi: open → closed → open = satu kedipan.
    if (eyeProb < AppConstants.eyeClosedThreshold) {
      _blinkSeenClosed = true;
    }
    if (_blinkSeenClosed && eyeProb > AppConstants.eyeOpenThreshold) {
      _blinkSeenClosed = false;
      _lastBlinkCapture = DateTime.now();
      _captureFrame(image);
    }
  }

  /// Step 2 & 3: Deteksi toleh kiri/kanan.
  /// Pose harus stabil (|yaw| > 20°) selama ≥ 500 ms.
  void _detectYaw(Face face, CameraImage image, {required bool isLeft}) {
    final yaw = face.headEulerAngleY ?? 0.0; // negatif = kiri, positif = kanan
    final targetMet = isLeft
        ? yaw < -AppConstants.yawTriggerAngle
        : yaw > AppConstants.yawTriggerAngle;

    if (targetMet) {
      _poseStableStart ??= DateTime.now();
      final elapsed = DateTime.now().difference(_poseStableStart!);
      if (elapsed >= AppConstants.poseStableDuration) {
        _captureFrame(image);
      }
    } else {
      _poseStableStart = null;
    }
  }

  /// Tangkap frame saat ini (JPEG) dan lanjut ke step berikutnya.
  void _captureFrame(CameraImage image) {
    // Simpan Y plane bytes sebagai proxy frame (JPEG encoding dilakukan
    // di backend Phase 4; di Phase 1 kita simpan raw bytes untuk simulasi).
    // Untuk mock: simpan placeholder bytes (tidak benar-benar JPEG).
    final yBytes = Uint8List.fromList(image.planes[0].bytes);
    _frames.add(yBytes);

    // Haptic feedback ringan.
    HapticFeedback.lightImpact();

    if (!mounted) return;
    setState(() {
      _frameIndex++;
      _blinkSeenClosed = false;
      _poseStableStart = null;

      if (_frameIndex >= 3) {
        _step = _EnrollStep.sending;
        _timeoutTimer?.cancel();
        _sendEnrollment();
      } else {
        _step = _EnrollStep.values[_frameIndex];
        _restartTimeout();
      }
    });
  }

  void _startTimeout() {
    _timeoutTimer = Timer(AppConstants.livenessTimeout, _onTimeout);
  }

  void _restartTimeout() {
    _timeoutTimer?.cancel();
    _timeoutTimer = Timer(AppConstants.livenessTimeout, _onTimeout);
  }

  void _onTimeout() {
    if (!mounted || _step == _EnrollStep.done || _step == _EnrollStep.sending) return;
    setState(() => _timedOut = true);
  }

  Future<void> _sendEnrollment() async {
    final repo = ApiFaceRepository();
    final result = await repo.enroll(frames: _frames);
    if (!mounted) return;

    if (result.success) {
      setState(() => _step = _EnrollStep.done);
      HapticFeedback.mediumImpact();

      await Future<void>.delayed(const Duration(seconds: 1));
      if (!mounted) return;

      ref.read(authControllerProvider.notifier).markFaceEnrolled();
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.message ?? 'Wajah berhasil terdaftar')),
      );
    } else {
      setState(() => _timedOut = true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.message ?? 'Gagal mendaftarkan wajah')),
      );
    }
  }

  void _retry() {
    setState(() {
      _step = _EnrollStep.blink;
      _frameIndex = 0;
      _frames.clear();
      _blinkSeenClosed = false;
      _lastBlinkCapture = null;
      _poseStableStart = null;
      _timedOut = false;
    });
    _restartTimeout();
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
    return PopScope(
      canPop: false,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.light.copyWith(
          statusBarColor: Colors.black,
        ),
        child: Scaffold(
          backgroundColor: Colors.black,
          body: SafeArea(
            top: false,
            child: _buildBody(),
          ),
        ),
      ),
    );
  }

  Widget _buildBody() {
    // Inisialisasi kamera.
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

    // Error kamera.
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

    // Sukses.
    if (_step == _EnrollStep.done) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TweenAnimationBuilder<double>(
              tween: Tween(begin: 0.0, end: 1.0),
              duration: const Duration(milliseconds: 500),
              builder: (ctx, value, child) => Transform.scale(
                scale: value,
                child: child,
              ),
              child: const Icon(Icons.check_circle,
                  color: AppColors.success, size: 96),
            ),
            const SizedBox(height: 20),
            const Text(
              'Wajah Berhasil Terdaftar!',
              style: TextStyle(
                color: Colors.white,
                fontSize: 22,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Anda sekarang dapat melakukan absensi',
              style: TextStyle(color: Colors.white70, fontSize: 14),
            ),
          ],
        ),
      );
    }

    // Sending.
    if (_step == _EnrollStep.sending) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(color: Colors.white, strokeWidth: 3),
            SizedBox(height: 20),
            Text(
              'Mengirim data wajah...',
              style: TextStyle(color: Colors.white70, fontSize: 16),
            ),
          ],
        ),
      );
    }

    // Camera preview + overlay.
    return Stack(
      children: [
        // Camera preview.
        Positioned.fill(
          child: _cameraCtrl != null && _cameraCtrl!.value.isInitialized
              ? CameraPreview(_cameraCtrl!)
              : Container(color: Colors.black),
        ),

        // Overlay gelap di luar oval guide.
        Positioned.fill(
          child: CustomPaint(painter: _OvalOverlayPainter()),
        ),

        // Header: progress + instruksi.
        Positioned(
          top: MediaQuery.of(context).padding.top + 20,
          left: 20,
          right: 20,
          child: _buildHeader(),
        ),

        // Bottom: timeout warning / retry.
        Positioned(
          bottom: MediaQuery.of(context).padding.bottom + 24,
          left: 20,
          right: 20,
          child: _buildBottom(),
        ),
      ],
    );
  }

  Widget _buildHeader() {
    final stepLabel = switch (_step) {
      _EnrollStep.blink => 'Kedipkan mata Anda',
      _EnrollStep.leftYaw => 'Tolehkan kepala ke KIRI',
      _EnrollStep.rightYaw => 'Tolehkan kepala ke KANAN',
      _ => '',
    };
    final stepIcon = switch (_step) {
      _EnrollStep.blink => Icons.visibility,
      _EnrollStep.leftYaw => Icons.arrow_back,
      _EnrollStep.rightYaw => Icons.arrow_forward,
      _ => Icons.check,
    };

    return Column(
      children: [
        // Progress dots.
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(3, (i) {
            final active = i < _frameIndex;
            final current = i == _frameIndex;
            return AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              margin: const EdgeInsets.symmetric(horizontal: 4),
              width: current ? 28 : 10,
              height: 10,
              decoration: BoxDecoration(
                color: active
                    ? AppColors.success
                    : current
                        ? Colors.white
                        : Colors.white30,
                borderRadius: BorderRadius.circular(5),
              ),
            );
          }),
        ),
        const SizedBox(height: 20),

        // Instruksi.
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          decoration: BoxDecoration(
            color: Colors.black54,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(stepIcon, color: Colors.white, size: 28),
              const SizedBox(width: 12),
              Text(
                stepLabel,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Langkah ${_frameIndex + 1} / 3',
          style: const TextStyle(color: Colors.white60, fontSize: 13),
        ),
      ],
    );
  }

  Widget _buildBottom() {
    if (_timedOut) {
      return Column(
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.error.withValues(alpha: 0.9),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.timer_off, color: Colors.white, size: 20),
                SizedBox(width: 8),
                Text('Waktu habis, silakan coba lagi',
                    style: TextStyle(color: Colors.white)),
              ],
            ),
          ),
          const SizedBox(height: 12),
          FilledButton.icon(
            onPressed: _retry,
            icon: const Icon(Icons.refresh),
            label: const Text('Ulangi'),
          ),
        ],
      );
    }

    return Container(
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
          const Text(
            'Kamera aktif — ikuti instruksi di atas',
            style: TextStyle(color: Colors.white70, fontSize: 13),
          ),
        ],
      ),
    );
  }
}

/// Custom painter: overlay gelap dengan oval transparan di tengah
/// (guide area untuk wajah user).
class _OvalOverlayPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final rect = Offset.zero & size;
    final ovalRect = Rect.fromCenter(
      center: Offset(size.width / 2, size.height * 0.42),
      width: size.width * 0.65,
      height: size.height * 0.38,
    );

    // Overlay gelap.
    final overlayPath = Path()
      ..addRect(rect)
      ..addOval(ovalRect)
      ..fillType = PathFillType.evenOdd;
    canvas.drawPath(
      overlayPath,
      Paint()..color = Colors.black.withValues(alpha: 0.55),
    );

    // Border oval.
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
