import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/format.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';

/// Hasil absen: berhasil, wajah tidak cocok, atau gagal lainnya.
class HasilAbsenScreen extends StatelessWidget {
  const HasilAbsenScreen({super.key, required this.hasil});
  final Map<String, dynamic> hasil;

  @override
  Widget build(BuildContext context) {
    final aksi = hasil['aksi'] == 'pulang' ? 'Pulang' : 'Masuk';
    return switch (hasil['jenis']) {
      'berhasil' => _berhasil(context, aksi),
      'wajah_tidak_cocok' => _wajah(context),
      _ => _gagal(context),
    };
  }

  Widget _berhasil(BuildContext context, String aksi) {
    final h = Map<String, dynamic>.from(hasil['hari_ini']);
    final jam = aksi == 'Masuk' ? h['jam_masuk'] : h['jam_pulang'];
    final telat = aksi == 'Masuk' && h['terlambat'] == true;
    return AppPage(
      bottom: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        PrimaryButton(label: 'Kembali ke Beranda', icon: Icons.home_rounded, onPressed: () => context.go('/guru')),
        const SizedBox(height: 10),
        SecondaryButton(label: 'Lihat Riwayat Presensi', icon: Icons.history_rounded, onPressed: () => context.pushReplacement('/guru/absen/rekap')),
      ]),
      child: Column(children: [
        ResultHero(icon: Icons.check_rounded, title: 'Presensi $aksi Berhasil', subtitle: '${Fmt.tanggalPanjang(DateTime.now())} · $jam'),
        InfoTable([
          ('Waktu ${aksi.toLowerCase()}', Text(aksi == 'Masuk' ? '$jam · ${telat ? 'Terlambat' : 'Tepat waktu'}' : '$jam', style: AppText.label.copyWith(color: telat ? const Color(0xFF8A6417) : AppColors.ok700))),
          ('Lokasi', Text('Di area sekolah (${Fmt.jarak(hasil['jarak_meter'] ?? 0)})', style: AppText.label)),
          ('Kecocokan wajah', Text('${(hasil['skor_wajah'] as num).round()}%', style: AppText.label)),
          ('Status', Text(h['status'] ?? 'Hadir', style: AppText.label)),
        ]),
      ]),
    );
  }

  Widget _wajah(BuildContext context) {
    final habis = hasil['habis'] == true;
    final skor = (hasil['skor_wajah'] as num?)?.round();
    final min = (hasil['min_skor_wajah'] as num?)?.round() ?? 85;
    return AppPage(
      title: 'Presensi Gagal',
      subtitle: 'Wajah tidak cocok',
      bottom: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        if (!habis) WarningButton(label: 'Coba Lagi', icon: Icons.refresh_rounded, onPressed: () => context.pushReplacement('/guru/absen/wajah', extra: hasil['args'])),
        if (!habis) const SizedBox(height: 10),
        SecondaryButton(label: 'Kembali', color: AppColors.inkMuted, onPressed: () => context.pop()),
      ]),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const ResultHero(icon: Icons.face_retouching_off, title: '', success: false),
        Text(
          habis
              ? 'Percobaan sudah ${hasil['percobaan']} kali. Hubungi Tata Usaha untuk absen manual atau mereset data wajah.'
              : 'Wajah tidak cocok dengan data terdaftar. Pastikan wajah terang, menghadap kamera, dan tanpa masker.',
          style: AppText.bodySmall,
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 16),
        InfoTable([
          ('Kecocokan wajah', Text(skor == null ? '-' : '$skor% (minimal $min%)', style: AppText.label.copyWith(color: AppColors.alpa))),
          ('Percobaan', Text('${hasil['percobaan']} dari 3', style: AppText.label)),
        ]),
      ]),
    );
  }

  Widget _gagal(BuildContext context) {
    final jenis = hasil['jenis'];
    final judul = switch (jenis) {
      'di_luar_area' => 'Lokasi tidak sesuai',
      'sudah_absen' => 'Sudah absen',
      'lewat_batas' => 'Absen masuk ditutup',
      _ => 'Presensi belum tercatat',
    };
    return AppPage(
      title: 'Presensi Gagal',
      subtitle: judul,
      bottom: SecondaryButton(label: 'Kembali', color: AppColors.inkMuted, onPressed: () => context.pop()),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        ResultHero(icon: jenis == 'di_luar_area' ? Icons.location_off_outlined : Icons.error_outline, title: '', success: false),
        Text(hasil['pesan'] ?? 'Terjadi kesalahan.', style: AppText.body, textAlign: TextAlign.center),
      ]),
    );
  }
}
