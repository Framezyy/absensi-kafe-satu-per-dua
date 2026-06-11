import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/attendance/presentation/attendance_page.dart';
import '../../features/attendance/presentation/face_verify_page.dart';
import '../../features/auth/domain/app_user.dart';
import '../../features/auth/presentation/auth_controller.dart';
import '../../features/auth/presentation/login_page.dart';
import '../../features/face_enroll/presentation/face_enroll_page.dart';
import '../../features/history/presentation/history_page.dart';
import '../../features/home/presentation/home_page.dart';
import '../../features/leave/presentation/leave_page.dart';
import '../../features/profile/presentation/profile_page.dart';
import 'app_routes.dart';

/// Listenable yang mendengarkan perubahan state auth + enrollment di
/// Riverpod, lalu memberi tahu GoRouter untuk re-evaluasi `redirect`.
///
/// Implementasi guard mengikuti keputusan plan #6e:
/// - belum login           → /login
/// - login & belum enroll  → /enroll (paksa, no skip, no back)
/// - login & sudah enroll  → /home (akses /enroll di-block, redirect ke /home)
class AppRouterNotifier extends ChangeNotifier {
  AppRouterNotifier(this._ref) {
    // Trigger re-evaluasi redirect setiap kali user / status enroll berubah.
    _ref.listen<AsyncValue<AppUser?>>(
      authControllerProvider,
      (_, _) => notifyListeners(),
    );
  }

  final Ref _ref;

  String? redirect(_, GoRouterState state) {
    final user = _ref.read(currentUserProvider);
    final hasFaceEnrolled = _ref.read(faceEnrollmentStatusProvider);
    final loc = state.matchedLocation;

    final isLoggedIn = user != null;
    final goingToLogin = loc == AppRoutes.login;
    final goingToEnroll = loc == AppRoutes.enroll;

    // 1. Belum login → paksa ke /login.
    if (!isLoggedIn) {
      return goingToLogin ? null : AppRoutes.login;
    }

    // 2. Sudah login tapi belum enroll → paksa ke /enroll.
    //    /enroll adalah satu-satunya layar yang boleh diakses.
    if (!hasFaceEnrolled) {
      return goingToEnroll ? null : AppRoutes.enroll;
    }

    // 3. Sudah login & sudah enroll:
    //    - jika masih di /login atau /enroll → tendang ke /home.
    //    - akses /enroll di-block (sesuai plan: enrollment one-time).
    if (goingToLogin || goingToEnroll) {
      return AppRoutes.home;
    }

    return null;
  }
}

/// Provider GoRouter aplikasi.
final routerProvider = Provider<GoRouter>((ref) {
  final notifier = AppRouterNotifier(ref);

  return GoRouter(
    initialLocation: AppRoutes.login,
    debugLogDiagnostics: kDebugMode,
    refreshListenable: notifier,
    redirect: notifier.redirect,
    routes: [
      GoRoute(
        path: AppRoutes.login,
        name: AppRoutes.loginName,
        builder: (_, _) => const LoginPage(),
      ),
      GoRoute(
        path: AppRoutes.enroll,
        name: AppRoutes.enrollName,
        builder: (_, _) => const FaceEnrollPage(),
      ),
      GoRoute(
        path: AppRoutes.home,
        name: AppRoutes.homeName,
        builder: (_, _) => const HomePage(),
      ),
      GoRoute(
        path: AppRoutes.attendance,
        name: AppRoutes.attendanceName,
        builder: (_, _) => const AttendancePage(),
      ),
      GoRoute(
        path: AppRoutes.verify,
        name: AppRoutes.verifyName,
        builder: (_, _) => const FaceVerifyPage(),
      ),
      GoRoute(
        path: AppRoutes.history,
        name: AppRoutes.historyName,
        builder: (_, _) => const HistoryPage(),
      ),
      GoRoute(
        path: AppRoutes.leave,
        name: AppRoutes.leaveName,
        builder: (_, _) => const LeavePage(),
      ),
      GoRoute(
        path: AppRoutes.profile,
        name: AppRoutes.profileName,
        builder: (_, _) => const ProfilePage(),
      ),
    ],
  );
});
