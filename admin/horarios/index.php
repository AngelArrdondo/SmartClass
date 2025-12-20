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

// --- FILTRO DE GRUPO CON MEMORIA ---
$filtro_grupo = "";
$where_clause = "";
$turno_actual = "Matutino";
$codigo_grupo_print = "";

// 1. Si el usuario selecciona un grupo por el selector, lo guardamos en la sesión
if (isset($_GET['grupo_id'])) {
    if ($_GET['grupo_id'] == 'todos') {
        unset($_SESSION['ultimo_grupo_visto']);
    } else {
        $_SESSION['ultimo_grupo_visto'] = $_GET['grupo_id'];
    }
}

// 2. Si existe un grupo en la sesión, lo cargamos (esto evita que se "cierre" al volver de editar)
if (isset($_SESSION['ultimo_grupo_visto'])) {
    $grupo_selec = mysqli_real_escape_string($conn, $_SESSION['ultimo_grupo_visto']);
    $where_clause = "WHERE h.grupo_id = $grupo_selec";
    $filtro_grupo = $grupo_selec;

    $q_turno = mysqli_query($conn, "SELECT codigo, turno FROM grupos WHERE id = $grupo_selec");
    $d_turno = mysqli_fetch_assoc($q_turno);
    $turno_actual = $d_turno['turno'] ?? 'Matutino';
    $codigo_grupo_print = $d_turno['codigo'] ?? '';
}

// Configuración de bloques según turno
if($turno_actual == 'Vespertino') {
    $bloques_vista = ['14:00 - 15:00','15:00 - 16:00','16:00 - 17:00','17:00 - 18:00 (RECESO)','18:00 - 19:00','19:00 - 20:00','20:00 - 21:00'];
} else {
    $bloques_vista = ['07:00 - 08:00','08:00 - 09:00','09:00 - 10:00','10:00 - 11:00 (RECESO)','11:00 - 12:00','12:00 - 13:00','13:00 - 14:00'];
}

// --- CONSULTA PARA LA CUADRÍCULA ---
$horario_visual = [];
$dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

if ($filtro_grupo) {
    $sql_grid = "SELECT h.id, h.dia_semana, h.hora_inicio, h.hora_fin, m.nombre as nombre_materia, s.codigo as codigo_salon, u.nombre as nombre_profe, u.apellido_paterno as ape_profe
                 FROM horarios h
                 INNER JOIN materias m ON h.materia_id = m.id
                 INNER JOIN salones s ON h.salon_id = s.id
                 INNER JOIN profesores p ON h.profesor_id = p.id
                 INNER JOIN users u ON p.user_id = u.id
                 $where_clause";
    $res_grid = mysqli_query($conn, $sql_grid);
    while ($rv = mysqli_fetch_assoc($res_grid)) {
        $rango = date("H:i", strtotime($rv['hora_inicio'])) . " - " . date("H:i", strtotime($rv['hora_fin']));
        $horario_visual[$rango][$rv['dia_semana']] = $rv;
    }
}

$res_grupos = mysqli_query($conn, "SELECT id, codigo FROM grupos ORDER BY codigo ASC");
$grupos_lista = mysqli_fetch_all($res_grupos, MYSQLI_ASSOC);
// --- NUEVO: Obtener la foto del admin ---
$user_id = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT foto FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($query_user);

