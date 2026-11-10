import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../shared/notifikasi_screen.dart';
import '../../widgets/kartu_identitas.dart';
import '../admin/admin_sekolah_view.dart';

class GuruDashboardScreen extends StatefulWidget {
  const GuruDashboardScreen({super.key});

  @override
  State<GuruDashboardScreen> createState() => _GuruDashboardScreenState();
}

class _GuruDashboardScreenState extends State<GuruDashboardScreen> {
  /// Kepala sekolah/admin: 0 = Sekolah, 1 = Mengajar Saya.
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    final session = context.watch<Session>();
    final akun = session.akun;
    final api = context.read<ApiClient>();
    final admin = session.isAdmin;

    return DecoratedBox(
      decoration: const BoxDecoration(gradient: AppColors.pageGradient),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        body: SafeArea(
          child: AsyncBody<Map<String, dynamic>>(
            load: () => api.get('/guru/dashboard'),
            builder: (context, d, reload) => ListView(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              children: [
                _Header(akun: akun, admin: admin),
                const SizedBox(height: 20),
                Row(children: [
                  Expanded(child: Text('Kartu Guru', style: AppText.heading)),
                  Text('TA ${(akun['tahun_ajaran'] ?? '-').toString().split(' ').first}', style: AppText.caption),
                ]),
                const SizedBox(height: 8),
                KartuIdentitas(
                  jenis: 'KARTU GURU',
                  nama: akun['nama'] ?? '-',
                  inisial: akun['inisial'] ?? '',
                  fotoUrl: akun['foto_url'],
                  fields: [('NIP', akun['nip'] ?? '-'), ('Jabatan', akun['jabatan'] ?? 'Guru')],
                  tahunAjaran: akun['tahun_ajaran'],
                  footerKanan: 'Kartu Identitas Guru',
                ),
                const SizedBox(height: 16),
                if (admin) ...[
                  _TabAdmin(nilai: _tab, onChanged: (v) => setState(() => _tab = v)),
                  const SizedBox(height: 16),
                ],
                if (admin && _tab == 0)
                  AdminSekolahView(onReturn: reload)
                else ...[
                  _KehadiranCard(data: Map<String, dynamic>.from(d['kehadiran_siswa'])),
                  const SizedBox(height: 16),
                  _MenuGrid(izinMenunggu: d['izin_menunggu'] as int? ?? 0, bisaPresensi: d['bisa_isi_presensi'] == true, onReturn: reload),
                  const SizedBox(height: 16),
                  _PresensiSayaCard(data: Map<String, dynamic>.from(d['presensi_saya']), onTap: () async {
                    await context.push('/guru/absen');
                    reload();
                  }),
                  const SizedBox(height: 20),
                  Row(children: [
                    Expanded(child: Text('Jadwal Mengajar Hari Ini', style: AppText.heading)),
                    Text(Fmt.tanggalPendek(Fmt.parse(d['tanggal'])), style: AppText.caption),
                  ]),
                  const SizedBox(height: 10),
                  if ((d['jadwal_hari_ini'] as List).isEmpty)
                    const AppCard(child: EmptyPanel(icon: Icons.event_available_outlined, message: 'Tidak ada jadwal mengajar hari ini.'))
                  else
                    for (final j in d['jadwal_hari_ini'] as List) _JadwalCard(j: Map<String, dynamic>.from(j), onReturn: reload),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _TabAdmin extends StatelessWidget {
  const _TabAdmin({required this.nilai, required this.onChanged});
  final int nilai;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    Widget tab(int i, String label) => Expanded(
          child: Semantics(
            selected: nilai == i,
            button: true,
            child: GestureDetector(
              onTap: () => onChanged(i),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                height: 44,
                alignment: Alignment.center,
                decoration: BoxDecoration(color: nilai == i ? AppColors.surface : Colors.transparent, borderRadius: BorderRadius.circular(10)),
                child: Text(label, style: AppText.label.copyWith(color: nilai == i ? AppColors.brand700 : AppColors.inkMuted)),
              ),
            ),
          ),
        );
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: AppColors.field, borderRadius: BorderRadius.circular(12)),
      child: Row(children: [tab(0, 'Sekolah'), tab(1, 'Mengajar Saya')]),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.akun, required this.admin});
  final Map<String, dynamic> akun;
  final bool admin;

  @override
  Widget build(BuildContext context) {
    final peran = (akun['jabatan'] as String?) ?? (admin ? 'Kepala Sekolah' : 'Guru');
    return Row(children: [
      Semantics(
        button: true,
        label: 'Buka profil',
        child: GestureDetector(onTap: () => context.push('/guru/profil'), child: FotoAvatar(fotoUrl: akun['foto_url'], inisial: akun['inisial'] ?? '')),
      ),
      const SizedBox(width: 12),
      Expanded(
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(admin ? 'Selamat Datang,' : 'Selamat Mengajar,', style: AppText.bodySmall),
          Text(akun['nama'] ?? '-', style: AppText.heading.copyWith(fontSize: 17), maxLines: 1, overflow: TextOverflow.ellipsis),
          Text(peran, style: AppText.caption, maxLines: 1, overflow: TextOverflow.ellipsis),
        ]),
      ),
      const TombolNotifikasi(rute: '/guru/notifikasi'),
    ]);
  }
}

class _KehadiranCard extends StatelessWidget {
  const _KehadiranCard({required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final total = (data['total'] as int?) ?? 0;
    final persen = data['persen_hadir'];
    final items = [
      ('Hadir', data['hadir'] as int, AppColors.ok600),
      ('Izin', data['izin'] as int, AppColors.gold),
      ('Sakit', data['sakit'] as int, AppColors.sakit),
      ('Alpa', data['alpa'] as int, AppColors.alpa),
    ];
    return AppCard(
      padding: const EdgeInsets.all(18),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('Kehadiran Hari Ini', style: AppText.heading),
        const SizedBox(height: 8),
        if (total == 0)
          Text('Belum ada presensi kelas yang diisi hari ini.', style: AppText.bodySmall)
        else ...[
          Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
            Text('${persen.toString().replaceAll('.', ',')}%', style: AppText.number),
            const SizedBox(width: 8),
            Padding(padding: const EdgeInsets.only(bottom: 6), child: Text('Hadir', style: AppText.bodySmall)),
          ]),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: SizedBox(
              height: 8,
              child: Row(children: [
                for (final it in items)
                  if (it.$2 > 0) Expanded(flex: it.$2, child: Container(color: it.$3, margin: const EdgeInsets.only(right: 2))),
              ]),
            ),
          ),
        ],
        const SizedBox(height: 14),
        Row(children: [
          for (final it in items)
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('${it.$2}', style: AppText.heading.copyWith(fontSize: 18)),
                Row(children: [
                  Container(width: 8, height: 8, decoration: BoxDecoration(color: it.$3, borderRadius: BorderRadius.circular(2))),
                  const SizedBox(width: 4),
                  Text(it.$1, style: AppText.caption),
                ]),
              ]),
            ),
        ]),
      ]),
    );
  }
}

