<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// --- FILTRO DE GRUPO ---
$filtro_grupo = "";
$where_clause = "";

if (isset($_GET['grupo_id']) && $_GET['grupo_id'] != 'todos') {
    $grupo_selec = $_GET['grupo_id'];
    $where_clause = "WHERE h.grupo_id = $grupo_selec";
    $filtro_grupo = $grupo_selec;
}

// --- CONSULTA MAESTRA (5 TABLAS) ---
$sql = "
    SELECT 
        h.id, h.dia_semana, h.hora_inicio, h.hora_fin,
        g.codigo as codigo_grupo, g.turno,
        m.nombre as nombre_materia,
        s.codigo as codigo_salon,
        u.nombre as nombre_profe, u.apellido_paterno as ape_profe
    FROM horarios h
    INNER JOIN grupos g ON h.grupo_id = g.id
    INNER JOIN materias m ON h.materia_id = m.id
    INNER JOIN salones s ON h.salon_id = s.id
    INNER JOIN profesores p ON h.profesor_id = p.id
    INNER JOIN users u ON p.user_id = u.id
    $where_clause
    ORDER BY FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'), h.hora_inicio ASC
";

$result = mysqli_query($conn, $sql);
$total_registros = mysqli_num_rows($result);

// Obtener lista de grupos para el filtro
$res_grupos = mysqli_query($conn, "SELECT id, codigo FROM grupos ORDER BY codigo ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Horarios Escolares | SmartClass</title>
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
                <h4 class="mb-0 fw-bold text-primary">Gestión de Horarios</h4>
                <div class="d-flex align-items-center">
                    <span class="d-none d-md-block small text-muted me-2"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                    <img src="../../assets/img/avatar.png" alt="Admin" class="rounded-circle border" width="35" height="35">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php 
                        if($_GET['msg'] == 'creado') echo "¡Clase asignada correctamente!";
                        if($_GET['msg'] == 'actualizado') echo "¡Horario actualizado!";
                        if($_GET['msg'] == 'eliminado') echo "Clase eliminada del horario.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                
                <div class="d-flex gap-2 w-100" style="max-width: 600px;">
                    <form action="" method="GET" class="d-flex w-100 gap-2">
                        <select name="grupo_id" class="form-select bg-white w-auto" onchange="this.form.submit()">
                            <option value="todos">Todos los Grupos</option>
                            <?php while($g = mysqli_fetch_assoc($res_grupos)): ?>
                                <option value="<?php echo $g['id']; ?>" <?php if($filtro_grupo == $g['id']) echo 'selected'; ?>>
                                    <?php echo $g['codigo']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                    
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="buscador" class="form-control border-start-0 ps-0" placeholder="Buscar materia o profesor...">
                    </div>
                </div>

                <a href="form.php" class="btn btn-primary px-4 rounded-pill shadow-sm">
                    <i class="bi bi-calendar-plus me-2"></i> Asignar Clase
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Grupo</th>
                                    <th class="py-3">Día y Hora</th>
                                    <th class="py-3">Materia</th>
                                    <th class="py-3">Docente Asignado</th>
                                    <th class="py-3">Aula</th>
                                    <th class="py-3 text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($total_registros == 0) {
                                    echo '<tr><td colspan="6" class="text-center py-5 text-muted">No hay clases programadas.</td></tr>';
                                }

                                while ($row = mysqli_fetch_assoc($result)): 
                                    // Formato de hora (quitar segundos)
                                    $inicio = date("H:i", strtotime($row['hora_inicio']));
                                    $fin = date("H:i", strtotime($row['hora_fin']));
                                    
                                    // Colores para días
                                    $color_dia = 'primary';
                                    if($row['dia_semana'] == 'Martes') $color_dia = 'success';
                                    if($row['dia_semana'] == 'Miercoles') $color_dia = 'danger';
                                    if($row['dia_semana'] == 'Jueves') $color_dia = 'warning text-dark';
                                    if($row['dia_semana'] == 'Viernes') $color_dia = 'info text-dark';
                                    
                                    // Iniciales Profe
                                    $ini_profe = strtoupper(substr($row['nombre_profe'], 0, 1) . substr($row['ape_profe'], 0, 1));
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-primary"><?php echo $row['codigo_grupo']; ?></span>
                                        <div class="small text-muted"><?php echo $row['turno']; ?></div>
                                    </td>
                                    
                                    <td>
                                        <span class="badge bg-<?php echo $color_dia; ?> bg-opacity-10 text-<?php echo str_replace(' text-dark', '', $color_dia); ?> border border-<?php echo str_replace(' text-dark', '', $color_dia); ?> mb-1">
                                            <?php echo $row['dia_semana']; ?>
                                        </span>
                                        <div class="fw-bold text-dark"><?php echo $inicio . ' - ' . $fin; ?></div>
                                    </td>
                                    
                                    <td class="fw-semibold"><?php echo $row['nombre_materia']; ?></td>
                                    
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle border d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                <?php echo $ini_profe; ?>
                                            </div>
                                            <span class="small"><?php echo $row['nombre_profe'] . ' ' . $row['ape_profe']; ?></span>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace"><?php echo $row['codigo_salon']; ?></span>
                                    </td>
                                    
                                    <td class="text-end pe-4">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger border-0"
                                           onclick="return confirm('¿Quitar esta clase del horario?')"
                                           title="Eliminar">
                                            <i class="bi bi-trash"></i>
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