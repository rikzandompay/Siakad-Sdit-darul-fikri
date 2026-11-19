import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';
import 'rekap_bersama.dart';

class RekapAbsensiScreen extends StatefulWidget {
  const RekapAbsensiScreen({super.key});
  @override
  State<RekapAbsensiScreen> createState() => _RekapAbsensiScreenState();
}

class _RekapAbsensiScreenState extends State<RekapAbsensiScreen>
    with OpsiAdmin {
  int? _kelas;
  int? _mapel;
  String _rentang = 'bulan_ini';

  @override
  void opsiSiap(Map<String, dynamic> d) {
    final list = daftarKelas('kelas_wali');
    if (list.isNotEmpty && mounted) {
      setState(() => _kelas = list[0]['id'] as int);
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    final q = {'kelas_id': _kelas, 'pelajaran_id': _mapel, 'rentang': _rentang};
    return AppPage(
      title: 'Rekap Absensi',
      subtitle: 'Rekapitulasi kehadiran siswa per kelas',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: _kelas == null
          ? null
          : tombolEkspor(context, 'absensi', q, awalanApi: awalanEkspor),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: AppCard(
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: PilihanDropdown<int>(
                          label: 'Kelas',
                          nilai: _kelas,
                          item: kelasItemDari('kelas_wali'),
                          onChanged: (v) => setState(() => _kelas = v),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: PilihanDropdown<int?>(
                          label: 'Mata pelajaran',
                          nilai: _mapel,
                          item: {
                            null: 'Semua Mapel',
                            for (final m in (opsi?['mapel'] as List? ?? []))
                              m['id'] as int: m['nama'] as String,
                          },
                          onChanged: (v) => setState(() => _mapel = v),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Align(
                    alignment: Alignment.centerLeft,
                    child: PilihanChips<String>(
                      pilihan: rentangWaktu,
                      nilai: _rentang,
                      onChanged: (v) => setState(() => _rentang = v),
                    ),
                  ),
                ],
              ),
            ),
          ),
          Expanded(
            child: _kelas == null
                ? tungguAtauKosong(
                    'Rekap presensi hanya untuk wali kelas. Anda belum menjadi wali kelas.',
                  )
                : AsyncBody<Map<String, dynamic>>(
                    key: ValueKey(q.toString()),
                    load: () => api.get(
                      '$awalan/rekap/absensi',
                      query: {
                        for (final e in q.entries)
                          if (e.value != null) e.key: e.value,
                      },
                    ),
                    builder: (context, d, _) => TabelRekap(
                      judul:
                          'Data Rekap · ${d['kelas']} · ${d['jumlah_siswa']} siswa',
                      periode: d['periode'],
                      kolom: const [
                        ('H', 'HADIR', AppColors.ok700),
                        ('S', 'SAKIT', AppColors.sakit),
                        ('I', 'IZIN', Color(0xFF8A6417)),
                        ('A', 'ALPA', AppColors.alpa),
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

