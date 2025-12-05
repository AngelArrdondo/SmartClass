<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    $ciclo_id = $_POST['ciclo_id'];
    $grado = $_POST['grado'];
    $turno = $_POST['turno'];
    $codigo = trim($_POST['codigo']); // Ej. "2A-2025"

    // Validar duplicados de CÓDIGO (No puede haber dos "2A-2025")
    $sql_check = "SELECT id FROM grupos WHERE codigo = ? AND id != ?"; 
    // Si es nuevo, el ID será '' (vacío), si es editar, excluye su propio ID
    
    // Pequeño truco: Si $id está vacío, usamos -1 para que la query SQL no falle en 'id != ?'
    $id_check = empty($id) ? -1 : $id;

    if ($stmt = mysqli_prepare($conn, $sql_check)) {
        mysqli_stmt_bind_param($stmt, "si", $codigo, $id_check);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            // Ya existe ese código
            echo "<script>alert('Error: El código de grupo $codigo ya existe.'); window.history.back();</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($id)) {
        // CREAR
        $sql = "INSERT INTO grupos (codigo, ciclo_id, grado, turno) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "siis", $codigo, $ciclo_id, $grado, $turno);
            if (mysqli_stmt_execute($stmt)) {
                header("location: index.php?msg=creado");
            } else {
                echo "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // EDITAR
        $sql = "UPDATE grupos SET codigo=?, ciclo_id=?, grado=?, turno=? WHERE id=?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "siisi", $codigo, $ciclo_id, $grado, $turno, $id);
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