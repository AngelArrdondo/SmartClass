<?php
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Sesión no autorizada']);
    exit;
}

$id = (int)$_POST['id'];
$dia = mysqli_real_escape_string($conn, $_POST['dia']);
$hora_inicio = $_POST['hora'] . ":00";
// Calculamos la hora de fin basándonos en la nueva hora de inicio
$hora_fin = date("H:i:s", strtotime($hora_inicio . " +1 hour"));

// 1. Obtener datos de la clase actual para conocer contexto (grupo, profe, salon)
$q = mysqli_query($conn, "SELECT grupo_id, profesor_id, salon_id, ciclo_id FROM horarios WHERE id = $id");
$h = mysqli_fetch_assoc($q);

if (!$h) {
    echo json_encode(['success' => false, 'message' => 'Clase no encontrada']);
    exit;
}

// 2. Validar colisiones (Traslape de tiempo)
// Esta lógica detecta si hay alguna clase que choque en el nuevo horario
$check = "SELECT id FROM horarios 
          WHERE ciclo_id = {$h['ciclo_id']} 
          AND dia_semana = '$dia' 
          AND id != $id
          AND (
               (hora_inicio < '$hora_fin' AND hora_fin > '$hora_inicio') 
          )
          AND (grupo_id = {$h['grupo_id']} OR profesor_id = {$h['profesor_id']} OR salon_id = {$h['salon_id']})";

$res_check = mysqli_query($conn, $check);

if (mysqli_num_rows($res_check) > 0) {
    echo json_encode(['success' => false, 'message' => '¡Conflicto! El profesor, salón o grupo ya tienen una actividad en este horario.']);
    exit;
}

// 3. Actualizar posición
$update = mysqli_query($conn, "UPDATE horarios SET dia_semana = '$dia', hora_inicio = '$hora_inicio', hora_fin = '$hora_fin' WHERE id = $id");

echo json_encode(['success' => (bool)$update]);