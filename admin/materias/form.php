<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// --- LÓGICA DE CONTROL ---
$modo_edicion = false;
$titulo = "Nueva Materia";
$btn_texto = "Registrar Materia";

// Variables vacías por defecto
$id = "";
$codigo = "";
$nombre = "";
$creditos = "0"; 
$descripcion = ""; // Variable para la descripción

// Si recibimos ID, activamos modo Edición
if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Materia";
    $btn_texto = "Guardar Cambios";
    $id = $_GET['id'];
    
    // TRAER DATOS REALES DE LA BD
    $sql = "SELECT * FROM materias WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $codigo = $fila['codigo'];
            $nombre = $fila['nombre'];
            $creditos = $fila['creditos'];
            
            // --- CORRECCIÓN: AHORA SÍ CARGAMOS LA DESCRIPCIÓN ---
            // Verifica que ya hayas corrido el ALTER TABLE en tu base de datos
            if (isset($fila['descripcion'])) {
                $descripcion = $fila['descripcion'];
            }
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
                    <i class="bi bi-arrow-left me-1"></i> Volver al catálogo
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8"> 
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            
                            <h5 class="fw-bold mb-4 text-secondary">Detalles de la Asignatura</h5>

                            <form action="save_materia.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold text-muted">Nombre de la Materia <span class="text-danger">*</span></label>
                                        <input type="text" name="nombre" class="form-control" placeholder="Ej. Cálculo Diferencial" value="<?php echo $nombre; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Código Interno <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-barcode"></i></span>
                                            <input type="text" name="codigo" class="form-control" placeholder="Ej. MAT-101" value="<?php echo $codigo; ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Créditos Académicos</label>
                                    <div class="input-group w-50"> <span class="input-group-text bg-white"><i class="bi bi-mortarboard"></i></span>
                                        <input type="number" name="creditos" class="form-control" placeholder="0" min="0" max="20" value="<?php echo $creditos; ?>">
                                    </div>
                                    <div class="form-text">Valor curricular de la asignatura.</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted">Descripción / Temario Breve</label>
                                    <textarea name="descripcion" class="form-control" rows="4" placeholder="Describe brevemente el contenido..."><?php echo $descripcion; ?></textarea>
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