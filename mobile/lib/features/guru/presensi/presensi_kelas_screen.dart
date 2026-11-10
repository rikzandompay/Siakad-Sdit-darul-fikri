import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/format.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';

/// Isi presensi H/I/S/A untuk satu jadwal pada hari ini.
class PresensiKelasScreen extends StatefulWidget {
  const PresensiKelasScreen({super.key, required this.jadwalId});
  final int jadwalId;

  @override
  State<PresensiKelasScreen> createState() => _PresensiKelasScreenState();
}

class _PresensiKelasScreenState extends State<PresensiKelasScreen> {
  Map<String, dynamic>? _data;
  final Map<int, String> _status = {};
  String? _error;
  bool _saving = false;
  bool _semua = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final d = await context.read<ApiClient>().get('/guru/presensi/jadwal/${widget.jadwalId}');
      setState(() {
        _data = d;
        for (final s in (d['data'] as List).cast<Map>()) {
          // Default Hadir untuk yang belum diisi, seperti di web.
          _status[s['siswa_id'] as int] = (s['status'] as String?) ?? 'H';
        }
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    }
  }

  Map<String, int> get _rekap {
    final r = {'H': 0, 'I': 0, 'S': 0, 'A': 0};
    for (final v in _status.values) {
      r[v] = r[v]! + 1;
    }
    return r;
  }

  Future<void> _simpan() async {
    setState(() => _saving = true);
    try {
      final res = await context.read<ApiClient>().post('/guru/presensi/jadwal/${widget.jadwalId}', {
        'tanggal': _data!['tanggal'],
        'presensi': [for (final e in _status.entries) {'siswa_id': e.key, 'status': e.value}],
      });
      if (!mounted) return;
      final j = _data!['jadwal'];
      context.pushReplacement('/guru/presensi/tersimpan', extra: {
        'rekap': res['rekap'],
        'judul': '${j['kelas']} · ${j['mata_pelajaran']} · ${_status.length} siswa',
        'kelas_id': j['kelas_id'],
      });
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = _data;
    if (d == null) {
      return AppPage(title: 'Presensi Kelas', child: _error == null ? const Padding(padding: EdgeInsets.only(top: 80), child: Center(child: CircularProgressIndicator())) : ErrorPanel(message: _error!, onRetry: _load));
    }
    final j = d['jadwal'];
    final siswa = (d['data'] as List).cast<Map>();
    final tampil = _semua || siswa.length <= 8 ? siswa : siswa.take(8).toList();
    final r = _rekap;
    final ringkas = [if (r['H']! > 0) '${r['H']} Hadir', if (r['I']! > 0) '${r['I']} Izin', if (r['S']! > 0) '${r['S']} Sakit', if (r['A']! > 0) '${r['A']} Alpa'].join(', ');

    return AppPage(
      title: 'Presensi ${j['kelas']}',
      subtitle: '${j['mata_pelajaran']} · ${Fmt.tanggalPanjang(Fmt.parse(d['tanggal']))}',
      bottom: SuccessButton(label: 'Simpan Presensi ($ringkas)', icon: Icons.check_circle_outline, loading: _saving, onPressed: siswa.isEmpty ? null : _simpan),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Row(children: [
          Expanded(child: Text('${siswa.length} siswa', style: AppText.heading)),
          SizedBox(
            width: 144,
            child: SecondaryButton(label: 'Semua Hadir', icon: Icons.done_all, color: AppColors.ok700, onPressed: () => setState(() => _status.updateAll((_, _) => 'H'))),
          ),
        ]),
        const SizedBox(height: 10),
        const Wrap(spacing: 6, children: [StatusChip('H Hadir', tone: ChipTone.ok), StatusChip('I Izin', tone: ChipTone.gold), StatusChip('S Sakit', tone: ChipTone.sakit), StatusChip('A Alpa', tone: ChipTone.alpa)]),
        const SizedBox(height: 12),
        if (siswa.isEmpty) const EmptyPanel(icon: Icons.group_off_outlined, message: 'Tidak ada siswa aktif di kelas ini.'),
        for (final s in tampil)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: AppCard(
              padding: const EdgeInsets.fromLTRB(14, 10, 10, 10),
              child: Row(children: [
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(s['nama'], style: AppText.label, maxLines: 1, overflow: TextOverflow.ellipsis),
                    Text('NIS: ${s['nis']}', style: AppText.caption),
                  ]),
                ),
                for (final kode in const ['H', 'I', 'S', 'A']) _StatusButton(kode: kode, aktif: _status[s['siswa_id']] == kode, onTap: () => setState(() => _status[s['siswa_id'] as int] = kode), nama: s['nama']),
              ]),
            ),
          ),
        if (!_semua && siswa.length > 8)
          TextButton.icon(
            onPressed: () => setState(() => _semua = true),
            icon: const Icon(Icons.expand_more),
            label: Text('Lihat semua ${siswa.length} siswa'),
          ),
      ]),
    );
  }
}

class _StatusButton extends StatelessWidget {
  const _StatusButton({required this.kode, required this.aktif, required this.onTap, required this.nama});
  final String kode;
  final bool aktif;
  final VoidCallback onTap;
  final String nama;

  static const _label = {'H': 'Hadir', 'I': 'Izin', 'S': 'Sakit', 'A': 'Alpa'};
  static const _warna = {'H': AppColors.ok700, 'I': AppColors.gold, 'S': AppColors.sakit, 'A': AppColors.alpa};

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: '$nama ${_label[kode]}',
      selected: aktif,
      button: true,
      child: Padding(
        padding: const EdgeInsets.only(left: 6),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(10),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 150),
            width: 44,
            height: 44,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: aktif ? _warna[kode] : AppColors.surface,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: aktif ? _warna[kode]! : AppColors.line),
            ),
            child: Text(kode, style: AppText.label.copyWith(color: aktif ? Colors.white : AppColors.inkMuted)),
          ),
        ),
      ),
    );
  }
}
