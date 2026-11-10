import 'package:flutter_test/flutter_test.dart';
import 'package:mydarfik/core/format.dart';
import 'package:mydarfik/features/face/face_capture.dart';
import 'package:mydarfik/features/face/face_service.dart';

void main() {
  test('format jarak', () {
    expect(Fmt.jarak(42), '42 m');
    expect(Fmt.jarak(1200), '1,2 km');
  });

  test('LokasiStatus properti sukses dan gagal', () {
    const sukses = LokasiStatus(
      sukses: true,
      nama: 'SDIT Darul Fikri, Sidoarjo',
    );
    expect(sukses.sukses, true);
    expect(sukses.persen, 100);
    expect(sukses.pesan, isNull);

    const gagal = LokasiStatus(
      sukses: false,
      nama: 'SDIT Darul Fikri, Sidoarjo',
      pesan: 'Lokasi tidak sesuai (Di luar jangkauan)',
    );
    expect(gagal.sukses, false);
    expect(gagal.persen, 100);
    expect(gagal.pesan, 'Lokasi tidak sesuai (Di luar jangkauan)');
  });

  test('skor wajah: identik 100, berlawanan 0, ambang 85 sekitar cos 0.71', () {
    final a = FaceService.rataRata([
      [1.0, 0.0, 0.0],
    ]);
    expect(FaceService.skor(a, a), 100);
    expect(FaceService.skor(a, [-1.0, 0.0, 0.0]), 0);
    expect(FaceService.skor([1.0, 0.0], [0.71, 0.704]), closeTo(85, 0.5));
    expect(FaceService.skor([1.0, 0.0], [1.0]), 0, reason: 'panjang beda = tidak cocok');
  });
}
