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

    // 0. Obtener el turno del grupo para saber qué bloques usar
    $res_grupo = mysqli_query($conn, "SELECT turno FROM grupos WHERE id = $grupo_id");
    $info_grupo = mysqli_fetch_assoc($res_grupo);
    $turno = $info_grupo['turno'] ?? 'Matutino';

    // 1. Configuración de Días y Bloques (Sin incluir la hora de recreo)
    $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
    
    if ($turno == 'Vespertino') {
        // Turno Tarde: Salta de 17:00 a 18:00 (Hora de comida/recreo)
        $bloques_pool = [
            ['ini' => '14:00:00', 'fin' => '15:00:00'],
            ['ini' => '15:00:00', 'fin' => '16:00:00'],
            ['ini' => '16:00:00', 'fin' => '17:00:00'],
            // El recreo es de 17:00 a 18:00, por eso saltamos a las 18:00
            ['ini' => '18:00:00', 'fin' => '19:00:00'],
            ['ini' => '19:00:00', 'fin' => '20:00:00'],
            ['ini' => '20:00:00', 'fin' => '21:00:00'],
        ];
    } else {
        // Turno Mañana: Salta de 10:00 a 11:00 (Hora de recreo)
        $bloques_pool = [
            ['ini' => '07:00:00', 'fin' => '08:00:00'],
            ['ini' => '08:00:00', 'fin' => '09:00:00'],
            ['ini' => '09:00:00', 'fin' => '10:00:00'],
            // El recreo es de 10:00 a 11:00, por eso saltamos a las 11:00
            ['ini' => '11:00:00', 'fin' => '12:00:00'],
            ['ini' => '12:00:00', 'fin' => '13:00:00'],
            ['ini' => '13:00:00', 'fin' => '14:00:00'],
        ];
    }

    // 2. Limpiar solo el horario del grupo actual para este ciclo
    mysqli_query($conn, "DELETE FROM horarios WHERE grupo_id = $grupo_id AND ciclo_id = $ciclo_id");

    // 3. Obtener materias con su carga horaria real
    $res_materias = mysqli_query($conn, "SELECT id, horas_semanales FROM materias");
    
    // 4. Catálogos
    $profes = mysqli_fetch_all(mysqli_query($conn, "SELECT id FROM profesores"), MYSQLI_ASSOC);
    $salones = mysqli_fetch_all(mysqli_query($conn, "SELECT id FROM salones"), MYSQLI_ASSOC);

    if (empty($profes) || empty($salones)) {
        header("Location: index.php?msg=error_catalogos");
        exit;
    }

    // 5. Algoritmo de asignación "Inteligente"
    while ($m = mysqli_fetch_assoc($res_materias)) {
        $m_id = $m['id'];
        $horas_totales = (int)$m['horas_semanales'];
        if ($horas_totales <= 0) $horas_totales = 1;

        for ($i = 0; $i < $horas_totales; $i++) {
            $asignada = false;
            $intentos = 0;

            while (!$asignada && $intentos < 500) { // Aumentamos intentos para encontrar huecos libres entre grupos
                $dia = $dias[array_rand($dias)];
                $bloque = $bloques_pool[array_rand($bloques_pool)];
                $p_id = $profes[array_rand($profes)]['id'];
                $s_id = $salones[array_rand($salones)]['id'];

                $hi = $bloque['ini'];
                $hf = $bloque['fin'];

                // VALIDACIÓN GLOBAL DE COLISIONES
                // Aquí es donde ocurre la magia: revisamos si el profe o el salón están ocupados 
                // por CUALQUIER otro grupo que ya tenga horario generado.
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