<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// --- AGREGACIÓN: Obtener la foto del administrador logueado ---
$user_id = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT foto FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($query_user);

// Subimos dos niveles (../../) para llegar a assets desde admin/salones/
$base_img_path = "../../assets/img/";
$foto_perfil = !empty($user_data['foto']) ? $base_img_path . "profiles/" . $user_data['foto'] : $base_img_path . "avatar.png";
// --------------------------------------------------------------

// 2. CONSULTA SQL
$sql = "SELECT * FROM salones ORDER BY codigo ASC";
$result = mysqli_query($conn, $sql);
$total_registros = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Salones | SmartClass</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .icon-circle {
            width: 40px; height: 40px;
            background-color: #f0f7ff; color: #0d6efd;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; /* Esquinas ligeramente redondeadas para salones */
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.02);
        }
        .fila-mantenimiento { background-color: #fcfcfc; }
        .fila-mantenimiento .icon-circle { background-color: #e9ecef; color: #6c757d; }
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
                    <h4 class="mb-0 fw-bold text-primary">Gestión de Espacios y Aulas</h4>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-sm-block">
                        <div class="small fw-bold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Administrador</div>
                    </div>
                    <img src="<?php echo $foto_perfil; ?>" alt="Admin" class="rounded-circle border" width="38" height="38" style="object-fit: cover;">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php 
                        if($_GET['msg'] == 'creado') echo "¡Salón registrado correctamente!";
                        if($_GET['msg'] == 'actualizado') echo "¡Datos del salón actualizados!";
                        if($_GET['msg'] == 'eliminado') echo "Salón eliminado.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4 rounded-4">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-9">
                            <label class="small text-muted fw-bold text-uppercase">Búsqueda rápida</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="buscador" class="form-control bg-light border-0" placeholder="Buscar por código, nombre o edificio...">
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end pt-3 pt-md-0">
                            <a href="form.php" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm fw-bold">
                                <i class="bi bi-house-add-fill me-2"></i> Nuevo Salón
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
                                <th class="ps-4 py-3">Código / Salón</th>
                                <th class="py-3">Ubicación</th>
                                <th class="py-3 text-center">Estado</th>
                                <th class="py-3">Recursos</th>
                                <th class="py-3 text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaSalones">
                            <?php if ($total_registros == 0): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No hay salones registrados.</td></tr>
                            <?php endif; ?>

                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                $recursos_array = !empty($row['recursos']) ? explode(',', $row['recursos']) : [];
                                $esta_activo = $row['is_active'] == 1;
                                $clase_fila = !$esta_activo ? 'fila-mantenimiento text-muted' : '';
                            ?>
                            <tr class="<?php echo $clase_fila; ?>">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-circle me-3">
                                            <i class="bi <?php echo $esta_activo ? 'bi-door-open-fill' : 'bi-tools'; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold mb-0 <?php echo $esta_activo ? 'text-dark' : ''; ?>">
                                                <?php echo htmlspecialchars($row['codigo']); ?>
                                            </div>
                                            <small><?php echo htmlspecialchars($row['nombre']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="small">
                                        <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($row['ubicacion']); ?>
                                    </div>
                                </td>
                                
                                <td class="text-center">
                                    <?php if($esta_activo): ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3">Disponible</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3">Mantenimiento</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php if(empty($recursos_array)): ?>
                                            <small class="text-muted">--</small>
                                        <?php else: ?>
                                            <?php foreach($recursos_array as $rec): ?>
                                                <span class="badge <?php echo $esta_activo ? 'bg-info-subtle text-info border-info-subtle' : 'bg-light text-muted border'; ?> border px-2">
                                                    <?php echo trim($rec); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-circle"
                                           onclick="return confirm('¿Eliminar este salón?')"
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
                <div class="card-footer bg-white border-top-0 py-3">
                    <small class="text-muted">Mostrando <strong><?php echo $total_registros; ?></strong> espacios registrados.</small>
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

    // Buscador en Tiempo Real
    document.getElementById('buscador').addEventListener('keyup', function() {
        let valor = this.value.toLowerCase();
        let filas = document.querySelectorAll('#tablaSalones tr');
        filas.forEach(fila => {
            // Saltamos la fila de "No hay registros" si existe
            if(fila.cells.length < 2) return;
            let contenido = fila.textContent.toLowerCase();
            fila.style.display = contenido.includes(valor) ? '' : 'none';
        });
    });
</script>
</body>
</html>