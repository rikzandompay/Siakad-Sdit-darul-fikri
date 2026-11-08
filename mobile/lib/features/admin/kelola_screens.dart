import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';
import '../shared/pengumuman_screen.dart';

Future<bool> konfirmasi(BuildContext context, String judul, String isi, {String ya = 'Hapus'}) async =>
    await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        title: Text(judul),
        content: Text(isi),
        actions: [TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Batal')), TextButton(onPressed: () => Navigator.pop(c, true), child: Text(ya, style: const TextStyle(color: AppColors.alpa)))],
      ),
    ) ??
    false;

class PengumumanAdminScreen extends StatefulWidget {
  const PengumumanAdminScreen({super.key});
  @override
  State<PengumumanAdminScreen> createState() => _PengumumanAdminScreenState();
}

class _PengumumanAdminScreenState extends State<PengumumanAdminScreen> {
  Key _key = UniqueKey();

  Future<void> _form([Map<String, dynamic>? p]) async {
    final ok = await showModalBottomSheet<bool>(context: context, isScrollControlled: true, showDragHandle: true, backgroundColor: AppColors.surface, builder: (_) => _FormPengumuman(p: p));
    if (ok == true) setState(() => _key = UniqueKey());
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Pengumuman',
      subtitle: 'Kelola informasi untuk wali dan guru',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: PrimaryButton(label: 'Buat Pengumuman', onPressed: () => _form()),
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () => api.get('/admin/pengumuman'),
        builder: (context, d, reload) => ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
          if ((d['data'] as List).isEmpty) const EmptyPanel(icon: Icons.campaign_outlined, message: 'Belum ada pengumuman.'),
          for (final p in (d['data'] as List).cast<Map>())
            PengumumanCard(
              p: Map<String, dynamic>.from(p),
              aksi: PopupMenuButton<String>(
                tooltip: 'Pilihan',
                onSelected: (v) async {
                  if (v == 'ubah') return _form(Map<String, dynamic>.from(p));
                  if (await konfirmasi(context, 'Hapus pengumuman?', 'Pengumuman juga hilang dari aplikasi wali dan guru.')) {
                    await api.delete('/admin/pengumuman/${p['id']}');
                    reload();
                  }
                },
                itemBuilder: (_) => const [PopupMenuItem(value: 'ubah', child: Text('Ubah')), PopupMenuItem(value: 'hapus', child: Text('Hapus'))],
              ),
            ),
        ]),
      ),
    );
  }
}

class _FormPengumuman extends StatefulWidget {
  const _FormPengumuman({this.p});
  final Map<String, dynamic>? p;
  @override
  State<_FormPengumuman> createState() => _FormPengumumanState();
}

class _FormPengumumanState extends State<_FormPengumuman> {
  late final _judul = TextEditingController(text: widget.p?['judul']);
  late final _isi = TextEditingController(text: widget.p?['isi']);
  late String _sasaran = widget.p?['sasaran'] ?? 'Semua';
  late bool _penting = widget.p?['penting'] == true;
  bool _simpan = false;
  String? _error;

  @override
  void dispose() {
    _judul.dispose();
    _isi.dispose();
    super.dispose();
  }

