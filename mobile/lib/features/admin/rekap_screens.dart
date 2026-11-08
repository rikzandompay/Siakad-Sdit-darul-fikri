import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/unduh.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';

/// Tombol ekspor: berkas diambil dari server dan langsung disimpan ke folder Download.
Widget tombolEkspor(BuildContext context, String jenis, Map<String, Object?> q) => Row(children: [
      Expanded(child: SecondaryButton(label: 'Unduh CSV', onPressed: () => unduhLaporan(context, jenis: jenis, format: 'csv', query: q))),
      const SizedBox(width: 10),
      Expanded(child: PrimaryButton(label: 'Unduh PDF', onPressed: () => unduhLaporan(context, jenis: jenis, format: 'pdf', query: q))),
    ]);

/// Pemuat pilihan filter (kelas, mapel, tahun ajaran) sekali untuk layar rekap.
mixin OpsiAdmin<T extends StatefulWidget> on State<T> {
  Map<String, dynamic>? opsi;

  @override
  void initState() {
    super.initState();
    context.read<ApiClient>().get('/admin/opsi').then((d) {
      if (mounted) setState(() => opsi = d);
      opsiSiap(d);
    }).catchError((_) {});
  }

  void opsiSiap(Map<String, dynamic> d) {}
  Map<int, String> get kelasItem => {for (final k in (opsi?['kelas'] as List? ?? [])) k['id'] as int: k['nama'] as String};
}

class RekapAbsensiScreen extends StatefulWidget {
  const RekapAbsensiScreen({super.key});
  @override
  State<RekapAbsensiScreen> createState() => _RekapAbsensiScreenState();
}

class _RekapAbsensiScreenState extends State<RekapAbsensiScreen> with OpsiAdmin {
  int? _kelas;
  int? _mapel;
  String _rentang = 'bulan_ini';

  @override
  void opsiSiap(Map<String, dynamic> d) {
    if ((d['kelas'] as List).isNotEmpty && mounted) setState(() => _kelas = d['kelas'][0]['id'] as int);
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final q = {'kelas_id': _kelas, 'pelajaran_id': _mapel, 'rentang': _rentang};
    return AppPage(
      title: 'Rekap Absensi',
      subtitle: 'Rekapitulasi kehadiran siswa per kelas',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: _kelas == null ? null : tombolEkspor(context, 'absensi', q),
      child: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: AppCard(
            child: Column(children: [
              Row(children: [
                Expanded(child: PilihanDropdown<int>(label: 'Kelas', nilai: _kelas, item: kelasItem, onChanged: (v) => setState(() => _kelas = v))),
                const SizedBox(width: 10),
                Expanded(child: PilihanDropdown<int?>(label: 'Mata pelajaran', nilai: _mapel, item: {null: 'Semua Mapel', for (final m in (opsi?['mapel'] as List? ?? [])) m['id'] as int: m['nama'] as String}, onChanged: (v) => setState(() => _mapel = v))),
              ]),
              const SizedBox(height: 10),
              Align(alignment: Alignment.centerLeft, child: PilihanChips<String>(pilihan: rentangWaktu, nilai: _rentang, onChanged: (v) => setState(() => _rentang = v))),
            ]),
          ),
        ),
        Expanded(
          child: _kelas == null
              ? const Center(child: CircularProgressIndicator())
              : AsyncBody<Map<String, dynamic>>(
                  key: ValueKey(q.toString()),
                  load: () => api.get('/admin/rekap/absensi', query: {for (final e in q.entries) if (e.value != null) e.key: e.value}),
                  builder: (context, d, _) => _TabelRekap(
                    judul: 'Data Rekap · ${d['kelas']} · ${d['jumlah_siswa']} siswa',
                    periode: d['periode'],
                    kolom: const [('H', 'HADIR', AppColors.ok700), ('S', 'SAKIT', AppColors.sakit), ('I', 'IZIN', Color(0xFF8A6417)), ('A', 'ALPA', AppColors.alpa)],
                    data: (d['data'] as List).cast<Map>(),
                  ),
                ),
        ),
      ]),
    );
  }
}

class RekapSholatScreen extends StatefulWidget {
  const RekapSholatScreen({super.key});
  @override
  State<RekapSholatScreen> createState() => _RekapSholatScreenState();
}

class _RekapSholatScreenState extends State<RekapSholatScreen> with OpsiAdmin {
  int? _kelas;
  String _jenis = 'Dzuhur';
  String _rentang = 'bulan_ini';

