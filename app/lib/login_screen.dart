import 'package:flutter/material.dart';
import 'package:flutter/services.dart'; 
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';
import 'home_screen.dart';
import 'theme.dart';
import 'package:url_launcher/url_launcher.dart';
import 'dart:html' as html;


class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passController = TextEditingController();
  bool _isLoading = false;
  bool _obscureText = true;

  @override
  void initState() {
    super.initState();
    _leerParametrosWeb();
    _cargarDatosGuardados();
  }

  void _leerParametrosWeb() {
    try {
      final uri = Uri.parse(html.window.location.href);

      final email = uri.queryParameters['email'];
      final rol = uri.queryParameters['rol'];

      if (email != null) {
        _emailController.text = email;
      }

      if (rol != null) {
        debugPrint("Rol recibido desde web: $rol");
      }
    } catch (e) {
      debugPrint("No es Flutter Web");
    }
  }


  Future<void> _cargarDatosGuardados() async {
    final prefs = await SharedPreferences.getInstance();
    final correo = prefs.getString('saved_email');
    final pass = prefs.getString('saved_password');

    if (correo != null && pass != null) {
      setState(() {
        _emailController.text = correo;
        _passController.text = pass;
      });
    }
  }

  void _doLogin() async {
    final email = _emailController.text.trim();
    final pass = _passController.text.trim();

    if (email.isEmpty || pass.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Por favor, llena todos los campos')),
      );
      return;
    }

    setState(() => _isLoading = true);
    final res = await ApiService.login(email, pass);
    setState(() => _isLoading = false);

    if (res['success'] == true) {
      final prefs = await SharedPreferences.getInstance();

      if (prefs.getString('saved_email') != email) {
        await _mostrarDialogoGuardar(email, pass);
      }

      await prefs.setInt('perfil_id', res['perfil_id']);
      await prefs.setString('rol', res['rol']);
      await prefs.setString('nombre', res['nombre']);
      TextInput.finishAutofillContext();
      if (!mounted) return;
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? 'Error'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _mostrarDialogoGuardar(String email, String pass) async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text("¿Recordar cuenta?"),
        content: const Text("¿Deseas guardar tus credenciales para la próxima vez?"),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text("No, gracias"),
          ),
          ElevatedButton(
            onPressed: () async {
              final prefs = await SharedPreferences.getInstance();
              await prefs.setString('saved_email', email);
              await prefs.setString('saved_password', pass);

              Navigator.pop(context); // 🔥 SOLO CIERRA EL DIÁLOGO
            },
            child: const Text("Guardar"),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F2F5),
      body: Center(
        child: SingleChildScrollView(
          child: Container(
            margin: const EdgeInsets.all(24),
            constraints: const BoxConstraints(maxWidth: 850),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 20,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),

              // 🔥 SOLUCIÓN CLAVE PARA FLUTTER WEB
              child: SizedBox(
                height: MediaQuery.of(context).size.height * 0.75,
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // COLUMNA IZQUIERDA
                    Expanded(
                      child: Container(
                        color: const Color(0xFF1A73E8),
                        padding: const EdgeInsets.all(40),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Image.asset(
                              'assets/img/logo.png',
                              width: 150,
                              height: 150,
                              fit: BoxFit.contain,
                              errorBuilder: (_, __, ___) =>
                                  const Icon(Icons.broken_image, size: 60, color: Colors.white),
                            ),
                            const SizedBox(height: 30),
                            const Text(
                              "Portal Académico",
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 28,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 12),
                            const Text(
                              "Acceso exclusivo para Docentes y Estudiantes.",
                              textAlign: TextAlign.center,
                              style: TextStyle(color: Colors.white70),
                            ),
                          ],
                        ),
                      ),
                    ),

                    // COLUMNA DERECHA
                    Expanded(
                      child: SingleChildScrollView(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 50),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Text(
                                "Bienvenido",
                                style: TextStyle(
                                  fontSize: 30,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF1A73E8),
                                ),
                              ),
                              const Text(
                                "Ingresa tus credenciales",
                                style: TextStyle(color: Colors.grey),
                              ),
                              const SizedBox(height: 35),

                              AutofillGroup(
                                child: Column(
                                  children: [
                                    _buildLabel("Correo Institucional"),
                                    TextField(
                                      controller: _emailController,
                                      autofillHints: const [AutofillHints.email],
                                      keyboardType: TextInputType.emailAddress,
                                    ),

                                    const SizedBox(height: 20),

                                    _buildLabel("Contraseña"),
                                    TextField(
                                      controller: _passController,
                                      obscureText: _obscureText,
                                      autofillHints: const [AutofillHints.password],
                                    ),
                                  ],
                                ),
                              ),

                              const SizedBox(height: 30),

                              SizedBox(
                                width: double.infinity,
                                height: 50,
                                child: ElevatedButton(
                                  onPressed: _isLoading ? null : _doLogin,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFF1A73E8), // El mismo azul de la izquierda
                                    foregroundColor: Colors.white,           // Color del texto (blanco)
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(25), // Bordes redondeados como la imagen
                                    ),
                                    elevation: 0, // Sin sombra pesada para un look más moderno/plano
                                  ),
                                  child: _isLoading
                                      ? const SizedBox(
                                          height: 20,
                                          width: 20,
                                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                        )
                                      : const Row(
                                          mainAxisAlignment: MainAxisAlignment.center,
                                          children: [
                                            Text(
                                              "Entrar",
                                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                                            ),
                                            SizedBox(width: 8),
                                            Icon(Icons.arrow_forward, size: 18), // La flechita que aparece en la imagen
                                          ],
                                        ),
                                ),
                              ),
                              const SizedBox(height: 20),

                              const Text(
                                "¿Olvidaste tu contraseña? Contacta a tu coordinador.",
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 12, color: Colors.grey),
                              ),

                              const SizedBox(height: 8),

                              TextButton(
                                onPressed: () async {
                                  const url = 'http://localhost:8080/SmartClass/SmartClassHomePage/SmartClassHomePage/index1.html';
                                  final uri = Uri.parse(url);
                                  if (await canLaunchUrl(uri)) {
                                    await launchUrl(uri, mode: LaunchMode.externalApplication);
                                  }
                                },
                                child: const Text("Volver al inicio"),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),

                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLabel(String text) {
    return Align(
      alignment: Alignment.centerLeft,
      child: Padding(
        padding: const EdgeInsets.only(bottom: 8),
        child: Text(text, style: const TextStyle(fontWeight: FontWeight.bold)),
      ),
    );
  }
}
