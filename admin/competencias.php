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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Competencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            padding: 2rem 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-0">📋 Gestión de Competencias</h1>
                    <p class="mb-0">Configurar competencias por área curricular</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-dark me-2">← Volver al Panel</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-list-check" style="font-size: 4rem; color: #ffc107;"></i>
                        <h3 class="mt-3 mb-3">Módulo en Desarrollo</h3>
                        <p class="text-muted mb-4">
                            La gestión de competencias estará disponible en la próxima versión.
                            Actualmente el sistema incluye competencias predefinidas por área.
                        </p>
                        <div class="alert alert-warning">
                            <strong>Competencias actuales del sistema:</strong><br>
                            • Personal Social: 6 competencias (C1-C6)<br>
                            • Comunicación: En configuración<br>
                            • Matemática: En configuración<br>
                            • Ciencia y Tecnología: En configuración<br>
                        </div>
                        <a href="index.php" class="btn btn-warning">Volver al Panel de Administración</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>