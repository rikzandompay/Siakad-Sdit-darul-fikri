import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/notifikasi.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

/// Riwayat notifikasi (guru & wali): konseling, pengumuman, surat izin, tugas.
class NotifikasiScreen extends StatefulWidget {
  const NotifikasiScreen({super.key});

  @override
  State<NotifikasiScreen> createState() => _NotifikasiScreenState();
}

class _NotifikasiScreenState extends State<NotifikasiScreen> {
  Key _key = UniqueKey();

  Future<Map<String, dynamic>> _muat() async {
    final res = await context.read<ApiClient>().get('/notifikasi');
    if (mounted) context.read<LayananNotifikasi>().setJumlah((res['belum_dibaca'] as num?)?.toInt() ?? 0);
    return res;
  }

  Future<void> _bacaSemua() async {
    try {
      await context.read<ApiClient>().post('/notifikasi/baca-semua');
      if (!mounted) return;
      context.read<LayananNotifikasi>().setJumlah(0);
      setState(() => _key = UniqueKey());
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  Future<void> _buka(Map<String, dynamic> n) async {
    final api = context.read<ApiClient>();
    final layanan = context.read<LayananNotifikasi>();
    if (n['dibaca'] != true) {
      try {
        final res = await api.post('/notifikasi/${n['id']}/baca');
        layanan.setJumlah((res['belum_dibaca'] as num?)?.toInt() ?? 0);
      } catch (_) {}
    }
    final rute = n['rute'] as String?;
    if (!mounted) return;
    if (rute != null && rute.isNotEmpty) await context.push(rute);
    if (mounted) setState(() => _key = UniqueKey());
  }

  @override
  Widget build(BuildContext context) {
    final session = context.read<Session>();
    final adaBelumDibaca = context.watch<LayananNotifikasi>().belumDibaca > 0;
    final rutePengumuman = session.isAdmin ? '/admin/pengumuman' : (session.peran == Peran.wali ? '/wali/pengumuman' : '/guru/pengumuman');

    return AppPage(
      title: 'Notifikasi',
      subtitle: 'Konseling, pengumuman, izin, dan tugas',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        key: _key,
        load: _muat,
        builder: (context, d, _) {
          final list = (d['data'] as List).cast<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
          return ListView(padding: const EdgeInsets.fromLTRB(16, 4, 16, 24), children: [
            // Wrap, bukan Row: di layar sempit tombol turun ke baris berikutnya, tidak overflow.
            Wrap(alignment: WrapAlignment.spaceBetween, children: [
              TextButton.icon(
                onPressed: () => context.push(rutePengumuman),
                icon: const Icon(Icons.campaign_outlined, size: 20),
                label: const Text('Semua pengumuman'),
              ),
              if (adaBelumDibaca) TextButton(onPressed: _bacaSemua, child: const Text('Tandai semua dibaca')),
            ]),
            const SizedBox(height: 4),
            if (list.isEmpty) const EmptyPanel(icon: Icons.notifications_none_rounded, message: 'Belum ada notifikasi.'),
            for (final n in list) _ItemNotifikasi(n: n, onTap: () => _buka(n)),
          ]);
        },
      ),
    );
  }
}

class _ItemNotifikasi extends StatelessWidget {
  const _ItemNotifikasi({required this.n, required this.onTap});
  final Map<String, dynamic> n;
  final VoidCallback onTap;

  static const _ikon = {
    'konseling': (Icons.forum_outlined, AppColors.brand700, AppColors.brand100),
    'pengumuman': (Icons.campaign_outlined, AppColors.gold, AppColors.goldTint),
    'izin': (Icons.assignment_outlined, AppColors.sakit, AppColors.sakitTint),
    'tugas': (Icons.menu_book_outlined, AppColors.ok700, AppColors.ok100),
    'foto': (Icons.account_circle_outlined, AppColors.brand700, AppColors.brand100),
    'lokasi': (Icons.wrong_location_outlined, AppColors.alpa, AppColors.alpaTint),
  };

  @override
  Widget build(BuildContext context) {
    final belum = n['dibaca'] != true;
    final (ikon, warna, latar) = _ikon[n['jenis']] ?? (Icons.notifications_none_rounded, AppColors.inkMuted, AppColors.field);
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Semantics(
        label: belum ? 'Belum dibaca' : null,
        child: AppCard(
          onTap: onTap,
          color: belum ? AppColors.brand50 : null,
          borderColor: belum ? AppColors.brand200 : null,
          padding: const EdgeInsets.all(14),
          child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(color: latar, borderRadius: BorderRadius.circular(12)),
              child: Icon(ikon, color: warna, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Expanded(
                    child: Text(n['judul'] ?? '', style: AppText.label.copyWith(fontWeight: belum ? FontWeight.w700 : FontWeight.w500), maxLines: 2, overflow: TextOverflow.ellipsis),
                  ),
                  const SizedBox(width: 8),
                  Text(_waktu(n['waktu'] as String?), style: AppText.caption),
                ]),
                const SizedBox(height: 4),
                Text(n['isi'] ?? '', style: AppText.bodySmall, maxLines: 3, overflow: TextOverflow.ellipsis),
              ]),
            ),
            if (belum) ...[
              const SizedBox(width: 8),
              Container(width: 8, height: 8, margin: const EdgeInsets.only(top: 6), decoration: const BoxDecoration(color: AppColors.brand700, shape: BoxShape.circle)),
            ],
          ]),
        ),
      ),
    );
  }

  static String _waktu(String? iso) {
    if (iso == null) return '';
    final t = DateTime.tryParse(iso)?.toLocal();
    if (t == null) return '';
    final selisih = DateTime.now().difference(t);
    if (selisih.inMinutes < 1) return 'Baru saja';
    if (selisih.inMinutes < 60) return '${selisih.inMinutes} mnt';
    if (selisih.inHours < 24) return '${selisih.inHours} jam';
    if (selisih.inDays < 7) return '${selisih.inDays} hr';
    return DateFormat('d MMM', 'id_ID').format(t);
  }
}

/// Tombol lonceng di header beranda, dengan badge jumlah notifikasi belum dibaca.
class TombolNotifikasi extends StatelessWidget {
  const TombolNotifikasi({super.key, required this.rute, this.setelahKembali});
  final String rute;
  final VoidCallback? setelahKembali;

  @override
  Widget build(BuildContext context) {
    final jumlah = context.watch<LayananNotifikasi>().belumDibaca;
    return Semantics(
      label: jumlah > 0 ? 'Notifikasi, $jumlah belum dibaca' : 'Notifikasi',
      button: true,
      excludeSemantics: true,
      child: Badge(
        isLabelVisible: jumlah > 0,
        label: Text(jumlah > 99 ? '99+' : '$jumlah'),
        backgroundColor: AppColors.alpa,
        offset: const Offset(-4, 4),
        child: IconButton.outlined(
          tooltip: 'Notifikasi',
          onPressed: () async {
            await context.push(rute);
            setelahKembali?.call();
          },
          icon: const Icon(Icons.notifications_none_rounded),
          style: IconButton.styleFrom(side: const BorderSide(color: AppColors.line), backgroundColor: AppColors.surface, minimumSize: const Size(48, 48), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
        ),
      ),
    );
  }
}
