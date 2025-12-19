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

// Variables
$id = "";
$ciclo_id = "";
$grupo_id = "";
$materia_id = "";
$profesor_id = "";
$salon_id = "";
$dia_semana = "";
$hora_inicio = "";
$hora_fin = "";

// 1. OBTENER LISTAS PARA LOS SELECTS
// Ciclos (Activos primero)
$res_ciclos = mysqli_query($conn, "SELECT id, nombre, activo FROM ciclos_escolares ORDER BY activo DESC, nombre DESC");

// Grupos
// En un sistema real, filtrarías grupos por ciclo usando AJAX. Aquí cargamos todos.
$res_grupos = mysqli_query($conn, "SELECT id, codigo, grado, turno FROM grupos ORDER BY codigo ASC");

// Materias
$res_materias = mysqli_query($conn, "SELECT id, nombre, codigo FROM materias ORDER BY nombre ASC");

// Profesores (JOIN con users para el nombre)
$sql_profes = "SELECT p.id, u.nombre, u.apellido_paterno FROM profesores p INNER JOIN users u ON p.user_id = u.id WHERE u.is_active = 1 ORDER BY u.nombre ASC";
$res_profes = mysqli_query($conn, $sql_profes);

// Salones
$res_salones = mysqli_query($conn, "SELECT id, codigo, capacidad FROM salones ORDER BY codigo ASC");


// 2. MODO EDICIÓN
if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Clase Programada";
    $btn_texto = "Actualizar Horario";
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
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <button class="btn btn-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
                <h4 class="mb-0 fw-bold text-primary"><?php echo $titulo; ?></h4>
                <div class="d-flex align-items-center">
                    <span class="d-none d-md-block small text-muted me-2"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                    <img src="../../assets/img/avatar.png" alt="Admin" class="rounded-circle border" width="35" height="35">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            
            <div class="mb-3">
                <a href="index.php" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i> Volver a horarios
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            
                            <h5 class="fw-bold mb-4 text-secondary">Configuración de la Sesión</h5>

                            <form action="save_horario.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="p-3 bg-light rounded-3 border mb-4">
                                    <h6 class="fw-bold text-primary mb-3 small text-uppercase">1. ¿A quién y qué se imparte?</h6>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted">Ciclo Escolar</label>
                                            <select name="ciclo_id" class="form-select" required>
                                                <option value="" disabled <?php if(!$ciclo_id) echo 'selected'; ?>>Selecciona...</option>
                                                <?php while($c = mysqli_fetch_assoc($res_ciclos)): ?>
                                                    <option value="<?php echo $c['id']; ?>" <?php if($ciclo_id == $c['id']) echo 'selected'; ?>>
                                                        <?php echo $c['nombre']; ?> <?php echo ($c['activo']==1)?'(ACTIVO)':''; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted">Grupo Destino</label>
                                            <select name="grupo_id" class="form-select" required>
                                                <option value="" disabled <?php if(!$grupo_id) echo 'selected'; ?>>Selecciona el grupo...</option>
                                                <?php while($g = mysqli_fetch_assoc($res_grupos)): ?>
                                                    <option value="<?php echo $g['id']; ?>" <?php if($grupo_id == $g['id']) echo 'selected'; ?>>
                                                        <?php echo $g['codigo']; ?> (<?php echo $g['turno']; ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-12">
                                            <label class="form-label small fw-bold text-muted">Materia</label>
                                            <select name="materia_id" class="form-select" required>
                                                <option value="" disabled <?php if(!$materia_id) echo 'selected'; ?>>Selecciona la asignatura...</option>
                                                <?php while($m = mysqli_fetch_assoc($res_materias)): ?>
                                                    <option value="<?php echo $m['id']; ?>" <?php if($materia_id == $m['id']) echo 'selected'; ?>>
                                                        <?php echo $m['nombre']; ?> (<?php echo $m['codigo']; ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded-3 border mb-4">
                                    <h6 class="fw-bold text-success mb-3 small text-uppercase">2. ¿Quién, Dónde y Cuándo?</h6>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted">Profesor Encargado</label>
                                            <select name="profesor_id" class="form-select" required>
                                                <option value="" disabled <?php if(!$profesor_id) echo 'selected'; ?>>Selecciona docente...</option>
                                                <?php while($p = mysqli_fetch_assoc($res_profes)): ?>
                                                    <option value="<?php echo $p['id']; ?>" <?php if($profesor_id == $p['id']) echo 'selected'; ?>>
                                                        <?php echo $p['nombre'] . ' ' . $p['apellido_paterno']; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted">Aula Asignada</label>
                                            <select name="salon_id" class="form-select" required>
                                                <option value="" disabled <?php if(!$salon_id) echo 'selected'; ?>>Selecciona espacio...</option>
                                                <?php while($s = mysqli_fetch_assoc($res_salones)): ?>
                                                    <option value="<?php echo $s['id']; ?>" <?php if($salon_id == $s['id']) echo 'selected'; ?>>
                                                        <?php echo $s['codigo']; ?> (Cap: <?php echo $s['capacidad']; ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <hr class="text-muted opacity-25">

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted">Día de la Semana</label>
                                            <select name="dia_semana" class="form-select" required>
                                                <option value="" disabled <?php if(!$dia_semana) echo 'selected'; ?>>Elige día...</option>
                                                <option value="Lunes" <?php if($dia_semana=='Lunes') echo 'selected'; ?>>Lunes</option>
                                                <option value="Martes" <?php if($dia_semana=='Martes') echo 'selected'; ?>>Martes</option>
                                                <option value="Miercoles" <?php if($dia_semana=='Miercoles') echo 'selected'; ?>>Miércoles</option>
                                                <option value="Jueves" <?php if($dia_semana=='Jueves') echo 'selected'; ?>>Jueves</option>
                                                <option value="Viernes" <?php if($dia_semana=='Viernes') echo 'selected'; ?>>Viernes</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted">Hora Inicio</label>
                                            <input type="time" name="hora_inicio" class="form-control" value="<?php echo $hora_inicio; ?>" required>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted">Hora Fin</label>
                                            <input type="time" name="hora_fin" class="form-control" value="<?php echo $hora_fin; ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-text mt-2 text-warning">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Asegúrate de no crear conflictos de horario para el salón o el profesor.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-light border px-4">Cancelar</a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn){
        toggleBtn.addEventListener('click', () => {
             const sidebar = document.getElementById('sidebar'); 
             if(sidebar) sidebar.classList.toggle('d-none');
        });
    }
</script>
</body>
</html>