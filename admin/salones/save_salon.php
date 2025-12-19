<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $capacidad = $_POST['capacidad'];
    $ubicacion = trim($_POST['ubicacion']);
    $recursos = trim($_POST['recursos']);
    $observaciones = trim($_POST['observaciones']);

    // Validar duplicados de CÓDIGO
    $id_check = empty($id) ? -1 : $id;
    $sql_check = "SELECT id FROM salones WHERE codigo = ? AND id != ?";
    
    if ($stmt = mysqli_prepare($conn, $sql_check)) {
        mysqli_stmt_bind_param($stmt, "si", $codigo, $id_check);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            echo "<script>alert('Error: El código de salón $codigo ya existe.'); window.history.back();</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($id)) {
        // CREAR
        $sql = "INSERT INTO salones (codigo, nombre, capacidad, ubicacion, recursos, observaciones) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // "ssisss" = string, string, int, string, string, string
            mysqli_stmt_bind_param($stmt, "ssisss", $codigo, $nombre, $capacidad, $ubicacion, $recursos, $observaciones);
            if (mysqli_stmt_execute($stmt)) {
                header("location: index.php?msg=creado");
            } else {
                echo "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // EDITAR
        $sql = "UPDATE salones SET codigo=?, nombre=?, capacidad=?, ubicacion=?, recursos=?, observaciones=? WHERE id=?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssisssi", $codigo, $nombre, $capacidad, $ubicacion, $recursos, $observaciones, $id);
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