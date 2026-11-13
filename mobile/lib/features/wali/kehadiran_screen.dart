import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

class KehadiranScreen extends StatefulWidget {
  const KehadiranScreen({super.key});

  @override
  State<KehadiranScreen> createState() => _KehadiranScreenState();
}

class _KehadiranScreenState extends State<KehadiranScreen> {
  DateTime _bulan = DateTime(DateTime.now().year, DateTime.now().month);

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final anak = context.watch<Session>().anak!;
    final ini = DateTime(DateTime.now().year, DateTime.now().month);
    return AppPage(
      title: 'Presensi & Ibadah Ananda',
      subtitle: 'Rekapitulasi Kehadiran & Sholat',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: PrimaryButton(
        label: 'Ajukan Izin',
        onPressed: () => context.push('/wali/izin'),
      ),
      child: Column(
        children: [
          Row(
            children: [
              const SizedBox(width: 8),
              IconButton(
                tooltip: 'Bulan sebelumnya',
                onPressed: () => setState(
                  () => _bulan = DateTime(_bulan.year, _bulan.month - 1),
                ),
                icon: const Icon(Icons.chevron_left),
              ),
              Expanded(
                child: Text(
                  Fmt.bulan(_bulan),
                  style: AppText.label,
                  textAlign: TextAlign.center,
                ),
              ),
              IconButton(
                tooltip: 'Bulan berikutnya',
                onPressed: _bulan.isBefore(ini)
                    ? () => setState(
                        () => _bulan = DateTime(_bulan.year, _bulan.month + 1),
                      )
                    : null,
                icon: const Icon(Icons.chevron_right),
              ),
              const SizedBox(width: 8),
            ],
          ),
          Expanded(
            child: AsyncBody<Map<String, dynamic>>(
              key: ValueKey('${anak['id']}-$_bulan'),
              load: () => api.get(
                '/wali/kehadiran',
                query: {
                  'siswa_id': anak['id'],
                  'bulan': DateFormat('yyyy-MM').format(_bulan),
                },
              ),
              builder: (context, d, _) {
                final list = (d['data'] as List).cast<Map>();
                final persen = d['persen_hadir'];
                return ListView(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                  children: [
                    AppCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Persentase Hadir Bulan Ini',
                            style: AppText.bodySmall,
                          ),
                          const SizedBox(height: 4),
                          Text(
                            persen == null
                                ? '-'
                                : '${persen.toString().replaceAll('.', ',')}%',
                            style: AppText.number,
                          ),
                          const SizedBox(height: 8),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(4),
                            child: LinearProgressIndicator(
                              value: (persen ?? 0) / 100,
                              minHeight: 6,
                              color: AppColors.ok600,
                              backgroundColor: AppColors.line,
                            ),
                          ),
                          const SizedBox(height: 10),
                          Wrap(
                            spacing: 6,
                            runSpacing: 6,
                            children: [
                              StatusChip(
                                '${d['hadir']} Hadir',
                                tone: ChipTone.ok,
                              ),
                              if (d['sakit'] > 0)
                                StatusChip(
                                  '${d['sakit']} Sakit',
                                  tone: ChipTone.sakit,
                                ),
                              if (d['izin'] > 0)
                                StatusChip(
                                  '${d['izin']} Izin',
                                  tone: ChipTone.gold,
                                ),
                              if (d['alpa'] > 0)
                                StatusChip(
                                  '${d['alpa']} Alpa',
                                  tone: ChipTone.alpa,
                                ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text('Catatan Harian', style: AppText.heading),
                    const SizedBox(height: 10),
                    if (list.isEmpty)
                      const EmptyPanel(
                        icon: Icons.event_note_outlined,
                        message: 'Belum ada catatan kehadiran di bulan ini.',
                      ),
                    for (final h in list)
                      _Hari(h: Map<String, dynamic>.from(h)),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _Hari extends StatelessWidget {
  const _Hari({required this.h});
  final Map<String, dynamic> h;

  @override
  Widget build(BuildContext context) {
    final sholat = Map<String, dynamic>.from(h['sholat'] as Map);
    final hadirSholat = sholat.entries
        .where((e) => e.value == 'Hadir')
        .map((e) => e.key)
        .toList();
    final tidak = sholat.entries
        .where((e) => e.value != 'Hadir')
        .map((e) => e.key)
        .toList();
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppCard(
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    Fmt.tanggalPanjang(Fmt.parse(h['tanggal'])),
                    style: AppText.label,
                  ),
                  if (hadirSholat.isNotEmpty)
                    Row(
                      children: [
                        const Icon(
                          Icons.check,
                          size: 16,
                          color: AppColors.ok600,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          'Sholat ${hadirSholat.join(' & ')}',
                          style: AppText.caption,
                        ),
                      ],
                    ),
                  if (tidak.isNotEmpty)
                    Row(
                      children: [
                        const Icon(
                          Icons.close,
                          size: 16,
                          color: AppColors.alpa,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          'Tidak sholat ${tidak.join(' & ')}',
                          style: AppText.caption,
                        ),
                      ],
                    ),
                ],
              ),
            ),
            if (h['status'] != null)
              StatusChip(h['status'], tone: StatusChip.toneFor(h['status'])),
          ],
        ),
      ),
    );
  }
}
