import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
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
import '../../shared/widgets/main_shell.dart';
import 'app_routes.dart';

/// Listenable yang mendengarkan perubahan state auth + enrollment di
/// Riverpod, lalu memberi tahu GoRouter untuk re-evaluasi redirect.
class AppRouterNotifier extends ChangeNotifier {
  AppRouterNotifier(this._ref) {
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

    if (!isLoggedIn) {
      return goingToLogin ? null : AppRoutes.login;
    }
    if (!hasFaceEnrolled) {
      return goingToEnroll ? null : AppRoutes.enroll;
    }
    if (goingToLogin || goingToEnroll) {
      return AppRoutes.home;
    }
    return null;
  }
}

final _rootKey = GlobalKey<NavigatorState>();
final _homeTabKey = GlobalKey<NavigatorState>();
final _historyTabKey = GlobalKey<NavigatorState>();
final _leaveTabKey = GlobalKey<NavigatorState>();
final _profileTabKey = GlobalKey<NavigatorState>();

/// Provider GoRouter aplikasi.
final routerProvider = Provider<GoRouter>((ref) {
  final notifier = AppRouterNotifier(ref);

  return GoRouter(
    navigatorKey: _rootKey,
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
      // Rute full-screen di atas shell (tanpa bottom nav).
      GoRoute(
        path: AppRoutes.attendance,
        name: AppRoutes.attendanceName,
        parentNavigatorKey: _rootKey,
        builder: (_, _) => const AttendancePage(),
      ),
      GoRoute(
        path: AppRoutes.verify,
        name: AppRoutes.verifyName,
        parentNavigatorKey: _rootKey,
        builder: (_, state) {
          final q = state.uri.queryParameters;
          return FaceVerifyPage(
            action: q['action'] ?? 'in',
            latitude: double.tryParse(q['lat'] ?? '') ?? 0,
            longitude: double.tryParse(q['lng'] ?? '') ?? 0,
            isMocked: q['mocked'] == 'true',
          );
        },
      ),
      // Shell dengan bottom navigation (4 tab).
      StatefulShellRoute.indexedStack(
        builder: (_, _, navigationShell) =>
            MainShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(
            navigatorKey: _homeTabKey,
            routes: [
              GoRoute(
                path: AppRoutes.home,
                name: AppRoutes.homeName,
                builder: (_, _) => const HomePage(),
              ),
            ],
          ),
          StatefulShellBranch(
            navigatorKey: _historyTabKey,
            routes: [
              GoRoute(
                path: AppRoutes.history,
                name: AppRoutes.historyName,
                builder: (_, _) => const HistoryPage(),
              ),
            ],
          ),
          StatefulShellBranch(
            navigatorKey: _leaveTabKey,
            routes: [
              GoRoute(
                path: AppRoutes.leave,
                name: AppRoutes.leaveName,
                builder: (_, _) => const LeavePage(),
              ),
            ],
          ),
          StatefulShellBranch(
            navigatorKey: _profileTabKey,
            routes: [
              GoRoute(
                path: AppRoutes.profile,
                name: AppRoutes.profileName,
                builder: (_, _) => const ProfilePage(),
              ),
            ],
          ),
        ],
      ),
    ],
  );
});