// Subimos dos niveles (../../) para llegar a assets desde admin/horarios/
$base_img_path = "../../assets/img/";
$foto_perfil = !empty($user_data['foto']) ? $base_img_path . "profiles/" . $user_data['foto'] : $base_img_path . "avatar.png";
// ---------------------------------------
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
        
        .dropzone { height: 100%; width: 100%; min-height: 80px; transition: background 0.2s; }
        .receso-row { background-color: #fff8e1 !important; height: 50px !important; text-align: center; color: #b08900; vertical-align: middle !important; font-size: 0.7rem; font-weight: bold; }
        
        .class-card {
            background: #f0f7ff; border-left: 4px solid #0d6efd; border-radius: 8px;
            padding: 8px; height: 100%; cursor: pointer; transition: 0.2s ease;
        }
        .class-card:hover { background: #e2efff; transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .class-title { font-weight: bold; font-size: 0.72rem; color: #084298; display: block; overflow: hidden; text-overflow: ellipsis; }
        .class-info { font-size: 0.62rem; color: #666; margin-top: 4px; pointer-events: none; }
        .sortable-ghost { opacity: 0.3; background: #0d6efd !important; border-radius: 8px; }

        @media print {
            .no-print, #sidebar-wrapper, .navbar, .btn, form, .card-header span { display: none !important; }
            body { background-color: white !important; }
            #page-content { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            .card { border: none !important; box-shadow: none !important; }
            .schedule-table td { height: 80px !important; border: 1px solid #ddd !important; }
            .schedule-table th { border: 1px solid #ddd !important; background-color: #f0f0f0 !important; color: black !important; }
            .class-card { border: 1px solid #ccc !important; background-color: white !important; }
        }
    </style>
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <div class="no-print"><?php require_once __DIR__ . '/../../includes/menu.php'; ?></div>

    <div id="page-content" class="w-100">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top no-print">
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
                    <img src="<?php echo $foto_perfil; ?>" alt="Admin" class="rounded-circle border" width="38" height="38" style="object-fit: cover;">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4 no-print" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
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

            <div class="card border-0 shadow-sm mb-4 rounded-4 no-print">
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
                        <div class="col-md-4 pt-4">
                            <button onclick="window.print()" class="btn btn-dark w-100 rounded-pill fw-bold"><i class="bi bi-printer me-2"></i>Imprimir Horario</button>
                        </div>
                        <div class="col-md-3 text-md-end pt-4">
                            <a href="form.php?grupo_id=<?php echo $filtro_grupo; ?>" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">Asignar Clase</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($filtro_grupo != "" && $filtro_grupo != "todos"): ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap me-2 text-primary no-print"></i>Horario Semanal - <?php echo $codigo_grupo_print; ?></h6>
                    <span class="badge bg-info-subtle text-info rounded-pill no-print">Haz clic para editar | Arrastra para mover</span>
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
                                            <i class="bi bi-cup-hot me-1 no-print"></i> RECESO
                                        <?php elseif(isset($horario_visual[$rango_limpio][$dia])): 
                                            $c = $horario_visual[$rango_limpio][$dia]; ?>
                                            <div class="class-card" data-id="<?php echo $c['id']; ?>" onclick="window.location.href='form.php?id=<?php echo $c['id']; ?>'">
                                                <span class="class-title"><?php echo $c['nombre_materia']; ?></span>
                                                <div class="class-info">
                                                    <div><i class="bi bi-person me-1 no-print"></i><?php echo $c['ape_profe']; ?></div>
                                                    <div><i class="bi bi-geo-alt me-1 no-print"></i><?php echo $c['codigo_salon']; ?></div>
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

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden no-print">
                <div class="card-header bg-light"><h6 class="mb-0 small fw-bold">RESUMEN DE CARGA ACADÉMICA</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Materia</th>
                                <th>Docente</th>
                                <th class="text-center">Total Horas</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql_resumen = "SELECT m.id as mat_id, m.nombre as nombre_materia, u.nombre, u.apellido_paterno, COUNT(h.id) as total_clases
                                            FROM horarios h
                                            INNER JOIN materias m ON h.materia_id = m.id
                                            INNER JOIN profesores p ON h.profesor_id = p.id
                                            INNER JOIN users u ON p.user_id = u.id
                                            $where_clause GROUP BY m.id";
                            $res_resumen = mysqli_query($conn, $sql_resumen);
                            while ($row = mysqli_fetch_assoc($res_resumen)): 
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-circle me-3"><i class="bi bi-book"></i></div>
                                        <div class="fw-bold text-dark"><?php echo $row['nombre_materia']; ?></div>
                                    </div>
                                </td>
                                <td><?php echo $row['nombre'] . ' ' . $row['apellido_paterno']; ?></td>
                                <td class="text-center"><span class="badge bg-primary rounded-pill"><?php echo $row['total_clases']; ?> hrs / semana</span></td>
                                <td class="text-end pe-4">
                                    <a href="delete_materia_completa.php?materia_id=<?php echo $row['mat_id']; ?>&grupo_id=<?php echo $filtro_grupo; ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-circle" 
                                       onclick="return confirm('¿Quitar todas las clases de esta materia?')">
                                       <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    // Sidebar Toggle
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn) toggleBtn.addEventListener('click', () => document.getElementById('wrapper').classList.toggle('toggled'));

    // Drag & Drop con SortableJS
    document.querySelectorAll('.dropzone').forEach(el => {
        new Sortable(el, {
            group: 'horario',
            animation: 150,
            ghostClass: 'sortable-ghost',
            // AJUSTE: Solo arrastrar si se agarra de un área específica o evitar delay
            delay: 50, 
            delayOnTouchOnly: false,
            onEnd: function (evt) {
                // Usamos onEnd en lugar de onAdd para detectar movimientos 
                // incluso dentro de la misma columna
                const itemEl = evt.item; 
                const id = itemEl.getAttribute('data-id');
                const targetBg = evt.to; // El <td> donde cayó
                const dia = targetBg.getAttribute('data-dia');
                const hora = targetBg.getAttribute('data-hora');

                if (!dia || !hora) return;

                fetch('actualizar_posicion.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `id=${id}&dia=${dia}&hora=${hora}`
                })
                .then(res => res.json())
                .then(data => {
                    if(!data.success) {
                        alert(data.message); // Muestra por qué hubo colisión
                        location.reload();   // Regresa el cuadro a su sitio
                    }
                })
                .catch(err => {
                    console.error("Error en AJAX:", err);
                    location.reload();
                });
            }
        });
    });
    // Manejo de mensajes en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    if (msg) {
        const alertBox = document.createElement('div');
        alertBox.className = 'position-fixed bottom-0 end-0 p-3';
        alertBox.style.zIndex = '1100';
        
        let text = "";
        let color = "success";

        switch(msg) {
            case 'creado': text = "¡Clase asignada correctamente!"; break;
            case 'actualizado': text = "Horario actualizado."; break;
            case 'eliminado': text = "Registro eliminado."; break;
            case 'materia_eliminada': text = "Se quitó toda la materia del grupo."; break;
            case 'generado': text = "¡Horario generado automáticamente!"; break;
        }

        if(text) {
            alertBox.innerHTML = `
                <div class="toast show align-items-center text-white bg-${color} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body"><i class="bi bi-check-circle me-2"></i>${text}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>`;
            document.body.appendChild(alertBox);
            setTimeout(() => alertBox.remove(), 4000); // Se quita solo en 4 seg
        }
    }
    // Limpiar los parámetros de la URL sin recargar la página
    window.history.replaceState({}, document.title, window.location.pathname + (filtro_grupo ? "?grupo_id=" + filtro_grupo : ""));
</script>
</body>
</html>