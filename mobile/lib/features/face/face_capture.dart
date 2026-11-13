import 'dart:io';
import 'dart:math' as math;

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';

import '../../core/theme.dart';

/// Hasil pencocokan yang ditampilkan lewat warna bingkai: hijau cocok, merah tidak cocok.
enum HasilWajah { belum, cocok, tidakCocok }

// Bingkai oval, relatif terhadap area kamera (lebar : tinggi = 0.82 : 1).
const _rasio = 0.82;
const _ovalCy = 0.44;
const _ovalR = 0.31;

/// Peringatan posisi wajah, atau null bila tepat satu wajah berada di tengah bingkai oval
/// dengan ukuran dan arah yang pas.
///
/// [kotak] memakai koordinat gambar yang sudah ditegakkan (seperti hasil ML Kit), sedangkan
/// [ukuranMentah] adalah ukuran frame kamera sebelum diputar ([diputar] = rotasi 90/270).
/// Pratinjau ditampilkan BoxFit.cover pada area 0.82 x 1 dan dicerminkan (kamera depan).
@visibleForTesting
String? cekPosisiWajah({
  required int jumlahWajah,
  Rect? kotak,
  required Size ukuranMentah,
  required bool diputar,
  double eulerY = 0,
  double eulerZ = 0,
}) {
  if (jumlahWajah == 0 || kotak == null && jumlahWajah == 1)
    return 'Wajah tidak ditemukan';
  if (jumlahWajah > 1) return 'Terlihat lebih dari satu wajah';

  final iw = diputar ? ukuranMentah.height : ukuranMentah.width;
  final ih = diputar ? ukuranMentah.width : ukuranMentah.height;
  final s = math.max(_rasio / iw, 1 / ih);
  final ox = (_rasio - iw * s) / 2;
  final oy = (1 - ih * s) / 2;
  final fx = ox + (iw - kotak!.center.dx) * s;
  final fy = oy + kotak.center.dy * s;
  final lebar = kotak.width * s;

  const rx = _rasio * _ovalR;
  const ry = _ovalR;
  final dx = (fx - _rasio / 2) / rx;
  final dy = (fy - _ovalCy) / ry;
  if (dx * dx + dy * dy > 0.3) return 'Masukkan wajah ke dalam bingkai';
  if (lebar < rx * 2 * 0.5) return 'Dekatkan wajah ke kamera';
  if (lebar > rx * 2 * 1.25) return 'Jauhkan sedikit wajah dari kamera';
  if (eulerY.abs() > 20 || eulerZ.abs() > 20)
    return 'Hadapkan wajah lurus ke kamera';
  return null;
}

/// Kamera depan + cek kehidupan (kedip mata) lalu mengambil foto.
///
/// Kedipan hanya dihitung saat tepat satu wajah berada di dalam bingkai oval; bila wajah
/// tidak ditemukan, keluar bingkai, terlalu jauh/dekat, atau miring, muncul peringatan dan
/// cek kehidupan diulang. Setelah lolos, [jumlahFoto] foto diambil dan dikirim lewat [onSelesai].
/// Hasil pencocokan ditampilkan lewat [hasil] tanpa pindah halaman.
class FaceCapture extends StatefulWidget {
  const FaceCapture({
    super.key,
    required this.onSelesai,
    required this.onGagal,
    this.jumlahFoto = 1,
    this.hasil = HasilWajah.belum,
    this.pesanProses,
  });

  final Future<void> Function(List<String> fotoPaths) onSelesai;
  final void Function(String pesan) onGagal;
  final int jumlahFoto;
  final HasilWajah hasil;

  /// Teks di bawah bingkai saat foto sudah diambil (mis. "Menunggu lokasi…").
  final String? pesanProses;

  @override
  State<FaceCapture> createState() => _FaceCaptureState();
}

enum _Tahap { memulai, posisikan, kedip, mengambil, memproses }

class _FaceCaptureState extends State<FaceCapture> {
  CameraController? _camera;
  CameraDescription? _desc;
  _Tahap _tahap = _Tahap.memulai;
  bool _sibuk = false;
  bool _mataTertutup = false;
  double _progress = 0;

  /// Peringatan posisi wajah (null = wajah sudah pas di bingkai).
  String? _peringatan = 'Wajah tidak ditemukan';

  final _detector = FaceDetector(
    options: FaceDetectorOptions(
      enableClassification: true,
      performanceMode: FaceDetectorMode.fast,
      minFaceSize: 0.15,
    ),
  );

