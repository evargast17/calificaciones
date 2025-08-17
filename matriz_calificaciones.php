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

$puede_editar = $auth->hasPermission(['Administrador', 'Tutor del Aula', 'Docente Área', 'Docente Taller']);
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
    
    <style>
        :root {
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }

        /* Header Compacto */
        .header-compacto {
            background: linear-gradient(135deg, var(--gray-800) 0%, var(--gray-900) 100%);
            color: white;
            padding: 0.75rem 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .area-badge {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .context-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .progress-global {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
        }

        .progress-bar-mini {
            width: 80px;
            height: 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--success);
            transition: width 0.3s ease;
        }

        /* Control de Filtros */
        .filtros-rapidos {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filtros-rapidos select {
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            background: white;
            min-width: 150px;
        }

        .filtros-rapidos select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        .herramientas-grupo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
        }

        .btn-herramienta {
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--gray-300);
            background: white;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: var(--gray-700);
        }

        .btn-herramienta:hover {
            border-color: var(--primary);
            background: #f0f7ff;
            color: var(--primary);
            text-decoration: none;
        }

        /* Header de Competencias Mejorado */
        .competencias-header {
            background: var(--gray-50);
            border-bottom: 2px solid var(--gray-200);
            padding: 1rem;
            position: sticky;
            top: 60px;
            z-index: 999;
        }

        .competencias-grid {
            display: grid;
            grid-template-columns: 250px repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            align-items: stretch;
        }

        .comp-header {
            text-align: center;
            padding: 1rem;
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-700);
            position: relative;
            transition: all 0.2s ease;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .comp-header:hover {
            border-color: var(--primary);
            background: #f0f7ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .comp-code {
            font-weight: 800;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .comp-descripcion {
            font-size: 0.75rem;
            color: var(--gray-600);
            line-height: 1.3;
            margin-bottom: 0.5rem;
            flex-grow: 1;
        }

        .comp-progress {
            font-size: 0.7rem;
            color: var(--success);
            font-weight: 600;
            margin-top: auto;
        }

        /* Lista de Estudiantes */
        .estudiantes-lista {
            background: white;
            margin-bottom: 100px; /* Espacio para el panel de estado */
        }

        .estudiante-fila {
            display: grid;
            grid-template-columns: 250px repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            align-items: center;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-100);
            transition: all 0.15s ease;
            position: relative;
        }

        .estudiante-fila:hover {
            background: var(--gray-50);
        }

        .estudiante-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .estudiante-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .estudiante-datos {
            flex-grow: 1;
            overflow: hidden;
        }

        .estudiante-nombre {
            font-weight: 600;
            color: var(--gray-800);
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .estudiante-dni {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.125rem;
        }

        .estudiante-progreso {
            width: 100%;
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.375rem;
        }

        .progreso-fill {
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        /* Botones de Calificación Espaciados */
        .calificacion-cell {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0.5rem;
        }

        .cal-group {
            display: flex;
            gap: 4px;
            background: var(--gray-100);
            border-radius: 8px;
            padding: 4px;
        }

        .cal-btn {
            width: 28px;
            height: 28px;
            border: 2px solid transparent;
            background: white;
            color: var(--gray-600);
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            border-radius: 6px;
        }

        .cal-btn:hover {
            transform: scale(1.1);
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .cal-btn.active {
            color: white;
            transform: scale(1.05);
            border-color: transparent;
        }

        .cal-btn.cal-AD.active {
            background: linear-gradient(135deg, var(--success), #059669);
        }

        .cal-btn.cal-A.active {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
        }

        .cal-btn.cal-B.active {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }

        .cal-btn.cal-C.active {
            background: linear-gradient(135deg, var(--danger), #dc2626);
        }

        /* Panel de Estado en Footer */
        .estado-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 2px solid var(--gray-200);
            padding: 1rem;
            z-index: 998;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        .estado-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 2rem;
            align-items: center;
        }

        .estado-izquierda {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .estado-centro {
            text-align: center;
        }

        .estado-derecha {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            justify-content: flex-end;
        }

        .estado-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .estado-valor {
            font-size: 1.25rem;
            font-weight: 800;
        }

        .estado-label {
            font-size: 0.75rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .circular-progress {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(var(--success) 0deg, var(--success) var(--progress, 0deg), var(--gray-200) var(--progress, 0deg));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .circular-progress::before {
            content: '';
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            position: absolute;
        }

        .progress-text {
            position: relative;
            z-index: 1;
            font-weight: 800;
            font-size: 0.875rem;
            color: var(--gray-800);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .competencias-grid,
            .estudiante-fila {
                grid-template-columns: 200px repeat(auto-fit, minmax(120px, 1fr));
            }
            
            .estado-content {
                grid-template-columns: 1fr;
                gap: 1rem;
                text-align: center;
            }
            
            .estado-izquierda,
            .estado-derecha {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .competencias-grid,
            .estudiante-fila {
                grid-template-columns: 180px repeat(auto-fit, minmax(100px, 1fr));
            }
            
            .cal-btn {
                width: 24px;
                height: 24px;
                font-size: 0.7rem;
            }
            
            .comp-header {
                min-height: 100px;
                padding: 0.75rem;
            }
            
            .comp-code {
                font-size: 1rem;
            }
            
            .comp-descripcion {
                font-size: 0.7rem;
            }
        }

        /* Animaciones */
        @keyframes pulse-save {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); background: var(--success); }
            100% { transform: scale(1); }
        }

        .cal-btn.saving {
            animation: pulse-save 0.4s ease;
        }

        /* Tooltips personalizados */
        .tooltip-custom {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gray-800);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 1000;
            margin-bottom: 0.5rem;
        }

        .tooltip-custom::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid transparent;
            border-top-color: var(--gray-800);
        }

        .comp-header:hover .tooltip-custom {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Header Compacto -->
    <div class="header-compacto">
        <div class="header-info">
            <div class="d-flex align-items-center gap-3">
                <div class="area-badge">
                    <i class="bi bi-book me-1"></i>
                    <?php echo $area_actual['nombre']; ?>
                </div>
                <div class="context-info">
                    <span>
                        <i class="bi bi-mortarboard me-1"></i>
                        <?php echo $grado_actual['nivel_nombre'] . ' ' . $grado_actual['nombre'] . ' - ' . $grado_actual['seccion']; ?>
                    </span>
                    <span>
                        <i class="bi bi-calendar3 me-1"></i>
                        <?php echo $periodo_actual['nombre']; ?>
                    </span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="progress-global">
                    <span><?php echo count($estudiantes); ?> estudiantes</span>
                    <div class="progress-bar-mini">
                        <div class="progress-fill" style="width: <?php echo $porcentaje_completitud; ?>%"></div>
                    </div>
                    <span><?php echo $porcentaje_completitud; ?>%</span>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo $_SESSION['user_name']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small"><?php echo $_SESSION['user_rol']; ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($auth->hasPermission(['Administrador', 'Coordinadora'])): ?>
                            <li><a class="dropdown-item" href="admin/index.php">Panel Admin</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="logout.php">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Herramientas -->
    <div class="filtros-rapidos">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <select name="periodo_id" onchange="this.form.submit()">
                <?php foreach ($periodos as $periodo): ?>
                    <option value="<?php echo $periodo['id']; ?>" <?php echo $periodo_id == $periodo['id'] ? 'selected' : ''; ?>>
                        <?php echo $periodo['nombre'] . ' - ' . $periodo['año']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="grado_id" onchange="this.form.submit()">
                <?php foreach ($grados as $grado): ?>
                    <option value="<?php echo $grado['id']; ?>" <?php echo $grado_id == $grado['id'] ? 'selected' : ''; ?>>
                        <?php echo $grado['nivel_nombre'] . ' ' . $grado['nombre'] . ' - ' . $grado['seccion']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="area_id" onchange="this.form.submit()">
                <?php foreach ($areas as $area): ?>
                    <option value="<?php echo $area['id']; ?>" <?php echo $area_id == $area['id'] ? 'selected' : ''; ?>>
                        <?php echo $area['nombre']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="herramientas-grupo">
            <button class="btn-herramienta" onclick="validarTodo()" title="Validar calificaciones">
                <i class="bi bi-check-circle me-1"></i> Validar
            </button>
            <a href="export/excel.php?grado_id=<?php echo $grado_id; ?>&area_id=<?php echo $area_id; ?>&periodo_id=<?php echo $periodo_id; ?>" 
               class="btn-herramienta" title="Exportar Excel">
                <i class="bi bi-download me-1"></i> Exportar
            </a>
        </div>
    </div>

    <!-- Header de Competencias -->
    <div class="competencias-header">
        <div class="competencias-grid">
            <div class="comp-header" style="background: var(--gray-100); border-color: var(--gray-300);">
                <div class="comp-code" style="color: var(--gray-700);">
                    <i class="bi bi-people me-1"></i>
                    ESTUDIANTES
                </div>
            </div>
            <?php foreach ($competencias as $competencia): ?>
                <div class="comp-header">
                    <div class="comp-code"><?php echo $competencia['codigo']; ?></div>
                    <div class="comp-descripcion">
                        <?php echo substr($competencia['descripcion'], 0, 80) . (strlen($competencia['descripcion']) > 80 ? '...' : ''); ?>
                    </div>
                    <div class="comp-progress">
                        <?php 
                        $evaluados = array_filter($matriz, function($m) use ($competencia) { 
                            return $m['competencia_id'] == $competencia['id'] && !is_null($m['calificacion']); 
                        });
                        echo count($evaluados) . '/' . count($estudiantes) . ' evaluados';
                        ?>
                    </div>
                    <div class="tooltip-custom">
                        <?php echo $competencia['descripcion']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Lista de Estudiantes -->
    <div class="estudiantes-lista">
        <?php foreach ($estudiantes as $estudiante_id => $estudiante): ?>
            <div class="estudiante-fila" data-estudiante="<?php echo $estudiante_id; ?>">
                <div class="estudiante-info">
                    <div class="estudiante-avatar">
                        <?php echo strtoupper(substr($estudiante['info']['nombres'], 0, 1) . substr($estudiante['info']['apellidos'], 0, 1)); ?>
                    </div>
                    <div class="estudiante-datos">
                        <div class="estudiante-nombre">
                            <?php echo $estudiante['info']['apellidos'] . ', ' . $estudiante['info']['nombres']; ?>
                        </div>
                        <div class="estudiante-dni">DNI: <?php echo $estudiante['info']['dni']; ?></div>
                        <?php 
                        $evaluadas = count(array_filter($estudiante['calificaciones'], function($c) { return !is_null($c); }));
                        $porcentaje_est = count($competencias) > 0 ? round(($evaluadas / count($competencias)) * 100) : 0;
                        ?>
                        <div class="estudiante-progreso">
                            <div class="progreso-fill" style="width: <?php echo $porcentaje_est; ?>%; 
                                 background: <?php 
                                 if ($porcentaje_est >= 90) echo 'var(--success)';
                                 elseif ($porcentaje_est >= 70) echo 'var(--primary)';
                                 elseif ($porcentaje_est >= 50) echo 'var(--warning)';
                                 else echo 'var(--danger)';
                                 ?>;"></div>
                        </div>
                    </div>
                </div>
                
                <?php foreach ($competencias as $competencia): ?>
                    <div class="calificacion-cell">
                        <?php if ($puede_editar): ?>
                            <div class="cal-group">
                                <?php 
                                $calificacion_actual = $estudiante['calificaciones'][$competencia['id']] ?? null;
                                foreach (['AD', 'A', 'B', 'C'] as $calif): 
                                ?>
                                    <button type="button" 
                                            class="cal-btn cal-<?php echo $calif; ?> <?php echo $calificacion_actual == $calif ? 'active' : ''; ?>"
                                            data-estudiante="<?php echo $estudiante_id; ?>"
                                            data-competencia="<?php echo $competencia['id']; ?>"
                                            data-calificacion="<?php echo $calif; ?>"
                                            title="<?php echo $calif; ?>">
                                        <?php echo $calif; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <?php 
                                $calificacion_actual = $estudiante['calificaciones'][$competencia['id']] ?? null;
                                if ($calificacion_actual): 
                                ?>
                                    <span class="badge" style="background: <?php 
                                    $colores = [
                                        'AD' => 'var(--success)', 'A' => 'var(--primary)',
                                        'B' => 'var(--warning)', 'C' => 'var(--danger)'
                                    ];
                                    echo $colores[$calificacion_actual];
                                    ?>; color: white; font-size: 0.8rem; padding: 0.375rem 0.75rem;">
                                        <?php echo $calificacion_actual; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--gray-400); font-size: 0.875rem;">-</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Panel de Estado en Footer -->
    <div class="estado-footer">
        <div class="estado-content">
            <div class="estado-izquierda">
                <div class="estado-item">
                    <div class="estado-valor" style="color: var(--success);" id="evaluadas-count">
                        <?php echo $estadisticas['evaluaciones_realizadas']; ?>
                    </div>
                    <div class="estado-label">Evaluadas</div>
                </div>
                <div class="estado-item">
                    <div class="estado-valor" style="color: var(--warning);" id="pendientes-count">
                        <?php echo $estadisticas['total_evaluaciones_posibles'] - $estadisticas['evaluaciones_realizadas']; ?>
                    </div>
                    <div class="estado-label">Pendientes</div>
                </div>
            </div>
            
            <div class="estado-centro">
                <div class="circular-progress" style="--progress: <?php echo $porcentaje_completitud * 3.6; ?>deg;">
                    <span class="progress-text"><?php echo $porcentaje_completitud; ?>%</span>
                </div>
                <div class="estado-label">Completitud General</div>
            </div>
            
            <div class="estado-derecha">
                <div class="estado-item">
                    <div class="estado-valor" style="color: var(--success);" id="ad-count">
                        <?php echo $estadisticas['destacado']; ?>
                    </div>
                    <div class="estado-label">AD</div>
                </div>
                <div class="estado-item">
                    <div class="estado-valor" style="color: var(--primary);" id="a-count">
                        <?php echo $estadisticas['esperado']; ?>
                    </div>
                    <div class="estado-label">A</div>
                </div>
                <div class="estado-item">
                    <div class="estado-valor" style="color: var(--warning);" id="b-count">
                        <?php echo $estadisticas['proceso']; ?>
                    </div>
                    <div class="estado-label">B</div>
                </div>
                <div class="estado-item">
                    <div class="estado-valor" style="color: var(--danger);" id="c-count">
                        <?php echo $estadisticas['inicio']; ?>
                    </div>
                    <div class="estado-label">C</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Configuración global
        window.matrizConfig = {
            periodoId: <?php echo $periodo_id; ?>,
            gradoId: <?php echo $grado_id; ?>,
            areaId: <?php echo $area_id; ?>,
            canEdit: <?php echo $puede_editar ? 'true' : 'false'; ?>,
            competencias: <?php echo json_encode($competencias); ?>
        };

        // Función principal para guardar calificación
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
                    }, 200);
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }

                return result;
            } catch (error) {
                console.error('Error al guardar calificación:', error);
                mostrarError('Error al guardar: ' + error.message);
                throw error;
            }
        }

        function actualizarVisualizacion(estudianteId, competenciaId, nuevaCalificacion) {
            // Encontrar la fila del estudiante
            const fila = document.querySelector(`[data-estudiante="${estudianteId}"]`);
            if (!fila) return;

            // Encontrar el grupo de botones específico
            const botones = fila.querySelectorAll(`[data-competencia="${competenciaId}"]`);
            
            botones.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.calificacion === nuevaCalificacion) {
                    btn.classList.add('active');
                    btn.classList.add('saving');
                    setTimeout(() => btn.classList.remove('saving'), 400);
                }
            });

            // Actualizar progreso del estudiante
            actualizarProgresoEstudiante(estudianteId);
        }

        function actualizarProgresoEstudiante(estudianteId) {
            const fila = document.querySelector(`[data-estudiante="${estudianteId}"]`);
            if (!fila) return;

            const totalCompetencias = window.matrizConfig.competencias.length;
            const evaluadas = fila.querySelectorAll('.cal-btn.active').length;
            const porcentaje = Math.round((evaluadas / totalCompetencias) * 100);

            // Actualizar barra de progreso
            const progressBar = fila.querySelector('.progreso-fill');
            if (progressBar) {
                progressBar.style.width = porcentaje + '%';
                
                // Cambiar color según progreso
                let color;
                if (porcentaje >= 90) color = 'var(--success)';
                else if (porcentaje >= 70) color = 'var(--primary)';
                else if (porcentaje >= 50) color = 'var(--warning)';
                else color = 'var(--danger)';
                
                progressBar.style.background = color;
            }
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

            // Actualizar contadores en el panel
            const updateElement = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.style.transform = 'scale(1.1)';
                    element.textContent = value;
                    setTimeout(() => {
                        element.style.transform = '';
                    }, 200);
                }
            };

            updateElement('ad-count', stats.AD);
            updateElement('a-count', stats.A);
            updateElement('b-count', stats.B);
            updateElement('c-count', stats.C);
            updateElement('evaluadas-count', stats.total);

            // Actualizar pendientes
            const totalPosibles = document.querySelectorAll('.cal-group').length;
            const pendientes = totalPosibles - stats.total;
            updateElement('pendientes-count', pendientes);

            // Actualizar progreso general
            const porcentajeGeneral = totalPosibles > 0 ? Math.round((stats.total / totalPosibles) * 100) : 0;
            const progressCircle = document.querySelector('.circular-progress');
            const progressText = document.querySelector('.progress-text');
            const progressFill = document.querySelector('.progress-fill');
            
            if (progressCircle) {
                progressCircle.style.setProperty('--progress', (porcentajeGeneral * 3.6) + 'deg');
            }
            if (progressText) {
                progressText.textContent = porcentajeGeneral + '%';
            }
            if (progressFill) {
                progressFill.style.width = porcentajeGeneral + '%';
            }

            // Actualizar progreso por competencia
            actualizarProgresoCompetencias();
        }

        function actualizarProgresoCompetencias() {
            const competenciasHeaders = document.querySelectorAll('.comp-header');
            
            competenciasHeaders.forEach((header, index) => {
                if (index === 0) return; // Skip the "ESTUDIANTES" header
                
                const competencia = window.matrizConfig.competencias[index - 1];
                if (!competencia) return;
                
                const activeButtons = document.querySelectorAll(`[data-competencia="${competencia.id}"].active`);
                const totalStudents = document.querySelectorAll(`[data-competencia="${competencia.id}"]`).length / 4;
                const evaluados = activeButtons.length;
                
                const progressElement = header.querySelector('.comp-progress');
                if (progressElement) {
                    progressElement.textContent = `${evaluados}/${Math.round(totalStudents)} evaluados`;
                }
            });
        }

        function validarTodo() {
            const celdasVacias = document.querySelectorAll('.cal-group').length - document.querySelectorAll('.cal-btn.active').length;
            
            if (celdasVacias === 0) {
                mostrarNotificacion('✅ Todas las calificaciones están completas.', 'success');
            } else {
                mostrarNotificacion(`⚠️ Faltan ${celdasVacias} calificaciones por completar.`, 'warning');
            }
        }

        function mostrarFeedbackSutil() {
            const indicator = document.createElement('div');
            indicator.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: linear-gradient(135deg, var(--success), #059669);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 8px;
                font-size: 0.875rem;
                z-index: 9999;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            `;
            indicator.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Guardado';
            document.body.appendChild(indicator);
            
            setTimeout(() => {
                indicator.style.opacity = '1';
                indicator.style.transform = 'translateX(0)';
            }, 10);
            
            setTimeout(() => {
                indicator.style.opacity = '0';
                indicator.style.transform = 'translateX(100%)';
                setTimeout(() => indicator.remove(), 300);
            }, 1500);
        }

        function mostrarNotificacion(mensaje, tipo = 'info') {
            const colors = {
                success: 'var(--success)',
                warning: 'var(--warning)',
                error: 'var(--danger)',
                info: 'var(--primary)'
            };
            
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: ${colors[tipo]};
                color: white;
                padding: 1rem 1.25rem;
                border-radius: 8px;
                font-size: 0.875rem;
                z-index: 9999;
                max-width: 320px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;
            notification.textContent = mensaje;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 10);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        function mostrarError(mensaje) {
            mostrarNotificacion(mensaje, 'error');
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Actualizar estadísticas iniciales
            actualizarEstadisticas();

            // Animación de entrada para las filas
            const filas = document.querySelectorAll('.estudiante-fila');
            filas.forEach((fila, index) => {
                fila.style.opacity = '0';
                fila.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    fila.style.transition = 'all 0.3s ease';
                    fila.style.opacity = '1';
                    fila.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });

        // Clicks en botones de calificación
        document.addEventListener('click', async function(e) {
            if (e.target.classList.contains('cal-btn') && window.matrizConfig.canEdit) {
                e.preventDefault();
                
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

        // Loading en filtros
        document.addEventListener('change', function(e) {
            if (e.target.matches('select')) {
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(255,255,255,0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                `;
                overlay.innerHTML = `
                    <div style="text-align: center;">
                        <div style="width: 50px; height: 50px; border: 4px solid var(--gray-300); border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                        <div style="color: var(--gray-600); font-size: 1rem; font-weight: 500;">Actualizando matriz...</div>
                    </div>
                `;
                const style = document.createElement('style');
                style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
                document.head.appendChild(style);
                document.body.appendChild(overlay);
            }
        });

        console.log('✅ Matriz Mejorada cargada correctamente');
    </script>
</body>
</html>