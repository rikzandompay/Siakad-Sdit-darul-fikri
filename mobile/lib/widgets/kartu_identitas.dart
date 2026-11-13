import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/api_client.dart';
import '../core/theme.dart';

/// Kartu Guru / Kartu Pelajar seperti di Figma (tanpa QR untuk guru).
class KartuIdentitas extends StatelessWidget {
  const KartuIdentitas({
    super.key,
    required this.jenis,
    required this.nama,
    required this.inisial,
    required this.fields,
    required this.tahunAjaran,
    this.footerKanan,
    this.fotoUrl,
    this.onTapFoto,
    this.qr,
  });

  final String jenis; // "KARTU GURU" / "KARTU PELAJAR"
  final String nama;
  final String inisial;
  final List<(String, String)> fields;
  final String? tahunAjaran;
  final String? footerKanan;
  final String? fotoUrl;
  final VoidCallback? onTapFoto;
  final Widget? qr;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.brand200),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.white, AppColors.brand50, AppColors.brand100],
        ),
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 4),
            child: Row(
              children: [
                Image.asset('assets/images/logo.png', height: 34),
                const SizedBox(width: 10),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      jenis,
                      style: AppText.overline.copyWith(
                        color: AppColors.brand700,
                      ),
                    ),
                    Text('SDIT Darul Fikri', style: AppText.title),
                  ],
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                GestureDetector(
                  onTap: onTapFoto,
                  child: Stack(
                    clipBehavior: Clip.none,
                    children: [
                      Container(
                        width: 74,
                        height: 92,
                        clipBehavior: Clip.antiAlias,
                        decoration: BoxDecoration(
                          color: AppColors.brand100,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: AppColors.brand200),
                        ),
                        alignment: Alignment.center,
                        child: (fotoUrl != null && fotoUrl!.trim().isNotEmpty)
                            ? Image.network(
                                fotoUrl!,
                                // Foto disajikan lewat rute yang butuh login.
                                headers: context.read<ApiClient>().authHeaders,
                                width: 74,
                                height: 92,
                                fit: BoxFit.cover,
                                errorBuilder: (_, _, _) =>
                                    _FotoPlaceholder(inisial: inisial),
                              )
                            : _FotoPlaceholder(inisial: inisial),
                      ),
                      if (onTapFoto != null)
                        Positioned(
                          right: -4,
                          bottom: -4,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: const BoxDecoration(
                              color: AppColors.brand700,
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(
                              Icons.camera_alt,
                              size: 12,
                              color: Colors.white,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        nama,
                        style: AppText.heading,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 6),
                      Wrap(
                        spacing: 16,
                        runSpacing: 6,
                        children: [
                          for (final f in fields)
                            SizedBox(
                              width: qr == null
                                  ? 200
                                  : (f.$1.toLowerCase() == 'alamat' ? 180 : 85),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    f.$1.toUpperCase(),
                                    style: AppText.overline.copyWith(
                                      fontSize: 10,
                                    ),
                                  ),
                                  Text(
                                    f.$2,
                                    style: AppText.captionBold.copyWith(
                                      fontSize: 12,
                                      color: AppColors.ink,
                                    ),
                                    maxLines: f.$1.toLowerCase() == 'alamat'
                                        ? 2
                                        : 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
                ?qr,
              ],
            ),
          ),
          Container(
            color: AppColors.brand700,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    'Tahun Ajaran ${tahunAjaran ?? '-'}',
                    style: AppText.captionBold.copyWith(
                      color: Colors.white,
                      fontSize: 11,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Text(
                  footerKanan ?? '',
                  style: AppText.caption.copyWith(
                    color: Colors.white,
                    fontSize: 11,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _FotoPlaceholder extends StatelessWidget {
  const _FotoPlaceholder({required this.inisial});
  final String inisial;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(inisial, style: AppText.title.copyWith(color: AppColors.brand700)),
        Text(
          'FOTO 3×4',
          style: AppText.overline.copyWith(
            color: AppColors.brand700,
            fontSize: 9,
          ),
        ),
      ],
    );
  }
}
