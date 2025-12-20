<?php
session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grupo_id'])) {
    $grupo_id = (int)$_POST['grupo_id'];
    $ciclo_id = (int)$_POST['ciclo_id'];

    if (!$ciclo_id) { 
        header("Location: index.php?msg=error_ciclo"); 
        exit; 
    }

    // 0. Obtener información del grupo
    $res_grupo = mysqli_query($conn, "SELECT turno FROM grupos WHERE id = $grupo_id");
    $info_grupo = mysqli_fetch_assoc($res_grupo);
    $turno = $info_grupo['turno'] ?? 'Matutino';

    // 1. Configuración de Bloques
    $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
    $bloques_pool = ($turno == 'Vespertino') 
        ? [
            ['ini' => '14:00:00', 'fin' => '15:00:00'], ['ini' => '15:00:00', 'fin' => '16:00:00'],
            ['ini' => '16:00:00', 'fin' => '17:00:00'], ['ini' => '18:00:00', 'fin' => '19:00:00'],
            ['ini' => '19:00:00', 'fin' => '20:00:00'], ['ini' => '20:00:00', 'fin' => '21:00:00']
          ]
        : [
            ['ini' => '07:00:00', 'fin' => '08:00:00'], ['ini' => '08:00:00', 'fin' => '09:00:00'],
            ['ini' => '09:00:00', 'fin' => '10:00:00'], ['ini' => '11:00:00', 'fin' => '12:00:00'],
            ['ini' => '12:00:00', 'fin' => '13:00:00'], ['ini' => '13:00:00', 'fin' => '14:00:00']
          ];

    // 2. Limpiar horario previo para este grupo y ciclo
    mysqli_query($conn, "DELETE FROM horarios WHERE grupo_id = $grupo_id AND ciclo_id = $ciclo_id");

    // 3. Obtener materias con carga horaria
    $res_materias = mysqli_query($conn, "SELECT id, horas_semanales FROM materias WHERE horas_semanales > 0");
    
    // 4. Cargar catálogos
    $profes_db = mysqli_fetch_all(mysqli_query($conn, "SELECT id FROM profesores"), MYSQLI_ASSOC);
    $salones = mysqli_fetch_all(mysqli_query($conn, "SELECT id FROM salones"), MYSQLI_ASSOC);

    if (empty($profes_db) || empty($salones)) {
        header("Location: index.php?msg=error_catalogos");
        exit;
    }

    // 5. Algoritmo de asignación con "Mapa de Recursos Fijos"
    $recursos_materia = []; // Para fijar Profe y Salón por materia

    while ($m = mysqli_fetch_assoc($res_materias)) {
        $m_id = $m['id'];
        $horas_totales = (int)$m['horas_semanales'];

        // Asignar recursos una sola vez por materia/grupo
        if (!isset($recursos_materia[$m_id])) {
            $recursos_materia[$m_id] = [
                'p_id' => $profes_db[array_rand($profes_db)]['id'],
                's_id' => $salones[array_rand($salones)]['id']
            ];
        }

        $p_id = $recursos_materia[$m_id]['p_id'];
        $s_id = $recursos_materia[$m_id]['s_id'];

        for ($i = 0; $i < $horas_totales; $i++) {
            $asignada = false;
            $intentos = 0;

            while (!$asignada && $intentos < 100) {
                $dia = $dias[array_rand($dias)];
                $bloque = $bloques_pool[array_rand($bloques_pool)];
                $hi = $bloque['ini'];
                $hf = $bloque['fin'];

                // VALIDACIÓN DE COLISIONES
                // Nota: Usamos una sola consulta para verificar los 3 conflictos posibles
                $check = "SELECT id FROM horarios 
                          WHERE ciclo_id = $ciclo_id 
                          AND dia_semana = '$dia' 
                          AND hora_inicio = '$hi' 
                          AND (grupo_id = $grupo_id OR profesor_id = $p_id OR salon_id = $s_id)";
                
                $result_check = mysqli_query($conn, $check);

                if (mysqli_num_rows($result_check) == 0) {
                    $sql = "INSERT INTO horarios (ciclo_id, grupo_id, materia_id, profesor_id, salon_id, dia_semana, hora_inicio, hora_fin) 
                            VALUES ($ciclo_id, $grupo_id, $m_id, $p_id, $s_id, '$dia', '$hi', '$hf')";
                    
                    if (mysqli_query($conn, $sql)) {
                        $asignada = true;
                    }
                }
                $intentos++;
            }
        }
    }

    header("Location: index.php?msg=generado&grupo_id=$grupo_id");
    exit;
}