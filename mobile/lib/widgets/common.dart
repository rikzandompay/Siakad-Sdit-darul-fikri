import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../core/api_client.dart';
import '../core/theme.dart';

/// Kerangka halaman: latar gradien, area aman, header opsional, tombol bawah opsional.
class AppPage extends StatelessWidget {
  const AppPage({
    super.key,
    this.title,
    this.subtitle,
    required this.child,
    this.bottom,
    this.scroll = true,
    this.padding = const EdgeInsets.fromLTRB(16, 8, 16, 24),
    this.onBack,
  });

  final String? title;
  final String? subtitle;
  final Widget child;
  final Widget? bottom;
  final bool scroll;
  final EdgeInsets padding;
  final VoidCallback? onBack;

  @override
  Widget build(BuildContext context) {
    final body = Padding(padding: padding, child: child);
    return DecoratedBox(
      decoration: const BoxDecoration(gradient: AppColors.pageGradient),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        // Bilah bawah (mis. kolom ketik chat) ada di dalam body, bukan bottomNavigationBar:
        // body ikut naik di atas keyboard, sedangkan bottomNavigationBar tertutup keyboard.
        body: SafeArea(
          bottom: bottom == null,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (title != null)
                PageHeader(title: title!, subtitle: subtitle, onBack: onBack),
              Expanded(
                child: scroll ? SingleChildScrollView(child: body) : body,
              ),
              if (bottom != null)
                Builder(
                  builder: (context) => Container(
                    color: AppColors.surface,
                    // Di dalam body, padding bawah sistem otomatis nol saat keyboard terbuka.
                    padding: EdgeInsets.fromLTRB(
                      16,
                      12,
                      16,
                      12 + MediaQuery.of(context).padding.bottom,
                    ),
                    child: bottom,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class PageHeader extends StatelessWidget {
  const PageHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.onBack,
  });

  final String title;
  final String? subtitle;
  final VoidCallback? onBack;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: Row(
        children: [
          Semantics(
            button: true,
            label: 'Kembali',
            child: InkWell(
              onTap:
                  onBack ??
                  () => context.canPop() ? context.pop() : context.go('/'),
              borderRadius: BorderRadius.circular(12),
              child: Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.line),
                ),
                child: const Icon(Icons.chevron_left, color: AppColors.ink),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: AppText.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                if (subtitle != null)
                  Text(
                    subtitle!,
                    style: AppText.bodySmall,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class AppCard extends StatelessWidget {
  const AppCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.onTap,
    this.borderColor,
    this.color,
  });

  final Widget child;
  final EdgeInsets padding;
  final VoidCallback? onTap;
  final Color? borderColor;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color ?? AppColors.surface,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: padding,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: borderColor ?? AppColors.line),
          ),
          child: child,
        ),
      ),
    );
  }
}

class _BaseButton extends StatelessWidget {
  const _BaseButton({
    required this.label,
    required this.onPressed,
    required this.bgColor,
    required this.fgColor,
    this.borderColor,
    this.shadowColor,
    this.icon,
    this.loading = false,
  });

  final String label;
  final VoidCallback? onPressed;
  final Color bgColor;
  final Color fgColor;
  final Color? borderColor;
  final Color? shadowColor;
  final IconData? icon;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    final disabled = onPressed == null || loading;
    final effectiveBg = disabled ? bgColor.withValues(alpha: 0.35) : bgColor;
    final effectiveFg = disabled ? fgColor.withValues(alpha: 0.45) : fgColor;
    final effectiveBorder = borderColor != null
        ? (disabled ? borderColor!.withValues(alpha: 0.35) : borderColor!)
        : null;

    final hasShadow = shadowColor != null && !disabled;

