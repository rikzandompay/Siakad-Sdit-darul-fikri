import 'dart:async';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';
import 'kelola_screens.dart' show konfirmasi;

String _tgl(DateTime d) => '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
String _jam24(TimeOfDay t) => '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';

// ── Lokasi, jam masuk, dan hari libur ──────────────────────────────────

class PengaturanSekolahScreen extends StatefulWidget {
  const PengaturanSekolahScreen({super.key});
  @override
  State<PengaturanSekolahScreen> createState() => _PengaturanSekolahScreenState();
}

class _PengaturanSekolahScreenState extends State<PengaturanSekolahScreen> {
  Key _key = UniqueKey();

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Lokasi dan Jam Absen',
      subtitle: 'Dipakai aplikasi untuk memeriksa absen guru',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () => api.get('/admin/pengaturan-sekolah'),
        builder: (context, d, reload) => ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
          _FormLokasi(awal: d, onSaved: () => setState(() => _key = UniqueKey())),
          const SizedBox(height: 20),
          Row(children: [
            Expanded(child: Text('Hari libur', style: AppText.heading)),
            TextButton.icon(
              onPressed: () async {
                final ok = await showModalBottomSheet<bool>(context: context, isScrollControlled: true, showDragHandle: true, backgroundColor: AppColors.surface, builder: (_) => const _FormLibur());
                if (ok == true) setState(() => _key = UniqueKey());
              },
              icon: const Icon(Icons.add),
              label: const Text('Tambah'),
              style: TextButton.styleFrom(minimumSize: const Size(48, 48)),
            ),
          ]),
          Text('Pada hari libur, guru tidak dihitung tidak hadir.', style: AppText.bodySmall),
          const SizedBox(height: 8),
          if ((d['libur'] as List).isEmpty)
            const AppCard(child: EmptyPanel(icon: Icons.event_busy_outlined, message: 'Belum ada hari libur terdaftar. Tambahkan libur semester atau libur nasional agar rekap guru tidak keliru.'))
          else
            AppCard(
              padding: EdgeInsets.zero,
              child: Column(children: [
                for (var i = 0; i < (d['libur'] as List).length; i++) ...[
                  if (i > 0) const Divider(height: 1, color: AppColors.line),
                  _BarisLibur(l: Map<String, dynamic>.from((d['libur'] as List)[i]), onHapus: () async {
                    final l = (d['libur'] as List)[i];
                    if (await konfirmasi(context, 'Hapus hari libur?', '${l['tanggal']} (${l['keterangan']}) akan dianggap hari masuk lagi.')) {
                      await api.delete('/admin/pengaturan-sekolah/libur/${l['id']}');
                      reload();
                    }
                  }),
                ],
              ]),
            ),
        ]),
      ),
    );
  }
}

class _BarisLibur extends StatelessWidget {
  const _BarisLibur({required this.l, required this.onHapus});
  final Map<String, dynamic> l;
  final VoidCallback onHapus;

  @override
  Widget build(BuildContext context) => ConstrainedBox(
        constraints: const BoxConstraints(minHeight: 56),
        child: Padding(
          padding: const EdgeInsets.only(left: 16),
          child: Row(children: [
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(l['keterangan'], style: AppText.label), Text(l['tanggal'], style: AppText.caption)])),
            IconButton(tooltip: 'Hapus hari libur', onPressed: onHapus, icon: const Icon(Icons.delete_outline, color: AppColors.alpa), style: IconButton.styleFrom(minimumSize: const Size(48, 48))),
          ]),
        ),
      );
}

class _FormLokasi extends StatefulWidget {
  const _FormLokasi({required this.awal, required this.onSaved});
  final Map<String, dynamic> awal;
  final VoidCallback onSaved;
  @override
  State<_FormLokasi> createState() => _FormLokasiState();
}

class _FormLokasiState extends State<_FormLokasi> {
  late final _lat = TextEditingController(text: widget.awal['latitude']?.toString() ?? '');
  late final _lng = TextEditingController(text: widget.awal['longitude']?.toString() ?? '');
  late double _radius = ((widget.awal['radius_meter'] as num?) ?? 100).toDouble().clamp(20, 500);
  late String _jam = widget.awal['jam_masuk'] ?? '07:00';
  late String _jamPulang = widget.awal['jam_pulang'] ?? '16:00';
  late String? _batasMasuk = widget.awal['batas_masuk'] as String?;
  bool _cari = false;
  bool _simpan = false;
  String? _error;

