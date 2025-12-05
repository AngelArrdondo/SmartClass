<?php
session_start();

// 1. REBOTE: Si ya está logueado, lo mandamos a su panel
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['role_id'] == 3) {
        header("location: alumno/index.php");
    } elseif ($_SESSION['role_id'] == 2) {
        header("location: profesor/index.php");
    } elseif ($_SESSION['role_id'] == 1) {
        header("location: ../admin/index.php");
    }
    exit;
}

require_once '../config/db.php'; 

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Por favor ingrese su correo y contraseña.";
    } else {
        $sql = "SELECT id, nombre, apellido_paterno, password_hash, role_id, is_active FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $nombre, $apellido, $hashed_password, $role_id, $is_active);
                    mysqli_stmt_fetch($stmt);
                    
                    if ($is_active == 1) {
                        if (password_verify($password, $hashed_password)) {
                            
                            $_SESSION['user_id'] = $id;
                            $_SESSION['user_name'] = $nombre . ' ' . $apellido;
                            $_SESSION['role_id'] = $role_id;
                            $_SESSION['loggedin'] = true;

                            switch ($role_id) {
                                case 2: // PROFESOR
                                    header("location: profesor/index.php");
                                    exit;
                                case 3: // ALUMNO
                                    header("location: alumno/index.php");
                                    exit;
                                default:
                                    $error = "Acceso denegado. Personal administrativo usar otro portal.";
                                    session_destroy();
                            }

                        } else {
                            $error = "Contraseña incorrecta.";
                        }
                    } else {
                        $error = "Tu cuenta está desactivada. Acude a Servicios Escolares.";
                    }
                } else {
                    $error = "No existe una cuenta con este correo.";
                }
            } else {
                $error = "Error del sistema.";
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
  <title>Acceso Académico | SmartClass</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/stylesLogin.css">
</head>
<body class="bg-light">
  
  <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-75 shadow-lg rounded-4 overflow-hidden animate-slide">

      <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-primary bg-gradient text-white p-5">
        <div class="text-center">
          <img src="../assets/img/logo.png" alt="Logo SmartClass" class="img-fluid mb-4" style="max-width: 180px;">
          <h2 class="fw-bold">Portal Académico</h2>
          <p class="mt-2">Acceso exclusivo para Docentes y Estudiantes.</p>
        </div>
      </div>

      <div class="col-md-6 bg-white p-5 d-flex flex-column justify-content-center">
        <h3 class="fw-bold text-center text-primary">Bienvenido</h3>
        <h4 class="fw-bold text-center mb-4 text-secondary small">Ingresa tus credenciales</h4>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
          <div class="mb-3">
            <label for="email" class="form-label fw-bold small text-muted">Correo Institucional</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-primary"></i></span>
              <input type="email" id="email" name="email" class="form-control border-start-0 ps-0" placeholder="usuario@escuela.edu" required>
            </div>
          </div>

          <div class="mb-4">
            <label for="password" class="form-label fw-bold small text-muted">Contraseña</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-primary"></i></span>
              <input type="password" id="password" name="password" class="form-control border-start-0 ps-0" required>
            </div>
          </div>

          <div class="d-grid mb-4">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                Entrar <i class="bi bi-arrow-right-short"></i>
            </button>
          </div>

          <div class="text-center text-muted small">
            <p class="mb-2">¿Olvidaste tu contraseña? Contacta a tu coordinador.</p>
            <a href="../index.php" class="text-decoration-none text-secondary">Volver al inicio</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>