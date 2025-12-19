<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Aquí podrías validar si ya se tomó asistencia para esa clase antes de borrar
    // Por ahora haremos un borrado directo para agilizar
    $sql = "DELETE FROM horarios WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            header("location: index.php?msg=eliminado");
        } else {
            echo "Error al eliminar.";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    header("location: index.php");
}
?>