  @override
  void dispose() {
    _lat.dispose();
    _lng.dispose();
    super.dispose();
  }

  Future<void> _pakaiLokasiSaya() async {
    setState(() {
      _cari = true;
      _error = null;
    });
    try {
      if (!await Geolocator.isLocationServiceEnabled()) throw 'GPS mati. Nyalakan lokasi HP lalu coba lagi.';
      var izin = await Geolocator.checkPermission();
      if (izin == LocationPermission.denied) izin = await Geolocator.requestPermission();
      if (izin == LocationPermission.denied || izin == LocationPermission.deniedForever) throw 'Izin lokasi ditolak. Izinkan lokasi untuk MyDarfik di pengaturan HP.';
      final p = await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.high, timeLimit: Duration(seconds: 20)));
      _lat.text = p.latitude.toStringAsFixed(6);
      _lng.text = p.longitude.toStringAsFixed(6);
    } on TimeoutException {
      _error = 'Lokasi belum didapat. Pindah ke area terbuka lalu coba lagi.';
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _cari = false);
    }
  }

  Future<void> _kirim() async {
    setState(() {
      _simpan = true;
      _error = null;
    });
    try {
      await context.read<ApiClient>().put('/admin/pengaturan-sekolah', {
        'latitude': _lat.text.trim().isEmpty ? null : double.tryParse(_lat.text.trim().replaceAll(',', '.')),
        'longitude': _lng.text.trim().isEmpty ? null : double.tryParse(_lng.text.trim().replaceAll(',', '.')),
        'radius_meter': _radius.round(),
        'jam_masuk': _jam,
        'batas_masuk': _batasMasuk,
        'jam_pulang': _jamPulang,
      });
      if (!mounted) return;
      showMessage(context, 'Pengaturan disimpan.');
      widget.onSaved();
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _simpan = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final belumAda = _lat.text.isEmpty || _lng.text.isEmpty;
    return AppCard(
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Text('Titik sekolah', style: AppText.heading),
        const SizedBox(height: 4),
        Text(belumAda ? 'Belum diatur. Selama kosong, guru tidak bisa absen karena lokasi tidak bisa diperiksa. Berdirilah di halaman sekolah lalu ketuk tombol di bawah.' : 'Guru harus berada dalam radius dari titik ini saat absen.', style: AppText.bodySmall),
        const SizedBox(height: 12),
        Row(children: [
          Expanded(child: TextField(controller: _lat, onChanged: (_) => setState(() {}), keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true), decoration: const InputDecoration(labelText: 'Lintang'))),
          const SizedBox(width: 10),
          Expanded(child: TextField(controller: _lng, onChanged: (_) => setState(() {}), keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true), decoration: const InputDecoration(labelText: 'Bujur'))),
        ]),
        const SizedBox(height: 10),
        SecondaryButton(label: _cari ? 'Mencari lokasi...' : 'Pakai lokasi saya sekarang', onPressed: _cari ? null : _pakaiLokasiSaya),
        const SizedBox(height: 16),
        Row(children: [Expanded(child: Text('Radius absen', style: AppText.label)), Text('${_radius.round()} meter', style: AppText.label.copyWith(color: AppColors.brand700))]),
        Slider(value: _radius, min: 20, max: 500, divisions: 48, label: '${_radius.round()} m', onChanged: (v) => setState(() => _radius = v)),
        const SizedBox(height: 4),
        Row(children: [
          Expanded(child: Text('Jam masuk guru', style: AppText.label)),
          OutlinedButton(
            onPressed: () async {
              final p = _jam.split(':');
              final t = await showTimePicker(context: context, initialTime: TimeOfDay(hour: int.tryParse(p[0]) ?? 7, minute: int.tryParse(p[1]) ?? 0), builder: (c, w) => MediaQuery(data: MediaQuery.of(c).copyWith(alwaysUse24HourFormat: true), child: w!));
              if (t != null) setState(() => _jam = _jam2(t));
            },
            style: OutlinedButton.styleFrom(minimumSize: const Size(88, 48)),
            child: Text(_jam),
          ),
        ]),
        const SizedBox(height: 10),
        Row(children: [
          Expanded(child: Text('Jam pulang guru', style: AppText.label)),
          OutlinedButton(
            onPressed: () async {
              final p = _jamPulang.split(':');
              final t = await showTimePicker(context: context, initialTime: TimeOfDay(hour: int.tryParse(p[0]) ?? 16, minute: int.tryParse(p[1]) ?? 0), builder: (c, w) => MediaQuery(data: MediaQuery.of(c).copyWith(alwaysUse24HourFormat: true), child: w!));
              if (t != null) setState(() => _jamPulang = _jam2(t));
            },
            style: OutlinedButton.styleFrom(minimumSize: const Size(88, 48)),
            child: Text(_jamPulang),
          ),
        ]),
        const SizedBox(height: 10),
        Row(children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Batas akhir absen masuk', style: AppText.label),
              Text('Absen masuk ditutup setelah jam ini (opsional)', style: AppText.caption.copyWith(color: AppColors.inkMuted, fontSize: 11)),
            ]),
          ),
          OutlinedButton(
            onPressed: () async {
              final p = (_batasMasuk ?? '18:00').split(':');
              final t = await showTimePicker(context: context, initialTime: TimeOfDay(hour: int.tryParse(p[0]) ?? 18, minute: int.tryParse(p[1]) ?? 0), builder: (c, w) => MediaQuery(data: MediaQuery.of(c).copyWith(alwaysUse24HourFormat: true), child: w!));
              if (t != null) setState(() => _batasMasuk = _jam2(t));
            },
            style: OutlinedButton.styleFrom(minimumSize: const Size(88, 48)),
            child: Text(_batasMasuk ?? 'Pilih jam'),
          ),
        ]),
        if (_error != null) ...[const SizedBox(height: 8), Text(_error!, style: AppText.bodySmall.copyWith(color: AppColors.alpa))],
        const SizedBox(height: 12),
        PrimaryButton(label: 'Simpan Pengaturan', loading: _simpan, onPressed: _simpan ? null : _kirim),
      ]),
    );
  }

  String _jam2(TimeOfDay t) => _jam24(t);
}

