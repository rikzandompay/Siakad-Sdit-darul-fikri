import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/format.dart';
import '../../../core/session.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import '../../face/face_service.dart';

/// "Presensi Guru": status masuk/pulang hari ini dan syarat absen.
class AbsenScreen extends StatefulWidget {
  const AbsenScreen({super.key});

  @override
  State<AbsenScreen> createState() => _AbsenScreenState();
}

class _AbsenScreenState extends State<AbsenScreen> {
  Map<String, dynamic>? _d;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final d = await context.read<ApiClient>().get('/guru/absen');
      if (mounted) setState(() => _d = d);

      if (d['wajah_terdaftar'] != true) {
        if (!mounted) return;
        final akun = context.read<Session>().akun;
        final fotoUrl = akun['foto_url'] as String?;
        if (fotoUrl != null) {
          try {
            final api = context.read<ApiClient>();
            final svc = FaceService.instance;
            final fotoBytes = await api.bytes(fotoUrl);
            final fotoEmbedding = await svc.embeddingDariFotoProfil(fotoBytes);
            await api.post('/guru/wajah', {
              'foto_embedding': fotoEmbedding,
              'dari_foto_profil': true,
            });
            if (mounted) {
              setState(() {
                _d?['wajah_terdaftar'] = true;
              });
            }
          } catch (_) {}
        }
      }
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    }
  }

  Future<void> _mulai(String aksi) async {
    final d = _d!;
    if (d['wajah_terdaftar'] != true) {
      final akun = context.read<Session>().akun;
      final fotoUrl = akun['foto_url'] as String?;
      var terdaftarOtomatis = false;
      if (fotoUrl != null) {
        try {
          final api = context.read<ApiClient>();
          final svc = FaceService.instance;
          final fotoBytes = await api.bytes(fotoUrl);
          final fotoEmbedding = await svc.embeddingDariFotoProfil(fotoBytes);
          await api.post('/guru/wajah', {
            'foto_embedding': fotoEmbedding,
            'dari_foto_profil': true,
          });
          terdaftarOtomatis = true;
          await _load();
        } catch (_) {}
      }

      if (!terdaftarOtomatis) {
        if (!mounted) return;
        final ok = await context.push<bool>('/guru/absen/daftar-wajah');
        await _load();
        if (ok != true || !mounted) return;
      }
    }
    if (!mounted) return;
    await context.push('/guru/absen/lokasi', extra: aksi);
    if (mounted) _load();
  }

  @override
  Widget build(BuildContext context) {
    final d = _d;
    if (d == null) {
      return AppPage(title: 'Presensi Guru', child: _error == null ? const Padding(padding: EdgeInsets.only(top: 80), child: Center(child: CircularProgressIndicator())) : ErrorPanel(message: _error!, onRetry: _load));
    }
    final h = Map<String, dynamic>.from(d['hari_ini']);
    final lokasi = d['lokasi'] as Map?;
    final tidakHadir = h['status'] == 'Tidak Hadir';
    final sudahMasuk = h['jam_masuk'] != null;
    final sudahPulang = h['jam_pulang'] != null;
    final lewatBatas = d['batas_masuk'] != null && (d['jam_sekarang'] as String).compareTo(d['batas_masuk']) >= 0;

    String? kendala;
    final adalahOtomatis = h['keterangan'] != null && h['keterangan'].toString().startsWith('Otomatis:');
    if (d['hari_sekolah'] != true) {
      kendala = 'Hari ini bukan hari sekolah.';
    } else if (lokasi == null) {
      kendala = 'Lokasi sekolah belum diatur admin. Hubungi Tata Usaha.';
    } else if (tidakHadir && (lewatBatas || !adalahOtomatis)) {
      kendala = h['keterangan'] ?? 'Anda tercatat Tidak Hadir hari ini. Hubungi Tata Usaha untuk koreksi.';
    } else if (!sudahMasuk && lewatBatas) {
      kendala = 'Absen masuk ditutup pukul ${d['batas_masuk']}. Hubungi Tata Usaha untuk koreksi.';
    }

    return AppPage(
      title: 'Presensi Guru',
      subtitle: Fmt.tanggalPanjang(Fmt.parse(d['tanggal'])),
      bottom: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        SuccessButton(icon: Icons.login_rounded, label: sudahMasuk ? 'Sudah Absen Masuk' : 'Absen Masuk', onPressed: kendala == null && !sudahMasuk ? () => _mulai('masuk') : null),
        const SizedBox(height: 10),
        WarningButton(icon: Icons.logout_rounded, label: sudahPulang ? 'Sudah Absen Pulang' : 'Absen Pulang', onPressed: kendala == null && sudahMasuk && !sudahPulang ? () => _mulai('pulang') : null),
      ]),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        AppCard(
          padding: const EdgeInsets.all(12),
          child: Row(children: [
            Expanded(child: _Kotak(judul: 'MASUK', nilai: h['jam_masuk'] ?? (tidakHadir ? 'Tidak hadir' : 'Belum absen'), ket: h['terlambat'] == true ? 'Terlambat (jam kerja ${d['jam_masuk']})' : 'Jam kerja ${d['jam_masuk']}', aktif: sudahMasuk, peringatan: h['terlambat'] == true)),
            const SizedBox(width: 10),
            Expanded(child: _Kotak(judul: 'PULANG', nilai: h['jam_pulang'] ?? 'Belum absen', ket: 'Jam kerja ${d['jam_pulang']}', aktif: sudahPulang)),
          ]),
        ),
        const SizedBox(height: 12),
        AppCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const SectionLabel('Syarat presensi'),
            _Syarat(icon: Icons.location_on_outlined, langkah: '1 · Lokasi', teks: lokasi == null ? 'Lokasi sekolah belum diatur' : 'Berada di area ${lokasi['nama']} (radius ${lokasi['radius_meter']} m)'),
            const SizedBox(height: 12),
            _Syarat(
              icon: Icons.face_retouching_natural,
              langkah: '2 · Wajah',
              teks: 'Cocok dengan wajah terdaftar',
              chip: d['wajah_terdaftar'] == true
                  ? const StatusChip('Terdaftar', tone: ChipTone.ok)
                  : StatusChip(d['foto_acuan'] == true ? 'Belum daftar' : (d['foto_menunggu'] == true ? 'Foto menunggu' : 'Perlu foto'), tone: ChipTone.gold),
            ),
            if (d['wajah_terdaftar'] != true) ...[
              const SizedBox(height: 12),
              SecondaryButton(
                icon: Icons.camera_front_rounded,
                label: 'Daftarkan Wajah Sekarang',
                onPressed: () async {
                  final ok = await context.push<bool>('/guru/absen/daftar-wajah');
                  if (ok == true && mounted) _load();
                },
              ),
            ],
          ]),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(color: kendala == null ? AppColors.brand50 : AppColors.goldTint, borderRadius: BorderRadius.circular(12), border: Border.all(color: kendala == null ? AppColors.brand200 : const Color(0xFFF2D58A))),
          child: Text(
            kendala ?? 'Terlambat jika absen masuk setelah ${d['jam_masuk']}. Tombol Absen Pulang aktif setelah absen masuk.${d['wajah_terdaftar'] == true ? '' : ' Wajah perlu didaftarkan sekali sebelum absen pertama.'}',
            style: AppText.bodySmall.copyWith(color: kendala == null ? AppColors.brand800 : const Color(0xFF7A5512)),
          ),
        ),
        const SizedBox(height: 8),
        TextButton(onPressed: () => context.push('/guru/absen/rekap'), child: const Text('Lihat rekap absen saya')),
      ]),
    );
  }
}

