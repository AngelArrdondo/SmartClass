<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

$modo_edicion = false;
$titulo = "Nuevo Profesor";
$btn_texto = "Registrar Profesor";

// Variables iniciales
$user_id = "";
$profesor_id = ""; // ID de la tabla profesores

$nombre = "";
$ape_pat = "";
$ape_mat = "";
$email = "";
$telefono = "";
$is_active = 1; // Por defecto activo

$codigo = "";
$especialidad = "";

// LÓGICA DE EDICIÓN
if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Profesor";
    $btn_texto = "Guardar Cambios";
    $profesor_id = $_GET['id'];

    // JOIN para traer datos de 'profesores' y 'users'
    $sql = "
        SELECT p.id as prof_id, p.codigo_empleado, p.especialidad,
               u.id as usr_id, u.nombre, u.apellido_paterno, u.apellido_materno, u.email, u.telefono, u.is_active
        FROM profesores p
        INNER JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $profesor_id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $user_id = $fila['usr_id'];
            $nombre = $fila['nombre'];
            $ape_pat = $fila['apellido_paterno'];
            $ape_mat = $fila['apellido_materno'];
            $email = $fila['email'];
            $telefono = $fila['telefono'];
            $is_active = $fila['is_active'];
            
            $codigo = $fila['codigo_empleado'];
            $especialidad = $fila['especialidad'];
        }
        mysqli_stmt_close($stmt);
    }
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
            
            <div class="mb-3">
                <a href="index.php" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i> Volver a la lista
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8"> <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            
                            <form action="save_profesor.php" method="POST">
                                <input type="hidden" name="profesor_id" value="<?php echo $profesor_id; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                    <h5 class="fw-bold text-secondary mb-0">Información del Docente</h5>
                                    
                                    <?php if($modo_edicion): ?>
                                    <div class="d-flex align-items-center">
                                        <label class="small fw-bold text-muted me-2">Estatus:</label>
                                        <select name="is_active" class="form-select form-select-sm" style="width: auto;">
                                            <option value="1" <?php if($is_active == 1) echo 'selected'; ?>>🟢 Activo</option>
                                            <option value="0" <?php if($is_active == 0) echo 'selected'; ?>>🔴 Inactivo (Baja)</option>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Nombre(s) <span class="text-danger">*</span></label>
                                        <input type="text" name="nombre" class="form-control" value="<?php echo $nombre; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Apellido Paterno <span class="text-danger">*</span></label>
                                        <input type="text" name="apellido_paterno" class="form-control" value="<?php echo $ape_pat; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Apellido Materno</label>
                                        <input type="text" name="apellido_materno" class="form-control" value="<?php echo $ape_mat; ?>">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Correo Electrónico <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="<?php echo $email; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">
                                            Contraseña 
                                            <?php if($modo_edicion): ?>
                                                <span class="badge bg-light text-dark border ms-2 fw-normal">Opcional</span>
                                            <?php else: ?>
                                                <span class="text-danger">*</span>
                                                <span class="text-muted small fw-normal">(Por defecto será el Código)</span>
                                            <?php endif; ?>
                                        </label>
                                        <input type="password" name="password" class="form-control" placeholder="<?php echo $modo_edicion ? 'Dejar vacío para mantener actual' : 'Opcional (si vacía = código)'; ?>">
                                    </div>
                                </div>

                                <hr class="my-4 text-muted opacity-25">
                                
                                <h5 class="fw-bold mb-4 text-secondary">Datos Académicos</h5>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Código de Empleado <span class="text-danger">*</span></label>
                                        <input type="text" name="codigo_empleado" class="form-control" placeholder="Ej: P-2025-01" value="<?php echo $codigo; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Especialidad <span class="text-danger">*</span></label>
                                        <input type="text" name="especialidad" class="form-control" placeholder="Ej: Matemáticas" value="<?php echo $especialidad; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Teléfono</label>
                                        <input type="tel" name="telefono" class="form-control" value="<?php echo $telefono; ?>">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-light border px-4">Cancelar</a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                                        <i class="bi bi-check-lg me-2"></i> <?php echo $btn_texto; ?>
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div> </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn){
        toggleBtn.addEventListener('click', () => {
             const sidebar = document.getElementById('sidebar'); 
             if(sidebar) sidebar.classList.toggle('d-none');
        });
    }
</script>

</body>
</html>