class _FormLibur extends StatefulWidget {
  const _FormLibur();
  @override
  State<_FormLibur> createState() => _FormLiburState();
}

class _FormLiburState extends State<_FormLibur> {
  final _ket = TextEditingController();
  DateTimeRange? _rentang;
  bool _simpan = false;
  String? _error;

  @override
  void dispose() {
    _ket.dispose();
    super.dispose();
  }

  Future<void> _pilih() async {
    final now = DateTime.now();
    final r = await showDateRangePicker(context: context, firstDate: DateTime(now.year - 1), lastDate: DateTime(now.year + 2), initialDateRange: _rentang);
    if (r != null) setState(() => _rentang = r);
  }

  Future<void> _kirim() async {
    if (_rentang == null) return setState(() => _error = 'Pilih tanggal libur dulu.');
    setState(() {
      _simpan = true;
      _error = null;
    });
    try {
      await context.read<ApiClient>().post('/admin/pengaturan-sekolah/libur', {
        'tanggal': _tgl(_rentang!.start),
        'tanggal_akhir': _tgl(_rentang!.end),
        'keterangan': _ket.text.trim(),
      });
      if (mounted) Navigator.pop(context, true);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _simpan = false);
    }
  }

  @override
  Widget build(BuildContext context) => Padding(
        padding: EdgeInsets.fromLTRB(20, 0, 20, 20 + MediaQuery.of(context).viewInsets.bottom),
        child: SingleChildScrollView(
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text('Tambah Hari Libur', style: AppText.title),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: _pilih,
              icon: const Icon(Icons.date_range),
              label: Text(_rentang == null ? 'Pilih tanggal atau rentang' : '${_tgl(_rentang!.start)} s/d ${_tgl(_rentang!.end)}'),
              style: OutlinedButton.styleFrom(minimumSize: const Size(0, 52), alignment: Alignment.centerLeft),
            ),
            const SizedBox(height: 10),
            TextField(controller: _ket, maxLength: 150, decoration: const InputDecoration(labelText: 'Keterangan', hintText: 'mis. Libur semester')),
            if (_error != null) Text(_error!, style: AppText.bodySmall.copyWith(color: AppColors.alpa)),
            const SizedBox(height: 8),
            PrimaryButton(label: 'Simpan', loading: _simpan, onPressed: _simpan ? null : _kirim),
          ]),
        ),
      );
}

