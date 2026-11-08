import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

/// Daftar tugas/PR ananda beserta nilainya.
class TugasWaliScreen extends StatelessWidget {
  const TugasWaliScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final anak = context.watch<Session>().anak!;
    return AppPage(
      title: 'Tugas / PR Ananda',
      subtitle: anak['nama'],
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get('/wali/tugas', query: {'siswa_id': anak['id']}),
        builder: (context, d, _) {
          final list = (d['data'] as List).cast<Map>();
          return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
            if (list.isEmpty) const EmptyPanel(icon: Icons.assignment_outlined, message: 'Belum ada tugas.'),
            for (final t in list)
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: AppCard(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [
                      Expanded(child: Text(t['mata_pelajaran'] ?? '-', style: AppText.caption)),
                      if (t['nilai'] != null) StatusChip('Nilai ${(t['nilai'] as num).round()}', tone: ChipTone.ok) else if (t['tenggat'] != null) StatusChip('Kumpul ${Fmt.tanggalPendek(Fmt.parse(t['tenggat']))}', tone: ChipTone.gold),
                    ]),
                    const SizedBox(height: 4),
                    Text(t['judul'], style: AppText.heading),
                    if (t['deskripsi'] != null) ...[const SizedBox(height: 4), Text(t['deskripsi'], style: AppText.bodySmall)],
                    const SizedBox(height: 4),
                    Text('Dari ${t['guru'] ?? 'guru'}', style: AppText.caption),
                  ]),
                ),
              ),
          ]);
        },
      ),
    );
  }
}
