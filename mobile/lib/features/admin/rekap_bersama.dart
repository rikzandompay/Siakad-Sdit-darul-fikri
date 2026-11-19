import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/unduh.dart';
import '../../widgets/common.dart';

/// Tombol ekspor: berkas diambil dari server dan langsung disimpan ke folder Download.
Widget tombolEkspor(
  BuildContext context,
  String jenis,
  Map<String, Object?> q, {
  String? awalanApi,
}) => Row(
  children: [
    Expanded(
      child: SecondaryButton(
        label: 'Unduh CSV',
        icon: Icons.table_chart_outlined,
        color: AppColors.ok700,
        onPressed: () => unduhLaporan(
          context,
          jenis: jenis,
          format: 'csv',
          query: q,
          alamatApi: awalanApi == null ? null : '$awalanApi/$jenis/csv',
        ),
      ),
    ),
    const SizedBox(width: 10),
    Expanded(
      child: AlertButton(
        label: 'Unduh PDF',
        icon: Icons.picture_as_pdf_outlined,
        onPressed: () => unduhLaporan(
          context,
          jenis: jenis,
          format: 'pdf',
          query: q,
          alamatApi: awalanApi == null ? null : '$awalanApi/$jenis/pdf',
        ),
      ),
    ),
  ],
);

/// Pemuat pilihan filter (kelas, mapel, tahun ajaran) sekali untuk layar rekap.
mixin OpsiAdmin<T extends StatefulWidget> on State<T> {
  Map<String, dynamic>? opsi;
  bool opsiGagal = false;

  // Kepala sekolah lewat jalur /admin; guru wali kelas lewat jalur /guru.
  String get awalan => context.read<Session>().isAdmin ? '/admin' : '/guru';
  String? get awalanEkspor => awalan == '/admin' ? null : '/guru/ekspor';
  String get alamatOpsi => '$awalan/opsi';

  @override
  void initState() {
    super.initState();
    context
        .read<ApiClient>()
        .get(alamatOpsi)
        .then((d) {
          if (mounted) setState(() => opsi = d);
          opsiSiap(d);
        })
        .catchError((_) {
          if (mounted) setState(() => opsiGagal = true);
        });
  }

  void opsiSiap(Map<String, dynamic> d) {}

  /// Daftar kelas untuk pilihan. 'kelas_wali' TIDAK PERNAH jatuh ke seluruh kelas: bila server lama belum
  /// mengirim kuncinya, daftar disaring dari nama kelas yang diwalikan akun ini (profil `wali_kelas`).
  List<Map> daftarKelas(String kunci) {
    final langsung = opsi?[kunci];
    if (langsung is List) return langsung.cast<Map>();
    final semua = ((opsi?['kelas'] as List?) ?? const []).cast<Map>();
    if (kunci != 'kelas_wali') return semua;
    final wali =
        ((context.read<Session>().akun['wali_kelas'] as List?) ?? const [])
            .map((e) => '$e')
            .toSet();
    return semua.where((k) => wali.contains(k['nama'])).toList();
  }

  Map<int, String> kelasItemDari(String kunci) => {
    for (final k in daftarKelas(kunci)) k['id'] as int: k['nama'] as String,
  };

  Widget tungguAtauKosong(String pesanKosong) {
    if (opsiGagal) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text('Gagal memuat daftar kelas.', style: AppText.bodySmall),
        ),
      );
    }
    if (opsi == null) return const Center(child: CircularProgressIndicator());
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Text(
          pesanKosong,
          style: AppText.bodySmall,
          textAlign: TextAlign.center,
        ),
      ),
    );
  }
}

class TabelRekap extends StatelessWidget {
  const TabelRekap({
    super.key,
    required this.judul,
    required this.periode,
    required this.kolom,
    required this.data,
    this.catatan,
  });

  final String judul;
  final String periode;
  final List<(String, String, Color)> kolom;
  final List<Map> data;
  final String? catatan;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
      children: [
        if (catatan != null) ...[
          AppCard(
            color: AppColors.brand50,
            borderColor: AppColors.brand200,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(
                  Icons.info_outline_rounded,
                  size: 18,
                  color: AppColors.brand700,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    catatan!,
                    style: AppText.bodySmall.copyWith(
                      color: AppColors.brand700,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),
        ],
        Row(
          children: [
            Expanded(child: Text(judul, style: AppText.label)),
            Text(periode, style: AppText.caption),
          ],
        ),
        const SizedBox(height: 8),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  'NAMA SISWA',
                  style: AppText.overline.copyWith(fontSize: 10),
                ),
              ),
              for (final k in kolom)
                SizedBox(
                  width: kolom.length > 2 ? 46 : 70,
                  child: Text(
                    k.$2,
                    style: AppText.overline.copyWith(fontSize: 10),
                    textAlign: TextAlign.center,
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 6),
        if (data.isEmpty)
          const EmptyPanel(
            icon: Icons.group_off_outlined,
            message: 'Belum ada siswa aktif.',
          ),
        for (var i = 0; i < data.length; i++)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: AppCard(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${i + 1}. ${data[i]['nama']}',
                          style: AppText.label,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text('NIS ${data[i]['nis']}', style: AppText.caption),
                      ],
                    ),
                  ),
                  for (final k in kolom)
                    SizedBox(
                      width: kolom.length > 2 ? 46 : 70,
                      child: Text(
                        '${data[i]['rekap'][k.$1]}',
                        style: AppText.heading.copyWith(color: k.$3),
                        textAlign: TextAlign.center,
                      ),
                    ),
                ],
              ),
            ),
          ),
      ],
    );
  }
}

