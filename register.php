<?php
require_once 'config/db.php'; 

$msg = ""; 
$msg_type = ""; 

// Inicializamos variables para rellenar el form si falla
$nombre = "";
$paterno = "";
$materno = "";
$email = "";
$telefono = "";

// TOKEN DE SEGURIDAD
$secret_token = "UTEQ_ADMIN_2025"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recibimos los datos separados
    $nombre = trim($_POST['nombre']);
    $paterno = trim($_POST['apellido_paterno']);
    $materno = trim($_POST['apellido_materno']); // Opcional
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $token_ingresado = $_POST['token']; 

    // Validaciones
    if (empty($nombre) || empty($paterno) || empty($email) || empty($password) || empty($token_ingresado)) {
        $msg = "Por favor completa los campos obligatorios (*).";
        $msg_type = "danger";
    } elseif ($token_ingresado !== $secret_token) {
        $msg = "¡Token de seguridad incorrecto!";
        $msg_type = "danger";
    } elseif ($password !== $confirm_password) {
        $msg = "Las contraseñas no coinciden.";
        $msg_type = "danger";
    } elseif (strlen($password) < 6) {
        $msg = "La contraseña debe tener al menos 6 caracteres.";
        $msg_type = "danger";
    } else {
        
        // Verificar correo duplicado
        $sql_check = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql_check)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $msg = "Este correo ya está registrado.";
                $msg_type = "danger";
            } else {
                // Encriptar password
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $role_id = 1; // ROL DE ADMIN

                // QUERY ACTUALIZADO: Insertamos los 3 nombres por separado
                $sql_insert = "INSERT INTO users (nombre, apellido_paterno, apellido_materno, email, telefono, password_hash, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                if ($stmt_insert = mysqli_prepare($conn, $sql_insert)) {
                    // "ssssssi" = 6 strings, 1 integer
                    mysqli_stmt_bind_param($stmt_insert, "ssssssi", $nombre, $paterno, $materno, $email, $telefono, $param_password, $role_id);
                    
                    if (mysqli_stmt_execute($stmt_insert)) {
                        $msg = "¡Administrador registrado! Redirigiendo...";
                        $msg_type = "success";
                        echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
                    } else {
                        $msg = "Error DB: " . mysqli_error($conn);
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
  <title>Registro Admin | SmartClass</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/stylesRegister.css">
</head>
<body class="bg-lightblue d-flex align-items-center justify-content-center min-vh-100">
  
  <div class="container text-center">
    <div class="row register-box shadow-lg rounded-4 overflow-hidden mx-auto">
      
      <div class="col-md-5 d-none d-md-flex align-items-center justify-content-center bg-primary bg-gradient text-white p-5">
        <div class="text-center">
          <img src="assets/img/logo.png" alt="Logo" class="img-fluid mb-4" style="max-width: 160px;">
          <h3 class="fw-bold">Integral Solutions</h3>
          <p class="mt-2 small">Gestión administrativa segura</p>
        </div>
      </div>

      <div class="col-md-7 bg-white p-4 d-flex flex-column justify-content-center">
        <h4 class="fw-bold text-center mb-3 text-primary">Alta de Administrador</h4>

        <?php if(!empty($msg)): ?>
            <div class="alert alert-<?php echo $msg_type; ?> py-2 role="alert">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
          
          <div class="mb-3 text-start">
            <label class="form-label fw-bold text-primary small" placeholder="UTEQ_ADMIN_2025">Token de Seguridad *</label>
            <input type="password" name="token" class="form-control form-control-sm border-primary" placeholder="Código requerido" required>
          </div>
          <hr class="text-primary my-2">

          <div class="row">
            <div class="col-md-6 mb-2 text-start">
                <label class="form-label fw-semibold small">Nombre(s) *</label>
                <input type="text" name="nombre" class="form-control" value="<?php echo $nombre; ?>" required>
            </div>
            <div class="col-md-6 mb-2 text-start">
                <label class="form-label fw-semibold small">Apellido Paterno *</label>
                <input type="text" name="apellido_paterno" class="form-control" value="<?php echo $paterno; ?>" required>
            </div>
          </div>

          <div class="row">
             <div class="col-md-6 mb-2 text-start">
                <label class="form-label fw-semibold small">Apellido Materno</label>
                <input type="text" name="apellido_materno" class="form-control" value="<?php echo $materno; ?>">
            </div>
            <div class="col-md-6 mb-2 text-start">
                <label class="form-label fw-semibold small">Teléfono</label>
                <input type="tel" name="telefono" class="form-control" value="<?php echo $telefono; ?>">
            </div>
          </div>

          <div class="mb-2 text-start">
            <label class="form-label fw-semibold small">Correo Institucional *</label>
            <input type="email" name="email" class="form-control" value="<?php echo $email; ?>" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3 text-start">
                <label class="form-label fw-semibold small">Contraseña *</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3 text-start">
                <label class="form-label fw-semibold small">Confirmar *</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-warning text-white fw-semibold">Registrar Admin</button>
          </div>

          <p class="text-center mt-3 mb-0 small">
            <a href="login.php" class="text-decoration-none text-primary">Volver al Login</a>
          </p>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!-- UTEQ_ADMIN_2025 -->