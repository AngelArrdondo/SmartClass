<?php
session_start();
require_once '../../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// CONSULTA SQL
$sql = "
    SELECT 
        g.id, g.codigo, g.grado, g.turno,
        c.nombre as nombre_ciclo, c.activo as ciclo_activo,
        (SELECT COUNT(*) FROM alumnos a WHERE a.grupo_id = g.id) as total_alumnos
    FROM grupos g
    INNER JOIN ciclos_escolares c ON g.ciclo_id = c.id
    ORDER BY g.grado ASC, g.codigo ASC
";
$result = mysqli_query($conn, $sql);
$total_registros = mysqli_num_rows($result);
// --- NUEVO: Obtener la foto del admin ---
$user_id = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT foto FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($query_user);

// Subimos dos niveles (../../) para llegar a assets desde admin/grupos/
$base_img_path = "../../assets/img/";
$foto_perfil = !empty($user_data['foto']) ? $base_img_path . "profiles/" . $user_data['foto'] : $base_img_path . "avatar.png";
// ---------------------------------------
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Grupos | SmartClass</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .icon-circle {
            width: 40px;
            height: 40px;
            background-color: #f0f7ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.02);
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
                    <h4 class="mb-0 fw-bold text-primary">Gestión de Grupos</h4>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-sm-block">
                        <div class="small fw-bold"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
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
                        if($_GET['msg'] == 'creado') echo "¡Grupo creado exitosamente!";
                        if($_GET['msg'] == 'actualizado') echo "¡Grupo actualizado!";
                        if($_GET['msg'] == 'eliminado') echo "Grupo eliminado correctamente.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                    <?php if($_GET['error'] == 'tiene_alumnos') echo "No se puede eliminar: El grupo tiene alumnos inscritos."; ?>
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
                                <input type="text" id="buscador" class="form-control bg-light border-0" placeholder="Buscar por código, grado o ciclo...">
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end pt-3 pt-md-0">
                            <a href="form.php" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm fw-bold">
                                <i class="bi bi-plus-lg me-2"></i> Nuevo Grupo
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
                                <th class="ps-4 py-3">Grupo</th>
                                <th class="py-3">Ciclo Escolar</th>
                                <th class="py-3">Turno</th>
                                <th class="py-3 text-center">Alumnos</th>
                                <th class="py-3 text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaGrupos">
                            <?php 
                            if ($total_registros == 0) {
                                echo '<tr><td colspan="5" class="text-center py-5 text-muted">No hay grupos registrados.</td></tr>';
                            }

                            while ($row = mysqli_fetch_assoc($result)): 
                                $badge_ciclo_class = ($row['ciclo_activo'] == 1) ? 'bg-success-subtle text-success border-success-subtle' : 'bg-secondary-subtle text-secondary border-secondary-subtle';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-circle me-3">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold mb-0 text-primary"><?php echo htmlspecialchars($row['codigo']); ?></div>
                                            <div class="small text-muted"><?php echo $row['grado']; ?>º Semestre</div>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="badge <?php echo $badge_ciclo_class; ?> border px-3">
                                        <?php echo htmlspecialchars($row['nombre_ciclo']); ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <?php if($row['turno'] == 'Matutino'): ?>
                                        <span class="text-warning fw-bold small">
                                            <i class="bi bi-sun-fill me-1"></i> Matutino
                                        </span>
                                    <?php else: ?>
                                        <span class="fw-bold small" style="color: #6610f2;">
                                            <i class="bi bi-moon-stars-fill me-1"></i> Vespertino
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <a href="ver_alumnos.php?id=<?php echo $row['id']; ?>" class="text-decoration-none">
                                        <span class="badge bg-light text-dark border rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-person-check-fill me-1 text-primary"></i> <?php echo $row['total_alumnos']; ?>
                                        </span>
                                    </a>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if($row['total_alumnos'] == 0): ?>
                                            <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-circle"
                                               onclick="return confirm('¿Eliminar este grupo definitivamente?')"
                                               title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary rounded-circle" disabled title="No se puede borrar porque tiene alumnos">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top-0 py-3">
                    <small class="text-muted">Total de registros: <strong><?php echo $total_registros; ?></strong> grupos.</small>
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
             document.getElementById('wrapper').classList.toggle('toggled');
        });
    }

    // Buscador en Tiempo Real
    document.getElementById('buscador').addEventListener('keyup', function() {
        let valor = this.value.toLowerCase();
        let filas = document.querySelectorAll('#tablaGrupos tr');
        filas.forEach(fila => {
            let contenido = fila.textContent.toLowerCase();
            fila.style.display = contenido.includes(valor) ? '' : 'none';
        });
    });
</script>
</body>
</html>