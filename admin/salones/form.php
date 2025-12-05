<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// --- Lógica Crear / Editar ---
$modo_edicion = false;
$titulo = "Nuevo Salón";
$btn_texto = "Registrar Salón";

// Variables iniciales
$id = "";
$codigo = "";
$nombre = "";
$capacidad = "30"; // Valor por defecto sugerido
$ubicacion = "";
$recursos = "";
$observaciones = "";

if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Salón";
    $btn_texto = "Guardar Cambios";
    $id = $_GET['id'];
    
    // TRAER DATOS DE LA BD
    $sql = "SELECT * FROM salones WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $codigo = $fila['codigo'];
            $nombre = $fila['nombre'];
            $capacidad = $fila['capacidad'];
            $ubicacion = $fila['ubicacion'];
            $recursos = $fila['recursos'];
            $observaciones = $fila['observaciones'];
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
                    <i class="bi bi-arrow-left me-1"></i> Volver a salones
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            
                            <h5 class="fw-bold mb-4 text-secondary">Datos del Espacio Físico</h5>

                            <form action="save_salon.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Código <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-tag"></i></span>
                                            <input type="text" name="codigo" class="form-control" placeholder="Ej. A-101" value="<?php echo $codigo; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold text-muted">Nombre Descriptivo <span class="text-danger">*</span></label>
                                        <input type="text" name="nombre" class="form-control" placeholder="Ej. Aula General, Laboratorio 1..." value="<?php echo $nombre; ?>" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Capacidad (Alumnos) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-people"></i></span>
                                            <input type="number" name="capacidad" class="form-control" placeholder="30" min="1" max="200" value="<?php echo $capacidad; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold text-muted">Ubicación</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
                                            <input type="text" name="ubicacion" class="form-control" placeholder="Ej. Edificio B, Planta Alta" value="<?php echo $ubicacion; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Recursos Disponibles</label>
                                    <textarea name="recursos" class="form-control" rows="2" placeholder="Ej. Proyector, Aire Acondicionado, Conexión a Internet..."><?php echo $recursos; ?></textarea>
                                    <div class="form-text">Separa los recursos por comas.</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted">Observaciones</label>
                                    <textarea name="observaciones" class="form-control bg-light" rows="2" placeholder="Notas internas (ej. En reparación, Chapa dañada)"><?php echo $observaciones; ?></textarea>
                                </div>

                                <hr class="my-4 text-muted opacity-25">

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-light border px-4">Cancelar</a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                                        <i class="bi bi-check-lg me-2"></i> <?php echo $btn_texto; ?>
                                    </button>
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
             const sidebar = document.getElementById('sidebar'); 
             if(sidebar) sidebar.classList.toggle('d-none');
        });
    }
</script>
</body>
</html>