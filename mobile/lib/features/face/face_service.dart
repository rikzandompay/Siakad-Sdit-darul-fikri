import 'dart:io';
import 'dart:math' as math;
import 'dart:typed_data';

import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:image/image.dart' as img;
import 'package:path_provider/path_provider.dart';
import 'package:tflite_flutter/tflite_flutter.dart';

class FaceException implements Exception {
  FaceException(this.message);
  final String message;
  @override
  String toString() => message;
}

/// Pengenalan wajah di perangkat (Android & iOS):
/// - ML Kit: menemukan wajah + mata (untuk liveness kedip).
/// - MobileFaceNet (TFLite, assets/models/mobilefacenet.tflite): mengubah wajah jadi embedding.
/// Foto kamera tidak pernah dikirim ke server; yang tersimpan hanya embedding (template).
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

  /// Margin di sekitar wajah, relatif terhadap sisi kotak wajah.
  static const _marginRel = 0.2;

  /// Embedding (sudah dinormalisasi L2) dari satu foto berisi tepat satu wajah.
  ///
  /// Urutan pemrosesan penting: wajah dipotong dulu memakai koordinat asli dari
  /// ML Kit, baru potongannya di-align. Kalau seluruh foto diputar lebih dulu,
  /// `boundingBox` menjadi tidak valid karena `copyRotate` memperbesar kanvas dan
  /// menggeser posisi wajah (puluhan persen lebar wajah), sehingga yang masuk ke
  /// MobileFaceNet adalah sebagian wajah + latar.
  Future<List<double>> embeddingDariFoto(String path) async {
    final faces = await _detector.processImage(InputImage.fromFilePath(path));
    if (faces.isEmpty) throw FaceException('Wajah tidak terlihat. Pastikan wajah berada di dalam bingkai dan cukup terang.');
    if (faces.length > 1) throw FaceException('Terdeteksi lebih dari satu wajah. Pastikan hanya Anda yang ada di kamera.');

    final decoded = img.decodeImage(await File(path).readAsBytes());
    if (decoded == null) throw FaceException('Foto tidak dapat dibaca. Coba lagi.');
    final foto = img.bakeOrientation(decoded);

    final face = faces.first;
    final box = face.boundingBox;

    // Sisi kotak persegi: cukup memuat seluruh wajah + margin.
    final sisiDiminta = math.max(box.width, box.height) * (1 + 2 * _marginRel);
    if (sisiDiminta < 80) throw FaceException('Wajah terlalu jauh. Dekatkan wajah ke kamera.');

    // Potong dengan koordinat yang masih valid (gambar belum dirotasi).
    var potong = _potongKotak(foto, box.center.dx, box.center.dy, sisiDiminta);
    final sisi = potong.width;

    // Align: putar potongan saja agar garis kedua mata sejajar horizontal.
    // Wajah berada di tengah potongan, jadi rotasi tidak menggesernya.
    final mataKiri = face.landmarks[FaceLandmarkType.leftEye]?.position;
    final mataKanan = face.landmarks[FaceLandmarkType.rightEye]?.position;
    if (mataKiri != null && mataKanan != null) {
      final dx = mataKanan.x - mataKiri.x;
      final dy = mataKanan.y - mataKiri.y;
      final sudut = math.atan2(dy, dx);
      if (sudut.abs() > 0.02) {
        final diputar = img.copyRotate(potong, angle: -sudut * 180 / math.pi, interpolation: img.Interpolation.linear);
        // Rotasi memperbesar kanvas; ambil ulang kotak seukuran asli dari tengah.
        potong = img.copyCrop(
          diputar,
          x: (diputar.width - sisi) ~/ 2,
          y: (diputar.height - sisi) ~/ 2,
          width: sisi,
          height: sisi,
        );
      }
    }

    final interpreter = await _model();
    final inShape = interpreter.getInputTensor(0).shape; // [1, 112, 112, 3]
    final outShape = interpreter.getOutputTensor(0).shape; // [1, 192]
    final size = inShape[1];
    // Interpolasi linear: default package ini `nearest`, yang menimbulkan aliasing
    // parah saat 400+ px diperkecil ke 112 px dan merusak embedding.
    final wajah = img.copyResize(potong, width: size, height: size, interpolation: img.Interpolation.linear);

    final input = Float32List(size * size * 3);
    var i = 0;
    for (final p in wajah) {
      input[i++] = (p.r - 127.5) / 128;
      input[i++] = (p.g - 127.5) / 128;
      input[i++] = (p.b - 127.5) / 128;
    }
    final output = List.generate(1, (_) => List.filled(outShape.last, 0.0));
    interpreter.run(input.reshape([1, size, size, 3]), output);
    return _normalisasi(output.first);
  }

  /// Potong kotak persegi berpusat di [cx],[cy]. Ukuran dibatasi agar kotak tetap
  /// berada di dalam gambar, sehingga wajah selalu berada tepat di tengah hasil.
  static img.Image _potongKotak(img.Image src, double cx, double cy, double sisiDiminta) {
    final maks = 2 * math.min(math.min(cx, cy), math.min(src.width - cx, src.height - cy));
    final batas = math.min(src.width, src.height);
    final s = math.min(sisiDiminta, maks).floor().clamp(1, batas).toInt();
    final x = (cx - s / 2).round().clamp(0, src.width - s).toInt();
    final y = (cy - s / 2).round().clamp(0, src.height - s).toInt();
    return img.copyCrop(src, x: x, y: y, width: s, height: s);
  }

  /// Embedding dari foto profil (acuan identitas saat daftar wajah). Pesannya menyebut foto profil,
  /// bukan kamera, karena yang perlu diperbaiki guru adalah fotonya.
  Future<List<double>> embeddingDariFotoProfil(List<int> bytes) async {
    final dir = await getTemporaryDirectory();
    final file = File('${dir.path}/foto_acuan_${DateTime.now().microsecondsSinceEpoch}');
    try {
      await file.writeAsBytes(bytes, flush: true);
      return await embeddingDariFoto(file.path);
    } on FaceException {
      throw FaceException('Wajah pada foto profil tidak terbaca. Ajukan foto lain yang jelas, menghadap depan, dan hanya berisi satu wajah.');
    } finally {
      if (await file.exists()) await file.delete();
    }
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
