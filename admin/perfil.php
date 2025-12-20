<?php
session_start();
require_once '../config/db.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = ""; 
$msg_type = ""; 

$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($query_user);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $telefono = mysqli_real_escape_string($conn, trim($_POST['telefono']));
    $foto_perfil = $user_data['foto']; 

    // Lógica de Foto Mejorada
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // RUTA ABSOLUTA: Intentamos asegurar que encuentre la carpeta
            $dir = "../assets/img/profiles/";
            
            // Si la carpeta no existe, intenta crearla
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $new_name = "admin_" . $user_id . "_" . time() . "." . $ext;
            $dest_path = $dir . $new_name;

            if (move_uploaded_file($file_tmp, $dest_path)) {
                // Borrar foto anterior si existe y no es la default
                if (!empty($user_data['foto']) && file_exists($dir . $user_data['foto'])) {
                    unlink($dir . $user_data['foto']);
                }
                $foto_perfil = $new_name;
            } else {
                $msg = "Error: No se pudo mover el archivo a la carpeta final. Revisa permisos.";
                $msg_type = "danger";
            }
        } else {
            $msg = "Solo se permiten formatos JPG o PNG.";
            $msg_type = "danger";
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $msg = "Error en el archivo subido (Código: " . $_FILES['foto']['error'] . ")";
        $msg_type = "danger";
    }

    // Actualizar Base de Datos
    if ($msg_type != "danger") {
        $sql = "UPDATE users SET telefono = '$telefono', foto = '$foto_perfil' WHERE id = $user_id";
        if (mysqli_query($conn, $sql)) {
            $msg = "¡Datos y fotografía actualizados!";
            $msg_type = "success";
            // Recargar datos frescos
            $query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
            $user_data = mysqli_fetch_assoc($query_user);
        } else {
            $msg = "Error SQL: " . mysqli_error($conn);
            $msg_type = "danger";
        }
    }
}

$display_photo = !empty($user_data['foto']) ? "../assets/img/profiles/" . $user_data['foto'] : "../assets/img/avatar.png";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Identificación | SmartClass</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --admin-blue: #1e3a8a; --admin-accent: #facc15; }
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .glass-card { background: #ffffff; border: none; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .sidebar-decor { background: linear-gradient(135deg, var(--admin-blue) 0%, #3b82f6 100%); color: white; }
        .form-control:read-only { background-color: #f1f5f9; cursor: not-allowed; border-color: #e2e8f0; color: #64748b; }
        .profile-upload { position: relative; display: inline-block; cursor: pointer; }
        .profile-upload:hover .upload-overlay { opacity: 1; }
        .upload-overlay { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); border-radius: 50%; display: flex; 
            align-items: center; justify-content: center; opacity: 0; transition: 0.3s;
        }
        .btn-primary { background-color: var(--admin-blue); border: none; border-radius: 12px; padding: 14px; font-weight: 600; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">

<div class="container" style="max-width: 1000px;">
    <div class="glass-card shadow-lg">
        <div class="row g-0">
            <div class="col-md-5 sidebar-decor p-5 text-center d-none d-md-flex flex-column justify-content-center">
                <h5 class="text-uppercase small mb-4 fw-bold" style="letter-spacing: 3px;">Credencial Digital</h5>
                <div class="mb-4">
                    <img src="<?php echo $display_photo; ?>" alt="Foto" id="preview-lateral" class="rounded-circle border border-4 border-white shadow" style="width: 160px; height: 160px; object-fit: cover;">
                </div>
                <h2 class="fw-bold mb-1"><?php echo $user_data['nombre']; ?></h2>
                <span class="badge bg-warning text-dark rounded-pill px-3 py-2 mb-4">ADMINISTRADOR</span>
                
                <div class="text-start bg-white bg-opacity-10 p-3 rounded-4 small">
                    <p class="mb-1"><strong>ID Usuario:</strong> #00<?php echo $user_id; ?></p>
                    <p class="mb-0"><strong>Acceso:</strong> Total / Sistema</p>
                </div>
                <a href="index.php" class="text-white mt-5 text-decoration-none small"><i class="bi bi-arrow-left me-2"></i>Regresar al tablero</a>
            </div>

            <div class="col-md-7 p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-dark mb-0">Mi Perfil</h3>
                    <img src="../assets/img/logo.png" height="35" alt="Logo">
                </div>

                <?php if($msg): ?>
                    <div class="alert alert-<?php echo $msg_type; ?> border-0 shadow-sm mb-4"><?php echo $msg; ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-4 d-md-none">
                        <img src="<?php echo $display_photo; ?>" class="rounded-circle border mb-3" width="80" height="80">
                    </div>

                    <div class="mb-4 text-center text-md-start">
                        <label class="form-label small fw-bold text-uppercase text-muted">Fotografía de Identificación</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="profile-upload" onclick="document.getElementById('foto').click();">
                                <img src="<?php echo $display_photo; ?>" id="preview-form" class="rounded-3 border" style="width: 60px; height: 60px; object-fit: cover;">
                                <div class="upload-overlay"><i class="bi bi-camera text-white"></i></div>
                            </div>
                            <input type="file" name="foto" id="foto" class="d-none" accept="image/*" onchange="previewImage(this)">
                            <div class="small text-muted">Haz clic en el icono para subir una nueva foto.<br>(JPG o PNG)</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombre</label>
                            <input type="text" class="form-control" value="<?php echo $user_data['nombre']; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Apellido Paterno</label>
                            <input type="text" class="form-control" value="<?php echo $user_data['apellido_paterno']; ?>" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Apellido Materno</label>
                            <input type="text" class="form-control" value="<?php echo $user_data['apellido_materno']; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Teléfono Celular *</label>
                            <input type="tel" name="telefono" class="form-control shadow-sm" style="background-color: #fff;" value="<?php echo $user_data['telefono']; ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Correo Institucional</label>
                        <input type="email" class="form-control" value="<?php echo $user_data['email']; ?>" readonly>
                    </div>

                    <div class="d-grid pt-2">
                        <button type="submit" class="btn btn-primary shadow-lg">
                            <i class="bi bi-check2-circle me-2"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Previsualización de imagen en tiempo real
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-form').src = e.target.result;
                document.getElementById('preview-lateral').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>