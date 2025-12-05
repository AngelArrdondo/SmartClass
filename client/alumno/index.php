<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD: Solo Alumnos (Rol 3)
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 3) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['user_name'];

// 2. OBTENER DATOS DEL ALUMNO (Grupo, Matrícula)
$sql_alumno = "
    SELECT a.id, a.matricula, g.codigo as grupo, g.grado, g.turno, g.id as grupo_id
    FROM alumnos a
    LEFT JOIN grupos g ON a.grupo_id = g.id
    WHERE a.user_id = ?
";

$grupo_info = null;
$grupo_id = null;

if ($stmt = mysqli_prepare($conn, $sql_alumno)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $alumno_data = mysqli_fetch_assoc($res);
    
    if ($alumno_data) {
        $grupo_info = $alumno_data['grupo'] ? $alumno_data['grado'] . 'º ' . $alumno_data['grupo'] . ' (' . $alumno_data['turno'] . ')' : 'Sin Asignar';
        $grupo_id = $alumno_data['grupo_id'];
    }
    mysqli_stmt_close($stmt);
}

// 3. OBTENER HORARIO DEL DÍA ACTUAL
$dias_esp = [
    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miercoles', 
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sabado', 'Sunday' => 'Domingo'
];
$dia_hoy_ingles = date('l'); 
$dia_hoy = $dias_esp[$dia_hoy_ingles]; 

$horario_hoy = [];
if ($grupo_id) {
    $sql_horario = "
        SELECT h.hora_inicio, h.hora_fin, m.nombre as materia, s.codigo as salon, 
               u.nombre as nom_profe, u.apellido_paterno as ape_profe
        FROM horarios h
        INNER JOIN materias m ON h.materia_id = m.id
        INNER JOIN salones s ON h.salon_id = s.id
        INNER JOIN profesores p ON h.profesor_id = p.id
        INNER JOIN users u ON p.user_id = u.id
        WHERE h.grupo_id = ? AND h.dia_semana = ?
        ORDER BY h.hora_inicio ASC
    ";
    
    if ($stmt = mysqli_prepare($conn, $sql_horario)) {
        mysqli_stmt_bind_param($stmt, "is", $grupo_id, $dia_hoy);
        mysqli_stmt_execute($stmt);
        $res_h = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res_h)) {
            $horario_hoy[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Mi Portal | SmartClass</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet"> 
    
    <style>
        .timeline-item { border-left: 3px solid #0d6efd; padding-left: 20px; padding-bottom: 20px; position: relative; }
        .timeline-item::before { content: ''; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #0d6efd; }
        .timeline-item:last-child { border-left: 3px solid transparent; }
        
        /* Efecto hover suave para los botones grandes */
        .hover-scale:hover { transform: translateY(-2px); transition: transform 0.2s; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-mortarboard-fill me-2 text-warning"></i>SmartClass
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <span class="text-white small">Hola, <strong><?php echo $nombre_usuario; ?></strong></span>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="btn btn-sm btn-warning text-dark fw-bold">
                            <i class="bi bi-box-arrow-right"></i> Salir
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h2 class="fw-bold text-primary mb-1">¡Bienvenido de nuevo!</h2>
                            <p class="text-muted mb-0">Portal del Estudiante</p>
                        </div>
                        <div class="text-end">
                            <span class="d-block text-uppercase small text-muted fw-bold">Matrícula</span>
                            <span class="fs-5 font-monospace fw-bold"><?php echo $alumno_data['matricula'] ?? '---'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h6 class="fw-bold text-primary"><i class="bi bi-people-fill me-2"></i>Mi Grupo</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center py-3">
                            <?php if($grupo_id): ?>
                                <h1 class="display-4 fw-bold text-dark mb-0"><?php echo $alumno_data['grupo']; ?></h1>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2 mt-2">
                                    <?php echo $grupo_info; ?>
                                </span>
                            <?php else: ?>
                                <div class="text-warning mb-2"><i class="bi bi-exclamation-circle fs-1"></i></div>
                                <h5 class="text-muted">No tienes grupo asignado</h5>
                                <p class="small text-muted">Contacta a control escolar.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-3">
                    <button class="btn btn-white border shadow-sm py-3 text-start px-4 rounded-4 hover-scale text-decoration-none text-dark">
                        <i class="bi bi-file-earmark-text text-primary me-2 fs-5"></i> Ver Calificaciones
                    </button>
                    
                    <a href="horario.php" class="btn btn-white border shadow-sm py-3 text-start px-4 rounded-4 hover-scale text-decoration-none text-dark">
                        <i class="bi bi-calendar3 text-success me-2 fs-5"></i> Horario Completo
                    </a>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-primary">
                            <i class="bi bi-clock-history me-2"></i>Clases de Hoy
                        </h5>
                        <span class="badge bg-light text-dark border">
                            <?php echo $dia_hoy; ?>, <?php echo date('d/m'); ?>
                        </span>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if(empty($horario_hoy)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-cup-hot display-1 text-muted opacity-25"></i>
                                <h5 class="text-muted mt-3">¡Día libre!</h5>
                                <p class="text-muted small">No hay clases programadas para hoy.</p>
                            </div>
                        <?php else: ?>
                            <div class="mt-2">
                                <?php foreach($horario_hoy as $clase): 
                                    $hora_i = date("H:i", strtotime($clase['hora_inicio']));
                                    $hora_f = date("H:i", strtotime($clase['hora_fin']));
                                ?>
                                    <div class="timeline-item">
                                        <span class="text-primary fw-bold small mb-1 d-block"><?php echo $hora_i; ?> - <?php echo $hora_f; ?></span>
                                        <h5 class="fw-bold mb-1"><?php echo $clase['materia']; ?></h5>
                                        <div class="d-flex align-items-center text-muted small mt-2">
                                            <span class="me-3"><i class="bi bi-geo-alt-fill me-1"></i> Aula <?php echo $clase['salon']; ?></span>
                                            <span><i class="bi bi-person-fill me-1"></i> Prof. <?php echo $clase['nom_profe'] . ' ' . $clase['ape_profe']; ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div> 
    </div>

    <footer class="text-center py-4 text-muted small">
        &copy; 2025 SmartClass - Portal del Alumno
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>