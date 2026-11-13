import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'kelola_screens.dart';

/// Kepala sekolah menyetujui foto profil guru (acuan identitas wajah) dan melihat wajah yang sangat mirip.
class FotoPengajuanScreen extends StatefulWidget {
  const FotoPengajuanScreen({super.key});

  @override
  State<FotoPengajuanScreen> createState() => _FotoPengajuanScreenState();
}

class _FotoPengajuanScreenState extends State<FotoPengajuanScreen> {
  Key _key = UniqueKey();

  Future<void> _tinjau(Map<String, dynamic> g, bool setujui) async {
    final ok = await konfirmasi(
      context,
      setujui ? 'Setujui foto ${g['nama']}?' : 'Tolak foto ${g['nama']}?',
      setujui
          ? 'Pastikan ini benar wajah yang bersangkutan. Foto ini menjadi acuan wajah saat absen.'
          : 'Foto ini dibuang dan guru diminta mengajukan foto lain.',
      ya: setujui ? 'Setujui' : 'Tolak',
    );
    if (!ok || !mounted) return;
    try {
      final r = await context.read<ApiClient>().post(
        '/admin/foto-pengajuan/${g['id']}/${setujui ? 'setujui' : 'tolak'}',
      );
      if (!mounted) return;
      showMessage(context, r['message'] ?? 'Tersimpan.');
      setState(() => _key = UniqueKey());
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Foto Guru',
      subtitle: 'Acuan identitas wajah untuk absen',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () => api.get('/admin/foto-pengajuan'),
        builder: (context, d, _) {
          final list = (d['data'] as List)
              .cast<Map>()
              .map((e) => Map<String, dynamic>.from(e))
              .toList();
          final mirip = (d['wajah_mirip'] as List).cast<Map>();
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              if (list.isEmpty)
                const EmptyPanel(
                  icon: Icons.account_circle_outlined,
                  message: 'Tidak ada foto yang menunggu persetujuan.',
                ),
              for (final g in list)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: AppCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(g['nama'] ?? '-', style: AppText.heading),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 16,
                          runSpacing: 8,
                          crossAxisAlignment: WrapCrossAlignment.center,
                          children: [
                            if (g['foto_url'] != null)
                              _Foto(
                                label: 'Foto sekarang',
                                url: g['foto_url'],
                                inisial: g['inisial'] ?? '',
                              ),
                            _Foto(
                              label: 'Foto baru',
                              url: g['foto_pengajuan_url'],
                              inisial: g['inisial'] ?? '',
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: AlertButton(
                                label: 'Tolak',
                                icon: Icons.close_rounded,
                                onPressed: () => _tinjau(g, false),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: SuccessButton(
                                label: 'Setujui',
                                icon: Icons.check_rounded,
                                onPressed: () => _tinjau(g, true),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              if (mirip.isNotEmpty) ...[
                const SizedBox(height: 8),
                AppCard(
                  borderColor: AppColors.gold,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Wajah sangat mirip', style: AppText.heading),
                      const SizedBox(height: 4),
                      Text(
                        'Sistem tidak bisa membedakan wajah secara pasti untuk pasangan ini (misalnya kembar). Periksa absen mereka secara berkala.',
                        style: AppText.bodySmall,
                      ),
                      const SizedBox(height: 8),
                      for (final m in mirip)
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Text(
                            '${m['guru']} dan ${m['mirip_dengan']} (${(m['skor'] as num).round()}%)',
                            style: AppText.label,
                          ),
                        ),
                    ],
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

class _Foto extends StatelessWidget {
  const _Foto({required this.label, required this.url, required this.inisial});
  final String label;
  final String? url;
  final String inisial;

  @override
  Widget build(BuildContext context) => Column(
    mainAxisSize: MainAxisSize.min,
    children: [
      FotoAvatar(fotoUrl: url, inisial: inisial, size: 96),
      const SizedBox(height: 6),
      Text(label, style: AppText.caption),
    ],
  );
}
