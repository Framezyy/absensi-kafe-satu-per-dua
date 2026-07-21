// Smoke test Phase 1.1 — verifikasi app boot dan layar login muncul.
//
// Uji router conditional (login → enroll → home) akan ditambah saat
// alur masing-masing layar selesai di Phase 1.2.

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'package:kafe_satuperdua/main.dart';

void main() {
  setUpAll(() async {
    // Inisialisasi locale id_ID supaya widget yang pakai DateFormat('id_ID')
    // (mis. ProfilePage) tidak crash saat di-test.
    await initializeDateFormatting('id_ID');
  });

  testWidgets('App boot menampilkan layar Login', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: KafeSatuPerduaApp()));
    // Login memiliki animasi berulang, jadi jangan menunggu seluruh animasi idle.
    for (var i = 0; i < 20 && find.text('Username').evaluate().isEmpty; i++) {
      await tester.pump(const Duration(milliseconds: 100));
    }

    expect(find.text('Selamat Datang'), findsOneWidget);
    expect(find.text('Username'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
  });
}
