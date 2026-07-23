import 'package:flutter_test/flutter_test.dart';
import 'package:kafe_satuperdua/shared/utils/location_helper.dart';

void main() {
  group('haversineMeters', () {
    test('same point is exactly zero and boundary remains inclusive', () {
      final distance = LocationHelper.haversineMeters(
        -6.2,
        106.816666,
        -6.2,
        106.816666,
      );
      expect(distance, 0);
      expect(distance <= 0, isTrue);
    });

    test('one degree at equator is about 111.2 km', () {
      final distance = LocationHelper.haversineMeters(0, 0, 0, 1);
      expect(distance, closeTo(111195, 100));
    });

    test('Jakarta to Bandung is a known approximate distance', () {
      final distance = LocationHelper.haversineMeters(
        -6.2088,
        106.8456,
        -6.9175,
        107.6191,
      );
      expect(distance / 1000, closeTo(117.5, 2));
    });

    test('distance is symmetric', () {
      final forward = LocationHelper.haversineMeters(-6.2, 106.8, -7.8, 110.4);
      final reverse = LocationHelper.haversineMeters(-7.8, 110.4, -6.2, 106.8);
      expect(forward, closeTo(reverse, 1e-9));
    });

    test('antipodal points are half earth circumference', () {
      final distance = LocationHelper.haversineMeters(0, 0, 0, 180);
      expect(distance, closeTo(20015086.8, 1));
    });
  });
}
