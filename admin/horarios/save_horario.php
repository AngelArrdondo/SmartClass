<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    
    // Recibir datos
    $ciclo_id = $_POST['ciclo_id'];
    $grupo_id = $_POST['grupo_id']; // Usaremos esto para la redirección
    $materia_id = $_POST['materia_id'];
    $profesor_id = $_POST['profesor_id'];
    $salon_id = $_POST['salon_id'];
    $dia_semana = $_POST['dia_semana'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];

    // VALIDACIÓN BÁSICA
    if ($hora_inicio >= $hora_fin) {
        echo "<script>alert('Error: La hora de fin debe ser posterior a la hora de inicio.'); window.history.back();</script>";
        exit;
    }

    $id_exclude = empty($id) ? -1 : $id;

    // VALIDACIÓN DE CONFLICTOS
    $sql_check = "
        SELECT * FROM horarios 
        WHERE ciclo_id = ? 
          AND dia_semana = ? 
          AND (salon_id = ? OR profesor_id = ? OR grupo_id = ?)
          AND (hora_inicio < ? AND hora_fin > ?)
          AND id != ?
    ";

    if ($stmt = mysqli_prepare($conn, $sql_check)) {
        mysqli_stmt_bind_param($stmt, "ississsi", $ciclo_id, $dia_semana, $salon_id, $profesor_id, $grupo_id, $hora_fin, $hora_inicio, $id_exclude);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($res)) {
            $error_msg = "Conflicto de Horario detectado:\\n";
            if ($row['salon_id'] == $salon_id) $error_msg .= "- El SALÓN ya está ocupado.\\n";
            if ($row['profesor_id'] == $profesor_id) $error_msg .= "- El PROFESOR ya tiene clase.\\n";
            if ($row['grupo_id'] == $grupo_id) $error_msg .= "- El GRUPO ya tiene otra materia.\\n";
            
            echo "<script>alert('$error_msg'); window.history.back();</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    // PROCESO DE GUARDADO
    if (empty($id)) {
        // CREAR
        $sql = "INSERT INTO horarios (ciclo_id, grupo_id, materia_id, profesor_id, salon_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiiiisss", $ciclo_id, $grupo_id, $materia_id, $profesor_id, $salon_id, $dia_semana, $hora_inicio, $hora_fin);
        $msg = "creado";
    } else {
        // EDITAR
        $sql = "UPDATE horarios SET ciclo_id=?, grupo_id=?, materia_id=?, profesor_id=?, salon_id=?, dia_semana=?, hora_inicio=?, hora_fin=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiiiisssi", $ciclo_id, $grupo_id, $materia_id, $profesor_id, $salon_id, $dia_semana, $hora_inicio, $hora_fin, $id);
        $msg = "actualizado";
    }

    if (mysqli_stmt_execute($stmt)) {
        // REDIRECCIÓN DINÁMICA: Regresamos al index pero manteniendo el grupo seleccionado
        header("location: index.php?grupo_id=$grupo_id&msg=$msg");
    } else {
        echo "Error SQL: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

} else {
    header("location: index.php");
}
?>