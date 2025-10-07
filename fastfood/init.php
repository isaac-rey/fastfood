<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de base de datos
$host = 'localhost';
$user = 'root'; // Cambiar según tu configuración
$pass = '';     // Cambiar según tu configuración
$db   = 'fastfood'; // Nombre de tu base de datos

// Conectar a la base de datos
$mysqli = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Configurar charset
$mysqli->set_charset("utf8mb4");

// Función para escapar HTML (prevenir XSS)
function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Función para verificar si el usuario está logueado
function is_logged_in() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

// Función para verificar rol
function has_role($rol_id) {
    return isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == $rol_id;
}

// Función para redirigir según rol
function redirect_by_role() {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
    
    $rol = $_SESSION['rol_id'] ?? 0;
    
    switch($rol) {
        case 1: // Admin
            header('Location: /admin/panel.php');
            break;
        case 2: // Cajero
            header('Location: /cajero/index.php');
            break;
        case 3: // Cocina
            header('Location: /cocina/index.php');
            break;
        case 4: // Cliente
            header('Location: /cliente/index.php');
            break;
        default:
            header('Location: /login.php');
            break;
    }
    exit;
}

// Compatibilidad de sesión (admin_id = user_id)
if (isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = $_SESSION['user_id'];
}

if (isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['admin_id'];
}