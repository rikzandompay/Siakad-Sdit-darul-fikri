import 'dart:convert';
import 'dart:io';
import 'dart:math' as math;

import 'package:flutter_test/flutter_test.dart';
import 'package:image/image.dart' as img;
import 'package:mydarfik/features/face/face_service.dart';

/// Pemotongan wajah di HP harus sama dengan server (scripts/wajah/embedding.py), karena acuan
/// wajah dibuat server dari foto profil dan dibandingkan dengan embedding kamera dari HP.
/// Golden dibuat dengan: python scripts/wajah/embedding.py FOTO --mata ... --potongan
void main() {
  final foto = img.decodePng(File('test/fixtures/wajah_sintetis.png').readAsBytesSync())!;
  final golden = jsonDecode(File('test/fixtures/potongan_wajah.json').readAsStringSync()) as Map<String, dynamic>;

  for (final kasus in (golden['kasus'] as List).cast<Map<String, dynamic>>()) {
    final m = (kasus['mata'] as List).cast<num>().map((e) => e.toDouble()).toList();
    test('potongan sama dengan server untuk mata $m', () {
      final hasil = FaceService.potongWajah(foto, math.Point(m[0], m[1]), math.Point(m[2], m[3]));
      expect(hasil.length, kasus['panjang']);
      final tiap = kasus['tiap'] as int;
      final nilai = (kasus['nilai'] as List).cast<num>();
      var maks = 0.0;
      for (var i = 0; i < nilai.length; i++) {
        maks = math.max(maks, (hasil[i * tiap] - nilai[i]).abs());
      }
      expect(maks, lessThan(1e-4), reason: 'selisih terbesar $maks');
    });
  }

  test('urutan mata tidak berpengaruh (label kiri/kanan ML Kit diabaikan)', () {
    final a = FaceService.potongWajah(foto, const math.Point(300.0, 200.0), const math.Point(380.0, 215.0));
    final b = FaceService.potongWajah(foto, const math.Point(380.0, 215.0), const math.Point(300.0, 200.0));
    expect(a, b);
  });
}
