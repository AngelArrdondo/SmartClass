<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD: Solo Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// Obtener datos para los selectores
$grupos = mysqli_query($conn, "SELECT id, codigo FROM grupos ORDER BY codigo");
$salones = mysqli_query($conn, "SELECT id, nombre, codigo FROM salones ORDER BY codigo");

// 2. DATOS DEL USUARIO (Para el Header)
$nombre_usuario = $_SESSION['user_name'] ?? 'Administrador';
$user_id = $_SESSION['user_id'];
$query_user = mysqli_query($conn, "SELECT foto FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($query_user);
$foto_perfil = !empty($user_data['foto']) ? "../../assets/img/profiles/" . $user_data['foto'] : "../../assets/img/avatar.png";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Centro de Reportes | SmartClass</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .card-report {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
        }
        .card-report:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.1) !important;
        }
        .icon-box {
            width: 60px; height: 60px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .breadcrumb-item a { text-decoration: none; color: #6c757d; }
    </style>
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php'; ?>

    <div id="page-content" class="w-100">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
                    <h4 class="mb-0 fw-bold text-primary">Reportes Académicos</h4>
                </div>

                <div class="dropdown">
                  <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="userMenu" data-bs-toggle="dropdown">
                    <div class="text-end me-2 d-none d-lg-block">
                        <span class="d-block fw-bold small"><?php echo $nombre_usuario; ?></span>
                        <span class="d-block text-muted small" style="font-size: 0.75rem;">Administrador</span>
                    </div>
                    <img src="<?php echo $foto_perfil; ?>" alt="Admin" class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item" href="../perfil.php">Mi Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../../logout.php">Salir</a></li>
                  </ul>
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            
            <nav aria-label="breadcrumb" class="mb-4">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Centro de Reportes</li>
              </ol>
            </nav>

            <div class="row mb-5 text-center">
                <div class="col-12">
                    <h2 class="fw-bold text-dark">Documentación y Consultas</h2>
                    <p class="text-muted">Genera archivos oficiales listos para imprimir o guardar en PDF.</p>
                </div>
            </div>

            <div class="row g-4">
                
                <div class="col-md-4">
                    <div class="card card-report shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-calendar3 fs-2"></i>
                            </div>
                            <h5 class="fw-bold">Horario Semanal</h5>
                            <p class="text-muted small">Visualización en cuadrícula de lunes a viernes por grupo.</p>
                            
                            <?php if(mysqli_num_rows($grupos) > 0): ?>
                            <form action="pdf_horario.php" method="GET" target="_blank" class="mt-auto report-form">
                                <select name="grupo_id" class="form-select mb-3" required>
                                    <option value="">Elegir Grupo...</option>
                                    <?php mysqli_data_seek($grupos, 0); ?>
                                    <?php while($g = mysqli_fetch_assoc($grupos)): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo $g['codigo']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Ver Horario
                                </button>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-light border-0 small mt-auto text-center">No hay grupos registrados.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-report shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="icon-box bg-success bg-opacity-10 text-success">
                                <i class="bi bi-people fs-2"></i>
                            </div>
                            <h5 class="fw-bold">Lista de Asistencia</h5>
                            <p class="text-muted small">Formato de pase de lista con matrícula y nombre del alumno.</p>
                            
                            <?php if(mysqli_num_rows($grupos) > 0): ?>
                            <form action="pdf_asistencia.php" method="GET" target="_blank" class="mt-auto report-form">
                                <select name="grupo_id" class="form-select mb-3" required>
                                    <option value="">Elegir Grupo...</option>
                                    <?php mysqli_data_seek($grupos, 0); ?>
                                    <?php while($g = mysqli_fetch_assoc($grupos)): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo $g['codigo']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" class="btn btn-success w-100 rounded-pill text-white fw-bold">
                                    <i class="bi bi-printer me-2"></i>Imprimir Lista
                                </button>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-light border-0 small mt-auto text-center text-success">No hay alumnos registrados.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-report shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="icon-box bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-building-check fs-2"></i>
                            </div>
                            <h5 class="fw-bold">Ocupación de Aula</h5>
                            <p class="text-muted small">Consulta qué grupos y profesores están usando cada aula.</p>
                            
                            <?php if(mysqli_num_rows($salones) > 0): ?>
                            <form action="pdf_ocupacion.php" method="GET" target="_blank" class="mt-auto report-form">
                                <select name="salon_id" class="form-select mb-3" required>
                                    <option value="">Elegir Salón...</option>
                                    <?php while($s = mysqli_fetch_assoc($salones)): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo $s['codigo']; ?> - <?php echo $s['nombre']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" class="btn btn-warning w-100 rounded-pill text-dark fw-bold">
                                    <i class="bi bi-search me-2"></i>Ver Disponibilidad
                                </button>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-light border-0 small mt-auto text-center text-warning">No hay aulas registradas.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Sidebar
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn){
        toggleBtn.addEventListener('click', () => {
             document.getElementById('wrapper').classList.toggle('toggled');
        });
    }

    // Efecto de carga en botones
    document.querySelectorAll('.report-form').forEach(form => {
        form.onsubmit = function() {
            const btn = this.querySelector('button');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generando...';
            
            // Re-habilitar después de 3 segundos
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }, 3000);
        };
    });
</script>
</body>
</html>