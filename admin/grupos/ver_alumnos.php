<?php
session_start();
require_once '../../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// Validar ID del grupo
if (!isset($_GET['id'])) {
    header("location: index.php");
    exit;
}
$grupo_id = mysqli_real_escape_string($conn, $_GET['id']);

// 1. Obtener Info del Grupo
$sql_grupo = "SELECT codigo, grado, turno FROM grupos WHERE id = $grupo_id";
$res_grupo = mysqli_query($conn, $sql_grupo);
$grupo = mysqli_fetch_assoc($res_grupo);

// 2. Obtener Alumnos de este grupo
$sql_alumnos = "
    SELECT a.id, a.matricula, u.nombre, u.apellido_paterno, u.apellido_materno, u.email
    FROM alumnos a
    INNER JOIN users u ON a.user_id = u.id
    WHERE a.grupo_id = $grupo_id
    ORDER BY u.apellido_paterno ASC
";
$result = mysqli_query($conn, $sql_alumnos);
$total_alumnos = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Lista de Grupo | SmartClass</title>
    
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
                    <h4 class="mb-0 fw-bold text-primary">Detalle de Grupo</h4>
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

            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-25 rounded-3 p-2 me-3">
                                <i class="bi bi-people-fill fs-3"></i>
                            </div>
                            <div>
                                <h2 class="fw-bold mb-0">Grupo <?php echo htmlspecialchars($grupo['codigo']); ?></h2>
                                <p class="mb-0 opacity-75">
                                    <?php echo $grupo['grado']; ?>º Semestre &bull; Turno <?php echo $grupo['turno']; ?> &bull; <?php echo $total_alumnos; ?> Alumnos inscritos
                                </p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-light text-primary fw-bold rounded-pill px-4">
                            <i class="bi bi-arrow-left me-1"></i> Volver a Grupos
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 rounded-4">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold text-uppercase">Filtrar lista de este grupo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="buscadorAlumnos" class="form-control bg-light border-0" placeholder="Buscar por matrícula o nombre del alumno...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Alumno</th>
                                <th class="py-3">Matrícula</th>
                                <th class="py-3">Correo Electrónico</th>
                                <th class="py-3 text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="listaAlumnos">
                            <?php if ($total_alumnos == 0): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No hay alumnos asignados a este grupo actualmente.</td></tr>
                            <?php endif; ?>

                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-circle me-3">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <?php echo htmlspecialchars($row['apellido_paterno'] . ' ' . $row['apellido_materno'] . ' ' . $row['nombre']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 font-monospace">
                                        <?php echo htmlspecialchars($row['matricula']); ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($row['email']); ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="quitar_alumno.php?alumno_id=<?php echo $row['id']; ?>&grupo_id=<?php echo $grupo_id; ?>" 
                                       class="btn btn-sm btn-outline-danger border-0 rounded-pill px-3"
                                       onclick="return confirm('¿Sacar a este alumno del grupo? Pasará a estar Sin Asignar.')">
                                        <i class="bi bi-person-dash me-1"></i> Quitar del grupo
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top-0 py-3">
                    <small class="text-muted">Total de alumnos en lista: <strong><?php echo $total_alumnos; ?></strong></small>
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

    // Buscador en Tiempo Real para la lista de alumnos
    document.getElementById('buscadorAlumnos').addEventListener('keyup', function() {
        let valor = this.value.toLowerCase();
        let filas = document.querySelectorAll('#listaAlumnos tr');
        filas.forEach(fila => {
            let contenido = fila.textContent.toLowerCase();
            fila.style.display = contenido.includes(valor) ? '' : 'none';
        });
    });
</script>
</body>
</html>