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

// Obtener información contextual
$grado_actual = array_filter($grados, function($g) use ($grado_id) { return $g['id'] == $grado_id; });
$grado_actual = reset($grado_actual);
$area_actual = array_filter($areas, function($a) use ($area_id) { return $a['id'] == $area_id; });
$area_actual = reset($area_actual);
$periodo_actual = array_filter($periodos, function($p) use ($periodo_id) { return $p['id'] == $periodo_id; });
$periodo_actual = reset($periodo_actual);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matriz de Evaluación - <?php echo $area_actual['nombre']; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/main.css" rel="stylesheet">
    
    <style>
        .matriz-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .context-bar {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
        }

        .matriz-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin: 1rem 0;
        }

        .competencias-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .competencia-column {
            min-width: 120px;
            text-align: center;
            position: relative;
        }

        .competencia-info {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem;
            margin: 0.25rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .competencia-info:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }

        .competencia-code {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .competencia-progress {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .competencia-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.5s ease;
        }

        .estudiante-row {
            border-bottom: 1px solid #f1f5f9;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            position: relative;
        }

        .estudiante-row:hover {
            background: #f8fafc;
        }

        .estudiante-row:last-child {
            border-bottom: none;
        }

        .estudiante-info {
            display: flex;
            align-items: center;
            min-width: 280px;
            padding-right: 1rem;
        }

        .estudiante-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            margin-right: 1rem;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
        }

        .estudiante-nombre {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .estudiante-dni {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        .estudiante-progress {
            width: 60px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .estudiante-progress-bar {
            height: 100%;
            transition: width 0.5s ease;
            border-radius: 3px;
        }

        .calificaciones-grid {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex: 1;
            padding-left: 1rem;
        }

        .competencia-cell {
            min-width: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0.25rem;
        }

        .cal-btn-group {
            display: flex;
            gap: 3px;
            background: #f8fafc;
            padding: 4px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .cal-btn {
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            color: #64748b;
            background: white;
        }

        .cal-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .cal-btn.active {
            color: white;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .cal-btn.cal-AD.active {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .cal-btn.cal-A.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .cal-btn.cal-B.active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .cal-btn.cal-C.active {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .quick-stats {
            position: fixed;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
            z-index: 999;
            min-width: 200px;
        }

        .quick-stats-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            text-align: center;
        }

        .stat-item {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            border-radius: 8px;
            background: #f8fafc;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
            flex: 1;
        }

        .stat-value {
            font-weight: 700;
            font-size: 1rem;
        }

        .progress-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: conic-gradient(var(--success-color) 0deg, var(--success-color) var(--progress, 0deg), #e2e8f0 var(--progress, 0deg));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem auto;
            position: relative;
        }

        .progress-circle::before {
            content: '';
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            position: absolute;
        }

        .progress-percentage {
            position: relative;
            z-index: 1;
            font-weight: 800;
            font-size: 0.9rem;
            color: #1e293b;
        }

        .filter-bar {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .area-indicator {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .competencia-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.7rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 100;
        }

        .competencia-info:hover .competencia-tooltip {
            opacity: 1;
        }

        .matriz-scroll {
            overflow-x: auto;
            max-width: 100%;
        }

        .matriz-table {
            min-width: max-content;
        }

        @media (max-width: 1200px) {
            .quick-stats {
                position: static;
                transform: none;
                margin: 1rem auto;
                max-width: 300px;
            }
        }

        @media (max-width: 768px) {
            .estudiante-info {
                min-width: 200px;
            }
            
            .competencia-column {
                min-width: 100px;
            }
            
            .cal-btn {
                width: 20px;
                height: 20px;
                font-size: 0.6rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header Principal -->
    <div class="matriz-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-grid-3x3-gap me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h1 class="h4 mb-0 fw-bold">Matriz de Evaluación</h1>
                            <small class="opacity-75">Registro por competencias</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <?php if ($auth->hasPermission(['Coordinadora'])): ?>
                            <span class="badge" style="background: linear-gradient(45deg, #fbbf24, #f59e0b); color: #92400e;">
                                <i class="bi bi-eye me-1"></i>Supervisión
                            </span>
                        <?php endif; ?>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo $_SESSION['user_name']; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><span class="dropdown-item-text small">
                                    <i class="bi bi-shield-check me-1 text-primary"></i>
                                    <?php echo $_SESSION['user_rol']; ?>
                                </span></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($auth->hasPermission(['Administrador', 'Coordinadora'])): ?>
                                    <li><a class="dropdown-item" href="admin/index.php">
                                        <i class="bi bi-gear me-1"></i> Panel Admin
                                    </a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid" style="max-width: 1400px;">
        <!-- Barra de Contexto -->
        <div class="context-bar">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="area-indicator">
                            <i class="bi bi-book"></i>
                            <?php echo $area_actual['nombre']; ?>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-mortarboard me-1"></i>
                            <?php echo $grado_actual['nivel_nombre'] . ' ' . $grado_actual['nombre'] . ' - ' . $grado_actual['seccion']; ?>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?php echo $periodo_actual['nombre'] . ' ' . $periodo_actual['año']; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <span class="badge bg-primary">
                            <?php echo count($estudiantes); ?> estudiantes
                        </span>
                        <span class="badge bg-success">
                            <?php echo count($competencias); ?> competencias
                        </span>
                        <span class="badge bg-info">
                            <?php echo $porcentaje_completitud; ?>% avance
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros Compactos -->
        <div class="filter-bar">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-3">
                    <select name="periodo_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($periodos as $periodo): ?>
                            <option value="<?php echo $periodo['id']; ?>" <?php echo $periodo_id == $periodo['id'] ? 'selected' : ''; ?>>
                                <?php echo $periodo['nombre'] . ' - ' . $periodo['año']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="grado_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($grados as $grado): ?>
                            <option value="<?php echo $grado['id']; ?>" <?php echo $grado_id == $grado['id'] ? 'selected' : ''; ?>>
                                <?php echo $grado['nivel_nombre'] . ' ' . $grado['nombre'] . ' - ' . $grado['seccion']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="area_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($areas as $area): ?>
                            <option value="<?php echo $area['id']; ?>" <?php echo $area_id == $area['id'] ? 'selected' : ''; ?>>
                                <?php echo $area['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-1">
                        <a href="export/excel.php?grado_id=<?php echo $grado_id; ?>&area_id=<?php echo $area_id; ?>&periodo_id=<?php echo $periodo_id; ?>" 
                           class="btn btn-outline-success btn-sm" title="Exportar Excel">
                            <i class="bi bi-download"></i>
                        </a>
                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                onclick="location.reload()" title="Actualizar">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Matriz Principal -->
        <div class="matriz-container">
            <!-- Header de Competencias -->
            <div class="competencias-header">
                <div class="matriz-scroll">
                    <div class="matriz-table d-flex">
                        <div style="min-width: 280px; padding-right: 1rem;">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-people me-2"></i>
                                Estudiantes
                            </h6>
                        </div>
                        <div class="d-flex gap-2 flex-1">
                            <?php foreach ($competencias as $competencia): ?>
                                <div class="competencia-column">
                                    <div class="competencia-info">
                                        <div class="competencia-code"><?php echo $competencia['codigo']; ?></div>
                                        <div class="small text-muted mb-2" style="line-height: 1.2;">
                                            <?php echo substr($competencia['descripcion'], 0, 40) . '...'; ?>
                                        </div>
                                        <?php 
                                        $evaluados = array_filter($matriz, function($m) use ($competencia) { 
                                            return $m['competencia_id'] == $competencia['id'] && !is_null($m['calificacion']); 
                                        });
                                        $porcentaje_comp = count($estudiantes) > 0 ? round((count($evaluados) / count($estudiantes)) * 100) : 0;
                                        ?>
                                        <div class="small fw-semibold text-success">
                                            <?php echo count($evaluados); ?>/<?php echo count($estudiantes); ?>
                                        </div>
                                        <div class="competencia-progress">
                                            <div class="competencia-progress-bar" style="width: <?php echo $porcentaje_comp; ?>%"></div>
                                        </div>
                                        <div class="competencia-tooltip">
                                            <?php echo $competencia['descripcion']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filas de Estudiantes -->
            <div class="matriz-body">
                <?php foreach ($estudiantes as $estudiante_id => $estudiante): ?>
                    <div class="estudiante-row">
                        <div class="matriz-scroll">
                            <div class="matriz-table d-flex align-items-center">
                                <div class="estudiante-info">
                                    <div class="estudiante-avatar">
                                        <?php echo strtoupper(substr($estudiante['info']['nombres'], 0, 1) . substr($estudiante['info']['apellidos'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="estudiante-nombre">
                                            <?php echo $estudiante['info']['apellidos'] . ', ' . $estudiante['info']['nombres']; ?>
                                        </div>
                                        <div class="estudiante-dni">
                                            DNI: <?php echo $estudiante['info']['dni']; ?>
                                        </div>
                                        <?php 
                                        $stats = $calificaciones->getEstadisticasEstudiante($estudiante_id, $periodo_id, $area_id);
                                        $porcentaje_estudiante = $stats['total_competencias'] > 0 ? 
                                            round(($stats['evaluadas'] / $stats['total_competencias']) * 100) : 0;
                                        ?>
                                        <div class="estudiante-progress mt-1">
                                            <div class="estudiante-progress-bar" 
                                                 style="width: <?php echo $porcentaje_estudiante; ?>%; 
                                                 background: <?php 
                                                 if ($porcentaje_estudiante >= 90) echo 'linear-gradient(90deg, #10b981, #059669)';
                                                 elseif ($porcentaje_estudiante >= 70) echo 'linear-gradient(90deg, #3b82f6, #2563eb)';
                                                 elseif ($porcentaje_estudiante >= 50) echo 'linear-gradient(90deg, #f59e0b, #d97706)';
                                                 else echo 'linear-gradient(90deg, #ef4444, #dc2626)';
                                                 ?>;">
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <?php echo $stats['evaluadas']; ?>/<?php echo count($competencias); ?> (<?php echo $porcentaje_estudiante; ?>%)
                                        </div>
                                    </div>
                                </div>
                                <div class="calificaciones-grid">
                                    <?php foreach ($competencias as $competencia): ?>
                                        <div class="competencia-cell">
                                            <?php 
                                            $calificacion_actual = $estudiante['calificaciones'][$competencia['id']] ?? null;
                                            $puede_editar = $auth->hasPermission(['Administrador', 'Tutor del Aula', 'Docente Área', 'Docente Taller']);
                                            ?>
                                            
                                            <?php if ($puede_editar): ?>
                                                <div class="cal-btn-group">
                                                    <?php foreach (['AD', 'A', 'B', 'C'] as $calif): ?>
                                                        <button type="button" 
                                                                class="cal-btn cal-<?php echo $calif; ?> <?php echo $calificacion_actual == $calif ? 'active' : ''; ?>"
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
                                                                ?>">
                                                            <?php echo $calif; ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center">
                                                    <?php if ($calificacion_actual): ?>
                                                        <span class="badge" style="background: <?php 
                                                        $colores = [
                                                            'AD' => 'linear-gradient(135deg, #10b981, #059669)',
                                                            'A' => 'linear-gradient(135deg, #3b82f6, #2563eb)',
                                                            'B' => 'linear-gradient(135deg, #f59e0b, #d97706)',
                                                            'C' => 'linear-gradient(135deg, #ef4444, #dc2626)'
                                                        ];
                                                        echo $colores[$calificacion_actual];
                                                        ?>; color: white;">
                                                            <?php echo $calificacion_actual; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Panel de Estadísticas Flotante -->
    <div class="quick-stats">
        <div class="quick-stats-title">
            <i class="bi bi-bar-chart-fill me-1"></i>
            Resumen General
        </div>
        
        <div class="progress-circle" style="--progress: <?php echo $porcentaje_completitud * 3.6; ?>deg;">
            <div class="progress-percentage"><?php echo $porcentaje_completitud; ?>%</div>
        </div>
        
        <div class="stat-item">
            <span class="stat-label">Evaluadas:</span>
            <span class="stat-value text-success"><?php echo $estadisticas['evaluaciones_realizadas']; ?></span>
        </div>
        
        <div class="stat-item">
            <span class="stat-label">Pendientes:</span>
            <span class="stat-value text-warning"><?php echo $estadisticas['total_evaluaciones_posibles'] - $estadisticas['evaluaciones_realizadas']; ?></span>
        </div>
        
        <div class="stat-item">
            <span class="stat-label">AD:</span>
            <span class="stat-value" style="color: #10b981;"><?php echo $estadisticas['destacado']; ?></span>
        </div>
        
        <div class="stat-item">
            <span class="stat-label">A:</span>
            <span class="stat-value" style="color: #3b82f6;"><?php echo $estadisticas['esperado']; ?></span>
        </div>
        
        <div class="stat-item">
            <span class="stat-label">B:</span>
            <span class="stat-value" style="color: #f59e0b;"><?php echo $estadisticas['proceso']; ?></span>
        </div>
        
        <div class="stat-item">
            <span class="stat-label">C:</span>
            <span class="stat-value" style="color: #ef4444;"><?php echo $estadisticas['inicio']; ?></span>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">
                <i class="bi bi-clock me-1"></i>
                <span id="last-update"><?php echo date('H:i:s'); ?></span>
            </small>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        // Configuración global
        window.matrizConfig = {
            periodoId: <?php echo $periodo_id; ?>,
            gradoId: <?php echo $grado_id; ?>,
            areaId: <?php echo $area_id; ?>,
            canEdit: <?php echo $auth->hasPermission(['Administrador', 'Tutor del Aula', 'Docente Área', 'Docente Taller']) ? 'true' : 'false'; ?>
        };

        // Función para guardar calificación con nueva UI
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
                    // Actualizar UI inmediatamente
                    actualizarVisualizacion(estudianteId, competenciaId, calificacion);
                    
                    // Mostrar feedback sutil
                    mostrarFeedbackSutil();
                    
                    // Actualizar estadísticas
                    setTimeout(() => {
                        actualizarEstadisticas();
                        actualizarProgresosVisuales();
                    }, 300);
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }

                return result;
            } catch (error) {
                console.error('Error al guardar calificación:', error);
                SystemJS.Notifications.error('Error al guardar: ' + error.message);
                throw error;
            }
        }

        function actualizarVisualizacion(estudianteId, competenciaId, nuevaCalificacion) {
            // Encontrar el grupo de botones
            const btnGroup = document.querySelector(
                `[data-estudiante="${estudianteId}"][data-competencia="${competenciaId}"]`
            ).closest('.cal-btn-group');
            
            if (btnGroup) {
                // Remover clase active de todos los botones
                btnGroup.querySelectorAll('.cal-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Activar el botón correspondiente
                const targetBtn = btnGroup.querySelector(`[data-calificacion="${nuevaCalificacion}"]`);
                if (targetBtn) {
                    targetBtn.classList.add('active');
                    
                    // Efecto de pulso
                    targetBtn.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        targetBtn.style.transform = '';
                    }, 200);
                }
            }
        }

        function mostrarFeedbackSutil() {
            // Feedback visual sutil en lugar de notificación
            const indicator = document.createElement('div');
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 20px;
                font-size: 0.8rem;
                z-index: 9999;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.3s ease;
            `;
            indicator.innerHTML = '<i class="bi bi-check2 me-1"></i>Guardado';
            document.body.appendChild(indicator);
            
            setTimeout(() => {
                indicator.style.opacity = '1';
                indicator.style.transform = 'translateY(0)';
            }, 10);
            
            setTimeout(() => {
                indicator.style.opacity = '0';
                indicator.style.transform = 'translateY(-20px)';
                setTimeout(() => indicator.remove(), 300);
            }, 1500);
        }

        function actualizarEstadisticas() {
            // Contar calificaciones actuales
            const stats = { AD: 0, A: 0, B: 0, C: 0, total: 0 };
            
            document.querySelectorAll('.cal-btn.active').forEach(btn => {
                const calif = btn.dataset.calificacion;
                if (calif && stats.hasOwnProperty(calif)) {
                    stats[calif]++;
                    stats.total++;
                }
            });

            // Actualizar panel de estadísticas
            const updateElement = (selector, value) => {
                const element = document.querySelector(selector);
                if (element) {
                    const currentValue = parseInt(element.textContent) || 0;
                    if (currentValue !== value) {
                        element.style.transform = 'scale(1.1)';
                        element.textContent = value;
                        setTimeout(() => {
                            element.style.transform = '';
                        }, 200);
                    }
                }
            };

            updateElement('.stat-item:nth-child(3) .stat-value', stats.AD);
            updateElement('.stat-item:nth-child(4) .stat-value', stats.A);
            updateElement('.stat-item:nth-child(5) .stat-value', stats.B);
            updateElement('.stat-item:nth-child(6) .stat-value', stats.C);

            // Actualizar porcentaje general
            const totalPosibles = document.querySelectorAll('.cal-btn-group').length;
            const porcentaje = totalPosibles > 0 ? Math.round((stats.total / totalPosibles) * 100) : 0;
            
            const progressPercentage = document.querySelector('.progress-percentage');
            const progressCircle = document.querySelector('.progress-circle');
            if (progressPercentage && progressCircle) {
                progressPercentage.textContent = porcentaje + '%';
                progressCircle.style.setProperty('--progress', (porcentaje * 3.6) + 'deg');
            }

            // Actualizar evaluadas/pendientes
            updateElement('.stat-item:nth-child(1) .stat-value', stats.total);
            updateElement('.stat-item:nth-child(2) .stat-value', totalPosibles - stats.total);
        }

        function actualizarProgresosVisuales() {
            // Actualizar progreso por estudiante
            document.querySelectorAll('.estudiante-row').forEach(row => {
                const estudianteId = row.querySelector('[data-estudiante]')?.dataset.estudiante;
                if (!estudianteId) return;
                
                const btnGroups = row.querySelectorAll('.cal-btn-group');
                const totalCompetencias = btnGroups.length;
                const evaluadas = Array.from(btnGroups).filter(group => 
                    group.querySelector('.cal-btn.active')
                ).length;
                
                const porcentaje = totalCompetencias > 0 ? Math.round((evaluadas / totalCompetencias) * 100) : 0;
                
                // Actualizar barra de progreso
                const progressBar = row.querySelector('.estudiante-progress-bar');
                if (progressBar) {
                    progressBar.style.width = porcentaje + '%';
                    
                    // Cambiar color según progreso
                    let color;
                    if (porcentaje >= 90) color = 'linear-gradient(90deg, #10b981, #059669)';
                    else if (porcentaje >= 70) color = 'linear-gradient(90deg, #3b82f6, #2563eb)';
                    else if (porcentaje >= 50) color = 'linear-gradient(90deg, #f59e0b, #d97706)';
                    else color = 'linear-gradient(90deg, #ef4444, #dc2626)';
                    
                    progressBar.style.background = color;
                }
                
                // Actualizar texto de progreso
                const progressText = row.querySelector('.small.text-muted');
                if (progressText) {
                    progressText.textContent = `${evaluadas}/${totalCompetencias} (${porcentaje}%)`;
                }
            });

            // Actualizar progreso por competencia
            document.querySelectorAll('.competencia-column').forEach((column, index) => {
                const competenciaId = document.querySelector(`[data-competencia="${competencias[index]?.id}"]`)?.dataset.competencia;
                if (!competenciaId) return;
                
                const activeButtons = document.querySelectorAll(`[data-competencia="${competenciaId}"].active`);
                const totalStudents = document.querySelectorAll(`[data-competencia="${competenciaId}"]`).length / 4; // 4 botones por estudiante
                const evaluados = activeButtons.length;
                
                const porcentaje = totalStudents > 0 ? Math.round((evaluados / totalStudents) * 100) : 0;
                
                // Actualizar barra de progreso de competencia
                const progressBar = column.querySelector('.competencia-progress-bar');
                if (progressBar) {
                    progressBar.style.width = porcentaje + '%';
                }
                
                // Actualizar contador
                const counter = column.querySelector('.small.fw-semibold');
                if (counter) {
                    counter.textContent = `${evaluados}/${Math.round(totalStudents)}`;
                }
            });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Animaciones de entrada
            const rows = document.querySelectorAll('.estudiante-row');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });

            // Animar competencias
            const competencias = document.querySelectorAll('.competencia-info');
            competencias.forEach((comp, index) => {
                comp.style.opacity = '0';
                comp.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    comp.style.transition = 'all 0.3s ease';
                    comp.style.opacity = '1';
                    comp.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animar barras de progreso
            setTimeout(() => {
                document.querySelectorAll('.competencia-progress-bar, .estudiante-progress-bar').forEach(bar => {
                    const width = bar.style.width;
                    if (width) {
                        bar.style.width = '0%';
                        setTimeout(() => {
                            bar.style.width = width;
                        }, 100);
                    }
                });
            }, 800);
        });

        // Manejar clicks en botones de calificación
        document.addEventListener('click', async function(e) {
            if (e.target.classList.contains('cal-btn') && window.matrizConfig.canEdit) {
                const estudianteId = e.target.dataset.estudiante;
                const competenciaId = e.target.dataset.competencia;
                const calificacion = e.target.dataset.calificacion;

                if (estudianteId && competenciaId && calificacion) {
                    // Feedback inmediato
                    e.target.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        e.target.style.transform = '';
                    }, 100);

                    try {
                        await guardarCalificacion(estudianteId, competenciaId, calificacion);
                    } catch (error) {
                        // Error ya manejado en la función
                    }
                }
            }
        });

        // Actualizar timestamp cada minuto
        setInterval(() => {
            const timestamp = document.getElementById('last-update');
            if (timestamp) {
                timestamp.textContent = new Date().toLocaleTimeString();
            }
        }, 60000);

        // Shortcuts de teclado
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                actualizarEstadisticas();
                mostrarFeedbackSutil();
            }
        });

        // Mostrar loading en filtros
        document.addEventListener('change', function(e) {
            if (e.target.matches('select')) {
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(255,255,255,0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                `;
                overlay.innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-2"></div>
                        <div class="small text-muted">Actualizando matriz...</div>
                    </div>
                `;
                document.body.appendChild(overlay);
            }
        });

        // Variable global para las competencias (para usar en actualizarProgresosVisuales)
        const competencias = <?php echo json_encode($competencias); ?>;

        console.log('🚀 Matriz moderna cargada correctamente');
    </script>
</body>
</html>