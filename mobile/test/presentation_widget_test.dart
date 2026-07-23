import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:kafe_satuperdua/features/attendance/data/attendance_providers.dart';
import 'package:kafe_satuperdua/features/attendance/domain/attendance_record.dart';
import 'package:kafe_satuperdua/features/attendance/domain/attendance_session.dart';
import 'package:kafe_satuperdua/features/auth/data/auth_repository.dart';
import 'package:kafe_satuperdua/features/auth/domain/app_user.dart';
import 'package:kafe_satuperdua/features/auth/domain/auth_exceptions.dart';
import 'package:kafe_satuperdua/features/auth/presentation/auth_controller.dart';
import 'package:kafe_satuperdua/features/auth/presentation/login_page.dart';
import 'package:kafe_satuperdua/features/home/presentation/home_page.dart';
import 'package:kafe_satuperdua/features/profile/presentation/profile_page.dart';

final _user = AppUser(
  id: 1,
  username: 'tester',
  nama: 'Budi',
  idKaryawan: 'K-1',
  jabatan: 'Kasir',
  tanggalBergabung: DateTime(2026, 1, 1),
  statusAktif: true,
  hasFaceEnrolled: true,
);

class WidgetAuthRepository implements AuthRepository {
  WidgetAuthRepository({this.loginFuture, this.currentUser});

  final Future<AppUser>? loginFuture;
  final AppUser? currentUser;

  @override
  Future<AppUser?> getCurrentUser() async => currentUser;

  @override
  Future<AppUser> login({required String username, required String password}) {
    return loginFuture ?? Future.error(const InvalidCredentialsException());
  }

  @override
  Future<void> logout() async {}
}

Future<void> showLogin(WidgetTester tester, AuthRepository repo) async {
  await tester.pumpWidget(
    ProviderScope(
      overrides: [authRepositoryProvider.overrideWithValue(repo)],
      child: const MaterialApp(home: LoginPage()),
    ),
  );
  await tester.pump(const Duration(milliseconds: 900));
}

void main() {
  setUpAll(() => initializeDateFormatting('id_ID'));

  group('LoginPage', () {
    testWidgets('validates required username and password', (tester) async {
      await showLogin(tester, WidgetAuthRepository());
      await tester.tap(find.text('Masuk'));
      await tester.pump();
      expect(find.text('Username wajib diisi'), findsOneWidget);
      expect(find.text('Password wajib diisi'), findsOneWidget);
    });

    testWidgets('shows friendly invalid credentials error', (tester) async {
      await showLogin(tester, WidgetAuthRepository());
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Username'),
        'tester',
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Password'),
        'wrong',
      );
      await tester.tap(find.text('Masuk'));
      await tester.pump();
      await tester.pump();
      expect(find.text('Username atau password salah'), findsOneWidget);
    });

    testWidgets('disables form and shows progress during login', (
      tester,
    ) async {
      final completer = Completer<AppUser>();
      await showLogin(
        tester,
        WidgetAuthRepository(loginFuture: completer.future),
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Username'),
        'tester',
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Password'),
        'secret',
      );
      await tester.tap(find.text('Masuk'));
      await tester.pump();
      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      expect(
        tester.widget<TextFormField>(find.byType(TextFormField).first).enabled,
        isFalse,
      );

      completer.completeError(const InvalidCredentialsException());
      await tester.pump();
      await tester.pump();
    });
  });

  testWidgets('ProfilePage handles an empty name without crashing', (
    tester,
  ) async {
    final emptyName = _user.copyWith(nama: '');
    await tester.pumpWidget(
      ProviderScope(
        overrides: [currentUserProvider.overrideWithValue(emptyName)],
        child: const MaterialApp(home: ProfilePage()),
      ),
    );
    await tester.pump();
    expect(find.text('?'), findsOneWidget);
    expect(find.text('-'), findsWidgets);
    expect(tester.takeException(), isNull);
  });

  testWidgets('HomePage renders basic not-yet-attended state from overrides', (
    tester,
  ) async {
    const session = AttendanceSession(canClockIn: true, canClockOut: false);
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          currentUserProvider.overrideWithValue(_user),
          todayAttendanceProvider.overrideWith((_) async => session),
          activeLocationProvider.overrideWith((_) async => null),
          monthHistoryProvider.overrideWith(
            (_, _) async => const <AttendanceRecord>[],
          ),
        ],
        child: const MaterialApp(home: HomePage()),
      ),
    );
    await tester.pump();
    await tester.pump();
    expect(find.text('Belum Absen'), findsOneWidget);
    expect(find.text('Budi'), findsOneWidget);
    expect(find.textContaining('Ringkasan'), findsOneWidget);
    expect(find.text('0'), findsNWidgets(3));
  });
}
