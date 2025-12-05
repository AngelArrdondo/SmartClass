<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $alumno_id = $_POST['alumno_id'];
    $user_id = $_POST['user_id'];

    $nombre = trim($_POST['nombre']);
    $paterno = trim($_POST['apellido_paterno']);
    $materno = trim($_POST['apellido_materno']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    
    // CAMBIO: Recibimos el estatus (por defecto 1 si no viene)
    $is_active = isset($_POST['is_active']) ? $_POST['is_active'] : 1;
    
    $matricula = trim($_POST['matricula']);
    $grupo_id = !empty($_POST['grupo_id']) ? $_POST['grupo_id'] : NULL;

    mysqli_begin_transaction($conn);

    try {
        if (empty($alumno_id)) {
            // CREAR (Insert)
            
            $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' UNION SELECT id FROM alumnos WHERE matricula='$matricula'");
            if (mysqli_num_rows($check) > 0) {
                throw new Exception("El correo o la matrícula ya están registrados.");
            }

            $password_hash = password_hash($matricula, PASSWORD_DEFAULT);
            $role_id = 3; 

            // Insertamos user (is_active es 1 por defecto en DB, pero podemos forzarlo)
            $sql_user = "INSERT INTO users (nombre, apellido_paterno, apellido_materno, email, telefono, password_hash, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql_user);
            mysqli_stmt_bind_param($stmt, "ssssssii", $nombre, $paterno, $materno, $email, $telefono, $password_hash, $role_id, $is_active);
            mysqli_stmt_execute($stmt);
            
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            $sql_alum = "INSERT INTO alumnos (user_id, matricula, grupo_id) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql_alum);
            mysqli_stmt_bind_param($stmt, "isi", $new_user_id, $matricula, $grupo_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $msg = "creado";

        } else {
            // EDITAR (Update)

            // CAMBIO: Actualizamos también 'is_active' en la tabla users
            $sql_user = "UPDATE users SET nombre=?, apellido_paterno=?, apellido_materno=?, email=?, telefono=?, is_active=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql_user);
            // "sssssii" = 5 strings, 2 ints
            mysqli_stmt_bind_param($stmt, "sssssii", $nombre, $paterno, $materno, $email, $telefono, $is_active, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $sql_alum = "UPDATE alumnos SET matricula=?, grupo_id=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql_alum);
            mysqli_stmt_bind_param($stmt, "sii", $matricula, $grupo_id, $alumno_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $msg = "actualizado";
        }

        mysqli_commit($conn);
        header("location: index.php?msg=$msg");

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "Error: " . $e->getMessage();
    }
    
    mysqli_close($conn);

} else {
    header("location: index.php");
}
?>