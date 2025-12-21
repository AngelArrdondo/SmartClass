<?php
session_start();
require_once '../config/db.php'; 

// 1. SEGURIDAD: Solo Admin
// Verificamos sesión y rol (Admin = 1)
if (!isset($_SESSION['loggedin'])) {
    header("location: ../login.php");
    exit;
}

// Redirección inteligente si no es admin
if ($_SESSION['role_id'] != 1) {
    if ($_SESSION['role_id'] == 3) header("location: ../client/alumno/index.php");
    elseif ($_SESSION['role_id'] == 2) header("location: ../client/profesor/index.php");
    else header("location: ../login.php");
    exit;
}

// --------------------------------------------------------
// A. CONSULTAS DE CONTADORES (BANNERS) - ¡CORREGIDO!
// --------------------------------------------------------

// 1. Alumnos ACTIVOS (JOIN con users para ver is_active)
$sql_alumnos = "SELECT COUNT(*) FROM alumnos a 
                INNER JOIN users u ON a.user_id = u.id 
                WHERE u.is_active = 1";
$total_alumnos = mysqli_fetch_row(mysqli_query($conn, $sql_alumnos))[0];

// 2. Profesores ACTIVOS (JOIN con users para ver is_active)
$sql_profes = "SELECT COUNT(*) FROM profesores p 
               INNER JOIN users u ON p.user_id = u.id 
               WHERE u.is_active = 1";
$total_profes = mysqli_fetch_row(mysqli_query($conn, $sql_profes))[0];

// 3. Grupos y Aulas (Estos se quedan igual, cuentan totales)
$total_grupos = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM grupos"))[0];
$total_aulas  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM salones"))[0];


// --------------------------------------------------------
// B. CONSULTA CICLO ESCOLAR ACTIVO
// --------------------------------------------------------
$sql_ciclo = "SELECT * FROM ciclos_escolares WHERE activo = 1 LIMIT 1";
$res_ciclo = mysqli_query($conn, $sql_ciclo);
$ciclo_data = mysqli_fetch_assoc($res_ciclo);

// Variables por defecto si no hay ciclo
$nombre_ciclo = "Sin Ciclo Activo";
$porcentaje_ciclo = 0;
$dias_restantes = 0;

if ($ciclo_data) {
    $nombre_ciclo = $ciclo_data['nombre'];
    
    // Cálculo de fechas
    $inicio = strtotime($ciclo_data['fecha_inicio']);
    $fin    = strtotime($ciclo_data['fecha_fin']);
    $hoy    = time();

    // Calcular Porcentaje
    $duracion_total = $fin - $inicio;
    $transcurrido   = $hoy - $inicio;

    if ($duracion_total > 0) {
        $porcentaje_ciclo = round(($transcurrido / $duracion_total) * 100);
    }
    
    // Limites visuales (0% a 100%)
    if ($porcentaje_ciclo < 0) $porcentaje_ciclo = 0;
    if ($porcentaje_ciclo > 100) $porcentaje_ciclo = 100;

    // Calcular días restantes
    $dias_restantes = ceil(($fin - $hoy) / (60 * 60 * 24));
}

// --------------------------------------------------------
// C. ESTADO DEL SISTEMA (Conflictos de Horario)
// --------------------------------------------------------
$sql_conflictos = "
    SELECT COUNT(*) 
    FROM horarios h1 
    INNER JOIN horarios h2 ON h1.salon_id = h2.salon_id 
        AND h1.dia_semana = h2.dia_semana 
        AND h1.ciclo_id = h2.ciclo_id
        AND h1.id != h2.id 
    WHERE h1.hora_inicio < h2.hora_fin 
      AND h1.hora_fin > h2.hora_inicio
";
$num_conflictos = mysqli_fetch_row(mysqli_query($conn, $sql_conflictos))[0];

// Determinar estatus general
$status_sistema = "Online";
$badge_color = "bg-success";

if ($num_conflictos > 0) {
    $status_sistema = "Atención Requerida";
    $badge_color = "bg-danger";
} elseif ($dias_restantes < 15 && $dias_restantes > 0) {
    $status_sistema = "Cierre Próximo";
    $badge_color = "bg-warning text-dark";
}

$nombre_usuario = $_SESSION['user_name'] ?? 'Administrador';
// Obtener la foto del usuario actual
$user_id = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT foto FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($query_user);

