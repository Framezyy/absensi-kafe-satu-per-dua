import 'package:flutter_test/flutter_test.dart';
import 'package:kafe_satuperdua/features/face_enroll/data/api_face_repository.dart';

void main() {
  test('face result requires match and one-time proof', () {
    final result = FaceResult.fromJson({
      'match': true,
      'similarity': '0.91',
      'verification_token': 'proof-123',
      'expires_at': '2026-07-21T17:00:00+07:00',
    });

    expect(result.success, isTrue);
    expect(result.similarity, 0.91);
    expect(result.verificationToken, 'proof-123');
  });

  test('matched response without proof is rejected safely', () {
    final result = FaceResult.fromJson({'match': true, 'similarity': null});

    expect(result.success, isFalse);
    expect(result.similarity, isNull);
  });
}