    return Container(
      height: 48,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        boxShadow: hasShadow
            ? [
                BoxShadow(
                  color: shadowColor!.withValues(alpha: 0.22),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ]
            : null,
      ),
      child: Material(
        color: effectiveBg,
        borderRadius: BorderRadius.circular(12),
        child: InkWell(
          onTap: disabled ? null : onPressed,
          borderRadius: BorderRadius.circular(12),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              border: effectiveBorder != null
                  ? Border.all(color: effectiveBorder, width: 1.2)
                  : null,
            ),
            alignment: Alignment.center,
            child: loading
                ? SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5,
                      color: effectiveFg,
                    ),
                  )
                : Row(
                    mainAxisSize: MainAxisSize.min,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      if (icon != null) ...[
                        Icon(icon, size: 18, color: effectiveFg),
                        const SizedBox(width: 8),
                      ],
                      Text(
                        label,
                        style: AppText.label.copyWith(
                          color: effectiveFg,
                          fontWeight: FontWeight.w600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}

/// Tombol Primary (Brand Blue) — untuk aksi utama pada halaman.
class PrimaryButton extends StatelessWidget {
  const PrimaryButton({
    super.key,
    required this.label,
    this.onPressed,
    this.loading = false,
    this.color,
    this.icon,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool loading;
  final Color? color;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final c = color ?? AppColors.brand700;
    return _BaseButton(
      label: label,
      onPressed: onPressed,
      bgColor: c,
      fgColor: Colors.white,
      shadowColor: c,
      icon: icon,
      loading: loading,
    );
  }
}

/// Tombol Secondary (Outlined) — untuk aksi sekunder atau alternatif.
class SecondaryButton extends StatelessWidget {
  const SecondaryButton({
    super.key,
    required this.label,
    this.onPressed,
    this.color = AppColors.brand700,
    this.icon,
    this.loading = false,
  });

  final String label;
  final VoidCallback? onPressed;
  final Color color;
  final IconData? icon;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    return _BaseButton(
      label: label,
      onPressed: onPressed,
      bgColor: AppColors.surface,
      fgColor: color,
      borderColor: color,
      icon: icon,
      loading: loading,
    );
  }
}

/// Tombol Tertiary (Ghost / Text) — tanpa border dan background, untuk navigasi halus atau batal.
class TertiaryButton extends StatelessWidget {
  const TertiaryButton({
    super.key,
    required this.label,
    this.onPressed,
    this.color = AppColors.brand700,
    this.icon,
    this.loading = false,
  });

  final String label;
  final VoidCallback? onPressed;
  final Color color;
  final IconData? icon;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    return _BaseButton(
      label: label,
      onPressed: onPressed,
      bgColor: Colors.transparent,
      fgColor: color,
      icon: icon,
      loading: loading,
    );
  }
}

/// Tombol Success (Hijau) — untuk aksi sukses, konfirmasi positif, atau absen masuk.
class SuccessButton extends StatelessWidget {
  const SuccessButton({
    super.key,
    required this.label,
    this.onPressed,
    this.loading = false,
    this.icon,
    this.color,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool loading;
  final IconData? icon;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final c = color ?? AppColors.ok600;
    return _BaseButton(
      label: label,
      onPressed: onPressed,
      bgColor: c,
      fgColor: Colors.white,
      shadowColor: c,
      icon: icon,
      loading: loading,
    );
  }
}

/// Tombol Warning (Amber / Kuning-Oranye) — untuk aksi peringatan, tinjauan, atau aksi kedua.
class WarningButton extends StatelessWidget {
  const WarningButton({
    super.key,
    required this.label,
    this.onPressed,
    this.loading = false,
    this.icon,
    this.color,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool loading;
  final IconData? icon;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final c = color ?? const Color(0xFFD97706);
    return _BaseButton(
      label: label,
      onPressed: onPressed,
      bgColor: c,
      fgColor: Colors.white,
      shadowColor: c,
      icon: icon,
      loading: loading,
    );
  }
}

/// Tombol Alert (Merah / Bahaya) — untuk aksi penolakan, pembatalan darurat, hapus, atau absen pulang.
class AlertButton extends StatelessWidget {
  const AlertButton({
    super.key,
    required this.label,
    this.onPressed,
    this.loading = false,
    this.icon,
    this.color,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool loading;
  final IconData? icon;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final c = color ?? AppColors.alpa;
    return _BaseButton(
      label: label,
      onPressed: onPressed,
      bgColor: c,
      fgColor: Colors.white,
      shadowColor: c,
      icon: icon,
      loading: loading,
    );
  }
}

enum ChipTone { ok, gold, sakit, alpa, netral, brand }

class StatusChip extends StatelessWidget {
  const StatusChip(this.label, {super.key, this.tone = ChipTone.netral});

  final String label;
  final ChipTone tone;

  static ChipTone toneFor(String? status) => switch (status) {
    'Hadir' || 'H' => ChipTone.ok,
    'Izin' || 'I' => ChipTone.gold,
    'Sakit' || 'S' => ChipTone.sakit,
    'Tidak Hadir' || 'Alpa' || 'A' => ChipTone.alpa,
    _ => ChipTone.netral,
  };

  @override
  Widget build(BuildContext context) {
    final (bg, fg) = switch (tone) {
      ChipTone.ok => (AppColors.ok100, AppColors.ok700),
      ChipTone.gold => (AppColors.goldTint, const Color(0xFF8A6417)),
      ChipTone.sakit => (AppColors.sakitTint, AppColors.sakit),
      ChipTone.alpa => (AppColors.alpaTint, AppColors.alpa),
      ChipTone.brand => (AppColors.brand100, AppColors.brand800),
      ChipTone.netral => (const Color(0xFFE5E7EB), const Color(0xFF4B5563)),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(label, style: AppText.captionBold.copyWith(color: fg)),
    );
  }
}

class SectionLabel extends StatelessWidget {
  const SectionLabel(this.text, {super.key});
  final String text;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 8, top: 4),
    child: Text(text.toUpperCase(), style: AppText.overline),
  );
}

/// Kotak biru muda berisi teks pendek (5A, 07:30, dll).
class BadgeBox extends StatelessWidget {
  const BadgeBox({super.key, required this.top, this.bottom, this.size = 52});

  final String top;
  final String? bottom;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: AppColors.brand100,
        borderRadius: BorderRadius.circular(12),
      ),
      alignment: Alignment.center,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            top,
            style: AppText.label.copyWith(
              color: AppColors.brand700,
              fontSize: bottom == null ? 16 : 14,
            ),
          ),
          if (bottom != null)
            Text(
              bottom!,
              style: AppText.caption.copyWith(color: AppColors.brand700),
            ),
        ],
      ),
    );
  }
}

