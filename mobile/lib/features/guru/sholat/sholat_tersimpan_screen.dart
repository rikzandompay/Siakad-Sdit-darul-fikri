import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme.dart';
import '../../../widgets/common.dart';

class SholatTersimpanScreen extends StatelessWidget {
  const SholatTersimpanScreen({super.key, required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final r = Map<String, dynamic>.from(data['rekap']);
    return AppPage(
      bottom: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          PrimaryButton(
            label: 'Kembali ke Beranda',
            onPressed: () => context.go('/guru'),
          ),
          const SizedBox(height: 10),
          SecondaryButton(
            label: 'Pilih Jenis Sholat Lain',
            onPressed: () => context.pop(),
          ),
        ],
      ),
      child: Column(
        children: [
          ResultHero(
            icon: Icons.check_rounded,
            title: 'Presensi Sholat Tersimpan',
            subtitle: data['judul'],
          ),
          InfoTable([
            (
              'Hadir',
              Text(
                '${r['hadir']}',
                style: AppText.label.copyWith(color: AppColors.ok700),
              ),
            ),
            ('Tidak Hadir', Text('${r['tidak_hadir']}', style: AppText.label)),
          ]),
        ],
      ),
    );
  }
}
