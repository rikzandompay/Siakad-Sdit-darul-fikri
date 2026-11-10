import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/format.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import '../../../widgets/pilihan.dart';

/// Verifikasi surat izin/sakit dari wali murid.
class IzinGuruScreen extends StatefulWidget {
  const IzinGuruScreen({super.key});

  @override
  State<IzinGuruScreen> createState() => _IzinGuruScreenState();
}

class _IzinGuruScreenState extends State<IzinGuruScreen> {
  String _status = 'Menunggu';
  Key _key = UniqueKey();

  void _muatUlang() => setState(() => _key = UniqueKey());

  Future<void> _setujui(Map i) async {
    try {
      final res = await context.read<ApiClient>().post('/guru/izin/${i['id']}/setujui');
      if (!mounted) return;
      await showDialog(
        context: context,
        builder: (c) => AlertDialog(
          icon: const Icon(Icons.check_circle, color: AppColors.ok600, size: 48),
          title: const Text('Izin Disetujui'),
          content: Text('${res['siswa']} tercatat ${res['jenis']} di ${res['jumlah_presensi']} presensi pelajaran.'),
          actions: [TextButton(onPressed: () => Navigator.pop(c), child: const Text('OK'))],
        ),
      );
      _muatUlang();
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  Future<void> _tolak(Map i) async {
    final ctrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        title: const Text('Tolak izin'),
        content: TextField(controller: ctrl, autofocus: true, maxLength: 255, decoration: const InputDecoration(hintText: 'Alasan penolakan untuk wali murid')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Batal')),
          TextButton(onPressed: () => Navigator.pop(c, true), child: const Text('Tolak', style: TextStyle(color: AppColors.alpa))),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await context.read<ApiClient>().post('/guru/izin/${i['id']}/tolak', {'catatan': ctrl.text.trim()});
      if (mounted) showMessage(context, 'Izin ditolak.');
      _muatUlang();
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  void _lihatLampiran(Map i) {
    final api = context.read<ApiClient>();
    showDialog(
      context: context,
      builder: (c) => Dialog(
        clipBehavior: Clip.antiAlias,
        child: InteractiveViewer(child: Image.network(api.url('/guru/izin/${i['id']}/lampiran'), headers: api.authHeaders, errorBuilder: (_, _, _) => const Padding(padding: EdgeInsets.all(32), child: Text('Foto tidak dapat dimuat.')))),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Verifikasi Surat Izin / Sakit',
      subtitle: 'Permohonan izin dari orang tua',
      scroll: false,
      padding: EdgeInsets.zero,
      child: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: PilihanChips<String>(pilihan: const {'Menunggu': 'Menunggu', 'Disetujui': 'Disetujui', 'Ditolak': 'Ditolak'}, nilai: _status, onChanged: (v) => setState(() {
                _status = v;
                _key = UniqueKey();
              })),
        ),
        Expanded(
          child: AsyncBody<Map<String, dynamic>>(
            key: _key,
            load: () => api.get('/guru/izin', query: {'status': _status}),
            builder: (context, d, _) {
              final list = (d['data'] as List).cast<Map>();
              return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
                if (list.isEmpty) const EmptyPanel(icon: Icons.mark_email_read_outlined, message: 'Tidak ada permohonan.\nPermohonan baru dari orang tua akan muncul di sini.'),
                for (final i in list)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: AppCard(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                        Row(children: [
                          StatusChip(i['jenis'], tone: i['jenis'] == 'Sakit' ? ChipTone.sakit : ChipTone.gold),
                          const Spacer(),
                          Text(i['tanggal_mulai'] == i['tanggal_selesai'] ? Fmt.tanggalPendek(Fmt.parse(i['tanggal_mulai'])) : '${Fmt.tanggalPendek(Fmt.parse(i['tanggal_mulai']))} – ${Fmt.tanggalPendek(Fmt.parse(i['tanggal_selesai']))}', style: AppText.caption),
                        ]),
                        const SizedBox(height: 8),
                        Text('${i['siswa']} (${i['kelas'] ?? '-'})', style: AppText.heading),
                        Text('Dari ${i['wali'] ?? 'wali murid'}', style: AppText.caption),
                        const SizedBox(height: 6),
                        Text('"${i['alasan']}"', style: AppText.bodySmall),
                        if (i['ada_lampiran'] == true) ...[
                          const SizedBox(height: 10),
                          AppCard(
                            color: AppColors.brand50,
                            borderColor: AppColors.brand100,
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            onTap: () => _lihatLampiran(i),
                            child: Row(children: [const Icon(Icons.description_outlined, color: AppColors.brand700), const SizedBox(width: 8), Expanded(child: Text('Lihat Foto Surat Dokter', style: AppText.label.copyWith(color: AppColors.brand700))), const Icon(Icons.chevron_right, color: AppColors.brand700)]),
                          ),
                        ],
                        if (i['status'] == 'Menunggu') ...[
                          const SizedBox(height: 12),
                          Row(children: [
                            Expanded(child: SuccessButton(label: 'Setujui (Otomatis Isi ${i['jenis']})', icon: Icons.check_circle_outline, onPressed: () => _setujui(i))),
                            const SizedBox(width: 8),
                            SizedBox(width: 90, child: AlertButton(label: 'Tolak', icon: Icons.cancel_outlined, onPressed: () => _tolak(i))),
                          ]),
                        ] else if (i['catatan_peninjau'] != null) ...[
                          const SizedBox(height: 8),
                          Text('Catatan: ${i['catatan_peninjau']}', style: AppText.caption),
                        ],
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
