import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mydarfik/widgets/common.dart';

void main() {
  testWidgets('TandaAbsen menampilkan lokasi palsu dan koreksi mandiri', (
    tester,
  ) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: TandaAbsen(lokasiPalsu: '07:05', koreksiMandiri: ''),
        ),
      ),
    );
    expect(find.text('Lokasi palsu 07:05'), findsOneWidget);
    expect(find.text('Koreksi mandiri'), findsOneWidget);
  });

  testWidgets('TandaAbsen kosong bila tidak ada catatan', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(home: Scaffold(body: TandaAbsen())),
    );
    expect(find.byType(StatusChip), findsNothing);
  });
}
