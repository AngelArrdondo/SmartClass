<?php
require_once '../../config/db.php';

// Validación de entrada
if (!isset($_GET['grupo_id']) || empty($_GET['grupo_id'])) {
    die("Error: ID de grupo no proporcionado.");
}

$grupo_id = intval($_GET['grupo_id']);

// 1. Obtener datos del grupo y ciclo activo
$res_info = mysqli_query($conn, "
    SELECT g.codigo as grupo, c.nombre as ciclo 
    FROM grupos g 
    LEFT JOIN ciclos_escolares c ON c.activo = 1 
    WHERE g.id = $grupo_id 
    LIMIT 1
");
$info = mysqli_fetch_assoc($res_info);

if (!$info) {
    die("Error: Grupo no encontrado.");
}

// 2. Obtener todos los horarios con nombres de materias (si tienes esa tabla)
$sql = "SELECT h.*, u.nombre as profe, u.apellido_paterno as apellido, s.codigo as salon
        FROM horarios h
        INNER JOIN profesores p ON h.profesor_id = p.id
        INNER JOIN users u ON p.user_id = u.id
        INNER JOIN salones s ON h.salon_id = s.id
        WHERE h.grupo_id = $grupo_id
        ORDER BY h.hora_inicio ASC";
$res_horarios = mysqli_query($conn, $sql);

// 3. Organizar horarios en una matriz
$agenda = [];
$horas_distintas = [];

while($h = mysqli_fetch_assoc($res_horarios)) {
    $rango = date("H:i", strtotime($h['hora_inicio'])) . " - " . date("H:i", strtotime($h['hora_fin']));
    $dia = $h['dia_semana'];
    $agenda[$rango][$dia] = [
        'profe' => $h['profe'] . " " . $h['apellido'],
        'salon' => $h['salon']
    ];
    if(!in_array($rango, $horas_distintas)) $horas_distintas[] = $rango;
}

$dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horario Oficial - <?php echo $info['grupo']; ?></title>
    <style>
        :root { --primary: #0d6efd; --bg-clase: #f0f7ff; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 40px; background: #fff; line-height: 1.4; }
        
        /* Encabezado */
        .top-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .logo-placeholder { width: 80px; height: 80px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 10px; color: #999; }
        .institution-details { text-align: right; }
        .institution-details h2 { margin: 0; color: var(--primary); font-size: 1.5rem; }
        
        /* Tabla de Horario */
        table { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #dee2e6; border-radius: 10px; overflow: hidden; }
        th { background-color: var(--primary); color: white; padding: 15px; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        td { border: 0.5px solid #f1f1f1; height: 70px; vertical-align: middle; text-align: center; font-size: 11px; padding: 5px; }
        
        .hora-col { background-color: #f8f9fa; font-weight: bold; width: 110px; border-right: 2px solid #dee2e6; color: #444; }
        
        /* Tarjeta de Clase */
        .clase { background-color: var(--bg-clase); border-radius: 6px; padding: 8px; border: 1px solid #cce3ff; height: 90%; display: flex; flex-direction: column; justify-content: center; transition: 0.3s; }
        .profe-name { display: block; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; font-size: 10px; }
        .salon-tag { font-size: 9px; color: var(--primary); font-weight: bold; background: #fff; padding: 2px 6px; border-radius: 10px; border: 1px solid #b3d7ff; align-self: center; }
        
        /* Pie de página */
        .signatures { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; }
        .sign-box { border-top: 1px solid #333; padding-top: 10px; text-align: center; width: 250px; margin: 0 auto; font-size: 12px; font-weight: bold; }

        /* Estilos de Impresión */
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            table { border: 1px solid #000; }
            th { background-color: #eee !important; color: #000 !important; border: 1px solid #000; }
            .clase { border: 1px solid #ccc !important; background-color: #fff !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.history.back()" style="padding: 10px 15px; border: 1px solid #ccc; background: white; border-radius: 5px; cursor: pointer; margin-right: 10px;">Volver</button>
        <button onclick="window.print()" style="padding: 10px 20px; background: #0d6efd; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
            🖨️ Descargar Horario PDF
        </button>
    </div>

    <div class="top-header">
        <div class="logo-placeholder">
            LOGO
        </div>
        <div class="institution-details">
            <h2>SMARTCLASS ACADEMY</h2>
            <p style="margin: 0; color: #666;">Sistema Integral de Gestión Escolar</p>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <h3 style="margin: 0; text-transform: uppercase;">Horario Oficial de Clases</h3>
        <span style="color: #666; font-size: 14px;">
            <strong>Grupo:</strong> <?php echo $info['grupo']; ?> | 
            <strong>Ciclo:</strong> <?php echo $info['ciclo'] ?? 'No definido'; ?> |
            <strong>Fecha de emisión:</strong> <?php echo date('d/m/Y'); ?>
        </span>
    </div>

    <?php if(empty($horas_distintas)): ?>
        <div style="padding: 50px; text-align: center; border: 2px dashed #ccc; border-radius: 10px; color: #999;">
            <i class="bi bi-calendar-x" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
            No se han programado clases para este grupo todavía.
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th class="hora-col">Hora</th>
                    <?php foreach($dias as $d): ?>
                        <th><?php echo $d; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($horas_distintas as $h_rango): ?>
                <tr>
                    <td class="hora-col"><?php echo $h_rango; ?></td>
                    <?php foreach($dias as $d): ?>
                        <td>
                            <?php if(isset($agenda[$h_rango][$d])): ?>
                                <div class="clase">
                                    <span class="profe-name"><?php echo mb_strtoupper($agenda[$h_rango][$d]['profe']); ?></span>
                                    <span class="salon-tag">AULA: <?php echo $agenda[$h_rango][$d]['salon']; ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="signatures">
        <div class="sign-box">
            Firma del Coordinador
        </div>
        <div class="sign-box">
            Sello de la Institución
        </div>
    </div>

    <div style="margin-top: 40px; font-size: 9px; color: #aaa; text-align: center; border-top: 1px solid #eee; padding-top: 10px;">
        Este documento es una representación oficial generada por el sistema SmartClass. Cualquier alteración invalida su contenido.
    </div>

</body>
</html>