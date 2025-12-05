<?php
session_start();
require_once '../../config/db.php'; 

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// --- LÓGICA DE CONTROL (CREAR vs EDITAR) ---
$modo_edicion = false;
$titulo = "Nuevo Grupo";
$btn_texto = "Crear Grupo";

$id = "";
$codigo = "";
$grado = "";
$turno = "";
$ciclo_id = ""; 

// 1. OBTENER CICLOS ESCOLARES (Para el Select)
// Traemos todos, pero idealmente ordenados por fecha
$sql_ciclos = "SELECT id, nombre, activo FROM ciclos_escolares ORDER BY fecha_inicio DESC";
$res_ciclos = mysqli_query($conn, $sql_ciclos);

// 2. MODO EDICIÓN
if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Grupo";
    $btn_texto = "Actualizar Grupo";
    $id = $_GET['id'];
    
    // TRAER DATOS REALES
    $sql = "SELECT * FROM grupos WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $codigo = $fila['codigo'];
            $grado = $fila['grado'];
            $turno = $fila['turno'];
            $ciclo_id = $fila['ciclo_id'];
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
                    <i class="bi bi-arrow-left me-1"></i> Volver a Grupos
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6"> 
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            
                            <h5 class="fw-bold mb-4 text-secondary">Datos Académicos</h5>

                            <form action="save_grupo.php" method="POST">
                                
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted">Ciclo Escolar <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-calendar-range"></i></span>
                                        <select name="ciclo_id" class="form-select" required>
                                            <option value="" selected disabled>Selecciona el periodo...</option>
                                            
                                            <?php while($c = mysqli_fetch_assoc($res_ciclos)): ?>
                                                <?php 
                                                    $es_activo = ($c['activo'] == 1) ? '(ACTIVO)' : '';
                                                    $selected = ($ciclo_id == $c['id']) ? 'selected' : '';
                                                ?>
                                                <option value="<?php echo $c['id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo $c['nombre'] . ' ' . $es_activo; ?>
                                                </option>
                                            <?php endwhile; ?>

                                        </select>
                                    </div>
                                    <div class="form-text">El grupo debe pertenecer a un ciclo escolar válido.</div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Grado / Semestre <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">#</span>
                                            <input type="number" name="grado" class="form-control" placeholder="Ej. 2" min="1" max="12" value="<?php echo $grado; ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Turno <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-clock"></i></span>
                                            <select name="turno" class="form-select" required>
                                                <option value="" selected disabled>Elegir...</option>
                                                <option value="Matutino" <?php if($turno == 'Matutino') echo 'selected'; ?>>Matutino</option>
                                                <option value="Vespertino" <?php if($turno == 'Vespertino') echo 'selected'; ?>>Vespertino</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted">Código Identificador <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" name="codigo" class="form-control bg-light" placeholder="Ej. 2A-2025" value="<?php echo $codigo; ?>" required>
                                    </div>
                                    <div class="form-text">Debe ser único. Sugerencia: [Grado][Letra]-[Año]</div>
                                </div>

                                <hr class="my-4 text-muted opacity-25">

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-light border px-4">Cancelar</a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold rounded-3">
                                        <i class="bi bi-save me-2"></i> <?php echo $btn_texto; ?>
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