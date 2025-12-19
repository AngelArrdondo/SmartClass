<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// --- Lógica Crear / Editar ---
$modo_edicion = false;
$titulo = "Nuevo Salón";
$btn_texto = "Registrar Salón";

// Variables iniciales
$id = ""; $codigo = ""; $nombre = ""; $capacidad = "30"; 
$ubicacion = ""; $recursos = ""; $observaciones = ""; $is_active = 1;

if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Salón";
    $btn_texto = "Guardar Cambios";
    $id = $_GET['id'];
    
    $sql = "SELECT * FROM salones WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $codigo = $fila['codigo'];
            $nombre = $fila['nombre'];
            $capacidad = $fila['capacidad'];
            $ubicacion = $fila['ubicacion'];
            $recursos = $fila['recursos'];
            $observaciones = $fila['observaciones'];
            // Asumiendo que existe columna is_active en la tabla salones para mantener coherencia
            $is_active = $fila['is_active'] ?? 1; 
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
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
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <form action="save_salon.php" method="POST" id="formSalon" class="needs-validation" novalidate>
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="d-flex justify-content-between mb-4 border-bottom pb-2">
                                    <h5 class="text-secondary fw-bold">1. Identificación del Espacio</h5>
                                    <div class="d-flex align-items-center">
                                        <label class="small fw-bold me-2 text-muted">ESTADO:</label>
                                        <select name="is_active" class="form-select form-select-sm w-auto">
                                            <option value="1" <?php echo $is_active==1?'selected':''; ?>>🟢 Disponible</option>
                                            <option value="0" <?php echo $is_active==0?'selected':''; ?>>🔴 Mantenimiento</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Código de Salón *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-tag"></i></span>
                                            <input type="text" name="codigo" class="form-control border-start-0 fw-bold text-primary" 
                                                   placeholder="Ej. A-101" value="<?php echo $codigo; ?>" required>
                                            <div class="invalid-feedback">Ingresa un código identificador.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold">Nombre Descriptivo *</label>
                                        <input type="text" name="nombre" class="form-control" 
                                               placeholder="Ej. Aula Magna, Laboratorio de Química..." 
                                               value="<?php echo $nombre; ?>" required>
                                        <div class="invalid-feedback">Ingresa el nombre del salón.</div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Capacidad (Alumnos) *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-people"></i></span>
                                            <input type="number" name="capacidad" class="form-control border-start-0" 
                                                   value="<?php echo $capacidad; ?>" min="1" required>
                                            <div class="invalid-feedback">Indica la capacidad mínima (1).</div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold">Ubicación Física</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-geo-alt"></i></span>
                                            <input type="text" name="ubicacion" class="form-control border-start-0" 
                                                   placeholder="Ej. Edificio B, Planta Alta" value="<?php echo $ubicacion; ?>">
                                        </div>
                                    </div>
                                </div>

                                <h5 class="text-secondary fw-bold mb-4 border-bottom pb-2">2. Equipamiento y Notas</h5>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Recursos Disponibles</label>
                                        <textarea name="recursos" class="form-control" rows="2" 
                                                  placeholder="Ej. Proyector, Aire Acondicionado, Conexión Ethernet..."><?php echo $recursos; ?></textarea>
                                        <div class="form-text">Separa los recursos por comas.</div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Observaciones Internas</label>
                                        <textarea name="observaciones" class="form-control bg-light" rows="2" 
                                                  placeholder="Ej. Detalles técnicos o avisos de mantenimiento"><?php echo $observaciones; ?></textarea>
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
    // --- LÓGICA DE VALIDACIÓN DE BOOTSTRAP ---
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