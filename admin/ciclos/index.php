<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD: Solo Admin (Con rebote inteligente)
if (!isset($_SESSION['loggedin'])) {
    header("location: ../../login.php");
    exit;
}

if ($_SESSION['role_id'] != 1) {
    // Si es cliente, lo mandamos a su dashboard correspondiente
    if ($_SESSION['role_id'] == 3) header("location: ../../client/alumno/index.php");
    elseif ($_SESSION['role_id'] == 2) header("location: ../../client/profesor/index.php");
    else header("location: ../../login.php");
    exit;
}

// 2. Consulta a la Base de Datos
$sql = "SELECT * FROM ciclos_escolares ORDER BY fecha_inicio DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Ciclos Escolares | SmartClass</title>
    
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
                <h4 class="mb-0 fw-bold text-primary">Periodos Académicos</h4>
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
                        if($_GET['msg'] == 'creado') echo "¡Nuevo ciclo escolar creado correctamente!";
                        if($_GET['msg'] == 'actualizado') echo "¡El ciclo escolar ha sido actualizado!";
                        if($_GET['msg'] == 'eliminado') echo "El ciclo escolar fue eliminado correctamente.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>No se pudo eliminar:</strong>
                    <?php 
                        if($_GET['error'] == 'es_activo') echo "Este ciclo está marcado como ACTIVO. Debes activar otro ciclo antes de borrarlo.";
                        if($_GET['error'] == 'tiene_datos') echo "Este ciclo tiene Grupos u Horarios vinculados. No puedes borrar historial académico.";
                        if($_GET['error'] == 'sql_error') echo "Ocurrió un error de integridad en la base de datos.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div class="input-group" style="max-width: 400px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Buscar ciclo (ej. 2025)..." id="buscador">
                </div>
                <a href="form.php" class="btn btn-primary px-4 rounded-pill shadow-sm">
                    <i class="bi bi-calendar-plus me-2"></i> Nuevo Ciclo
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Nombre del Ciclo</th> 
                                    <th class="py-3">Duración</th>              
                                    <th class="py-3 text-center">Estatus</th>   
                                    <th class="py-3 text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php 
                                if (mysqli_num_rows($result) == 0) {
                                    echo '<tr><td colspan="4" class="text-center py-4 text-muted">No hay ciclos escolares registrados.</td></tr>';
                                }

                                while ($row = mysqli_fetch_assoc($result)): 
                                    
                                    $es_activo = ($row['activo'] == 1);
                                    $clase_fila = $es_activo ? 'table-active bg-primary bg-opacity-10' : '';
                                    $clase_nombre = $es_activo ? 'text-primary' : 'text-secondary';
                                    
                                    $f_inicio = date("d/m/Y", strtotime($row['fecha_inicio']));
                                    $f_fin = date("d/m/Y", strtotime($row['fecha_fin']));
                                ?>
                                
                                <tr class="<?php echo $clase_fila; ?>">
                                    <td class="ps-4">
                                        <span class="fw-bold <?php echo $clase_nombre; ?> fs-5"><?php echo htmlspecialchars($row['nombre']); ?></span>
                                        <div class="small text-muted">ID: <?php echo $row['id']; ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 <?php echo $es_activo ? '' : 'opacity-75'; ?>">
                                            <span class="badge bg-light text-dark border"><i class="bi bi-calendar-event me-1"></i> <?php echo $f_inicio; ?></span>
                                            <i class="bi bi-arrow-right text-muted small"></i>
                                            <span class="badge bg-light text-dark border"><i class="bi bi-flag me-1"></i> <?php echo $f_fin; ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($es_activo): ?>
                                            <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm">
                                                <i class="bi bi-check-circle-fill me-1"></i> EN CURSO
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary rounded-pill px-3">
                                                Finalizado / Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger border-0" 
                                           onclick="return confirm('¿Estás seguro de eliminar este ciclo?')"
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