// ── Pengampu konseling ─────────────────────────────────────────────────

class PengampuScreen extends StatefulWidget {
  const PengampuScreen({super.key});
  @override
  State<PengampuScreen> createState() => _PengampuScreenState();
}

class _PengampuScreenState extends State<PengampuScreen> {
  Map<String, dynamic>? _d;
  final Map<int, int?> _pilihan = {};
  String? _error;
  bool _simpan = false;

  @override
  void initState() {
    super.initState();
    context.read<ApiClient>().get('/admin/konseling-pengampu').then((d) {
      if (!mounted) return;
      setState(() {
        _d = d;
        for (final k in (d['kelas'] as List)) {
          _pilihan[k['id'] as int] = k['pengampu_id'] as int?;
        }
      });
    }).catchError((e) {
      if (mounted) setState(() => _error = e.toString());
    });
  }

  Future<void> _kirim() async {
    setState(() => _simpan = true);
    try {
      await context.read<ApiClient>().put('/admin/konseling-pengampu', {'pengampu': {for (final e in _pilihan.entries) '${e.key}': e.value}});
      if (mounted) {
        showMessage(context, 'Pengampu konseling disimpan.');
        context.pop();
      }
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _simpan = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = _d;
    final guru = {for (final g in (d?['guru'] as List? ?? [])) g['id'] as int: g['nama'] as String};
    return AppPage(
      title: 'Pengampu Konseling',
      subtitle: 'Guru yang membalas konseling wali per kelas',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: d == null ? null : PrimaryButton(label: 'Simpan', loading: _simpan, onPressed: _simpan ? null : _kirim),
      child: _error != null
          ? ErrorPanel(message: _error!)
          : d == null
              ? const Center(child: CircularProgressIndicator())
              : ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
                  for (final k in (d['kelas'] as List).cast<Map>())
                    Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: AppCard(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text(k['nama'], style: AppText.label),
                          Text('${k['jumlah_siswa']} siswa · Wali kelas ${k['wali_kelas'] ?? 'belum diatur'}', style: AppText.caption),
                          const SizedBox(height: 10),
                          DropdownButtonFormField<int?>(
                            initialValue: guru.containsKey(_pilihan[k['id']]) ? _pilihan[k['id']] : null,
                            isExpanded: true,
                            hint: const Text('Belum ada pengampu'),
                            items: [
                              const DropdownMenuItem<int?>(value: null, child: Text('Belum ada pengampu')),
                              for (final e in guru.entries) DropdownMenuItem<int?>(value: e.key, child: Text(e.value, overflow: TextOverflow.ellipsis)),
                            ],
                            onChanged: (v) => setState(() => _pilihan[k['id'] as int] = v),
                            decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 12)),
                          ),
                        ]),
                      ),
                    ),
                ]),
    );
  }
}

