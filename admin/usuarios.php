<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (!$auth->hasPermission(['Administrador'])) {
    header('Location: ../matriz_calificaciones.php');
    exit;
}

// Solo para mostrar la estructura - implementación básica
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 2rem 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-0">👥 Gestión de Usuarios</h1>
                    <p class="mb-0">Administrar docentes y permisos del sistema</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-light me-2">← Volver al Panel</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-gear" style="font-size: 4rem; color: #6c757d;"></i>
                        <h3 class="mt-3 mb-3">Módulo en Desarrollo</h3>
                        <p class="text-muted mb-4">
                            Esta funcionalidad estará disponible en la próxima versión del sistema.
                            Por ahora puedes gestionar usuarios directamente en la base de datos.
                        </p>
                        <div class="alert alert-info">
                            <strong>Usuarios actuales del sistema:</strong><br>
                            • admin@colegio.edu.pe (Administrador)<br>
                            • coordinadora@colegio.edu.pe (Coordinadora)<br>
                            • kelly.correa@colegio.edu.pe (Tutor del Aula)<br>
                            <small>Contraseña: 123456</small>
                        </div>
                        <a href="index.php" class="btn btn-primary">Volver al Panel de Administración</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>