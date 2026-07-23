import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kafe_satuperdua/features/attendance/data/api_attendance_repository.dart';
import 'package:kafe_satuperdua/features/attendance/domain/clock_result.dart';
import 'package:kafe_satuperdua/features/face_enroll/data/api_face_repository.dart';

class RecordingAdapter implements HttpClientAdapter {
  RecordingAdapter(this.handler);

  final Future<ResponseBody> Function(RequestOptions options) handler;
  final requests = <RequestOptions>[];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) {
    requests.add(options);
    return handler(options);
  }

  @override
  void close({bool force = false}) {}
}

ResponseBody jsonBody(String body, {int status = 200}) =>
    ResponseBody.fromString(
      body,
      status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );

void main() {
  group('ApiAttendanceRepository', () {
    test('history sends zero-padded month query and parses list', () async {
      late RecordingAdapter adapter;
      adapter = RecordingAdapter(
        (_) async => jsonBody('''
        {"data":[{"id":1,"tanggal":"2026-02-01","jam_masuk":"08:00"}]}
      '''),
      );
      final dio = Dio(BaseOptions(baseUrl: 'https://example.test'))
        ..httpClientAdapter = adapter;
      final records = await ApiAttendanceRepository(
        dio: dio,
      ).getHistory(year: 2026, month: 2);
      expect(adapter.requests.single.path, '/attendance/history');
      expect(adapter.requests.single.queryParameters, {'month': '2026-02'});
      expect(records.single.id, 1);
    });

    test('clock-in sends exact payload and parses success', () async {
      final adapter = RecordingAdapter(
        (_) async => jsonBody('{"server_time":"2026-01-01T01:00:00Z"}'),
      );
      final dio = Dio(BaseOptions(baseUrl: 'https://example.test'))
        ..httpClientAdapter = adapter;
      final result = await ApiAttendanceRepository(dio: dio).clockIn(
        latitude: -6.2,
        longitude: 106.8,
        faceVerificationToken: 'proof',
        isMocked: true,
      );
      expect(result.status, ClockStatus.success);
      expect(adapter.requests.single.method, 'POST');
      expect(adapter.requests.single.path, '/attendance/clock-in');
      expect(adapter.requests.single.data, {
        'latitude': -6.2,
        'longitude': 106.8,
        'face_verification_token': 'proof',
        'is_mocked': true,
      });
    });

    test('server clock error maps code instead of throwing', () async {
      final adapter = RecordingAdapter(
        (_) async => jsonBody(
          '{"code":"OUTSIDE_GEOFENCE","message":"too far"}',
          status: 422,
        ),
      );
      final dio = Dio(
        BaseOptions(
          baseUrl: 'https://example.test',
          validateStatus: (status) => status != null && status < 400,
        ),
      )..httpClientAdapter = adapter;
      final result = await ApiAttendanceRepository(
        dio: dio,
      ).clockOut(latitude: 1, longitude: 2, faceVerificationToken: 'proof');
      expect(result.status, ClockStatus.outsideGeofence);
      expect(result.message, 'too far');
    });

    test('correction payload and wrapped response are parsed', () async {
      final adapter = RecordingAdapter(
        (_) async => jsonBody('''
        {"data":{"id":"3","attendance_id":"4","status":"pending"}}
      '''),
      );
      final dio = Dio(BaseOptions(baseUrl: 'https://example.test'))
        ..httpClientAdapter = adapter;
      final at = DateTime(2026, 1, 2, 17, 30);
      final result = await ApiAttendanceRepository(
        dio: dio,
      ).submitCorrection(attendanceId: 4, clockOutAt: at, reason: 'Lupa');
      expect(result.id, 3);
      expect(adapter.requests.single.data, {
        'attendance_id': 4,
        'clock_out_at': at.toIso8601String(),
        'reason': 'Lupa',
      });
    });
  });

  group('ApiFaceRepository', () {
    test('enroll sends three named multipart frames', () async {
      final adapter = RecordingAdapter(
        (_) async => jsonBody('{"message":"enrolled"}'),
      );
      final dio = Dio(BaseOptions(baseUrl: 'https://example.test'))
        ..httpClientAdapter = adapter;
      final result = await ApiFaceRepository(dio: dio).enroll(
        frames: [
          Uint8List.fromList([1]),
          Uint8List.fromList([2]),
          Uint8List.fromList([3]),
        ],
      );
      final request = adapter.requests.single;
      final form = request.data as FormData;
      expect(result.success, isTrue);
      expect(request.path, '/face/enroll');
      expect(form.files.map((entry) => entry.value.filename), [
        'frame_0.jpg',
        'frame_1.jpg',
        'frame_2.jpg',
      ]);
    });

    test('verify sends action and parses proof response', () async {
      final adapter = RecordingAdapter(
        (_) async => jsonBody('''
        {"match":true,"similarity":"0.91","verification_token":"proof"}
      '''),
      );
      final dio = Dio(BaseOptions(baseUrl: 'https://example.test'))
        ..httpClientAdapter = adapter;
      final result = await ApiFaceRepository(
        dio: dio,
      ).verify(frame: Uint8List.fromList([1, 2]), action: 'clock_in');
      final form = adapter.requests.single.data as FormData;
      expect(result.success, isTrue);
      expect(result.similarity, .91);
      expect(form.fields.single.key, 'action');
      expect(form.fields.single.value, 'clock_in');
      expect(form.files.single.value.filename, 'verify.jpg');
    });

    test('verify maps API error body', () async {
      final adapter = RecordingAdapter(
        (_) async => jsonBody(
          '{"code":"FACE_MISMATCH","message":"Tidak cocok"}',
          status: 422,
        ),
      );
      final dio = Dio(
        BaseOptions(
          baseUrl: 'https://example.test',
          validateStatus: (status) => status != null && status < 400,
        ),
      )..httpClientAdapter = adapter;
      final result = await ApiFaceRepository(
        dio: dio,
      ).verify(frame: Uint8List(1), action: 'clock_out');
      expect(result.success, isFalse);
      expect(result.code, 'FACE_MISMATCH');
      expect(result.message, 'Tidak cocok');
    });
  });
}
