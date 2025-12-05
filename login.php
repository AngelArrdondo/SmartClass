<?php
session_start();

// --- 1. LÓGICA DE REBOTE (Si ya hay sesión abierta, redirigir) ---
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    
    // Verificamos el rol para saber a dónde mandarlo
    if ($_SESSION['role_id'] == 1) {
        header("location: admin/index.php"); // Es Admin -> Panel Admin
    } elseif ($_SESSION['role_id'] == 2) {
        header("location: client/profesor/index.php"); // Es Profe -> Su Portal
    } elseif ($_SESSION['role_id'] == 3) {
        header("location: client/alumno/index.php"); // Es Alumno -> Su Portal
    }
    exit;
}
// ------------------------------------------------------------------

require_once 'config/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Por favor ingrese correo y contraseña.";
    } else {
        $sql = "SELECT id, nombre, apellido_paterno, password_hash, role_id, is_active FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $nombre, $apellido, $hashed_password, $role_id, $is_active);
                mysqli_stmt_fetch($stmt);
                
                if ($is_active == 1) {
                    if (password_verify($password, $hashed_password)) {
                        
                        // --- 2. FILTRO DE SEGURIDAD (Solo Rol 1 entra aquí) ---
                        if ($role_id == 1) { 
                            $_SESSION['user_id'] = $id;
                            $_SESSION['user_name'] = $nombre . ' ' . $apellido;
                            $_SESSION['role_id'] = $role_id;
                            $_SESSION['loggedin'] = true;

                            header("location: admin/index.php");
                            exit;
                        } else {
                            // Si un alumno intenta loguearse aquí (sin sesión previa), le denegamos el acceso
                            // y le sugerimos ir a su portal
                            $error = "Acceso denegado. Este login es solo para Administradores. <br> <a href='client/login.php' class='alert-link'>Ir al Portal Académico</a>";
                        }
                        // ------------------------------------------------------

                    } else {
                        $error = "La contraseña es incorrecta.";
                    }
                } else {
                    $error = "Cuenta desactivada.";
                }
            } else {
                $error = "No existe cuenta con ese correo.";
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
  <title>Inicio de Sesión | SmartClass</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/stylesLogin.css">
</head>
<body class="bg-light">
    
  <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-75 shadow-lg rounded-4 overflow-hidden animate-slide">

      <!-- Panel izquierdo -->
      <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-primary bg-gradient text-white p-5">
        <div class="text-center">
          <img src="assets/img/logo.png" alt="Logo SmartClass" class="img-fluid mb-4" style="max-width: 200px;">
          <h2 class="fw-bold">Bienvenido a SmartClass</h2>
          <p class="mt-2">Optimiza tu gestión académica con tecnología inteligente</p>
        </div>
      </div>

      <!-- Panel derecho -->
      <div class="col-md-6 bg-white p-5 d-flex flex-column justify-content-center animate-slide">
        <h3 class="fw-bold text-center text-primary">Inicio de Sesión</h3>
        <h4 class="fw-bold text-center mb-4 text-primary">SmartClass</h4>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
          <div class="mb-3">
            <label for="correo" class="form-label">Correo electrónico</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
              <input type="email" id="correo" name="email" class="form-control" placeholder="Ingresa tu correo" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
              <input type="password" id="password" name="password" class="form-control" placeholder="Ingresa tu contraseña" required>
            </div>
          </div>

          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill">Iniciar Sesión</button>
          </div>

          <p class="text-center mt-3">
            ¿No tienes una cuenta?
            <a href="register.php" class="text-decoration-none text-primary fw-semibold">Regístrate</a>
          </p>
          
          <div class="text-center mt-2 small">
            <a href="client/login.php" class="text-secondary text-decoration-none">Soy Alumno o Profesor</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>