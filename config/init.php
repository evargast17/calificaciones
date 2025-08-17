<?php
// Configuración inicial del sistema

// Configurar timezone
date_default_timezone_set('America/Lima');

// Configurar nivel de errores para desarrollo
// En producción usar: error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS

// Iniciar sesión si no está activa
function initSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Función para sanitizar entrada de datos
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Constantes del sistema
define('SISTEMA_NOMBRE', 'Sistema de Calificaciones por Competencias');
define('SISTEMA_VERSION', '1.0.0');
define('ROLES_PERMITIDOS', ['Administrador', 'Coordinadora', 'Tutor del Aula', 'Docente Área', 'Docente Taller']);
define('CALIFICACIONES_VALIDAS', ['AD', 'A', 'B', 'C']);

// Inicializar sesión
initSession();
?>