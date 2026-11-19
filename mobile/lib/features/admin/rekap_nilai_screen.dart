import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';
import 'rekap_bersama.dart';

class RekapNilaiScreen extends StatefulWidget {
  const RekapNilaiScreen({super.key});
  @override
  State<RekapNilaiScreen> createState() => _RekapNilaiScreenState();
}

class _RekapNilaiScreenState extends State<RekapNilaiScreen> with OpsiAdmin {
  int? _kelas;
  int? _ta;

  @override
  void opsiSiap(Map<String, dynamic> d) {
    if (!mounted) return;
    final list = daftarKelas('kelas_wali');
    setState(() {
      if (list.isNotEmpty) _kelas = list[0]['id'] as int;
      final aktif = (d['tahun_ajaran'] as List).where(
        (t) => t['aktif'] == true,
      );
      _ta = aktif.isNotEmpty
          ? aktif.first['id'] as int
          : ((d['tahun_ajaran'] as List).isNotEmpty
                ? d['tahun_ajaran'][0]['id'] as int
                : null);
    });
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final q = {'kelas_id': _kelas, 'tahun_ajaran_id': _ta};
    return AppPage(
      title: 'Rekap Nilai',
      subtitle: 'Rekapitulasi nilai semua mata pelajaran',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: _kelas == null
          ? null
          : tombolEkspor(context, 'nilai', q, awalanApi: awalanEkspor),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: AppCard(
              child: Row(
                children: [
                  Expanded(
                    child: PilihanDropdown<int>(
                      label: 'Tahun ajaran',
                      nilai: _ta,
                      item: {
                        for (final t in (opsi?['tahun_ajaran'] as List? ?? []))
                          t['id'] as int: t['nama'] as String,
                      },
                      onChanged: (v) => setState(() => _ta = v),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: PilihanDropdown<int>(
                      label: 'Kelas',
                      nilai: _kelas,
                      item: kelasItemDari('kelas_wali'),
                      onChanged: (v) => setState(() => _kelas = v),
                    ),
                  ),
                ],
              ),
            ),
          ),
          Expanded(
            child: _kelas == null
                ? tungguAtauKosong(
                    'Belum ada kelas yang diampu sebagai wali kelas.',
                  )
                : AsyncBody<Map<String, dynamic>>(
                    key: ValueKey(q.toString()),
                    load: () => api.get(
                      '$awalan/rekap/nilai',
                      query: {
                        for (final e in q.entries)
                          if (e.value != null) e.key: e.value,
                      },
                    ),
                    builder: (context, d, _) {
                      final list = (d['data'] as List).cast<Map>();
                      final kkm = d['kkm'] as num;
                      return ListView(
                        padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  'Nilai Rapor per Mata Pelajaran',
                                  style: AppText.label,
                                ),
                              ),
                              Text('KKM $kkm', style: AppText.caption),
                            ],
                          ),
                          const SizedBox(height: 8),
                          for (var i = 0; i < list.length; i++)
                            Padding(
                              padding: const EdgeInsets.only(bottom: 10),
                              child: AppCard(
                                child: Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.stretch,
                                  children: [
                                    Row(
                                      children: [
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                '${i + 1}. ${list[i]['nama']}',
                                                style: AppText.label,
                                              ),
                                              Text(
                                                'NIS ${list[i]['nis']}',
                                                style: AppText.caption,
                                              ),
                                            ],
                                          ),
                                        ),
                                        Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.end,
                                          children: [
                                            Text(
                                              'RATA-RATA',
                                              style: AppText.overline.copyWith(
                                                fontSize: 9,
                                                color: AppColors.brand700,
                                              ),
                                            ),
                                            Row(
                                              children: [
                                                Text(
                                                  list[i]['rata_rata']
                                                          ?.toString() ??
                                                      '-',
                                                  style: AppText.title.copyWith(
                                                    color: AppColors.brand700,
                                                  ),
                                                ),
                                                const SizedBox(width: 6),
                                                StatusChip(
                                                  list[i]['predikat'],
                                                  tone: ChipTone.ok,
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ],
                                    ),
                                    if ((list[i]['mapel'] as List)
                                        .isNotEmpty) ...[
                                      const SizedBox(height: 10),
                                      Wrap(
                                        spacing: 6,
                                        runSpacing: 6,
                                        children: [
                                          for (final m
                                              in (list[i]['mapel'] as List)
                                                  .cast<Map>())
                                            Container(
                                              width: 74,
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                    vertical: 6,
                                                  ),
                                              decoration: BoxDecoration(
                                                color: (m['nilai'] ?? 100) < kkm
                                                    ? AppColors.alpaTint
                                                    : AppColors.field,
                                                borderRadius:
                                                    BorderRadius.circular(8),
                                              ),
                                              child: Column(
                                                children: [
                                                  Text(
                                                    m['kode'] ?? '',
                                                    style: AppText.caption
                                                        .copyWith(
                                                          fontSize: 10,
                                                          color:
                                                              (m['nilai'] ??
                                                                      100) <
                                                                  kkm
                                                              ? AppColors.alpa
                                                              : AppColors
                                                                    .inkMuted,
                                                        ),
                                                  ),
                                                  Text(
                                                    m['nilai']?.toString() ??
                                                        '-',
                                                    style: AppText.label
                                                        .copyWith(
                                                          color:
                                                              (m['nilai'] ??
                                                                      100) <
                                                                  kkm
                                                              ? AppColors.alpa
                                                              : AppColors.ink,
                                                        ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                        ],
                                      ),
                                    ] else
                                      Text(
                                        'Belum ada nilai.',
                                        style: AppText.caption,
                                      ),
                                  ],
                                ),
                              ),
                            ),
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

