import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// Token warna dari variabel Figma "SisforSinta".
class AppColors {
  static const brand900 = Color(0xFF0D3F63);
  static const brand800 = Color(0xFF15649C);
  static const brand700 = Color(0xFF1E7FC4);
  static const brand200 = Color(0xFFBBDDF3);
  static const brand100 = Color(0xFFDCEEFA);
  static const brand50 = Color(0xFFEFF7FD);
  static const ink = Color(0xFF111827);
  static const inkMuted = Color(0xFF6B7280);
  static const inkFaint = Color(0xFF9CA3AF);
  static const surface = Color(0xFFFFFFFF);
  static const line = Color(0xFFE5E7EB);
  static const field = Color(0xFFF3F4F6);
  static const ok700 = Color(0xFF15803D);
  static const ok600 = Color(0xFF16A34A);
  static const ok100 = Color(0xFFDCFCE7);
  static const gold = Color(0xFFC8962E);
  static const goldTint = Color(0xFFFEF3C7);
  static const sakit = Color(0xFF6D4FC2);
  static const sakitTint = Color(0xFFEDE9FE);
  static const alpa = Color(0xFFB3392F);
  static const alpaTint = Color(0xFFFDE2E1);
  static const dark = Color(0xFF111827);

  /// Latar halaman: putih ke biru muda.
  static const pageGradient = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFFFFFFFF), Color(0xFFD6EAF8)],
  );
}

/// Gaya teks dari Figma (Inter).
class AppText {
  static TextStyle _inter(double size, FontWeight w, double height, [Color c = AppColors.ink]) =>
      GoogleFonts.inter(fontSize: size, fontWeight: w, height: height / size, color: c);

  static final title = _inter(20, FontWeight.w700, 26);
  static final heading = _inter(16, FontWeight.w700, 22);
  static final label = _inter(14, FontWeight.w600, 20);
  static final body = _inter(14, FontWeight.w400, 20);
  static final bodySmall = _inter(13, FontWeight.w400, 18, AppColors.inkMuted);
  static final caption = _inter(12, FontWeight.w500, 16, AppColors.inkMuted);
  static final captionBold = _inter(12, FontWeight.w600, 16);
  static final overline = GoogleFonts.inter(fontSize: 11, fontWeight: FontWeight.w600, letterSpacing: 0.8, color: AppColors.inkMuted);
  static final number = _inter(40, FontWeight.w800, 44, AppColors.ok700);
}

ThemeData buildTheme() {
  final base = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(seedColor: AppColors.brand700, primary: AppColors.brand700, surface: AppColors.surface),
    scaffoldBackgroundColor: Colors.transparent,
    fontFamily: GoogleFonts.inter().fontFamily,
  );
  return base.copyWith(
    snackBarTheme: const SnackBarThemeData(behavior: SnackBarBehavior.floating),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.field,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.line)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.line)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.brand700, width: 1.5)),
      hintStyle: AppText.body.copyWith(color: AppColors.inkFaint),
    ),
  );
}
