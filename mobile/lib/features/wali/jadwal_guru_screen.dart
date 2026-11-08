import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../widgets/common.dart';

/// Cari nama guru lalu lihat jadwal mengajarnya.
class JadwalGuruScreen extends StatefulWidget {
  const JadwalGuruScreen({super.key});

  @override
  State<JadwalGuruScreen> createState() => _JadwalGuruScreenState();
}

class _JadwalGuruScreenState extends State<JadwalGuruScreen> {
  String _q = '';
  Timer? _tunda;

  @override
  void dispose() {
    _tunda?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Jadwal Guru',
      subtitle: 'Cari nama guru',
      scroll: false,
      padding: EdgeInsets.zero,
      child: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: TextField(
            decoration: const InputDecoration(hintText: 'Cari nama guru…', prefixIcon: Icon(Icons.search)),
            onChanged: (v) {
              _tunda?.cancel();
              _tunda = Timer(const Duration(milliseconds: 400), () => setState(() => _q = v.trim()));
            },
          ),
        ),
        Expanded(
          child: AsyncBody<Map<String, dynamic>>(
            key: ValueKey(_q),
            load: () => api.get('/wali/guru', query: {'q': _q}),
            builder: (context, d, _) {
              final list = (d['data'] as List).cast<Map>();
              return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
                const SectionLabel('Daftar guru'),
                if (list.isEmpty) const EmptyPanel(icon: Icons.person_search_outlined, message: 'Guru tidak ditemukan.'),
                for (final g in list)
                  ListRowCard(
                    badge: BadgeBox(top: g['inisial'] ?? ''),
                    title: g['nama'],
                    subtitle: [(g['mata_pelajaran'] as List).join(', '), if ((g['wali_kelas'] as List).isNotEmpty) 'Wali ${(g['wali_kelas'] as List).join(', ')}'].where((e) => e.isNotEmpty).join(' · '),
                    onTap: () => context.push('/wali/guru/${g['id']}'),
                  ),
              ]);
            },
          ),
        ),
      ]),
    );
  }
}
