import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/theme.dart';
import '../../core/unduh.dart';
import '../../widgets/common.dart';

/// Daftar pengumuman (wali: /wali/pengumuman, guru: /guru/pengumuman).
class PengumumanScreen extends StatelessWidget {
  const PengumumanScreen({super.key, required this.endpoint});
  final String endpoint;

  /// Alamat PDF ikut peran: wali → /wali/pengumuman/surat/{id}, guru → /guru/...
  /// Bentuk /pengumuman/surat/{id} dipakai agar tetap cocok dengan server yang
  /// cache rutenya masih versi lama.
  String _alamatPdf(String id) =>
      '${endpoint.replaceFirst('/pengumuman', '')}'
      '/pengumuman/surat/$id';

  @override
  Widget build(BuildContext context) {
    final api = context.read<ApiClient>();
    return AppPage(
      title: 'Pengumuman Resmi Sekolah',
      subtitle: 'Informasi SDIT Darul Fikri',
      scroll: false,
      padding: EdgeInsets.zero,
      child: AsyncBody<Map<String, dynamic>>(
        load: () => api.get(endpoint),
        builder: (context, d, _) {
          final list = (d['data'] as List).cast<Map>();
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              if (list.isEmpty)
                const EmptyPanel(
                  icon: Icons.campaign_outlined,
                  message: 'Belum ada pengumuman.',
                ),
              for (final p in list)
                PengumumanCard(
                  p: Map<String, dynamic>.from(p),
                  onUnduh: () => unduhLaporan(
                    context,
                    jenis: 'pengumuman',
                    format: 'pdf',
                    keterangan: 'Surat pengumuman',
                    namaBerkas: 'pengumuman-${p['id']}',
                    alamatApi: _alamatPdf('${p['id']}'),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

/// Kartu pengumuman ringkas: isi dibatasi beberapa baris, ketuk kartu untuk
/// membuka surat pengumuman PDF lengkap (ber-kop & bertanda tangan).
class PengumumanCard extends StatelessWidget {
  const PengumumanCard({super.key, required this.p, this.aksi, this.onUnduh});
  final Map<String, dynamic> p;
  final Widget? aksi;

  /// Membuka surat PDF; muncul petunjuk "Buka surat" bila diisi.
  final VoidCallback? onUnduh;

  /// Tanggal untuk ditampilkan: tanggal surat bila ada, jika tidak tanggal terbit.
  String get _tanggalTampil {
    final t = p['tanggal_surat'] ?? p['tanggal'];
    final d = t == null ? null : Fmt.parse(t);
    return d == null ? '-' : Fmt.tanggalPendek(d);
  }

  @override
  Widget build(BuildContext context) {
    final punyaJadwal = [
      p['jadwal_hari_tanggal'],
      p['jadwal_waktu'],
      p['jadwal_tempat'],
    ].any((v) => (v ?? '').toString().isNotEmpty);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        borderColor: p['penting'] == true ? AppColors.gold : null,
        onTap: onUnduh,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                if (p['penting'] == true)
                  const Padding(
                    padding: EdgeInsets.only(right: 6),
                    child: StatusChip('Penting', tone: ChipTone.gold),
                  ),
                StatusChip(
                  p['sasaran'] == 'Wali Murid' ? 'Wali' : p['sasaran'],
                  tone: ChipTone.brand,
                ),
                const Spacer(),
                Text(_tanggalTampil, style: AppText.caption),
                ?aksi,
              ],
            ),
            const SizedBox(height: 10),
            Text(p['judul'], style: AppText.heading),
            if (p['nomor_surat'] != null)
              Padding(
                padding: const EdgeInsets.only(top: 2),
                child: Text(
                  'Nomor: ${p['nomor_surat']}',
                  style: AppText.caption,
                ),
              ),
            const SizedBox(height: 6),
            Text(
              p['isi'],
              style: AppText.bodySmall,
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
            ),
            if (punyaJadwal) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(
                    Icons.event_outlined,
                    size: 16,
                    color: AppColors.brand700,
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      [
                            p['jadwal_hari_tanggal'],
                            p['jadwal_waktu'],
                            p['jadwal_tempat'],
                          ]
                          .where((v) => (v ?? '').toString().isNotEmpty)
                          .join(' · '),
                      style: AppText.caption,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ],
            if (onUnduh != null) ...[
              const SizedBox(height: 10),
              Row(
                children: [
                  const Icon(
                    Icons.picture_as_pdf_outlined,
                    size: 16,
                    color: AppColors.brand700,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    'Buka surat pengumuman',
                    style: AppText.label.copyWith(color: AppColors.brand700),
                  ),
                  const Spacer(),
                  const Icon(
                    Icons.chevron_right,
                    size: 18,
                    color: AppColors.brand700,
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
