import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/format.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import '../../../widgets/pilihan.dart';

/// Penilaian tugas: isi nilai 0–100 per siswa.
class PenilaianScreen extends StatefulWidget {
  const PenilaianScreen({super.key, required this.tugasId});
  final int tugasId;

  @override
  State<PenilaianScreen> createState() => _PenilaianScreenState();
}

class _PenilaianScreenState extends State<PenilaianScreen> {
  Map<String, dynamic>? _d;
  String? _error;
  final Map<int, TextEditingController> _nilai = {};
  String _filter = 'semua';
  bool _simpan = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final c in _nilai.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final d = await context.read<ApiClient>().get(
        '/guru/tugas/${widget.tugasId}',
      );
      if (!mounted) return;
      setState(() {
        _d = d;
        for (final s in (d['data'] as List).cast<Map>()) {
          final n = s['nilai'];
          _nilai.putIfAbsent(
            s['siswa_id'] as int,
            () => TextEditingController(
              text: n == null
                  ? ''
                  : (n as num).toStringAsFixed(n % 1 == 0 ? 0 : 1),
            ),
          );
        }
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    }
  }

  Future<void> _simpanNilai() async {
    final isi = <Map<String, dynamic>>[];
    for (final e in _nilai.entries) {
      final t = e.value.text.trim().replaceAll(',', '.');
      if (t.isEmpty) continue;
      final n = double.tryParse(t);
      if (n == null || n < 0 || n > 100) {
        showMessage(context, 'Nilai harus angka 0–100.', error: true);
        return;
      }
      isi.add({'siswa_id': e.key, 'nilai': n});
    }
    if (isi.isEmpty) {
      showMessage(context, 'Isi minimal satu nilai.', error: true);
      return;
    }
    setState(() => _simpan = true);
    try {
      final res = await context.read<ApiClient>().post(
        '/guru/tugas/${widget.tugasId}/nilai',
        {'nilai': isi},
      );
      if (!mounted) return;
      showMessage(context, '${res['tersimpan']} nilai tersimpan.');
      context.pop();
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _simpan = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = _d;
    if (d == null) {
      return AppPage(
        title: 'Penilaian Tugas',
        child: _error == null
            ? const Padding(
                padding: EdgeInsets.only(top: 80),
                child: Center(child: CircularProgressIndicator()),
              )
            : ErrorPanel(message: _error!, onRetry: _load),
      );
    }
    final t = d['tugas'];
    final siswa = (d['data'] as List).cast<Map>().where((s) {
      final ada = _nilai[s['siswa_id']]!.text.trim().isNotEmpty;
      return _filter == 'semua' || (_filter == 'sudah' ? ada : !ada);
    }).toList();
    return AppPage(
      title: 'Penilaian Tugas',
      subtitle: 'Input nilai PR / tugas siswa',
      bottom: SuccessButton(
        label: 'Simpan Nilai',
        icon: Icons.save_rounded,
        loading: _simpan,
        onPressed: _simpanNilai,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const SectionLabel('Tugas yang dinilai'),
          AppCard(
            color: AppColors.field,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${t['mata_pelajaran']}: ${t['judul']}',
                  style: AppText.label,
                ),
                Text(
                  '${t['kelas']}${t['tenggat'] == null ? '' : ' · Kumpul ${Fmt.tanggalPendek(Fmt.parse(t['tenggat']))}'}',
                  style: AppText.caption,
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          PilihanChips<String>(
            pilihan: const {
              'semua': 'Semua',
              'belum': 'Belum dinilai',
              'sudah': 'Sudah dinilai',
            },
            nilai: _filter,
            onChanged: (v) => setState(() => _filter = v),
          ),
          const SizedBox(height: 12),
          if (siswa.isEmpty)
            const EmptyPanel(
              icon: Icons.check_circle_outline,
              message: 'Tidak ada siswa di filter ini.',
            ),
          for (final s in siswa)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: AppCard(
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(s['nama'], style: AppText.label),
                          const SizedBox(height: 4),
                          _nilai[s['siswa_id']]!.text.trim().isEmpty
                              ? const StatusChip(
                                  'Belum dinilai',
                                  tone: ChipTone.gold,
                                )
                              : const StatusChip(
                                  'Sudah dinilai',
                                  tone: ChipTone.ok,
                                ),
                        ],
                      ),
                    ),
                    SizedBox(
                      width: 84,
                      child: TextField(
                        controller: _nilai[s['siswa_id']],
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        textAlign: TextAlign.center,
                        style: AppText.heading,
                        decoration: const InputDecoration(hintText: '0–100'),
                        onChanged: (_) => setState(() {}),
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}
