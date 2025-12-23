import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';

class ApiService {
  // ==============================
  // CONFIGURACIÓN DE IP
  // ==============================
  static String get baseUrl {
    if (kIsWeb) return "http://localhost:8000";
    return "http://192.168.0.6:8000"; // TU FASTAPI EN SAME WIFI
  }

  // ==============================
  // LOGIN
  // ==============================
  static Future<Map<String, dynamic>> login(String email, String password) async {
    final url = Uri.parse("$baseUrl/api/login");

    try {
      final response = await http.post(
        url,
        headers: {"Content-Type": "application/json"},
        body: jsonEncode({"email": email, "password": password}),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return data;
      }

      return {"success": false, "message": data["detail"] ?? "Error"};
    } catch (e) {
      return {"success": false, "message": "Error de conexión: $e"};
    }
  }

  // ==============================
  // HORARIO
  // ==============================
  static Future<Map<String, dynamic>> getHorario(int perfilId, String rol) async {
    final url = Uri.parse("$baseUrl/api/horario?id=$perfilId&rol=$rol");

    try {
      final response = await http.get(url);

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }

      return {"success": false, "message": "Error al obtener horario"};
    } catch (e) {
      return {"success": false, "message": "Error: $e"};
    }
  }
  // ==============================
  // DATOS DEL ALUMNO
  // ==============================
  static Future<Map<String, dynamic>> getAlumnoData(int perfilId) async {
    final url = Uri.parse("$baseUrl/api/alumnos/$perfilId");

    try {
      final response = await http.get(url);

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }

      return {"success": false, "message": "Error al obtener datos del alumno"};
    } catch (e) {
      return {"success": false, "message": "Error: $e"};
    }
  }
}
