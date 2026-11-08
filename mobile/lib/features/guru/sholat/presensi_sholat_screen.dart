import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';

/// Daftar siswa + status sholat. Hadir terisi dari scan QR; guru bisa mengoreksi
/// status satu per satu (mis. kartu tertinggal) lalu menyimpan.
class PresensiSholatScreen extends StatefulWidget {
  const PresensiSholatScreen({super.key, required this.kelasId, required this.jenis});
  final int kelasId;
  final String jenis;

  @override
  State<PresensiSholatScreen> createState() => _PresensiSholatScreenState();
}

class _PresensiSholatScreenState extends State<PresensiSholatScreen> {
  Map<String, dynamic>? _data;
  final Map<int, String?> _ubah = {};
  String? _error;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final d = await context.read<ApiClient>().get('/guru/sholat/kelas/${widget.kelasId}/siswa', query: {'jenis': widget.jenis});
      setState(() {
        _data = d;
        _ubah.clear();
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    }
  }

  Future<void> _scan() async {
    await context.push('/guru/sholat/${widget.kelasId}/${widget.jenis}/scan');
    _load();
  }

  Future<void> _simpan() async {
    setState(() => _saving = true);
    try {
      final res = await context.read<ApiClient>().post('/guru/sholat/kelas/${widget.kelasId}', {
        'jenis': widget.jenis,
        'presensi': [for (final e in _ubah.entries) {'siswa_id': e.key, 'status': e.value}],
      });
      if (!mounted) return;
      context.pushReplacement('/guru/sholat-tersimpan', extra: {
        'rekap': res['rekap'],
        'judul': '${_data!['kelas']['nama']} · Sholat ${widget.jenis} · ${res['rekap']['total']} siswa',
      });
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _pilihStatus(Map s) async {
    final pilihan = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      backgroundColor: AppColors.surface,
      builder: (c) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Padding(padding: const EdgeInsets.only(bottom: 8), child: Text(s['nama'], style: AppText.heading)),
          ListTile(leading: const Icon(Icons.check_circle, color: AppColors.ok600), title: const Text('Hadir'), onTap: () => Navigator.pop(c, 'Hadir')),
          ListTile(leading: const Icon(Icons.cancel, color: AppColors.alpa), title: const Text('Tidak Hadir'), onTap: () => Navigator.pop(c, 'Tidak Hadir')),
          const SizedBox(height: 8),
        ]),
      ),
    );
    if (pilihan != null) setState(() => _ubah[s['siswa_id'] as int] = pilihan);
  }

  @override
  Widget build(BuildContext context) {
    final d = _data;
    if (d == null) {
      return AppPage(title: 'Presensi Ibadah Sholat', child: _error == null ? const Padding(padding: EdgeInsets.only(top: 80), child: Center(child: CircularProgressIndicator())) : ErrorPanel(message: _error!, onRetry: _load));
    }
    final siswa = (d['data'] as List).cast<Map>();
    final total = d['total'] as int;
    final hadir = siswa.where((s) => (_ubah[s['siswa_id']] ?? s['status']) == 'Hadir').length;

    return AppPage(
      title: 'Presensi Ibadah Sholat',
      subtitle: '${d['kelas']['nama']} · Sholat ${widget.jenis}',
      bottom: PrimaryButton(label: _ubah.isEmpty ? 'Selesai' : 'Simpan Presensi Sholat', loading: _saving, onPressed: _ubah.isEmpty ? () => context.pop() : _simpan),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        SecondaryButton(label: 'Scan QR Siswa', onPressed: _scan),
        const SizedBox(height: 12),
        SholatProgressCard(tempat: d['tempat'], waktu: d['waktu'], hadir: hadir, total: total),
        const SizedBox(height: 12),
        if (siswa.isEmpty) const EmptyPanel(icon: Icons.group_off_outlined, message: 'Tidak ada siswa aktif di kelas ini.'),
        for (var i = 0; i < siswa.length; i++)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: AppCard(
              onTap: () => _pilihStatus(siswa[i]),
              child: Row(children: [
                Expanded(child: Text('${i + 1}. ${siswa[i]['nama']}', style: AppText.label)),
                _chip(_ubah[siswa[i]['siswa_id']] ?? siswa[i]['status'], _ubah.containsKey(siswa[i]['siswa_id'])),
              ]),
            ),
          ),
        Text('Ketuk nama siswa untuk mengubah status secara manual.', style: AppText.caption, textAlign: TextAlign.center),
      ]),
    );
  }

  Widget _chip(String? status, bool diubah) {
    final label = status ?? 'Belum scan';
    return Row(mainAxisSize: MainAxisSize.min, children: [
      if (diubah) const Padding(padding: EdgeInsets.only(right: 6), child: Icon(Icons.edit, size: 14, color: AppColors.inkMuted)),
      StatusChip(label, tone: StatusChip.toneFor(status)),
    ]);
  }
}

class SholatProgressCard extends StatelessWidget {
  const SholatProgressCard({super.key, required this.tempat, required this.waktu, required this.hadir, required this.total});
  final String tempat;
  final String waktu;
  final int hadir;
  final int total;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(tempat, style: AppText.heading),
              Text('Dilaksanakan berjamaah pukul $waktu', style: AppText.bodySmall),
            ]),
          ),
          Text('$hadir/$total Anak', style: AppText.label.copyWith(color: AppColors.ok700)),
        ]),
        const SizedBox(height: 12),
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: LinearProgressIndicator(value: total == 0 ? 0 : hadir / total, minHeight: 6, color: AppColors.ok600, backgroundColor: AppColors.line),
        ),
      ]),
    );
  }
}