// ── Akun wali ──────────────────────────────────────────────────────────

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
      bottom: SecondaryButton(label: 'Sinkronkan dari data siswa', onPressed: _sinkron),
      child: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: TextField(decoration: const InputDecoration(hintText: 'Cari wali, NIS, atau nama siswa', prefixIcon: Icon(Icons.search)), onChanged: (v) {
            _t?.cancel();
            _t = Timer(const Duration(milliseconds: 350), () => setState(() {
                  _q = v.trim();
                  _key = UniqueKey();
                }));
          }),
        ),
        Expanded(
          child: AsyncBody<Map<String, dynamic>>(
            key: _key,
            load: () => api.get('/admin/wali', query: {'q': _q}),
            builder: (context, d, _) {
              final list = (d['data'] as List).cast<Map>();
              return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
                SectionLabel('${d['total']} akun'),
                if (list.isEmpty) EmptyPanel(icon: Icons.family_restroom_outlined, message: _q.isEmpty ? 'Belum ada akun wali. Isi tanggal lahir di Data Siswa, lalu ketuk Sinkronkan.' : 'Tidak ada akun yang cocok dengan "$_q".'),
                for (final w in list)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: AppCard(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Row(children: [
                          Expanded(child: Text(w['nama'], style: AppText.label)),
                          StatusChip(w['aktif'] == true ? 'Aktif' : 'Nonaktif', tone: w['aktif'] == true ? ChipTone.ok : ChipTone.netral),
                        ]),
                        const SizedBox(height: 2),
                        Text('Masuk dengan NIS ${w['login_nis']}', style: AppText.caption),
                        for (final a in (w['anak'] as List).cast<Map>()) Text('${a['nama']}${a['kelas'] != null ? ' · ${a['kelas']}' : ''}', style: AppText.bodySmall),
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

// ── Koreksi absen guru dan reset wajah ─────────────────────────────────

class KoreksiGuruScreen extends StatefulWidget {
  const KoreksiGuruScreen({super.key, required this.guruId, this.nama});
  final int guruId;
  final String? nama;
  @override
  State<KoreksiGuruScreen> createState() => _KoreksiGuruScreenState();
}

class _KoreksiGuruScreenState extends State<KoreksiGuruScreen> {
  Key _key = UniqueKey();

  Future<void> _koreksi() async {
    final ok = await showModalBottomSheet<bool>(context: context, isScrollControlled: true, showDragHandle: true, backgroundColor: AppColors.surface, builder: (_) => _FormKoreksi(guruId: widget.guruId));
    if (ok == true) setState(() => _key = UniqueKey());
  }

  Future<void> _resetWajah() async {
    if (!await konfirmasi(context, 'Reset data wajah?', 'Guru harus mendaftarkan wajah lagi sebelum bisa absen.', ya: 'Reset')) return;
    if (!mounted) return;
    try {
      final r = await context.read<ApiClient>().delete('/admin/guru/${widget.guruId}/wajah');
      if (!mounted) return;
      showMessage(context, r['message'] ?? 'Data wajah dihapus.');
      setState(() => _key = UniqueKey());
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: widget.nama ?? 'Absen Guru',
      subtitle: '30 hari terakhir',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: WarningButton(label: 'Koreksi Absen', icon: Icons.edit_note_rounded, onPressed: _koreksi),
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () => api.get('/admin/presensi-guru/${widget.guruId}'),
        builder: (context, d, _) {
          final guru = Map<String, dynamic>.from(d['guru']);
          final list = (d['data'] as List).cast<Map>();
          return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
            AppCard(
              child: Row(children: [
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Data wajah', style: AppText.label),
                  Text(guru['wajah_terdaftar'] == true ? 'Sudah terdaftar di aplikasi' : 'Belum terdaftar. Guru mendaftar saat absen pertama.', style: AppText.bodySmall),
                ])),
                if (guru['wajah_terdaftar'] == true) TextButton(onPressed: _resetWajah, style: TextButton.styleFrom(minimumSize: const Size(48, 48), foregroundColor: AppColors.alpa), child: const Text('Reset')),
              ]),
            ),
            const SizedBox(height: 14),
            if (list.isEmpty) const EmptyPanel(icon: Icons.event_busy_outlined, message: 'Belum ada catatan absen dalam 30 hari terakhir. Gunakan Koreksi Absen bila guru hadir tetapi tidak tercatat.'),
            for (final p in list)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: AppCard(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [
                      Expanded(child: Text(p['tanggal'], style: AppText.label)),
                      StatusChip(p['terlambat'] == true ? 'Terlambat' : p['status'], tone: p['status'] == 'Hadir' ? (p['terlambat'] == true ? ChipTone.gold : ChipTone.ok) : ChipTone.alpa),
                    ]),
                    if (p['jam_masuk'] != null) Text('Masuk ${p['jam_masuk']}${p['jam_pulang'] != null ? ', pulang ${p['jam_pulang']}' : ''}', style: AppText.bodySmall),
                    if (p['sumber'] == 'admin' && p['keterangan'] != null) Text(p['keterangan'], style: AppText.caption),
                  ]),
                ),
              ),
          ]);
        },
      ),
    );
  }
}

