import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/format.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';

class RekapAbsenScreen extends StatefulWidget {
  const RekapAbsenScreen({super.key});

  @override
  State<RekapAbsenScreen> createState() => _RekapAbsenScreenState();
}

class _RekapAbsenScreenState extends State<RekapAbsenScreen> {
  DateTime _bulan = DateTime(DateTime.now().year, DateTime.now().month);

  void _geser(int n) => setState(() => _bulan = DateTime(_bulan.year, _bulan.month + n));

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final bulanIni = DateTime(DateTime.now().year, DateTime.now().month);
    return AppPage(
      title: 'Rekap Absen Saya',
      subtitle: Fmt.bulan(_bulan),
      scroll: false,
      padding: EdgeInsets.zero,
      child: Column(children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8),
          child: Row(children: [
            IconButton(tooltip: 'Bulan sebelumnya', onPressed: () => _geser(-1), icon: const Icon(Icons.chevron_left)),
            Expanded(child: Text(Fmt.bulan(_bulan), style: AppText.label, textAlign: TextAlign.center)),
            IconButton(tooltip: 'Bulan berikutnya', onPressed: _bulan.isBefore(bulanIni) ? () => _geser(1) : null, icon: const Icon(Icons.chevron_right)),
          ]),
        ),
        Expanded(
          child: AsyncBody<Map<String, dynamic>>(
            key: ValueKey(_bulan),
            load: () => api.get('/guru/absen/rekap', query: {'bulan': DateFormat('yyyy-MM').format(_bulan)}),
            builder: (context, d, _) {
              final rows = (d['data'] as List).cast<Map>();
              final persen = d['persen'];
              return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
                AppCard(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text('Kehadiran ${DateFormat('MMMM', 'id_ID').format(_bulan)}', style: AppText.heading),
                          Text('${d['hadir']} Hadir · ${d['tidak_hadir']} Tidak Hadir${d['terlambat'] > 0 ? ' · ${d['terlambat']} terlambat' : ''}', style: AppText.bodySmall),
                        ]),
                      ),
                      if (persen != null) Text('Persentase $persen%', style: AppText.label.copyWith(color: AppColors.ok700)),
                    ]),
                    const SizedBox(height: 12),
                    ClipRRect(borderRadius: BorderRadius.circular(4), child: LinearProgressIndicator(value: (persen ?? 0) / 100, minHeight: 6, color: AppColors.ok600, backgroundColor: AppColors.line)),
                  ]),
                ),
                const SizedBox(height: 12),
                if (rows.isEmpty) const EmptyPanel(icon: Icons.event_note_outlined, message: 'Belum ada catatan absen di bulan ini.'),
                for (final r in rows)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: AppCard(
                      child: Row(children: [
                        Expanded(
                          child: Text(
                            '${Fmt.tanggalPendek(Fmt.parse(r['tanggal']))} · ${r['jam_masuk'] == null ? 'Tidak scan masuk' : '${r['jam_masuk']} – ${r['jam_pulang'] ?? '…'}'}',
                            style: AppText.label,
                          ),
                        ),
                        if (r['terlambat'] == true) const Padding(padding: EdgeInsets.only(right: 6), child: StatusChip('Terlambat', tone: ChipTone.gold)),
                        StatusChip(r['status'], tone: StatusChip.toneFor(r['status'])),
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
