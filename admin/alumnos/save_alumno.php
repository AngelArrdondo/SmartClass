<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD: Solo administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recuperar IDs (si existen)
    $alumno_id = $_POST['alumno_id'];
    $user_id   = $_POST['user_id'];

    // Saneamiento de datos personales
    $nombre    = trim($_POST['nombre']);
    $paterno   = trim($_POST['apellido_paterno']);
    $materno   = trim($_POST['apellido_materno']);
    $email     = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $telefono  = trim($_POST['telefono']);
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    
    // Datos académicos
    $matricula = trim($_POST['matricula']);
    $grupo_id  = !empty($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : NULL;

    // Iniciar Transacción
    mysqli_begin_transaction($conn);

    try {
        if (empty($alumno_id)) {
            // --- OPERACIÓN: CREAR NUEVO ALUMNO ---
            
            // Validar si el correo o la matrícula ya existen
            $sql_check = "SELECT u.id FROM users u LEFT JOIN alumnos a ON u.id = a.user_id WHERE u.email = ? OR a.matricula = ?";
            $stmt_check = mysqli_prepare($conn, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "ss", $email, $matricula);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                throw new Exception("El correo electrónico o la matrícula ya están registrados en el sistema.");
            }
            mysqli_stmt_close($stmt_check);

            // La contraseña por defecto será su matrícula (hash seguro)
            $password_hash = password_hash($matricula, PASSWORD_DEFAULT);
            $role_id = 3; // ROL ALUMNO

            // 1. Insertar en tabla USERS
            $sql_user = "INSERT INTO users (nombre, apellido_paterno, apellido_materno, email, telefono, password_hash, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_u = mysqli_prepare($conn, $sql_user);
            mysqli_stmt_bind_param($stmt_u, "ssssssii", $nombre, $paterno, $materno, $email, $telefono, $password_hash, $role_id, $is_active);
            mysqli_stmt_execute($stmt_u);
            
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_u);

            // 2. Insertar en tabla ALUMNOS
            $sql_alum = "INSERT INTO alumnos (user_id, matricula, grupo_id) VALUES (?, ?, ?)";
            $stmt_a = mysqli_prepare($conn, $sql_alum);
            mysqli_stmt_bind_param($stmt_a, "isi", $new_user_id, $matricula, $grupo_id);
            mysqli_stmt_execute($stmt_a);
            mysqli_stmt_close($stmt_a);

            $msg = "creado";

        } else {
            // --- OPERACIÓN: EDITAR ALUMNO EXISTENTE ---

            // 1. Actualizar tabla USERS
            $sql_user = "UPDATE users SET nombre=?, apellido_paterno=?, apellido_materno=?, email=?, telefono=?, is_active=? WHERE id=?";
            $stmt_u = mysqli_prepare($conn, $sql_user);
            mysqli_stmt_bind_param($stmt_u, "sssssii", $nombre, $paterno, $materno, $email, $telefono, $is_active, $user_id);
            mysqli_stmt_execute($stmt_u);
            mysqli_stmt_close($stmt_u);

            // 2. Actualizar tabla ALUMNOS
            $sql_alum = "UPDATE alumnos SET matricula=?, grupo_id=? WHERE id=?";
            $stmt_a = mysqli_prepare($conn, $sql_alum);
            mysqli_stmt_bind_param($stmt_a, "sii", $matricula, $grupo_id, $alumno_id);
            mysqli_stmt_execute($stmt_a);
            mysqli_stmt_close($stmt_a);

            $msg = "actualizado";
        }

        // Si todo salió bien, confirmar cambios
        mysqli_commit($conn);
        header("location: index.php?msg=$msg");
        exit;

    } catch (Exception $e) {
        // Si hubo error, deshacer todo
        mysqli_rollback($conn);
        die("Error en la operación: " . $e->getMessage());
    }
    
    mysqli_close($conn);

} else {
    header("location: index.php");
    exit;
}
?>