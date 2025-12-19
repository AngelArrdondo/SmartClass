import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';
import 'theme.dart';

class HorarioScreen extends StatefulWidget {
  const HorarioScreen({super.key});

  @override
  State<HorarioScreen> createState() => _HorarioScreenState();
}

class _HorarioScreenState extends State<HorarioScreen> {
  bool _isLoading = true;
  String _rol = "";

  // Mapa para almacenar el horario agrupado por día
  Map<String, List<dynamic>> _horarioAgrupado = {};

  // Días de la semana en orden correcto
  final List<String> _diasSemana = [
    'Lunes',
    'Martes',
    'Miercoles',
    'Jueves',
    'Viernes'
  ];

  @override
  void initState() {
    super.initState();
    _loadHorario();
  }

  Future<void> _loadHorario() async {
    final prefs = await SharedPreferences.getInstance();
    final perfilId = prefs.getInt('perfil_id');
    final rol = prefs.getString('rol');

    if (perfilId != null && rol != null) {
      final res = await ApiService.getHorario(perfilId, rol);
      if (mounted) {
        final List<dynamic> horarioCompleto = res['data'] ?? [];
        _rol = rol;
        _horarioAgrupado = _agruparPorDia(horarioCompleto);
        _isLoading = false;
        setState(() {});
      }
    } else {
      _isLoading = false;
      setState(() {});
    }
  }

  // Agrupar horario por día
  Map<String, List<dynamic>> _agruparPorDia(List<dynamic> horario) {
    Map<String, List<dynamic>> agrupado = {};
    for (var dia in _diasSemana) {
      agrupado[dia] = [];
    }

    for (var clase in horario) {
      final dia = clase['dia_semana'];
      if (agrupado.containsKey(dia)) {
        agrupado[dia]!.add(clase);
      }
    }

    return agrupado;
  }

  // Tarjeta de clase
  Widget _buildClaseCard(dynamic clase) {
    final String inicio = clase['hora_inicio'].substring(0, 5);
    final String fin = clase['hora_fin'].substring(0, 5);

    final List<Color> colores = [
      Colors.blue.shade700,
      Colors.green.shade700,
      Colors.red.shade700,
      Colors.yellow.shade700,
      Colors.cyan.shade700,
      Colors.purple.shade700,
    ];

    final int colorIndex = clase['materia'].hashCode % colores.length;
    final Color colorBorde = colores[colorIndex];

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: SmartTheme.white,
        borderRadius: BorderRadius.circular(8),
        border: Border(left: BorderSide(color: colorBorde, width: 4)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 3,
            offset: const Offset(0, 2),
          )
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              "$inicio - $fin",
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: Colors.grey.shade600,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              clase['materia'],
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 14,
                color: Colors.black87,
              ),
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                Icon(Icons.location_on, size: 12, color: Colors.grey.shade600),
                const SizedBox(width: 4),
                Text(
                  clase['salon'],
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade800),
                ),
              ],
            ),
            Row(
              children: [
                if (_rol == 'alumno') ...[
                  Icon(Icons.person, size: 12, color: SmartTheme.blue400),
                  const SizedBox(width: 4),
                  Text(
                    "Prof. ${clase['nombre_profesor']} ${clase['apellido_profesor']?.substring(0, 1) ?? ''}.",
                    style: TextStyle(fontSize: 12, color: SmartTheme.blue400),
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
                if (_rol == 'profesor') ...[
                  Icon(Icons.group, size: 12, color: SmartTheme.orange),
                  const SizedBox(width: 4),
                  Text(
                    clase['grupo'] ?? 'Grupo: ---',
                    style: TextStyle(
                      fontSize: 12,
                      color: SmartTheme.orange,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: SmartTheme.blue600,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: SmartTheme.white),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: const Text(
          "Horario de Clases",
          style: TextStyle(color: SmartTheme.white),
        ),
        elevation: 0,
      ),

      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Card(
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                clipBehavior: Clip.antiAlias,
                child: Column(
                  children: [
                    // SI NO HAY HORARIO
                    if (_horarioAgrupado.values.every((list) => list.isEmpty))
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.symmetric(vertical: 80),
                          child: Column(
                            children: [
                              Icon(Icons.warning_amber_rounded,
                                  color: Colors.orange, size: 60),
                              SizedBox(height: 16),
                              Text(
                                "Sin Horario Asignado",
                                style: TextStyle(
                                    fontSize: 18, fontWeight: FontWeight.bold),
                              ),
                              Text(
                                "Contacta a la administración para la asignación de grupo.",
                                style: TextStyle(color: Colors.grey),
                              ),
                            ],
                          ),
                        ),
                      )
                    else
                      // TABLA COMPLETA
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: DataTable(
                          columnSpacing: 10,
                          dataRowMinHeight: 180,
                          dataRowMaxHeight: double.infinity,
                          headingRowColor: MaterialStateProperty.resolveWith(
                              (states) => SmartTheme.blue600),
                          border: TableBorder.all(
                              color: Colors.grey.shade200, width: 1),

                          // ENCABEZADOS
                          columns: _diasSemana
                              .map(
                                (dia) => DataColumn(
                                  label: Container(
                                    alignment: Alignment.center,
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 10, vertical: 8),
                                    child: Text(
                                      dia.toUpperCase(),
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        color: SmartTheme.white,
                                        fontSize: 14,
                                      ),
                                    ),
                                  ),
                                ),
                              )
                              .toList(),

                          // UNA SOLA FILA
                          rows: [
                            DataRow(
                              cells: _diasSemana.map((dia) {
                                final clases = _horarioAgrupado[dia] ?? [];
                                return DataCell(
                                  SingleChildScrollView(
                                    child: Container(
                                      constraints: const BoxConstraints(
                                          minWidth: 150),
                                      padding: const EdgeInsets.symmetric(
                                          vertical: 10, horizontal: 8),
                                      child: clases.isEmpty
                                          ? const Padding(
                                              padding: EdgeInsets.all(20),
                                              child: Text(
                                                "Libre",
                                                style: TextStyle(
                                                    color: Colors.grey),
                                              ),
                                            )
                                          : Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.stretch,
                                              children: clases
                                                  .map(
                                                      (clase) =>
                                                          _buildClaseCard(
                                                              clase),
                                                    )
                                                  .toList(),
                                            ),
                                    ),
                                  ),
                                );
                              }).toList(),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
            ),

      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
      floatingActionButton: SizedBox(
        width: 240,
        child: FloatingActionButton.extended(
          onPressed: () {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text(
                  'Función de Imprimir/Exportar no disponible en esta simulación móvil.',
                ),
              ),
            );
          },
          label: const Text('Imprimir Horario'),
          icon: const Icon(Icons.print),
          backgroundColor: SmartTheme.blue400,
          foregroundColor: SmartTheme.white,
        ),
      ),
    );
  }
}
