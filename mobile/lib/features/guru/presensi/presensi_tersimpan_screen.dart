import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme.dart';
import '../../../widgets/common.dart';

class PresensiTersimpanScreen extends StatelessWidget {
  const PresensiTersimpanScreen({super.key, required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final r = Map<String, dynamic>.from(data['rekap']);
    Text angka(Object v, [Color c = AppColors.ink]) => Text('$v', style: AppText.label.copyWith(color: c));
    return AppPage(
      bottom: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        PrimaryButton(label: 'Kembali ke Beranda', onPressed: () => context.go('/guru')),
        const SizedBox(height: 10),
        SecondaryButton(label: 'Absen Mata Pelajaran Lain', onPressed: () => context.pop()),
      ]),
      child: Column(children: [
        ResultHero(icon: Icons.check_rounded, title: 'Presensi Tersimpan', subtitle: data['judul']),
        InfoTable([
          ('Hadir', angka(r['hadir'], AppColors.ok700)),
          ('Sakit', angka(r['sakit'])),
          ('Izin', angka(r['izin'])),
          ('Alpa', angka(r['alpa'])),
        ]),
      ]),
    );
  }
}