class _FormKoreksi extends StatefulWidget {
  const _FormKoreksi({required this.guruId});
  final int guruId;
  @override
  State<_FormKoreksi> createState() => _FormKoreksiState();
}

class _FormKoreksiState extends State<_FormKoreksi> {
  DateTime _tanggal = DateTime.now();
  String _status = 'Hadir';
  TimeOfDay _masuk = const TimeOfDay(hour: 7, minute: 0);
  TimeOfDay? _pulang;
  final _ket = TextEditingController();
  bool _simpan = false;
  String? _error;

  @override
  void dispose() {
    _ket.dispose();
    super.dispose();
  }

  Future<TimeOfDay?> _pilihJam(TimeOfDay awal) => showTimePicker(context: context, initialTime: awal, builder: (c, w) => MediaQuery(data: MediaQuery.of(c).copyWith(alwaysUse24HourFormat: true), child: w!));

  Future<void> _kirim() async {
    setState(() {
      _simpan = true;
      _error = null;
    });
    try {
      await context.read<ApiClient>().post('/admin/presensi-guru/koreksi', {
        'guru_id': widget.guruId,
        'tanggal': _tgl(_tanggal),
        'status': _status,
        if (_status == 'Hadir') 'jam_masuk': _jam24(_masuk),
        if (_status == 'Hadir' && _pulang != null) 'jam_pulang': _jam24(_pulang!),
        'keterangan': _ket.text.trim(),
      });
      if (mounted) Navigator.pop(context, true);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _simpan = false);
    }
  }

  @override
  Widget build(BuildContext context) => Padding(
        padding: EdgeInsets.fromLTRB(20, 0, 20, 20 + MediaQuery.of(context).viewInsets.bottom),
        child: SingleChildScrollView(
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text('Koreksi Absen Guru', style: AppText.title),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: () async {
                final t = await showDatePicker(context: context, initialDate: _tanggal, firstDate: DateTime.now().subtract(const Duration(days: 120)), lastDate: DateTime.now());
                if (t != null) setState(() => _tanggal = t);
              },
              icon: const Icon(Icons.event),
              label: Text(_tgl(_tanggal)),
              style: OutlinedButton.styleFrom(minimumSize: const Size(0, 52), alignment: Alignment.centerLeft),
            ),
            const SizedBox(height: 10),
            PilihanChips<String>(pilihan: const {'Hadir': 'Hadir', 'Tidak Hadir': 'Tidak Hadir'}, nilai: _status, onChanged: (v) => setState(() => _status = v)),
            if (_status == 'Hadir') ...[
              const SizedBox(height: 10),
              Row(children: [
                Expanded(child: OutlinedButton(onPressed: () async { final t = await _pilihJam(_masuk); if (t != null) setState(() => _masuk = t); }, style: OutlinedButton.styleFrom(minimumSize: const Size(0, 52)), child: Text('Masuk ${_jam24(_masuk)}'))),
                const SizedBox(width: 10),
                Expanded(child: OutlinedButton(onPressed: () async { final t = await _pilihJam(_pulang ?? const TimeOfDay(hour: 14, minute: 0)); if (t != null) setState(() => _pulang = t); }, style: OutlinedButton.styleFrom(minimumSize: const Size(0, 52)), child: Text(_pulang == null ? 'Pulang (opsional)' : 'Pulang ${_jam24(_pulang!)}'))),
              ]),
            ],
            const SizedBox(height: 10),
            TextField(controller: _ket, maxLength: 200, decoration: const InputDecoration(labelText: 'Alasan koreksi', hintText: 'Wajib diisi, tercatat di rekap')),
            if (_error != null) Text(_error!, style: AppText.bodySmall.copyWith(color: AppColors.alpa)),
            const SizedBox(height: 8),
            SuccessButton(label: 'Simpan Koreksi', icon: Icons.save_rounded, loading: _simpan, onPressed: _simpan ? null : _kirim),
          ]),
        ),
      );
}
