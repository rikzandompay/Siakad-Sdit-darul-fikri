import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../shared/konseling_list.dart';

/// Bimbingan konseling: info guru pembimbing, mulai konseling, riwayat.
class KonselingWaliScreen extends StatefulWidget {
  const KonselingWaliScreen({super.key});

  @override
  State<KonselingWaliScreen> createState() => _KonselingWaliScreenState();
}

class _KonselingWaliScreenState extends State<KonselingWaliScreen> {
  Key _key = UniqueKey();

  Future<void> _mulai(int siswaId) async {
    final topik = TextEditingController();
    final isi = TextEditingController();
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: AppColors.surface,
      builder: (c) => Padding(
        padding: EdgeInsets.fromLTRB(
          20,
          0,
          20,
          20 + MediaQuery.of(c).viewInsets.bottom,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Mulai Konseling', style: AppText.title),
            const SizedBox(height: 12),
            TextField(
              controller: topik,
              maxLength: 150,
              decoration: const InputDecoration(
                labelText: 'Topik',
                hintText: 'Contoh: Perkembangan belajar Matematika',
              ),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: isi,
              minLines: 3,
              maxLines: 5,
              maxLength: 2000,
              decoration: const InputDecoration(labelText: 'Pesan pertama'),
            ),
            const SizedBox(height: 12),
            PrimaryButton(
              label: 'Kirim',
              onPressed: () => Navigator.pop(c, true),
            ),
          ],
        ),
      ),
    );
    if (ok != true || !mounted) return;
    try {
      final res = await context.read<ApiClient>().post('/wali/konseling', {
        'siswa_id': siswaId,
        'topik': topik.text.trim(),
        'isi': isi.text.trim(),
      });
      if (!mounted) return;
      await context.push('/wali/konseling/${res['id']}');
      setState(() => _key = UniqueKey());
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final anak = context.watch<Session>().anak!;
    return AppPage(
      title: 'Bimbingan Konseling',
      subtitle: 'Konsultasi dengan guru',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        key: ValueKey('$_key-${anak['id']}'),
        load: () => api.get('/wali/konseling', query: {'siswa_id': anak['id']}),
        builder: (context, d, _) {
          final g = d['pengampu'] as Map?;
          final list = (d['data'] as List).cast<Map>();
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              Container(
                height: 120,
                decoration: BoxDecoration(
                  color: AppColors.brand100,
                  borderRadius: BorderRadius.circular(20),
                ),
                alignment: Alignment.center,
                child: Image.asset('assets/icons/konseling.png', height: 72),
              ),
              const SizedBox(height: 14),
              Text(
                'Layanan konseling untuk Bapak/Ibu.',
                style: AppText.heading,
              ),
              const SizedBox(height: 4),
              Text(
                'Dapatkan bantuan dan layanan informasi dari guru Ananda.',
                style: AppText.bodySmall,
              ),
              const SizedBox(height: 12),
              AppCard(
                child: g == null
                    ? Text(
                        'Kelas ${d['anak']['kelas'] ?? ''} belum memiliki guru pengampu konseling. Hubungi sekolah.',
                        style: AppText.bodySmall,
                      )
                    : Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              InisialAvatar(g['inisial'] ?? '', size: 44),
                              const SizedBox(width: 12),
                              Text('GURU PEMBIMBING', style: AppText.overline),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text('Nama Guru', style: AppText.caption),
                          Text(g['nama'], style: AppText.label),
                          if ((g['jabatan'] as String?)?.isNotEmpty ==
                              true) ...[
                            const SizedBox(height: 8),
                            Text('Jabatan', style: AppText.caption),
                            Text(g['jabatan'], style: AppText.label),
                          ],
                          const SizedBox(height: 8),
                          Text('Kelas Ananda', style: AppText.caption),
                          Text(
                            '${d['anak']['kelas'] ?? '-'} · ${d['anak']['nama']}',
                            style: AppText.label,
                          ),
                        ],
                      ),
              ),
              const SizedBox(height: 12),
              if (g != null)
                PrimaryButton(
                  label: 'Mulai Konseling',
                  onPressed: () => _mulai(anak['id'] as int),
                ),
              const SizedBox(height: 20),
              Text('Riwayat Konseling', style: AppText.heading),
              const SizedBox(height: 10),
              if (list.isEmpty)
                Text('Belum ada percakapan.', style: AppText.bodySmall),
              for (final p in list)
                KonselingRow(
                  p: {
                    ...Map<String, dynamic>.from(p),
                    'inisial': g?['inisial'] ?? '',
                  },
                  judul: p['topik'],
                  subjudul: g?['nama'] ?? 'Guru pengampu',
                  onTap: () async {
                    await context.push('/wali/konseling/${p['id']}');
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
