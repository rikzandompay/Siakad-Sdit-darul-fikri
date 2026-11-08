import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import '../../face/face_capture.dart';
import '../../face/face_service.dart';

/// Pendaftaran wajah sekali sebelum absen pertama. Mengirim template (embedding), bukan foto.
class DaftarWajahScreen extends StatefulWidget {
  const DaftarWajahScreen({super.key});

  @override
  State<DaftarWajahScreen> createState() => _DaftarWajahScreenState();
}

class _DaftarWajahScreenState extends State<DaftarWajahScreen> {
  bool _kamera = false;
  String? _error;

  Future<void> _simpan(List<String> foto) async {
    try {
      final svc = FaceService.instance;
      final embeddings = <List<double>>[];
      for (final p in foto) {
        embeddings.add(await svc.embeddingDariFoto(p));
      }
      final template = FaceService.rataRata(embeddings);
      if (!mounted) return;
      await context.read<ApiClient>().post('/guru/wajah', {'template': template});
      if (!mounted) return;
      showMessage(context, 'Wajah berhasil didaftarkan.');
      context.pop(true);
    } on FaceException catch (e) {
      setState(() {
        _kamera = false;
        _error = e.message;
      });
    } on ApiException catch (e) {
      setState(() {
        _kamera = false;
        _error = e.message;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppPage(
      title: 'Daftarkan Wajah',
      subtitle: 'Diperlukan sekali sebelum presensi',
      bottom: _kamera ? SecondaryButton(label: 'Batalkan', onPressed: () => setState(() => _kamera = false)) : PrimaryButton(label: 'Mulai Ambil Foto', onPressed: () => setState(() {
            _kamera = true;
            _error = null;
          })),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        if (_kamera)
          FaceCapture(jumlahFoto: 3, onSelesai: _simpan, onGagal: (p) => setState(() {
                _kamera = false;
                _error = p;
              }))
        else ...[
          Container(
            height: 300,
            decoration: BoxDecoration(color: AppColors.dark, borderRadius: BorderRadius.circular(20)),
            alignment: Alignment.center,
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              const Icon(Icons.face_outlined, size: 96, color: Color(0xFF4B5563)),
              const SizedBox(height: 16),
              Text('Wajah belum terdaftar', style: AppText.heading.copyWith(color: Colors.white)),
              const SizedBox(height: 4),
              Text('Ambil foto wajah untuk verifikasi presensi', style: AppText.bodySmall.copyWith(color: AppColors.brand200)),
            ]),
          ),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Container(padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: AppColors.alpaTint, borderRadius: BorderRadius.circular(12)), child: Text(_error!, style: AppText.bodySmall.copyWith(color: AppColors.alpa))),
          ],
          const SizedBox(height: 12),
          AppCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: const [
              SectionLabel('Panduan foto'),
              _Panduan(icon: Icons.wb_sunny_outlined, judul: 'Pencahayaan', teks: 'Cukup terang, tidak membelakangi cahaya'),
              _Panduan(icon: Icons.center_focus_strong_outlined, judul: 'Posisi', teks: 'Wajah menghadap lurus ke kamera'),
              _Panduan(icon: Icons.visibility_outlined, judul: 'Aksesori', teks: 'Lepas masker dan kacamata hitam'),
            ]),
          ),
          const SizedBox(height: 10),
          Text('Foto hanya diproses di HP ini. Yang disimpan di server adalah kode pengenal wajah terenkripsi, bukan foto.', style: AppText.caption),
        ],
      ]),
    );
  }
}

class _Panduan extends StatelessWidget {
  const _Panduan({required this.icon, required this.judul, required this.teks});
  final IconData icon;
  final String judul;
  final String teks;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Row(children: [
          Container(width: 36, height: 36, decoration: const BoxDecoration(color: AppColors.brand100, shape: BoxShape.circle), child: Icon(icon, size: 18, color: AppColors.brand800)),
          const SizedBox(width: 12),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(judul, style: AppText.caption), Text(teks, style: AppText.label)])),
        ]),
      );
}
