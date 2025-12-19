<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

$modo_edicion = false;
$titulo = "Nuevo Profesor";
$btn_texto = "Registrar Profesor";

$user_id = ""; $profesor_id = ""; $nombre = ""; $ape_pat = ""; $ape_mat = "";
$email = ""; $telefono = ""; $is_active = 1; $codigo = ""; $especialidad = "";

if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Profesor";
    $btn_texto = "Guardar Cambios";
    $profesor_id = $_GET['id'];

    $sql = "SELECT p.id as prof_id, p.codigo_empleado, p.especialidad, u.id as usr_id, u.nombre, u.apellido_paterno, u.apellido_materno, u.email, u.telefono, u.is_active
            FROM profesores p INNER JOIN users u ON p.user_id = u.id WHERE p.id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $profesor_id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $user_id = $fila['usr_id']; $nombre = $fila['nombre']; $ape_pat = $fila['apellido_paterno'];
            $ape_mat = $fila['apellido_materno']; $email = $fila['email']; $telefono = $fila['telefono'];
            $is_active = $fila['is_active']; $codigo = $fila['codigo_empleado']; $especialidad = $fila['especialidad'];
        }
    }
} else {
    $anio = date("Y");
    $prefix = "PROF-$anio-";
    $sql_last = "SELECT codigo_empleado FROM profesores WHERE codigo_empleado LIKE '$prefix%' ORDER BY codigo_empleado DESC LIMIT 1";
    $res_last = mysqli_query($conn, $sql_last);
    if ($f = mysqli_fetch_assoc($res_last)) {
        $num = (int)substr($f['codigo_empleado'], -3);
        $codigo = $prefix . str_pad($num + 1, 3, "0", STR_PAD_LEFT);
    } else {
        $codigo = $prefix . "001";
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
                            <form action="save_profesor.php" method="POST" id="formProfesor" class="needs-validation" novalidate>
                                <input type="hidden" name="profesor_id" value="<?php echo $profesor_id; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                                <div class="d-flex justify-content-between mb-4 border-bottom pb-2">
                                    <h5 class="text-secondary fw-bold">1. Datos Personales</h5>
                                    <div class="d-flex align-items-center">
                                        <label class="small fw-bold me-2 text-muted">ESTADO:</label>
                                        <select name="is_active" class="form-select form-select-sm w-auto">
                                            <option value="1" <?php echo $is_active==1?'selected':''; ?>>🟢 Activo</option>
                                            <option value="0" <?php echo $is_active==0?'selected':''; ?>>🔴 Inactivo</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nombre(s) *</label>
                                        <input type="text" name="nombre" id="nombre" class="form-control" 
                                               value="<?php echo $nombre; ?>" required 
                                               pattern="[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+" 
                                               title="Solo se permiten letras y espacios">
                                        <div class="invalid-feedback">Ingresa un nombre válido (solo letras).</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Apellido Paterno *</label>
                                        <input type="text" name="apellido_paterno" id="ape_pat" class="form-control" 
                                               value="<?php echo $ape_pat; ?>" required 
                                               pattern="[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+"
                                               title="Solo se permiten letras y espacios">
                                        <div class="invalid-feedback">Ingresa un apellido válido.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Apellido Materno</label>
                                        <input type="text" name="apellido_materno" class="form-control" 
                                               value="<?php echo $ape_mat; ?>" pattern="[A-Za-zñÑáéíóúÁÉÍÓÚ\s]*">
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-bold">Correo Institucional (Auto)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope-at"></i></span>
                                            <input type="email" name="email" id="email" class="form-control border-start-0 bg-light fw-bold" 
                                                   value="<?php echo $email; ?>" readonly required>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold">Teléfono (10 dígitos) *</label>
                                        <input type="tel" name="telefono" class="form-control" 
                                               value="<?php echo $telefono; ?>" required
                                               pattern="[0-9]{10}" 
                                               maxlength="10"
                                               title="Deben ser exactamente 10 números"
                                               onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                                        <div class="invalid-feedback">Debes ingresar exactamente 10 números.</div>
                                    </div>
                                </div>

                                <h5 class="text-secondary fw-bold mb-4 border-bottom pb-2">2. Información del Cargo</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Código de Empleado (Usuario/Pass)</label>
                                        <input type="text" name="codigo_empleado" id="codigo_empleado" 
                                               class="form-control bg-light fw-bold text-primary" 
                                               value="<?php echo $codigo; ?>" readonly required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Especialidad *</label>
                                        <select name="especialidad" class="form-select" required>
                                            <option value="">Seleccione una...</option>
                                            <option value="Matemáticas" <?php echo $especialidad=='Matemáticas'?'selected':''; ?>>Matemáticas</option>
                                            <option value="Ciencias" <?php echo $especialidad=='Ciencias'?'selected':''; ?>>Ciencias</option>
                                            <option value="Idiomas" <?php echo $especialidad=='Idiomas'?'selected':''; ?>>Idiomas</option>
                                            <option value="Tecnología" <?php echo $especialidad=='Tecnología'?'selected':''; ?>>Tecnología</option>
                                            <option value="Humanidades" <?php echo $especialidad=='Humanidades'?'selected':''; ?>>Humanidades</option>
                                        </select>
                                        <div class="invalid-feedback">Selecciona una especialidad.</div>
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

    // --- LÓGICA DE GENERACIÓN DE CORREO ---
    const nombre = document.getElementById('nombre');
    const apePat = document.getElementById('ape_pat');
    const emailField = document.getElementById('email');
    const codigoField = document.getElementById('codigo_empleado');

    function generarCorreo() {
        if(nombre.value && apePat.value) {
            let cleanNombre = nombre.value.trim().split(' ')[0].toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            let cleanApe = apePat.value.trim().split(' ')[0].toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            let cod = codigoField.value.toLowerCase();
            emailField.value = `${cleanNombre}.${cleanApe}.${cod}@smartclass.com`;
        }
    }

    nombre.addEventListener('input', generarCorreo);
    apePat.addEventListener('input', generarCorreo);
</script>
</body>
</html>