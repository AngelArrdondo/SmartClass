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
$titulo = "Asignar Clase";
$btn_texto = "Guardar en Horario";

// Variables iniciales
$id = "";
$ciclo_id = "";
$grupo_id = $_GET['grupo_id'] ?? ""; // Captura el grupo del que vienes
$materia_id = "";
$profesor_id = "";
$salon_id = "";
$dia_semana = "";
$hora_inicio = "";
$hora_fin = "";

// 1. OBTENER LISTAS PARA LOS SELECTS
$res_ciclos = mysqli_query($conn, "SELECT id, nombre, activo FROM ciclos_escolares ORDER BY activo DESC, nombre DESC");
$res_grupos = mysqli_query($conn, "SELECT id, codigo, grado, turno FROM grupos ORDER BY codigo ASC");
$res_materias = mysqli_query($conn, "SELECT id, nombre, codigo FROM materias ORDER BY nombre ASC");
$sql_profes = "SELECT p.id, u.nombre, u.apellido_paterno FROM profesores p INNER JOIN users u ON p.user_id = u.id WHERE u.is_active = 1 ORDER BY u.nombre ASC";
$res_profes = mysqli_query($conn, $sql_profes);
$res_salones = mysqli_query($conn, "SELECT id, codigo, capacidad FROM salones ORDER BY codigo ASC");

// 2. MODO EDICIÓN
if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Clase Programada";
    $btn_texto = "Guardar Cambios";
    $id = $_GET['id'];
    
    $sql = "SELECT * FROM horarios WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $ciclo_id = $fila['ciclo_id'];
            $grupo_id = $fila['grupo_id'];
            $materia_id = $fila['materia_id'];
            $profesor_id = $fila['profesor_id'];
            $salon_id = $fila['salon_id'];
            $dia_semana = $fila['dia_semana'];
            $hora_inicio = $fila['hora_inicio'];
            $hora_fin = $fila['hora_fin'];
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
                            <?php if($grupo_id && !$modo_edicion): ?>
                                <div class="alert alert-info py-2 small border-0">
                                    <i class="bi bi-info-circle me-2"></i>Asignando clase para el grupo seleccionado en el horario.
                                </div>
                            <?php endif; ?>

                            <form action="save_horario.php" method="POST" id="formHorario" class="needs-validation" novalidate>
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="d-flex justify-content-between mb-4 border-bottom pb-2">
                                    <h5 class="text-secondary fw-bold">1. Datos de la Asignatura</h5>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Ciclo Escolar *</label>
                                        <select name="ciclo_id" class="form-select" required>
                                            <option value="" disabled <?php if(!$ciclo_id) echo 'selected'; ?>>Selecciona...</option>
                                            <?php 
                                            mysqli_data_seek($res_ciclos, 0); // Reiniciar puntero
                                            while($c = mysqli_fetch_assoc($res_ciclos)): ?>
                                                <option value="<?php echo $c['id']; ?>" <?php if($ciclo_id == $c['id'] || ($c['activo'] == 1 && !$ciclo_id)) echo 'selected'; ?>>
                                                    <?php echo $c['nombre']; ?> <?php echo ($c['activo']==1)?'(ACTIVO)':''; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Grupo Destino *</label>
                                        <select name="grupo_id" class="form-select" required>
                                            <option value="" disabled <?php if(!$grupo_id) echo 'selected'; ?>>Selecciona el grupo...</option>
                                            <?php 
                                            mysqli_data_seek($res_grupos, 0); 
                                            while($g = mysqli_fetch_assoc($res_grupos)): ?>
                                                <option value="<?php echo $g['id']; ?>" <?php if($grupo_id == $g['id']) echo 'selected'; ?>>
                                                    <?php echo $g['codigo']; ?> (<?php echo $g['turno']; ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Materia *</label>
                                        <select name="materia_id" class="form-select" required>
                                            <option value="" disabled <?php if(!$materia_id) echo 'selected'; ?>>Selecciona la asignatura...</option>
                                            <?php 
                                            mysqli_data_seek($res_materias, 0);
                                            while($m = mysqli_fetch_assoc($res_materias)): ?>
                                                <option value="<?php echo $m['id']; ?>" <?php if($materia_id == $m['id']) echo 'selected'; ?>>
                                                    <?php echo $m['nombre']; ?> (<?php echo $m['codigo']; ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <h5 class="text-secondary fw-bold mb-4 border-bottom pb-2">2. Asignación de Recursos y Tiempo</h5>
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Profesor Encargado *</label>
                                        <select name="profesor_id" class="form-select" required>
                                            <option value="" disabled <?php if(!$profesor_id) echo 'selected'; ?>>Selecciona docente...</option>
                                            <?php 
                                            mysqli_data_seek($res_profes, 0);
                                            while($p = mysqli_fetch_assoc($res_profes)): ?>
                                                <option value="<?php echo $p['id']; ?>" <?php if($profesor_id == $p['id']) echo 'selected'; ?>>
                                                    <?php echo $p['nombre'] . ' ' . $p['apellido_paterno']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Aula Asignada *</label>
                                        <select name="salon_id" class="form-select" required>
                                            <option value="" disabled <?php if(!$salon_id) echo 'selected'; ?>>Selecciona espacio...</option>
                                            <?php 
                                            mysqli_data_seek($res_salones, 0);
                                            while($s = mysqli_fetch_assoc($res_salones)): ?>
                                                <option value="<?php echo $s['id']; ?>" <?php if($salon_id == $s['id']) echo 'selected'; ?>>
                                                    <?php echo $s['codigo']; ?> (Cap: <?php echo $s['capacidad']; ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Día de la Semana *</label>
                                        <select name="dia_semana" class="form-select" required>
                                            <option value="" disabled <?php if(!$dia_semana) echo 'selected'; ?>>Elige día...</option>
                                            <?php 
                                            $dias = ["Lunes", "Martes", "Miercoles", "Jueves", "Viernes"];
                                            foreach($dias as $d): ?>
                                                <option value="<?php echo $d; ?>" <?php if($dia_semana == $d) echo 'selected'; ?>><?php echo $d; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Hora Inicio *</label>
                                        <input type="time" name="hora_inicio" class="form-control" value="<?php echo $hora_inicio; ?>" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Hora Fin *</label>
                                        <input type="time" name="hora_fin" class="form-control" value="<?php echo $hora_fin; ?>" required>
                                    </div>
                                </div>

                                <div class="alert alert-warning border-0 small d-flex align-items-center rounded-3">
                                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                                    El sistema verificará que el profesor y el aula no tengan cruces en este horario.
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
    // Validación de Bootstrap y una pequeña mejora: evitar que hora_fin sea menor a hora_inicio
    (function () {
      'use strict'
      var forms = document.querySelectorAll('.needs-validation')
      Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
          const hInicio = form.querySelector('[name="hora_inicio"]').value;
          const hFin = form.querySelector('[name="hora_fin"]').value;

          if (hInicio && hFin && hFin <= hInicio) {
              alert("La hora de fin debe ser posterior a la de inicio.");
              event.preventDefault();
              event.stopPropagation();
              return;
          }

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