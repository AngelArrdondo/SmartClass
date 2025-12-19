<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['role_id'] != 1) {
    header("location: ../../login.php");
    exit;
}

// CONSULTA SQL
// Ordenamos por nombre alfabéticamente
$sql = "SELECT * FROM materias ORDER BY nombre ASC";
$result = mysqli_query($conn, $sql);
$total_registros = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Materias | SmartClass</title>
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
                <h4 class="mb-0 fw-bold text-primary">Catálogo de Materias</h4>
                <div class="d-flex align-items-center">
                    <span class="d-none d-md-block small text-muted me-2"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                    <img src="../../assets/img/avatar.png" alt="Admin" class="rounded-circle border" width="35" height="35">
                </div>
            </div>
        </nav>

        <main class="container-fluid p-4">
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php 
                        if($_GET['msg'] == 'creado') echo "¡Materia registrada exitosamente!";
                        if($_GET['msg'] == 'actualizado') echo "¡Materia actualizada!";
                        if($_GET['msg'] == 'eliminado') echo "Materia eliminada del catálogo.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> No se puede eliminar: Esta materia ya está en uso (horarios o asistencias).
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div class="input-group" style="max-width: 400px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="buscador" class="form-control border-start-0 ps-0" placeholder="Buscar por materia o código...">
                </div>
                <a href="form.php" class="btn btn-primary px-4 rounded-pill shadow-sm">
                    <i class="bi bi-journal-plus me-2"></i> Nueva Materia
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Código</th>
                                    <th class="py-3">Nombre de la Asignatura</th>
                                    <th class="py-3">Créditos</th>
                                    <th class="py-3" style="width: 30%;">Descripción</th>
                                    <th class="py-3 text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php 
                                if ($total_registros == 0) {
                                    echo '<tr><td colspan="5" class="text-center py-5 text-muted">No hay materias registradas.</td></tr>';
                                }

                                while ($row = mysqli_fetch_assoc($result)): 
                                    // Cálculos visuales para la barra de progreso (Max 10 créditos como ejemplo)
                                    $creditos = $row['creditos'];
                                    $porcentaje = ($creditos / 10) * 100;
                                    if($porcentaje > 100) $porcentaje = 100;
                                    
                                    // Color de la barra según créditos
                                    $barra_color = 'bg-info';
                                    if($creditos >= 8) $barra_color = 'bg-success';
                                    if($creditos <= 4) $barra_color = 'bg-warning';
                                    
                                    // Descripción (si existe la columna, sino vacía)
                                    $desc = isset($row['descripcion']) ? $row['descripcion'] : 'Sin descripción disponible.';
                                ?>

                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary font-monospace">
                                            <?php echo $row['codigo']; ?>
                                        </span>
                                    </td>
                                    
                                    <td class="fw-bold text-dark"><?php echo $row['nombre']; ?></td>
                                    
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="fw-bold me-2"><?php echo $creditos; ?></span>
                                            <div class="progress" style="height: 5px; width: 50px;">
                                                <div class="progress-bar <?php echo $barra_color; ?>" role="progressbar" style="width: <?php echo $porcentaje; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="text-muted small text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($desc); ?>">
                                        <?php echo htmlspecialchars($desc); ?>
                                    </td>
                                    
                                    <td class="text-end pe-4">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger border-0"
                                           onclick="return confirm('¿Seguro que deseas eliminar esta materia?')"
                                           title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>

                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-between align-items-center">
                        <small class="text-muted">Total: <?php echo $total_registros; ?> materias registradas</small>
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
    
    // Buscador JS
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