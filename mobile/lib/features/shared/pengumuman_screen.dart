import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

/// Daftar pengumuman (wali: /wali/pengumuman, guru: /guru/pengumuman).
class PengumumanScreen extends StatelessWidget {
  const PengumumanScreen({super.key, required this.endpoint});
  final String endpoint;

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Pengumuman Resmi Sekolah',
      subtitle: 'Informasi SDIT Darul Fikri',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get(endpoint),
        builder: (context, d, _) {
          final list = (d['data'] as List).cast<Map>();
          return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
            if (list.isEmpty) const EmptyPanel(icon: Icons.campaign_outlined, message: 'Belum ada pengumuman.'),
            for (final p in list) PengumumanCard(p: Map<String, dynamic>.from(p)),
          ]);
        },
      ),
    );
  }
}

class PengumumanCard extends StatelessWidget {
  const PengumumanCard({super.key, required this.p, this.aksi});
  final Map<String, dynamic> p;
  final Widget? aksi;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        borderColor: p['penting'] == true ? AppColors.gold : null,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            if (p['penting'] == true) const Padding(padding: EdgeInsets.only(right: 6), child: StatusChip('Penting', tone: ChipTone.gold)),
            StatusChip(p['sasaran'] == 'Wali Murid' ? 'Wali' : p['sasaran'], tone: ChipTone.brand),
            const Spacer(),
            Text(Fmt.tanggalPendek(Fmt.parse(p['tanggal'])), style: AppText.caption),
            ?aksi,
          ]),
          const SizedBox(height: 10),
          Text(p['judul'], style: AppText.heading),
          const SizedBox(height: 6),
          Text(p['isi'], style: AppText.bodySmall),
        ]),
      ),
    );
  }
}
