<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD: Solo Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// 2. VALIDAR QUE RECIBIMOS LOS DATOS
if (isset($_GET['alumno_id']) && isset($_GET['grupo_id'])) {
    
    $alumno_id = $_GET['alumno_id'];
    $grupo_id = $_GET['grupo_id']; 
    
    // 3. ACTUALIZAR: Poner grupo_id en NULL (Sacarlo del salón)
    $sql = "UPDATE alumnos SET grupo_id = NULL WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $alumno_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Regresamos a la lista de ese grupo específico
            header("location: ver_alumnos.php?id=" . $grupo_id . "&msg=alumno_quitado");
        } else {
            echo "Error al quitar alumno: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Si faltan datos, regresamos al índice de grupos
    header("location: index.php");
}
?>