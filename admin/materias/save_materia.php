<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $codigo = strtoupper(trim($_POST['codigo'])); // Forzamos mayúsculas
    $creditos = (int)$_POST['creditos'];
    $horas_semanales = (int)$_POST['horas_semanales']; // 1. RECIBIR LAS HORAS
    $descripcion = trim($_POST['descripcion']); 

    // Validaciones básicas
    if (empty($nombre) || empty($codigo) || $horas_semanales < 1) {
        echo "<script>alert('Error: Datos incompletos o carga horaria inválida.'); window.history.back();</script>";
        exit;
    }

    // Validar duplicados de código
    $id_check = empty($id) ? -1 : $id;
    $sql_check = "SELECT id FROM materias WHERE codigo = ? AND id != ?";
    
    if ($stmt = mysqli_prepare($conn, $sql_check)) {
        mysqli_stmt_bind_param($stmt, "si", $codigo, $id_check);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            echo "<script>alert('Error: El código de materia $codigo ya existe en otro registro.'); window.history.back();</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($id)) {
        // CREAR (INSERT) - SE AÑADE horas_semanales
        $sql = "INSERT INTO materias (codigo, nombre, creditos, horas_semanales, descripcion) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // "ssiis" = string, string, int (cred), int (horas), string (desc)
            mysqli_stmt_bind_param($stmt, "ssiis", $codigo, $nombre, $creditos, $horas_semanales, $descripcion);
            
            if (mysqli_stmt_execute($stmt)) {
                header("location: index.php?msg=creado");
            } else {
                echo "Error al insertar: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // EDITAR (UPDATE) - SE AÑADE horas_semanales
        $sql = "UPDATE materias SET codigo=?, nombre=?, creditos=?, horas_semanales=?, descripcion=? WHERE id=?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // "ssiisi" = string, string, int, int, string, int (id al final)
            mysqli_stmt_bind_param($stmt, "ssiisi", $codigo, $nombre, $creditos, $horas_semanales, $descripcion, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                header("location: index.php?msg=actualizado");
            } else {
                echo "Error al actualizar: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);

} else {
    header("location: index.php");
}
?>