  @override
  void opsiSiap(Map<String, dynamic> d) {
    if ((d['kelas'] as List).isNotEmpty && mounted) setState(() => _kelas = d['kelas'][0]['id'] as int);
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final q = {'kelas_id': _kelas, 'jenis': _jenis, 'rentang': _rentang};
    return AppPage(
      title: 'Rekap Sholat',
      subtitle: 'Kehadiran sholat berjamaah per kelas',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: _kelas == null ? null : tombolEkspor(context, 'sholat', q),
      child: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: AppCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              PilihanDropdown<int>(label: 'Kelas', nilai: _kelas, item: kelasItem, onChanged: (v) => setState(() => _kelas = v)),
              const SizedBox(height: 10),
              PilihanChips<String>(pilihan: const {'Dhuha': 'Sholat Dhuha', 'Dzuhur': 'Sholat Dzuhur'}, nilai: _jenis, onChanged: (v) => setState(() => _jenis = v)),
              const SizedBox(height: 8),
              PilihanChips<String>(pilihan: rentangWaktu, nilai: _rentang, onChanged: (v) => setState(() => _rentang = v)),
            ]),
          ),
        ),
        Expanded(
          child: _kelas == null
              ? const Center(child: CircularProgressIndicator())
              : AsyncBody<Map<String, dynamic>>(
                  key: ValueKey(q.toString()),
                  load: () => api.get('/admin/rekap/sholat', query: q),
                  builder: (context, d, _) => _TabelRekap(
                    judul: 'Sholat ${d['jenis']} · ${d['kelas']} · ${d['jumlah_siswa']} siswa',
                    periode: d['periode'],
                    kolom: const [('hadir', 'HADIR', AppColors.ok700), ('tidak_hadir', 'TIDAK HADIR', AppColors.alpa)],
                    data: (d['data'] as List).cast<Map>(),
                  ),
                ),
        ),
      ]),
    );
  }
}

class _TabelRekap extends StatelessWidget {
  const _TabelRekap({required this.judul, required this.periode, required this.kolom, required this.data});
  final String judul;
  final String periode;
  final List<(String, String, Color)> kolom;
  final List<Map> data;

