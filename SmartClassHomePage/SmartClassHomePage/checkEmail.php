<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');

if ($email === '') {
    echo json_encode([
        "success" => false,
        "message" => "Correo inválido"
    ]);
    exit;
}

$sql = "SELECT role_id FROM users WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta"
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) === 1) {
    mysqli_stmt_bind_result($stmt, $role_id);
    mysqli_stmt_fetch($stmt);

    $rol = match ($role_id) {
        1 => 'admin',
        2 => 'profesor',
        3 => 'alumno',
        default => 'desconocido'
    };

    echo json_encode([
        "success" => true,
        "rol" => $rol
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "El correo no está registrado"
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
