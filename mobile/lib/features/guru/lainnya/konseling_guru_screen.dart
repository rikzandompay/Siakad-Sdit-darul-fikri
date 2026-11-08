import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../widgets/common.dart';
import '../../shared/konseling_list.dart';

class KonselingGuruScreen extends StatefulWidget {
  const KonselingGuruScreen({super.key});

  @override
  State<KonselingGuruScreen> createState() => _KonselingGuruScreenState();
}

class _KonselingGuruScreenState extends State<KonselingGuruScreen> {
  Key _key = UniqueKey();

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Konseling',
      subtitle: 'Pesan dari wali murid',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () => api.get('/guru/konseling'),
        builder: (context, d, _) {
          final list = (d['data'] as List).cast<Map>();
          return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
            const SectionLabel('Permintaan konseling'),
            if (list.isEmpty) const EmptyPanel(icon: Icons.forum_outlined, message: 'Belum ada pesan. Percakapan muncul saat wali murid di kelas yang Anda ampu konselingnya mengirim pesan.'),
            for (final p in list)
              KonselingRow(
                p: Map<String, dynamic>.from(p),
                judul: p['nama_wali'] ?? 'Wali ${p['siswa']}',
                subjudul: 'Wali ${p['siswa']} · ${p['kelas'] ?? '-'} · ${p['topik']}',
                onTap: () async {
                  await context.push('/guru/konseling/${p['id']}');
                  setState(() => _key = UniqueKey());
                },
              ),
          ]);
        },
      ),
    );
  }
}
