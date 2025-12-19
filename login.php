<?php
session_start();

// --- 1. LÓGICA DE REBOTE ---
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $redirecciones = [
        1 => "admin/index.php",
        2 => "client/profesor/index.php",
        3 => "client/alumno/index.php"
    ];
    $url = $redirecciones[$_SESSION['role_id']] ?? "login.php";
    header("location: $url");
    exit;
}

require_once 'config/db.php';

$error = '';

// Inicializar contador de intentos si no existe
if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Saneamiento de entradas
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // Bloqueo temporal por intentos fallidos (Seguridad contra fuerza bruta)
    if ($_SESSION['attempts'] >= 5) {
        $error = "Demasiados intentos fallidos. Por seguridad, el acceso ha sido pausado.";
    } 
    elseif (empty($email) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } 
    else {
        // Consulta preparada para evitar Inyección SQL
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
                        
                        // FILTRO DE SEGURIDAD EXCLUSIVO ADMIN (Rol 1)
                        if ($role_id == 1) {
                            $_SESSION['attempts'] = 0;
                            session_regenerate_id(true);

                            $_SESSION['user_id'] = $id;
                            $_SESSION['user_name'] = $nombre . ' ' . $apellido;
                            $_SESSION['role_id'] = $role_id;
                            $_SESSION['loggedin'] = true;

                            header("location: admin/index.php");
                            exit;
                        } else {
                            $error = "<strong>Acceso Denegado.</strong> Este panel es para administradores.<br><a href='client/login.php' class='alert-link'>Ir al Portal Alumnos/Profesores</a>";
                        }
                    } else {
                        $_SESSION['attempts']++;
                        $error = "Contraseña incorrecta. Intento " . $_SESSION['attempts'] . " de 5.";
                    }
                } else {
                    $error = "Tu cuenta está desactivada temporalmente.";
                }
            } else {
                $error = "No existe una cuenta de administrador con ese correo.";
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
  <title>Login Administrativo | SmartClass</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f0f2f5; }
    .login-container { max-width: 950px; border-radius: 20px; overflow: hidden; }
    .bg-gradient-admin { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); }
    .input-group-text { border-right: none; background-color: #fff; }
    .form-control { border-left: none; }
    .form-control:focus { box-shadow: none; border-color: #dee2e6; }
    .btn-admin { background-color: #facc15; border: none; color: #1e3a8a; font-weight: 700; }
    .btn-admin:hover { background-color: #eab308; transform: translateY(-1px); }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 p-3">
    
  <div class="container login-container shadow-2xl bg-white p-0">
    <div class="row g-0">

      <div class="col-md-6 d-none d-md-flex flex-column align-items-center justify-content-center bg-gradient-admin text-white p-5 text-center">
        <img src="assets/img/logo.png" alt="Logo SmartClass" class="img-fluid mb-4" style="max-width: 180px;">
        <h2 class="fw-bold">SmartClass Admin</h2>
        <p class="opacity-75">Panel de gestión centralizada y seguridad institucional.</p>
        <div class="mt-3">
            <i class="bi bi-shield-lock" style="font-size: 2rem;"></i>
        </div>
      </div>

      <div class="col-md-6 p-4 p-lg-5">
        <div class="mb-4">
            <h3 class="fw-bold text-dark">Bienvenido</h3>
            <p class="text-muted small">Ingresa tus credenciales de administrador</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Correo Electrónico</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope-at text-primary"></i></span>
              <input type="email" name="email" class="form-control" placeholder="nombre@uteq.edu.mx" required>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-bold text-secondary">Contraseña</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill text-primary"></i></span>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-admin btn-lg rounded-3 shadow-sm">Acceder al Sistema</button>
          </div>

          <div class="text-center mt-4">
            <!-- Registro administrativo -->
            <p class="small text-muted mb-3">
              ¿Necesitas una cuenta administrativa? <br>
              <a href="register.php" class="text-primary fw-bold text-decoration-none">Solicitar Registro</a>
            </p>
            <hr class="my-3">
            <!-- Botón de acceso Alumnos/Profesores -->
            <div class="mb-3">
              <a href="client/login.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 me-2 mb-2">
                <i class="bi bi-people-fill me-2"></i>Acceso Alumnos y Profesores
              </a>
            </div>
            <!-- Link para volver al inicio -->
            <div>
              <a href="/SmartClass/SmartClassHomePage/SmartClassHomePage/index1.html"
                class="text-decoration-none text-secondary small">
                Volver al inicio
              </a>
            </div>
          </div>
        </form>
      </div>
      
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>