  @override
  void initState() {
    super.initState();
    _mulai();
  }

  Future<void> _mulai() async {
    try {
      final kamera = await availableCameras();
      _desc = kamera.firstWhere(
        (c) => c.lensDirection == CameraLensDirection.front,
        orElse: () => kamera.first,
      );
      final c = CameraController(
        _desc!,
        ResolutionPreset.high,
        enableAudio: false,
        imageFormatGroup: Platform.isAndroid
            ? ImageFormatGroup.nv21
            : ImageFormatGroup.bgra8888,
      );
      await c.initialize();
      await c.lockCaptureOrientation(DeviceOrientation.portraitUp);
      if (!mounted) {
        await c.dispose();
        return;
      }
      setState(() {
        _camera = c;
        _tahap = _Tahap.posisikan;
      });
      await c.startImageStream(_olahFrame);
    } on CameraException catch (e) {
      widget.onGagal(
        e.code == 'CameraAccessDenied'
            ? 'Izin kamera ditolak. Aktifkan izin kamera untuk MyDarfik di pengaturan HP.'
            : 'Kamera tidak dapat dibuka.',
      );
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

      final peringatan = _cekPosisi(faces, input.metadata!);
      if (peringatan != null) {
        // Wajah hilang/keluar bingkai di tengah cek kehidupan: ulangi dari awal.
        setState(() {
          _peringatan = peringatan;
          _tahap = _Tahap.posisikan;
          _progress = 0;
          _mataTertutup = false;
        });
        return;
      }

      final f = faces.first;
      final kiri = f.leftEyeOpenProbability ?? 1;
      final kanan = f.rightEyeOpenProbability ?? 1;
      if (_tahap == _Tahap.posisikan) {
        setState(() {
          _peringatan = null;
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

  String? _cekPosisi(List<Face> faces, InputImageMetadata meta) {
    final f = faces.length == 1 ? faces.first : null;
    return cekPosisiWajah(
      jumlahWajah: faces.length,
      kotak: f?.boundingBox,
      ukuranMentah: meta.size,
      diputar:
          meta.rotation == InputImageRotation.rotation90deg ||
          meta.rotation == InputImageRotation.rotation270deg,
      eulerY: f?.headEulerAngleY ?? 0,
      eulerZ: f?.headEulerAngleZ ?? 0,
    );
  }

  Future<void> _ambilFoto() async {
    final c = _camera!;
    setState(() => _tahap = _Tahap.mengambil);
    await c.stopImageStream();
    final paths = <String>[];
    for (var i = 0; i < widget.jumlahFoto; i++) {
      paths.add((await c.takePicture()).path);
      if (i < widget.jumlahFoto - 1)
        await Future.delayed(const Duration(milliseconds: 250));
    }
    if (!mounted) return;
    setState(() => _tahap = _Tahap.memproses);
    await widget.onSelesai(paths);
  }

  static const _rotasi = {
    DeviceOrientation.portraitUp: 0,
    DeviceOrientation.landscapeLeft: 90,
    DeviceOrientation.portraitDown: 180,
    DeviceOrientation.landscapeRight: 270,
  };

  InputImage? _toInputImage(CameraImage frame) {
    final desc = _desc!;
    InputImageRotation? rotation;
    if (Platform.isIOS) {
      rotation = InputImageRotationValue.fromRawValue(desc.sensorOrientation);
    } else {
      final kompensasi = _rotasi[_camera!.value.deviceOrientation];
      if (kompensasi == null) return null;
      rotation = InputImageRotationValue.fromRawValue(
        desc.lensDirection == CameraLensDirection.front
            ? (desc.sensorOrientation + kompensasi) % 360
            : (desc.sensorOrientation - kompensasi + 360) % 360,
      );
    }
    final format = InputImageFormatValue.fromRawValue(frame.format.raw);
    if (rotation == null || format == null || frame.planes.length != 1)
      return null;
    final plane = frame.planes.first;
    return InputImage.fromBytes(
      bytes: plane.bytes,
      metadata: InputImageMetadata(
        size: Size(frame.width.toDouble(), frame.height.toDouble()),
        rotation: rotation,
        format: format,
        bytesPerRow: plane.bytesPerRow,
      ),
    );
  }

  @override
  void dispose() {
    _camera?.dispose();
    _detector.close();
    super.dispose();
  }

  String get _petunjuk => switch (widget.hasil) {
    HasilWajah.cocok => 'Wajah cocok',
    HasilWajah.tidakCocok => 'Wajah tidak sesuai',
    HasilWajah.belum => switch (_tahap) {
      _Tahap.memulai => 'Membuka kamera…',
      _Tahap.posisikan => 'Posisikan wajah di dalam bingkai',
      _Tahap.kedip => 'Tetap diam, kedipkan mata satu kali',
      _Tahap.mengambil => 'Mengambil foto…',
      _Tahap.memproses => widget.pesanProses ?? 'Mencocokkan wajah…',
    },
  };

  @override
  Widget build(BuildContext context) {
    final c = _camera;
    final adaHasil = widget.hasil != HasilWajah.belum;
    final peringatan = !adaHasil && _tahap == _Tahap.posisikan
        ? _peringatan
        : null;
    final warna = switch (widget.hasil) {
      HasilWajah.cocok => AppColors.ok600,
      HasilWajah.tidakCocok => AppColors.alpa,
      HasilWajah.belum => peringatan != null ? AppColors.gold : Colors.white,
    };

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(20),
          child: AspectRatio(
            aspectRatio: _rasio,
            child: Stack(
              fit: StackFit.expand,
              children: [
                Container(color: AppColors.dark),
                if (c != null && c.value.isInitialized)
                  FittedBox(
                    fit: BoxFit.cover,
                    child: SizedBox(
                      width: c.value.previewSize!.height,
                      height: c.value.previewSize!.width,
                      child: CameraPreview(c),
                    ),
                  ),
                CustomPaint(
                  painter: _OvalPainter(
                    color: warna,
                    penuh: adaHasil,
                    cy: _ovalCy,
                    r: _ovalR,
                  ),
                ),
                if (adaHasil)
                  Align(
                    alignment: const Alignment(0, _ovalCy * 2 - 1),
                    child: Container(
                      width: 64,
                      height: 64,
                      decoration: BoxDecoration(
                        color: warna,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        widget.hasil == HasilWajah.cocok
                            ? Icons.check_rounded
                            : Icons.close_rounded,
                        color: Colors.white,
                        size: 38,
                      ),
                    ),
                  ),
                if (peringatan != null)
                  Positioned(
                    top: 14,
                    left: 14,
                    right: 14,
                    child: Semantics(
                      liveRegion: true,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 9,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xE6FEF3C7),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.warning_amber_rounded,
                              size: 20,
                              color: Color(0xFF7A5512),
                            ),
                            const SizedBox(width: 8),
                            Flexible(
                              child: Text(
                                peringatan,
                                style: AppText.label.copyWith(
                                  color: const Color(0xFF7A5512),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                Positioned(
                  left: 16,
                  right: 16,
                  bottom: 20,
                  child: Column(
                    children: [
                      if (!adaHasil && _tahap.index >= _Tahap.mengambil.index)
                        const Padding(
                          padding: EdgeInsets.only(bottom: 8),
                          child: SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.5,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      Text(
                        _petunjuk,
                        style: AppText.label.copyWith(
                          color: Colors.white,
                          shadows: const [Shadow(blurRadius: 6)],
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        if (!adaHasil) ...[
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.line),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Verifikasi kehidupan (liveness)',
                        style: AppText.captionBold,
                      ),
                    ),
                    Text(
                      '${(_progress * 100).round()}%',
                      style: AppText.captionBold.copyWith(
                        color: AppColors.brand700,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: _progress,
                    minHeight: 6,
                    color: AppColors.brand700,
                    backgroundColor: AppColors.line,
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }
}

class _OvalPainter extends CustomPainter {
  _OvalPainter({
    required this.color,
    required this.penuh,
    required this.cy,
    required this.r,
  });
  final Color color;

  /// Garis utuh dan tebal saat hasil sudah ada; putus-putus saat masih memindai.
  final bool penuh;
  final double cy;
  final double r;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Rect.fromCenter(
      center: Offset(size.width / 2, size.height * cy),
      width: size.width * r * 2,
      height: size.height * r * 2,
    );
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = penuh ? 6 : 4;
    final path = Path()..addOval(rect);
    if (penuh) {
      canvas.drawPath(path, paint);
      return;
    }
    for (final m in path.computeMetrics()) {
      for (double d = 0; d < m.length; d += 18) {
        canvas.drawPath(m.extractPath(d, d + 10), paint);
      }
    }
  }

  @override
  bool shouldRepaint(_OvalPainter old) =>
      old.color != color || old.penuh != penuh;
}
