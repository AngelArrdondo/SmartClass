<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// Obtener el ciclo activo
$sql_ciclo = "SELECT id, nombre FROM ciclos_escolares WHERE activo = 1 LIMIT 1";
$res_ciclo = mysqli_query($conn, $sql_ciclo);
$ciclo_activo = mysqli_fetch_assoc($res_ciclo);

// --- FILTRO DE GRUPO ---
$filtro_grupo = "";
$where_clause = "";

if (isset($_GET['grupo_id']) && $_GET['grupo_id'] != 'todos') {
    $grupo_selec = mysqli_real_escape_string($conn, $_GET['grupo_id']);
    $where_clause = "WHERE h.grupo_id = $grupo_selec";
    $filtro_grupo = $grupo_selec;
}

// --- CONSULTA MAESTRA ---
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

// --- PROCESAMIENTO PARA LA CUADRÍCULA VISUAL ---
$horario_visual = [];
$horas_eje = [];
$dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

$result_clon = mysqli_query($conn, $sql);
while ($row_v = mysqli_fetch_assoc($result_clon)) {
    $rango = date("H:i", strtotime($row_v['hora_inicio'])) . " - " . date("H:i", strtotime($row_v['hora_fin']));
    $horario_visual[$rango][$row_v['dia_semana']] = $row_v;
    if (!in_array($rango, $horas_eje)) $horas_eje[] = $rango;
}
sort($horas_eje);

