import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';
import 'rekap_screens.dart' show tombolEkspor;

class DataGuruScreen extends StatefulWidget {
  const DataGuruScreen({super.key});
  @override
  State<DataGuruScreen> createState() => _DataGuruScreenState();
}

class _DataGuruScreenState extends State<DataGuruScreen> {
  String _q = '';
  Timer? _t;

  @override
  void dispose() {
    _t?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Data Guru',
      subtitle: 'Guru aktif dan status absen hari ini',
      scroll: false,
      bottom: tombolEkspor(context, 'data-guru', const {}),
      padding: EdgeInsets.zero,
      child: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: TextField(decoration: const InputDecoration(hintText: 'Cari nama atau NIP', prefixIcon: Icon(Icons.search)), onChanged: (v) {
            _t?.cancel();
            _t = Timer(const Duration(milliseconds: 350), () => setState(() => _q = v.trim()));
          }),
        ),
        Expanded(
          child: AsyncBody<Map<String, dynamic>>(
            key: ValueKey(_q),
            load: () => api.get('/admin/guru', query: {'q': _q}),
            builder: (context, d, _) {
              final list = (d['data'] as List).cast<Map>();
              return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
                SectionLabel('${list.length} guru'),
                for (final g in list)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: AppCard(
                      child: Row(children: [
                        FotoAvatar(fotoUrl: g['foto_url'], inisial: g['inisial'] ?? '', size: 44),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(g['nama'], style: AppText.label),
                            Text([if (g['nip'] != null) 'NIP ${g['nip']}', (g['mata_pelajaran'] as List).join(', '), if ((g['wali_kelas'] as List).isNotEmpty) 'Wali ${(g['wali_kelas'] as List).join(', ')}'].where((e) => e.isNotEmpty).join(' · '), style: AppText.caption, maxLines: 2, overflow: TextOverflow.ellipsis),
                          ]),
                        ),
                        g['absen_hari_ini'] == null
                            ? const StatusChip('Belum absen')
                            : StatusChip(g['absen_hari_ini']['jam_masuk'] ?? g['absen_hari_ini']['status'], tone: StatusChip.toneFor(g['absen_hari_ini']['status'])),
                      ]),
                    ),
                  ),
              ]);
            },
          ),
        ),
      ]),
    );
  }
}

class DataSiswaScreen extends StatefulWidget {
  const DataSiswaScreen({super.key});
  @override
  State<DataSiswaScreen> createState() => _DataSiswaScreenState();
}

class _DataSiswaScreenState extends State<DataSiswaScreen> {
  String _q = '';
  int? _kelas;
  Timer? _t;
  List<Map>? _opsiKelas;

  @override
  void initState() {
    super.initState();
    context.read<ApiClient>().get('/admin/opsi').then((d) {
      if (mounted) setState(() => _opsiKelas = (d['kelas'] as List).cast<Map>());
    }).catchError((_) {});
  }

  @override
  void dispose() {
    _t?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Data Siswa',
      subtitle: 'Siswa aktif',
      scroll: false,
      bottom: tombolEkspor(context, 'data-siswa', const {}),
      padding: EdgeInsets.zero,
      child: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: TextField(decoration: const InputDecoration(hintText: 'Cari nama atau NISN', prefixIcon: Icon(Icons.search)), onChanged: (v) {
            _t?.cancel();
            _t = Timer(const Duration(milliseconds: 350), () => setState(() => _q = v.trim()));
          }),
        ),
        if (_opsiKelas != null)
          SizedBox(
            height: 44,
            child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 16), children: [
              PilihanChips<int?>(pilihan: {null: 'Semua', for (final k in _opsiKelas!) k['id'] as int: k['nama'] as String}, nilai: _kelas, onChanged: (v) => setState(() => _kelas = v)),
            ]),
          ),
        Expanded(
          child: AsyncBody<Map<String, dynamic>>(
            key: ValueKey('$_q-$_kelas'),
            load: () => api.get('/admin/siswa', query: {'q': _q, 'kelas_id': ?_kelas}),
            builder: (context, d, _) {
              final list = (d['data'] as List).cast<Map>();
              return ListView(padding: const EdgeInsets.fromLTRB(16, 8, 16, 24), children: [
                SectionLabel('${d['total']} siswa aktif${d['halaman_terakhir'] > 1 ? ' · menampilkan 30 pertama' : ''}'),
                if (list.isEmpty) const EmptyPanel(icon: Icons.person_search_outlined, message: 'Siswa tidak ditemukan.'),
                for (final s in list)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: AppCard(
                      child: Row(children: [
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(s['nama'], style: AppText.label),
                            Text('NIS ${s['nis']}${s['nisn'] == null ? '' : ' · NISN ${s['nisn']}'}', style: AppText.caption),
                            Text('Wali: ${s['nama_wali'] ?? '-'}', style: AppText.caption),
                          ]),
                        ),
                        if (s['kelas'] != null) StatusChip(s['kelas'], tone: ChipTone.brand),
                      ]),
                    ),
                  ),
              ]);
            },
          ),
        ),
      ]),
    );
  }
}

class KelasMapelScreen extends StatelessWidget {
  const KelasMapelScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Kelas & Mapel',
      subtitle: 'Wali kelas, siswa, dan mata pelajaran',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get('/admin/kelas'),
        builder: (context, d, _) => ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
          for (final k in (d['data'] as List).cast<Map>())
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: AppCard(
                child: Theme(
                  data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
                  child: ExpansionTile(
                    tilePadding: EdgeInsets.zero,
                    leading: BadgeBox(top: k['singkatan']),
                    title: Text(k['nama'], style: AppText.heading),
                    subtitle: Text('${k['jumlah_siswa']} siswa · Wali: ${k['wali_kelas'] ?? '-'}', style: AppText.caption),
                    children: [
                      if ((k['mapel'] as List).isEmpty) Padding(padding: const EdgeInsets.all(8), child: Text('Belum ada jadwal.', style: AppText.bodySmall)),
                      for (final m in (k['mapel'] as List).cast<Map>())
                        ListTile(dense: true, contentPadding: EdgeInsets.zero, title: Text(m['nama'] ?? '-', style: AppText.label), subtitle: Text(m['guru'] ?? '-', style: AppText.caption), trailing: Text('${m['sesi']} sesi/minggu', style: AppText.caption)),
                    ],
                  ),
                ),
              ),
            ),
        ]),
      ),
    );
  }
}
