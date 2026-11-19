import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';
import 'rekap_bersama.dart';

class RekapGuruScreen extends StatefulWidget {
  const RekapGuruScreen({super.key});
  @override
  State<RekapGuruScreen> createState() => _RekapGuruScreenState();
}

class _RekapGuruScreenState extends State<RekapGuruScreen> {
  String _rentang = 'bulan_ini';

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Rekap Presensi Guru',
      subtitle: 'Rekapitulasi kehadiran guru',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: tombolEkspor(context, 'guru-presensi', {'rentang': _rentang}),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: Align(
              alignment: Alignment.centerLeft,
              child: PilihanChips<String>(
                pilihan: rentangWaktu,
                nilai: _rentang,
                onChanged: (v) => setState(() => _rentang = v),
              ),
            ),
          ),
          Expanded(
            child: AsyncBody<Map<String, dynamic>>(
              key: ValueKey(_rentang),
              load: () =>
                  api.get('/admin/rekap/guru', query: {'rentang': _rentang}),
              builder: (context, d, _) {
                final t = Map<String, dynamic>.from(d['total']);
                final list = (d['data'] as List).cast<Map>();
                return ListView(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: KotakAngka(
                            angka: '${t['hadir']}',
                            label: 'Hadir',
                            warna: AppColors.ok700,
                            latar: AppColors.ok100,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: KotakAngka(
                            angka: '${t['terlambat']}',
                            label: 'Terlambat',
                            warna: const Color(0xFF8A6417),
                            latar: AppColors.goldTint,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: KotakAngka(
                            angka: '${t['tidak_hadir']}',
                            label: 'Tidak Hadir',
                            warna: AppColors.alpa,
                            latar: AppColors.alpaTint,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            '${list.length} guru',
                            style: AppText.label,
                          ),
                        ),
                        Text(d['periode'], style: AppText.caption),
                      ],
                    ),
                    const SizedBox(height: 8),
                    for (final g in list)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: AppCard(
                          onTap: () => context.push(
                            '/admin/guru/${g['id']}/absen',
                            extra: g['nama'],
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(g['nama'], style: AppText.label),
                                    if ((g['keterangan'] as String).isNotEmpty)
                                      Text(
                                        g['keterangan'],
                                        style: AppText.caption,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    if (g['persen'] != null)
                                      Text(
                                        'Kehadiran ${g['persen']}%',
                                        style: AppText.captionBold.copyWith(
                                          color: g['persen'] >= 90
                                              ? AppColors.ok700
                                              : AppColors.alpa,
                                        ),
                                      ),
                                    TandaAbsen(
                                      lokasiPalsu: (g['lokasi_palsu'] ?? 0) > 0
                                          ? '${g['lokasi_palsu']} hari'
                                          : null,
                                      koreksiMandiri:
                                          (g['koreksi_mandiri'] ?? 0) > 0
                                          ? '${g['koreksi_mandiri']}×'
                                          : null,
                                    ),
                                  ],
                                ),
                              ),
                              for (final (k, c) in [
                                ('hadir', AppColors.ok700),
                                ('terlambat', const Color(0xFF8A6417)),
                                ('tidak_hadir', AppColors.alpa),
                              ])
                                SizedBox(
                                  width: 40,
                                  child: Text(
                                    '${g[k]}',
                                    style: AppText.heading.copyWith(color: c),
                                    textAlign: TextAlign.center,
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
                    Text(
                      'Kolom: hadir, terlambat, tidak hadir. Ketuk nama guru untuk koreksi absen atau reset wajah.',
                      style: AppText.caption,
                      textAlign: TextAlign.center,
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

