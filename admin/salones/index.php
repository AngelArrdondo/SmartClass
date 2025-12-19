<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// CONSULTA
$sql = "SELECT * FROM salones ORDER BY codigo ASC";
$result = mysqli_query($conn, $sql);
$total_registros = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Salones | SmartClass</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/menu.php'; ?>

    <div id="page-content" class="w-100">
        
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
            <div class="d-flex align-items-center w-100 justify-content-between">
                <button class="btn btn-primary d-md-none me-2" id="btnToggleSidebar"><i class="bi bi-list"></i></button>
                <h4 class="mb-0 fw-bold text-primary">Gestión de Espacios y Aulas</h4>
                <div class="d-flex align-items-center">
                    <span class="d-none d-md-block small text-muted me-2"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                    <img src="../../assets/img/avatar.png" alt="Admin" class="rounded-circle border" width="35" height="35">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        if($_GET['msg'] == 'creado') echo "¡Salón registrado correctamente!";
                        if($_GET['msg'] == 'actualizado') echo "¡Datos del salón actualizados!";
                        if($_GET['msg'] == 'eliminado') echo "Salón eliminado.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> No se puede eliminar: El salón tiene horarios asignados.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div class="input-group" style="max-width: 400px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="buscador" class="form-control border-start-0 ps-0" placeholder="Buscar por código o edificio...">
                </div>
                <a href="form.php" class="btn btn-primary px-4 rounded-pill shadow-sm">
                    <i class="bi bi-house-add-fill me-2"></i> Nuevo Salón
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Código</th>
                                    <th class="py-3">Ubicación / Nombre</th>
                                    <th class="py-3 text-center">Capacidad</th>
                                    <th class="py-3">Recursos Disponibles</th>
                                    <th class="py-3 text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($total_registros == 0) {
                                    echo '<tr><td colspan="5" class="text-center py-5 text-muted">No hay salones registrados.</td></tr>';
                                }

                                while ($row = mysqli_fetch_assoc($result)): 
                                    // Procesar recursos (separar por comas)
                                    $recursos_array = !empty($row['recursos']) ? explode(',', $row['recursos']) : [];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-dark bg-opacity-10 text-dark border border-secondary font-monospace px-3 py-2">
                                            <?php echo htmlspecialchars($row['codigo']); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['nombre']); ?></div>
                                        <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($row['ubicacion']); ?></small>
                                    </td>
                                    
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-light border rounded-pill px-3 py-1">
                                            <i class="bi bi-people-fill text-muted me-2"></i> <?php echo $row['capacidad']; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php if(empty($recursos_array)): ?>
                                            <span class="text-muted small">--</span>
                                        <?php else: ?>
                                            <?php foreach($recursos_array as $rec): ?>
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary me-1 mb-1">
                                                    <?php echo trim($rec); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-end pe-4">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger border-0"
                                           onclick="return confirm('¿Eliminar este salón?')"
                                           title="Eliminar">
                                            <i class="bi bi-trash"></i>
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
<script>
    const toggleBtn = document.getElementById('btnToggleSidebar');
    if(toggleBtn){
        toggleBtn.addEventListener('click', () => {
             const sidebar = document.getElementById('sidebar'); 
             if(sidebar) sidebar.classList.toggle('d-none');
        });
    }
    // Buscador
    document.getElementById('buscador').addEventListener('keyup', function() {
        let filtro = this.value.toLowerCase();
        let filas = document.querySelectorAll('tbody tr');
        filas.forEach(fila => {
            let texto = fila.innerText.toLowerCase();
            fila.style.display = texto.includes(filtro) ? '' : 'none';
        });
    });
</script>
</body>
</html>