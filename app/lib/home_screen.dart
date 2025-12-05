import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';
import 'login_screen.dart';
import 'theme.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  String _nombre = "";
  String _rol = "";
  List<dynamic> _horario = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    final prefs = await SharedPreferences.getInstance();
    final perfilId = prefs.getInt('perfil_id');
    final rol = prefs.getString('rol');
    final nombre = prefs.getString('nombre');

    if (perfilId != null && rol != null) {
      // Llamar a la API
      final res = await ApiService.getHorario(perfilId, rol);
      if (mounted) {
        setState(() {
          _nombre = nombre ?? "Usuario";
          _rol = rol; // 'profesor' o 'alumno'
          _horario = res['data'] ?? [];
          _isLoading = false;
        });
      }
    } else {
      // Si no hay sesión, salir
      _logout();
    }
  }

  void _logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    if (!mounted) return;
    Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Mi Horario"),
        actions: [
          IconButton(
            icon: const Icon(Icons.exit_to_app),
            onPressed: _logout,
          )
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                // HEADER DE BIENVENIDA
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(20),
                  decoration: const BoxDecoration(
                    color: SmartTheme.blue600,
                    borderRadius: BorderRadius.only(
                      bottomLeft: Radius.circular(20),
                      bottomRight: Radius.circular(20),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        "Hola, $_nombre",
                        style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                      ),
                      Text(
                        _rol.toUpperCase(),
                        style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 14),
                      ),
                    ],
                  ),
                ),

                // LISTA DE HORARIOS
                Expanded(
                  child: _horario.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.event_busy, size: 60, color: Colors.grey[400]),
                              const SizedBox(height: 10),
                              const Text("No hay clases programadas"),
                            ],
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(10),
                          itemCount: _horario.length,
                          itemBuilder: (context, index) {
                            final item = _horario[index];
                            return _buildClassCard(item);
                          },
                        ),
                ),
              ],
            ),
    );
  }

  Widget _buildClassCard(dynamic item) {
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 5),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            // COLUMNA IZQUIERDA: HORARIO Y DÍA
            Column(
              children: [
                Text(
                  item['hora_inicio'],
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: SmartTheme.blue600),
                ),
                Text(
                  item['dia_semana'].toString().substring(0, 3).toUpperCase(),
                  style: const TextStyle(color: Colors.grey, fontWeight: FontWeight.bold, fontSize: 12),
                ),
              ],
            ),
            const SizedBox(width: 15),
            Container(width: 2, height: 50, color: Colors.grey[200]),
            const SizedBox(width: 15),

            // COLUMNA DERECHA: DETALLES
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item['materia'],
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Icon(Icons.room, size: 14, color: Colors.grey[600]),
                      const SizedBox(width: 4),
                      Text(item['salon'], style: TextStyle(color: Colors.grey[800], fontSize: 13)),
                      const Spacer(),
                      
                      // LOGICA VISUAL SEGUN ROL
                      // Si soy Alumno, veo el nombre del Profe
                      if (_rol == 'alumno') ...[
                         const Icon(Icons.person, size: 14, color: SmartTheme.blue400),
                         const SizedBox(width: 4),
                         Expanded(child: Text("${item['nombre_profesor']}", style: const TextStyle(color: SmartTheme.blue400, fontSize: 12), overflow: TextOverflow.ellipsis)),
                      ],
                      
                      // Si soy Profe, veo el Grupo
                      if (_rol == 'profesor') ...[
                         const Icon(Icons.group, size: 14, color: SmartTheme.orange),
                         const SizedBox(width: 4),
                         Text(item['grupo'], style: const TextStyle(color: SmartTheme.orange, fontWeight: FontWeight.bold, fontSize: 12)),
                      ]
                    ],
                  )
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}