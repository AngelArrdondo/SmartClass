<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// --- VARIABLES INICIALES (VACÍAS) ---
// Esto es para cuando entras a "Crear Nuevo"
$modo_edicion = false;
$titulo = "Nuevo Ciclo Escolar";
$btn_texto = "Crear Periodo";

$id = "";
$nombre = "";
$fecha_inicio = "";
$fecha_fin = "";
$activo = "0"; 

// --- LÓGICA DE EDICIÓN ---
// Si la URL trae un ID (ej: form.php?id=1), buscamos los datos
if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Ciclo Escolar";
    $btn_texto = "Guardar Cambios";
    $id = $_GET['id'];
    
    // Consulta segura para traer los datos actuales
    $sql = "SELECT * FROM ciclos_escolares WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($fila = mysqli_fetch_assoc($resultado)) {
            // AQUÍ LLENAMOS LAS VARIABLES CON LO QUE HAY EN LA BD
            $nombre = $fila['nombre'];
            $fecha_inicio = $fila['fecha_inicio'];
            $fecha_fin = $fila['fecha_fin'];
            $activo = $fila['activo'];
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
                    <i class="bi bi-arrow-left me-1"></i> Volver a ciclos
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-6"> 
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4 text-secondary">Datos del Periodo</h5>

                            <form action="save_ciclo.php" method="POST">
                                
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted">Nombre Oficial <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Ej. Enero - Junio 2025" value="<?php echo $nombre; ?>" required>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-muted">Fecha Inicio <span class="text-danger">*</span></label>
                                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-muted">Fecha Fin <span class="text-danger">*</span></label>
                                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>" required>
                                    </div>
                                </div>

                                <hr class="my-4 text-muted opacity-25">

                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Estado del Ciclo</label>
                                    <select name="activo" class="form-select <?php echo ($activo == '1') ? 'border-success text-success fw-bold' : ''; ?>">
                                        <option value="0" <?php if($activo == '0') echo 'selected'; ?>>Inactivo / Finalizado</option>
                                        <option value="1" <?php if($activo == '1') echo 'selected'; ?>>🟢 ACTIVO (En curso)</option>
                                    </select>
                                    <div class="form-text text-warning">
                                        <i class="bi bi-info-circle"></i> Al marcar este como Activo, los demás ciclos se desactivarán automáticamente.
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary fw-bold py-2">
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
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn){
        toggleBtn.addEventListener('click', () => {
             // Asegúrate de que coincida con tu lógica de menú (toggled o d-none)
             const sidebar = document.getElementById('sidebar'); 
             if(sidebar) sidebar.classList.toggle('d-none');
        });
    }
</script>
</body>
</html>