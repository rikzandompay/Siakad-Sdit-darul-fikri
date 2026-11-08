import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/format.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';

/// Langkah 1: ambil GPS, server menghitung jarak ke sekolah.
class VerifikasiLokasiScreen extends StatefulWidget {
  const VerifikasiLokasiScreen({super.key, required this.aksi});
  final String aksi; // masuk | pulang

  @override
  State<VerifikasiLokasiScreen> createState() => _VerifikasiLokasiScreenState();
}

class _VerifikasiLokasiScreenState extends State<VerifikasiLokasiScreen> {
  Map<String, dynamic>? _hasil;
  Position? _posisi;
  String? _error;
  bool _memuat = true;

  @override
  void initState() {
    super.initState();
    _cek();
  }

  Future<void> _cek() async {
    setState(() {
      _memuat = true;
      _error = null;
      _hasil = null;
    });
    try {
      if (!await Geolocator.isLocationServiceEnabled()) throw ApiException('GPS tidak aktif. Nyalakan lokasi di HP lalu coba lagi.');
      var izin = await Geolocator.checkPermission();
      if (izin == LocationPermission.denied) izin = await Geolocator.requestPermission();
      if (izin == LocationPermission.denied || izin == LocationPermission.deniedForever) {
        throw ApiException('Izin lokasi ditolak. Aktifkan izin lokasi untuk MyDarfik di pengaturan HP.');
      }
      final pos = await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.best, timeLimit: Duration(seconds: 20)));
      if (pos.isMocked) throw ApiException('Terdeteksi aplikasi lokasi palsu. Matikan aplikasi tersebut untuk melakukan presensi.');
      if (!mounted) return;
      final res = await context.read<ApiClient>().post('/guru/absen/cek-lokasi', {'latitude': pos.latitude, 'longitude': pos.longitude, 'akurasi': pos.accuracy});
      setState(() {
        _posisi = pos;
        _hasil = res;
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Lokasi tidak dapat dibaca. Pastikan GPS aktif dan coba di tempat terbuka.');
    } finally {
      if (mounted) setState(() => _memuat = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final h = _hasil;
    final didalam = h?['di_dalam_area'] == true;

    if (h != null && !didalam) return _diLuarArea(h);

    return AppPage(
      title: 'Verifikasi Lokasi',
      subtitle: 'Langkah 1 dari 2',
      bottom: PrimaryButton(
        label: 'Lanjut Verifikasi Wajah',
        onPressed: didalam
            ? () => context.pushReplacement('/guru/absen/wajah', extra: {
                  'aksi': widget.aksi,
                  'latitude': _posisi!.latitude,
                  'longitude': _posisi!.longitude,
                  'akurasi': _posisi!.accuracy,
                  'mock': _posisi!.isMocked,
                  'jarak_meter': h!['jarak_meter'],
                })
            : null,
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(20),
          child: SizedBox(
            height: 250,
            child: CustomPaint(painter: _PetaPainter(jarak: (h?['jarak_meter'] as num?)?.toDouble(), radius: (h?['radius_meter'] as num?)?.toDouble() ?? 100), child: Container()),
          ),
        ),
        const SizedBox(height: 12),
        if (_memuat)
          const AppCard(child: Row(children: [SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5)), SizedBox(width: 12), Text('Membaca lokasi GPS…')]))
        else if (_error != null)
          ErrorPanel(message: _error!, onRetry: _cek)
        else if (h != null) ...[
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: AppColors.ok100, borderRadius: BorderRadius.circular(14)),
            child: Row(children: [
              Container(width: 36, height: 36, decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle), child: const Icon(Icons.check, color: AppColors.ok700)),
              const SizedBox(width: 12),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Di dalam area sekolah', style: AppText.label.copyWith(color: AppColors.ok700)),
                Text('Lokasi Anda sesuai untuk presensi', style: AppText.caption.copyWith(color: AppColors.ok700)),
              ])),
            ]),
          ),
          const SizedBox(height: 12),
          AppCard(
            child: Column(children: [
              _Info(icon: Icons.location_on_outlined, label: 'Jarak ke sekolah', nilai: Fmt.jarak(h['jarak_meter'])),
              const SizedBox(height: 12),
              _Info(icon: Icons.gps_fixed, label: 'Akurasi GPS', nilai: h['akurasi_meter'] == null ? '-' : '±${h['akurasi_meter']} meter'),
            ]),
          ),
        ],
      ]),
    );
  }

  Widget _diLuarArea(Map<String, dynamic> h) {
    return AppPage(
      title: 'Presensi Gagal',
      subtitle: 'Lokasi tidak sesuai',
      bottom: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        PrimaryButton(label: 'Cek Lokasi Lagi', onPressed: _cek),
        const SizedBox(height: 10),
        SecondaryButton(label: 'Kembali', onPressed: () => context.pop()),
      ]),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const ResultHero(icon: Icons.location_on_outlined, title: '', success: false),
        Text('Presensi hanya dapat dilakukan di dalam area sekolah. Dekati ${h['sekolah']?['nama'] ?? 'sekolah'} lalu coba lagi.', style: AppText.bodySmall, textAlign: TextAlign.center),
        const SizedBox(height: 16),
        AppCard(
          child: Column(children: [
            _Info(icon: Icons.location_on_outlined, label: 'Jarak ke sekolah', nilai: Fmt.jarak(h['jarak_meter']), merah: true),
            const SizedBox(height: 12),
            _Info(icon: Icons.warning_amber_rounded, label: 'Radius presensi', nilai: 'Maksimal ${h['radius_meter']} meter', merah: true),
          ]),
        ),
      ]),
    );
  }
}

