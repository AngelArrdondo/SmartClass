<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $codigo = trim($_POST['codigo']);
    $creditos = $_POST['creditos'];
    
    // 1. RECIBIR LA DESCRIPCIÓN
    $descripcion = trim($_POST['descripcion']); 

    // Validar duplicados de código
    $id_check = empty($id) ? -1 : $id;
    $sql_check = "SELECT id FROM materias WHERE codigo = ? AND id != ?";
    
    if ($stmt = mysqli_prepare($conn, $sql_check)) {
        mysqli_stmt_bind_param($stmt, "si", $codigo, $id_check);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            echo "<script>alert('Error: El código de materia $codigo ya existe.'); window.history.back();</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($id)) {
        // CREAR (INSERT) - AHORA CON DESCRIPCIÓN
        $sql = "INSERT INTO materias (codigo, nombre, creditos, descripcion) VALUES (?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // "ssis" = string, string, int, string
            mysqli_stmt_bind_param($stmt, "ssis", $codigo, $nombre, $creditos, $descripcion);
            
            if (mysqli_stmt_execute($stmt)) {
                header("location: index.php?msg=creado");
            } else {
                echo "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // EDITAR (UPDATE) - AHORA CON DESCRIPCIÓN
        $sql = "UPDATE materias SET codigo=?, nombre=?, creditos=?, descripcion=? WHERE id=?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // "ssisi" = string, string, int, string, int (id al final)
            mysqli_stmt_bind_param($stmt, "ssisi", $codigo, $nombre, $creditos, $descripcion, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                header("location: index.php?msg=actualizado");
            } else {
                echo "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);

} else {
    header("location: index.php");
}
?>