import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

class AkunWaliScreen extends StatefulWidget {
  const AkunWaliScreen({super.key});
  @override
  State<AkunWaliScreen> createState() => _AkunWaliScreenState();
}

class _AkunWaliScreenState extends State<AkunWaliScreen> {
  String _q = '';
  Timer? _t;
  Key _key = UniqueKey();

  @override
  void dispose() {
    _t?.cancel();
    super.dispose();
  }

  Future<void> _sinkron() async {
    try {
      final r = await context.read<ApiClient>().post('/admin/wali/sinkron');
      if (!mounted) return;
      showMessage(context, r['message'] ?? 'Selesai.');
      setState(() => _key = UniqueKey());
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Akun Wali Murid',
      subtitle: 'Masuk dengan NIS dan tanggal lahir siswa (YY-MM-DD)',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: SecondaryButton(
        label: 'Sinkronkan dari data siswa',
        onPressed: _sinkron,
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: TextField(
              decoration: const InputDecoration(
                hintText: 'Cari wali, NIS, atau nama siswa',
                prefixIcon: Icon(Icons.search),
              ),
              onChanged: (v) {
                _t?.cancel();
                _t = Timer(
                  const Duration(milliseconds: 350),
                  () => setState(() {
                    _q = v.trim();
                    _key = UniqueKey();
                  }),
                );
              },
            ),
          ),
          Expanded(
            child: AsyncBody<Map<String, dynamic>>(
              key: _key,
              load: () => api.get('/admin/wali', query: {'q': _q}),
              builder: (context, d, _) {
                final list = (d['data'] as List).cast<Map>();
                return ListView(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                  children: [
                    SectionLabel('${d['total']} akun'),
                    if (list.isEmpty)
                      EmptyPanel(
                        icon: Icons.family_restroom_outlined,
                        message: _q.isEmpty
                            ? 'Belum ada akun wali. Isi tanggal lahir di Data Siswa, lalu ketuk Sinkronkan.'
                            : 'Tidak ada akun yang cocok dengan "$_q".',
                      ),
                    for (final w in list)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: AppCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      w['nama'],
                                      style: AppText.label,
                                    ),
                                  ),
                                  StatusChip(
                                    w['aktif'] == true ? 'Aktif' : 'Nonaktif',
                                    tone: w['aktif'] == true
                                        ? ChipTone.ok
                                        : ChipTone.netral,
                                  ),
                                ],
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Masuk dengan NIS ${w['login_nis']}',
                                style: AppText.caption,
                              ),
                              for (final a in (w['anak'] as List).cast<Map>())
                                Text(
                                  '${a['nama']}${a['kelas'] != null ? ' · ${a['kelas']}' : ''}',
                                  style: AppText.bodySmall,
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