/// Baris daftar: badge, judul, subjudul, chip opsional, panah.
class ListRowCard extends StatelessWidget {
  const ListRowCard({
    super.key,
    required this.badge,
    required this.title,
    this.subtitle,
    this.chip,
    this.onTap,
  });

  final Widget badge;
  final String title;
  final String? subtitle;
  final Widget? chip;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        onTap: onTap,
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            badge,
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: AppText.heading),
                  if (subtitle != null) ...[
                    const SizedBox(height: 2),
                    Text(subtitle!, style: AppText.bodySmall),
                  ],
                  if (chip != null) ...[const SizedBox(height: 6), chip!],
                ],
              ),
            ),
            if (onTap != null)
              const Icon(Icons.chevron_right, color: AppColors.inkMuted),
          ],
        ),
      ),
    );
  }
}

class InisialAvatar extends StatelessWidget {
  const InisialAvatar(this.inisial, {super.key, this.size = 52});
  final String inisial;
  final double size;

  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    decoration: const BoxDecoration(
      color: AppColors.brand100,
      shape: BoxShape.circle,
    ),
    alignment: Alignment.center,
    child: Text(
      inisial,
      style: AppText.heading.copyWith(
        color: AppColors.brand700,
        fontSize: size * 0.32,
      ),
    ),
  );
}

/// Foto profil bulat; bila belum ada (atau gagal dimuat) tampil inisial.
class FotoAvatar extends StatelessWidget {
  const FotoAvatar({
    super.key,
    required this.fotoUrl,
    required this.inisial,
    this.size = 52,
  });
  final String? fotoUrl;
  final String inisial;
  final double size;

  @override
  Widget build(BuildContext context) {
    final url = fotoUrl;
    if (url == null || url.isEmpty) return InisialAvatar(inisial, size: size);
    return ClipOval(
      child: Image.network(
        url,
        headers: context.read<ApiClient>().authHeaders,
        width: size,
        height: size,
        fit: BoxFit.cover,
        cacheWidth: (size * MediaQuery.devicePixelRatioOf(context)).round(),
        errorBuilder: (_, _, _) => InisialAvatar(inisial, size: size),
        loadingBuilder: (_, child, p) =>
            p == null ? child : InisialAvatar(inisial, size: size),
      ),
    );
  }
}

/// Satu butir menu: ikon, judul, keterangan singkat, lencana angka opsional.
class MenuItemData {
  const MenuItemData(
    this.judul,
    this.ikon,
    this.path, {
    this.ket,
    this.lencana = 0,
  });
  final String judul;
  final String ikon;
  final String path;
  final String? ket;
  final int lencana;
}

