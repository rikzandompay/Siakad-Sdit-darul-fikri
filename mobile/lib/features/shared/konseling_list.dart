import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../core/theme.dart';
import '../../widgets/common.dart';

/// Baris percakapan konseling (dipakai wali & guru).
class KonselingRow extends StatelessWidget {
  const KonselingRow({
    super.key,
    required this.p,
    required this.judul,
    required this.subjudul,
    required this.onTap,
  });
  final Map<String, dynamic> p;
  final String judul;
  final String subjudul;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final belum = p['belum_dibaca'] as int? ?? 0;
    final waktu = p['pesan_terakhir_pada'] == null
        ? null
        : DateTime.parse(p['pesan_terakhir_pada']).toLocal();
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        onTap: onTap,
        child: Row(
          children: [
            InisialAvatar(p['inisial'] ?? '', size: 48),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    judul,
                    style: AppText.heading,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    subjudul,
                    style: AppText.caption,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (p['pesan_terakhir'] != null)
                    Text(
                      p['pesan_terakhir'],
                      style: AppText.bodySmall,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                if (waktu != null)
                  Text(
                    DateUtils.isSameDay(waktu, DateTime.now())
                        ? DateFormat('HH:mm').format(waktu)
                        : DateFormat('d MMM', 'id_ID').format(waktu),
                    style: AppText.caption,
                  ),
                const SizedBox(height: 4),
                if (belum > 0)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 7,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.brand700,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      '$belum',
                      style: AppText.captionBold.copyWith(color: Colors.white),
                    ),
                  )
                else if (p['status'] == 'Selesai')
                  const StatusChip('Selesai'),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