class _MenuGrid extends StatelessWidget {
  const _MenuGrid({required this.izinMenunggu, required this.bisaPresensi, required this.onReturn});
  final int izinMenunggu;
  final bool bisaPresensi;
  final Future<void> Function() onReturn;

  @override
  Widget build(BuildContext context) {
    Future<void> buka(String path) async {
      await context.push(path);
      onReturn();
    }

    final menu = [
      if (bisaPresensi) ('Presensi', 'presensi', () => buka('/guru/presensi'), 0),
      if (bisaPresensi) ('Sholat', 'sholat', () => buka('/guru/sholat'), 0),
      ('Bagi Tugas', 'tugas', () => buka('/guru/tugas'), 0),
      ('Nilai Tugas', 'nilai', () => buka('/guru/tugas'), 0),
      ('Surat Izin', 'izin', () => buka('/guru/izin'), izinMenunggu),
      ('Konseling', 'konseling', () => buka('/guru/konseling'), 0),
      ('Absen Saya', 'absen', () => buka('/guru/absen'), 0),
      ('Rekap Absen', 'rekap_absen', () => buka('/guru/absen/rekap'), 0),
    ];

    return GridView.count(
      crossAxisCount: 4,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 10,
      crossAxisSpacing: 10,
      childAspectRatio: 0.78,
      children: [
        for (final m in menu)
          AppCard(
            padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 2),
            onTap: m.$3,
            child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
              Stack(clipBehavior: Clip.none, children: [
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(color: AppColors.brand50, borderRadius: BorderRadius.circular(10)),
                  alignment: Alignment.center,
                  child: Image.asset('assets/icons/${m.$2}.png', width: 24, height: 24),
                ),
                if (m.$4 > 0)
                  Positioned(
                    right: -6,
                    top: -6,
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: const BoxDecoration(color: AppColors.gold, shape: BoxShape.circle),
                      child: Text('${m.$4}', style: AppText.captionBold.copyWith(color: Colors.white, fontSize: 10)),
                    ),
                  ),
              ]),
              const SizedBox(height: 6),
              Text(m.$1, style: AppText.captionBold.copyWith(fontSize: 11, height: 1.15), textAlign: TextAlign.center, maxLines: 2, overflow: TextOverflow.ellipsis),
            ]),
          ),
      ],
    );
  }
}

