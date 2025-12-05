<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// Validar ID del grupo
if (!isset($_GET['id'])) {
    header("location: index.php");
    exit;
}
$grupo_id = $_GET['id'];

// 1. Obtener Info del Grupo
$sql_grupo = "SELECT codigo, grado, turno FROM grupos WHERE id = $grupo_id";
$res_grupo = mysqli_query($conn, $sql_grupo);
$grupo = mysqli_fetch_assoc($res_grupo);

// 2. Obtener Alumnos de este grupo
$sql_alumnos = "
    SELECT a.id, a.matricula, u.nombre, u.apellido_paterno, u.apellido_materno, u.email
    FROM alumnos a
    INNER JOIN users u ON a.user_id = u.id
    WHERE a.grupo_id = $grupo_id
    ORDER BY u.apellido_paterno ASC
";
$result = mysqli_query($conn, $sql_alumnos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Lista de Grupo | SmartClass</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php'; ?>

    <div id="page-content" class="w-100">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
            <div class="d-flex align-items-center">
                <button class="btn btn-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
                <h4 class="mb-0 fw-bold text-primary">Lista de Asistencia</h4>
            </div>
        </nav>

        <main class="container-fluid p-4">

            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-0">Grupo <?php echo $grupo['codigo']; ?></h2>
                        <p class="mb-0 opacity-75"><?php echo $grupo['grado']; ?>º Semestre - Turno <?php echo $grupo['turno']; ?></p>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-light text-primary fw-bold"><i class="bi bi-arrow-left"></i> Volver</a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Matrícula</th>
                                    <th class="py-3">Nombre del Alumno</th>
                                    <th class="py-3">Correo</th>
                                    <th class="py-3 text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">Este grupo no tiene alumnos inscritos.</td></tr>
                                <?php endif; ?>

                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="ps-4 font-monospace"><?php echo $row['matricula']; ?></td>
                                    <td class="fw-bold text-dark">
                                        <?php echo $row['apellido_paterno'] . ' ' . $row['apellido_materno'] . ' ' . $row['nombre']; ?>
                                    </td>
                                    <td class="text-muted small"><?php echo $row['email']; ?></td>
                                    <td class="text-end pe-4">
                                        <a href="quitar_alumno.php?alumno_id=<?php echo $row['id']; ?>&grupo_id=<?php echo $grupo_id; ?>" 
                                           class="btn btn-sm btn-outline-danger border-0 fw-bold"
                                           onclick="return confirm('¿Sacar a este alumno del grupo? Pasará a estar Sin Asignar.')">
                                            <i class="bi bi-person-dash-fill me-1"></i> Quitar
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>