class _Kotak extends StatelessWidget {
  const _Kotak({required this.judul, required this.nilai, required this.ket, required this.aktif, this.peringatan = false});
  final String judul;
  final String nilai;
  final String ket;
  final bool aktif;
  final bool peringatan;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(color: aktif ? AppColors.ok100 : AppColors.field, borderRadius: BorderRadius.circular(12)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(judul, style: AppText.overline.copyWith(fontSize: 10)),
          const SizedBox(height: 4),
          Text(nilai, style: AppText.heading.copyWith(color: aktif ? AppColors.ok700 : AppColors.ink)),
          const SizedBox(height: 2),
          Text(ket, style: AppText.caption.copyWith(fontSize: 11, color: peringatan ? const Color(0xFF8A6417) : AppColors.inkMuted)),
        ]),
      );
}

class _Syarat extends StatelessWidget {
  const _Syarat({required this.icon, required this.langkah, required this.teks, this.chip});
  final IconData icon;
  final String langkah;
  final String teks;
  final Widget? chip;

  @override
  Widget build(BuildContext context) => Row(children: [
        Container(width: 40, height: 40, decoration: const BoxDecoration(color: AppColors.brand100, shape: BoxShape.circle), child: Icon(icon, color: AppColors.brand800, size: 20)),
        const SizedBox(width: 12),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(langkah, style: AppText.caption), Text(teks, style: AppText.label)])),
        ?chip,
      ]);
}
