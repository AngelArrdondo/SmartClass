<?php
require_once '../../config/db.php';

// Validación de entrada
if (!isset($_GET['salon_id']) || empty($_GET['salon_id'])) {
    die("Error: Seleccione un aula válida.");
}

$salon_id = intval($_GET['salon_id']);

// 1. Obtener datos del salón
$res_salon = mysqli_query($conn, "SELECT * FROM salones WHERE id = $salon_id");
$salon = mysqli_fetch_assoc($res_salon);

if (!$salon) {
    die("Error: El aula seleccionada no existe.");
}

// 2. Obtener horarios asignados con orden lógico por día y hora
$sql = "SELECT h.*, g.codigo as grupo, u.nombre as profesor, u.apellido_paterno
        FROM horarios h
        INNER JOIN grupos g ON h.grupo_id = g.id
        INNER JOIN profesores p ON h.profesor_id = p.id
        INNER JOIN users u ON p.user_id = u.id
        WHERE h.salon_id = $salon_id
        ORDER BY FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'), h.hora_inicio";
$horarios = mysqli_query($conn, $sql);
$total_clases = mysqli_num_rows($horarios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ocupación de Aula - <?php echo $salon['codigo']; ?></title>
    <style>
        :root { --primary: #f59e0b; --text: #374151; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 40px; color: var(--text); background: #fff; }
        
        /* Cabecera */
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f3f4f6; padding-bottom: 20px; margin-bottom: 30px; }
        .title-area h1 { margin: 0; color: var(--primary); font-size: 22px; }
        .title-area p { margin: 5px 0 0 0; color: #6b7280; font-size: 14px; }
        
        /* Tarjetas de Resumen */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fffbeb; border: 1px solid #fef3c7; padding: 15px; border-radius: 10px; text-align: center; }
        .stat-card small { display: block; text-transform: uppercase; font-size: 10px; font-weight: bold; color: #92400e; }
        .stat-card span { font-size: 18px; font-weight: bold; color: #451a03; }

        /* Tabla */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th { background-color: #f9fafb; color: #4b5563; text-align: left; padding: 12px; border-bottom: 2px solid #e5e7eb; text-transform: uppercase; font-size: 11px; }
        td { padding: 12px; border-bottom: 1px solid #f3f4f6; }
        tr:nth-child(even) { background-color: #fafafa; }
        
        .dia-badge { background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 11px; }
        .hora-text { color: #059669; font-weight: 600; }
        
        .no-print-btn { 
            position: fixed; bottom: 20px; right: 20px;
            padding: 12px 25px; background: #1f2937; color: white; 
            border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        @media print {
            .no-print-btn { display: none; }
            body { padding: 20px; }
            .stat-card { border: 1px solid #ddd; background: none; }
        }
    </style>
</head>
<body>

    <button class="no-print-btn" onclick="window.print()">
        <i class="bi bi-printer"></i> Imprimir Reporte
    </button>

    <div class="header">
        <div class="title-area">
            <h1>Reporte de Disponibilidad y Uso</h1>
            <p>SmartClass | Gestión de Infraestructura Educativa</p>
        </div>
        <div style="text-align: right">
            <div style="font-weight: bold; font-size: 18px;"><?php echo $salon['codigo']; ?></div>
            <div style="font-size: 12px; color: #666;">ID de Aula: #<?php echo $salon['id']; ?></div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <small>Nombre del Aula</small>
            <span><?php echo $salon['nombre']; ?></span>
        </div>
        <div class="stat-card">
            <small>Capacidad Máxima</small>
            <span><?php echo $salon['capacidad']; ?> Alumnos</span>
        </div>
        <div class="stat-card">
            <small>Clases Semanales</small>
            <span><?php echo $total_clases; ?> Sesiones</span>
        </div>
    </div>

    <?php if($total_clases > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Día de la Semana</th>
                <th>Intervalo de Horario</th>
                <th>Grupo / Sección</th>
                <th>Catedrático Responsable</th>
            </tr>
        </thead>
        <tbody>
            <?php while($h = mysqli_fetch_assoc($horarios)): ?>
            <tr>
                <td><span class="dia-badge"><?php echo $h['dia_semana']; ?></span></td>
                <td><span class="hora-text"><?php echo date("H:i", strtotime($h['hora_inicio'])); ?> - <?php echo date("H:i", strtotime($h['hora_fin'])); ?></span></td>
                <td><strong><?php echo $h['grupo']; ?></strong></td>
                <td><?php echo $h['profesor']." ".$h['apellido_paterno']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; background: #f9fafb; border-radius: 15px; border: 2px dashed #e5e7eb;">
            <p style="color: #6b7280;">No existen actividades programadas para este salón en el ciclo escolar actual.</p>
        </div>
    <?php endif; ?>

    <div style="margin-top: 50px; font-size: 11px; color: #9ca3af; text-align: center;">
        Este reporte muestra la ocupación oficial de las instalaciones. <br>
        Documento generado el <?php echo date('d/m/Y \a \l\a\s H:i'); ?> hrs.
    </div>

</body>
</html>