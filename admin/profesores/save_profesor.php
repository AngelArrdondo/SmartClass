<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $profesor_id = $_POST['profesor_id'];
    $user_id = $_POST['user_id'];

    $nombre = trim($_POST['nombre']);
    $ape_pat = trim($_POST['apellido_paterno']);
    $ape_mat = trim($_POST['apellido_materno']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password_input = $_POST['password']; // Puede venir vacía
    
    // Estatus (si no viene, asumimos 1)
    $is_active = isset($_POST['is_active']) ? $_POST['is_active'] : 1;

    $codigo = trim($_POST['codigo_empleado']);
    $especialidad = trim($_POST['especialidad']);

    mysqli_begin_transaction($conn);

    try {
        if (empty($profesor_id)) {
            // ================= CREAR =================
            
            // Validar duplicados (Email o Código)
            $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' UNION SELECT id FROM profesores WHERE codigo_empleado='$codigo'");
            if (mysqli_num_rows($check) > 0) {
                throw new Exception("El correo o el código de empleado ya existen.");
            }

            // Contraseña: Si el input está vacío, usamos el CÓDIGO EMPLEADO
            $pass_plain = !empty($password_input) ? $password_input : $codigo;
            $password_hash = password_hash($pass_plain, PASSWORD_DEFAULT);
            $role_id = 2; // Profesor

            // 1. Insert User
            $sql_user = "INSERT INTO users (nombre, apellido_paterno, apellido_materno, email, telefono, password_hash, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql_user);
            mysqli_stmt_bind_param($stmt, "ssssssii", $nombre, $ape_pat, $ape_mat, $email, $telefono, $password_hash, $role_id, $is_active);
            mysqli_stmt_execute($stmt);
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // 2. Insert Profesor
            $sql_prof = "INSERT INTO profesores (user_id, codigo_empleado, especialidad) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql_prof);
            mysqli_stmt_bind_param($stmt, "iss", $new_user_id, $codigo, $especialidad);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $msg = "creado";

        } else {
            // ================= EDITAR =================

            // 1. Update User
            // Solo actualizamos password si escribieron algo nuevo
            if (!empty($password_input)) {
                $password_hash = password_hash($password_input, PASSWORD_DEFAULT);
                $sql_user = "UPDATE users SET nombre=?, apellido_paterno=?, apellido_materno=?, email=?, telefono=?, is_active=?, password_hash=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $sql_user);
                mysqli_stmt_bind_param($stmt, "sssssisi", $nombre, $ape_pat, $ape_mat, $email, $telefono, $is_active, $password_hash, $user_id);
            } else {
                $sql_user = "UPDATE users SET nombre=?, apellido_paterno=?, apellido_materno=?, email=?, telefono=?, is_active=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $sql_user);
                mysqli_stmt_bind_param($stmt, "sssssii", $nombre, $ape_pat, $ape_mat, $email, $telefono, $is_active, $user_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 2. Update Profesor
            $sql_prof = "UPDATE profesores SET codigo_empleado=?, especialidad=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql_prof);
            mysqli_stmt_bind_param($stmt, "ssi", $codigo, $especialidad, $profesor_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $msg = "actualizado";
        }

        mysqli_commit($conn);
        header("location: index.php?msg=$msg");

    } catch (Exception $e) {
        mysqli_rollback($conn);
        // Regresar al form con error (básico) o mostrar echo
        echo "Error: " . $e->getMessage();
    }
    
    mysqli_close($conn);

} else {
    header("location: index.php");
}
?>