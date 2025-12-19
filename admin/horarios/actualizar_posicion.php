<?php
require_once '../../config/db.php';
header('Content-Type: application/json');

$id = (int)$_POST['id'];
$dia = $_POST['dia'];
$hora_inicio = $_POST['hora'] . ":00";
$hora_fin = date("H:i:s", strtotime($hora_inicio . " +1 hour"));

// 1. Obtener datos de la clase actual
$q = mysqli_query($conn, "SELECT grupo_id, profesor_id, salon_id, ciclo_id FROM horarios WHERE id = $id");
$h = mysqli_fetch_assoc($q);

// 2. Validar colisiones (Muy importante)
$check = "SELECT id FROM horarios WHERE ciclo_id = {$h['ciclo_id']} AND dia_semana = '$dia' 
          AND hora_inicio = '$hora_inicio' AND id != $id
          AND (grupo_id = {$h['grupo_id']} OR profesor_id = {$h['profesor_id']} OR salon_id = {$h['salon_id']})";

if (mysqli_num_rows(mysqli_query($conn, $check)) > 0) {
    echo json_encode(['success' => false, 'message' => '¡Colisión detectada! El profesor, salón o grupo ya están ocupados.']);
    exit;
}

// 3. Actualizar posición
$update = mysqli_query($conn, "UPDATE horarios SET dia_semana = '$dia', hora_inicio = '$hora_inicio', hora_fin = '$hora_fin' WHERE id = $id");

echo json_encode(['success' => $update]);