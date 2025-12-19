<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// --- LÓGICA DE CONTROL ---
$modo_edicion = false;
$titulo = "Nueva Materia";
$btn_texto = "Registrar Materia";

$id = ""; $codigo = ""; $nombre = ""; $creditos = "0"; $descripcion = ""; 

if (isset($_GET['id'])) {
    $modo_edicion = true;
    $titulo = "Editar Materia";
    $btn_texto = "Guardar Cambios";
    $id = $_GET['id'];
    
    $sql = "SELECT * FROM materias WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        if ($fila = mysqli_fetch_assoc($resultado)) {
            $codigo = $fila['codigo'];
            $nombre = $fila['nombre'];
            $creditos = $fila['creditos'];
            $descripcion = $fila['descripcion'] ?? "";
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
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            
                            <form action="save_materia.php" method="POST" id="formMateria" class="needs-validation" novalidate>
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="d-flex justify-content-between mb-4 border-bottom pb-2">
                                    <h5 class="text-secondary fw-bold">1. Identificación de la Asignatura</h5>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold">Nombre de la Materia *</label>
                                        <input type="text" name="nombre" id="nombreMateria" class="form-control" 
                                               placeholder="Ej. Cálculo Diferencial" 
                                               value="<?php echo $nombre; ?>" 
                                               required maxlength="100"
                                               pattern="^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s]+$">
                                        <div class="invalid-feedback">Ingresa un nombre válido (letras y números únicamente).</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Código Interno *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-tag"></i></span>
                                            <input type="text" name="codigo" id="codigoMateria" 
                                                   class="form-control border-start-0 fw-bold text-primary text-uppercase" 
                                                   placeholder="Ej. MAT-101" value="<?php echo $codigo; ?>" 
                                                   required maxlength="15">
                                            <div class="invalid-feedback">El código es obligatorio.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold">Créditos Académicos *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-mortarboard"></i></span>
                                            <input type="number" name="creditos" id="creditosMateria" 
                                                   class="form-control border-start-0" 
                                                   placeholder="0" min="0" max="20" 
                                                   value="<?php echo $creditos; ?>" required>
                                            <div class="invalid-feedback">Debe ser un número entre 0 y 20.</div>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="text-secondary fw-bold mb-4 border-bottom pb-2">2. Información Adicional</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Descripción / Temario Breve</label>
                                        <textarea name="descripcion" class="form-control bg-light" rows="4" 
                                                  maxlength="500"
                                                  placeholder="Describe los temas principales de la materia..."><?php echo $descripcion; ?></textarea>
                                        <div class="form-text d-flex justify-content-between">
                                            <span>Resumen del contenido curricular.</span>
                                            <span id="charCount">0 / 500</span>
                                        </div>
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

    // 1. Convertir Código a Mayúsculas automáticamente
    const codigoInput = document.getElementById('codigoMateria');
    codigoInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/\s/g, '');
    });

    // 2. Contador de caracteres para la descripción
    const textarea = document.querySelector('textarea[name="descripcion"]');
    const charCount = document.getElementById('charCount');
    textarea.addEventListener('input', function() {
        charCount.textContent = `${this.value.length} / 500`;
    });

    // 3. Prevenir números negativos en créditos por teclado
    const creditosInput = document.getElementById('creditosMateria');
    creditosInput.addEventListener('keydown', function(e) {
        if (e.key === '-' || e.key === 'e' || e.key === '+') {
            e.preventDefault();
        }
    });
</script>

</body>
</html>