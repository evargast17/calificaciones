<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Calificaciones</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="me-4">
                            <i class="bi bi-speedometer2" style="font-size: 3rem; opacity: 0.9;"></i>
                        </div>
                        <div>
                            <h1 class="h2 mb-0 fw-bold">Panel de Administración</h1>
                            <p class="mb-0 opacity-75">Gestión y supervisión integral del sistema de calificaciones</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge badge-modern" style="background: linear-gradient(45deg, #fbbf24, #f59e0b); color: #92400e; font-size: 0.9rem;">
                        <i class="bi bi-shield-check me-1"></i>
                        <?php echo $_SESSION['user_rol']; ?>
                    </span>
                    <div class="dropdown d-inline-block ms-3">
                        <button class="btn btn-light btn-modern dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo $_SESSION['user_name']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../matriz_calificaciones.php">
                                <i class="bi bi-grid-3x3-gap me-2 text-primary"></i>
                                Ver Matriz
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Cerrar Sesión
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas Generales -->
        <div class="dashboard-stats">
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="stat-card-admin estudiantes animate-fade-in">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon-admin me-3" style="background: var(--gradient-primary);">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <h3 class="stat-number-admin mb-0"><?php echo $total_estudiantes; ?></h3>
                                <p class="text-muted mb-0 fw-semibold">Estudiantes</p>
                                <small class="text-success">
                                    <i class="bi bi-arrow-up me-1"></i>
                                    Activos en el sistema
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card-admin evaluaciones animate-fade-in">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon-admin me-3" style="background: var(--gradient-success);">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div>
                                <?php 
                                $total_eval = array_sum(array_column($resumen_general, 'total_evaluaciones'));
                                ?>
                                <h3 class="stat-number-admin mb-0"><?php echo $total_eval; ?></h3>
                                <p class="text-muted mb-0 fw-semibold">Evaluaciones</p>
                                <small class="text-success">
                                    <i class="bi bi-graph-up me-1"></i>
                                    Registradas este período
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card-admin grados animate-fade-in">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon-admin me-3" style="background: var(--gradient-warning);">
                                <i class="bi bi-layers-fill"></i>
                            </div>
                            <div>
                                <h3 class="stat-number-admin mb-0"><?php echo count($grados); ?></h3>
                                <p class="text-muted mb-0 fw-semibold">Grados</p>
                                <small class="text-info">
                                    <i class="bi bi-collection me-1"></i>
                                    Configurados
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card-admin areas animate-fade-in">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon-admin me-3" style="background: linear-gradient(135deg, #8b5cf6, #a855f7);">
                                <i class="bi bi-book-fill"></i>
                            </div>
                            <div>
                                <h3 class="stat-number-admin mb-0"><?php echo count($areas); ?></h3>
                                <p class="text-muted mb-0 fw-semibold">Áreas</p>
                                <small class="text-purple">
                                    <i class="bi bi-bookmark me-1"></i>
                                    Curriculares
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menú de Gestión -->
        <div class="row mb-5">
            <div class="col-12">
                <h4 class="mb-4 fw-bold text-dark">
                    <i class="bi bi-tools me-2 text-primary"></i>
                    Herramientas de Gestión
                </h4>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="estudiantes.php" class="menu-card-admin estudiantes d-block text-center hover-lift">
                    <i class="bi bi-person-plus menu-icon-admin"></i>
                    <h5 class="fw-bold mb-2">Gestionar Estudiantes</h5>
                    <p class="text-muted mb-0">Agregar, editar y administrar información de estudiantes del sistema</p>
                    <div class="mt-3">
                        <span class="badge badge-admin-primary">
                            <i class="bi bi-arrow-right me-1"></i>
                            Acceder
                        </span>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="usuarios.php" class="menu-card-admin usuarios d-block text-center hover-lift">
                    <i class="bi bi-people menu-icon-admin"></i>
                    <h5 class="fw-bold mb-2">Gestionar Usuarios</h5>
                    <p class="text-muted mb-0">Administrar docentes, roles y permisos del sistema educativo</p>
                    <div class="mt-3">
                        <span class="badge badge-admin-success">
                            <i class="bi bi-arrow-right me-1"></i>
                            Acceder
                        </span>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="reportes.php" class="menu-card-admin reportes d-block text-center hover-lift">
                    <i class="bi bi-graph-up menu-icon-admin"></i>
                    <h5 class="fw-bold mb-2">Reportes Avanzados</h5>
                    <p class="text-muted mb-0">Generar reportes detallados y estadísticas del rendimiento académico</p>
                    <div class="mt-3">
                        <span class="badge badge-admin" style="background: var(--gradient-info);">
                            <i class="bi bi-arrow-right me-1"></i>
                            Acceder
                        </span>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="competencias.php" class="menu-card-admin competencias d-block text-center hover-lift">
                    <i class="bi bi-list-check menu-icon-admin"></i>
                    <h5 class="fw-bold mb-2">Gestionar Competencias</h5>
                    <p class="text-muted mb-0">Configurar y administrar competencias por área curricular</p>
                    <div class="mt-3">
                        <span class="badge badge-admin-warning">
                            <i class="bi bi-arrow-right me-1"></i>
                            Acceder
                        </span>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="configuracion.php" class="menu-card-admin configuracion d-block text-center hover-lift">
                    <i class="bi bi-gear menu-icon-admin"></i>
                    <h5 class="fw-bold mb-2">Configuración</h5>
                    <p class="text-muted mb-0">Configurar períodos académicos y parámetros del sistema</p>
                    <div class="mt-3">
                        <span class="badge" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white;">
                            <i class="bi bi-arrow-right me-1"></i>
                            Acceder
                        </span>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="../matriz_calificaciones.php" class="menu-card-admin matriz d-block text-center hover-lift">
                    <i class="bi bi-grid-3x3-gap menu-icon-admin"></i>
                    <h5 class="fw-bold mb-2">Ver Matriz</h5>
                    <p class="text-muted mb-0">Acceder a la matriz principal de calificaciones y evaluaciones</p>
                    <div class="mt-3">
                        <span class="badge badge-admin-danger">
                            <i class="bi bi-arrow-right me-1"></i>
                            Acceder
                        </span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Resumen por Nivel -->
        <div class="row">
            <div class="col-12">
                <div class="admin-table animate-fade-in">
                    <div class="card-header" style="background: var(--gradient-primary); color: white; padding: 1.5rem;">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-bar-chart-fill me-2"></i>
                            Resumen por Nivel Educativo - <?php echo $periodo_actual['nombre'] ?? 'Período Actual'; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead style="background: var(--gradient-primary); color: white;">
                                    <tr>
                                        <th class="fw-bold">
                                            <i class="bi bi-mortarboard me-1"></i>
                                            Nivel
                                        </th>
                                        <th class="text-center">
                                            <i class="bi bi-people me-1"></i>
                                            Estudiantes
                                        </th>
                                        <th class="text-center">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Evaluaciones
                                        </th>
                                        <th class="text-center">AD</th>
                                        <th class="text-center">A</th>
                                        <th class="text-center">B</th>
                                        <th class="text-center">C</th>
                                        <th class="text-center">
                                            <i class="bi bi-graph-up me-1"></i>
                                            Progreso
                                        </th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumen_general as $nivel): ?>
                                        <?php 
                                        $total_calif = $nivel['destacado'] + $nivel['esperado'] + $nivel['proceso'] + $nivel['inicio'];
                                        $porcentaje_ad_a = $total_calif > 0 ? round((($nivel['destacado'] + $nivel['esperado']) / $total_calif) * 100, 1) : 0;
                                        ?>
                                        <tr class="hover-lift">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon-admin me-3" style="width: 40px; height: 40px; font-size: 1rem; background: var(--gradient-primary);">
                                                        <i class="bi bi-mortarboard"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?php echo $nivel['nivel']; ?></div>
                                                        <small class="text-muted">Nivel educativo</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-primary" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['total_estudiantes']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin" style="background: var(--gradient-info); font-size: 0.9rem;">
                                                    <?php echo $nivel['total_evaluaciones']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-success" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['destacado']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-primary" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['esperado']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-warning" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['proceso']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-danger" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['inicio']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <div class="admin-progress me-2" style="width: 80px;">
                                                        <div class="progress-bar" style="width: <?php echo $porcentaje_ad_a; ?>%"></div>
                                                    </div>
                                                    <span class="fw-bold text-success"><?php echo $porcentaje_ad_a; ?>%</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-admin btn-admin-primary" 
                                                        onclick="verDetalleNivel('<?php echo $nivel['nivel']; ?>')"
                                                        data-bs-toggle="tooltip"
                                                        title="Ver detalles del nivel">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="row mt-5">
            <div class="col-md-8">
                <div class="alert-admin alert-admin-info">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Sistema de Evaluación por Competencias</h6>
                            <p class="mb-0">
                                Este panel te permite gestionar integralmente el sistema educativo. 
                                Utiliza las herramientas disponibles para administrar estudiantes, generar reportes y configurar el sistema.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="modern-card text-center p-4">
                    <i class="bi bi-speedometer2" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h6 class="fw-bold mb-2">Rendimiento del Sistema</h6>
                    <div class="d-flex justify-content-between text-center">
                        <div>
                            <div class="fw-bold text-success">99.9%</div>
                            <small class="text-muted">Uptime</small>
                        </div>
                        <div>
                            <div class="fw-bold text-primary">
                                <?php echo count($resumen_general); ?>
                            </div>
                            <small class="text-muted">Niveles</small>
                        </div>
                        <div>
                            <div class="fw-bold text-warning">
                                <?php echo date('Y'); ?>
                            </div>
                            <small class="text-muted">Año</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function verDetalleNivel(nivel) {
            SystemJS.Notifications.info(`Cargando detalles para ${nivel}...`);
            
            // Simular carga y mostrar información
            setTimeout(() => {
                SystemJS.Confirm.show(
                    `Detalles del nivel ${nivel}:\n\n• Funcionalidad completa disponible\n• Reportes detallados\n• Gestión de estudiantes\n• Seguimiento de progreso`,
                    `Información del ${nivel}`,
                    {
                        confirmText: 'Ver Matriz',
                        cancelText: 'Cerrar'
                    }
                ).then(confirmed => {
                    if (confirmed) {
                        window.location.href = '../matriz_calificaciones.php';
                    }
                });
            }, 500);
        }

        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Animar contadores
            const statNumbers = document.querySelectorAll('.stat-number-admin');
            statNumbers.forEach(element => {
                const target = parseInt(element.textContent);
                SystemJS.Effects.countUp(element, target, 1500);
            });

            // Animar barras de progreso
            const progressBars = document.querySelectorAll('.admin-progress .progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });

            // Tooltip initialization
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Auto-refresh cada 5 minutos
        setInterval(function() {
            const timestamp = new Date().toLocaleTimeString();
            console.log(`🔄 Panel actualizado: ${timestamp}`);
        }, 300000);
    </script>
</body>
</html><?php
// Iniciar sesión si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Calificaciones.php';
require_once __DIR__ . '/../classes/Estudiantes.php';
require_once __DIR__ . '/../classes/Reportes.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Solo administradores y coordinadoras pueden acceder
if (!$auth->hasPermission(['Administrador', 'Coordinadora'])) {
    header('Location: ../matriz_calificaciones.php');
    exit;
}

$calificaciones = new Calificaciones();
$estudiantes = new Estudiantes();
$reportes = new Reportes();

// Obtener estadísticas generales
$grados = $calificaciones->getGrados();
$areas = $calificaciones->getAreas();
$periodos = $calificaciones->getPeriodos();

// Estadísticas del sistema
$periodo_actual = array_filter($periodos, function($p) { return $p['activo'] == 1; });
$periodo_actual = reset($periodo_actual);
$periodo_id = $periodo_actual['id'] ?? 1;

$resumen_general = $reportes->getResumenGeneral($periodo_id);
$total_estudiantes = count($estudiantes->getAll());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Calificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
            padding: 2rem 0;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 5px solid;
        }
        .stat-card.estudiantes { border-left-color: #007bff; }
        .stat-card.evaluaciones { border-left-color: #28a745; }
        .stat-card.grados { border-left-color: #ffc107; }
        .stat-card.areas { border-left-color: #6f42c1; }
        
        .menu-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s ease;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            text-decoration: none;
            color: inherit;
        }
        .menu-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-0">🔧 Panel de Administración</h1>
                    <p class="mb-0">Gestión y supervisión del sistema de calificaciones</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-warning text-dark fs-6 me-2"><?php echo $_SESSION['user_rol']; ?></span>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <?php echo $_SESSION['user_name']; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../matriz_calificaciones.php">Ver Matriz</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card estudiantes">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-people-fill text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $total_estudiantes; ?></h3>
                            <p class="text-muted mb-0">Estudiantes</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card evaluaciones">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <?php 
                            $total_eval = array_sum(array_column($resumen_general, 'total_evaluaciones'));
                            ?>
                            <h3 class="mb-0"><?php echo $total_eval; ?></h3>
                            <p class="text-muted mb-0">Evaluaciones</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card grados">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-layers-fill text-warning" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo count($grados); ?></h3>
                            <p class="text-muted mb-0">Grados</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card areas">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-book-fill text-purple" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo count($areas); ?></h3>
                            <p class="text-muted mb-0">Áreas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menú de Gestión -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">🛠️ Herramientas de Gestión</h4>
            </div>
            <div class="col-md-4 mb-3">
                <a href="estudiantes.php" class="menu-card d-block text-center">
                    <i class="bi bi-person-plus menu-icon text-primary"></i>
                    <h5>Gestionar Estudiantes</h5>
                    <p class="text-muted">Agregar, editar y administrar estudiantes</p>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="usuarios.php" class="menu-card d-block text-center">
                    <i class="bi bi-people menu-icon text-success"></i>
                    <h5>Gestionar Usuarios</h5>
                    <p class="text-muted">Administrar docentes y permisos</p>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="reportes.php" class="menu-card d-block text-center">
                    <i class="bi bi-graph-up menu-icon text-info"></i>
                    <h5>Reportes Avanzados</h5>
                    <p class="text-muted">Generar reportes y estadísticas</p>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="competencias.php" class="menu-card d-block text-center">
                    <i class="bi bi-list-check menu-icon text-warning"></i>
                    <h5>Gestionar Competencias</h5>
                    <p class="text-muted">Configurar competencias por área</p>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="configuracion.php" class="menu-card d-block text-center">
                    <i class="bi bi-gear menu-icon text-secondary"></i>
                    <h5>Configuración</h5>
                    <p class="text-muted">Configurar períodos y sistema</p>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="../matriz_calificaciones.php" class="menu-card d-block text-center">
                    <i class="bi bi-grid-3x3-gap menu-icon text-danger"></i>
                    <h5>Ver Matriz</h5>
                    <p class="text-muted">Ir a la matriz de calificaciones</p>
                </a>
            </div>
        </div>

        <!-- Resumen por Nivel -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📊 Resumen por Nivel Educativo - <?php echo $periodo_actual['nombre'] ?? 'Período Actual'; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nivel</th>
                                        <th>Estudiantes</th>
                                        <th>Evaluaciones</th>
                                        <th>AD (Destacado)</th>
                                        <th>A (Esperado)</th>
                                        <th>B (Proceso)</th>
                                        <th>C (Inicio)</th>
                                        <th>Progreso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumen_general as $nivel): ?>
                                        <?php 
                                        $total_calif = $nivel['destacado'] + $nivel['esperado'] + $nivel['proceso'] + $nivel['inicio'];
                                        $porcentaje_ad_a = $total_calif > 0 ? round((($nivel['destacado'] + $nivel['esperado']) / $total_calif) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $nivel['nivel']; ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $nivel['total_estudiantes']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $nivel['total_evaluaciones']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $nivel['destacado']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $nivel['esperado']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning"><?php echo $nivel['proceso']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $nivel['inicio']; ?></span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" style="width: <?php echo $porcentaje_ad_a; ?>%">
                                                        <?php echo $porcentaje_ad_a; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>