  Future<void> _kirim() async {
    setState(() {
      _simpan = true;
      _error = null;
    });
    final body = {'judul': _judul.text.trim(), 'isi': _isi.text.trim(), 'sasaran': _sasaran, 'penting': _penting};
    try {
      final api = context.read<ApiClient>();
      widget.p == null ? await api.post('/admin/pengumuman', body) : await api.put('/admin/pengumuman/${widget.p!['id']}', body);
      if (mounted) Navigator.pop(context, true);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _simpan = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(20, 0, 20, 20 + MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Text(widget.p == null ? 'Buat Pengumuman' : 'Ubah Pengumuman', style: AppText.title),
          const SizedBox(height: 12),
          TextField(controller: _judul, maxLength: 150, decoration: const InputDecoration(labelText: 'Judul')),
          const SizedBox(height: 8),
          TextField(controller: _isi, minLines: 4, maxLines: 8, maxLength: 5000, decoration: const InputDecoration(labelText: 'Isi pengumuman')),
          const SizedBox(height: 8),
          Text('DITUJUKAN UNTUK', style: AppText.overline.copyWith(fontSize: 10)),
          const SizedBox(height: 6),
          PilihanChips<String>(pilihan: const {'Semua': 'Semua', 'Guru': 'Guru', 'Wali Murid': 'Wali Murid'}, nilai: _sasaran, onChanged: (v) => setState(() => _sasaran = v)),
          SwitchListTile(value: _penting, onChanged: (v) => setState(() => _penting = v), contentPadding: EdgeInsets.zero, title: const Text('Tandai penting'), subtitle: const Text('Tampil paling atas')),
          if (_error != null) Text(_error!, style: AppText.bodySmall.copyWith(color: AppColors.alpa)),
          const SizedBox(height: 8),
          PrimaryButton(label: widget.p == null ? 'Terbitkan' : 'Simpan', loading: _simpan, onPressed: _kirim),
        ]),
      ),
    );
  }
}

class TahunAjaranScreen extends StatefulWidget {
  const TahunAjaranScreen({super.key});
  @override
  State<TahunAjaranScreen> createState() => _TahunAjaranScreenState();
}

class _TahunAjaranScreenState extends State<TahunAjaranScreen> {
  Key _key = UniqueKey();

  Future<void> _aksi(Future<Map<String, dynamic>> Function() f) async {
    try {
      final r = await f();
      if (mounted) showMessage(context, r['message'] ?? 'Tersimpan.');
      setState(() => _key = UniqueKey());
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  Future<void> _tambah() async {
    final tahun = TextEditingController(text: '${DateTime.now().year}/${DateTime.now().year + 1}');
    var semester = 'Ganjil';
    var aktif = false;
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: AppColors.surface,
      builder: (c) => StatefulBuilder(
        builder: (c, set) => Padding(
          padding: EdgeInsets.fromLTRB(20, 0, 20, 20 + MediaQuery.of(c).viewInsets.bottom),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text('Tambah Tahun Ajaran', style: AppText.title),
            const SizedBox(height: 12),
            TextField(controller: tahun, decoration: const InputDecoration(labelText: 'Tahun ajaran', hintText: '2026/2027')),
            const SizedBox(height: 10),
            PilihanChips<String>(pilihan: const {'Ganjil': 'Ganjil', 'Genap': 'Genap'}, nilai: semester, onChanged: (v) => set(() => semester = v)),
            SwitchListTile(value: aktif, onChanged: (v) => set(() => aktif = v), contentPadding: EdgeInsets.zero, title: const Text('Jadikan semester aktif')),
            PrimaryButton(label: 'Simpan', onPressed: () => Navigator.pop(c, true)),
          ]),
        ),
      ),
    );
    if (ok == true && mounted) await _aksi(() => context.read<ApiClient>().post('/admin/tahun-ajaran', {'tahun': tahun.text.trim(), 'semester': semester, 'aktif': aktif}));
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Tahun Ajaran',
      subtitle: 'Kelola tahun ajaran & semester',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: PrimaryButton(label: 'Tambah Tahun Ajaran', onPressed: _tambah),
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () => api.get('/admin/tahun-ajaran'),
        builder: (context, d, _) {
          final list = (d['data'] as List).cast<Map>();
          return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: AppColors.brand50, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.brand200)),
              child: Text('Hanya satu semester yang aktif. Mengaktifkan semester lain akan menonaktifkan semester yang sedang aktif.', style: AppText.bodySmall.copyWith(color: AppColors.brand800)),
            ),
            const SizedBox(height: 12),
            Row(children: [Expanded(child: Text('Daftar Semester', style: AppText.label)), Text('${list.length} semester', style: AppText.caption)]),
            const SizedBox(height: 8),
            for (final t in list)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: AppCard(
                  child: Row(children: [
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('${t['tahun']} - ${t['semester']}', style: AppText.heading),
                        const SizedBox(height: 6),
                        if (t['aktif'] == true)
                          Text('Semester berjalan', style: AppText.captionBold.copyWith(color: AppColors.ok700))
                        else
                          TextButton(
                            onPressed: () => _aksi(() => api.post('/admin/tahun-ajaran/${t['id']}/aktif')),
                            style: TextButton.styleFrom(backgroundColor: AppColors.brand100, padding: const EdgeInsets.symmetric(horizontal: 14)),
                            child: const Text('Jadikan Aktif'),
                          ),
                      ]),
                    ),
                    Column(children: [
                      StatusChip(t['aktif'] == true ? 'Aktif' : 'Tidak Aktif', tone: t['aktif'] == true ? ChipTone.ok : ChipTone.netral),
                      if (t['bisa_dihapus'] == true)
                        IconButton(
                          tooltip: 'Hapus',
                          icon: const Icon(Icons.delete_outline, color: AppColors.alpa),
                          onPressed: () async {
                            if (await konfirmasi(context, 'Hapus ${t['nama']}?', 'Semester tanpa nilai rapor ini akan dihapus.')) _aksi(() => api.delete('/admin/tahun-ajaran/${t['id']}'));
                          },
                        ),
                    ]),
                  ]),
                ),
              ),
          ]);
        },
      ),
    );
  }
}

