<?php
// Ubicación: admin/ciclos/save_ciclo.php
session_start();
require_once '../../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recibimos datos
    $id = $_POST['id']; // Puede venir vacío (Crear) o con numero (Editar)
    $nombre = trim($_POST['nombre']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $activo = $_POST['activo']; // 1 o 0

    // --- LÓGICA DE CICLO ACTIVO ---
    // Si el usuario marcó este ciclo como ACTIVO (1), desactivamos todos los demás.
    if ($activo == 1) {
        $sql_reset = "UPDATE ciclos_escolares SET activo = 0";
        mysqli_query($conn, $sql_reset);
    }

    if (empty($id)) {
        // --- CREAR NUEVO (INSERT) ---
        $sql = "INSERT INTO ciclos_escolares (nombre, fecha_inicio, fecha_fin, activo) VALUES (?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssi", $nombre, $fecha_inicio, $fecha_fin, $activo);
            
            if (mysqli_stmt_execute($stmt)) {
                // Redirigir con éxito
                header("location: index.php?msg=creado");
            } else {
                echo "Error al crear: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // --- ACTUALIZAR EXISTENTE (UPDATE) ---
        $sql = "UPDATE ciclos_escolares SET nombre=?, fecha_inicio=?, fecha_fin=?, activo=? WHERE id=?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssii", $nombre, $fecha_inicio, $fecha_fin, $activo, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Redirigir con éxito
                header("location: index.php?msg=actualizado");
            } else {
                echo "Error al actualizar: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);

} else {
    // Si intentan entrar directo sin POST
    header("location: index.php");
}
?>