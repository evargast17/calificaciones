<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();

// Si está logueado, redirigir a la matriz
if ($auth->isLoggedIn()) {
    header('Location: matriz_calificaciones.php');
    exit;
}

// Si no está logueado, redirigir al login
header('Location: login.php');
exit;
?>