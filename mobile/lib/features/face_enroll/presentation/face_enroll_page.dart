import 'dart:async';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:image/image.dart' as img;

import '../../../core/constants/app_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../auth/presentation/auth_controller.dart';
import '../data/api_face_repository.dart';
import '../data/camera_image_converter.dart';

/// Tahapan enrollment wajah.
enum _EnrollStep {
  /// Step 1 (liveness): Kedipkan mata untuk memastikan wajah asli.
  blink,

  /// Step 2: Hadapkan wajah lurus ke kamera. Sistem menangkap 3 frame
  /// frontal (wajah lurus, mata terbuka, stabil) untuk embedding akurat.
  frontalCapture,

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

  // Frontal capture tracking.
  bool _isCapturing = false; // sedang takePicture() berlangsung.
  DateTime? _lastFrameCapture; // cooldown antar 3 frame frontal.

  // Pose stabil tracking.
  DateTime? _poseStableStart;

  // Timeout per step.
  Timer? _timeoutTimer;
  bool _timedOut = false;

  // Pesan error saat enrollment gagal (mis. wajah tidak terdeteksi,
  // atau wajah sudah terdaftar di akun lain). Null = pakai pesan default.
  String? _errorMessage;

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
        ResolutionPreset.high, // High untuk embedding wajah yang akurat.
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
      case _EnrollStep.frontalCapture:
        _detectFrontal(face, image);
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
    // Setelah kedip terdeteksi (liveness terbukti), lanjut ke tahap
    // capture frame frontal — BUKAN langsung capture di posisi kedip.
    if (eyeProb < AppConstants.eyeClosedThreshold) {
      _blinkSeenClosed = true;
    }
    if (_blinkSeenClosed && eyeProb > AppConstants.eyeOpenThreshold) {
      _blinkSeenClosed = false;
      if (!mounted) return;
      setState(() {
        _step = _EnrollStep.frontalCapture;
        _poseStableStart = null;
        _restartTimeout();
      });
    }
  }

  /// Step 2: Capture 3 frame FRONTAL (wajah lurus ke kamera).
  ///
  /// Frame hanya ditangkap saat wajah benar-benar menghadap depan
  /// (|yaw| < 12°, |pitch| < 12°), mata terbuka, dan stabil ≥ 400 ms.
  /// Ada cooldown 900 ms antar frame supaya 3 frame tidak identik.
  /// Ini menghasilkan embedding yang jauh lebih akurat sehingga wajah
  /// orang berbeda tidak keliru dianggap sama.
  void _detectFrontal(Face face, CameraImage image) {
    // Sedang meng-capture? Jangan proses frame lain dulu.
    if (_isCapturing) return;

    // Cooldown antar frame.
    if (_lastFrameCapture != null &&
        DateTime.now().difference(_lastFrameCapture!) < AppConstants.captureCooldown) {
      return;
    }

    final yaw = face.headEulerAngleY ?? 99.0;
    final pitch = face.headEulerAngleX ?? 99.0;
    final leftOpen = face.leftEyeOpenProbability ?? 1.0;
    final rightOpen = face.rightEyeOpenProbability ?? 1.0;

    final isFrontal = yaw.abs() <= AppConstants.yawFrontalMaxAngle &&
        pitch.abs() <= AppConstants.pitchFrontalMaxAngle;
    final eyesOpen = leftOpen >= AppConstants.eyeOpenForCapture &&
        rightOpen >= AppConstants.eyeOpenForCapture;

    if (isFrontal && eyesOpen) {
      _poseStableStart ??= DateTime.now();
      final elapsed = DateTime.now().difference(_poseStableStart!);
      if (elapsed >= AppConstants.frontalStableDuration) {
        _captureFrame(image);
      }
    } else {
      // Wajah tidak frontal / mata tertutup → reset timer stabil.
      _poseStableStart = null;
    }
  }

  /// Tangkap frame frontal (JPEG) dan lanjut ke frame berikutnya.
  ///
  /// Foto di-resize ke 480px dan dikompres ke JPEG quality 75% sebelum
  /// disimpan. Ini mempercepat upload ke server (~50-100KB vs 2-4MB).
  Future<void> _captureFrame(CameraImage image) async {
    _isCapturing = true;
    try {
      final file = await _cameraCtrl!.takePicture();
      final rawBytes = await file.readAsBytes();

      // Kompres: decode → resize 480px → encode JPEG quality 75%.
      final decoded = img.decodeImage(rawBytes);
      if (decoded != null) {
        final resized = img.copyResize(decoded, width: 480);
        final compressed = Uint8List.fromList(img.encodeJpg(resized, quality: 75));
        _frames.add(compressed);
      } else {
        _frames.add(rawBytes); // Fallback: pakai raw jika decode gagal.
      }
    } catch (e) {
      _isCapturing = false;
      _poseStableStart = null;
      return;
    }
    _isCapturing = false;
    _lastFrameCapture = DateTime.now();

    HapticFeedback.lightImpact();

    if (!mounted) return;
    setState(() {
      _frameIndex++;
      _poseStableStart = null;

      if (_frameIndex >= AppConstants.enrollFrameCount) {
        _step = _EnrollStep.sending;
        _timeoutTimer?.cancel();
        _sendEnrollment();
      }
      // Tetap di step frontalCapture sampai semua frame terkumpul.
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

      // Fallback: jika router guard tidak otomatis redirect, navigasi eksplisit.
      await Future<void>.delayed(const Duration(milliseconds: 500));
      if (!mounted) return;
      if (context.mounted) {
        context.go('/home');
      }
    } else {
      setState(() {
        _step = _EnrollStep.frontalCapture; // Kembali ke layar kamera agar error terlihat.
        _timedOut = true;
        _errorMessage = result.message ?? 'Gagal mendaftarkan wajah';
      });
    }
  }

  void _retry() {
    setState(() {
      _step = _EnrollStep.blink;
      _frameIndex = 0;
      _frames.clear();
      _blinkSeenClosed = false;
      _lastBlinkCapture = null;
      _isCapturing = false;
      _lastFrameCapture = null;
      _poseStableStart = null;
      _timedOut = false;
      _errorMessage = null;
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
      _EnrollStep.frontalCapture => 'Hadapkan wajah lurus ke kamera',
      _ => '',
    };
    final stepIcon = switch (_step) {
      _EnrollStep.blink => Icons.visibility,
      _EnrollStep.frontalCapture => Icons.face,
      _ => Icons.check,
    };

    final stepText = _step == _EnrollStep.frontalCapture
        ? 'Foto ${_frameIndex + 1} / ${AppConstants.enrollFrameCount}'
        : 'Langkah 1: Liveness';

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
          stepText,
          style: const TextStyle(color: Colors.white60, fontSize: 13),
        ),
      ],
    );
  }

  Widget _buildBottom() {
    if (_timedOut) {
      final msg = _errorMessage ?? 'Waktu habis, silakan coba lagi';
      final isDuplicate = _errorMessage != null && _errorMessage!.contains('sudah terdaftar');
      return Column(
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.error.withValues(alpha: 0.9),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, color: Colors.white, size: 20),
                const SizedBox(width: 8),
                Flexible(
                  child: Text(msg,
                      style: const TextStyle(color: Colors.white)),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          if (isDuplicate)
            FilledButton.icon(
              onPressed: () {
                ref.read(authControllerProvider.notifier).logout();
              },
              icon: const Icon(Icons.arrow_back),
              label: const Text('Kembali'),
            )
          else
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
