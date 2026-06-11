import 'package:flutter/material.dart';

/// Palet warna brand Kafe Satu Per Dua Kopitiam.
///
/// Tema mengambil nuansa kopi: brown sebagai primary, tan/gold sebagai
/// aksen, dan cream sebagai latar. Konsistensi ini dipakai di seluruh
/// layar mobile.
class AppColors {
  AppColors._();

  // Primary palette (coffee brown)
  static const Color primary = Color(0xFF6F4E37);
  static const Color primaryDark = Color(0xFF4A2C1F);
  static const Color primaryLight = Color(0xFF8B6B4F);

  // Secondary palette (tan / gold)
  static const Color secondary = Color(0xFFC9A86A);
  static const Color secondaryDark = Color(0xFFA88848);

  // Surface & background
  static const Color background = Color(0xFFFFF8F0); // cream
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceVariant = Color(0xFFF5EDE3);

  // Text
  static const Color textPrimary = Color(0xFF2D1B0E);
  static const Color textSecondary = Color(0xFF6E5A4C);
  static const Color textOnPrimary = Color(0xFFFFFFFF);

  // Status colors
  static const Color success = Color(0xFF2E7D32);
  static const Color warning = Color(0xFFE6A23C);
  static const Color error = Color(0xFFC0392B);
  static const Color info = Color(0xFF2980B9);

  // Borders & dividers
  static const Color border = Color(0xFFE0D6CA);
  static const Color divider = Color(0xFFEDE5DA);
}
