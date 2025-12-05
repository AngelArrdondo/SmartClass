<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD: Solo Profesores (Rol 2)
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 2) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. OBTENER ID DEL PROFESOR
// Necesitamos el ID de la tabla 'profesores', no el 'user_id'
$sql_profe = "SELECT id FROM profesores WHERE user_id = ?";
$profe_id = 0;

if ($stmt = mysqli_prepare($conn, $sql_profe)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $profe_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

// 3. OBTENER LA AGENDA SEMANAL
$horario_data = [];

if ($profe_id) {
    $sql = "
        SELECT h.dia_semana, h.hora_inicio, h.hora_fin, 
               m.nombre as materia, s.codigo as salon, 
               g.codigo as grupo, g.grado, g.turno
        FROM horarios h
        INNER JOIN materias m ON h.materia_id = m.id
        INNER JOIN salones s ON h.salon_id = s.id
        INNER JOIN grupos g ON h.grupo_id = g.id
        WHERE h.profesor_id = ?
        ORDER BY h.hora_inicio ASC
    ";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $profe_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($res)) {
            $horario_data[$row['dia_semana']][] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

$dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Agenda Docente | SmartClass</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    
    <style>
        .tabla-horario th { background-color: #198754; color: white; text-align: center; } /* Verde Docente */
        .tabla-horario td { vertical-align: top; height: 150px; min-width: 150px; background-color: #f8f9fa; }
        
        .clase-card { 
            background: white; 
            border-left: 4px solid #198754; /* Borde verde */
            padding: 10px; 
            margin-bottom: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
            border-radius: 4px;
            transition: transform 0.2s;
        }
        .clase-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        
        .clase-hora { font-size: 0.85rem; font-weight: bold; color: #198754; display: block; margin-bottom: 4px; }
        .clase-materia { font-weight: bold; color: #212529; display: block; font-size: 0.95rem; }
        .clase-info { font-size: 0.8rem; color: #6c757d; margin-top: 5px; display: block; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-success bg-gradient shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-arrow-left me-2"></i> Volver al Panel
            </a>
            <span class="navbar-text text-white fw-bold">Mi Agenda Semanal</span>
        </div>
    </nav>

    <div class="container pb-5">
        
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                
                <?php if (!$profe_id): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-exclamation-circle text-warning display-1"></i>
                        <h4 class="mt-3">Perfil Incompleto</h4>
                        <p class="text-muted">No se encontraron datos de docente asociados a tu usuario.</p>
                    </div>
                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 tabla-horario">
                            <thead>
                                <tr>
                                    <?php foreach ($dias_semana as $dia): ?>
                                        <th class="py-3 text-uppercase"><?php echo $dia; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php foreach ($dias_semana as $dia): ?>
                                        <td>
                                            <?php 
                                            if (isset($horario_data[$dia])) {
                                                foreach ($horario_data[$dia] as $clase) {
                                                    $inicio = date("H:i", strtotime($clase['hora_inicio']));
                                                    $fin = date("H:i", strtotime($clase['hora_fin']));
                                                    ?>
                                                    
                                                    <div class="clase-card">
                                                        <span class="clase-hora">
                                                            <i class="bi bi-clock"></i> <?php echo $inicio . ' - ' . $fin; ?>
                                                        </span>
                                                        <span class="clase-materia">
                                                            <?php echo $clase['materia']; ?>
                                                        </span>
                                                        <span class="clase-info">
                                                            <div class="d-flex justify-content-between">
                                                                <span><i class="bi bi-people-fill"></i> <strong><?php echo $clase['grupo']; ?></strong></span>
                                                                <span><i class="bi bi-geo-alt-fill"></i> <?php echo $clase['salon']; ?></span>
                                                            </div>
                                                            <small class="d-block text-muted mt-1 text-truncate">
                                                                <?php echo $clase['grado']; ?>º Semestre - <?php echo $clase['turno']; ?>
                                                            </small>
                                                        </span>
                                                    </div>

                                                    <?php
                                                }
                                            } else {
                                                echo '<div class="text-center text-muted small py-5 opacity-50">-</div>';
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

            </div>
        </div>
        
        <div class="text-center mt-4 d-print-none">
            <button class="btn btn-outline-success rounded-pill px-4" onclick="window.print()">
                <i class="bi bi-printer me-2"></i> Imprimir Agenda
            </button>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>