  @override
  Widget build(BuildContext context) {
    return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
      Row(children: [Expanded(child: Text(judul, style: AppText.label)), Text(periode, style: AppText.caption)]),
      const SizedBox(height: 8),
      Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14),
        child: Row(children: [
          Expanded(child: Text('NAMA SISWA', style: AppText.overline.copyWith(fontSize: 10))),
          for (final k in kolom) SizedBox(width: kolom.length > 2 ? 46 : 70, child: Text(k.$2, style: AppText.overline.copyWith(fontSize: 10), textAlign: TextAlign.center)),
        ]),
      ),
      const SizedBox(height: 6),
      if (data.isEmpty) const EmptyPanel(icon: Icons.group_off_outlined, message: 'Belum ada siswa aktif.'),
      for (var i = 0; i < data.length; i++)
        Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            child: Row(children: [
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('${i + 1}. ${data[i]['nama']}', style: AppText.label, maxLines: 1, overflow: TextOverflow.ellipsis), Text('NIS ${data[i]['nis']}', style: AppText.caption)])),
              for (final k in kolom) SizedBox(width: kolom.length > 2 ? 46 : 70, child: Text('${data[i]['rekap'][k.$1]}', style: AppText.heading.copyWith(color: k.$3), textAlign: TextAlign.center)),
            ]),
          ),
        ),
    ]);
  }
}

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
    setState(() {
      if ((d['kelas'] as List).isNotEmpty) _kelas = d['kelas'][0]['id'] as int;
      final aktif = (d['tahun_ajaran'] as List).where((t) => t['aktif'] == true);
      _ta = aktif.isNotEmpty ? aktif.first['id'] as int : ((d['tahun_ajaran'] as List).isNotEmpty ? d['tahun_ajaran'][0]['id'] as int : null);
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
      bottom: _kelas == null ? null : tombolEkspor(context, 'nilai', q),
      child: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: AppCard(
            child: Row(children: [
              Expanded(child: PilihanDropdown<int>(label: 'Tahun ajaran', nilai: _ta, item: {for (final t in (opsi?['tahun_ajaran'] as List? ?? [])) t['id'] as int: t['nama'] as String}, onChanged: (v) => setState(() => _ta = v))),
              const SizedBox(width: 10),
              Expanded(child: PilihanDropdown<int>(label: 'Kelas', nilai: _kelas, item: kelasItem, onChanged: (v) => setState(() => _kelas = v))),
            ]),
          ),
        ),
        Expanded(
          child: _kelas == null
              ? const Center(child: CircularProgressIndicator())
              : AsyncBody<Map<String, dynamic>>(
                  key: ValueKey(q.toString()),
                  load: () => api.get('/admin/rekap/nilai', query: {for (final e in q.entries) if (e.value != null) e.key: e.value}),
                  builder: (context, d, _) {
                    final list = (d['data'] as List).cast<Map>();
                    final kkm = d['kkm'] as num;
                    return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
                      Row(children: [Expanded(child: Text('Nilai Rapor per Mata Pelajaran', style: AppText.label)), Text('KKM $kkm', style: AppText.caption)]),
                      const SizedBox(height: 8),
                      for (var i = 0; i < list.length; i++)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: AppCard(
                            child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                              Row(children: [
                                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('${i + 1}. ${list[i]['nama']}', style: AppText.label), Text('NIS ${list[i]['nis']}', style: AppText.caption)])),
                                Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                                  Text('RATA-RATA', style: AppText.overline.copyWith(fontSize: 9, color: AppColors.brand700)),
                                  Row(children: [Text(list[i]['rata_rata']?.toString() ?? '-', style: AppText.title.copyWith(color: AppColors.brand700)), const SizedBox(width: 6), StatusChip(list[i]['predikat'], tone: ChipTone.ok)]),
                                ]),
                              ]),
                              if ((list[i]['mapel'] as List).isNotEmpty) ...[
                                const SizedBox(height: 10),
                                Wrap(spacing: 6, runSpacing: 6, children: [
                                  for (final m in (list[i]['mapel'] as List).cast<Map>())
                                    Container(
                                      width: 74,
                                      padding: const EdgeInsets.symmetric(vertical: 6),
                                      decoration: BoxDecoration(color: (m['nilai'] ?? 100) < kkm ? AppColors.alpaTint : AppColors.field, borderRadius: BorderRadius.circular(8)),
                                      child: Column(children: [
                                        Text(m['kode'] ?? '', style: AppText.caption.copyWith(fontSize: 10, color: (m['nilai'] ?? 100) < kkm ? AppColors.alpa : AppColors.inkMuted)),
                                        Text(m['nilai']?.toString() ?? '-', style: AppText.label.copyWith(color: (m['nilai'] ?? 100) < kkm ? AppColors.alpa : AppColors.ink)),
                                      ]),
                                    ),
                                ]),
                              ] else
                                Text('Belum ada nilai.', style: AppText.caption),
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
      child: Column(children: [
        Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 8), child: Align(alignment: Alignment.centerLeft, child: PilihanChips<String>(pilihan: rentangWaktu, nilai: _rentang, onChanged: (v) => setState(() => _rentang = v)))),
        Expanded(
          child: AsyncBody<Map<String, dynamic>>(
            key: ValueKey(_rentang),
            load: () => api.get('/admin/rekap/guru', query: {'rentang': _rentang}),
            builder: (context, d, _) {
              final t = Map<String, dynamic>.from(d['total']);
              final list = (d['data'] as List).cast<Map>();
              return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
                Row(children: [
                  Expanded(child: KotakAngka(angka: '${t['hadir']}', label: 'Hadir', warna: AppColors.ok700, latar: AppColors.ok100)),
                  const SizedBox(width: 8),
                  Expanded(child: KotakAngka(angka: '${t['terlambat']}', label: 'Terlambat', warna: const Color(0xFF8A6417), latar: AppColors.goldTint)),
                  const SizedBox(width: 8),
                  Expanded(child: KotakAngka(angka: '${t['tidak_hadir']}', label: 'Tidak Hadir', warna: AppColors.alpa, latar: AppColors.alpaTint)),
                ]),
                const SizedBox(height: 12),
                Row(children: [Expanded(child: Text('${list.length} guru', style: AppText.label)), Text(d['periode'], style: AppText.caption)]),
                const SizedBox(height: 8),
                for (final g in list)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: AppCard(
                      onTap: () => context.push('/admin/guru/${g['id']}/absen', extra: g['nama']),
                      child: Row(children: [
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(g['nama'], style: AppText.label),
                            if ((g['keterangan'] as String).isNotEmpty) Text(g['keterangan'], style: AppText.caption, maxLines: 1, overflow: TextOverflow.ellipsis),
                            if (g['persen'] != null) Text('Kehadiran ${g['persen']}%', style: AppText.captionBold.copyWith(color: g['persen'] >= 90 ? AppColors.ok700 : AppColors.alpa)),
                          ]),
                        ),
                        for (final (k, c) in [('hadir', AppColors.ok700), ('terlambat', const Color(0xFF8A6417)), ('tidak_hadir', AppColors.alpa)])
                          SizedBox(width: 40, child: Text('${g[k]}', style: AppText.heading.copyWith(color: c), textAlign: TextAlign.center)),
                      ]),
                    ),
                  ),
                Text('Kolom: hadir, terlambat, tidak hadir. Ketuk nama guru untuk koreksi absen atau reset wajah.', style: AppText.caption, textAlign: TextAlign.center),
              ]);
            },
          ),
        ),
      ]),
    );
  }
}
