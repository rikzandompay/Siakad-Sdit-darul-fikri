import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../widgets/common.dart';

class PilihKelasScreen extends StatelessWidget {
  const PilihKelasScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Presensi Kelas',
      subtitle: 'Diisi oleh wali kelas',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get('/guru/presensi/kelas'),
        builder: (context, d, _) {
          final kelas = (d['data'] as List).cast<Map>();
          return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
            const SectionLabel('Kelas yang Anda walikan'),
            if (kelas.isEmpty) const EmptyPanel(icon: Icons.class_outlined, message: 'Presensi kelas hanya diisi oleh wali kelas. Anda belum terdaftar sebagai wali kelas mana pun.'),
            for (final k in kelas)
              ListRowCard(
                badge: BadgeBox(top: k['singkatan']),
                title: k['nama'],
                subtitle: '${k['jumlah_siswa']} siswa${k['wali_kelas'] == true ? ' · Wali Kelas' : ''}',
                onTap: () => context.push('/guru/presensi/kelas/${k['id']}'),
              ),
          ]);
        },
      ),
    );
  }
}
