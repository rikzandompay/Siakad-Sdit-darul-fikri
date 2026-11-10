import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/format.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import '../../../widgets/pilihan.dart';

/// Kelola Tugas / PR: buat pengingat tugas baru + daftar tugas aktif.
class TugasScreen extends StatefulWidget {
  const TugasScreen({super.key});

  @override
  State<TugasScreen> createState() => _TugasScreenState();
}

class _TugasScreenState extends State<TugasScreen> {
  Map<String, dynamic>? _d;
  String? _error;
  String? _pilihan;
  DateTime? _tenggat;
  final _judul = TextEditingController();
  final _deskripsi = TextEditingController();
  bool _kirim = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _judul.dispose();
    _deskripsi.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final d = await context.read<ApiClient>().get('/guru/tugas');
      if (!mounted) return;
      setState(() {
        _d = d;
        _error = null;
        final p = (d['pilihan'] as List);
        if (_pilihan == null && p.isNotEmpty) _pilihan = '${p.first['kelas_id']}-${p.first['pelajaran_id']}';
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    }
  }

  Future<void> _buat() async {
    if (_pilihan == null || _judul.text.trim().isEmpty) {
      showMessage(context, 'Pilih kelas & mapel lalu isi judul tugas.', error: true);
      return;
    }
    final ids = _pilihan!.split('-');
    setState(() => _kirim = true);
    try {
      await context.read<ApiClient>().post('/guru/tugas', {
        'kelas_id': int.parse(ids[0]),
        'pelajaran_id': int.parse(ids[1]),
        'judul': _judul.text.trim(),
        'deskripsi': _deskripsi.text.trim().isEmpty ? null : _deskripsi.text.trim(),
        'tenggat': _tenggat == null ? null : '${_tenggat!.year}-${_tenggat!.month.toString().padLeft(2, '0')}-${_tenggat!.day.toString().padLeft(2, '0')}',
      });
      if (!mounted) return;
      _judul.clear();
      _deskripsi.clear();
      setState(() => _tenggat = null);
      showMessage(context, 'Tugas terkirim. Wali murid melihatnya di aplikasi.');
      _load();
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _kirim = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = _d;
    return AppPage(
      title: 'Kelola Tugas / PR Pelajaran',
      subtitle: 'Tersinkron langsung ke HP orang tua',
      child: d == null
          ? (_error == null ? const Padding(padding: EdgeInsets.only(top: 80), child: Center(child: CircularProgressIndicator())) : ErrorPanel(message: _error!, onRetry: _load))
          : Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              AppCard(
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  Row(children: [const Icon(Icons.add, color: AppColors.brand700), const SizedBox(width: 6), Text('Buat Pengingat Tugas Baru', style: AppText.heading)]),
                  const SizedBox(height: 12),
                  if ((d['pilihan'] as List).isEmpty)
                    Text('Anda belum punya jadwal mengajar, jadi belum bisa membuat tugas.', style: AppText.bodySmall)
                  else ...[
                    PilihanDropdown<String>(
                      label: 'Mata pelajaran & kelas',
                      nilai: _pilihan,
                      item: {for (final p in (d['pilihan'] as List)) '${p['kelas_id']}-${p['pelajaran_id']}': p['label'] as String},
                      onChanged: (v) => setState(() => _pilihan = v),
                    ),
                    const SizedBox(height: 12),
                    Text('JUDUL TUGAS / PR', style: AppText.overline.copyWith(fontSize: 10)),
                    const SizedBox(height: 6),
                    TextField(controller: _judul, maxLength: 150, decoration: const InputDecoration(hintText: 'Tulis judul tugas', counterText: '')),
                    const SizedBox(height: 10),
                    TextField(controller: _deskripsi, minLines: 2, maxLines: 4, decoration: const InputDecoration(hintText: 'Keterangan (opsional)')),
                    const SizedBox(height: 12),
                    Text('BATAS WAKTU PENGUMPULAN', style: AppText.overline.copyWith(fontSize: 10)),
                    const SizedBox(height: 6),
                    OutlinedButton.icon(
                      onPressed: () async {
                        final t = await showDatePicker(context: context, firstDate: DateTime.now(), lastDate: DateTime.now().add(const Duration(days: 120)), initialDate: _tenggat ?? DateTime.now().add(const Duration(days: 1)));
                        if (t != null) setState(() => _tenggat = t);
                      },
                      icon: const Icon(Icons.event_outlined),
                      label: Text(_tenggat == null ? 'Pilih tanggal' : Fmt.tanggalPanjang(_tenggat!)),
                      style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(48), alignment: Alignment.centerLeft),
                    ),
                    const SizedBox(height: 14),
                    PrimaryButton(label: 'Kirim Tugas ke Orang Tua', icon: Icons.send_rounded, loading: _kirim, onPressed: _buat),
                  ],
                ]),
              ),
              const SizedBox(height: 20),
              Text('Tugas Aktif', style: AppText.heading),
              const SizedBox(height: 10),
              if ((d['data'] as List).isEmpty) const EmptyPanel(icon: Icons.assignment_outlined, message: 'Belum ada tugas.'),
              for (final t in (d['data'] as List).cast<Map>()) _TugasCard(t: Map<String, dynamic>.from(t), onReturn: _load),
            ]),
    );
  }
}

class _TugasCard extends StatelessWidget {
  const _TugasCard({required this.t, required this.onReturn});
  final Map<String, dynamic> t;
  final VoidCallback onReturn;

  @override
  Widget build(BuildContext context) {
    final tenggat = t['tenggat'] == null ? null : Fmt.parse(t['tenggat']);
    final sisa = tenggat?.difference(DateUtils.dateOnly(DateTime.now())).inDays;
    final chip = sisa == null
        ? null
        : sisa < 0
            ? const StatusChip('Lewat tenggat', tone: ChipTone.alpa)
            : sisa == 0
                ? const StatusChip('Kumpul hari ini', tone: ChipTone.gold)
                : sisa == 1
                    ? const StatusChip('Kumpul besok', tone: ChipTone.gold)
                    : StatusChip('Kumpul ${Fmt.tanggalPendek(tenggat!)}', tone: ChipTone.brand);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Row(children: [Expanded(child: Text('${t['mata_pelajaran']} · ${t['kelas']}', style: AppText.caption)), ?chip]),
          const SizedBox(height: 6),
          Text(t['judul'], style: AppText.heading),
          const SizedBox(height: 4),
          Text('${t['dinilai']}/${t['jumlah_siswa']} siswa dinilai', style: AppText.caption),
          const SizedBox(height: 12),
          WarningButton(label: 'Beri Nilai', icon: Icons.rate_review_outlined, onPressed: () async {
            await context.push('/guru/tugas/${t['id']}');
            onReturn();
          }),
        ]),
      ),
    );
  }
}
