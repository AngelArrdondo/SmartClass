<?php
session_start();
require_once '../../config/db.php';

// 1. Seguridad de Acceso
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// 2. Validar parámetros (Casting a int para mayor seguridad)
if (isset($_GET['materia_id']) && isset($_GET['grupo_id'])) {
    $materia_id = (int)$_GET['materia_id'];
    $grupo_id = (int)$_GET['grupo_id'];

    // 3. Ejecutar el borrado masivo
    // Esto eliminará todos los bloques de tiempo (Lunes 8am, Miércoles 10am, etc.) de esa materia específica
    $sql = "DELETE FROM horarios WHERE materia_id = ? AND grupo_id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $materia_id, $grupo_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Guardamos en sesión para persistencia
            $_SESSION['ultimo_grupo_visto'] = $grupo_id;
            
            // 4. Redirección con parámetros de éxito
            header("location: index.php?grupo_id=$grupo_id&msg=materia_eliminada");
            exit;
        } else {
            // En caso de error por llaves foráneas (si ya hay asistencias)
            header("location: index.php?grupo_id=$grupo_id&msg=error_vinculo");
            exit;
        }
    }
} else {
    header("location: index.php");
    exit;
}