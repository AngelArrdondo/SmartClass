import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';
import 'home_screen.dart'; // La crearemos en el siguiente paso
import 'theme.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passController = TextEditingController();
  bool _isLoading = false;

  void _doLogin() async {
    setState(() => _isLoading = true);

    final res = await ApiService.login(
      _emailController.text.trim(),
      _passController.text.trim(),
    );

    setState(() => _isLoading = false);

    if (res['success'] == true) {
      // Guardar sesión
      final prefs = await SharedPreferences.getInstance();
      await prefs.setInt('perfil_id', res['perfil_id']);
      await prefs.setString('rol', res['rol']);
      await prefs.setString('nombre', res['nombre']);

      if (!mounted) return;
      
      // Ir al Home
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Error'), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    // El Scaffold usa el color de fondo #CCD9E5 definido en SmartTheme
    return Scaffold(
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 360), // Máx-width del CSS
            child: Container(
              // Contenedor principal 'login-wrapper' (blanco, sombra)
              decoration: BoxDecoration(
                color: SmartTheme.white,
                borderRadius: BorderRadius.circular(15),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.12),
                    blurRadius: 14,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              padding: const EdgeInsets.all(16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Contenedor azul 'login-container'
                  Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: const Color(0xFF0051A8), // Azul del CSS #0051a8
                      borderRadius: BorderRadius.circular(35),
                    ),
                    padding: const EdgeInsets.fromLTRB(18, 30, 18, 26),
                    child: Column(
                      children: [
                        // LOGO (Adaptando el diseño IS Integral Solutions)
                        Row(
                          mainAxisAlignment: MainAxisAlignment.start,
                          children: [
                            Container(
                              width: 45, // Ajustado para móvil
                              height: 45,
                              decoration: BoxDecoration(
                                color: SmartTheme.orange, // Simula el fondo naranja
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Center(
                                child: Text("IS", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20, color: SmartTheme.blue600)),
                              ),
                            ),
                            const SizedBox(width: 12),
                            const Text(
                              "INTEGRAL\nSOLUTIONS",
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: SmartTheme.white, height: 1.1),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        const Text(
                          "Portal Académico",
                          style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: SmartTheme.white),
                        ),
                        const Text(
                          "Acceso exclusivo para Docentes y Estudiantes.",
                          style: TextStyle(fontSize: 14, color: SmartTheme.white),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 20),
                      ],
                    ),
                  ),
                  
                  const SizedBox(height: 18),
                  
                  // Formulario de Login
                  const Text(
                    "Iniciar Sesión",
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: SmartTheme.blue600),
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    "Ingresa tus credenciales",
                    style: TextStyle(fontSize: 14, color: Colors.grey),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 18),

                  // INPUT CORREO
                  TextField(
                    controller: _emailController,
                    decoration: const InputDecoration(
                      labelText: "Correo Institucional",
                      prefixIcon: Icon(Icons.email_outlined), // El color lo toma del InputDecorationTheme
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  // INPUT CONTRASEÑA
                  TextField(
                    controller: _passController,
                    obscureText: true,
                    decoration: const InputDecoration(
                      labelText: "Contraseña",
                      prefixIcon: Icon(Icons.lock_outline), // El color lo toma del InputDecorationTheme
                    ),
                  ),
                  const SizedBox(height: 24),
                  
                  // BOTÓN INGRESAR
                  SizedBox(
                    width: double.infinity, // Ocupa todo el ancho del contenedor padre
                    height: 50,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0051A8), // Azul del CSS
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)), // Ajuste de borde
                        side: const BorderSide(color: SmartTheme.white, width: 2), // Borde blanco del CSS
                        elevation: 0,
                      ),
                      onPressed: _isLoading ? null : _doLogin,
                      child: _isLoading
                          ? const CircularProgressIndicator(color: Colors.white)
                          : const Text(
                              "INGRESAR",
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                    ),
                  ),
                  
                  const SizedBox(height: 18),

                  // FOOTER
                  const Text(
                    "¿Olvidaste tu contraseña? Contacta a tu coordinador.",
                    style: TextStyle(fontSize: 12, color: Colors.grey),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  GestureDetector(
                    onTap: () { 
                       // Aquí podrías implementar la navegación para volver al inicio
                    },
                    child: const Text(
                      "Volver al inicio",
                      style: TextStyle(fontSize: 12, color: Colors.grey, decoration: TextDecoration.underline),
                    ),
                  ),
                  const SizedBox(height: 10),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}