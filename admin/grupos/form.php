<?php
session_start();
require_once '../../config/db.php'; 

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

$modo_edicion = false;
$titulo = "Nuevo Grupo";
$btn_texto = "Crear Grupo";

$id = ""; $codigo = ""; $grado = ""; $turno = ""; $ciclo_id = ""; 

// 1. OBTENER CICLOS ESCOLARES
$sql_ciclos = "SELECT id, nombre, activo FROM ciclos_escolares ORDER BY activo DESC, fecha_inicio DESC";
$res_ciclos = mysqli_query($conn, $sql_ciclos);

// 2. MODO EDICIÓN
if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Grupo";
    $btn_texto = "Guardar Cambios";
    $id = $_GET['id'];
    
    $sql = "SELECT * FROM grupos WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $codigo = $fila['codigo'];
            $grado = $fila['grado'];
            $turno = $fila['turno'];
            $ciclo_id = $fila['ciclo_id'];
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
    <style>
        .icon-circle {
            width: 45px; height: 45px;
            background-color: #f0f7ff; color: #0d6efd;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; font-size: 1.2rem;
        }
        /* Estilos para validación visual */
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        }
    </style>
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php'; ?>

    <div id="page-content" class="w-100">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
                    <h4 class="mb-0 fw-bold text-primary"><?php echo $titulo; ?></h4>
                </div>
                <div class="d-flex align-items-center">
                    <img src="../../assets/img/avatar.png" alt="Admin" class="rounded-circle border" width="35" height="35">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8"> 
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            
                            <form action="save_grupo.php" method="POST" class="needs-validation" novalidate id="formGrupo">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

                                <div class="d-flex align-items-center mb-4">
                                    <div class="icon-circle me-3"><i class="bi bi-calendar-check"></i></div>
                                    <div>
                                        <h5 class="fw-bold mb-0">1. Asignación de Periodo</h5>
                                        <p class="text-muted small mb-0">Seleccione el ciclo escolar correspondiente.</p>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted">Ciclo Escolar <span class="text-danger">*</span></label>
                                    <select name="ciclo_id" id="ciclo_id" class="form-select border-primary-subtle shadow-sm py-2" required>
                                        <option value="" selected disabled>Elegir periodo...</option>
                                        <?php while($c = mysqli_fetch_assoc($res_ciclos)): ?>
                                            <?php 
                                                $es_activo = ($c['activo'] == 1);
                                                $label = $es_activo ? "🟢 " . $c['nombre'] . " (Vigente)" : "⚪ " . $c['nombre'];
                                                $selected = ($ciclo_id == $c['id']) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo $c['id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="invalid-feedback">Debes seleccionar un ciclo escolar.</div>
                                </div>

                                <div class="d-flex align-items-center mb-4 pt-2 border-top">
                                    <div class="icon-circle me-3 mt-3"><i class="bi bi-grid-1x2"></i></div>
                                    <div class="mt-3">
                                        <h5 class="fw-bold mb-0">2. Identificación del Grupo</h5>
                                        <p class="text-muted small mb-0">Defina el nivel académico y turno.</p>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Grado / Semestre *</label>
                                        <input type="number" name="grado" id="grado" class="form-control" 
                                               placeholder="1 - 12" min="1" max="12" 
                                               value="<?php echo htmlspecialchars($grado); ?>" required>
                                        <div class="invalid-feedback">Ingresa un grado válido (1-12).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Turno *</label>
                                        <select name="turno" id="turno" class="form-select" required>
                                            <option value="" selected disabled>Elegir turno...</option>
                                            <option value="Matutino" <?php echo ($turno == 'Matutino') ? 'selected' : ''; ?>>☀️ Matutino</option>
                                            <option value="Vespertino" <?php echo ($turno == 'Vespertino') ? 'selected' : ''; ?>>🌙 Vespertino</option>
                                        </select>
                                        <div class="invalid-feedback">Selecciona un turno.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted">Código de Grupo *</label>
                                        <div class="input-group has-validation">
                                            <span class="input-group-text bg-light text-muted fw-bold">ID</span>
                                            <input type="text" name="codigo" id="codigo" 
                                                   class="form-control fw-bold text-primary text-uppercase" 
                                                   placeholder="Ej. 2A-2025" 
                                                   value="<?php echo htmlspecialchars($codigo); ?>" 
                                                   pattern="^[A-Za-z0-9\-]+$"
                                                   required>
                                            <div class="invalid-feedback">El código es obligatorio y solo permite letras, números y guiones.</div>
                                        </div>
                                        <div class="form-text small">Sugerencia: <strong>[GRADO][LETRA]-[AÑO]</strong></div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                    <a href="index.php" class="btn btn-light border px-4">Cancelar</a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formGrupo');
        const codigoInput = document.getElementById('codigo');

        // 1. Validación nativa de Bootstrap
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);

        // 2. Formateo automático de Código (Mayúsculas y sin espacios)
        codigoInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/\s/g, '');
        });

        // 3. Prevenir ingreso de caracteres no válidos en Grado por teclado
        document.getElementById('grado').addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key)) e.preventDefault();
        });
    });

    // Sidebar Toggle
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn){
        toggleBtn.addEventListener('click', () => {
             document.getElementById('wrapper').classList.toggle('toggled');
        });
    }
</script>
</body>
</html>