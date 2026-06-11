import 'dart:typed_data';
import 'dart:ui';

import 'package:camera/camera.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';

/// Konversi [CameraImage] dari plugin camera ke [InputImage] untuk ML Kit.
///
/// Mendeteksi otomatis jumlah plane yang dikembalikan camera plugin Android:
/// - **1 plane**: NV21 kontinu (Y + VU interleaved dalam satu buffer) — Infinix, beberapa MTK
/// - **2 plane**: NV21 native (Y terpisah, VU interleaved)
/// - **3 plane**: YUV420 (Y, U, V terpisah) → konversi ke NV21
InputImage? cameraImageToInputImage(
  CameraImage image,
  CameraDescription camera,
  int sensorOrientation,
) {
  final rawBytes = _extractNv21Bytes(image);
  if (rawBytes == null) {
    print('[MLKIT] NV21 extraction FAILED: planes=${image.planes.length}, '
        'w=${image.width}, h=${image.height}');
    return null;
  }

  final rotation =
      InputImageRotationValue.fromRawValue(sensorOrientation) ??
          InputImageRotation.rotation0deg;

  print('[MLKIT] InputImage OK: bytes=${rawBytes.length}, '
      'rotation=$sensorOrientation, w=${image.width}, h=${image.height}');

  return InputImage.fromBytes(
    bytes: rawBytes,
    metadata: InputImageMetadata(
      size: Size(image.width.toDouble(), image.height.toDouble()),
      rotation: rotation,
      format: InputImageFormat.nv21,
      bytesPerRow: image.planes[0].bytesPerRow,
    ),
  );
}

/// Ekstraksi NV21 bytes dari [CameraImage].
Uint8List? _extractNv21Bytes(CameraImage image) {
  final planes = image.planes;
  final w = image.width;
  final h = image.height;
  final expectedSize = w * h * 3 ~/ 2; // NV21 = 1.5 bytes per pixel

  print('[MLKIT] CameraImage: planes=${planes.length}, w=$w, h=$h, '
      'format=${image.format.group}/${image.format.raw}, '
      'expectedNv21Size=$expectedSize');

  if (planes.isEmpty) return null;

  // ── Kasus 1 plane: NV21 kontinu (seluruh buffer di planes[0]) ──
  // Terjadi di beberapa device (Infinix MTK, dll) saat format = NV21.
  // Data sudah dalam format NV21, langsung pakai.
  if (planes.length == 1) {
    final bytes = planes[0].bytes;
    print('[MLKIT] 1-plane NV21: bytes=${bytes.length}, '
        'stride=${planes[0].bytesPerRow}, expected=$expectedSize');

    // Jika ukuran sudah pas, langsung return.
    if (bytes.length == expectedSize) {
      return bytes;
    }
    // Jika ada padding (bytes > expected), potong sesuai expected.
    if (bytes.length > expectedSize) {
      return Uint8List.sublistView(bytes, 0, expectedSize);
    }
    // Jika kurang dari expected, ada masalah — return apa adanya.
    print('[MLKIT] WARNING: bytes < expected, returning as-is');
    return bytes;
  }

  // ── Kasus 2 plane: NV21 native (Y + VU interleaved) ──
  if (planes.length == 2) {
    final yBytes = planes[0].bytes;
    final uvBytes = planes[1].bytes;

    print('[MLKIT] 2-plane: Y=${yBytes.length}, UV=${uvBytes.length}, '
        'Y stride=${planes[0].bytesPerRow}, UV stride=${planes[1].bytesPerRow}');

    // Jika Y stride == width (tidak ada padding), gabung langsung.
    if (planes[0].bytesPerRow == w) {
      return Uint8List.fromList([...yBytes, ...uvBytes]);
    }
    // Ada padding → salin baris per baris.
    final nv21 = Uint8List(expectedSize);
    var offset = 0;
    for (var row = 0; row < h; row++) {
      nv21.setRange(offset, offset + w, yBytes, row * planes[0].bytesPerRow);
      offset += w;
    }
    final uvHeight = h ~/ 2;
    final uvWidth = w ~/ 2 * 2;
    for (var row = 0; row < uvHeight; row++) {
      nv21.setRange(offset, offset + uvWidth, uvBytes, row * planes[1].bytesPerRow);
      offset += uvWidth;
    }
    return nv21;
  }

  // ── Kasus 3 plane: YUV420 (Y + U + V) → konversi ke NV21 ──
  if (planes.length == 3) {
    final yPlane = planes[0];
    final uPlane = planes[1];
    final vPlane = planes[2];

    print('[MLKIT] 3-plane YUV420: Y=${yPlane.bytes.length}, '
        'U=${uPlane.bytes.length}, V=${vPlane.bytes.length}');

    final yStride = yPlane.bytesPerRow;
    final uvRowStride = uPlane.bytesPerRow;
    final uvPixelStride = uPlane.bytesPerPixel ?? 1;
    final uvHeight = h ~/ 2;
    final uvWidth = w ~/ 2;

    final nv21 = Uint8List(expectedSize);
    var offset = 0;

    for (var row = 0; row < h; row++) {
      nv21.setRange(offset, offset + w, yPlane.bytes, row * yStride);
      offset += w;
    }
    for (var row = 0; row < uvHeight; row++) {
      for (var col = 0; col < uvWidth; col++) {
        final uvIndex = row * uvRowStride + col * uvPixelStride;
        if (uvIndex < vPlane.bytes.length && uvIndex < uPlane.bytes.length) {
          nv21[offset++] = vPlane.bytes[uvIndex];
          nv21[offset++] = uPlane.bytes[uvIndex];
        }
      }
    }
    return nv21;
  }

  print('[MLKIT] Unsupported plane count: ${planes.length}');
  return null;
}
