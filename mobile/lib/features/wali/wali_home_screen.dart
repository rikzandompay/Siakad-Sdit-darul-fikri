import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/kartu_pelajar_desain.dart';
import '../shared/notifikasi_screen.dart';

/// Beranda wali murid: kartu pelajar (QR sholat), aktivitas hari ini, menu, pengingat PR, info sekolah.
class WaliHomeScreen extends StatefulWidget {
  const WaliHomeScreen({super.key});

  @override
  State<WaliHomeScreen> createState() => _WaliHomeScreenState();
}

class _WaliHomeScreenState extends State<WaliHomeScreen> {
  Key _key = UniqueKey();

  Future<void> _buka(String path) async {
    await context.push(path);
    if (mounted) setState(() => _key = UniqueKey());
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<Session>();
    final anak = session.anak;
    final api = context.read<ApiClient>();
    return DecoratedBox(
      decoration: const BoxDecoration(gradient: AppColors.pageGradient),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        body: SafeArea(
          child: anak == null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 28),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(
                          Icons.child_care,
                          size: 72,
                          color: AppColors.inkFaint,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          session.akun['nama'] != null
                              ? 'Halo, ${session.akun['nama']}'
                              : 'Akun Belum Terhubung',
                          style: AppText.heading,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Akun ini belum terhubung ke data siswa. Hubungi pihak sekolah atau keluar untuk masuk dengan akun lain.',
                          textAlign: TextAlign.center,
                          style: AppText.body,
                        ),
                        const SizedBox(height: 24),
                        Wrap(
                          alignment: WrapAlignment.center,
                          spacing: 12,
                          runSpacing: 8,
                          children: [
                            OutlinedButton.icon(
                              onPressed: () async {
                                try {
                                  await session.refreshMe();
                                  if (mounted)
                                    setState(() => _key = UniqueKey());
                                } catch (_) {}
                              },
                              icon: const Icon(Icons.refresh),
                              label: const Text('Muat Ulang'),
                            ),
                            FilledButton.icon(
                              style: FilledButton.styleFrom(
                                backgroundColor: AppColors.alpa,
                              ),
                              onPressed: () => session.logout(),
                              icon: const Icon(Icons.logout),
                              label: const Text('Keluar'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                )
              : AsyncBody<Map<String, dynamic>>(
                  key: ValueKey('$_key-${anak['id']}'),
                  load: () => api.get(
                    '/wali/dashboard',
                    query: {'siswa_id': anak['id']},
                  ),
                  builder: (context, d, reload) {
                    final a = Map<String, dynamic>.from(d['anak']);
                    return ListView(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                      children: [
                        _Header(a: a, session: session),
                        const SizedBox(height: 18),
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                'Kartu Pelajar',
                                style: AppText.heading,
                              ),
                            ),
                            Text(
                              'TA ${(session.akun['tahun_ajaran'] ?? '-').toString().split(' ').first}',
                              style: AppText.caption,
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        KartuPelajar(
                          a: a,
                          tahunAjaran: session.akun['tahun_ajaran'],
                        ),
                        const SizedBox(height: 18),
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                'Aktivitas Hari Ini',
                                style: AppText.heading,
                              ),
                            ),
                            Text(
                              Fmt.tanggalPendek(Fmt.parse(d['tanggal'])),
                              style: AppText.caption,
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        _Aktivitas(
                          d: Map<String, dynamic>.from(d['aktivitas']),
                        ),
                        const SizedBox(height: 16),
                        _Menu(siswaId: a['id'], buka: _buka),
                        if (d['tugas'] != null) ...[
                          const SizedBox(height: 16),
                          _PengingatTugas(
                            t: Map<String, dynamic>.from(d['tugas']),
                            onTap: () => _buka('/wali/tugas'),
                          ),
                        ],
                        if (d['pengumuman'] != null) ...[
                          const SizedBox(height: 12),
                          AppCard(
                            onTap: () => _buka('/wali/pengumuman'),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    const StatusChip('Kegiatan Sekolah'),
                                    const Spacer(),
                                    Text(
                                      'Informasi Sekolah Terkini',
                                      style: AppText.caption,
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  d['pengumuman']['judul'],
                                  style: AppText.heading,
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    );
                  },
                ),
        ),
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.a, required this.session});
  final Map<String, dynamic> a;
  final Session session;

  @override
  Widget build(BuildContext context) {
    final banyak = session.daftarAnak.length > 1;
    return Row(
      children: [
        FotoAvatar(fotoUrl: a['foto_url'], inisial: a['inisial'] ?? ''),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Perkembangan Ananda:', style: AppText.bodySmall),
              InkWell(
                onTap: banyak
                    ? () => showModalBottomSheet(
                        context: context,
                        showDragHandle: true,
                        backgroundColor: AppColors.surface,
                        builder: (c) => SafeArea(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              for (final x in session.daftarAnak)
                                ListTile(
                                  leading: FotoAvatar(
                                    fotoUrl: x['foto_url'],
                                    inisial: x['inisial'] ?? '',
                                    size: 40,
                                  ),
                                  title: Text(x['nama']),
                                  subtitle: Text(x['kelas'] ?? '-'),
                                  trailing: x['id'] == a['id']
                                      ? const Icon(
                                          Icons.check,
                                          color: AppColors.brand700,
                                        )
                                      : null,
                                  onTap: () {
                                    session.pilihAnak(x['id'] as int);
                                    Navigator.pop(c);
                                  },
                                ),
                            ],
                          ),
                        ),
                      )
                    : null,
                child: Row(
                  children: [
                    Flexible(
                      child: Text(
                        a['nama'],
                        style: AppText.heading.copyWith(fontSize: 17),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    if (banyak) const Icon(Icons.expand_more),
                  ],
                ),
              ),
              Text(
                '${a['kelas'] ?? '-'}${a['wali_kelas'] == null ? '' : ' · Wali: ${a['wali_kelas']}'}',
                style: AppText.caption,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
        const SizedBox(width: 8),
        const TombolNotifikasi(rute: '/wali/notifikasi'),
      ],
    );
  }
}

/// Kartu Pelajar (desain baru) dengan QR Sholat untuk discan guru. Ketuk QR untuk memperbesar.
class KartuPelajar extends StatelessWidget {
  const KartuPelajar({
    super.key,
    required this.a,
    required this.tahunAjaran,
    this.onTapFoto,
  });
  final Map<String, dynamic> a;
  final String? tahunAjaran;
  final VoidCallback? onTapFoto;

  @override
  Widget build(BuildContext context) {
    return KartuPelajarDepan(
      nama: a['nama'] ?? '-',
      nis: a['nis'] ?? '-',
      kelas: a['kelas_singkat'] ?? a['kelas'] ?? '-',
      alamat: a['alamat'] ?? '-',
      inisial: a['inisial'] ?? '',
      fotoUrl: a['foto_url'],
      tahunAjaran: tahunAjaran,
      onTapFoto: onTapFoto,
      qr: a['qr'],
      onTapQr: a['qr'] == null
          ? null
          : () => showDialog(
              context: context,
              builder: (c) => AlertDialog(
                title: const Text('QR Sholat', textAlign: TextAlign.center),
                content: SizedBox(
                  width: 260,
                  height: 260,
                  child: QrImageView(
                    data: a['qr'],
                    backgroundColor: Colors.white,
                  ),
                ),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(c),
                    child: const Text('Tutup'),
                  ),
                ],
              ),
            ),
    );
  }
}

class _Aktivitas extends StatelessWidget {
  const _Aktivitas({required this.d});
  final Map<String, dynamic> d;

  @override
  Widget build(BuildContext context) {
    Widget baris(String label, Widget chip, {bool garis = true}) => Container(
      padding: const EdgeInsets.symmetric(vertical: 12),
      decoration: BoxDecoration(
        border: garis
            ? const Border(bottom: BorderSide(color: AppColors.line))
            : null,
      ),
      child: Row(
        children: [
          Expanded(child: Text(label, style: AppText.body)),
          chip,
        ],
      ),
    );
    final sholat = (d['sholat'] as List).cast<Map>();
    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        children: [
          baris(
            'Kehadiran',
            d['kehadiran'] == null
                ? const StatusChip('Belum diabsen')
                : StatusChip(
                    d['kehadiran'],
                    tone: StatusChip.toneFor(d['kehadiran']),
                  ),
          ),
          for (var i = 0; i < sholat.length; i++)
            baris(
              'Sholat ${sholat[i]['jenis']}',
              sholat[i]['status'] != null
                  ? StatusChip(
                      sholat[i]['status'],
                      tone: StatusChip.toneFor(sholat[i]['status']),
                    )
                  : StatusChip(
                      sholat[i]['sudah_masuk_waktu'] == true
                          ? 'Belum scan'
                          : (sholat[i]['jenis'] == 'Dhuha'
                                ? 'Pagi ini'
                                : 'Siang ini'),
                    ),
              garis: i < sholat.length - 1,
            ),
        ],
      ),
    );
  }
}

class _Menu extends StatelessWidget {
  const _Menu({required this.siswaId, required this.buka});
  final int siswaId;
  final Future<void> Function(String) buka;

