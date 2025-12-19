<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // VERIFICAR USO EN HORARIOS O ASISTENCIAS
    $check_h = mysqli_query($conn, "SELECT COUNT(*) as num FROM horarios WHERE materia_id = $id");
    $data_h = mysqli_fetch_assoc($check_h);

    $check_a = mysqli_query($conn, "SELECT COUNT(*) as num FROM asistencias WHERE materia_id = $id");
    $data_a = mysqli_fetch_assoc($check_a);
    
    if ($data_h['num'] > 0 || $data_a['num'] > 0) {
        // ESTÁ EN USO -> NO BORRAR
        header("location: index.php?error=en_uso");
    } else {
        // ESTÁ LIMPIA -> BORRAR
        $sql = "DELETE FROM materias WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            header("location: index.php?msg=eliminado");
        }
    }
} else {
    header("location: index.php");
}
?>