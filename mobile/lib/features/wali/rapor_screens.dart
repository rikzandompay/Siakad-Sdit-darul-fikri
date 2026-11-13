import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

String _angka(num? n, [int d = 2]) => n == null
    ? '-'
    : n.toStringAsFixed(n % 1 == 0 ? 0 : d).replaceAll('.', ',');

/// Langkah 1: pilih tahun/kelas.
class RaporKelasScreen extends StatelessWidget {
  const RaporKelasScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final anak = context.watch<Session>().anak!;
    return AppPage(
      title: 'Nilai Rapor',
      subtitle: 'Pilih kelas ananda',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        key: ValueKey(anak['id']),
        load: () => api.get('/wali/rapor', query: {'siswa_id': anak['id']}),
        builder: (context, d, _) {
          final list = (d['data'] as List).cast<Map>();
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              const SectionLabel('Kelas ananda'),
              if (list.isEmpty)
                const EmptyPanel(
                  icon: Icons.menu_book_outlined,
                  message: 'Nilai rapor belum tersedia. Nilai muncul setelah guru mengisi rapor.',
                ),
              for (final t in list)
                ListRowCard(
                  badge: BadgeBox(
                    top: t['kelas_saat_ini'] == true
                        ? (anak['kelas_singkat'] ?? '-')
                        : 'TA',
                  ),
                  title: t['kelas_saat_ini'] == true
                      ? (d['kelas'] ?? 'Kelas')
                      : 'Tahun ${t['tahun']}',
                  subtitle:
                      'TA ${t['tahun']}${t['kelas_saat_ini'] == true ? ' · Kelas saat ini' : ''}',
                  onTap: () => context.push(
                    '/wali/rapor/semester',
                    extra: {
                      ...Map<String, dynamic>.from(t),
                      'kelas': t['kelas_saat_ini'] == true
                          ? d['kelas']
                          : 'TA ${t['tahun']}',
                    },
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

/// Langkah 2: pilih semester.
class RaporSemesterScreen extends StatelessWidget {
  const RaporSemesterScreen({super.key, required this.tahun});
  final Map<String, dynamic> tahun;

  @override
  Widget build(BuildContext context) {
    final sem = (tahun['semester'] as List).cast<Map>();
    return AppPage(
      title: tahun['kelas'] ?? 'Rapor',
      subtitle: 'Pilih semester',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          SectionLabel('Semester · TA ${tahun['tahun']}'),
          for (final s in sem)
            ListRowCard(
              badge: BadgeBox(top: s['semester'] == 'Ganjil' ? 'I' : 'II'),
              title: 'Semester ${s['semester']}',
              subtitle:
                  'Rata-rata ${_angka(s['rata_rata'])} · ${s['tuntas'] == true ? 'Tuntas' : 'Belum tuntas'}',
              onTap: () => context.push(
                '/wali/rapor/pilihan',
                extra: {
                  'tahun': tahun,
                  'semester': Map<String, dynamic>.from(s),
                },
              ),
            ),
        ],
      ),
    );
  }
}

/// Langkah 3: hasil studi 2 semester atau nilai per mapel.
class RaporPilihanScreen extends StatelessWidget {
  const RaporPilihanScreen({super.key, required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final tahun = Map<String, dynamic>.from(data['tahun']);
    final sem = Map<String, dynamic>.from(data['semester']);
    return AppPage(
      title: '${tahun['kelas']} · Semester ${sem['semester']}',
      subtitle: 'Pilih yang ingin dilihat',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const SectionLabel('Tampilan rapor'),
          ListRowCard(
            badge: Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: AppColors.brand100,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Image.asset('assets/icons/rapor.png'),
              ),
            ),
            title: 'Hasil Studi 2 Semester',
            subtitle: 'Grafik Ganjil dan Genap ${tahun['kelas']}',
            onTap: () => context.push('/wali/rapor/grafik', extra: tahun),
          ),
          ListRowCard(
            badge: const BadgeBox(top: 'A'),
            title: 'Lihat Nilai',
            subtitle: 'Nilai mata pelajaran semester ${sem['semester']}',
            onTap: () => context.push(
              '/wali/rapor/nilai/${sem['tahun_ajaran_id']}',
              extra: tahun['kelas'],
            ),
          ),
        ],
      ),
    );
  }
}

