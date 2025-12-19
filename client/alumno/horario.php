<?php
session_start();
require_once '../../config/db.php';

// Seguridad: Solo Alumnos
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 3) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. OBTENER GRUPO DEL ALUMNO
// Necesitamos saber el ID del grupo para buscar el horario
$sql_grupo = "SELECT grupo_id FROM alumnos WHERE user_id = ?";
$grupo_id = 0;

if ($stmt = mysqli_prepare($conn, $sql_grupo)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $grupo_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

// 2. OBTENER EL HORARIO COMPLETO
$horario_data = [];

if ($grupo_id) {
    $sql = "
        SELECT h.dia_semana, h.hora_inicio, h.hora_fin, 
               m.nombre as materia, s.codigo as salon, 
               u.nombre as n_prof, u.apellido_paterno as a_prof
        FROM horarios h
        INNER JOIN materias m ON h.materia_id = m.id
        INNER JOIN salones s ON h.salon_id = s.id
        INNER JOIN profesores p ON h.profesor_id = p.id
        INNER JOIN users u ON p.user_id = u.id
        WHERE h.grupo_id = ?
        ORDER BY h.hora_inicio ASC
    ";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $grupo_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($res)) {
            // Guardamos en un array asociativo: [Día][] = Clase
            $horario_data[$row['dia_semana']][] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

// Días de la semana para iterar
$dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Mi Horario | SmartClass</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    
    <style>
        /* Estilos para la tabla de horario */
        .tabla-horario th { background-color: #0d6efd; color: white; text-align: center; }
        .tabla-horario td { vertical-align: top; height: 150px; min-width: 150px; background-color: #f8f9fa; }
        .clase-card { 
            background: white; 
            border-left: 4px solid #0dcaf0; 
            padding: 10px; 
            margin-bottom: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
            border-radius: 4px;
            transition: transform 0.2s;
        }
        .clase-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .clase-hora { font-size: 0.85rem; font-weight: bold; color: #6c757d; display: block; margin-bottom: 4px; }
        .clase-materia { font-weight: bold; color: #212529; display: block; }
        .clase-info { font-size: 0.8rem; color: #6c757d; margin-top: 4px; display: block; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-arrow-left me-2"></i> Volver al Inicio
            </a>
            <span class="navbar-text text-white">Horario de Clases</span>
        </div>
    </nav>

    <div class="container pb-5">
        
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                
                <?php if (!$grupo_id): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-exclamation-circle text-warning display-1"></i>
                        <h4 class="mt-3">Sin Grupo Asignado</h4>
                        <p class="text-muted">No tienes un grupo asignado actualmente. Contacta a la administración.</p>
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
                                            // Si hay clases este día, las mostramos
                                            if (isset($horario_data[$dia])) {
                                                foreach ($horario_data[$dia] as $clase) {
                                                    // Formato de horas
                                                    $inicio = date("H:i", strtotime($clase['hora_inicio']));
                                                    $fin = date("H:i", strtotime($clase['hora_fin']));
                                                    
                                                    // Generar un color aleatorio suave para el borde basado en la materia
                                                    $colores = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0', '#6610f2'];
                                                    $color_borde = $colores[rand(0, 5)];
                                                    ?>
                                                    
                                                    <div class="clase-card" style="border-left-color: <?php echo $color_borde; ?>;">
                                                        <span class="clase-hora">
                                                            <i class="bi bi-clock"></i> <?php echo $inicio . ' - ' . $fin; ?>
                                                        </span>
                                                        <span class="clase-materia">
                                                            <?php echo $clase['materia']; ?>
                                                        </span>
                                                        <span class="clase-info">
                                                            <i class="bi bi-geo-alt-fill"></i> <?php echo $clase['salon']; ?> <br>
                                                            <i class="bi bi-person-fill"></i> <?php echo $clase['n_prof'] . ' ' . substr($clase['a_prof'], 0, 1) . '.'; ?>
                                                        </span>
                                                    </div>

                                                    <?php
                                                }
                                            } else {
                                                echo '<div class="text-center text-muted small py-5 opacity-50">Libre</div>';
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
        
        <div class="text-center mt-4">
            <button class="btn btn-outline-primary rounded-pill px-4" onclick="window.print()">
                <i class="bi bi-printer me-2"></i> Imprimir Horario
            </button>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>