  @override
  Widget build(BuildContext context) {
    final menu = [
      ('Absensi', 'rekap_absen', '/wali/kehadiran'),
      ('Nilai Rapor', 'rapor', '/wali/rapor'),
      ('Jadwal sekolah', 'jadwal', '/wali/jadwal'),
      ('Info Sekolah', 'info_sekolah', '/wali/pengumuman'),
      ('Izin', 'surat_izin', '/wali/izin'),
      ('Jadwal Guru', 'jadwal_guru', '/wali/guru'),
      ('Konseling', 'konseling', '/wali/konseling'),
      ('Profil', 'profil', '/wali/profil'),
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
            onTap: () => buka(m.$3),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                IkonMenu(m.$2),
                const SizedBox(height: 6),
                Text(
                  m.$1,
                  style: AppText.captionBold.copyWith(
                    fontSize: 11,
                    height: 1.15,
                  ),
                  textAlign: TextAlign.center,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
      ],
    );
  }
}

class _PengingatTugas extends StatelessWidget {
  const _PengingatTugas({required this.t, required this.onTap});
  final Map<String, dynamic> t;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final tenggat = t['tenggat'] == null ? null : Fmt.parse(t['tenggat']);
    final sisa = tenggat?.difference(DateUtils.dateOnly(DateTime.now())).inDays;
    return AppCard(
      onTap: onTap,
      borderColor: AppColors.gold,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.edit_outlined,
                size: 18,
                color: Color(0xFF8A6417),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  'Pengingat Tugas / PR',
                  style: AppText.label.copyWith(color: const Color(0xFF8A6417)),
                ),
              ),
              if (sisa != null)
                StatusChip(
                  sisa <= 0
                      ? 'Kumpul hari ini'
                      : (sisa == 1
                            ? 'Besok Kumpul'
                            : 'Kumpul ${Fmt.tanggalPendek(tenggat!)}'),
                  tone: ChipTone.gold,
                ),
            ],
          ),
          const SizedBox(height: 8),
          Text('${t['mata_pelajaran']}: ${t['judul']}', style: AppText.heading),
          const SizedBox(height: 4),
          Text(
            'Diberikan oleh ${t['guru'] ?? 'guru'}. Mohon didampingi belajar di rumah.',
            style: AppText.bodySmall,
          ),
        ],
      ),
    );
  }
}
