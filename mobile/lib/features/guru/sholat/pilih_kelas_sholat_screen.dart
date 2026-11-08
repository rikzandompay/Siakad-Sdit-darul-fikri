import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../widgets/common.dart';

class PilihKelasSholatScreen extends StatelessWidget {
  const PilihKelasSholatScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Presensi Sholat',
      subtitle: 'Pilih kelas binaan Anda',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get('/guru/sholat/kelas'),
        builder: (context, d, _) {
          final kelas = (d['data'] as List).cast<Map>();
          return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
            const SectionLabel('Kelas binaan Anda'),
            if (kelas.isEmpty) const EmptyPanel(icon: Icons.mosque_outlined, message: 'Presensi sholat diisi oleh wali kelas. Anda belum terdaftar sebagai wali kelas mana pun.'),
            for (final k in kelas)
              ListRowCard(
                badge: BadgeBox(top: k['singkatan']),
                title: k['nama'],
                subtitle: '${k['jumlah_siswa']} siswa${k['wali_kelas'] == true ? ' · Wali Kelas' : ''}',
                onTap: () => context.push('/guru/sholat/${k['id']}'),
              ),
          ]);
        },
      ),
    );
  }
}