class _Info extends StatelessWidget {
  const _Info({required this.icon, required this.label, required this.nilai, this.merah = false});
  final IconData icon;
  final String label;
  final String nilai;
  final bool merah;

  @override
  Widget build(BuildContext context) => Row(children: [
        Container(width: 40, height: 40, decoration: BoxDecoration(color: merah ? AppColors.alpaTint : AppColors.brand100, shape: BoxShape.circle), child: Icon(icon, size: 20, color: merah ? AppColors.alpa : AppColors.brand800)),
        const SizedBox(width: 12),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(label, style: AppText.caption), Text(nilai, style: AppText.heading)])),
      ]);
}

/// Peta skematis (tanpa layanan peta): lingkaran radius sekolah dan posisi guru.
class _PetaPainter extends CustomPainter {
  _PetaPainter({required this.jarak, required this.radius});
  final double? jarak;
  final double radius;

  @override
  void paint(Canvas canvas, Size size) {
    canvas.drawRect(Offset.zero & size, Paint()..color = AppColors.brand50);
    final jalan = Paint()..color = Colors.white;
    canvas.drawRect(Rect.fromLTWH(0, size.height * 0.36, size.width, 22), jalan);
    canvas.drawRect(Rect.fromLTWH(0, size.height * 0.7, size.width, 14), jalan);
    for (final x in [0.2, 0.47, 0.75]) {
      canvas.drawRect(Rect.fromLTWH(size.width * x, 0, x == 0.47 ? 20 : 14, size.height), jalan);
    }

    final pusat = Offset(size.width / 2, size.height / 2);
    final r = size.height * 0.38;
    canvas.drawCircle(pusat, r, Paint()..color = AppColors.brand700.withValues(alpha: 0.15));
    final garis = Paint()
      ..color = AppColors.brand700
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2;
    final keliling = Path()..addOval(Rect.fromCircle(center: pusat, radius: r));
    for (final m in keliling.computeMetrics()) {
      for (double d = 0; d < m.length; d += 12) {
        canvas.drawPath(m.extractPath(d, d + 6), garis);
      }
    }
    // Titik sekolah.
    canvas.drawCircle(pusat, 7, Paint()..color = Colors.white);
    canvas.drawCircle(pusat, 5, Paint()..color = AppColors.gold);
    // Titik guru: jarak relatif terhadap radius (dibatasi agar tetap di layar).
    if (jarak != null) {
      final rel = math.min(jarak! / radius, 1.6);
      final posGuru = pusat + Offset(math.cos(0.6), math.sin(0.6)) * (r * rel);
      canvas.drawCircle(posGuru, 9, Paint()..color = Colors.white);
      canvas.drawCircle(posGuru, 6.5, Paint()..color = AppColors.brand700);
    }
    final tp = TextPainter(text: TextSpan(text: 'SDIT Darul Fikri', style: AppText.captionBold.copyWith(fontSize: 11)), textDirection: TextDirection.ltr)..layout();
    final label = Rect.fromCenter(center: pusat - Offset(0, r * 0.5), width: tp.width + 20, height: tp.height + 10);
    canvas.drawRRect(RRect.fromRectAndRadius(label, const Radius.circular(10)), Paint()..color = Colors.white);
    tp.paint(canvas, label.topLeft + const Offset(10, 5));
  }

  @override
  bool shouldRepaint(_PetaPainter old) => old.jarak != jarak || old.radius != radius;
}
