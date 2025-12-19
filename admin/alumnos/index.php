<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// 2. LÓGICA DE FILTRADO
$filtro_estado = isset($_GET['filtro']) ? $_GET['filtro'] : 'activos'; // Por defecto solo mostramos ACTIVOS
$condicion_sql = "";

if ($filtro_estado == 'activos') {
    $condicion_sql = "WHERE u.is_active = 1";
} elseif ($filtro_estado == 'inactivos') {
    $condicion_sql = "WHERE u.is_active = 0";
} 
// Si es 'todos', no ponemos WHERE (o ponemos WHERE 1=1 si hubiera más condiciones)

// 3. CONSULTA SQL CON FILTRO
$sql = "
    SELECT 
        a.id as alumno_id, a.matricula, 
        u.nombre, u.apellido_paterno, u.apellido_materno, u.email, u.telefono, u.is_active,
        g.codigo as nombre_grupo, g.turno
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
    <title>Alumnos | SmartClass</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php' ?>

    <div id="page-content" class="w-100">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <button class="btn btn-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
                <h4 class="mb-0 fw-bold text-primary">Directorio de Alumnos</h4>
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
                        if($_GET['msg'] == 'creado') echo "¡Alumno registrado con éxito!";
                        if($_GET['msg'] == 'actualizado') echo "¡Datos del alumno actualizados!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                
                <div class="d-flex gap-2 w-100" style="max-width: 600px;">
                    
                    <form action="" method="GET">
                        <select name="filtro" class="form-select bg-white" onchange="this.form.submit()">
                            <option value="activos" <?php if($filtro_estado=='activos') echo 'selected'; ?>>🟢 Solo Activos</option>
                            <option value="inactivos" <?php if($filtro_estado=='inactivos') echo 'selected'; ?>>🔴 Solo Bajas</option>
                            <option value="todos" <?php if($filtro_estado=='todos') echo 'selected'; ?>>⚪ Mostrar Todos</option>
                        </select>
                    </form>

                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="buscador" class="form-control border-start-0 ps-0" placeholder="Buscar alumno...">
                    </div>
                </div>

                <a href="form.php" class="btn btn-primary px-4 rounded-pill shadow-sm">
                    <i class="bi bi-person-plus-fill me-2"></i> Nuevo Alumno
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Alumno</th>
                                    <th class="py-3">Matrícula</th>
                                    <th class="py-3">Grupo</th>
                                    <th class="py-3">Teléfono</th>
                                    <th class="py-3 text-center">Estado</th> 
                                    <th class="py-3 text-end pe-4">Editar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($total_registros == 0) {
                                    echo '<tr><td colspan="6" class="text-center py-5 text-muted">No se encontraron alumnos con este filtro.</td></tr>';
                                }

                                while ($row = mysqli_fetch_assoc($result)): 
                                    $nombre_completo = $row['nombre'] . ' ' . $row['apellido_paterno'];
                                    $iniciales = strtoupper(substr($row['nombre'], 0, 1) . substr($row['apellido_paterno'], 0, 1));
                                    
                                    // Opacidad visual para inactivos
                                    $opacity_class = ($row['is_active'] == 0) ? 'opacity-50' : '';
                                ?>

                                <tr class="<?php echo $opacity_class; ?>">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <span class="fw-bold"><?php echo $iniciales; ?></span>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo $nombre_completo; ?></div>
                                                <div class="small text-muted"><?php echo $row['email']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td><span class="badge bg-light text-dark border font-monospace"><?php echo $row['matricula']; ?></span></td>
                                    
                                    <td>
                                        <?php if($row['nombre_grupo']): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2">
                                                <?php echo $row['nombre_grupo']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-muted small"><?php echo $row['telefono'] ? $row['telefono'] : '--'; ?></td>
                                    
                                    <td class="text-center">
                                        <?php if($row['is_active'] == 1): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Baja</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-end pe-4">
                                        <a href="form.php?id=<?php echo $row['alumno_id']; ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar">
                                            <i class="bi bi-pencil-square fs-6"></i>
                                        </a>
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
    
    // Buscador JS (Funciona sobre los resultados ya filtrados)
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