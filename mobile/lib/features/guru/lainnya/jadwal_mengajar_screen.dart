import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/session.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import '../../../widgets/pilihan.dart';

/// Jadwal mengajar seminggu. Dipakai guru (/guru/jadwal) dan wali melihat jadwal guru (/wali/guru/{id}/jadwal).
class JadwalMengajarScreen extends StatefulWidget {
  const JadwalMengajarScreen({super.key, required this.endpoint});
  final String endpoint;

  @override
  State<JadwalMengajarScreen> createState() => _JadwalMengajarScreenState();
}

class _JadwalMengajarScreenState extends State<JadwalMengajarScreen> {
  String? _hari;

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final akun = context.read<Session>().akun;
    return AppPage(
      title: 'Jadwal Mengajar',
      subtitle: 'Jadwal per hari',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get(widget.endpoint),
        builder: (context, d, _) {
          final guru =
              d['guru'] as Map? ??
              {
                'nama': akun['nama'],
                'inisial': akun['inisial'],
                'mata_pelajaran': akun['mata_pelajaran'],
                'wali_kelas': akun['wali_kelas'],
              };
          final minggu = Map<String, dynamic>.from(d['minggu']);
          _hari ??= minggu.containsKey(d['hari_ini']) ? d['hari_ini'] : 'Senin';
          final list = (minggu[_hari] as List).cast<Map>();
          final info = [
            ((guru['mata_pelajaran'] as List?) ?? []).join(', '),
            if (((guru['wali_kelas'] as List?) ?? []).isNotEmpty)
              'Wali ${(guru['wali_kelas'] as List).join(', ')}',
          ].where((e) => e.isNotEmpty).join(' · ');
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              AppCard(
                child: Row(
                  children: [
                    InisialAvatar(guru['inisial'] ?? '', size: 48),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(guru['nama'] ?? '-', style: AppText.heading),
                          if (info.isNotEmpty)
                            Text(info, style: AppText.caption),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              PilihanChips<String>(
                pilihan: {for (final h in minggu.keys) h: h},
                nilai: _hari!,
                onChanged: (v) => setState(() => _hari = v),
              ),
              const SizedBox(height: 14),
              Text(_hari!, style: AppText.heading),
              const SizedBox(height: 10),
              if (list.isEmpty)
                EmptyPanel(
                  icon: Icons.event_busy_outlined,
                  message: 'Tidak ada jadwal di hari $_hari.',
                ),
              for (final j in list)
                ListRowCard(
                  badge: BadgeBox(
                    top: j['jam_mulai'],
                    bottom: j['jam_selesai'],
                    size: 60,
                  ),
                  title: j['mata_pelajaran'] ?? '-',
                  subtitle: j['kelas'],
                ),
            ],
          );
        },
      ),
    );
  }
}