class RaporGrafikScreen extends StatelessWidget {
  const RaporGrafikScreen({super.key, required this.tahun});
  final Map<String, dynamic> tahun;

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final anak = context.watch<Session>().anak!;
    return AppPage(
      title: 'Hasil Studi / Rapor',
      subtitle: '${tahun['kelas']} · TA ${tahun['tahun']}',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get(
          '/wali/rapor/grafik',
          query: {'siswa_id': anak['id'], 'tahun': tahun['tahun']},
        ),
        builder: (context, d, _) {
          final sem = (d['semester'] as List).cast<Map>();
          final mapel = (d['mapel'] as List).cast<Map>();
          final warna = [AppColors.brand200, AppColors.brand700];
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Hasil Studi ${tahun['kelas']}',
                      style: AppText.heading,
                    ),
                    Text(
                      'Rata-rata nilai akhir tiap semester, skala 0–100',
                      style: AppText.caption,
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      height: 190,
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: [
                          for (var i = 0; i < sem.length; i++)
                            Column(
                              mainAxisAlignment: MainAxisAlignment.end,
                              children: [
                                Text(
                                  _angka(sem[i]['rata_rata']),
                                  style: AppText.label.copyWith(
                                    color: i == sem.length - 1
                                        ? AppColors.brand700
                                        : AppColors.inkMuted,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Semantics(
                                  label:
                                      'Semester ${sem[i]['semester']} rata-rata ${_angka(sem[i]['rata_rata'])}',
                                  child: Container(
                                    width: 84,
                                    height:
                                        140 *
                                        (((sem[i]['rata_rata'] as num?) ?? 0) /
                                            100),
                                    decoration: BoxDecoration(
                                      color: warna[i % 2],
                                      borderRadius: const BorderRadius.vertical(
                                        top: Radius.circular(6),
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  'Semester ${sem[i]['semester']}',
                                  style: AppText.captionBold,
                                ),
                              ],
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Nilai Murni per Mata Pelajaran',
                      style: AppText.heading,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        for (var i = 0; i < sem.length; i++) ...[
                          Container(width: 10, height: 10, color: warna[i % 2]),
                          const SizedBox(width: 4),
                          Text(sem[i]['semester'], style: AppText.caption),
                          const SizedBox(width: 14),
                        ],
                      ],
                    ),
                    for (final m in mapel) ...[
                      const SizedBox(height: 12),
                      Text(m['mata_pelajaran'] ?? '-', style: AppText.label),
                      for (var i = 0; i < sem.length; i++)
                        Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Row(
                            children: [
                              SizedBox(
                                width: 56,
                                child: Text(
                                  sem[i]['semester'],
                                  style: AppText.caption,
                                ),
                              ),
                              Expanded(
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(4),
                                  child: LinearProgressIndicator(
                                    value:
                                        (((m['nilai']
                                                    as Map)[sem[i]['semester']]
                                                as num?) ??
                                            0) /
                                        100,
                                    minHeight: 8,
                                    color: warna[i % 2],
                                    backgroundColor: AppColors.line,
                                  ),
                                ),
                              ),
                              SizedBox(
                                width: 44,
                                child: Text(
                                  _angka(
                                    (m['nilai'] as Map)[sem[i]['semester']]
                                        as num?,
                                    1,
                                  ),
                                  style: AppText.label,
                                  textAlign: TextAlign.right,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class RaporNilaiScreen extends StatelessWidget {
  const RaporNilaiScreen({
    super.key,
    required this.tahunAjaranId,
    required this.kelas,
  });
  final int tahunAjaranId;
  final String? kelas;

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final anak = context.watch<Session>().anak!;
    return AppPage(
      title: 'Hasil Studi / Rapor',
      subtitle: kelas,
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get(
          '/wali/rapor/$tahunAjaranId',
          query: {'siswa_id': anak['id']},
        ),
        builder: (context, d, _) {
          final list = (d['data'] as List).cast<Map>();
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              Text(
                '${kelas ?? ''} · Semester ${d['semester']['semester']} ${d['semester']['tahun']}',
                style: AppText.caption,
              ),
              const SizedBox(height: 8),
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Rata-rata Nilai Semester', style: AppText.bodySmall),
                    Text(_angka(d['rata_rata']), style: AppText.number),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 6,
                      children: [
                        StatusChip(
                          'Status: ${d['tuntas'] == true ? 'Tuntas' : 'Belum tuntas'}',
                          tone: d['tuntas'] == true
                              ? ChipTone.ok
                              : ChipTone.alpa,
                        ),
                        StatusChip(
                          'Predikat Umum: ${d['keterangan']} (${d['predikat']})',
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              for (final m in list)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: AppCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    m['mata_pelajaran'] ?? '-',
                                    style: AppText.heading,
                                  ),
                                  if (m['guru'] != null)
                                    Text(m['guru'], style: AppText.caption),
                                ],
                              ),
                            ),
                            Text(
                              _angka(m['nilai'], 1),
                              style: AppText.title.copyWith(
                                color: m['tuntas'] == true
                                    ? AppColors.ok700
                                    : AppColors.alpa,
                              ),
                            ),
                            const SizedBox(width: 8),
                            StatusChip(
                              '(${m['predikat']}) ${m['tuntas'] == true ? 'Tuntas' : 'Remedial'}',
                              tone: m['tuntas'] == true
                                  ? ChipTone.ok
                                  : ChipTone.alpa,
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: AppColors.brand50,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text.rich(
                                TextSpan(
                                  text: 'Formatif ',
                                  style: AppText.caption,
                                  children: [
                                    TextSpan(
                                      text: _angka(m['formatif'], 1),
                                      style: AppText.label,
                                    ),
                                  ],
                                ),
                              ),
                              Text.rich(
                                TextSpan(
                                  text: 'SAS ',
                                  style: AppText.caption,
                                  children: [
                                    TextSpan(
                                      text: _angka(m['sas'], 1),
                                      style: AppText.label,
                                    ),
                                  ],
                                ),
                              ),
                              Text.rich(
                                TextSpan(
                                  text: 'Kehadiran ',
                                  style: AppText.caption,
                                  children: [
                                    TextSpan(
                                      text: m['kehadiran_persen'] == null
                                          ? '-'
                                          : '${m['kehadiran_persen']}%',
                                      style: AppText.label,
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              Text(
                'KKM ${d['kkm']}. Nilai rapor = Formatif × 50% + SAS × 30% + kehadiran.',
                style: AppText.caption,
                textAlign: TextAlign.center,
              ),
            ],
          );
        },
      ),
    );
  }
}
