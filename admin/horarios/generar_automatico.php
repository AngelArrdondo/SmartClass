<?php
session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grupo_id'])) {
    $grupo_id = (int)$_POST['grupo_id'];
    $ciclo_id = (int)$_POST['ciclo_id'];

    if (!$ciclo_id) { header("Location: index.php?msg=error_ciclo"); exit; }

    // 1. Configurar bloques de tiempo (Ejemplo estándar de 1 hora)
    $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
    $bloques = [
        ['ini' => '07:00:00', 'fin' => '08:00:00'],
        ['ini' => '08:00:00', 'fin' => '09:00:00'],
        ['ini' => '09:00:00', 'fin' => '10:00:00'],
        ['ini' => '11:00:00', 'fin' => '12:00:00'],
        ['ini' => '12:00:00', 'fin' => '13:00:00']
    ];

    // 2. Limpiar horario previo para este grupo y ciclo (RECOMODO)
    mysqli_query($conn, "DELETE FROM horarios WHERE grupo_id = $grupo_id AND ciclo_id = $ciclo_id");

    // 3. Obtener materias aleatorias para asignar (puedes ajustar el LIMIT)
    $res_materias = mysqli_query($conn, "SELECT id FROM materias ORDER BY RAND() LIMIT 6");
    
    // 4. Cargar catálogos disponibles
    $profes = mysqli_fetch_all(mysqli_query($conn, "SELECT id FROM profesores"), MYSQLI_ASSOC);
    $salones = mysqli_fetch_all(mysqli_query($conn, "SELECT id FROM salones"), MYSQLI_ASSOC);

    // 5. Algoritmo de asignación sin colisiones
    while ($m = mysqli_fetch_assoc($res_materias)) {
        $m_id = $m['id'];
        $asignada = false;
        $intentos = 0;

        while (!$asignada && $intentos < 50) {
            $dia = $dias[array_rand($dias)];
            $bloque = $bloques[array_rand($bloques)];
            $p_id = $profes[array_rand($profes)]['id'];
            $s_id = $salones[array_rand($salones)]['id'];

            $hi = $bloque['ini'];
            $hf = $bloque['fin'];

            // VALIDACIÓN DE COLISIÓN (Igual a tu save_horario.php)
            $check = "SELECT id FROM horarios WHERE ciclo_id = $ciclo_id AND dia_semana = '$dia' 
                      AND (hora_inicio < '$hf' AND hora_fin > '$hi') 
                      AND (grupo_id = $grupo_id OR profesor_id = $p_id OR salon_id = $s_id)";
            
            if (mysqli_num_rows(mysqli_query($conn, $check)) == 0) {
                $sql = "INSERT INTO horarios (ciclo_id, grupo_id, materia_id, profesor_id, salon_id, dia_semana, hora_inicio, hora_fin) 
                        VALUES ($ciclo_id, $grupo_id, $m_id, $p_id, $s_id, '$dia', '$hi', '$hf')";
                mysqli_query($conn, $sql);
                $asignada = true;
            }
            $intentos++;
        }
    }
    header("Location: index.php?msg=generado&grupo_id=$grupo_id");
    exit;
}