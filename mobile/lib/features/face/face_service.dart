import 'dart:io';
import 'dart:math' as math;
import 'dart:typed_data';

import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:image/image.dart' as img;
import 'package:tflite_flutter/tflite_flutter.dart';

class FaceException implements Exception {
  FaceException(this.message);
  final String message;
  @override
  String toString() => message;
}

/// Pengenalan wajah di perangkat (Android & iOS):
/// - ML Kit: menemukan wajah + mata (liveness kedip dan posisi mata untuk pemotongan).
/// - MobileFaceNet (TFLite, assets/models/mobilefacenet.tflite): mengubah wajah jadi embedding.
/// Foto kamera tidak pernah dikirim ke server; hanya embedding-nya. Acuan (dari foto profil)
/// dibuat dan disimpan server, tidak pernah dikirim ke HP.
class FaceService {
  FaceService._();
  static final instance = FaceService._();

  Interpreter? _interpreter;
  final FaceDetector _detector = FaceDetector(
    options: FaceDetectorOptions(
      performanceMode: FaceDetectorMode.accurate,
      enableLandmarks: true,
      minFaceSize: 0.2,
    ),
  );

  /// Kalibrasi skor: cosine similarity MobileFaceNet -> persen.
  /// cos 0.15 = 0%, cos 0.75 = 100%. Lebih lebar dari sebelumnya agar skor
  /// tidak terlalu "ketat" saat kondisi foto profil vs kamera berbeda jauh.
  static const _cosNol = 0.15;
  static const _cosPenuh = 0.75;

  Future<Interpreter> _model() async {
    return _interpreter ??= await Interpreter.fromAsset('assets/models/mobilefacenet.tflite', options: InterpreterOptions()..threads = 2);
  }

  /// Embedding (sudah dinormalisasi L2) dari satu foto kamera berisi tepat satu wajah.
  ///
  /// Acuan wajah dibuat di server dari foto profil (scripts/wajah/embedding.py) dengan
  /// pemotongan yang sama persis ([potongWajah]); kedua sisi hanya memakai posisi mata,
  /// bukan kotak wajah, karena kotak wajah ML Kit dan detektor server berbeda.
  Future<List<double>> embeddingDariFoto(String path) async {
    final faces = await _detector.processImage(InputImage.fromFilePath(path));
    if (faces.isEmpty) throw FaceException('Wajah tidak terlihat. Pastikan wajah berada di dalam bingkai dan cukup terang.');
    if (faces.length > 1) throw FaceException('Terdeteksi lebih dari satu wajah. Pastikan hanya Anda yang ada di kamera.');

    final decoded = img.decodeImage(await File(path).readAsBytes());
    if (decoded == null) throw FaceException('Foto tidak dapat dibaca. Coba lagi.');
    final foto = img.bakeOrientation(decoded);

    final face = faces.first;
    final mataA = face.landmarks[FaceLandmarkType.leftEye]?.position;
    final mataB = face.landmarks[FaceLandmarkType.rightEye]?.position;
    if (mataA == null || mataB == null) throw FaceException('Mata tidak terlihat jelas. Hadapkan wajah lurus ke kamera tanpa kacamata hitam.');
    final a = math.Point<double>(mataA.x.toDouble(), mataA.y.toDouble());
    final b = math.Point<double>(mataB.x.toDouble(), mataB.y.toDouble());
    if (a.distanceTo(b) < minJarakMata) throw FaceException('Wajah terlalu jauh. Dekatkan wajah ke kamera.');

    final interpreter = await _model();
    final outShape = interpreter.getOutputTensor(0).shape; // [1, 192]
    final input = potongWajah(foto, a, b);
    final output = List.generate(1, (_) => List.filled(outShape.last, 0.0));
    interpreter.run(input.reshape([1, ukuranInput, ukuranInput, 3]), output);
    return _normalisasi(output.first);
  }

  // Pemotongan wajah: HARUS sama dengan scripts/wajah/embedding.py (potong_wajah).
  static const sisiMata = 3.0; // sisi potong / jarak mata
  static const turunMata = 0.4; // pusat potong di bawah titik tengah mata (x jarak mata)
  static const ukuranAntara = 448;
  static const ukuranInput = 112;
  static const minJarakMata = 20.0;