/// Kelompok menu sebagai daftar bersekat: tiap baris minimal 56 dp, panah di kanan.
class MenuGroup extends StatelessWidget {
  const MenuGroup({
    super.key,
    required this.judul,
    required this.items,
    required this.onBuka,
  });
  final String judul;
  final List<MenuItemData> items;
  final void Function(String path) onBuka;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 20, bottom: 8, left: 4),
          child: Text(judul.toUpperCase(), style: AppText.overline),
        ),
        Container(
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.line),
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            children: [
              for (var i = 0; i < items.length; i++) ...[
                if (i > 0)
                  const Divider(height: 1, indent: 64, color: AppColors.line),
                InkWell(
                  onTap: () => onBuka(items[i].path),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(minHeight: 56),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 8,
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 36,
                            height: 36,
                            decoration: BoxDecoration(
                              color: AppColors.brand50,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            alignment: Alignment.center,
                            child: Image.asset(
                              'assets/icons/${items[i].ikon}.png',
                              width: 22,
                              height: 22,
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(items[i].judul, style: AppText.label),
                                if (items[i].ket != null)
                                  Text(items[i].ket!, style: AppText.caption),
                              ],
                            ),
                          ),
                          if (items[i].lencana > 0) ...[
                            StatusChip(
                              '${items[i].lencana} baru',
                              tone: ChipTone.gold,
                            ),
                            const SizedBox(width: 6),
                          ],
                          const Icon(
                            Icons.chevron_right,
                            color: AppColors.inkFaint,
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

/// Halaman hasil (berhasil/gagal) dengan ikon besar di lingkaran.
class ResultHero extends StatelessWidget {
  const ResultHero({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.success = true,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final bool success;

  @override
  Widget build(BuildContext context) {
    final strong = success ? AppColors.ok600 : AppColors.alpa;
    final tint = success ? AppColors.ok100 : AppColors.alpaTint;
    return Column(
      children: [
        const SizedBox(height: 24),
        Container(
          width: 112,
          height: 112,
          decoration: BoxDecoration(color: tint, shape: BoxShape.circle),
          alignment: Alignment.center,
          child: Container(
            width: 76,
            height: 76,
            decoration: BoxDecoration(color: strong, shape: BoxShape.circle),
            child: Icon(icon, color: Colors.white, size: 40),
          ),
        ),
        const SizedBox(height: 20),
        Text(title, style: AppText.title, textAlign: TextAlign.center),
        if (subtitle != null) ...[
          const SizedBox(height: 6),
          Text(
            subtitle!,
            style: AppText.bodySmall,
            textAlign: TextAlign.center,
          ),
        ],
        const SizedBox(height: 20),
      ],
    );
  }
}

/// Tabel ringkas label : nilai.
class InfoTable extends StatelessWidget {
  const InfoTable(this.rows, {super.key});
  final List<(String, Widget)> rows;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        children: [
          for (var i = 0; i < rows.length; i++)
            Container(
              padding: const EdgeInsets.symmetric(vertical: 14),
              decoration: BoxDecoration(
                border: i == rows.length - 1
                    ? null
                    : const Border(bottom: BorderSide(color: AppColors.line)),
              ),
              child: Row(
                children: [
                  Expanded(child: Text(rows[i].$1, style: AppText.bodySmall)),
                  rows[i].$2,
                ],
              ),
            ),
        ],
      ),
    );
  }
}

/// Memuat data async dengan tampilan loading, error + coba lagi, dan pull-to-refresh.
class AsyncBody<T> extends StatefulWidget {
  const AsyncBody({super.key, required this.load, required this.builder});

  final Future<T> Function() load;
  final Widget Function(
    BuildContext context,
    T data,
    Future<void> Function() reload,
  )
  builder;

  @override
  State<AsyncBody<T>> createState() => _AsyncBodyState<T>();
}

class _AsyncBodyState<T> extends State<AsyncBody<T>> {
  late Future<T> _future = widget.load();

  Future<void> _reload() async {
    final f = widget.load();
    setState(() => _future = f);
    await f.catchError((_) => null as T);
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<T>(
      future: _future,
      builder: (context, snap) {
        if (snap.connectionState != ConnectionState.done) {
          return const Padding(
            padding: EdgeInsets.only(top: 80),
            child: Center(child: CircularProgressIndicator()),
          );
        }
        if (snap.hasError) {
          return ErrorPanel(
            message: snap.error is ApiException
                ? snap.error.toString()
                : 'Terjadi kesalahan. Coba lagi.',
            onRetry: _reload,
          );
        }
        return RefreshIndicator(
          onRefresh: _reload,
          child: widget.builder(context, snap.data as T, _reload),
        );
      },
    );
  }
}

class ErrorPanel extends StatelessWidget {
  const ErrorPanel({super.key, required this.message, this.onRetry});
  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 60),
      child: Column(
        children: [
          const Icon(
            Icons.cloud_off_rounded,
            size: 48,
            color: AppColors.inkFaint,
          ),
          const SizedBox(height: 12),
          Text(message, style: AppText.body, textAlign: TextAlign.center),
          if (onRetry != null) ...[
            const SizedBox(height: 16),
            SizedBox(
              width: 160,
              child: SecondaryButton(label: 'Coba lagi', onPressed: onRetry),
            ),
          ],
        ],
      ),
    );
  }
}

