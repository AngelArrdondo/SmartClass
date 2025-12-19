<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // VERIFICAR USO EN HORARIOS
    $check = mysqli_query($conn, "SELECT COUNT(*) as num FROM horarios WHERE salon_id = $id");
    $data = mysqli_fetch_assoc($check);
    
    if ($data['num'] > 0) {
        // EN USO -> NO BORRAR
        header("location: index.php?error=en_uso");
    } else {
        // BORRAR
        $sql = "DELETE FROM salones WHERE id = ?";
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