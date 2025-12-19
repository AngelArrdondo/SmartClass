<?php
session_start();
require_once '../../config/db.php';

// Seguridad: Solo administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // 1. VERIFICACIÓN DE INTEGRIDAD REFERENCIAL
    // Consultamos si la materia existe en horarios o asistencias antes de intentar borrarla
    $sql_check = "SELECT 
                    (SELECT COUNT(*) FROM horarios WHERE materia_id = ?) AS total_h,
                    (SELECT COUNT(*) FROM asistencias WHERE materia_id = ?) AS total_a";
    
    if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
        mysqli_stmt_bind_param($stmt_check, "ii", $id, $id);
        mysqli_stmt_execute($stmt_check);
        $result = mysqli_stmt_get_result($stmt_check);
        $data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_check);

        if ($data['total_h'] > 0 || $data['total_a'] > 0) {
            // La materia tiene historial, no se puede borrar por integridad de datos
            header("location: index.php?error=en_uso");
            exit;
        }
    }

    // 2. PROCESO DE ELIMINACIÓN
    $sql_delete = "DELETE FROM materias WHERE id = ?";
    if ($stmt_del = mysqli_prepare($conn, $sql_delete)) {
        mysqli_stmt_bind_param($stmt_del, "i", $id);
        
        if (mysqli_stmt_execute($stmt_del)) {
            // Éxito
            header("location: index.php?msg=eliminado");
        } else {
            // Error técnico en la base de datos
            header("location: index.php?error=db_error");
        }
        mysqli_stmt_close($stmt_del);
    }
} else {
    // Si no hay ID o no es válido, regresamos al índice
    header("location: index.php");
}

mysqli_close($conn);
?>