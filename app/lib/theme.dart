import 'package:flutter/material.dart';

class SmartTheme {
  // Colores extraídos de tu CSS Web
  static const Color blue600 = Color(0xFF0046A5); // Tu azul oscuro
  static const Color blue400 = Color(0xFF5A8DEE); // Tu azul claro
  static const Color orange = Color(0xFFF7941E);
  static const Color background = Color(0xFFF6FBFF); // Fondo gris azulado suave
  static const Color white = Colors.white;

  static ThemeData get theme {
    return ThemeData(
      primaryColor: blue600,
      scaffoldBackgroundColor: background,
      colorScheme: ColorScheme.fromSwatch().copyWith(
        primary: blue600,
        secondary: blue400,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: blue600,
        elevation: 0,
        centerTitle: true,
        titleTextStyle: TextStyle(fontWeight: FontWeight.bold, fontSize: 20),
      ),
      cardTheme: CardTheme(
        elevation: 4,
        shadowColor: blue600.withOpacity(0.1),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: blue400.withOpacity(0.3)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: blue400.withOpacity(0.3)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: blue600, width: 2),
        ),
      ),
    );
  }
}