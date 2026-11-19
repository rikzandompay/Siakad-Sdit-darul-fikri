import 'dart:async';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'kelola_screens.dart' show konfirmasi;

String _tgl(DateTime d) =>
    '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
String _jam24(TimeOfDay t) =>
    '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';

class PengaturanSekolahScreen extends StatefulWidget {
  const PengaturanSekolahScreen({super.key});
  @override
  State<PengaturanSekolahScreen> createState() =>
      _PengaturanSekolahScreenState();
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
        builder: (context, d, reload) => ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
          children: [
            _FormLokasi(
              awal: d,
              onSaved: () => setState(() => _key = UniqueKey()),
            ),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(child: Text('Hari libur', style: AppText.heading)),
                TextButton.icon(
                  onPressed: () async {
                    final ok = await showModalBottomSheet<bool>(
                      context: context,
                      isScrollControlled: true,
                      showDragHandle: true,
                      backgroundColor: AppColors.surface,
                      builder: (_) => const _FormLibur(),
                    );
                    if (ok == true) setState(() => _key = UniqueKey());
                  },
                  icon: const Icon(Icons.add),
                  label: const Text('Tambah'),
                  style: TextButton.styleFrom(minimumSize: const Size(48, 48)),
                ),
              ],
            ),
            Text(
              'Pada hari libur, guru tidak dihitung tidak hadir.',
              style: AppText.bodySmall,
            ),
            const SizedBox(height: 8),
            if ((d['libur'] as List).isEmpty)
              const AppCard(
                child: EmptyPanel(
                  icon: Icons.event_busy_outlined,
                  message:
                      'Belum ada hari libur terdaftar. Tambahkan libur semester atau libur nasional agar rekap guru tidak keliru.',
                ),
              )
            else
              AppCard(
                padding: EdgeInsets.zero,
                child: Column(
                  children: [
                    for (var i = 0; i < (d['libur'] as List).length; i++) ...[
                      if (i > 0)
                        const Divider(height: 1, color: AppColors.line),
                      _BarisLibur(
                        l: Map<String, dynamic>.from((d['libur'] as List)[i]),
                        onHapus: () async {
                          final l = (d['libur'] as List)[i];
                          if (await konfirmasi(
                            context,
                            'Hapus hari libur?',
                            '${l['tanggal']} (${l['keterangan']}) akan dianggap hari masuk lagi.',
                          )) {
                            await api.delete(
                              '/admin/pengaturan-sekolah/libur/${l['id']}',
                            );
                            reload();
                          }
                        },
                      ),
                    ],
                  ],
                ),
              ),
          ],
        ),
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
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l['keterangan'], style: AppText.label),
                Text(l['tanggal'], style: AppText.caption),
              ],
            ),
          ),
          IconButton(
            tooltip: 'Hapus hari libur',
            onPressed: onHapus,
            icon: const Icon(Icons.delete_outline, color: AppColors.alpa),
            style: IconButton.styleFrom(minimumSize: const Size(48, 48)),
          ),
        ],
      ),
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
  late final _lat = TextEditingController(
    text: widget.awal['latitude']?.toString() ?? '',
  );
  late final _lng = TextEditingController(
    text: widget.awal['longitude']?.toString() ?? '',
  );
  late double _radius = ((widget.awal['radius_meter'] as num?) ?? 100)
      .toDouble()
      .clamp(20, 500);
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

  Future<String?> _pilihJam(String awal, {required int defaultJam}) async {
    final p = awal.split(':');
    final t = await showTimePicker(
      context: context,
      initialTime: TimeOfDay(
        hour: int.tryParse(p[0]) ?? defaultJam,
        minute: p.length > 1 ? (int.tryParse(p[1]) ?? 0) : 0,
      ),
      builder: (c, w) => MediaQuery(
        data: MediaQuery.of(c).copyWith(alwaysUse24HourFormat: true),
        child: w!,
      ),
    );
    return t == null ? null : _jam24(t);
  }

  Future<void> _pakaiLokasiSaya() async {
    setState(() {
      _cari = true;
      _error = null;
    });
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        throw 'GPS mati. Nyalakan lokasi HP lalu coba lagi.';
      }
      var izin = await Geolocator.checkPermission();
      if (izin == LocationPermission.denied) {
        izin = await Geolocator.requestPermission();
      }
      if (izin == LocationPermission.denied ||
          izin == LocationPermission.deniedForever) {
        throw 'Izin lokasi ditolak. Izinkan lokasi untuk MyDarfik di pengaturan HP.';
      }
      final p = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 20),
        ),
      );
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
        'latitude': _lat.text.trim().isEmpty
            ? null
            : double.tryParse(_lat.text.trim().replaceAll(',', '.')),
        'longitude': _lng.text.trim().isEmpty
            ? null
            : double.tryParse(_lng.text.trim().replaceAll(',', '.')),
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text('Titik sekolah', style: AppText.heading),
          const SizedBox(height: 4),
          Text(
            belumAda
                ? 'Belum diatur. Selama kosong, guru tidak bisa absen karena lokasi tidak bisa diperiksa. Berdirilah di halaman sekolah lalu ketuk tombol di bawah.'
                : 'Guru harus berada dalam radius dari titik ini saat absen.',
            style: AppText.bodySmall,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _lat,
                  onChanged: (_) => setState(() {}),
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                    signed: true,
                  ),
                  decoration: const InputDecoration(labelText: 'Lintang'),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: TextField(
                  controller: _lng,
                  onChanged: (_) => setState(() {}),
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                    signed: true,
                  ),
                  decoration: const InputDecoration(labelText: 'Bujur'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          SecondaryButton(
            label: _cari ? 'Mencari lokasi...' : 'Pakai lokasi saya sekarang',
            onPressed: _cari ? null : _pakaiLokasiSaya,
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: Text('Radius absen', style: AppText.label)),
              Text(
                '${_radius.round()} meter',
                style: AppText.label.copyWith(color: AppColors.brand700),
              ),
            ],
          ),
          Slider(
            value: _radius,
            min: 20,
            max: 500,
            divisions: 48,
            label: '${_radius.round()} m',
            onChanged: (v) => setState(() => _radius = v),
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              Expanded(child: Text('Jam masuk guru', style: AppText.label)),
              OutlinedButton(
                onPressed: () async {
                  final j = await _pilihJam(_jam, defaultJam: 7);
                  if (j != null) setState(() => _jam = j);
                },
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size(88, 48),
                ),
                child: Text(_jam),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(child: Text('Jam pulang guru', style: AppText.label)),
              OutlinedButton(
                onPressed: () async {
                  final j = await _pilihJam(_jamPulang, defaultJam: 16);
                  if (j != null) setState(() => _jamPulang = j);
                },
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size(88, 48),
                ),
                child: Text(_jamPulang),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Batas akhir absen masuk', style: AppText.label),
                    Text(
                      'Absen masuk ditutup setelah jam ini (opsional)',
                      style: AppText.caption.copyWith(
                        color: AppColors.inkMuted,
                        fontSize: 11,
                      ),
                    ),
                  ],
                ),
              ),
              OutlinedButton(
                onPressed: () async {
                  final j = await _pilihJam(
                    _batasMasuk ?? '18:00',
                    defaultJam: 18,
                  );
                  if (j != null) setState(() => _batasMasuk = j);
                },
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size(88, 48),
                ),
                child: Text(_batasMasuk ?? 'Pilih jam'),
              ),
            ],
          ),
          if (_error != null) ...[
            const SizedBox(height: 8),
            Text(
              _error!,
              style: AppText.bodySmall.copyWith(color: AppColors.alpa),
            ),
          ],
          const SizedBox(height: 12),
          PrimaryButton(
            label: 'Simpan Pengaturan',
            loading: _simpan,
            onPressed: _simpan ? null : _kirim,
          ),
        ],
      ),
    );
  }
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
    final r = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 2),
      initialDateRange: _rentang,
    );
    if (r != null) setState(() => _rentang = r);
  }

  Future<void> _kirim() async {
    if (_rentang == null) {
      return setState(() => _error = 'Pilih tanggal libur dulu.');
    }
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
    padding: EdgeInsets.fromLTRB(
      20,
      0,
      20,
      20 + MediaQuery.of(context).viewInsets.bottom,
    ),
    child: SingleChildScrollView(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text('Tambah Hari Libur', style: AppText.title),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _pilih,
            icon: const Icon(Icons.date_range),
            label: Text(
              _rentang == null
                  ? 'Pilih tanggal atau rentang'
                  : '${_tgl(_rentang!.start)} s/d ${_tgl(_rentang!.end)}',
            ),
            style: OutlinedButton.styleFrom(
              minimumSize: const Size(0, 52),
              alignment: Alignment.centerLeft,
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _ket,
            maxLength: 150,
            decoration: const InputDecoration(
              labelText: 'Keterangan',
              hintText: 'mis. Libur semester',
            ),
          ),
          if (_error != null)
            Text(
              _error!,
              style: AppText.bodySmall.copyWith(color: AppColors.alpa),
            ),
          const SizedBox(height: 8),
          PrimaryButton(
            label: 'Simpan',
            loading: _simpan,
            onPressed: _simpan ? null : _kirim,
          ),
        ],
      ),
    ),
  );
}
