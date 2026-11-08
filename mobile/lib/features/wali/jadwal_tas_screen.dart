import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';

class JadwalTasScreen extends StatefulWidget {
  const JadwalTasScreen({super.key});

  @override
  State<JadwalTasScreen> createState() => _JadwalTasScreenState();
}

class _JadwalTasScreenState extends State<JadwalTasScreen> {
  String? _hari;

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final anak = context.watch<Session>().anak!;
    return AppPage(
      title: 'Jadwal & Persiapan Tas',
      subtitle: 'Membantu Ananda Menyiapkan Buku',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        key: ValueKey(anak['id']),
        load: () => api.get('/wali/jadwal', query: {'siswa_id': anak['id']}),
        builder: (context, d, _) {
          final besok = Map<String, dynamic>.from(d['besok']);
          final minggu = Map<String, dynamic>.from(d['minggu']);
          _hari ??= besok['hari'];
          final list = ((minggu[_hari] as List?) ?? []).cast<Map>();
          final bawa = (besok['bawa'] as List).cast<String>();
          return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
            AppCard(
              borderColor: AppColors.gold,
              color: const Color(0xFFFFFBEB),
              child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Container(width: 40, height: 40, decoration: BoxDecoration(color: AppColors.goldTint, borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.lightbulb_outline, color: Color(0xFF8A6417))),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('${besok['hari']} (${Fmt.tanggalPendek(Fmt.parse(besok['tanggal']))}):', style: AppText.label.copyWith(color: const Color(0xFF8A6417))),
                    const SizedBox(height: 2),
                    Text(bawa.isEmpty ? 'Tidak ada pelajaran terjadwal.' : 'Siapkan buku ${bawa.join(', ')}.', style: AppText.body),
                  ]),
                ),
              ]),
            ),
            const SizedBox(height: 16),
            PilihanChips<String>(pilihan: {for (final h in minggu.keys) h: h}, nilai: _hari!, onChanged: (v) => setState(() => _hari = v)),
            const SizedBox(height: 12),
            Text('Jadwal $_hari${_hari == besok['hari'] ? ' (besok)' : ''}', style: AppText.heading),
            const SizedBox(height: 10),
            if (list.isEmpty) const EmptyPanel(icon: Icons.event_busy_outlined, message: 'Tidak ada pelajaran.'),
            for (final j in list)
              ListRowCard(badge: BadgeBox(top: j['jam_mulai'], bottom: j['jam_selesai'], size: 60), title: j['mata_pelajaran'] ?? '-', subtitle: 'Buku ${j['mata_pelajaran']}${j['guru'] == null ? '' : ' · ${j['guru']}'}'),
          ]);
        },
      ),
    );
  }
}
