<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD: Solo Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 2. VERIFICACIÓN DE INTEGRIDAD
    // Antes de borrar, revisamos si hay alumnos inscritos en este grupo
    $sql_check = "SELECT COUNT(*) as total FROM alumnos WHERE grupo_id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql_check)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $total_alumnos);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Si tiene alumnos, PROHIBIDO borrar
        if ($total_alumnos > 0) {
            header("location: index.php?error=tiene_alumnos");
            exit;
        }
    }

    // 3. SI PASÓ LA PRUEBA -> BORRAR
    // También deberíamos verificar si tiene horarios asignados, pero por ahora priorizamos alumnos
    $sql_delete = "DELETE FROM grupos WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql_delete)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            header("location: index.php?msg=eliminado");
        } else {
            // Error de BD (quizás tiene horarios asignados y la llave foránea lo impide)
            header("location: index.php?error=sql_error"); 
        }
        mysqli_stmt_close($stmt);
    }
} else {
    header("location: index.php");
}
?>