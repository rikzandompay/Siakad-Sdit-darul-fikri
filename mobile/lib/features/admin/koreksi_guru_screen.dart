import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';
import 'kelola_screens.dart' show konfirmasi;

String _tgl(DateTime d) =>
    '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
String _jam24(TimeOfDay t) =>
    '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';

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
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: AppColors.surface,
      builder: (_) => _FormKoreksi(guruId: widget.guruId),
    );
    if (ok == true) setState(() => _key = UniqueKey());
  }

  Future<void> _resetWajah() async {
    if (!await konfirmasi(
      context,
      'Reset data wajah?',
      'Acuan wajah dibuat ulang dari foto profil saat guru membuka Presensi Guru.',
      ya: 'Reset',
    )) {
      return;
    }
    if (!mounted) return;
    try {
      final r = await context.read<ApiClient>().delete(
        '/admin/guru/${widget.guruId}/wajah',
      );
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
      bottom: WarningButton(
        label: 'Koreksi Absen',
        icon: Icons.edit_note_rounded,
        onPressed: _koreksi,
      ),
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: () => api.get('/admin/presensi-guru/${widget.guruId}'),
        builder: (context, d, _) {
          final guru = Map<String, dynamic>.from(d['guru']);
          final list = (d['data'] as List).cast<Map>();
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              AppCard(
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Data wajah', style: AppText.label),
                          Text(
                            guru['wajah_terdaftar'] == true
                                ? 'Sudah terdaftar di aplikasi'
                                : 'Belum terdaftar. Guru mendaftar saat absen pertama.',
                            style: AppText.bodySmall,
                          ),
                        ],
                      ),
                    ),
                    if (guru['wajah_terdaftar'] == true)
                      TextButton(
                        onPressed: _resetWajah,
                        style: TextButton.styleFrom(
                          minimumSize: const Size(48, 48),
                          foregroundColor: AppColors.alpa,
                        ),
                        child: const Text('Reset'),
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              if (list.isEmpty)
                const EmptyPanel(
                  icon: Icons.event_busy_outlined,
                  message:
                      'Belum ada catatan absen dalam 30 hari terakhir. Gunakan Koreksi Absen bila guru hadir tetapi tidak tercatat.',
                ),
              for (final p in list)
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: AppCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(p['tanggal'], style: AppText.label),
                            ),
                            StatusChip(
                              p['terlambat'] == true
                                  ? 'Terlambat'
                                  : (p['status'] ?? 'Ditolak'),
                              tone: p['status'] == 'Hadir'
                                  ? (p['terlambat'] == true
                                        ? ChipTone.gold
                                        : ChipTone.ok)
                                  : ChipTone.alpa,
                            ),
                          ],
                        ),
                        if (p['jam_masuk'] != null)
                          Text(
                            'Masuk ${p['jam_masuk']}${p['jam_pulang'] != null ? ', pulang ${p['jam_pulang']}' : ''}',
                            style: AppText.bodySmall,
                          ),
                        if (p['sumber'] == 'admin' && p['keterangan'] != null)
                          Text(p['keterangan'], style: AppText.caption),
                        TandaAbsen(
                          lokasiPalsu: p['lokasi_palsu'],
                          koreksiMandiri: p['koreksi_mandiri'] == true
                              ? ''
                              : null,
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

  Future<TimeOfDay?> _pilihJam(TimeOfDay awal) => showTimePicker(
    context: context,
    initialTime: awal,
    builder: (c, w) => MediaQuery(
      data: MediaQuery.of(c).copyWith(alwaysUse24HourFormat: true),
      child: w!,
    ),
  );

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
        if (_status == 'Hadir' && _pulang != null)
          'jam_pulang': _jam24(_pulang!),
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
          Text('Koreksi Absen Guru', style: AppText.title),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: () async {
              final t = await showDatePicker(
                context: context,
                initialDate: _tanggal,
                firstDate: DateTime.now().subtract(const Duration(days: 120)),
                lastDate: DateTime.now(),
              );
              if (t != null) setState(() => _tanggal = t);
            },
            icon: const Icon(Icons.event),
            label: Text(_tgl(_tanggal)),
            style: OutlinedButton.styleFrom(
              minimumSize: const Size(0, 52),
              alignment: Alignment.centerLeft,
            ),
          ),
          const SizedBox(height: 10),
          PilihanChips<String>(
            pilihan: const {'Hadir': 'Hadir', 'Tidak Hadir': 'Tidak Hadir'},
            nilai: _status,
            onChanged: (v) => setState(() => _status = v),
          ),
          if (_status == 'Hadir') ...[
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () async {
                      final t = await _pilihJam(_masuk);
                      if (t != null) setState(() => _masuk = t);
                    },
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size(0, 52),
                    ),
                    child: Text('Masuk ${_jam24(_masuk)}'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton(
                    onPressed: () async {
                      final t = await _pilihJam(
                        _pulang ?? const TimeOfDay(hour: 14, minute: 0),
                      );
                      if (t != null) setState(() => _pulang = t);
                    },
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size(0, 52),
                    ),
                    child: Text(
                      _pulang == null
                          ? 'Pulang (opsional)'
                          : 'Pulang ${_jam24(_pulang!)}',
                    ),
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: 10),
          TextField(
            controller: _ket,
            maxLength: 200,
            decoration: const InputDecoration(
              labelText: 'Alasan koreksi',
              hintText: 'Wajib diisi, tercatat di rekap',
            ),
          ),
          if (_error != null)
            Text(
              _error!,
              style: AppText.bodySmall.copyWith(color: AppColors.alpa),
            ),
          const SizedBox(height: 8),
          SuccessButton(
            label: 'Simpan Koreksi',
            icon: Icons.save_rounded,
            loading: _simpan,
            onPressed: _simpan ? null : _kirim,
          ),
        ],
      ),
    ),
  );
}
