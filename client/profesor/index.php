<?php
// 1. Reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// 2. Verificar conexión
if (!file_exists('../../config/db.php')) {
    die("Error crítico: No se encuentra el archivo de conexión.");
}
require_once '../../config/db.php';

// 3. Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['user_name'];

// 4. Datos del Profesor
$sql_profe = "SELECT id, codigo_empleado, especialidad FROM profesores WHERE user_id = ?";
$profe_id = 0;
$codigo_empleado = "---";
$especialidad = "---";

if ($stmt = mysqli_prepare($conn, $sql_profe)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($datos = mysqli_fetch_assoc($res)) {
        $profe_id = $datos['id'];
        $codigo_empleado = $datos['codigo_empleado'];
        $especialidad = $datos['especialidad'];
    }
    mysqli_stmt_close($stmt);
}

// 5. Clases de Hoy
$dias_esp = [
    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miercoles', 
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sabado', 'Sunday' => 'Domingo'
];
$dia_hoy_ingles = date('l'); 
$dia_hoy = $dias_esp[$dia_hoy_ingles]; 

$clases_hoy = [];

if ($profe_id > 0) {
    $sql_horario = "
        SELECT h.hora_inicio, h.hora_fin, 
               m.nombre as materia, s.codigo as salon,
               g.codigo as grupo, g.grado, g.turno
        FROM horarios h
        INNER JOIN materias m ON h.materia_id = m.id
        INNER JOIN salones s ON h.salon_id = s.id
        INNER JOIN grupos g ON h.grupo_id = g.id
        WHERE h.profesor_id = ? AND h.dia_semana = ?
        ORDER BY h.hora_inicio ASC
    ";
    
    if ($stmt = mysqli_prepare($conn, $sql_horario)) {
        mysqli_stmt_bind_param($stmt, "is", $profe_id, $dia_hoy);
        mysqli_stmt_execute($stmt);
        $res_h = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res_h)) {
            $clases_hoy[] = $row;
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
    <title>Portal Docente | SmartClass</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Forzar fondo limpio */
        body { 
            background-color: #f8f9fa !important; 
            overflow: auto !important; /* Permitir scroll */
        }
        
        /* OCULTAR CUALQUIER SOMBRA O OVERLAY RESIDUAL */
        .modal-backdrop, .overlay, .sidebar-overlay, #wrapper::before {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Estilos visuales */
        .timeline-item { border-left: 3px solid #198754; padding-left: 20px; padding-bottom: 20px; position: relative; }
        .timeline-item::before { content: ''; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #198754; }
        .timeline-item:last-child { border-left: 3px solid transparent; }
        .hover-scale:hover { transform: translateY(-2px); transition: transform 0.2s; cursor: pointer; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-success bg-gradient shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-briefcase-fill me-2 text-warning"></i>SmartClass Docente
            </a>
            
            <div class="d-flex align-items-center">
                <span class="text-white small me-3 d-none d-md-block">Prof. <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></span>
                <a href="../logout.php" class="btn btn-sm btn-light text-success fw-bold">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h2 class="fw-bold text-success mb-1">¡Buenos días!</h2>
                            <p class="text-muted mb-0">Panel de Gestión Académica</p>
                        </div>
                        <div class="text-end">
                            <span class="d-block text-uppercase small text-muted fw-bold">No. Empleado</span>
                            <span class="fs-5 font-monospace fw-bold text-dark"><?php echo htmlspecialchars($codigo_empleado); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h6 class="fw-bold text-success"><i class="bi bi-person-badge me-2"></i>Mi Perfil</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center py-2">
                            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($especialidad); ?></h5>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-1">
                                Docente Activo
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-3">
                    <a href="horario.php" class="btn btn-white border shadow-sm py-3 text-start px-4 rounded-4 hover-scale text-decoration-none text-dark">
                        <i class="bi bi-calendar-week text-warning me-2 fs-5"></i> Mi Agenda Semanal
                    </a>
                    
                    <button class="btn btn-white border shadow-sm py-3 text-start px-4 rounded-4 hover-scale text-decoration-none text-dark">
                        <i class="bi bi-check2-square text-success me-2 fs-5"></i> Pasar Asistencia
                    </button>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-success">
                            <i class="bi bi-clock-history me-2"></i>Clases a Impartir Hoy
                        </h5>
                        <span class="badge bg-light text-dark border">
                            <?php echo $dia_hoy; ?>, <?php echo date('d/m'); ?>
                        </span>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if(empty($clases_hoy)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-cup-hot display-1 text-muted opacity-25" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">Agenda Libre</h5>
                                <p class="text-muted small">No tienes grupos asignados para el día de hoy.</p>
                            </div>
                        <?php else: ?>
                            <div class="mt-2">
                                <?php foreach($clases_hoy as $clase): 
                                    $hora_i = date("H:i", strtotime($clase['hora_inicio']));
                                    $hora_f = date("H:i", strtotime($clase['hora_fin']));
                                ?>
                                    <div class="timeline-item">
                                        <span class="text-success fw-bold small mb-1 d-block"><?php echo $hora_i; ?> - <?php echo $hora_f; ?></span>
                                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($clase['materia']); ?></h5>
                                        <div class="d-flex align-items-center text-muted small mt-2">
                                            <span class="me-3 badge bg-light text-dark border">
                                                <i class="bi bi-people-fill me-1"></i> Grupo <?php echo htmlspecialchars($clase['grupo']); ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-geo-alt-fill me-1"></i> Aula <?php echo htmlspecialchars($clase['salon']); ?>
                                            </span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>