class MapelScreen extends StatefulWidget {
  const MapelScreen({super.key});
  @override
  State<MapelScreen> createState() => _MapelScreenState();
}

class _MapelScreenState extends State<MapelScreen> {
  Key _key = UniqueKey();

  Future<void> _tambah() async {
    final kode = TextEditingController();
    final nama = TextEditingController();
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: AppColors.surface,
      builder: (c) => Padding(
        padding: EdgeInsets.fromLTRB(20, 0, 20, 20 + MediaQuery.of(c).viewInsets.bottom),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Text('Tambah Mata Pelajaran', style: AppText.title),
          const SizedBox(height: 12),
          TextField(controller: kode, maxLength: 20, textCapitalization: TextCapitalization.characters, decoration: const InputDecoration(labelText: 'Kode', hintText: 'MTK')),
          const SizedBox(height: 8),
          TextField(controller: nama, maxLength: 100, decoration: const InputDecoration(labelText: 'Nama mata pelajaran')),
          const SizedBox(height: 4),
          Text('Kode pelajaran harus unik. Mata pelajaran dipakai pada jadwal mengajar.', style: AppText.caption),
          const SizedBox(height: 12),
          PrimaryButton(label: 'Simpan', onPressed: () => Navigator.pop(c, true)),
        ]),
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await context.read<ApiClient>().post('/admin/mapel', {'kode': kode.text.trim(), 'nama': nama.text.trim()});
      if (mounted) showMessage(context, 'Mata pelajaran ditambahkan.');
      setState(() => _key = UniqueKey());
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Mata Pelajaran',
      subtitle: 'Kode dan nama mata pelajaran',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: PrimaryButton(label: 'Tambah Mata Pelajaran', onPressed: _tambah),
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () => api.get('/admin/mapel'),
        builder: (context, d, reload) => ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
          for (final m in (d['data'] as List).cast<Map>())
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: AppCard(
                child: Row(children: [
                  BadgeBox(top: (m['kode'] as String).length > 4 ? (m['kode'] as String).substring(0, 4) : m['kode']),
                  const SizedBox(width: 12),
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(m['nama'], style: AppText.label), Text('${m['jadwal']} sesi di jadwal', style: AppText.caption)])),
                  if (m['jadwal'] == 0)
                    IconButton(
                      tooltip: 'Hapus',
                      icon: const Icon(Icons.delete_outline, color: AppColors.alpa),
                      onPressed: () async {
                        if (!await konfirmasi(context, 'Hapus ${m['nama']}?', 'Mata pelajaran ini belum dipakai di jadwal.')) return;
                        try {
                          await api.delete('/admin/mapel/${m['id']}');
                          reload();
                        } on ApiException catch (e) {
                          if (context.mounted) showMessage(context, e.message, error: true);
                        }
                      },
                    ),
                ]),
              ),
            ),
        ]),
      ),
    );
  }
}