class _PresensiSayaCard extends StatelessWidget {
  const _PresensiSayaCard({required this.data, required this.onTap});
  final Map<String, dynamic> data;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final masuk = data['jam_masuk'] as String?;
    final pulang = data['jam_pulang'] as String?;
    final status = data['status'] as String?;
    final keterangan = status == 'Tidak Hadir'
        ? 'Tercatat Tidak Hadir'
        : masuk == null
            ? 'Masuk ${data['jam_masuk_acuan'] ?? '07:00'} · Belum absen'
            : 'Masuk $masuk${data['terlambat'] == true ? ' (terlambat)' : ''} · ${pulang == null ? 'Belum pulang' : 'Pulang $pulang'}';
    final bisaAbsen = status != 'Tidak Hadir' && pulang == null;

    return AppCard(
      padding: const EdgeInsets.all(16),
      child: Row(children: [
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Presensi Saya', style: AppText.heading),
            const SizedBox(height: 2),
            Text(keterangan, style: AppText.bodySmall),
          ]),
        ),
        if (bisaAbsen)
          FilledButton(
            onPressed: onTap,
            style: FilledButton.styleFrom(backgroundColor: AppColors.brand700, shape: const StadiumBorder(), minimumSize: const Size(0, 40)),
            child: Text(masuk == null ? 'Absen' : 'Pulang'),
          )
        else
          StatusChip(status ?? '-', tone: StatusChip.toneFor(status)),
      ]),
    );
  }
}

class _JadwalCard extends StatelessWidget {
  const _JadwalCard({required this.j, required this.onReturn});
  final Map<String, dynamic> j;
  final Future<void> Function() onReturn;

  @override
  Widget build(BuildContext context) {
    final aktif = j['sedang_berlangsung'] == true;
    final bisa = j['bisa_absen'] != false;
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        borderColor: aktif ? AppColors.brand700 : null,
        onTap: bisa
            ? () async {
                // Muat ulang beranda setelah kembali (angka kehadiran berubah).
                await context.push('/guru/presensi/jadwal/${j['jadwal_id']}');
                onReturn();
              }
            : null,
        child: Row(children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(j['kelas'] ?? '-', style: AppText.title),
              const SizedBox(height: 2),
              Text(j['mata_pelajaran'] ?? '-', style: AppText.bodySmall),
              const SizedBox(height: 6),
              Row(children: [
                Icon(Icons.schedule, size: 16, color: aktif ? AppColors.brand700 : AppColors.inkMuted),
                const SizedBox(width: 4),
                Text('${j['jam_mulai']} - ${j['jam_selesai']}', style: AppText.captionBold.copyWith(color: aktif ? AppColors.brand700 : AppColors.inkMuted)),
              ]),
            ]),
          ),
          if (aktif) const StatusChip('Berlangsung', tone: ChipTone.brand),
        ]),
      ),
    );
  }
}
