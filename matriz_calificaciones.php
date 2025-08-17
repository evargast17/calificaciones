<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Calificaciones.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$calificaciones = new Calificaciones();

// Obtener datos para filtros
$grados = $calificaciones->getGrados();
$areas = $calificaciones->getAreas();
$periodos = $calificaciones->getPeriodos();

// Filtros por defecto
$grado_id = $_GET['grado_id'] ?? 1;
$area_id = $_GET['area_id'] ?? 1;
$periodo_id = $_GET['periodo_id'] ?? 1;

// Obtener datos de la matriz
$matriz = $calificaciones->getMatrizCalificaciones($grado_id, $area_id, $periodo_id);
$competencias = $calificaciones->getCompetencias($area_id);
$estadisticas = $calificaciones->getEstadisticasGenerales($grado_id, $area_id, $periodo_id);

// Organizar datos por estudiante
$estudiantes = [];
foreach ($matriz as $row) {
    $estudiante_id = $row['estudiante_id'];
    if (!isset($estudiantes[$estudiante_id])) {
        $estudiantes[$estudiante_id] = [
            'info' => [
                'id' => $row['estudiante_id'],
                'nombres' => $row['nombres'],
                'apellidos' => $row['apellidos'],
                'dni' => $row['dni']
            ],
            'calificaciones' => []
        ];
    }
    $estudiantes[$estudiante_id]['calificaciones'][$row['competencia_id']] = $row['calificacion'];
}

