import 'dart:async';
import 'dart:io';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';

import '../../core/theme.dart';

/// Kamera depan + cek kehidupan (kedip mata) lalu mengambil foto.
/// Setelah lolos liveness, [jumlahFoto] foto diambil dan dikembalikan lewat [onSelesai].
class FaceCapture extends StatefulWidget {
  const FaceCapture({super.key, required this.onSelesai, required this.onGagal, this.jumlahFoto = 1});

  final Future<void> Function(List<String> fotoPaths) onSelesai;
  final void Function(String pesan) onGagal;
  final int jumlahFoto;

  @override
  State<FaceCapture> createState() => _FaceCaptureState();
}

enum _Tahap { memulai, cariWajah, kedip, mengambil, memproses }

class _FaceCaptureState extends State<FaceCapture> {
  CameraController? _camera;
  CameraDescription? _desc;
  _Tahap _tahap = _Tahap.memulai;
  bool _sibuk = false;
  bool _mataTertutup = false;
  double _progress = 0;
  Timer? _batasWaktu;
  final _detector = FaceDetector(options: FaceDetectorOptions(enableClassification: true, performanceMode: FaceDetectorMode.fast, minFaceSize: 0.25));

  @override
  void initState() {
    super.initState();
    _mulai();
  }

  Future<void> _mulai() async {
    try {
      final kamera = await availableCameras();
      _desc = kamera.firstWhere((c) => c.lensDirection == CameraLensDirection.front, orElse: () => kamera.first);
      final c = CameraController(
        _desc!,
        ResolutionPreset.high,
        enableAudio: false,
        imageFormatGroup: Platform.isAndroid ? ImageFormatGroup.nv21 : ImageFormatGroup.bgra8888,
      );
      await c.initialize();
      await c.lockCaptureOrientation(DeviceOrientation.portraitUp);
      if (!mounted) {
        await c.dispose();
        return;
      }
      setState(() {
        _camera = c;
        _tahap = _Tahap.cariWajah;
      });
      await c.startImageStream(_olahFrame);
      _batasWaktu = Timer(const Duration(seconds: 25), () {
        if (mounted && _tahap.index < _Tahap.mengambil.index) widget.onGagal('Waktu habis. Pastikan wajah terlihat jelas lalu kedipkan mata sekali.');
      });
    } on CameraException catch (e) {
      widget.onGagal(e.code == 'CameraAccessDenied' ? 'Izin kamera ditolak. Aktifkan izin kamera untuk MyDarfik di pengaturan HP.' : 'Kamera tidak dapat dibuka.');
    }
  }

  Future<void> _olahFrame(CameraImage frame) async {
    if (_sibuk || _tahap.index >= _Tahap.mengambil.index) return;
    _sibuk = true;
    try {
      final input = _toInputImage(frame);
      if (input == null) return;
      final faces = await _detector.processImage(input);
      if (!mounted) return;
      if (faces.length != 1) {
        setState(() {
          _tahap = _Tahap.cariWajah;
          _progress = 0;
        });
        return;
      }
      final f = faces.first;
      final kiri = f.leftEyeOpenProbability ?? 1;
      final kanan = f.rightEyeOpenProbability ?? 1;
      if (_tahap == _Tahap.cariWajah) {
        setState(() {
          _tahap = _Tahap.kedip;
          _progress = 0.4;
        });
      } else if (!_mataTertutup && kiri < 0.25 && kanan < 0.25) {
        _mataTertutup = true;
        setState(() => _progress = 0.8);
      } else if (_mataTertutup && kiri > 0.7 && kanan > 0.7) {
        setState(() => _progress = 1);
        await _ambilFoto();
      }
    } catch (e) {
      debugPrint('Face frame error: $e');
    } finally {
      _sibuk = false;
    }
  }

  Future<void> _ambilFoto() async {
    _batasWaktu?.cancel();
    final c = _camera!;
    setState(() => _tahap = _Tahap.mengambil);
    await c.stopImageStream();
    final paths = <String>[];
    for (var i = 0; i < widget.jumlahFoto; i++) {
      paths.add((await c.takePicture()).path);
      if (i < widget.jumlahFoto - 1) await Future.delayed(const Duration(milliseconds: 250));
    }
    if (!mounted) return;
    setState(() => _tahap = _Tahap.memproses);
    await widget.onSelesai(paths);
  }

