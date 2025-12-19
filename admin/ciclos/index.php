<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD: Solo Admin (Con rebote inteligente)
if (!isset($_SESSION['loggedin'])) {
    header("location: ../../login.php");
    exit;
}

if ($_SESSION['role_id'] != 1) {
    if ($_SESSION['role_id'] == 3) header("location: ../../client/alumno/index.php");
    elseif ($_SESSION['role_id'] == 2) header("location: ../../client/profesor/index.php");
    else header("location: ../../login.php");
    exit;
}

// 2. Consulta a la Base de Datos
$sql = "SELECT * FROM ciclos_escolares ORDER BY activo DESC, fecha_inicio DESC";
$result = mysqli_query($conn, $sql);
$total_registros = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Ciclos Escolares | SmartClass</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .icon-circle {
            width: 40px; height: 40px;
            background-color: #f0f7ff; color: #0d6efd;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.02);
        }
        /* Estilo para ciclos inactivos */
        .fila-inactiva {
            background-color: #f8f9fa;
        }
        .fila-inactiva .icon-circle {
            background-color: #e9ecef; color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php'; ?>

    <div id="page-content" class="w-100">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-primary d-md-none me-2" id="btnToggleSidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <h4 class="mb-0 fw-bold text-primary">Gestión de Periodos Académicos</h4>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-sm-block">
                        <div class="small fw-bold"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Administrador</div>
                    </div>
                    <img src="../../assets/img/avatar.png" alt="Admin" class="rounded-circle border" width="38" height="38">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php 
                        if($_GET['msg'] == 'creado') echo "¡Periodo registrado correctamente!";
                        if($_GET['msg'] == 'actualizado') echo "¡Datos del ciclo actualizados!";
                        if($_GET['msg'] == 'eliminado') echo "Ciclo eliminado correctamente.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Error: </strong>
                    <?php 
                        if($_GET['error'] == 'es_activo') echo "No puedes eliminar el ciclo activo. Activa otro primero.";
                        if($_GET['error'] == 'tiene_datos') echo "El ciclo tiene grupos u horarios vinculados.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4 rounded-4">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-9">
                            <label class="small text-muted fw-bold text-uppercase">Filtro de periodos</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="buscador" class="form-control bg-light border-0" placeholder="Buscar por nombre o año (ej: 2025)...">
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end pt-3 pt-md-0">
                            <a href="form.php" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm fw-bold">
                                <i class="bi bi-calendar-plus-fill me-2"></i> Nuevo Ciclo
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-secondary small text-uppercase">
                                <th class="ps-4 py-3">Nombre del Ciclo</th>
                                <th class="py-3">Duración / Fechas</th>
                                <th class="py-3 text-center">Estatus</th>
                                <th class="py-3 text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaCiclos">
                            <?php 
                            if ($total_registros == 0) {
                                echo '<tr><td colspan="4" class="text-center py-5 text-muted">No hay ciclos registrados.</td></tr>';
                            }

                            while ($row = mysqli_fetch_assoc($result)): 
                                $es_activo = $row['activo'] == 1;
                                $clase_fila = !$es_activo ? 'fila-inactiva text-muted' : '';
                                $f_inicio = date("d/m/Y", strtotime($row['fecha_inicio']));
                                $f_fin = date("d/m/Y", strtotime($row['fecha_fin']));
                            ?>
                            <tr class="<?php echo $clase_fila; ?>">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-circle me-3">
                                            <i class="bi <?php echo $es_activo ? 'bi-calendar-check-fill' : 'bi-calendar-x'; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold mb-0 <?php echo $es_activo ? 'text-dark' : ''; ?>">
                                                <?php echo htmlspecialchars($row['nombre']); ?>
                                            </div>
                                            <small>ID: #<?php echo $row['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="small d-flex align-items-center gap-2">
                                        <span class="badge bg-white text-dark border-0 shadow-xs"><i class="bi bi-calendar-event me-1"></i><?php echo $f_inicio; ?></span>
                                        <i class="bi bi-arrow-right text-muted"></i>
                                        <span class="badge bg-white text-dark border-0 shadow-xs"><i class="bi bi-flag me-1"></i><?php echo $f_fin; ?></span>
                                    </div>
                                </td>
                                
                                <td class="text-center">
                                    <?php if($es_activo): ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3">En Curso</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-light text-muted border px-3">Finalizado</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-circle"
                                           onclick="return confirm('¿Eliminar este periodo académico?')"
                                           title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top-0 py-3 text-center text-md-start">
                    <small class="text-muted">Mostrando <strong><?php echo $total_registros; ?></strong> ciclos en el historial.</small>
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
             document.getElementById('wrapper').classList.toggle('toggled');
        });
    }

    // Buscador en tiempo real
    document.getElementById('buscador').addEventListener('keyup', function() {
        let valor = this.value.toLowerCase();
        let filas = document.querySelectorAll('#tablaCiclos tr');
        filas.forEach(fila => {
            let contenido = fila.textContent.toLowerCase();
            fila.style.display = contenido.includes(valor) ? '' : 'none';
        });
    });
</script>
</body>
</html>