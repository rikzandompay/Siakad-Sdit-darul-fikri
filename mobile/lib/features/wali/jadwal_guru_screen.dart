import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

/// Daftar guru; ketuk untuk melihat jadwal mengajarnya.
class JadwalGuruScreen extends StatelessWidget {
  const JadwalGuruScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Jadwal Guru',
      subtitle: 'Pilih guru untuk melihat jadwal',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get('/wali/guru'),
        builder: (context, d, _) {
          final list = (d['data'] as List).cast<Map>();
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              const SectionLabel('Daftar guru'),
              if (list.isEmpty)
                const EmptyPanel(
                  icon: Icons.person_search_outlined,
                  message: 'Belum ada data guru.',
                ),
              for (final g in list)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: AppCard(
                    onTap: () => context.push('/wali/guru/${g['id']}'),
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(g['nama'] ?? '-', style: AppText.label),
                              const SizedBox(height: 2),
                              Text(
                                (g['wali_kelas'] as List).isNotEmpty
                                    ? 'Wali ${(g['wali_kelas'] as List).join(', ')}'
                                    : 'Guru',
                                style: AppText.bodySmall,
                              ),
                            ],
                          ),
                        ),
                        const Icon(
                          Icons.chevron_right,
                          color: AppColors.inkMuted,
                        ),
                      ],
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}
