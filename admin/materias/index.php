<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// CONSULTA SQL (Asegúrate de haber ejecutado el ALTER TABLE para horas_semanales)
$sql = "SELECT * FROM materias ORDER BY nombre ASC";
$result = mysqli_query($conn, $sql);
$total_registros = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Materias | SmartClass</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .icon-circle {
            width: 42px; height: 42px;
            background-color: #f0f7ff; color: #0d6efd;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; /* Un poco más moderno que el círculo perfecto */
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.03);
            transition: background-color 0.2s ease;
        }
        .badge-hora {
            background-color: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            font-weight: 600;
        }
        .progress { background-color: #e9ecef; border-radius: 10px; }
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
                    <h4 class="mb-0 fw-bold text-primary">Catálogo de Materias</h4>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-sm-block">
                        <div class="small fw-bold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Administrador</div>
                    </div>
                    <img src="../../assets/img/avatar.png" alt="Admin" class="rounded-circle border" width="38" height="38">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>
                            <?php 
                                if($_GET['msg'] == 'creado') echo "¡Materia registrada exitosamente!";
                                if($_GET['msg'] == 'actualizado') echo "¡Materia actualizada correctamente!";
                                if($_GET['msg'] == 'eliminado') echo "Materia eliminada del catálogo.";
                            ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4 rounded-4">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="buscador" class="form-control bg-light border-0" placeholder="Buscar por código, nombre o descripción...">
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <a href="form.php" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm fw-bold">
                                <i class="bi bi-journal-plus me-1"></i> Nueva Materia
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
                                <th class="ps-4 py-3" style="width: 35%;">Materia</th>
                                <th class="py-3">Código</th>
                                <th class="py-3">Carga Horaria</th>
                                <th class="py-3">Créditos</th>
                                <th class="py-3 text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaMaterias">
                            <?php if ($total_registros == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <img src="../../assets/img/empty.svg" alt="Vacío" width="80" class="mb-3 opacity-50">
                                        <p class="text-muted">No se encontraron materias en el sistema.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): 
                                    $creditos = (int)$row['creditos'];
                                    $horas = (int)($row['horas_semanales'] ?? 0);
                                    $porcentaje = min(($creditos / 10) * 100, 100);
                                    
                                    $barra_color = 'bg-info';
                                    if($creditos >= 8) $barra_color = 'bg-success';
                                    if($creditos <= 4) $barra_color = 'bg-warning';
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-circle me-3">
                                                <i class="bi bi-journal-bookmark-fill"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($row['nombre']); ?></div>
                                                <div class="text-muted small text-truncate" style="max-width: 250px;">
                                                    <?php echo htmlspecialchars($row['descripcion'] ?? 'Sin descripción'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 font-monospace">
                                            <?php echo htmlspecialchars($row['codigo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-hora px-3 py-2 rounded-pill">
                                            <i class="bi bi-calendar3 me-1"></i> <?php echo $horas; ?> hrs / sem
                                        </span>
                                    </td>
                                    <td>
                                        <div style="width: 100px;">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span class="fw-bold"><?php echo $creditos; ?></span>
                                                <span class="text-muted">Créd.</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar <?php echo $barra_color; ?> shadow-none" 
                                                     role="progressbar" style="width: <?php echo $porcentaje; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-light border rounded-circle me-2" title="Editar">
                                                <i class="bi bi-pencil text-primary"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-light border rounded-circle"
                                               onclick="return confirm('¿Estás seguro de que deseas eliminar esta materia? Esta acción no se puede deshacer.')"
                                               title="Eliminar">
                                                <i class="bi bi-trash text-danger"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top-0 py-3 text-center text-sm-start">
                    <small class="text-muted">Mostrando <strong><?php echo $total_registros; ?></strong> registros en total.</small>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar Toggle
    document.getElementById('btnToggleSidebar')?.addEventListener('click', () => {
        document.getElementById('wrapper').classList.toggle('toggled');
    });

    // Buscador Inteligente
    document.getElementById('buscador').addEventListener('keyup', function() {
        let valor = this.value.toLowerCase().trim();
        let filas = document.querySelectorAll('#tablaMaterias tr');
        
        filas.forEach(fila => {
            // No procesar la fila de "No hay registros"
            if (fila.cells.length < 2) return; 
            
            let texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? '' : 'none';
        });
    });
</script>
</body>
</html>