import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import 'presensi_sholat_screen.dart';

/// Scan QR Kartu Pelajar. Setiap QR yang terbaca langsung tercatat Hadir di server.
class ScanQrScreen extends StatefulWidget {
  const ScanQrScreen({super.key, required this.kelasId, required this.jenis});
  final int kelasId;
  final String jenis;

  @override
  State<ScanQrScreen> createState() => _ScanQrScreenState();
}

class _ScanQrScreenState extends State<ScanQrScreen> {
  final _scanner = MobileScannerController(
    detectionSpeed: DetectionSpeed.noDuplicates,
    formats: const [BarcodeFormat.qrCode],
  );
  Map<String, dynamic>? _info;
  Map<String, dynamic>? _hasil; // siswa terakhir yang terbaca
  String? _error;
  bool _memproses = false;
  String? _terakhir;

  @override
  void initState() {
    super.initState();
    context
        .read<ApiClient>()
        .get(
          '/guru/sholat/kelas/${widget.kelasId}/siswa',
          query: {'jenis': widget.jenis},
        )
        .then((d) {
          if (mounted) setState(() => _info = d);
        })
        .catchError((_) {});
  }

  @override
  void dispose() {
    _scanner.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    final kode = capture.barcodes.firstOrNull?.rawValue;
    if (kode == null || _memproses || _hasil != null || kode == _terakhir)
      return;
    setState(() {
      _memproses = true;
      _error = null;
      _terakhir = kode;
    });
    try {
      final res = await context.read<ApiClient>().post(
        '/guru/sholat/kelas/${widget.kelasId}/scan',
        {'jenis': widget.jenis, 'qr': kode},
      );
      await _scanner.stop();
      setState(() {
        _hasil = res;
        _info = {...?_info, 'hadir': res['hadir'], 'total': res['total']};
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
      // Beri jeda sebelum QR yang sama bisa dicoba lagi.
      Future.delayed(const Duration(seconds: 2), () => _terakhir = null);
    } finally {
      if (mounted) setState(() => _memproses = false);
    }
  }

  Future<void> _lanjut() async {
    setState(() {
      _hasil = null;
      _terakhir = null;
    });
    await _scanner.start();
  }

  @override
  Widget build(BuildContext context) {
    final kelas = _info?['kelas']?['nama'] ?? '';
    final subjudul = '$kelas · Sholat ${widget.jenis}';
    if (_hasil != null) return _terbaca(subjudul);

    return AppPage(
      title: 'Scan QR Sholat',
      subtitle: subjudul,
      bottom: PrimaryButton(label: 'Selesai', onPressed: () => context.pop()),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_info != null)
            SholatProgressCard(
              tempat: _info!['tempat'] ?? '',
              waktu: _info!['waktu'] ?? '',
              hadir: _info!['hadir'] ?? 0,
              total: _info!['total'] ?? 0,
            ),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: AspectRatio(
              aspectRatio: 1.15,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  Container(color: AppColors.dark),
                  MobileScanner(
                    controller: _scanner,
                    onDetect: _onDetect,
                    errorBuilder: (context, error) => Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Text(
                          error.errorCode ==
                                  MobileScannerErrorCode.permissionDenied
                              ? 'Izin kamera ditolak. Aktifkan izin kamera untuk MyDarfik di pengaturan HP.'
                              : 'Kamera tidak dapat dibuka.',
                          style: AppText.body.copyWith(color: Colors.white),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                  ),
                  Center(
                    child: Container(
                      width: 190,
                      height: 190,
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.white, width: 4),
                        borderRadius: BorderRadius.circular(20),
                      ),
                    ),
                  ),
                  Positioned(
                    left: 0,
                    right: 0,
                    bottom: 18,
                    child: Text(
                      _memproses
                          ? 'Mencatat…'
                          : 'Arahkan kamera ke QR pada kartu siswa',
                      style: AppText.body.copyWith(color: Colors.white),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.alpaTint,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                _error!,
                style: AppText.bodySmall.copyWith(color: AppColors.alpa),
              ),
            ),
          ],
          const SizedBox(height: 12),
          Text(
            'Hadir tercatat saat QR terbaca. Siswa yang belum scan sampai batas waktu otomatis tercatat Tidak Hadir.',
            style: AppText.caption,
          ),
        ],
      ),
    );
  }

  Widget _terbaca(String subjudul) {
    final s = Map<String, dynamic>.from(_hasil!['siswa']);
    final sudah = _hasil!['sudah_tercatat_sebelumnya'] == true;
    return AppPage(
      title: 'QR Terbaca',
      subtitle: subjudul,
      bottom: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          PrimaryButton(label: 'Scan Berikutnya', onPressed: _lanjut),
          const SizedBox(height: 10),
          SecondaryButton(label: 'Selesai', onPressed: () => context.pop()),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AppCard(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                InisialAvatar(s['inisial'] ?? '', size: 72),
                const SizedBox(height: 12),
                Text(
                  s['nama'],
                  style: AppText.heading,
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 4),
                Text(
                  '${s['kelas']} · NIS ${s['nis']}',
                  style: AppText.bodySmall,
                ),
                const SizedBox(height: 10),
                StatusChip(
                  sudah
                      ? 'Sudah tercatat sebelumnya'
                      : 'QR terbaca · ${_hasil!['jam']}',
                  tone: sudah ? ChipTone.brand : ChipTone.ok,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          SectionLabel('Status sholat ${widget.jenis} (otomatis)'),
          const Align(
            alignment: Alignment.centerLeft,
            child: StatusChip('Hadir', tone: ChipTone.ok),
          ),
          const SizedBox(height: 10),
          Text(
            'Sudah tersimpan di presensi sholat ${widget.jenis} hari ini (${_hasil!['hadir']}/${_hasil!['total']} anak hadir).',
            style: AppText.bodySmall,
          ),
        ],
      ),
    );
  }
}