// Calcular porcentaje de completitud
$porcentaje_completitud = 0;
if ($estadisticas['total_evaluaciones_posibles'] > 0) {
    $porcentaje_completitud = round(($estadisticas['evaluaciones_realizadas'] / $estadisticas['total_evaluaciones_posibles']) * 100, 1);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matriz de Calificaciones</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="system-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="me-4">
                            <i class="bi bi-grid-3x3-gap" style="font-size: 2.5rem; opacity: 0.9;"></i>
                        </div>
                        <div>
                            <h1 class="h2 mb-0 fw-bold">Matriz de Evaluación</h1>
                            <p class="mb-0 opacity-75">Registro y seguimiento de calificaciones por competencias</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <?php if ($auth->hasPermission(['Coordinadora'])): ?>
                        <span class="badge badge-modern" style="background: linear-gradient(45deg, #fbbf24, #f59e0b); color: #92400e;">
                            <i class="bi bi-eye-fill me-1"></i> Supervisión
                        </span>
                    <?php endif; ?>
                    <div class="dropdown d-inline-block ms-2">
                        <button class="btn btn-light btn-modern dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo $_SESSION['user_name']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text">
                                <i class="bi bi-shield-check me-2 text-primary"></i>
                                <?php echo $_SESSION['user_rol']; ?>
                            </span></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($auth->hasPermission(['Administrador', 'Coordinadora'])): ?>
                                <li><a class="dropdown-item" href="admin/index.php">
                                    <i class="bi bi-gear me-2"></i> Panel Admin
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Info y Estadísticas -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                    <?php 
                    $grado_actual = array_filter($grados, function($g) use ($grado_id) { return $g['id'] == $grado_id; });
                    $grado_actual = reset($grado_actual);
                    $area_actual = array_filter($areas, function($a) use ($area_id) { return $a['id'] == $area_id; });
                    $area_actual = reset($area_actual);
                    $periodo_actual = array_filter($periodos, function($p) use ($periodo_id) { return $p['id'] == $periodo_id; });
                    $periodo_actual = reset($periodo_actual);
                    ?>
                    <span class="badge badge-gradient-primary badge-modern">
                        <i class="bi bi-mortarboard me-1"></i>
                        <?php echo $grado_actual['nivel_nombre'] . ' ' . $grado_actual['nombre'] . ' - ' . $grado_actual['seccion']; ?>
                    </span>
                    <span class="badge badge-gradient-success badge-modern">
                        <i class="bi bi-book me-1"></i>
                        <?php echo $area_actual['nombre']; ?>
                    </span>
                    <span class="badge badge-gradient-info badge-modern">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?php echo $periodo_actual['nombre']; ?>
                    </span>
                    <span class="badge badge-gradient-warning badge-modern">
                        <i class="bi bi-people me-1"></i>
                        <?php echo count($estudiantes); ?> Estudiantes
                    </span>
                    <span class="badge" style="background: linear-gradient(45deg, #8b5cf6, #a855f7); color: white;">
                        <i class="bi bi-list-check me-1"></i>
                        <?php echo count($competencias); ?> Competencias
                    </span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="modern-card card-gradient-success text-center text-white" style="background: var(--gradient-success);">
                    <div class="row align-items-center p-3">
                        <div class="col-6">
                            <h2 class="mb-0" id="stats-total"><?php echo $estadisticas['evaluaciones_realizadas']; ?></h2>
                            <small class="opacity-75">Evaluadas</small>
                        </div>
                        <div class="col-6">
                            <h2 class="mb-0"><?php echo $estadisticas['total_evaluaciones_posibles'] - $estadisticas['evaluaciones_realizadas']; ?></h2>
                            <small class="opacity-75">Pendientes</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <h1 class="mb-0" id="completitud-general"><?php echo $porcentaje_completitud; ?>%</h1>
                        <small class="opacity-75">Completitud General</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-container animate-fade-in">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-calendar3 me-1 text-primary"></i>
                        Período
                    </label>
                    <select name="periodo_id" class="form-select form-select-modern" onchange="this.form.submit()">
                        <?php foreach ($periodos as $periodo): ?>
                            <option value="<?php echo $periodo['id']; ?>" <?php echo $periodo_id == $periodo['id'] ? 'selected' : ''; ?>>
                                <?php echo $periodo['nombre'] . ' - ' . $periodo['año']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-mortarboard me-1 text-success"></i>
                        Grado
                    </label>
                    <select name="grado_id" class="form-select form-select-modern" onchange="this.form.submit()">
                        <?php foreach ($grados as $grado): ?>
                            <option value="<?php echo $grado['id']; ?>" <?php echo $grado_id == $grado['id'] ? 'selected' : ''; ?>>
                                <?php echo $grado['nivel_nombre'] . ' ' . $grado['nombre'] . ' - ' . $grado['seccion']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-book me-1 text-info"></i>
                        Área Curricular
                    </label>
                    <select name="area_id" class="form-select form-select-modern" onchange="this.form.submit()">
                        <?php foreach ($areas as $area): ?>
                            <option value="<?php echo $area['id']; ?>" <?php echo $area_id == $area['id'] ? 'selected' : ''; ?>>
                                <?php echo $area['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <a href="export/excel.php?grado_id=<?php echo $grado_id; ?>&area_id=<?php echo $area_id; ?>&periodo_id=<?php echo $periodo_id; ?>" 
                           class="btn btn-success-modern btn-modern btn-export" 
                           title="Exportar a Excel" 
                           data-bs-toggle="tooltip">
                            <i class="bi bi-file-earmark-excel"></i>
                        </a>
                        <button type="button" 
                                class="btn btn-info-modern btn-modern" 
                                onclick="window.matriz && window.matriz.refresh()" 
                                title="Actualizar matriz"
                                data-bs-toggle="tooltip">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Leyenda -->
        <div class="mb-4">
            <div class="modern-card p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="fw-bold text-dark">
                            <i class="bi bi-info-circle me-1"></i>
                            Escala de Calificación:
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="calificacion-btn calificacion-AD">AD</span>
                            <small class="text-muted">Logro destacado</small>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="calificacion-btn calificacion-A">A</span>
                            <small class="text-muted">Logro esperado</small>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="calificacion-btn calificacion-B">B</span>
                            <small class="text-muted">En proceso</small>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="calificacion-btn calificacion-C">C</span>
                            <small class="text-muted">En inicio</small>
                        </div>
                    </div>
                    <div class="text-muted">
                        <small class="last-update">
                            <i class="bi bi-clock me-1"></i>
                            Actualizado: <?php echo date('H:i:s'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matriz de Calificaciones -->
        <div class="matriz-container animate-fade-in">
            <div class="p-4">
                <!-- Headers de Competencias -->
                <div class="row mb-4">
                    <div class="col-3">
                        <div class="modern-card p-3 h-100 d-flex align-items-center">
                            <h5 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-people-fill me-2 text-primary"></i>
                                Estudiantes
                            </h5>
                        </div>
                    </div>
                    <div class="col-9">
                        <div class="row g-3">
                            <?php foreach ($competencias as $competencia): ?>
                                <div class="col">
                                    <div class="competencia-card hover-lift">
                                        <div class="competencia-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge badge-gradient-primary"><?php echo $competencia['codigo']; ?></span>
                                                <small class="text-primary fw-semibold">
                                                    <?php 
                                                    $evaluados = array_filter($matriz, function($m) use ($competencia) { 
                                                        return $m['competencia_id'] == $competencia['id'] && !is_null($m['calificacion']); 
                                                    });
                                                    echo count($evaluados) . '/' . count($estudiantes);
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="p-3">
                                            <small class="text-muted competencia-description">
                                                <?php echo substr($competencia['descripcion'], 0, 80) . '...'; ?>
                                            </small>
                                            <div class="mt-3">
                                                <div class="progress" style="height: 6px;">
                                                    <?php 
                                                    $porcentaje_comp = count($estudiantes) > 0 ? round((count($evaluados) / count($estudiantes)) * 100) : 0;
                                                    ?>
                                                    <div class="progress-bar bg-gradient" 
                                                         style="width: <?php echo $porcentaje_comp; ?>%; background: var(--gradient-success);">
                                                    </div>
                                                </div>
                                                <small class="text-success fw-semibold mt-1 d-block">
                                                    <?php echo $porcentaje_comp; ?>% completado
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Filas de Estudiantes -->
                <?php foreach ($estudiantes as $estudiante_id => $estudiante): ?>
                    <div class="estudiante-row animate-slide-up" data-student-id="<?php echo $estudiante_id; ?>">
                        <div class="row align-items-center">
                            <div class="col-3">
                                <div class="d-flex align-items-center">
                                    <div class="estudiante-avatar me-3">
                                        <?php echo strtoupper(substr($estudiante['info']['nombres'], 0, 1) . substr($estudiante['info']['apellidos'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark">
                                            <?php echo $estudiante['info']['apellidos'] . ', ' . $estudiante['info']['nombres']; ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-credit-card-2-front me-1"></i>
                                            DNI: <?php echo $estudiante['info']['dni']; ?>
                                        </small>
                                        <div class="mt-2" data-student-progress="<?php echo $estudiante_id; ?>">
                                            <?php 
                                            $stats = $calificaciones->getEstadisticasEstudiante($estudiante_id, $periodo_id, $area_id);
                                            $porcentaje_estudiante = $stats['total_competencias'] > 0 ? 
                                                round(($stats['evaluadas'] / $stats['total_competencias']) * 100) : 0;
                                            ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-success fw-semibold">
                                                    <span class="progress-number"><?php echo $stats['evaluadas']; ?></span>
                                                    /<?php echo count($competencias); ?>
                                                </small>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar" 
                                                         style="width: <?php echo $porcentaje_estudiante; ?>%; 
                                                         background: <?php 
                                                         if ($porcentaje_estudiante >= 90) echo '#10b981';
                                                         elseif ($porcentaje_estudiante >= 70) echo '#3b82f6';
                                                         elseif ($porcentaje_estudiante >= 50) echo '#f59e0b';
                                                         else echo '#ef4444';
                                                         ?>;">
                                                    </div>
                                                </div>
                                                <small class="text-muted fw-bold"><?php echo $porcentaje_estudiante; ?>%</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-9">
                                <div class="row g-3">
                                    <?php foreach ($competencias as $competencia): ?>
                                        <div class="col text-center">
                                            <?php 
                                            $calificacion_actual = $estudiante['calificaciones'][$competencia['id']] ?? null;
                                            $puede_editar = $auth->hasPermission(['Administrador', 'Tutor del Aula', 'Docente Área', 'Docente Taller']);
                                            ?>
                                            
                                            <?php if ($puede_editar): ?>
                                                <div class="d-flex flex-column gap-1" role="group">
                                                    <?php foreach (['AD', 'A', 'B', 'C'] as $calif): ?>
                                                        <button type="button" 
                                                                class="calificacion-btn <?php echo $calificacion_actual == $calif ? 'calificacion-'.$calif : 'calificacion-empty'; ?> hover-lift"
                                                                data-estudiante="<?php echo $estudiante_id; ?>"
                                                                data-competencia="<?php echo $competencia['id']; ?>"
                                                                data-calificacion="<?php echo $calif; ?>"
                                                                title="<?php 
                                                                $descripciones = [
                                                                    'AD' => 'Logro destacado',
                                                                    'A' => 'Logro esperado',
                                                                    'B' => 'En proceso',
                                                                    'C' => 'En inicio'
                                                                ];
                                                                echo $descripciones[$calif];
                                                                ?>"
                                                                data-bs-toggle="tooltip">
                                                            <?php echo $calif; ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center">
                                                    <?php if ($calificacion_actual): ?>
                                                        <span class="calificacion-btn calificacion-<?php echo $calificacion_actual; ?>"
                                                              title="<?php 
                                                              $descripciones = [
                                                                  'AD' => 'Logro destacado',
                                                                  'A' => 'Logro esperado',
                                                                  'B' => 'En proceso',
                                                                  'C' => 'En inicio'
                                                              ];
                                                              echo $descripciones[$calificacion_actual];
                                                              ?>"
                                                              data-bs-toggle="tooltip">
                                                            <?php echo $calificacion_actual; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="calificacion-btn calificacion-empty"
                                                              title="No evaluado"
                                                              data-bs-toggle="tooltip">
                                                            <i class="bi bi-dash"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <?php echo $calificacion_actual ? '15/08' : ''; ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Resumen por Competencia y Estadísticas -->
        <div class="row mt-5">
            <div class="col-md-8">
                <div class="modern-card">
                    <div class="card-header bg-gradient text-white" style="background: var(--gradient-primary) !important;">
                        <h5 class="mb-0">
                            <i class="bi bi-bar-chart-fill me-2"></i>
                            Resumen por Competencia
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <?php foreach ($competencias as $competencia): ?>
                                <?php 
                                $comp_stats = [];
                                foreach (['AD', 'A', 'B', 'C'] as $nivel) {
                                    $comp_stats[$nivel] = count(array_filter($matriz, function($m) use ($competencia, $nivel) {
                                        return $m['competencia_id'] == $competencia['id'] && $m['calificacion'] == $nivel;
                                    }));
                                }
                                $total_eval = array_sum($comp_stats);
                                ?>
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded-3 p-3 hover-lift" style="border-color: #e2e8f0 !important;">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge badge-gradient-primary badge-modern"><?php echo $competencia['codigo']; ?></span>
                                            <small class="text-muted fw-semibold">
                                                <?php echo $total_eval; ?>/<?php echo count($estudiantes); ?> evaluados
                                            </small>
                                        </div>
                                        <p class="text-muted mb-3 small">
                                            <?php echo substr($competencia['descripcion'], 0, 60) . '...'; ?>
                                        </p>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <span class="badge" style="background: var(--gradient-success);">AD: <?php echo $comp_stats['AD']; ?></span>
                                            <span class="badge" style="background: var(--gradient-primary);">A: <?php echo $comp_stats['A']; ?></span>
                                            <span class="badge" style="background: var(--gradient-warning);">B: <?php echo $comp_stats['B']; ?></span>
                                            <span class="badge" style="background: linear-gradient(135deg, #ef4444, #dc2626);">C: <?php echo $comp_stats['C']; ?></span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="progress" style="height: 8px;">
                                                <?php 
                                                $porcentaje_logro = $total_eval > 0 ? round((($comp_stats['AD'] + $comp_stats['A']) / $total_eval) * 100) : 0;
                                                ?>
                                                <div class="progress-bar" 
                                                     style="width: <?php echo $porcentaje_logro; ?>%; background: var(--gradient-success);">
                                                </div>
                                            </div>
                                            <small class="text-success fw-semibold mt-1 d-block">
                                                <?php echo $porcentaje_logro; ?>% logro satisfactorio
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="modern-card">
                    <div class="card-header bg-gradient text-white" style="background: var(--gradient-success) !important;">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up-arrow me-2"></i>
                            Estadísticas Generales
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="stat-icon stat-icon-primary mx-auto">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <h3 class="stat-number" id="stats-evaluated"><?php echo $estadisticas['evaluaciones_realizadas']; ?></h3>
                                <small class="text-muted">Evaluadas</small>
                            </div>
                            <div class="col-6">
                                <div class="stat-icon stat-icon-warning mx-auto">
                                    <i class="bi bi-clock-fill"></i>
                                </div>
                                <h3 class="stat-number"><?php echo $estadisticas['total_evaluaciones_posibles'] - $estadisticas['evaluaciones_realizadas']; ?></h3>
                                <small class="text-muted">Pendientes</small>
                            </div>
                        </div>
                        
                        <div class="progress-ring mx-auto mb-3" style="--progress: <?php echo $porcentaje_completitud * 3.6; ?>deg;">
                            <div class="percentage"><?php echo $porcentaje_completitud; ?>%</div>
                        </div>
                        <h4 class="text-success mb-3">Completitud General</h4>
                        
                        <hr class="my-4">
                        
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <span class="badge badge-modern" style="background: var(--gradient-warning); font-size: 1rem; padding: 0.5rem 1rem;">
                                    B: <span id="stats-b"><?php echo $estadisticas['proceso']; ?></span>
                                </span>
                            </div>
                            <div class="col-6">
                                <span class="badge badge-modern" style="background: linear-gradient(135deg, #ef4444, #dc2626); font-size: 1rem; padding: 0.5rem 1rem;">
                                    C: <span id="stats-c"><?php echo $estadisticas['inicio']; ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/matriz.js"></script>
    
    <!-- Script específico para esta página -->
    <script>
        // Configurar variables globales para JavaScript
        window.matrizConfig = {
            periodoId: <?php echo $periodo_id; ?>,
            gradoId: <?php echo $grado_id; ?>,
            areaId: <?php echo $area_id; ?>,
            canEdit: <?php echo $auth->hasPermission(['Administrador', 'Tutor del Aula', 'Docente Área', 'Docente Taller']) ? 'true' : 'false'; ?>
        };

        // Función específica para guardar calificaciones
        async function guardarCalificacion(estudianteId, competenciaId, calificacion) {
            try {
                const response = await fetch('ajax/guardar_calificacion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        estudiante_id: parseInt(estudianteId),
                        competencia_id: parseInt(competenciaId),
                        periodo_id: window.matrizConfig.periodoId,
                        calificacion: calificacion
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const result = await response.json();
                
                if (result.success) {
                    // Actualizar visualmente
                    actualizarVisualizacion(estudianteId, competenciaId, calificacion);
                    
                    // Mostrar confirmación
                    SystemJS.Notifications.success('Calificación guardada correctamente');
                    
                    // Actualizar estadísticas
                    setTimeout(() => {
                        actualizarEstadisticas();
                    }, 500);
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }

                return result;
            } catch (error) {
                console.error('Error al guardar calificación:', error);
                SystemJS.Notifications.error('Error al guardar la calificación: ' + error.message);
                throw error;
            }
        }

        function actualizarVisualizacion(estudianteId, competenciaId, nuevaCalificacion) {
            // Encontrar todos los botones de esta celda
            const buttons = document.querySelectorAll(
                `[data-estudiante="${estudianteId}"][data-competencia="${competenciaId}"]`
            );
            
            buttons.forEach(btn => {
                // Remover todas las clases de calificación
                btn.classList.remove('calificacion-AD', 'calificacion-A', 'calificacion-B', 'calificacion-C');
                btn.classList.add('calificacion-empty');
                
                // Aplicar la nueva calificación al botón correspondiente
                if (btn.dataset.calificacion === nuevaCalificacion) {
                    btn.classList.remove('calificacion-empty');
                    btn.classList.add(`calificacion-${nuevaCalificacion}`);
                    
                    // Efecto visual
                    SystemJS.Effects.pulse(btn);
                }
            });
        }

        function actualizarEstadisticas() {
            // Contar todas las calificaciones actuales
            const stats = { AD: 0, A: 0, B: 0, C: 0, total: 0 };
            
            document.querySelectorAll('.calificacion-btn:not(.calificacion-empty)').forEach(btn => {
                if (btn.classList.contains('calificacion-AD')) stats.AD++;
                else if (btn.classList.contains('calificacion-A')) stats.A++;
                else if (btn.classList.contains('calificacion-B')) stats.B++;
                else if (btn.classList.contains('calificacion-C')) stats.C++;
            });
            
            stats.total = stats.AD + stats.A + stats.B + stats.C;

            // Actualizar elementos en la UI
            const elements = {
                'stats-ad': stats.AD,
                'stats-a': stats.A,
                'stats-b': stats.B,
                'stats-c': stats.C
            };

            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    SystemJS.Effects.countUp(element, value, 500);
                }
            });

            // Actualizar progreso por estudiante
            actualizarProgresosEstudiantes();
        }

        function actualizarProgresosEstudiantes() {
            document.querySelectorAll('[data-student-progress]').forEach(progressElement => {
                const estudianteId = progressElement.dataset.studentProgress;
                const buttons = document.querySelectorAll(`[data-estudiante="${estudianteId}"]`);
                
                const totalCompetencias = buttons.length / 4; // 4 botones por competencia
                const evaluadas = Array.from(buttons).filter(btn => 
                    !btn.classList.contains('calificacion-empty')
                ).length;
                
                const porcentaje = Math.round((evaluadas / totalCompetencias) * 100);
                
                // Actualizar barra de progreso
                const progressBar = progressElement.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${porcentaje}%`;
                    
                    // Cambiar color según progreso
                    let color = '#ef4444'; // Rojo por defecto
                    if (porcentaje >= 90) color = '#10b981'; // Verde
                    else if (porcentaje >= 70) color = '#3b82f6'; // Azul
                    else if (porcentaje >= 50) color = '#f59e0b'; // Amarillo
                    
                    progressBar.style.background = color;
                }
                
                // Actualizar número
                const numberElement = progressElement.querySelector('.progress-number');
                if (numberElement) {
                    SystemJS.Effects.countUp(numberElement, evaluadas, 300);
                }
            });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Animar contadores al cargar
            setTimeout(() => {
                const statNumbers = document.querySelectorAll('.stat-number');
                statNumbers.forEach(element => {
                    const target = parseInt(element.textContent);
                    if (target > 0) {
                        element.textContent = '0';
                        SystemJS.Effects.countUp(element, target, 1000);
                    }
                });
            }, 300);

            // Animar barras de progreso
            setTimeout(() => {
                const progressBars = document.querySelectorAll('.progress-bar');
                progressBars.forEach(bar => {
                    const width = bar.style.width;
                    if (width) {
                        bar.style.width = '0%';
                        setTimeout(() => {
                            bar.style.width = width;
                        }, 100);
                    }
                });
            }, 500);
        });

        // Manejar clicks en botones de calificación
        document.addEventListener('click', async function(e) {
            if (e.target.classList.contains('calificacion-btn') && 
                window.matrizConfig.canEdit && 
                !e.target.classList.contains('calificacion-empty')) {
                
                const estudianteId = e.target.dataset.estudiante;
                const competenciaId = e.target.dataset.competencia;
                const calificacion = e.target.dataset.calificacion;

                if (estudianteId && competenciaId && calificacion) {
                    try {
                        // Feedback visual inmediato
                        e.target.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            e.target.style.transform = '';
                        }, 150);

                        await guardarCalificacion(estudianteId, competenciaId, calificacion);
                    } catch (error) {
                        // El error ya se maneja en la función guardarCalificacion
                    }
                }
            }
        });

        // Shortcuts de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl + S: Mostrar estado
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                SystemJS.Notifications.info('Sistema de auto-guardado activo');
            }
            
            // Ctrl + R: Actualizar estadísticas
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                actualizarEstadisticas();
                SystemJS.Notifications.success('Estadísticas actualizadas');
            }
        });

        // Auto-actualizar timestamp
        setInterval(() => {
            const timestamp = document.querySelector('.last-update');
            if (timestamp) {
                const now = new Date();
                timestamp.innerHTML = `<i class="bi bi-clock me-1"></i>Actualizado: ${now.toLocaleTimeString()}`;
            }
        }, 60000);

        // Mostrar indicador de carga en cambios de filtro
        document.addEventListener('change', function(e) {
            if (e.target.matches('select[name="periodo_id"], select[name="grado_id"], select[name="area_id"]')) {
                SystemJS.Loading.show(document.body, 'Actualizando matriz...');
            }
        });

        console.log('🎯 Matriz de calificaciones cargada correctamente');
    </script>
</body>
</html> 