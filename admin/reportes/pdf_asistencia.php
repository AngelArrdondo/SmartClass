<?php
require_once '../../config/db.php';

// Validación de entrada
if (!isset($_GET['grupo_id']) || empty($_GET['grupo_id'])) {
    die("Error: Seleccione un grupo válido.");
}

$grupo_id = intval($_GET['grupo_id']);

// 1. Obtener datos del grupo
$res_grupo = mysqli_query($conn, "SELECT codigo FROM grupos WHERE id = $grupo_id");
$grupo = mysqli_fetch_assoc($res_grupo);

if (!$grupo) {
    die("Error: El grupo seleccionado no existe.");
}

// 2. Obtener alumnos inscritos
$sql = "SELECT u.apellido_paterno, u.apellido_materno, u.nombre, a.matricula 
        FROM alumnos a 
        INNER JOIN users u ON a.user_id = u.id 
        WHERE a.grupo_id = $grupo_id 
        ORDER BY u.apellido_paterno ASC, u.apellido_materno ASC, u.nombre ASC";
$alumnos = mysqli_query($conn, $sql);
$total_alumnos = mysqli_num_rows($alumnos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Asistencia - <?php echo $grupo['codigo']; ?></title>
    <style>
        :root { --primary-color: #059669; } /* Color esmeralda para asistencia */
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; color: #1f2937; padding: 20px; }
        
        /* Encabezado Profesional */
        .header-table { width: 100%; border-bottom: 2px solid var(--primary-color); margin-bottom: 20px; padding-bottom: 10px; }
        .title { color: var(--primary-color); font-size: 18px; font-weight: bold; margin: 0; }
        .info-sub { color: #6b7280; font-size: 10px; margin-top: 5px; }
        
        /* Tabla de Alumnos */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 10px 5px; text-transform: uppercase; font-size: 9px; }
        td { border: 1px solid #d1d5db; padding: 8px 5px; }
        
        .text-center { text-align: center; }
        .col-num { width: 30px; background: #f9fafb; font-weight: bold; }
        .col-mat { width: 80px; font-family: monospace; color: #2563eb; }
        .col-name { width: auto; text-align: left; padding-left: 10px; }
        .col-day { width: 35px; }

        /* Estilo de Filas Cebradas */
        tbody tr:nth-child(even) { background-color: #fcfcfc; }
        
        /* Footer y Firmas */
        .footer-info { margin-top: 25px; display: flex; justify-content: space-between; align-items: center; }
        .signature-area { margin-top: 50px; text-align: center; }
        .line { border-top: 1px solid #000; width: 200px; margin: 0 auto 5px auto; }
        
        .btn-print { 
            position: fixed; top: 20px; right: 20px; padding: 10px 20px; 
            background: var(--primary-color); color: white; border: none; 
            border-radius: 6px; cursor: pointer; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        @media print {
            .btn-print { display: none; }
            body { padding: 0; }
            th { -webkit-print-color-adjust: exact; background-color: #f3f4f6 !important; }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">
        🖨️ Imprimir Lista Oficial
    </button>

    <table class="header-table">
        <tr>
            <td style="border:none; padding:0;">
                <h1 class="title">SMARTCLASS | REGISTRO DE ASISTENCIA</h1>
                <div class="info-sub">SISTEMA INTEGRAL DE GESTIÓN ACADÉMICA</div>
            </td>
            <td style="border:none; padding:0; text-align:right; vertical-align: bottom;">
                <strong>GRUPO:</strong> <?php echo $grupo['codigo']; ?> | 
                <strong>MES:</strong> _________________
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
        <span><strong>Profesor:</strong> ________________________________________________</span>
        <span><strong>Total Alumnos:</strong> <?php echo $total_alumnos; ?></span>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th class="text-center">Matrícula</th>
                <th class="col-name">Nombre del Estudiante (Apellido Paterno / Materno / Nombres)</th>
                <th class="col-day">LUN</th>
                <th class="col-day">MAR</th>
                <th class="col-day">MIE</th>
                <th class="col-day">JUE</th>
                <th class="col-day">VIE</th>
                <th style="width: 60px;">OBS.</th>
            </tr>
        </thead>
        <tbody>
            <?php if($total_alumnos > 0): ?>
                <?php $i=1; while($row = mysqli_fetch_assoc($alumnos)): ?>
                <tr>
                    <td class="text-center col-num"><?php echo $i++; ?></td>
                    <td class="text-center col-mat"><?php echo $row['matricula']; ?></td>
                    <td class="col-name">
                        <?php echo mb_strtoupper($row['apellido_paterno'] . " " . $row['apellido_materno'] . ", " . $row['nombre']); ?>
                    </td>
                    <td></td><td></td><td></td><td></td><td></td>
                    <td></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center" style="padding: 30px; color: #999;">
                        No hay alumnos inscritos en este grupo.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signature-area">
        <div class="line"></div>
        <div style="font-size: 10px;">Firma del Docente Responsable</div>
    </div>

    <div class="footer-info">
        <div style="font-size: 9px; color: #9ca3af;">
            Documento generado el: <?php echo date('d/m/Y H:i'); ?>
        </div>
        <div style="font-size: 9px; color: #9ca3af;">
            SmartClass v2.0 - Reporte de Control Escolar
        </div>
    </div>

</body>
</html>