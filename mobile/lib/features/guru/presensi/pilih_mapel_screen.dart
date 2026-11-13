import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/format.dart';
import '../../../widgets/common.dart';

class PilihMapelScreen extends StatefulWidget {
  const PilihMapelScreen({super.key, required this.kelasId});
  final int kelasId;

  @override
  State<PilihMapelScreen> createState() => _PilihMapelScreenState();
}

class _PilihMapelScreenState extends State<PilihMapelScreen> {
  String _judul = 'Pilih Mata Pelajaran';
  Key _key = UniqueKey();

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: _judul,
      subtitle: 'Pilih mata pelajaran untuk diabsen',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () async {
          final d = await api.get(
            '/guru/presensi/kelas/${widget.kelasId}/jadwal',
          );
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted && _judul != d['kelas']['nama'])
              setState(() => _judul = d['kelas']['nama']);
          });
          return d;
        },
        builder: (context, d, _) {
          final jadwal = (d['data'] as List).cast<Map>();
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              SectionLabel(
                'Jadwal mengajar · ${Fmt.tanggalPendek(Fmt.parse(d['tanggal']))}',
              ),
              if (jadwal.isEmpty)
                EmptyPanel(
                  icon: Icons.event_busy_outlined,
                  message:
                      'Tidak ada jadwal pelajaran di kelas ini pada hari ${d['hari']}.',
                ),
              for (final j in jadwal)
                ListRowCard(
                  badge: BadgeBox(
                    top: j['jam_mulai'],
                    bottom: j['jam_selesai'],
                    size: 60,
                  ),
                  title: j['mata_pelajaran'] ?? '-',
                  subtitle: [
                    d['kelas']['nama'],
                    if (j['guru'] != null && d['semua_guru'] == true) j['guru'],
                  ].join(' · '),
                  chip: j['sudah_diabsen'] == true
                      ? const StatusChip('Sudah diabsen', tone: ChipTone.ok)
                      : const StatusChip('Belum diabsen'),
                  onTap: () async {
                    await context.push(
                      '/guru/presensi/jadwal/${j['jadwal_id']}',
                    );
                    setState(() => _key = UniqueKey());
                  },
                ),
            ],
          );
        },
      ),
    );
  }
}