// Determinar la ruta de la imagen
// Si el campo foto no está vacío, usamos la ruta de la carpeta profiles
$foto_perfil = !empty($user_data['foto']) ? "../assets/img/profiles/" . $user_data['foto'] : "../assets/img/avatar.png";
?> 

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Panel Administrador | SmartClass</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="d-flex" id="wrapper">
    
    <?php require_once __DIR__ . '/../includes/menu.php' ?>

    <div id="page-content" class="w-100">
      
      <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <button class="btn btn-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
            
            <h4 class="mb-0 fw-bold text-primary">Dashboard General</h4>

            <div class="dropdown">
              <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="userMenu" data-bs-toggle="dropdown">
                <div class="text-end me-2 d-none d-lg-block">
                    <span class="d-block fw-bold small"><?php echo $nombre_usuario; ?></span>
                    <span class="d-block text-muted small" style="font-size: 0.75rem;">Administrador</span>
                </div>
                <img src="<?php echo $foto_perfil; ?>" alt="Admin" class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="../logout.php">Salir</a></li>
              </ul>
            </div>
        </div>
      </nav>

      <main class="container-fluid p-4">
        
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
            <div class="flex-grow-1">
                <strong>Ciclo Escolar Activo:</strong> <?php echo $nombre_ciclo; ?>
                <?php if($ciclo_data): ?>
                    <div class="progress mt-1" style="height: 6px; width: 200px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $porcentaje_ciclo; ?>%"></div>
                    </div>
                    <small class="text-muted" style="font-size: 0.75rem;"><?php echo $porcentaje_ciclo; ?>% Completado</small>
                <?php else: ?>
                    <br><small>No hay un ciclo activo configurado.</small>
                <?php endif; ?>
            </div>
            <a href="ciclos/index.php" class="btn btn-sm btn-light ms-auto fw-bold text-info">Ver Calendario</a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-0 small text-uppercase fw-bold">Alumnos Inscritos</p>
                                <h2 class="mb-0 fw-bold text-dark"><?php echo $total_alumnos; ?></h2>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded text-primary">
                                <i class="bi bi-mortarboard fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-0 small text-uppercase fw-bold">Docentes Activos</p>
                                <h2 class="mb-0 fw-bold text-dark"><?php echo $total_profes; ?></h2>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded text-success">
                                <i class="bi bi-briefcase fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-0 small text-uppercase fw-bold">Grupos Totales</p>
                                <h2 class="mb-0 fw-bold text-dark"><?php echo $total_grupos; ?></h2>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded text-warning">
                                <i class="bi bi-collection fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-0 small text-uppercase fw-bold">Aulas Disponibles</p>
                                <h2 class="mb-0 fw-bold text-dark"><?php echo $total_aulas; ?></h2>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-3 rounded text-danger">
                                <i class="bi bi-building fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Acciones Rápidas</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3">
                                <a href="alumnos/form.php" class="btn btn-outline-light text-dark border p-3 w-100 h-100 d-flex flex-column align-items-center gap-2 hover-shadow">
                                    <i class="bi bi-person-plus-fill fs-2 text-primary"></i>
                                    <span class="small fw-bold">Nuevo Alumno</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="profesores/form.php" class="btn btn-outline-light text-dark border p-3 w-100 h-100 d-flex flex-column align-items-center gap-2 hover-shadow">
                                    <i class="bi bi-briefcase-fill fs-2 text-success"></i>
                                    <span class="small fw-bold">Alta Profesor</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="horarios/index.php" class="btn btn-outline-light text-dark border p-3 w-100 h-100 d-flex flex-column align-items-center gap-2 hover-shadow">
                                    <i class="bi bi-calendar-plus fs-2 text-danger"></i>
                                    <span class="small fw-bold">Crear Horario</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="reportes/index.php" class="btn btn-outline-light text-dark border p-3 w-100 h-100 d-flex flex-column align-items-center gap-2 hover-shadow">
                                    <i class="bi bi-file-earmark-pdf fs-2 text-secondary"></i>
                                    <span class="small fw-bold">Reportes</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Estado del Sistema</h6>
                        <span class="badge <?php echo $badge_color; ?> rounded-pill"><?php echo $status_sistema; ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            
                            <?php if($num_conflictos > 0): ?>
                                <div class="list-group-item px-3 py-3 list-group-item-danger">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 small fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Conflicto de Aulas</h6>
                                        <small class="text-danger">¡Urgente!</small>
                                    </div>
                                    <p class="mb-1 small">Se detectaron <strong><?php echo $num_conflictos; ?></strong> cruces de horario. Revisa la sección de Horarios.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group-item px-3 py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 small fw-bold text-success"><i class="bi bi-check-circle-fill"></i> Horarios</h6>
                                    </div>
                                    <p class="mb-1 small text-muted">No se detectan conflictos de aulas en este momento.</p>
                                </div>
                            <?php endif; ?>

                            <?php if($ciclo_data): ?>
                                <div class="list-group-item px-3 py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 small fw-bold">Cierre de Ciclo</h6>
                                        <?php 
                                            $clase_dias = ($dias_restantes < 15) ? 'text-danger fw-bold' : 'text-muted';
                                        ?>
                                        <small class="<?php echo $clase_dias; ?>"><?php echo $dias_restantes; ?> días</small>
                                    </div>
                                    <p class="mb-1 small text-muted">Fecha fin: <?php echo date("d/m/Y", strtotime($ciclo_data['fecha_fin'])); ?></p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>

      </main>

      <footer class="py-3 text-center text-muted small bg-white mt-auto border-top">
        © 2025 SmartClass — Sistema Integral de Gestión
      </footer>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const wrapper = document.getElementById('wrapper');
  const btnToggle = document.getElementById('btnToggleSidebar');

  if(btnToggle){
      btnToggle.addEventListener('click', () => {
         wrapper.classList.toggle('toggled'); 
      });
  }
</script>

</body>
</html>