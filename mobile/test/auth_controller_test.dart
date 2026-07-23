import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kafe_satuperdua/features/auth/data/auth_repository.dart';
import 'package:kafe_satuperdua/features/auth/data/mock_auth_repository.dart';
import 'package:kafe_satuperdua/features/auth/domain/app_user.dart';
import 'package:kafe_satuperdua/features/auth/domain/auth_exceptions.dart';
import 'package:kafe_satuperdua/features/auth/presentation/auth_controller.dart';

final _user = AppUser(
  id: 1,
  username: 'tester',
  nama: 'Test User',
  idKaryawan: 'K-1',
  jabatan: 'QA',
  tanggalBergabung: DateTime(2026, 1, 1),
  statusAktif: true,
  hasFaceEnrolled: false,
);

class FakeAuthRepository implements AuthRepository {
  FakeAuthRepository({this.currentUser, this.loginError, this.logoutError});

  AppUser? currentUser;
  Object? loginError;
  Object? logoutError;
  String? loginUsername;
  String? loginPassword;
  var logoutCalls = 0;

  @override
  Future<AppUser?> getCurrentUser() async => currentUser;

  @override
  Future<AppUser> login({
    required String username,
    required String password,
  }) async {
    loginUsername = username;
    loginPassword = password;
    if (loginError case final error?) throw error;
    return currentUser ?? _user;
  }

  @override
  Future<void> logout() async {
    logoutCalls++;
    if (logoutError case final error?) throw error;
  }
}

void main() {
  group('MockAuthRepository', () {
    test('accepts trimmed case-insensitive username', () async {
      final user = await MockAuthRepository().login(
        username: ' KARYAWAN1 ',
        password: '123456',
      );
      expect(user.username, 'karyawan1');
      expect(user.hasFaceEnrolled, isFalse);
    });

    test('second account is enrolled and bad credentials throw', () async {
      final repo = MockAuthRepository();
      final user = await repo.login(username: 'karyawan2', password: '123456');
      expect(user.hasFaceEnrolled, isTrue);
      await expectLater(
        repo.login(username: 'karyawan2', password: 'wrong'),
        throwsA(isA<InvalidCredentialsException>()),
      );
      expect(await repo.getCurrentUser(), isNull);
      await expectLater(repo.logout(), completes);
    });
  });

  group('AuthController providers', () {
    test('restores current user and derived states', () async {
      final repo = FakeAuthRepository(currentUser: _user);
      final container = ProviderContainer(
        overrides: [authRepositoryProvider.overrideWithValue(repo)],
      );
      addTearDown(container.dispose);

      expect(await container.read(authControllerProvider.future), _user);
      expect(container.read(currentUserProvider), _user);
      expect(container.read(faceEnrollmentStatusProvider), isFalse);
    });

    test('login success captures credentials and updates state', () async {
      final repo = FakeAuthRepository(currentUser: _user);
      final container = ProviderContainer(
        overrides: [authRepositoryProvider.overrideWithValue(repo)],
      );
      addTearDown(container.dispose);
      await container.read(authControllerProvider.future);

      await container
          .read(authControllerProvider.notifier)
          .login(username: 'tester', password: 'secret');
      expect(repo.loginUsername, 'tester');
      expect(repo.loginPassword, 'secret');
      expect(container.read(authControllerProvider).value, _user);
    });

    test('login error is retained in AsyncError', () async {
      final error = StateError('offline');
      final repo = FakeAuthRepository(loginError: error);
      final container = ProviderContainer(
        overrides: [authRepositoryProvider.overrideWithValue(repo)],
      );
      addTearDown(container.dispose);
      await container.read(authControllerProvider.future);

      await container
          .read(authControllerProvider.notifier)
          .login(username: 'tester', password: 'secret');
      expect(container.read(authControllerProvider).hasError, isTrue);
      expect(container.read(authControllerProvider).error, same(error));
    });

    test('logout clears state even when repository throws', () async {
      final repo = FakeAuthRepository(
        currentUser: _user,
        logoutError: StateError('server down'),
      );
      final container = ProviderContainer(
        overrides: [authRepositoryProvider.overrideWithValue(repo)],
      );
      addTearDown(container.dispose);
      await container.read(authControllerProvider.future);

      await container.read(authControllerProvider.notifier).logout();
      expect(repo.logoutCalls, 1);
      expect(container.read(authControllerProvider).value, isNull);
    });

    test(
      'mark enrolled updates user and does nothing when logged out',
      () async {
        final repo = FakeAuthRepository(currentUser: _user);
        final container = ProviderContainer(
          overrides: [authRepositoryProvider.overrideWithValue(repo)],
        );
        addTearDown(container.dispose);
        await container.read(authControllerProvider.future);

        container.read(authControllerProvider.notifier).markFaceEnrolled();
        expect(container.read(faceEnrollmentStatusProvider), isTrue);
        await container.read(authControllerProvider.notifier).logout();
        container.read(authControllerProvider.notifier).markFaceEnrolled();
        expect(container.read(authControllerProvider).value, isNull);
      },
    );
  });
}