  /// Potongan wajah 112x112 RGB, dinormalisasi (x - 127.5) / 128, urutan [baris][kolom][rgb].
  ///
  /// Mata diurutkan menurut x (bukan label kiri/kanan ML Kit, yang memakai sudut pandang orang
  /// di foto), wajah diluruskan pada garis mata, sisi potong 3 x jarak mata dengan pusat 0.4 x
  /// jarak mata di bawah titik tengah mata. Disampel bilinear 448x448 (tepi direplikasi) lalu
  /// dirata-rata per blok 4x4 agar tidak terjadi aliasing saat diperkecil.
  static Float32List potongWajah(img.Image foto, math.Point<double> mataA, math.Point<double> mataB) {
    final kiri = mataA.x <= mataB.x ? mataA : mataB;
    final kanan = identical(kiri, mataA) ? mataB : mataA;
    final dx = kanan.x - kiri.x, dy = kanan.y - kiri.y;
    final jarak = math.sqrt(dx * dx + dy * dy);
    if (jarak < 1e-6) throw FaceException('Posisi mata tidak valid.');
    final cos = dx / jarak, sin = dy / jarak;
    final cx = (kiri.x + kanan.x) / 2 - sin * turunMata * jarak;
    final cy = (kiri.y + kanan.y) / 2 + cos * turunMata * jarak;
    final skala = sisiMata * jarak / ukuranAntara;
    final w = foto.width, h = foto.height;

    // Salin piksel sekali ke larik datar agar sampling cepat.
    final px = Float64List(w * h * 3);
    var k = 0;
    for (var y = 0; y < h; y++) {
      for (var x = 0; x < w; x++) {
        final p = foto.getPixel(x, y);
        px[k++] = p.r.toDouble();
        px[k++] = p.g.toDouble();
        px[k++] = p.b.toDouble();
      }
    }

    const blok = ukuranAntara ~/ ukuranInput;
    final jumlah = Float64List(ukuranInput * ukuranInput * 3);
    const tengah = (ukuranAntara - 1) / 2;
    for (var r = 0; r < ukuranAntara; r++) {
      final fy = (r - tengah) * skala;
      final baris = (r ~/ blok) * ukuranInput;
      for (var c = 0; c < ukuranAntara; c++) {
        final fx = (c - tengah) * skala;
        final sx = (cx + cos * fx - sin * fy).clamp(0.0, w - 1.0);
        final sy = (cy + sin * fx + cos * fy).clamp(0.0, h - 1.0);
        final x0 = sx.floor(), y0 = sy.floor();
        final x1 = math.min(x0 + 1, w - 1), y1 = math.min(y0 + 1, h - 1);
        final ax = sx - x0, ay = sy - y0;
        final i00 = (y0 * w + x0) * 3, i01 = (y0 * w + x1) * 3, i10 = (y1 * w + x0) * 3, i11 = (y1 * w + x1) * 3;
        final o = (baris + c ~/ blok) * 3;
        for (var ch = 0; ch < 3; ch++) {
          final atas = px[i00 + ch] * (1 - ax) + px[i01 + ch] * ax;
          final bawah = px[i10 + ch] * (1 - ax) + px[i11 + ch] * ax;
          jumlah[o + ch] += atas * (1 - ay) + bawah * ay;
        }
      }
    }
    const n = blok * blok;
    return Float32List.fromList([for (final v in jumlah) (v / n - 127.5) / 128]);
  }

  /// Rata-rata beberapa embedding (saat pendaftaran) agar lebih stabil.
  static List<double> rataRata(List<List<double>> daftar) {
    final hasil = List.filled(daftar.first.length, 0.0);
    for (final e in daftar) {
      for (var i = 0; i < e.length; i++) {
        hasil[i] += e[i];
      }
    }
    return _normalisasi(hasil);
  }

  /// Skor kecocokan 0–100.
  static double skor(List<double> a, List<double> b) {
    if (a.length != b.length) return 0;
    var dot = 0.0;
    for (var i = 0; i < a.length; i++) {
      dot += a[i] * b[i];
    }
    final p = ((dot - _cosNol) / (_cosPenuh - _cosNol)).clamp(0.0, 1.0) * 100;
    return double.parse(p.toStringAsFixed(1));
  }

  static List<double> _normalisasi(List<double> v) {
    final n = math.sqrt(v.fold<double>(0, (s, x) => s + x * x));
    return n == 0 ? v : [for (final x in v) x / n];
  }

  FaceDetector get detector => _detector;
}
