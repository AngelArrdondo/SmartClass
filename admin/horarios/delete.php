<?php
session_start();
require_once '../../config/db.php';

// 1. Seguridad de Acceso
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // Casting a entero por seguridad
    
    // 2. Obtener el grupo_id antes de borrar
    // Usamos sentencia preparada también aquí para evitar cualquier riesgo
    $stmt_grupo = mysqli_prepare($conn, "SELECT grupo_id FROM horarios WHERE id = ?");
    mysqli_stmt_bind_param($stmt_grupo, "i", $id);
    mysqli_stmt_execute($stmt_grupo);
    $res = mysqli_stmt_get_result($stmt_grupo);
    $reg = mysqli_fetch_assoc($res);
    $grupo_id = $reg['grupo_id'] ?? '';
    mysqli_stmt_close($stmt_grupo);

    // 3. Ejecutar el borrado
    $sql = "DELETE FROM horarios WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Éxito
            header("location: index.php?grupo_id=$grupo_id&msg=eliminado");
        } else {
            // Error de integridad (por ejemplo, si tiene asistencias ligadas)
            header("location: index.php?grupo_id=$grupo_id&msg=error_integridad");
        }
        mysqli_stmt_close($stmt);
    }
    exit;
} else {
    header("location: index.php");
    exit;
}