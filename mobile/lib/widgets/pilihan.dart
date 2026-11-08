import 'package:flutter/material.dart';

import '../core/theme.dart';

/// Chip pilihan tunggal (rentang waktu, jenis sholat, hari, dll).
class PilihanChips<T> extends StatelessWidget {
  const PilihanChips({super.key, required this.pilihan, required this.nilai, required this.onChanged});

  final Map<T, String> pilihan;
  final T nilai;
  final ValueChanged<T> onChanged;

  @override
  Widget build(BuildContext context) {
    return Wrap(spacing: 8, runSpacing: 8, children: [
      for (final e in pilihan.entries)
        ChoiceChip(
          label: Text(e.value),
          selected: e.key == nilai,
          onSelected: (_) => onChanged(e.key),
          showCheckmark: false,
          selectedColor: AppColors.brand700,
          backgroundColor: AppColors.surface,
          side: BorderSide(color: e.key == nilai ? AppColors.brand700 : AppColors.line),
          labelStyle: AppText.captionBold.copyWith(fontSize: 13, color: e.key == nilai ? Colors.white : AppColors.ink),
          shape: const StadiumBorder(),
        ),
    ]);
  }
}

const rentangWaktu = {'hari_ini': 'Hari Ini', 'minggu_ini': 'Minggu Ini', 'bulan_ini': 'Bulan Ini', 'semester_ini': 'Semester'};

/// Dropdown bergaya Figma.
class PilihanDropdown<T> extends StatelessWidget {
  const PilihanDropdown({super.key, required this.label, required this.nilai, required this.item, required this.onChanged});

  final String label;
  final T? nilai;
  final Map<T, String> item;
  final ValueChanged<T?> onChanged;

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label.toUpperCase(), style: AppText.overline.copyWith(fontSize: 10)),
      const SizedBox(height: 6),
      DropdownButtonFormField<T>(
        initialValue: item.containsKey(nilai) ? nilai : null,
        isExpanded: true,
        items: [for (final e in item.entries) DropdownMenuItem(value: e.key, child: Text(e.value, overflow: TextOverflow.ellipsis))],
        onChanged: onChanged,
        decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 12)),
      ),
    ]);
  }
}

/// Ringkasan angka berwarna (Hadir / Terlambat / Tidak hadir, dll).
class KotakAngka extends StatelessWidget {
  const KotakAngka({super.key, required this.angka, required this.label, required this.warna, required this.latar});
  final String angka;
  final String label;
  final Color warna;
  final Color latar;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(color: latar, borderRadius: BorderRadius.circular(14)),
        child: Column(children: [
          Text(angka, style: AppText.title.copyWith(color: warna, fontSize: 22)),
          Text(label, style: AppText.caption.copyWith(color: warna)),
        ]),
      );
}
