import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';
import 'rekap_bersama.dart';

class RekapSholatScreen extends StatefulWidget {
  const RekapSholatScreen({super.key});
  @override
  State<RekapSholatScreen> createState() => _RekapSholatScreenState();
}

class _RekapSholatScreenState extends State<RekapSholatScreen> with OpsiAdmin {
  int? _kelas;
  String _jenis = 'Dzuhur';
  String _rentang = 'bulan_ini';

  @override
  void opsiSiap(Map<String, dynamic> d) {
    final list = daftarKelas('kelas_wali');
    if (list.isNotEmpty && mounted) {
      setState(() {
        _kelas = list[0]['id'] as int;
        if (list[0]['ikut_dzuhur'] == false) {
          _jenis = 'Dhuha';
        }
      });
    }
  }

  /// Kelas 1 dan 2 tidak mengikuti sholat Dzuhur, jadi pilihan Dzuhur
  /// disembunyikan untuk kelas tersebut.
  bool get _ikutDzuhur =>
      daftarKelas('kelas_wali').firstWhere(
        (k) => k['id'] == _kelas,
        orElse: () => const {},
      )['ikut_dzuhur'] !=
      false;

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    // Jangan pernah meminta rekap Dzuhur untuk kelas yang tidak ikut sholat Dzuhur.
    final jenis = _ikutDzuhur ? _jenis : 'Dhuha';
    final q = {'kelas_id': _kelas, 'jenis': jenis, 'rentang': _rentang};
    return AppPage(
      title: 'Rekap Sholat',
      subtitle: 'Kehadiran sholat berjamaah per kelas',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: _kelas == null
          ? null
          : tombolEkspor(context, 'sholat', q, awalanApi: awalanEkspor),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: AppCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  PilihanDropdown<int>(
                    label: 'Kelas',
                    nilai: _kelas,
                    item: kelasItemDari('kelas_wali'),
                    onChanged: (v) => setState(() => _kelas = v),
                  ),
                  const SizedBox(height: 10),
                  PilihanChips<String>(
                    pilihan: {
                      'Dhuha': 'Sholat Dhuha',
                      if (_ikutDzuhur) 'Dzuhur': 'Sholat Dzuhur',
                    },
                    nilai: jenis,
                    onChanged: (v) => setState(() => _jenis = v),
                  ),
                  const SizedBox(height: 8),
                  PilihanChips<String>(
                    pilihan: rentangWaktu,
                    nilai: _rentang,
                    onChanged: (v) => setState(() => _rentang = v),
                  ),
                ],
              ),
            ),
          ),
          Expanded(
            child: _kelas == null
                ? tungguAtauKosong(
                    'Belum ada kelas yang diampu sebagai wali kelas.',
                  )
                : AsyncBody<Map<String, dynamic>>(
                    key: ValueKey(q.toString()),
                    load: () => api.get('$awalan/rekap/sholat', query: q),
                    builder: (context, d, _) => TabelRekap(
                      judul:
                          'Sholat ${d['jenis']} · ${d['kelas']} · ${d['jumlah_siswa']} siswa',
                      periode: d['periode'],
                      catatan: d['catatan'] as String?,
                      kolom: const [
                        ('hadir', 'HADIR', AppColors.ok700),
                        ('tidak_hadir', 'TIDAK HADIR', AppColors.alpa),
                      ],
                      data: (d['data'] as List).cast<Map>(),
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

