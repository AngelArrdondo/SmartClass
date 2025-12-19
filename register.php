<?php
require_once 'config/db.php'; 

$msg = ""; 
$msg_type = ""; 

$nombre = $paterno = $materno = $email = $telefono = "";
$secret_token = "UTEQ_ADMIN_2025"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recibimos y saneamos datos
    $nombre   = trim($_POST['nombre']);
    $paterno  = trim($_POST['paterno']); // Corregido el name del post
    $materno  = trim($_POST['apellido_materno']);
    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $token_ingresado  = $_POST['token']; 

    // --- VALIDACIONES DE SERVIDOR ---

    if (empty($nombre) || empty($paterno) || empty($email) || empty($password) || empty($token_ingresado)) {
        $msg = "Por favor completa los campos obligatorios (*).";
        $msg_type = "danger";
    } 
    elseif ($token_ingresado !== $secret_token) {
        $msg = "¡Token de seguridad incorrecto!";
        $msg_type = "danger";
    }
    // Validar formato de nombres (Solo letras y espacios)
    elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $paterno)) {
        $msg = "Los nombres y apellidos solo deben contener letras.";
        $msg_type = "danger";
    }
    // Validar Email con arroba y formato
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "El formato de correo electrónico no es válido.";
        $msg_type = "danger";
    }
    // Validar Teléfono (Exactamente 10 dígitos)
    elseif (!preg_match("/^[0-9]{10}$/", $telefono)) {
        $msg = "El teléfono debe tener exactamente 10 dígitos numéricos.";
        $msg_type = "danger";
    }
    // Validar Password
    elseif ($password !== $confirm_password) {
        $msg = "Las contraseñas no coinciden.";
        $msg_type = "danger";
    } 
    elseif (strlen($password) < 8) {
        $msg = "La seguridad requiere al menos 8 caracteres en la contraseña.";
        $msg_type = "danger";
    } 
    else {
        // Verificar correo duplicado
        $sql_check = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql_check)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $msg = "Este correo ya está registrado en el sistema.";
                $msg_type = "danger";
            } else {
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $role_id = 1; // ROL ADMIN

                $sql_insert = "INSERT INTO users (nombre, apellido_paterno, apellido_materno, email, telefono, password_hash, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                
                if ($stmt_insert = mysqli_prepare($conn, $sql_insert)) {
                    mysqli_stmt_bind_param($stmt_insert, "ssssssi", $nombre, $paterno, $materno, $email, $telefono, $param_password, $role_id);
                    
                    if (mysqli_stmt_execute($stmt_insert)) {
                        $msg = "¡Administrador registrado exitosamente! Redirigiendo...";
                        $msg_type = "success";
                        echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
                    } else {
                        $msg = "Error en el registro: " . mysqli_error($conn);
                        $msg_type = "danger";
                    }
                    mysqli_stmt_close($stmt_insert);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Admin | SmartClass</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f0f4f8; }
    .register-container { max-width: 950px; border-radius: 20px; overflow: hidden; }
    .bg-gradient-admin { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.1); }
    .btn-admin { background-color: #facc15; border: none; color: #1e3a8a; font-weight: 700; }
    .btn-admin:hover { background-color: #eab308; }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">
  
  <div class="container register-container shadow-lg bg-white p-0">
    <div class="row g-0">
      
      <div class="col-md-5 d-none d-md-flex flex-column align-items-center justify-content-center bg-gradient-admin text-white p-5 text-center">
        <img src="assets/img/logo.png" alt="Logo SmartClass" class="img-fluid mb-4" style="max-width: 160px;">
        <h3 class="fw-bold">SmartClass Admin</h3>
        <p class="opacity-75">Creación de cuentas de alta jerarquía</p>
        <hr class="w-25 border-light">
        <small class="mt-2"><i class="bi bi-shield-check-fill"></i> Conexión Segura SSL</small>
      </div>

      <div class="col-md-7 p-4 p-lg-5">
        <h4 class="fw-bold text-primary mb-3">Alta de Administrador</h4>
        <p class="text-muted small mb-4">Complete los campos para generar un nuevo perfil administrativo.</p>

        <?php if(!empty($msg)): ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show py-2 small" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
          
          <div class="mb-3">
            <label class="form-label fw-bold text-primary small">Token de Seguridad *</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-primary"><i class="bi bi-key text-primary"></i></span>
                <input type="password" name="token" class="form-control border-primary" placeholder="Clave de autorización" required>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-2">
                <label class="form-label fw-semibold small">Nombre(s) *</label>
                <input type="text" name="nombre" class="form-control" value="<?php echo $nombre; ?>" 
                       pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" title="Solo letras" required>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label fw-semibold small">Apellido Paterno *</label>
                <input type="text" name="paterno" class="form-control" value="<?php echo $paterno; ?>" 
                       pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" title="Solo letras" required>
            </div>
          </div>

          <div class="row">
             <div class="col-md-6 mb-2">
                <label class="form-label fw-semibold small">Apellido Materno</label>
                <input type="text" name="apellido_materno" class="form-control" value="<?php echo $materno; ?>" 
                       pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" title="Solo letras">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label fw-semibold small">Teléfono (10 dígitos) *</label>
                <input type="tel" name="telefono" class="form-control" value="<?php echo $telefono; ?>" 
                       pattern="[0-9]{10}" maxlength="10" title="Debe contener exactamente 10 números" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Correo Institucional *</label>
            <input type="email" name="email" class="form-control" value="<?php echo $email; ?>" 
                   placeholder="usuario@uteq.edu.mx" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4">
                <label class="form-label fw-semibold small">Contraseña *</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label fw-semibold small">Confirmar Contraseña *</label>
                <input type="password" name="confirm_password" class="form-control" minlength="8" required>
            </div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-admin btn-lg rounded-3 shadow-sm">Registrar Administrador</button>
            <a href="login.php" class="btn btn-link btn-sm text-decoration-none text-muted">
              ¿Ya tienes cuenta? <span class="text-primary fw-bold text-decoration-none">Inicia sesión</span>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>