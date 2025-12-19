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
$turno_actual = "Matutino";

if (isset($_GET['grupo_id']) && $_GET['grupo_id'] != 'todos') {
    $grupo_selec = mysqli_real_escape_string($conn, $_GET['grupo_id']);
    $where_clause = "WHERE h.grupo_id = $grupo_selec";
    $filtro_grupo = $grupo_selec;

    // Obtener turno para definir bloques de recreo
    $q_turno = mysqli_query($conn, "SELECT turno FROM grupos WHERE id = $grupo_selec");
    $d_turno = mysqli_fetch_assoc($q_turno);
    $turno_actual = $d_turno['turno'] ?? 'Matutino';
}

// Configuración de bloques según turno (Para la vista visual con recreo)
if($turno_actual == 'Vespertino') {
    $bloques_vista = ['14:00 - 15:00','15:00 - 16:00','16:00 - 17:00','17:00 - 18:00 (RECESO)','18:00 - 19:00','19:00 - 20:00','20:00 - 21:00'];
} else {
    $bloques_vista = ['07:00 - 08:00','08:00 - 09:00','09:00 - 10:00','10:00 - 11:00 (RECESO)','11:00 - 12:00','12:00 - 13:00','13:00 - 14:00'];
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

// --- PROCESAMIENTO PARA LA CUADRÍCULA VISUAL ---
$horario_visual = [];
$dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

$result_clon = mysqli_query($conn, $sql);
while ($row_v = mysqli_fetch_assoc($result_clon)) {
    $rango = date("H:i", strtotime($row_v['hora_inicio'])) . " - " . date("H:i", strtotime($row_v['hora_fin']));
    $horario_visual[$rango][$row_v['dia_semana']] = $row_v;
}

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
        .icon-circle { width: 40px; height: 40px; background-color: #f0f7ff; color: #0d6efd; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        .schedule-table { table-layout: fixed; width: 100%; margin-bottom: 0; border-collapse: separate; border-spacing: 0; }
        .schedule-table th { background: #f8f9fa; text-align: center; padding: 15px; font-size: 0.8rem; color: #6c757d; text-transform: uppercase; border-bottom: 1px solid #eee; }
        .schedule-table td { border: 1px solid #f8f9fa; height: 100px; vertical-align: top; padding: 8px; }
        .time-col { background: #fdfdfd; width: 120px; font-weight: bold; font-size: 0.75rem; text-align: center; vertical-align: middle !important; color: #0d6efd; border-right: 1px solid #eee !important; }
        
        /* Drag & Drop Styles */
        .dropzone { height: 100%; width: 100%; min-height: 80px; transition: background 0.2s; }
        .receso-row { background-color: #fff8e1 !important; height: 50px !important; text-align: center; color: #b08900; vertical-align: middle !important; font-size: 0.7rem; font-weight: bold; }
        
        .class-card {
            background: #f0f7ff; border-left: 4px solid #0d6efd; border-radius: 8px;
            padding: 8px; height: 100%; cursor: move; transition: 0.2s ease;
        }
        .class-card:hover { background: #e2efff; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .class-title { font-weight: bold; font-size: 0.72rem; color: #084298; display: block; overflow: hidden; text-overflow: ellipsis; }
        .class-info { font-size: 0.62rem; color: #666; margin-top: 4px; }
        .sortable-ghost { opacity: 0.3; background: #0d6efd !important; border-radius: 8px; }
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
                    <img src="../../assets/img/avatar.png" class="rounded-circle border" width="38" height="38">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
                <div class="card-body p-4 text-white">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h5 class="fw-bold mb-1"><i class="bi bi-magic me-2"></i> Generador Inteligente</h5>
                            <p class="mb-0 opacity-75 small">Ciclo escolar activo: <?php echo $ciclo_activo['nombre'] ?? 'Sin ciclo'; ?></p>
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
                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Generar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 rounded-4">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-5">
                            <form action="" method="GET">
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
                            <input type="text" id="buscador" class="form-control bg-light border-0" placeholder="Materia o profesor...">
                        </div>
                        <div class="col-md-3 text-md-end pt-4">
                            <a href="form.php" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">Asignar Clase</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($filtro_grupo != "" && $filtro_grupo != "todos"): ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Horario Semanal</h6>
                    <span class="badge bg-info-subtle text-info rounded-pill">Modo Edición: Arrastra para mover</span>
                </div>
                <div class="table-responsive">
                    <table class="table schedule-table">
                        <thead>
                            <tr>
                                <th class="time-col">Hora</th>
                                <?php foreach($dias_semana as $dia): ?><th><?php echo $dia; ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bloques_vista as $bloque): 
                                $es_receso = strpos($bloque, 'RECESO') !== false;
                                $rango_limpio = trim(explode('(', $bloque)[0]);
                            ?>
                            <tr class="<?php echo $es_receso ? 'receso-row' : ''; ?>">
                                <td class="time-col"><?php echo $bloque; ?></td>
                                <?php foreach($dias_semana as $dia): ?>
                                    <td class="<?php echo !$es_receso ? 'dropzone' : ''; ?>" 
                                        data-dia="<?php echo $dia; ?>" 
                                        data-hora="<?php echo explode(' - ', $rango_limpio)[0]; ?>">
                                        
                                        <?php if($es_receso): ?>
                                            <i class="bi bi-cup-hot me-1"></i> RECESO
                                        <?php elseif(isset($horario_visual[$rango_limpio][$dia])): 
                                            $c = $horario_visual[$rango_limpio][$dia]; ?>
                                            <div class="class-card" data-id="<?php echo $c['id']; ?>" onclick="if(event.target == this) window.location.href='form.php?id=<?php echo $c['id']; ?>'">
                                                <span class="class-title"><?php echo $c['nombre_materia']; ?></span>
                                                <div class="class-info">
                                                    <div><i class="bi bi-person me-1"></i><?php echo $c['ape_profe']; ?></div>
                                                    <div><i class="bi bi-geo-alt me-1"></i><?php echo $c['codigo_salon']; ?></div>
                                                </div>
                                            </div>
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
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Materia / Grupo</th>
                                <th>Día y Horario</th>
                                <th>Docente</th>
                                <th>Aula</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaHorarios">
                            <?php 
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)): 
                                $inicio = date("H:i", strtotime($row['hora_inicio']));
                                $fin = date("H:i", strtotime($row['hora_fin']));
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-circle me-3"><i class="bi bi-calendar-event"></i></div>
                                        <div>
                                            <div class="fw-bold mb-0 text-dark"><?php echo $row['nombre_materia']; ?></div>
                                            <div class="small text-primary fw-bold"><?php echo $row['codigo_grupo']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 mb-1"><?php echo $row['dia_semana']; ?></span>
                                    <div class="text-muted small fw-bold"><?php echo $inicio . ' - ' . $fin; ?></div>
                                </td>
                                <td><div class="small fw-bold text-dark"><?php echo $row['nombre_profe'] . ' ' . $row['ape_profe']; ?></div></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?php echo $row['codigo_salon']; ?></span></td>
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    // Sidebar Toggle
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn) toggleBtn.addEventListener('click', () => document.getElementById('wrapper').classList.toggle('toggled'));

    // Buscador
    document.getElementById('buscador').addEventListener('keyup', function() {
        let valor = this.value.toLowerCase();
        let filas = document.querySelectorAll('#tablaHorarios tr');
        filas.forEach(fila => {
            fila.style.display = fila.textContent.toLowerCase().includes(valor) ? '' : 'none';
        });
    });

    // Drag & Drop con SortableJS
    document.querySelectorAll('.dropzone').forEach(el => {
        new Sortable(el, {
            group: 'horario',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onAdd: function (evt) {
                const id = evt.item.getAttribute('data-id');
                const dia = evt.to.getAttribute('data-dia');
                const hora = evt.to.getAttribute('data-hora');
                
                // Petición AJAX para guardar la nueva posición
                fetch('actualizar_posicion.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `id=${id}&dia=${dia}&hora=${hora}`
                })
                .then(res => res.json())
                .then(data => {
                    if(!data.success) {
                        alert(data.message); // Muestra error de colisión
                        location.reload();   // Recarga para devolver la tarjeta a su lugar
                    }
                })
                .catch(err => {
                    console.error(err);
                    location.reload();
                });
            }
        });
    });
</script>
</body>
</html>