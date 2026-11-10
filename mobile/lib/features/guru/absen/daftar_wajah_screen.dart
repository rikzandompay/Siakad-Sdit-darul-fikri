import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/session.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import '../../face/face_capture.dart';
import '../../face/face_service.dart';

/// Pendaftaran wajah sekali sebelum absen pertama. Wajah di kamera harus cocok dengan foto profil
/// yang sudah disetujui admin. Mengirim template (embedding), bukan foto.
class DaftarWajahScreen extends StatefulWidget {
  const DaftarWajahScreen({super.key});

  @override
  State<DaftarWajahScreen> createState() => _DaftarWajahScreenState();
}

class _DaftarWajahScreenState extends State<DaftarWajahScreen> {
  bool _kamera = false;
  String? _error;
  LokasiStatus _lokasiStatus = const LokasiStatus(
    sukses: true,
    nama: 'SDIT Darul Fikri, Sidoarjo',
  );

  @override
  void initState() {
    super.initState();
    _segarkan();
    _cekLokasi();
  }

  Future<void> _cekLokasi() async {
    try {
      if (!await Geolocator.isLocationServiceEnabled()) return;
      var izin = await Geolocator.checkPermission();
      if (izin == LocationPermission.denied) izin = await Geolocator.requestPermission();
      if (izin == LocationPermission.denied || izin == LocationPermission.deniedForever) return;
      final pos = await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.best, timeLimit: Duration(seconds: 10)));
      if (!mounted) return;
      final res = await context.read<ApiClient>().post('/guru/absen/cek-lokasi', {
        'latitude': pos.latitude,
        'longitude': pos.longitude,
        'akurasi': pos.accuracy,
      });
      if (!mounted) return;
      final didalam = res['di_dalam_area'] == true;
      final nama = '${res['sekolah']?['nama'] ?? 'SDIT Darul Fikri'}, Sidoarjo';
      setState(() {
        _lokasiStatus = LokasiStatus(
          sukses: didalam,
          nama: nama,
          pesan: didalam ? null : 'Lokasi tidak sesuai (Di luar jangkauan)',
        );
      });
    } catch (_) {}
  }

  /// Status foto bisa berubah di luar layar ini (admin baru saja menyetujui).
  Future<void> _segarkan() async {
    try {
      await context.read<Session>().refreshMe();
    } catch (_) {}
  }

  Future<void> _bukaProfil() async {
    await context.push('/guru/profil');
    if (mounted) await _segarkan();
  }

  Future<void> _simpan(List<String> foto) async {
    final api = context.read<ApiClient>();
    final fotoUrl = context.read<Session>().akun['foto_url'] as String?;
    try {
      final svc = FaceService.instance;
      final embeddings = <List<double>>[];
      for (final p in foto) {
        embeddings.add(await svc.embeddingDariFoto(p));
      }
      final template = FaceService.rataRata(embeddings);
      if (fotoUrl == null) throw ApiException('Foto profil belum disetujui kepala sekolah.');
      final fotoEmbedding = await svc.embeddingDariFotoProfil(await api.bytes(fotoUrl));
      await api.post('/guru/wajah', {'template': template, 'foto_embedding': fotoEmbedding});
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
    final akun = context.watch<Session>().akun;
    if (akun['foto_url'] == null) {
      final menunggu = akun['foto_menunggu'] == true;
      return AppPage(
        title: 'Daftarkan Wajah',
        subtitle: 'Foto profil diperlukan',
        bottom: PrimaryButton(label: menunggu ? 'Lihat Profil' : 'Unggah Foto Profil', onPressed: _bukaProfil),
        child: EmptyPanel(
          icon: menunggu ? Icons.hourglass_top_rounded : Icons.account_circle_outlined,
          message: menunggu
              ? 'Foto profil Anda menunggu persetujuan kepala sekolah. Setelah disetujui, wajah bisa didaftarkan.'
              : 'Wajah dicocokkan dengan foto profil Anda. Unggah foto yang jelas menampilkan wajah, lalu tunggu persetujuan kepala sekolah.',
        ),
      );
    }
    return AppPage(
      title: 'Daftarkan Wajah',
      subtitle: 'Diperlukan sekali sebelum presensi',
      bottom: _kamera ? SecondaryButton(label: 'Batalkan', onPressed: () => setState(() => _kamera = false)) : PrimaryButton(label: 'Mulai Ambil Foto', onPressed: () => setState(() {
            _kamera = true;
            _error = null;
          })),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        if (_kamera)
          FaceCapture(
            jumlahFoto: 3,
            lokasi: _lokasiStatus,
            onSelesai: _simpan,
            onGagal: (p) => setState(() {
              _kamera = false;
              _error = p;
            }),
          )
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
          Text('Wajah di kamera dicocokkan dengan foto profil Anda. Foto kamera hanya diproses di HP ini; yang disimpan di server adalah kode pengenal wajah terenkripsi.', style: AppText.caption),
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
