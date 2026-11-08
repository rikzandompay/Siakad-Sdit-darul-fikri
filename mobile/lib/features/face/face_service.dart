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
/// - ML Kit: menemukan wajah + mata (untuk liveness kedip).
/// - MobileFaceNet (TFLite, assets/models/mobilefacenet.tflite): mengubah wajah jadi embedding.
/// Foto wajah tidak pernah dikirim ke server; yang tersimpan hanya embedding (template).
class FaceService {
  FaceService._();
  static final instance = FaceService._();

  Interpreter? _interpreter;
  final FaceDetector _detector = FaceDetector(
    options: FaceDetectorOptions(performanceMode: FaceDetectorMode.accurate, minFaceSize: 0.2),
  );

  /// Kalibrasi skor: cosine similarity MobileFaceNet -> persen.
  /// cos 0.20 → 0%, cos 0.71 → 85% (ambang server default), cos ≥ 0.80 → 100%.
  /// Sesuaikan setelah uji coba di HP sekolah bila terlalu ketat/longgar.
  static const _cosNol = 0.20;
  static const _cosPenuh = 0.80;

  Future<Interpreter> _model() async {
    return _interpreter ??= await Interpreter.fromAsset('assets/models/mobilefacenet.tflite', options: InterpreterOptions()..threads = 2);
  }

  /// Embedding (sudah dinormalisasi L2) dari satu foto berisi tepat satu wajah.
  Future<List<double>> embeddingDariFoto(String path) async {
    final faces = await _detector.processImage(InputImage.fromFilePath(path));
    if (faces.isEmpty) throw FaceException('Wajah tidak terlihat. Pastikan wajah berada di dalam bingkai dan cukup terang.');
    if (faces.length > 1) throw FaceException('Terdeteksi lebih dari satu wajah. Pastikan hanya Anda yang ada di kamera.');

    final decoded = img.decodeImage(await File(path).readAsBytes());
    if (decoded == null) throw FaceException('Foto tidak dapat dibaca. Coba lagi.');
    final foto = img.bakeOrientation(decoded);

    final box = faces.first.boundingBox;
    final margin = box.width * 0.1;
    final x = math.max(0, (box.left - margin).round());
    final y = math.max(0, (box.top - margin).round());
    final w = math.min(foto.width - x, (box.width + margin * 2).round());
    final h = math.min(foto.height - y, (box.height + margin * 2).round());
    if (w < 80 || h < 80) throw FaceException('Wajah terlalu jauh. Dekatkan wajah ke kamera.');

    final interpreter = await _model();
    final inShape = interpreter.getInputTensor(0).shape; // [1, 112, 112, 3]
    final outShape = interpreter.getOutputTensor(0).shape; // [1, 192]
    final size = inShape[1];
    final wajah = img.copyResize(img.copyCrop(foto, x: x, y: y, width: w, height: h), width: size, height: size);

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
