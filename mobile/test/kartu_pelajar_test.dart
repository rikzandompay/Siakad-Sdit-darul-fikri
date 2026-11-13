import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mydarfik/widgets/kartu_pelajar_desain.dart';

void main() {
  for (final lebar in [320.0, 360.0, 720.0]) {
    testWidgets(
      'kartu pelajar depan & belakang muat tanpa overflow di lebar $lebar',
      (tester) async {
        await tester.pumpWidget(
          MaterialApp(
            home: Scaffold(
              body: SingleChildScrollView(
                child: Center(
                  child: SizedBox(
                    width: lebar,
                    child: const Column(
                      children: [
                        KartuPelajarDepan(
                          nama: 'Afif Maulana Shaid Al-Fatih Pratama Putra Wijaya',
                          nis: '202101001',
                          kelas: '1',
                          alamat: 'Jl. Sungai Durian Laut, Kec. Sungai Raya, Kab. Kubu Raya, Kalimantan Barat',
                          inisial: 'AM',
                          tahunAjaran: '2026/2027 - Ganjil',
                          qr: 'DARFIK-abc123',
                        ),
                        SizedBox(height: 12),
                        KartuPelajarBelakang(kepalaSekolah: 'Dewi Quraisyinta'),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        );
        await tester.pump();
        expect(find.text('KARTU PELAJAR'), findsNWidgets(2));
        expect(find.text('QR SHOLAT'), findsOneWidget);
        expect(find.text('Dewi Quraisyinta'), findsOneWidget);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
