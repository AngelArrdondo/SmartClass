<?php
session_start();
require_once '../../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// CONSULTA SQL
// Traemos info del Grupo, del Ciclo, y contamos los alumnos reales
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
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php'; ?>

    <div id="page-content" class="w-100">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <button class="btn btn-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
                <h4 class="mb-0 fw-bold text-primary">Gestión de Grupos</h4>
                <div class="d-flex align-items-center">
                    <span class="d-none d-md-block small text-muted me-2"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                    <img src="../../assets/img/avatar.png" alt="Admin" class="rounded-circle border" width="35" height="35">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        if($_GET['msg'] == 'creado') echo "¡Grupo creado exitosamente!";
                        if($_GET['msg'] == 'actualizado') echo "¡Grupo actualizado!";
                        if($_GET['msg'] == 'eliminado') echo "Grupo eliminado correctamente.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                    <?php if($_GET['error'] == 'tiene_alumnos') echo "No se puede eliminar: El grupo tiene alumnos inscritos."; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div class="input-group" style="max-width: 400px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="buscador" class="form-control border-start-0 ps-0" placeholder="Buscar grupo...">
                </div>
                <a href="form.php" class="btn btn-primary px-4 rounded-pill shadow-sm">
                    <i class="bi bi-plus-lg me-2"></i> Nuevo Grupo
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Código</th>
                                    <th class="py-3">Grado y Grupo</th>
                                    <th class="py-3">Ciclo Escolar</th>
                                    <th class="py-3">Turno</th>
                                    <th class="py-3 text-center">Alumnos</th> <th class="py-3 text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($total_registros == 0) {
                                    echo '<tr><td colspan="6" class="text-center py-5 text-muted">No hay grupos registrados.</td></tr>';
                                }

                                while ($row = mysqli_fetch_assoc($result)): 
                                    // Determinar si el ciclo es el activo
                                    $badge_ciclo_class = ($row['ciclo_activo'] == 1) ? 'bg-success bg-opacity-10 text-success border-success' : 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><?php echo $row['codigo']; ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo $row['grado']; ?>º Semestre</div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badge_ciclo_class; ?> border px-2">
                                            <?php echo $row['nombre_ciclo']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['turno'] == 'Matutino'): ?>
                                            <span class="badge rounded-pill text-bg-warning text-white">
                                                <i class="bi bi-sun-fill me-1"></i> Matutino
                                            </span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-indigo text-white" style="background-color: #6610f2;">
                                                <i class="bi bi-moon-stars-fill me-1"></i> Vespertino
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <a href="ver_alumnos.php?id=<?php echo $row['id']; ?>" class="text-decoration-none" title="Ver lista de asistencia">
                                            <span class="badge bg-light text-dark border rounded-pill px-3 shadow-sm" style="cursor: pointer;">
                                                <i class="bi bi-people-fill me-1 text-primary"></i> <?php echo $row['total_alumnos']; ?>
                                            </span>
                                        </a>
                                    </td>

                                    <td class="text-end pe-4">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary border-0"><i class="bi bi-pencil-square"></i></a>
                                        
                                        <?php if($row['total_alumnos'] == 0): ?>
                                            <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger border-0"
                                               onclick="return confirm('¿Eliminar este grupo definitivamente?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary border-0" disabled title="No se puede borrar porque tiene alumnos">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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
    // Buscador
    document.getElementById('buscador').addEventListener('keyup', function() {
        let filtro = this.value.toLowerCase();
        let filas = document.querySelectorAll('tbody tr');
        filas.forEach(fila => {
            let texto = fila.innerText.toLowerCase();
            fila.style.display = texto.includes(filtro) ? '' : 'none';
        });
    });
</script>
</body>
</html>