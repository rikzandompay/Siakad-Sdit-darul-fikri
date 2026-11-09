import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

/// Tab "Sekolah" di beranda kepala sekolah: ringkasan hari ini + menu pengelolaan.
class AdminSekolahView extends StatelessWidget {
  const AdminSekolahView({super.key, required this.onReturn});
  final Future<void> Function() onReturn;

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return FutureBuilder<Map<String, dynamic>>(
      future: api.get('/admin/dashboard'),
      builder: (context, s) {
        if (s.hasError) return ErrorPanel(message: s.error.toString(), onRetry: onReturn);
        if (!s.hasData) return const Padding(padding: EdgeInsets.all(32), child: Center(child: CircularProgressIndicator()));
        final d = s.data!;
        final siswa = Map<String, dynamic>.from(d['siswa']);
        final guru = Map<String, dynamic>.from(d['guru']);
        Future<void> buka(String path) async {
          await context.push(path);
          onReturn();
        }

        Widget grup(String judul, List<(String, String, String, int)> menu, {int kolom = 4}) => Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              Padding(padding: const EdgeInsets.only(top: 16, bottom: 8), child: Text(judul.toUpperCase(), style: AppText.overline)),
              GridView.count(
                crossAxisCount: kolom,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
                childAspectRatio: kolom == 4 ? 0.72 : 1.05,
                children: [
                  for (final m in menu)
                    AppCard(
                      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 2),
                      onTap: () => buka(m.$3),
                      child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                        Stack(clipBehavior: Clip.none, children: [
                          Container(width: 38, height: 38, decoration: BoxDecoration(color: AppColors.brand50, borderRadius: BorderRadius.circular(10)), alignment: Alignment.center, child: Image.asset('assets/icons/${m.$2}.png', width: 24)),
                          if (m.$4 > 0)
                            Positioned(right: -6, top: -6, child: Container(padding: const EdgeInsets.all(4), decoration: const BoxDecoration(color: AppColors.gold, shape: BoxShape.circle), child: Text('${m.$4}', style: AppText.captionBold.copyWith(color: Colors.white, fontSize: 10)))),
                        ]),
                        const SizedBox(height: 6),
                        Text(m.$1, style: AppText.captionBold.copyWith(fontSize: 11, height: 1.15), textAlign: TextAlign.center, maxLines: 2, overflow: TextOverflow.ellipsis),
                      ]),
                    ),
                ],
              ),
            ]);

        return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          AppCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Kehadiran Siswa Hari Ini', style: AppText.bodySmall),
              const SizedBox(height: 4),
              Text(siswa['persen_hadir'] == null ? '-' : '${siswa['persen_hadir'].toString().replaceAll('.', ',')}%', style: AppText.number),
              Text(siswa['tercatat'] == 0 ? 'Belum ada presensi kelas hari ini · ${siswa['kelas']} kelas' : '${siswa['hadir']} dari ${siswa['tercatat']} siswa tercatat hadir · ${siswa['kelas']} kelas', style: AppText.bodySmall),
            ]),
          ),
          const SizedBox(height: 12),
          AppCard(
            onTap: () => buka('/admin/rekap-guru'),
            child: Row(children: [
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('Presensi Guru Hari Ini', style: AppText.heading), Text('${guru['sudah_absen']} dari ${guru['total']} guru sudah absen', style: AppText.bodySmall)])),
              StatusChip('${guru['belum']} belum', tone: guru['belum'] > 0 ? ChipTone.gold : ChipTone.ok),
            ]),
          ),
          grup('Menu', [('Data Guru', 'absen', '/admin/guru', 0), ('Data Siswa', 'lock', '/admin/siswa', 0), ('Kelas & Mapel', 'calendar', '/admin/kelas', 0), ('Info Sekolah', 'mega', '/admin/pengumuman', 0)]),
          grup('Presensi', [
            ('Input Presensi', 'presensi', '/guru/presensi', 0),
            ('Rekap Absensi', 'tugas', '/admin/rekap-absensi', 0),
            ('Sholat', 'sholat', '/guru/sholat', 0),
            ('Rekap Sholat', 'sholat', '/admin/rekap-sholat', 0),
            ('Surat Izin', 'izin', '/guru/izin', d['izin_menunggu'] as int),
            ('Rekap Guru', 'rekap_absen', '/admin/rekap-guru', 0),
          ]),
          grup('Nilai & Pengaturan', [('Rekap Nilai', 'nilai', '/admin/rekap-nilai', 0), ('Tahun Ajaran', 'calendar', '/admin/tahun-ajaran', 0), ('Mata Pelajaran', 'lock', '/admin/mapel', 0)], kolom: 3),
          grup('Pengelolaan Sekolah', [
            ('Lokasi & Jam Absen', 'absen', '/admin/pengaturan-sekolah', 0),
            ('Konseling', 'konseling', '/guru/konseling', 0),
            ('Pengampu Konseling', 'konseling', '/admin/pengampu', 0),
            ('Akun Wali', 'izin', '/admin/wali', 0),
            ('Foto Guru', 'absen', '/admin/foto-pengajuan', (d['foto_menunggu'] as int?) ?? 0),
            ('Tugas Semua Kelas', 'tugas', '/guru/tugas', 0),
          ]),
        ]);
      },
    );
  }
}
