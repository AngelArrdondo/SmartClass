<?php
$hostname = "localhost";
$username = "root";
$password = "";
$database = "SmartClass";
$port = 3307; // Tu puerto

$conn = mysqli_connect($hostname, $username, $password, $database, $port);

// Verificar conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

date_default_timezone_set('America/Mexico_City');
?>