$res_grupos = mysqli_query($conn, "SELECT id, codigo FROM grupos ORDER BY codigo ASC");
$grupos_lista = mysqli_fetch_all($res_grupos, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Horarios | SmartClass</title>
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
        .schedule-grid { background: #fff; border-radius: 16px; overflow: hidden; border: none; }
        .schedule-table { table-layout: fixed; width: 100%; margin-bottom: 0; }
        .schedule-table th { background: #f8f9fa; text-align: center; padding: 15px; font-size: 0.8rem; color: #6c757d; text-transform: uppercase; border-bottom: 1px solid #eee; }
        .schedule-table td { border: 1px solid #f8f9fa; height: 90px; vertical-align: top; padding: 8px; }
        .time-col { background: #fdfdfd; width: 100px; font-weight: bold; font-size: 0.75rem; text-align: center; vertical-align: middle !important; color: #0d6efd; }
        
        .class-card {
            background: #f0f7ff; border-left: 4px solid #0d6efd; border-radius: 8px;
            padding: 8px; height: 100%; cursor: pointer; transition: 0.2s ease;
        }
        .class-card:hover { background: #e2efff; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .class-title { font-weight: bold; font-size: 0.75rem; color: #084298; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .class-info { font-size: 0.65rem; color: #666; margin-top: 4px; }
        .empty-slot { color: #f0f0f0; text-align: center; padding-top: 25px; font-size: 1.2rem; }
        .table-hover tbody tr:hover { background-color: rgba(13, 110, 253, 0.02); }
    </style>
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php'; ?>

    <div id="page-content" class="w-100">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
                    <h4 class="mb-0 fw-bold text-primary">Gestión de Horarios</h4>
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

            <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
                <div class="card-body p-4 text-white">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h5 class="fw-bold mb-1"><i class="bi bi-magic me-2"></i> Generador Inteligente</h5>
                            <p class="mb-0 opacity-75 small">Ciclo escolar: <?php echo $ciclo_activo['nombre'] ?? 'Sin ciclo'; ?></p>
                        </div>
                        <div class="col-md-5">
                            <form action="generar_automatico.php" method="POST" class="d-flex gap-2 bg-white p-2 rounded-pill shadow">
                                <input type="hidden" name="ciclo_id" value="<?php echo $ciclo_activo['id'] ?? ''; ?>">
                                <select name="grupo_id" class="form-select border-0 bg-transparent" required>
                                    <option value="">Seleccionar grupo...</option>
                                    <?php foreach($grupos_lista as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo $g['codigo']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="return confirm('¿Reemplazar horario actual?')">Generar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 rounded-4">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-5">
                            <form action="" method="GET" id="formFiltro">
                                <label class="small text-muted fw-bold text-uppercase">Filtrar por Grupo</label>
                                <select name="grupo_id" class="form-select bg-light border-0" onchange="this.form.submit()">
                                    <option value="todos">Todos los Grupos</option>
                                    <?php foreach($grupos_lista as $g): ?>
                                        <option value="<?php echo $g['id']; ?>" <?php if($filtro_grupo == $g['id']) echo 'selected'; ?>><?php echo $g['codigo']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold text-uppercase">Búsqueda rápida</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="buscador" class="form-control bg-light border-0" placeholder="Materia o profesor...">
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end pt-4">
                            <a href="form.php" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">
                                <i class="bi bi-calendar-plus me-2"></i> Asignar Clase
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($filtro_grupo != "" && $filtro_grupo != "todos"): ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Horario del Grupo</h6>
                    <button onclick="window.print()" class="btn btn-sm btn-light rounded-pill px-3"><i class="bi bi-printer me-1"></i> Imprimir</button>
                </div>
                <div class="table-responsive">
                    <table class="table schedule-table">
                        <thead>
                            <tr>
                                <th class="time-col">Hora</th>
                                <?php foreach($dias_semana as $dia): ?>
                                    <th><?php echo $dia; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($horas_eje as $hora): ?>
                            <tr>
                                <td class="time-col"><?php echo $hora; ?></td>
                                <?php foreach($dias_semana as $dia): ?>
                                    <td>
                                        <?php if(isset($horario_visual[$hora][$dia])): 
                                            $c = $horario_visual[$hora][$dia]; ?>
                                            <div class="class-card" onclick="window.location.href='form.php?id=<?php echo $c['id']; ?>'">
                                                <span class="class-title"><?php echo $c['nombre_materia']; ?></span>
                                                <div class="class-info">
                                                    <div class="mb-1"><i class="bi bi-person-fill me-1"></i><?php echo $c['ape_profe']; ?></div>
                                                    <div><i class="bi bi-geo-alt-fill me-1"></i>Aula: <?php echo $c['codigo_salon']; ?></div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="empty-slot"><i class="bi bi-plus-circle"></i></div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-secondary small text-uppercase">
                                <th class="ps-4 py-3">Materia / Grupo</th>
                                <th class="py-3">Día y Horario</th>
                                <th class="py-3">Docente</th>
                                <th class="py-3">Aula</th>
                                <th class="py-3 text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaHorarios">
                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                $inicio = date("H:i", strtotime($row['hora_inicio']));
                                $fin = date("H:i", strtotime($row['hora_fin']));
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-circle me-3">
                                            <i class="bi bi-calendar-event"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold mb-0 text-dark"><?php echo $row['nombre_materia']; ?></div>
                                            <div class="small text-primary fw-bold"><?php echo $row['codigo_grupo']; ?> <span class="text-muted fw-normal">| <?php echo $row['turno']; ?></span></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 mb-1">
                                        <?php echo $row['dia_semana']; ?>
                                    </span>
                                    <div class="text-muted small fw-bold"><?php echo $inicio . ' - ' . $fin; ?></div>
                                </td>
                                <td>
                                    <div class="small fw-bold text-dark"><?php echo $row['nombre_profe'] . ' ' . $row['ape_profe']; ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace"><?php echo $row['codigo_salon']; ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle"><i class="bi bi-pencil"></i></a>
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('¿Quitar esta clase?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn) toggleBtn.addEventListener('click', () => document.getElementById('wrapper').classList.toggle('toggled'));

    document.getElementById('buscador').addEventListener('keyup', function() {
        let valor = this.value.toLowerCase();
        let filas = document.querySelectorAll('#tablaHorarios tr');
        filas.forEach(fila => {
            fila.style.display = fila.textContent.toLowerCase().includes(valor) ? '' : 'none';
        });
    });
</script>
</body>
</html>