import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';
import 'login_screen.dart';
import 'theme.dart';
import 'horario_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  String _nombre = "";
  String _rol = "";
  String _grupoInfo = "";
  String _matricula = "";
  List<dynamic> _horarioHoy = []; // Usamos una lista nueva para el horario de hoy
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
    
    // Obtenemos el día de hoy en español para simular la lógica del PHP
    final String diaHoy = _getSpanishDay(DateTime.now().weekday);

    if (perfilId != null && rol != null) {
      // Llamar a la API para obtener TODO el horario
      // NOTA: La API devuelve el HORARIO COMPLETO, no solo el de hoy.
      // Filtraremos en Flutter si es necesario o ajustamos la llamada si el backend lo permite.
      final res = await ApiService.getHorario(perfilId, rol); 
      
      // Simulación de datos extra del alumno
      if (rol == 'alumno') {
        final alumnoRes = await ApiService.getAlumnoData(perfilId);

        if (alumnoRes['success'] == true && alumnoRes['data'] != null) {
          _grupoInfo = alumnoRes['data']['grupo_info'] ?? '';
          _matricula = alumnoRes['data']['matricula'] ?? '';
        }
      }

      if (mounted) {
        if (res['success'] != true || res['data'] == null) {
          setState(() {
            _nombre = nombre ?? "Usuario";
            _rol = rol;
            _horarioHoy = [];
            _isLoading = false;
          });
          return;
        }

        final List<dynamic> horarioCompleto = List.from(res['data']);
        final List<dynamic> horarioHoyFiltrado = horarioCompleto.where((item) {
          final diaApi = item['dia_semana']?.toString().toLowerCase().trim();
          final diaLocal = diaHoy.toLowerCase().trim();
          return diaApi == diaLocal;
        }).toList();
                
        setState(() {
          _nombre = nombre ?? "Usuario";
          _rol = rol;
          _horarioHoy = horarioHoyFiltrado;
          _isLoading = false;
        });
      }
    } else {
      if (!mounted) return;
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (route) => false,
      );
    }
  }
  
  // Función auxiliar para obtener el día en español, coincidiendo con la base de datos
  String _getSpanishDay(int weekday) {
    switch (weekday) {
      case 1: return 'Lunes';
      case 2: return 'Martes';
      case 3: return 'Miercoles'; // Nota: La base de datos usa "Miercoles" sin tilde.
      case 4: return 'Jueves';
      case 5: return 'Viernes';
      case 6: return 'Sabado';
      case 7: return 'Domingo';
      default: return '';
    }
  }

  void _logout() async {
    final prefs = await SharedPreferences.getInstance();

    // ❌ NO BORRES TODO
    // await prefs.clear();

    // ✅ SOLO BORRA LA SESIÓN
    await prefs.remove('perfil_id');
    await prefs.remove('rol');
    await prefs.remove('nombre');

    if (!mounted) return;
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }


  // Widget para el botón grande con efecto hover
  Widget _buildPanelButton({
    required IconData icon,
    required String text,
    required Color iconColor,
    VoidCallback? onTap,
  }) {
    return Card(
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 0,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        // Simulación del estilo de botón blanco con sombra del CSS
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 16),
          decoration: BoxDecoration(
            color: SmartTheme.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.grey.shade200),
            boxShadow: [
              BoxShadow(
                color: SmartTheme.blue600.withOpacity(0.05),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Row(
            children: [
              Icon(icon, color: iconColor, size: 24),
              const SizedBox(width: 12),
              Text(
                text,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
              ),
              const Spacer(),
              const Icon(Icons.arrow_forward_ios, size: 16, color: Colors.grey),
            ],
          ),
        ),
      ),
    );
  }
  
  // Widget para la tarjeta de clase, adaptado al timeline
  Widget _buildClassCard(dynamic item) {
    // Simulamos la línea de tiempo con un Container decorado a la izquierda
    final String horaInicio = item['hora_inicio'].substring(0, 5); // 08:00
    final String horaFin = item['hora_fin'].substring(0, 5); // 09:30
    
    // Si la lista de horario de hoy solo tiene un elemento, no mostramos la línea.
    final bool isLast = _horarioHoy.indexOf(item) == _horarioHoy.length - 1;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // 1. TIMELINE Y HORA
        SizedBox(
          width: 70, // Espacio fijo para la hora
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                horaInicio,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: SmartTheme.blue600),
              ),
              Text(
                "- $horaFin",
                style: const TextStyle(color: Colors.grey, fontSize: 12),
              ),
            ],
          ),
        ),
        
        // 2. INDICADOR
        Column(
          children: [
            Container(
              width: 10,
              height: 10,
              decoration: BoxDecoration(
                color: SmartTheme.blue600,
                borderRadius: BorderRadius.circular(5),
              ),
            ),
            // Línea de tiempo vertical (se oculta si es el último)
            Container(
              width: 2,
              height: isLast ? 0 : 80, // Altura que permite el padding inferior
              color: SmartTheme.blue600.withOpacity(0.3),
            ),
          ],
        ),
        const SizedBox(width: 15),

        // 3. DETALLES DE LA CLASE (similar al Card, pero como parte de la lista)
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(bottom: 20), // Padding inferior para la separación
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['materia'],
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.black87),
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                // INFO SALÓN Y PROFESOR/GRUPO
                Row(
                  children: [
                    Icon(Icons.location_on, size: 14, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Text("Aula ${item['salon']}", style: TextStyle(color: Colors.grey[800], fontSize: 13)),
                  ],
                ),
                const SizedBox(height: 4),
                // INFO ROL
                Row(
                  children: [
                    // Si soy Alumno, veo el nombre del Profe
                    if (_rol == 'alumno') ...[
                      const Icon(Icons.person, size: 14, color: SmartTheme.blue400),
                      const SizedBox(width: 4),
                      Text(
                        "Prof. ${item['nombre_profesor']} ${item['apellido_profesor'] ?? ''}",
                        style: const TextStyle(color: SmartTheme.blue400, fontSize: 12),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    // Si soy Profe, veo el Grupo
                    if (_rol == 'profesor') ...[
                      const Icon(Icons.group, size: 14, color: SmartTheme.orange),
                      const SizedBox(width: 4),
                      Text("Grupo ${item['grupo']}", style: const TextStyle(color: SmartTheme.orange, fontWeight: FontWeight.bold, fontSize: 12)),
                    ]
                  ],
                )
              ],
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : CustomScrollView(
              slivers: [
                // 1. BARRA DE NAVEGACIÓN (Simulación de la navbar Bootstrap)
                SliverAppBar(
                  expandedHeight: 0,
                  floating: true,
                  pinned: true,
                  toolbarHeight: 56, // Altura estándar de AppBar
                  backgroundColor: SmartTheme.blue600,
                  elevation: 4,
                  automaticallyImplyLeading: false, // Quitamos la flecha de back por defecto
                  title: Row(
                    children: [
                      const Icon(Icons.school, color: SmartTheme.orange, size: 24),
                      const SizedBox(width: 8),
                      const Text("SmartClass", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: SmartTheme.white)),
                      const Spacer(),
                      // USUARIO Y BOTÓN DE SALIR
                      Text(
                        "Hola, $_nombre",
                        style: TextStyle(color: SmartTheme.white.withOpacity(0.8), fontSize: 13),
                      ),
                      IconButton(
                        icon: const Icon(Icons.exit_to_app, color: SmartTheme.orange),
                        onPressed: _logout,
                      ),
                    ],
                  ),
                ),
                
                // 2. CONTENIDO PRINCIPAL (py-5)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // A. TARJETA DE BIENVENIDA (row mb-4)
                        Card(
                          margin: const EdgeInsets.only(bottom: 24),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          elevation: 0,
                          child: Padding(
                            padding: const EdgeInsets.all(20),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        "¡Bienvenido de nuevo!",
                                        style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: SmartTheme.blue600),
                                      ),
                                      Text(
                                        "Portal del ${_rol == 'alumno' ? 'Estudiante' : 'Profesor'}",
                                        style: const TextStyle(color: Colors.grey, fontSize: 14),
                                      ),
                                    ],
                                  ),
                                ),
                                if (_rol == 'alumno')
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.end,
                                    children: [
                                      const Text("Matrícula", style: TextStyle(color: Colors.grey, fontSize: 10, fontWeight: FontWeight.bold)),
                                      Text(_matricula, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87)),
                                    ],
                                  ),
                              ],
                            ),
                          ),
                        ),

                        // B. PANELES LATERALES (row g-4)
                        LayoutBuilder(
                          builder: (context, constraints) {
                            // Si el ancho es grande, usamos la disposición de dos columnas (tablet/desktop)
                            if (constraints.maxWidth > 700) {
                              return Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(flex: 4, child: _buildLeftPanel()), // lg-4
                                  const SizedBox(width: 16),
                                  Expanded(flex: 8, child: _buildRightPanel()), // lg-8
                                ],
                              );
                            } else {
                              // En móvil, apilamos los paneles
                              return Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  _buildLeftPanel(),
                                  const SizedBox(height: 24),
                                  _buildRightPanel(),
                                ],
                              );
                            }
                          },
                        ),
                      ],
                    ),
                  ),
                ),
                
                // 3. FOOTER
                const SliverToBoxAdapter(
                  child: Padding(
                    padding: EdgeInsets.only(top: 24, bottom: 24),
                    child: Center(
                      child: Text(
                        "© 2025 SmartClass - Portal",
                        style: TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                    ),
                  ),
                ),
              ],
            ),
    );
  }
  
  Widget _buildLeftPanel() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Mi Grupo (Tarjeta de información del grupo)
        Card(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          margin: const EdgeInsets.only(bottom: 24),
          elevation: 0,
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                Row(
                  children: [
                    Icon(Icons.people_alt, color: SmartTheme.blue600, size: 20),
                    const SizedBox(width: 8),
                    Text("Mi Grupo", style: TextStyle(fontWeight: FontWeight.bold, color: SmartTheme.blue600)),
                  ],
                ),
                const Divider(height: 20),
                if (_rol == 'alumno') ...[
                  // Simulación de datos de Grupo (como en PHP)
                  Text(
                    _grupoInfo.isNotEmpty ? _grupoInfo : "---",
                    style: const TextStyle(
                      fontSize: 36,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                  ),
                  Container(
                    margin: const EdgeInsets.only(top: 8),
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: SmartTheme.blue600.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: SmartTheme.blue600.withOpacity(0.5)),
                    ),
                    child: Text(
                      _grupoInfo,
                      style: TextStyle(color: SmartTheme.blue600, fontWeight: FontWeight.bold, fontSize: 12),
                    ),
                  ),
                ] else if (_rol == 'profesor') ...[
                   // Si es profesor, mostramos el rol o info relevante
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 20.0),
                      child: Text(
                        "Docente Activo",
                        style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: SmartTheme.orange),
                      ),
                    ),
                ] else ...[
                  const Padding(
                    padding: EdgeInsets.all(20.0),
                    child: Text("Información no asignada", style: TextStyle(color: Colors.grey)),
                  )
                ]
              ],
            ),
          ),
        ),

        // Botones de Navegación (d-grid gap-3)
        _buildPanelButton(
          icon: Icons.score,
          text: "Ver Calificaciones",
          iconColor: SmartTheme.blue600,
          onTap: () { /* Navegar a Calificaciones */ },
        ),
        const SizedBox(height: 12),
        _buildPanelButton(
          icon: Icons.calendar_today,
          text: "Horario Completo",
          iconColor: Colors.green, // Simulando el color success de Bootstrap
          onTap: () { 
            Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => const HorarioScreen()),
          ); 
          },
        ),
      ],
    );
  }

  Widget _buildRightPanel() {
    // Panel de Clases de Hoy (col-lg-8)
    final String diaHoy = _getSpanishDay(DateTime.now().weekday);
    final String fechaHoy = "${DateTime.now().day}/${DateTime.now().month}";

    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      elevation: 0,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Icon(Icons.access_time, color: SmartTheme.blue600, size: 20),
                    const SizedBox(width: 8),
                    Text(
                      "Clases de Hoy",
                      style: TextStyle(fontWeight: FontWeight.bold, color: SmartTheme.blue600),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(color: Colors.grey.shade300),
                  ),
                  child: Text("$diaHoy, $fechaHoy", style: const TextStyle(fontSize: 12, color: Colors.black87)),
                ),
              ],
            ),
            const Divider(height: 25),

            // Contenido del Horario
            if (_horarioHoy.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 40.0),
                child: Center(
                  child: Column(
                    children: [
                      Icon(Icons.free_breakfast, size: 60, color: Colors.grey[400]),
                      const SizedBox(height: 16),
                      const Text("¡Día libre!", style: TextStyle(fontSize: 18, color: Colors.grey)),
                      const Text("No hay clases programadas para hoy.", style: TextStyle(fontSize: 14, color: Colors.grey)),
                    ],
                  ),
                ),
              )
            else
              ..._horarioHoy.map((item) => _buildClassCard(item)).toList(),
          ],
        ),
      ),
    );
  }
}