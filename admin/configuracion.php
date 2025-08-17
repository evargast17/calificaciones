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
    <title>Configuración del Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
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
                    <h1 class="h2 mb-0">⚙️ Configuración del Sistema</h1>
                    <p class="mb-0">Configurar períodos académicos y parámetros del sistema</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-light me-2">← Volver al Panel</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📅 Períodos Académicos</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Períodos configurados:</strong><br>
                            • I Bimestre 2025 (Mar - May)<br>
                            • II Bimestre 2025 (May - Jul)<br>
                            • III Bimestre 2025 (Ago - Oct)<br>
                            • IV Bimestre 2025 (Oct - Dic)<br>
                        </div>
                        <p class="text-muted">
                            La gestión de períodos se realizará en futuras versiones.
                            Los períodos actuales están configurados para el año 2025.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">🏫 Información del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Versión:</strong></td>
                                <td><?php echo SISTEMA_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Base de Datos:</strong></td>
                                <td>MySQL</td>
                            </tr>
                            <tr>
                                <td><strong>PHP:</strong></td>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Timezone:</strong></td>
                                <td><?php echo date_default_timezone_get(); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Última actualización:</strong></td>
                                <td><?php echo date('Y-m-d H:i:s'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-gear" style="font-size: 4rem; color: #6c757d;"></i>
                        <h3 class="mt-3 mb-3">Configuración Avanzada</h3>
                        <p class="text-muted mb-4">
                            Las opciones de configuración avanzada estarán disponibles en futuras versiones.
                            Incluirá gestión de períodos, backup automático, y configuración de notificaciones.
                        </p>
                        <div class="alert alert-secondary">
                            <strong>Próximas funcionalidades:</strong><br>
                            • Gestión de períodos académicos<br>
                            • Configuración de backup automático<br>
                            • Configuración de notificaciones<br>
                            • Personalización de escalas de calificación<br>
                            • Configuración de reportes automáticos<br>
                        </div>
                        <a href="index.php" class="btn btn-secondary">Volver al Panel de Administración</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>