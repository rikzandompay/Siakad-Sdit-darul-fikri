import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mydarfik/widgets/common.dart';

void main() {
  test('semua berkas ikon menu tersedia', () {
    const nama = [
      'presensi_kelas',
      'sholat',
      'rekap_absensi',
      'rekap_sholat',
      'rekap_nilai',
      'rekap_guru',
      'rekap_absen',
      'tahun_ajaran',
      'absen_saya',
      'tugas',
      'nilai',
      'surat_izin',
      'konseling',
      'data_guru',
      'data_siswa',
      'kelas',
      'mata_pelajaran',
      'info_sekolah',
      'lokasi_jam',
      'pengampu_konseling',
      'akun_wali',
      'jadwal',
      'jadwal_guru',
      'profil',
      'rapor',
      'kartu',
      'foto',
      'sandi',
      'panduan',
      'bantuan',
    ];
    for (final n in nama) {
      expect(
        File('assets/icons/$n.png').existsSync(),
        isTrue,
        reason: 'ikon $n hilang',
      );
    }
  });

  testWidgets('IkonMenu tampil sebagai lingkaran dengan gambar', (
    tester,
  ) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(body: Center(child: IkonMenu('presensi_kelas'))),
      ),
    );
    expect(find.byType(Image), findsOneWidget);
    final box = tester.getSize(find.byType(IkonMenu));
    expect(box.width, 42);
    expect(box.height, 42);
  });
}
