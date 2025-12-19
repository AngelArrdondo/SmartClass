<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// 2. LÓGICA DE FILTRADO
$filtro_estado = isset($_GET['filtro']) ? $_GET['filtro'] : 'activos'; 
$condicion_sql = "";

if ($filtro_estado == 'activos') {
    $condicion_sql = "WHERE u.is_active = 1";
} elseif ($filtro_estado == 'inactivos') {
    $condicion_sql = "WHERE u.is_active = 0";
} 

// 3. CONSULTA SQL MEJORADA
$sql = "
    SELECT 
        a.id as alumno_id, a.matricula, 
        u.nombre, u.apellido_paterno, u.apellido_materno, u.email, u.telefono, u.is_active,
        g.codigo as nombre_grupo
    FROM alumnos a
    INNER JOIN users u ON a.user_id = u.id
    LEFT JOIN grupos g ON a.grupo_id = g.id
    $condicion_sql
    ORDER BY u.apellido_paterno ASC
";

$result = mysqli_query($conn, $sql);
$total_registros = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Directorio de Alumnos | SmartClass</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            background-color: #e9ecef;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border-radius: 50%;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.02);
        }
    </style>
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php' ?>

    <div id="page-content" class="w-100">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-primary d-md-none me-2" id="btnToggleSidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <h4 class="mb-0 fw-bold text-primary">Directorio de Alumnos</h4>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3 d-none d-sm-block">
                        <div class="small fw-bold"><?php echo $_SESSION['user_name']; ?></div>
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
                        if($_GET['msg'] == 'creado') echo "¡Alumno registrado con éxito!";
                        if($_GET['msg'] == 'actualizado') echo "¡Datos del alumno actualizados!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm mb-4 rounded-4">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <form action="" method="GET" id="formFiltro">
                                <label class="small text-muted fw-bold text-uppercase">Estado</label>
                                <select name="filtro" class="form-select border-0 bg-light" onchange="this.form.submit()">
                                    <option value="activos" <?php if($filtro_estado=='activos') echo 'selected'; ?>>🟢 Solo Activos</option>
                                    <option value="inactivos" <?php if($filtro_estado=='inactivos') echo 'selected'; ?>>🔴 Solo Bajas</option>
                                    <option value="todos" <?php if($filtro_estado=='todos') echo 'selected'; ?>>⚪ Mostrar Todos</option>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold text-uppercase">Búsqueda rápida</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="buscador" class="form-control bg-light border-0" placeholder="Nombre, correo o matrícula...">
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end pt-3 pt-md-0">
                            <a href="form.php" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm fw-bold">
                                <i class="bi bi-person-plus-fill me-2"></i> Nuevo Alumno
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
                                <th class="ps-4 py-3">Alumno</th>
                                <th class="py-3">Matrícula</th>
                                <th class="py-3">Grupo</th>
                                <th class="py-3">Contacto</th>
                                <th class="py-3 text-center">Estado</th> 
                                <th class="py-3 text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaAlumnos">
                            <?php if ($total_registros == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                                        <p class="mt-3 text-muted">No se encontraron alumnos con el filtro seleccionado.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                $nombre_completo = $row['nombre'] . ' ' . $row['apellido_paterno'] . ' ' . $row['apellido_materno'];
                                $iniciales = strtoupper(substr($row['nombre'], 0, 1) . substr($row['apellido_paterno'], 0, 1));
                                $clase_inactivo = ($row['is_active'] == 0) ? 'text-muted' : '';
                            ?>
                            <tr class="<?php echo $clase_inactivo; ?>">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3"><?php echo $iniciales; ?></div>
                                        <div>
                                            <div class="fw-bold mb-0"><?php echo $nombre_completo; ?></div>
                                            <small class="text-muted"><?php echo $row['email']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><code class="fw-bold text-dark"><?php echo $row['matricula']; ?></code></td>
                                <td>
                                    <?php if($row['nombre_grupo']): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3">
                                            <?php echo $row['nombre_grupo']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="small text-muted italic">No asignado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small"><i class="bi bi-telephone me-1"></i> <?php echo $row['telefono'] ?: 'N/A'; ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if($row['is_active'] == 1): ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3">Activo</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3">Baja</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="form.php?id=<?php echo $row['alumno_id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top-0 py-3">
                    <small class="text-muted">Mostrando <strong><?php echo $total_registros; ?></strong> alumnos.</small>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Sidebar
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn){
        toggleBtn.addEventListener('click', () => {
             document.getElementById('wrapper').classList.toggle('toggled');
        });
    }
    
    // Buscador en Tiempo Real
    document.getElementById('buscador').addEventListener('keyup', function() {
        let valor = this.value.toLowerCase();
        let filas = document.querySelectorAll('#tablaAlumnos tr');
        
        filas.forEach(fila => {
            let contenido = fila.textContent.toLowerCase();
            fila.style.display = contenido.includes(valor) ? '' : 'none';
        });
    });
</script>
</body>
</html>