class EmptyPanel extends StatelessWidget {
  const EmptyPanel({super.key, required this.icon, required this.message});
  final IconData icon;
  final String message;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 48, horizontal: 16),
    child: Column(
      children: [
        Icon(icon, size: 48, color: AppColors.inkFaint),
        const SizedBox(height: 12),
        Text(message, style: AppText.bodySmall, textAlign: TextAlign.center),
      ],
    ),
  );
}

void showMessage(BuildContext context, String message, {bool error = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: error ? AppColors.alpa : AppColors.ink,
      ),
    );
}

/// Baris menu profil: ikon dalam kotak biru muda, label, dan panah.
class MenuRow extends StatelessWidget {
  const MenuRow({
    super.key,
    required this.icon,
    required this.label,
    required this.onTap,
  });
  final String icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => ListTile(
    onTap: onTap,
    leading: Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: AppColors.brand50,
        borderRadius: BorderRadius.circular(10),
      ),
      alignment: Alignment.center,
      child: Image.asset('assets/icons/$icon.png', width: 28),
    ),
    title: Text(label, style: AppText.label),
    trailing: const Icon(Icons.chevron_right, color: AppColors.inkMuted),
  );
}

/// Ikon menu berwarna dalam badge lingkaran putih (tile dashboard guru, kepala sekolah, wali).
/// Berkas PNG 128 px memuat ikon 96 px + ruang tepi untuk bayangan, jadi ikon digambar sedikit lebih besar dari lingkaran dalamnya.
class IkonMenu extends StatelessWidget {
  const IkonMenu(this.nama, {super.key, this.ukuran = 42});
  final String nama;
  final double ukuran;

  @override
  Widget build(BuildContext context) {
    final gambar = ukuran * 0.76;
    return Container(
      width: ukuran,
      height: ukuran,
      decoration: BoxDecoration(
        color: AppColors.surface,
        shape: BoxShape.circle,
        border: Border.all(color: AppColors.line),
        boxShadow: const [
          BoxShadow(
            color: Color(0x1F14284B),
            blurRadius: 4,
            offset: Offset(0, 2),
          ),
        ],
      ),
      alignment: Alignment.center,
      child: Image.asset(
        'assets/icons/$nama.png',
        width: gambar,
        height: gambar,
        filterQuality: FilterQuality.medium,
      ),
    );
  }
}

/// Tanda bukti di riwayat dan rekap absen guru: lokasi palsu (merah) dan koreksi atas absen
/// sendiri (kuning). Keduanya tercatat permanen di server dan tidak bisa dihapus dari aplikasi.
class TandaAbsen extends StatelessWidget {
  const TandaAbsen({super.key, this.lokasiPalsu, this.koreksiMandiri});

  /// Teks pelengkap, mis. jam "07:05" atau "2 hari"; null = tidak ada tanda.
  final String? lokasiPalsu;
  final String? koreksiMandiri;

  @override
  Widget build(BuildContext context) {
    if (lokasiPalsu == null && koreksiMandiri == null) {
      return const SizedBox.shrink();
    }
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: Wrap(
        spacing: 6,
        runSpacing: 4,
        children: [
          if (lokasiPalsu != null)
            StatusChip('Lokasi palsu $lokasiPalsu'.trim(), tone: ChipTone.alpa),
          if (koreksiMandiri != null)
            StatusChip(
              'Koreksi mandiri $koreksiMandiri'.trim(),
              tone: ChipTone.gold,
            ),
        ],
      ),
    );
  }
}
