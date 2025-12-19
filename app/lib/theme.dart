import 'package:flutter/material.dart';

class SmartTheme {
  // Colores extraídos de tu CSS Web
  static const Color blue600 = Color(0xFF0046A5); // Tu azul oscuro
  static const Color blue400 = Color(0xFF5A8DEE); // Tu azul claro
  static const Color orange = Color(0xFFF7941E);
  // Color del body en el CSS: #ccd9e5
  static const Color background = Color(0xFFCCD9E5); // Fondo gris azulado suave (del CSS)
  static const Color white = Colors.white;

  static ThemeData get theme {
    return ThemeData(
      primaryColor: blue600,
      scaffoldBackgroundColor: background, // Se usa el color #CCD9E5 aquí
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
        // Adaptar el padding de los inputs para que sean más compactos
        contentPadding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(6), // Borde más pequeño
          borderSide: BorderSide(color: blue400.withOpacity(0.3)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(6),
          borderSide: BorderSide(color: blue400.withOpacity(0.3)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(6),
          borderSide: const BorderSide(color: blue600, width: 2),
        ),
        prefixIconColor: const Color(0xFF0051A8), // Color de icono primario (azul fuerte del contenedor)
      ),
    );
  }
}