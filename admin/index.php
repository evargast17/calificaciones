<?php
require_once __DIR__ . '/../config/init.php';
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
$periodo_actual = null;
if (!empty($periodos)) {
    $periodo_actual = array_filter($periodos, function($p) { return $p['activo'] == 1; });
    if (empty($periodo_actual)) {
        $periodo_actual = $periodos[0]; // Tomar el primer período si no hay activo
    } else {
        $periodo_actual = reset($periodo_actual);
    }
}

$periodo_id = $periodo_actual['id'] ?? 1;

$resumen_general = $reportes->getResumenGeneral($periodo_id);
$total_estudiantes = count($estudiantes->getAll());

// Verificar que las variables de sesión existan
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? 'Sin rol';
?>
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
                        <?php echo htmlspecialchars($user_rol); ?>
                    </span>
                    <div class="dropdown d-inline-block ms-3">
                        <button class="btn btn-light btn-modern dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo htmlspecialchars($user_name); ?>
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
                                $total_eval = 0;
                                if (!empty($resumen_general)) {
                                    $total_eval = array_sum(array_column($resumen_general, 'total_evaluaciones'));
                                }
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
                            Resumen por Nivel Educativo - <?php echo ($periodo_actual['nombre'] ?? 'Período Actual') . ' ' . ($periodo_actual['año'] ?? date('Y')); ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($resumen_general)): ?>
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
                                        $total_calif = ($nivel['destacado'] ?? 0) + ($nivel['esperado'] ?? 0) + ($nivel['proceso'] ?? 0) + ($nivel['inicio'] ?? 0);
                                        $porcentaje_ad_a = $total_calif > 0 ? round(((($nivel['destacado'] ?? 0) + ($nivel['esperado'] ?? 0)) / $total_calif) * 100, 1) : 0;
                                        ?>
                                        <tr class="hover-lift">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon-admin me-3" style="width: 40px; height: 40px; font-size: 1rem; background: var(--gradient-primary);">
                                                        <i class="bi bi-mortarboard"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($nivel['nivel'] ?? 'N/A'); ?></div>
                                                        <small class="text-muted">Nivel educativo</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-primary" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['total_estudiantes'] ?? 0; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin" style="background: var(--gradient-info); font-size: 0.9rem;">
                                                    <?php echo $nivel['total_evaluaciones'] ?? 0; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-success" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['destacado'] ?? 0; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-primary" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['esperado'] ?? 0; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-warning" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['proceso'] ?? 0; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-admin-danger" style="font-size: 0.9rem;">
                                                    <?php echo $nivel['inicio'] ?? 0; ?>
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
                                                        onclick="verDetalleNivel('<?php echo htmlspecialchars($nivel['nivel'] ?? ''); ?>')"
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
                        <?php else: ?>
                        <div class="text-center p-5">
                            <i class="bi bi-info-circle" style="font-size: 3rem; color: #6c757d;"></i>
                            <h5 class="mt-3 text-muted">No hay datos disponibles</h5>
                            <p class="text-muted">No se encontraron estadísticas para mostrar. Verifique que existan períodos y datos configurados.</p>
                        </div>
                        <?php endif; ?>
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
            if (typeof SystemJS !== 'undefined' && SystemJS.Notifications) {
                SystemJS.Notifications.info(`Cargando detalles para ${nivel}...`);
                
                setTimeout(() => {
                    if (SystemJS.Confirm) {
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
                    } else {
                        if (confirm(`Ver matriz de calificaciones para ${nivel}?`)) {
                            window.location.href = '../matriz_calificaciones.php';
                        }
                    }
                }, 500);
            } else {
                if (confirm(`Ver matriz de calificaciones para ${nivel}?`)) {
                    window.location.href = '../matriz_calificaciones.php';
                }
            }
        }

        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            // Animar contadores
            const statNumbers = document.querySelectorAll('.stat-number-admin');
            statNumbers.forEach(element => {
                const target = parseInt(element.textContent);
                if (target > 0) {
                    let current = 0;
                    const increment = target / 50;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            element.textContent = target;
                            clearInterval(timer);
                        } else {
                            element.textContent = Math.floor(current);
                        }
                    }, 30);
                }
            });

            // Animar barras de progreso
            const progressBars = document.querySelectorAll('.admin-progress .progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                if (width) {
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 500);
                }
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
</html>