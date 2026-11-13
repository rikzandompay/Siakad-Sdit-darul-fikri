import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';

class PilihJenisScreen extends StatefulWidget {
  const PilihJenisScreen({super.key, required this.kelasId});
  final int kelasId;

  @override
  State<PilihJenisScreen> createState() => _PilihJenisScreenState();
}

class _PilihJenisScreenState extends State<PilihJenisScreen> {
  Key _key = UniqueKey();
  String _judul = 'Pilih Sholat';

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: _judul,
      subtitle: 'Pilih jenis sholat untuk diabsen',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () async {
          final d = await api.get('/guru/sholat/kelas/${widget.kelasId}');
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted && _judul != d['kelas']['nama'])
              setState(() => _judul = d['kelas']['nama']);
          });
          return d;
        },
        builder: (context, d, _) {
          final jenis = (d['data'] as List).cast<Map>();
          final catatan = d['catatan'] as String?;
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              const SectionLabel('Sholat berjamaah hari ini'),
              for (final j in jenis)
                ListRowCard(
                  badge: BadgeBox(
                    top: j['waktu'],
                    bottom: j['jenis'],
                    size: 60,
                  ),
                  title: 'Sholat ${j['jenis']}',
                  subtitle: j['tempat'],
                  chip: j['tercatat'] > 0
                      ? StatusChip(
                          '${j['hadir']}/${j['total']} hadir',
                          tone: ChipTone.ok,
                        )
                      : j['sudah_masuk_waktu'] == true
                      ? const StatusChip('Belum diabsen')
                      : const StatusChip('Belum masuk waktu'),
                  onTap: () async {
                    await context.push(
                      '/guru/sholat/${widget.kelasId}/${j['jenis']}',
                    );
                    setState(() => _key = UniqueKey());
                  },
                ),
              if (catatan != null) ...[
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: AppColors.brand50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.brand200),
                  ),
                  child: Text(
                    catatan,
                    style: AppText.bodySmall.copyWith(
                      color: AppColors.brand800,
                    ),
                  ),
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}
