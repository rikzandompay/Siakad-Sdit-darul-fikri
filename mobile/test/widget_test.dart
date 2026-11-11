import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mydarfik/core/format.dart';
import 'package:mydarfik/features/face/face_service.dart';
import 'package:mydarfik/widgets/common.dart';

void main() {
  test('format jarak', () {
    expect(Fmt.jarak(42), '42 m');
    expect(Fmt.jarak(1200), '1,2 km');
  });

  testWidgets('Ragam tombol design system (Success, Warning, Alert, Tertiary)', (tester) async {
    bool ditekan = false;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Column(
            children: [
              PrimaryButton(label: 'Primary', onPressed: () {}),
              SecondaryButton(label: 'Secondary', onPressed: () {}),
              TertiaryButton(label: 'Tertiary', onPressed: () {}),
              SuccessButton(label: 'Setujui', onPressed: () => ditekan = true),
              WarningButton(label: 'Peringatan', onPressed: () {}),
              AlertButton(label: 'Tolak', onPressed: () {}),
            ],
          ),
        ),
      ),
    );

    expect(find.text('Primary'), findsOneWidget);
    expect(find.text('Secondary'), findsOneWidget);
    expect(find.text('Tertiary'), findsOneWidget);
    expect(find.text('Setujui'), findsOneWidget);
    expect(find.text('Peringatan'), findsOneWidget);
    expect(find.text('Tolak'), findsOneWidget);

    await tester.tap(find.text('Setujui'));
    expect(ditekan, true);
  });

  test('skor wajah: identik 100, berlawanan 0, ambang 70 sekitar cos 0.57', () {
    final a = FaceService.rataRata([
      [1.0, 0.0, 0.0],
    ]);
    expect(FaceService.skor(a, a), 100);
    expect(FaceService.skor(a, [-1.0, 0.0, 0.0]), 0);
    // cos 0.57 mendekati 70% dengan kalibrasi baru (floor=0.15, ceil=0.75)
    expect(FaceService.skor([1.0, 0.0], [0.57, 0.8216]), closeTo(70, 0.5));
    expect(FaceService.skor([1.0, 0.0], [1.0]), 0, reason: 'panjang beda = tidak cocok');
  });
}
