<?php
session_start();
require_once '../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $capacidad = (int)$_POST['capacidad'];
    $ubicacion = trim($_POST['ubicacion']);
    $recursos = trim($_POST['recursos']);
    $observaciones = trim($_POST['observaciones']);
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if (empty($id)) {
        // INSERT: 7 campos, 7 signos ?, 7 letras (ssisssi)
        $sql = "INSERT INTO salones (codigo, nombre, capacidad, ubicacion, recursos, observaciones, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssisssi", $codigo, $nombre, $capacidad, $ubicacion, $recursos, $observaciones, $is_active);
    } else {
        // UPDATE: 7 campos + ID, 8 signos ?, 8 letras (ssisssii)
        $sql = "UPDATE salones SET codigo=?, nombre=?, capacidad=?, ubicacion=?, recursos=?, observaciones=?, is_active=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssisssii", $codigo, $nombre, $capacidad, $ubicacion, $recursos, $observaciones, $is_active, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        header("location: index.php?msg=" . (empty($id) ? "creado" : "actualizado"));
    } else {
        die("Error de ejecución: " . mysqli_error($conn));
    }
}