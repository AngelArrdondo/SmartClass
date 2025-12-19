<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

$titulo = "Registrar Nuevo Alumno";
$btn_texto = "Guardar Alumno";
$modo_edicion = false;

// Variables Usuario
$user_id = "";
$nombre = "";
$paterno = "";
$materno = "";
$email = "";
$telefono = "";
$is_active = 1;

// Variables Alumno
$alumno_id = "";
$matricula = "";
$grupo_id = "";

// 1. Obtener grupos para el select
$sql_grupos = "SELECT id, codigo FROM grupos ORDER BY codigo ASC";
$res_grupos = mysqli_query($conn, $sql_grupos);

// 2. Lógica Edición vs Nuevo
if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Alumno";
    $btn_texto = "Actualizar Datos";
    $alumno_id = $_GET['id'];
    
    $sql = "
        SELECT a.id as alum_id, a.matricula, a.grupo_id,
               u.id as usr_id, u.nombre, u.apellido_paterno, u.apellido_materno, u.email, u.telefono, u.is_active
        FROM alumnos a
        INNER JOIN users u ON a.user_id = u.id
        WHERE a.id = ?
    ";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $alumno_id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $user_id = $fila['usr_id'];
            $nombre = $fila['nombre'];
            $paterno = $fila['apellido_paterno'];
            $materno = $fila['apellido_materno'];
            $email = $fila['email'];
            $telefono = $fila['telefono'];
            $matricula = $fila['matricula'];
            $grupo_id = $fila['grupo_id'];
            $is_active = $fila['is_active'];
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // --- LÓGICA DE AUTOGENERACIÓN DE MATRÍCULA ---
    $anio_actual = date("Y");
    $sql_last = "SELECT matricula FROM alumnos WHERE matricula LIKE '$anio_actual%' ORDER BY matricula DESC LIMIT 1";
    $res_last = mysqli_query($conn, $sql_last);
    
    if ($fila_last = mysqli_fetch_assoc($res_last)) {
        $ultimo_numero = substr($fila_last['matricula'], 4);
        $siguiente_numero = str_pad((int)$ultimo_numero + 1, 4, "0", STR_PAD_LEFT);
        $matricula = $anio_actual . $siguiente_numero;
    } else {
        $matricula = $anio_actual . "0001";
    }

    // --- LÓGICA DE AUTOGENERACIÓN DE CORREO ---
    $email = $matricula . "@smartclass.com";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?php echo $titulo; ?> | SmartClass</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php'; ?>

    <div id="page-content" class="w-100">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <button class="btn btn-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
                <h4 class="mb-0 fw-bold text-primary"><?php echo $titulo; ?></h4>
                <div class="d-flex align-items-center">
                    <span class="d-none d-md-block small text-muted me-2"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                    <img src="../../assets/img/avatar.png" alt="Admin" class="rounded-circle border" width="35" height="35">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8"> 
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            
                            <form action="save_alumno.php" method="POST">
                                <input type="hidden" name="alumno_id" value="<?php echo $alumno_id; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                    <h6 class="fw-bold text-primary text-uppercase small mb-0">Información Personal</h6>
                                    
                                    <div class="d-flex align-items-center">
                                        <label class="small fw-bold text-muted me-2">Estatus:</label>
                                        <select name="is_active" class="form-select form-select-sm" style="width: auto;">
                                            <option value="1" <?php if($is_active == 1) echo 'selected'; ?>>🟢 Activo</option>
                                            <option value="0" <?php if($is_active == 0) echo 'selected'; ?>>🔴 Inactivo (Baja)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Nombre(s) *</label>
                                        <input type="text" name="nombre" class="form-control" value="<?php echo $nombre; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Apellido Paterno *</label>
                                        <input type="text" name="apellido_paterno" class="form-control" value="<?php echo $paterno; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Apellido Materno</label>
                                        <input type="text" name="apellido_materno" class="form-control" value="<?php echo $materno; ?>">
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Correo Institucional *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-envelope-at text-primary"></i></span>
                                            <input type="email" id="email_input" name="email" class="form-control fw-bold" value="<?php echo $email; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Teléfono</label>
                                        <input type="tel" name="telefono" class="form-control" value="<?php echo $telefono; ?>" maxlength="10">
                                    </div>
                                </div>

                                <h6 class="fw-bold text-primary mb-3 text-uppercase small border-bottom pb-2 mt-4">Información Académica</h6>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Matrícula Escolar *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-hash text-primary"></i></span>
                                            <input type="text" id="matricula_input" name="matricula" class="form-control fw-bold <?php echo !$modo_edicion ? 'text-primary' : ''; ?>" 
                                                   value="<?php echo $matricula; ?>" 
                                                   placeholder="YYYY0000"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Grupo Asignado</label>
                                        <select name="grupo_id" class="form-select">
                                            <option value="">-- Sin Grupo --</option>
                                            <?php while($g = mysqli_fetch_assoc($res_grupos)): ?>
                                                <option value="<?php echo $g['id']; ?>" <?php if($grupo_id == $g['id']) echo 'selected'; ?>>
                                                    <?php echo $g['codigo']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-5">
                                    <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm">
                                        <i class="bi bi-save me-2"></i> <?php echo $btn_texto; ?>
                                    </button>
                                    <a href="index.php" class="btn btn-light border text-muted">Cancelar</a>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar Toggle
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn){
        toggleBtn.addEventListener('click', () => {
             const sidebar = document.getElementById('sidebar'); 
             if(sidebar) sidebar.classList.toggle('d-none');
        });
    }

    // --- LÓGICA DE SINCRONIZACIÓN MATRÍCULA -> CORREO ---
    const matriculaInput = document.getElementById('matricula_input');
    const emailInput = document.getElementById('email_input');
    const esModoEdicion = <?php echo $modo_edicion ? 'true' : 'false'; ?>;

    // Solo automatizamos en modo creación para evitar errores en registros antiguos
    if(!esModoEdicion) {
        matriculaInput.addEventListener('input', function() {
            const matriculaActual = this.value.trim();
            if(matriculaActual !== "") {
                emailInput.value = matriculaActual + "@smartclass.com";
            }
        });
    }
</script>
</body>
</html>