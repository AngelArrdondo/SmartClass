<?php
session_start();
require_once '../../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. VERIFICAR SI ES EL CICLO ACTIVO
    // No permitimos borrar el ciclo activo porque el sistema depende de él
    $sql_activo = "SELECT activo, nombre FROM ciclos_escolares WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql_activo)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $es_activo, $nombre_ciclo);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($es_activo == 1) {
            header("location: index.php?error=es_activo");
            exit;
        }
    }

    // 2. VERIFICAR DEPENDENCIAS (Grupos y Horarios)
    // Si el ciclo tiene grupos creados o clases programadas, no se puede borrar
    // para no romper el historial académico.
    $sql_check = "
        SELECT 
            (SELECT COUNT(*) FROM grupos WHERE ciclo_id = ?) as num_grupos,
            (SELECT COUNT(*) FROM horarios WHERE ciclo_id = ?) as num_horarios
    ";
    
    if ($stmt = mysqli_prepare($conn, $sql_check)) {
        mysqli_stmt_bind_param($stmt, "ii", $id, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $num_grupos, $num_horarios);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($num_grupos > 0 || $num_horarios > 0) {
            header("location: index.php?error=tiene_datos");
            exit;
        }
    }

    // 3. SI PASÓ LAS PRUEBAS -> BORRAR
    $sql_delete = "DELETE FROM ciclos_escolares WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql_delete)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        try {
            if (mysqli_stmt_execute($stmt)) {
                header("location: index.php?msg=eliminado");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            // Si falla por alguna otra llave foránea no prevista
            header("location: index.php?error=sql_error");
        }
        mysqli_stmt_close($stmt);
    }

} else {
    header("location: index.php");
}
?>