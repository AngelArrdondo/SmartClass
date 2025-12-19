<?php
// Obtenemos el nombre del script actual (ej: /SmartClass/admin/ciclos/index.php)
$uri = $_SERVER['PHP_SELF'];
?>

<nav id="sidebar">
    <div class="sidebar-header text-center">
        <i class="bi bi-mortarboard-fill fs-2 text-warning"></i>
        <h6 class="mb-0 fw-bold mt-2">SmartClass</h6>
    </div>

    <ul class="nav flex-column mt-3">
        
        <li class="nav-item">
            <a class="nav-link <?php echo (strpos($uri, '/admin/index.php') !== false) ? 'active' : ''; ?>" href="/SmartClass/admin/index.php">
                <i class="bi bi-speedometer2 me-3"></i> Dashboard
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo (strpos($uri, '/ciclos/') !== false) ? 'active' : ''; ?>" href="/SmartClass/admin/ciclos/index.php">
                <i class="bi bi-calendar-range me-3"></i> Ciclos
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo (strpos($uri, '/grupos/') !== false) ? 'active' : ''; ?>" href="/SmartClass/admin/grupos/index.php">
                <i class="bi bi-people me-3"></i> Grupos
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo (strpos($uri, '/materias/') !== false) ? 'active' : ''; ?>" href="/SmartClass/admin/materias/index.php">
                <i class="bi bi-book me-3"></i> Materias
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo (strpos($uri, '/salones/') !== false) ? 'active' : ''; ?>" href="/SmartClass/admin/salones/index.php">
                <i class="bi bi-building me-3"></i> Salones
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo (strpos($uri, '/horarios/') !== false) ? 'active' : ''; ?>" href="/SmartClass/admin/horarios/index.php">
                <i class="bi bi-calendar-week me-3"></i> Horarios
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo (strpos($uri, '/profesores/') !== false) ? 'active' : ''; ?>" href="/SmartClass/admin/profesores/index.php">
                <i class="bi bi-person-badge me-3"></i> Profesores
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo (strpos($uri, '/alumnos/') !== false) ? 'active' : ''; ?>" href="/SmartClass/admin/alumnos/index.php">
                <i class="bi bi-person-video3 me-3"></i> Alumnos
            </a>
        </li>
    </ul>

    <div class="mt-auto mb-3 px-2">
        <a class="nav-link text-white bg-white bg-opacity-10" href="/SmartClass/logout.php">
            <i class="bi bi-box-arrow-left me-3"></i> Salir
        </a>
    </div>
</nav>