  static const _rotasi = {DeviceOrientation.portraitUp: 0, DeviceOrientation.landscapeLeft: 90, DeviceOrientation.portraitDown: 180, DeviceOrientation.landscapeRight: 270};

  InputImage? _toInputImage(CameraImage frame) {
    final desc = _desc!;
    InputImageRotation? rotation;
    if (Platform.isIOS) {
      rotation = InputImageRotationValue.fromRawValue(desc.sensorOrientation);
    } else {
      final kompensasi = _rotasi[_camera!.value.deviceOrientation];
      if (kompensasi == null) return null;
      rotation = InputImageRotationValue.fromRawValue(desc.lensDirection == CameraLensDirection.front
          ? (desc.sensorOrientation + kompensasi) % 360
          : (desc.sensorOrientation - kompensasi + 360) % 360);
    }
    final format = InputImageFormatValue.fromRawValue(frame.format.raw);
    if (rotation == null || format == null || frame.planes.length != 1) return null;
    final plane = frame.planes.first;
    return InputImage.fromBytes(
      bytes: plane.bytes,
      metadata: InputImageMetadata(size: Size(frame.width.toDouble(), frame.height.toDouble()), rotation: rotation, format: format, bytesPerRow: plane.bytesPerRow),
    );
  }

  @override
  void dispose() {
    _batasWaktu?.cancel();
    _camera?.dispose();
    _detector.close();
    super.dispose();
  }

  String get _petunjuk => switch (_tahap) {
        _Tahap.memulai => 'Membuka kamera…',
        _Tahap.cariWajah => 'Posisikan wajah di dalam bingkai',
        _Tahap.kedip => 'Tetap diam, kedipkan mata satu kali',
        _Tahap.mengambil => 'Mengambil foto…',
        _Tahap.memproses => 'Mencocokkan wajah…',
      };

  @override
  Widget build(BuildContext context) {
    final c = _camera;
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: AspectRatio(
          aspectRatio: 0.82,
          child: Stack(fit: StackFit.expand, children: [
            Container(color: AppColors.dark),
            if (c != null && c.value.isInitialized)
              FittedBox(fit: BoxFit.cover, child: SizedBox(width: c.value.previewSize!.height, height: c.value.previewSize!.width, child: CameraPreview(c))),
            CustomPaint(painter: _OvalPainter(color: _progress >= 1 ? AppColors.ok600 : (_tahap == _Tahap.kedip ? AppColors.ok600 : AppColors.brand200))),
            Positioned(
              left: 16,
              right: 16,
              bottom: 20,
              child: Column(children: [
                if (_tahap.index >= _Tahap.mengambil.index) const Padding(padding: EdgeInsets.only(bottom: 8), child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))),
                Text(_petunjuk, style: AppText.label.copyWith(color: Colors.white), textAlign: TextAlign.center),
              ]),
            ),
          ]),
        ),
      ),
      const SizedBox(height: 12),
      Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.line)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(child: Text('Verifikasi kehidupan (liveness)', style: AppText.captionBold)),
            Text('${(_progress * 100).round()}%', style: AppText.captionBold.copyWith(color: AppColors.brand700)),
          ]),
          const SizedBox(height: 8),
          ClipRRect(borderRadius: BorderRadius.circular(4), child: LinearProgressIndicator(value: _progress, minHeight: 6, color: AppColors.brand700, backgroundColor: AppColors.line)),
        ]),
      ),
    ]);
  }
}

class _OvalPainter extends CustomPainter {
  _OvalPainter({required this.color});
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Rect.fromCenter(center: Offset(size.width / 2, size.height * 0.44), width: size.width * 0.62, height: size.height * 0.62);
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 4;
    final path = Path()..addOval(rect);
    // Garis putus-putus.
    for (final m in path.computeMetrics()) {
      for (double d = 0; d < m.length; d += 18) {
        canvas.drawPath(m.extractPath(d, d + 10), paint);
      }
    }
  }

  @override
  bool shouldRepaint(_OvalPainter old) => old.color != color;
}
