import 'package:flutter_test/flutter_test.dart';
import 'package:kafe_satuperdua/core/router/app_router.dart';
import 'package:kafe_satuperdua/core/router/app_routes.dart';

void main() {
  final locations = [
    AppRoutes.login,
    AppRoutes.enroll,
    AppRoutes.home,
    AppRoutes.history,
    AppRoutes.leave,
    AppRoutes.profile,
    AppRoutes.attendance,
  ];

  group('authRedirect matrix', () {
    for (final location in locations) {
      test('logged out at $location', () {
        expect(
          authRedirect(
            isLoggedIn: false,
            hasFaceEnrolled: false,
            location: location,
          ),
          location == AppRoutes.login ? isNull : AppRoutes.login,
        );
      });

      test('unenrolled user at $location', () {
        expect(
          authRedirect(
            isLoggedIn: true,
            hasFaceEnrolled: false,
            location: location,
          ),
          location == AppRoutes.enroll ? isNull : AppRoutes.enroll,
        );
      });

      test('enrolled user at $location', () {
        final redirect = authRedirect(
          isLoggedIn: true,
          hasFaceEnrolled: true,
          location: location,
        );
        expect(
          redirect,
          location == AppRoutes.login || location == AppRoutes.enroll
              ? AppRoutes.home
              : isNull,
        );
      });
    }
  });
}
