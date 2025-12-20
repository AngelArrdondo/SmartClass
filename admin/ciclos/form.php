<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

$modo_edicion = false;
$titulo = "Nuevo Ciclo Escolar";
$btn_texto = "Crear Periodo";
$id = ""; $nombre = ""; $fecha_inicio = ""; $fecha_fin = ""; $activo = "0"; 

if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Ciclo Escolar";
    $btn_texto = "Guardar Cambios";
    $id = $_GET['id'];
    
    $sql = "SELECT * FROM ciclos_escolares WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $nombre = $fila['nombre'];
            $fecha_inicio = $fila['fecha_inicio'];
            $fecha_fin = $fila['fecha_fin'];
            $activo = $fila['activo'];
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
    <title><?php echo $titulo; ?> | SmartClass</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php'; ?>

    <div id="page-content" class="w-100">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
            <h4 class="mb-0 fw-bold text-primary"><?php echo $titulo; ?></h4>
        </nav>

        <main class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-10"> 
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <form action="save_ciclo.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="d-flex justify-content-between mb-4 border-bottom pb-2">
                                    <h5 class="text-secondary fw-bold">1. Configuración del Periodo</h5>
                                    <div class="d-flex align-items-center">
                                        <label class="small fw-bold me-2 text-muted">ESTADO:</label>
                                        <select name="activo" class="form-select form-select-sm w-auto">
                                            <option value="1" <?php echo ($activo == '1') ? 'selected' : ''; ?>>🟢 Activo</option>
                                            <option value="0" <?php echo ($activo == '0') ? 'selected' : ''; ?>>🔴 Inactivo</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Esquema *</label>
                                        <select id="esquema" class="form-select border-primary-subtle shadow-sm">
                                            <option value="">Seleccione...</option>
                                            <option value="Semestre">Semestre</option>
                                            <option value="Cuatrimestre">Cuatrimestre</option>
                                            <option value="Trimestre">Trimestre</option>
                                            <option value="Anual">Anual</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Rango de Meses *</label>
                                        <select id="rango_meses" class="form-select" disabled>
                                            <option value="">-- Primero elija esquema --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold">Año *</label>
                                        <input type="number" id="anio_periodo" class="form-control" value="<?php echo date('Y'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-primary">Nombre Oficial (BD)</label>
                                        <input type="text" name="nombre" id="nombre_oficial" class="form-control bg-light fw-bold" 
                                               value="<?php echo $nombre; ?>" readonly required>
                                    </div>
                                </div>

                                <h5 class="text-secondary fw-bold mb-4 border-bottom pb-2">2. Vigencia en Calendario</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Fecha de Inicio Real *</label>
                                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Fecha de Cierre Real *</label>
                                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>" required>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4 pt-3">
                                    <a href="index.php" class="btn btn-outline-secondary px-4">Cancelar</a>
                                    <button type="submit" class="btn btn-primary px-5 fw-bold shadow">
                                        <i class="bi bi-save me-2"></i> <?php echo $btn_texto; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    const opcionesPeriodos = {
        "Semestre": ["Enero - Junio", "Julio - Diciembre"],
        "Cuatrimestre": ["Enero - Abril", "Mayo - Agosto", "Septiembre - Diciembre"],
        "Trimestre": ["Enero - Marzo", "Abril - Junio", "Julio - Septiembre", "Octubre - Diciembre"],
        "Anual": ["Enero - Diciembre"]
    };

    const esquemaSelect = document.getElementById('esquema');
    const rangoSelect = document.getElementById('rango_meses');
    const anioInput = document.getElementById('anio_periodo');
    const nombreOficial = document.getElementById('nombre_oficial');

    esquemaSelect.addEventListener('change', function() {
        const esquema = this.value;
        rangoSelect.innerHTML = '<option value="">Seleccione rango...</option>';
        if (esquema && opcionesPeriodos[esquema]) {
            rangoSelect.disabled = false;
            opcionesPeriodos[esquema].forEach(rango => {
                const opt = document.createElement('option');
                opt.value = rango;
                opt.textContent = rango;
                rangoSelect.appendChild(opt);
            });
        } else {
            rangoSelect.disabled = true;
        }
        actualizarNombre();
    });

    function actualizarNombre() {
        if (rangoSelect.value && anioInput.value) {
            nombreOficial.value = `${rangoSelect.value} ${anioInput.value}`;
        }
    }

    rangoSelect.addEventListener('change', actualizarNombre);
    anioInput.addEventListener('input', actualizarNombre);

    // --- LÓGICA DE REUPERACIÓN DE DATOS AL EDITAR ---
    window.addEventListener('load', () => {
        const nombreGuardado = "<?php echo $nombre; ?>";
        if (nombreGuardado) {
            const partes = nombreGuardado.split(' ');
            const anio = partes.pop();
            const rango = partes.join(' ');

            // Buscar a qué esquema pertenece el rango
            for (const esquema in opcionesPeriodos) {
                if (opcionesPeriodos[esquema].includes(rango)) {
                    esquemaSelect.value = esquema;
                    // Forzar el llenado del select de rangos
                    esquemaSelect.dispatchEvent(new Event('change'));
                    rangoSelect.value = rango;
                    anioInput.value = anio;
                    break;
                }
            }
        }
    });

    // Validación de Bootstrap
    (function () {
      'use strict'
      var forms = document.querySelectorAll('.needs-validation')
      Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }
          form.classList.add('was-validated')
        }, false)
      })
    })()
</script>
</body>
</html>