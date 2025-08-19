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

// Validar que los IDs existen
$grado_valido = array_filter($grados, function($g) use ($grado_id) { return $g['id'] == $grado_id; });
$area_valida = array_filter($areas, function($a) use ($area_id) { return $a['id'] == $area_id; });
$periodo_valido = array_filter($periodos, function($p) use ($periodo_id) { return $p['id'] == $periodo_id; });

// Si no son válidos, usar los primeros disponibles
if (empty($grado_valido) && !empty($grados)) $grado_id = $grados[0]['id'];
if (empty($area_valida) && !empty($areas)) $area_id = $areas[0]['id'];
if (empty($periodo_valido) && !empty($periodos)) $periodo_id = $periodos[0]['id'];

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
    
    if ($row['competencia_id']) {
        $estudiantes[$estudiante_id]['calificaciones'][$row['competencia_id']] = $row['calificacion'];
    }
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

// Verificar permisos de edición
$puede_editar = $auth->hasPermission(['Administrador', 'Tutor del Aula', 'Docente Área', 'Docente Taller']);

// Datos adicionales para JavaScript
$user_data = [
    'id' => $_SESSION['user_id'] ?? 0,
    'name' => $_SESSION['user_name'] ?? 'Usuario',
    'email' => $_SESSION['user_email'] ?? '',
    'role' => $_SESSION['user_rol'] ?? 'Sin rol',
    'can_edit' => $puede_editar
];

// Manejar errores de datos faltantes
if (!$grado_actual) $grado_actual = ['id' => 0, 'nombre' => 'Sin grado', 'seccion' => '', 'nivel_nombre' => 'Sin nivel'];
if (!$area_actual) $area_actual = ['id' => 0, 'nombre' => 'Sin área'];
if (!$periodo_actual) $periodo_actual = ['id' => 0, 'nombre' => 'Sin período', 'año' => date('Y')];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matriz de Evaluación por Competencias - <?php echo htmlspecialchars($area_actual['nombre']); ?></title>
    
    <!-- Meta tags para SEO y redes sociales -->
    <meta name="description" content="Sistema de calificaciones por competencias - Matriz de evaluación para <?php echo htmlspecialchars($area_actual['nombre']); ?>">
    <meta name="keywords" content="calificaciones, competencias, evaluación, educación, matriz">
    <meta name="author" content="Sistema de Calificaciones por Competencias">
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Custom -->
    <link href="assets/css/main.css?v=<?php echo filemtime(__DIR__ . '/assets/css/main.css'); ?>" rel="stylesheet">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/js/main.js" as="script">
    <link rel="preload" href="ajax/guardar_calificacion.php" as="fetch" crossorigin>
</head>
<body>
    <!-- Header Principal -->
    <header class="header-principal">
        <div class="container">
            <div class="header-info">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="area-badge">
                        <i class="bi bi-book-fill me-2"></i>
                        <?php echo htmlspecialchars($area_actual['nombre']); ?>
                    </div>
                    <div class="context-info">
                        <div>
                            <i class="bi bi-mortarboard me-1"></i>
                            <?php echo htmlspecialchars($grado_actual['nivel_nombre'] . ' ' . $grado_actual['nombre'] . ' - ' . $grado_actual['seccion']); ?>
                        </div>
                        <div>
                            <i class="bi bi-calendar3 me-1"></i>
                            <?php echo htmlspecialchars($periodo_actual['nombre'] . ' ' . $periodo_actual['año']); ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div class="progress-global">
                        <span><?php echo count($estudiantes); ?> estudiantes</span>
                        <div class="progress-bar-header">
                            <div class="progress-fill" style="width: <?php echo $porcentaje_completitud; ?>%"></div>
                        </div>
                        <span class="fw-bold"><?php echo $porcentaje_completitud; ?>%</span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle fw-semibold" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text small fw-semibold text-primary">
                                    <i class="bi bi-shield-check me-1"></i>
                                    <?php echo htmlspecialchars($_SESSION['user_rol']); ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($auth->hasPermission(['Administrador', 'Coordinadora'])): ?>
                                <li>
                                    <a class="dropdown-item" href="admin/index.php">
                                        <i class="bi bi-speedometer2 me-2 text-primary"></i>Panel Admin
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="admin/reportes.php">
                                        <i class="bi bi-graph-up me-2 text-info"></i>Reportes
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <button class="dropdown-item" onclick="mostrarAyuda()">
                                    <i class="bi bi-question-circle me-2 text-warning"></i>Ayuda (F1)
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <section class="filtros-container">
        <div class="container">
            <form method="GET" class="filtros-grid" id="filtrosForm">
                <div class="filtro-grupo">
                    <label class="filtro-label">
                        <i class="bi bi-calendar3 me-1"></i>
                        Período Bimestre
                    </label>
                    <select name="periodo_id" class="filtro-select" onchange="cambiarFiltro()" data-tooltip="Selecciona el período académico">
                        <?php foreach ($periodos as $periodo): ?>
                            <option value="<?php echo $periodo['id']; ?>" <?php echo $periodo_id == $periodo['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($periodo['nombre'] . ' - ' . $periodo['año']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label class="filtro-label">
                        <i class="bi bi-mortarboard me-1"></i>
                        Grado y Sección
                    </label>
                    <select name="grado_id" class="filtro-select" onchange="cambiarFiltro()" data-tooltip="Selecciona el grado y sección">
                        <?php foreach ($grados as $grado): ?>
                            <option value="<?php echo $grado['id']; ?>" <?php echo $grado_id == $grado['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grado['nivel_nombre'] . ' ' . $grado['nombre'] . ' - ' . $grado['seccion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label class="filtro-label">
                        <i class="bi bi-book me-1"></i>
                        Área Curricular
                    </label>
                    <select name="area_id" class="filtro-select" onchange="cambiarFiltro()" data-tooltip="Selecciona el área curricular">
                        <?php foreach ($areas as $area): ?>
                            <option value="<?php echo $area['id']; ?>" <?php echo $area_id == $area['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($area['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-modern" style="background: var(--gradient-success); color: white;" onclick="validarTodo()" data-tooltip="Validar completitud de calificaciones">
                            <i class="bi bi-check-circle"></i>
                            Validar
                        </button>
                        <a href="export/excel.php?<?php echo http_build_query($_GET); ?>" class="btn btn-modern" style="background: var(--gradient-info); color: white;" target="_blank" data-tooltip="Exportar datos a Excel">
                            <i class="bi bi-download"></i>
                            Exportar
                        </a>
                        <?php if ($auth->hasPermission(['Administrador', 'Coordinadora'])): ?>
                            <button type="button" class="btn btn-modern" style="background: var(--gradient-warning); color: white;" onclick="window.location.href='admin/reportes.php'" data-tooltip="Ver reportes avanzados">
                                <i class="bi bi-graph-up"></i>
                                Reportes
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Matriz de Calificaciones -->
    <main class="container" role="main">
        <?php if (empty($estudiantes) && empty($competencias)): ?>
            <!-- Estado vacío -->
            <div class="modern-card text-center p-5 mt-4">
                <i class="bi bi-info-circle" style="font-size: 4rem; color: var(--info-color); margin-bottom: 2rem;"></i>
                <h3>No hay datos disponibles</h3>
                <p class="text-muted mb-4">
                    No se encontraron estudiantes o competencias para los filtros seleccionados.
                </p>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <?php if ($auth->hasPermission(['Administrador', 'Coordinadora'])): ?>
                        <a href="admin/estudiantes.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>
                            Agregar Estudiantes
                        </a>
                        <a href="admin/competencias.php" class="btn btn-secondary">
                            <i class="bi bi-list-check me-2"></i>
                            Gestionar Competencias
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-outline-primary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Refrescar
                    </button>
                </div>
            </div>
        <?php elseif (empty($estudiantes)): ?>
            <!-- Sin estudiantes -->
            <div class="modern-card text-center p-5 mt-4">
                <i class="bi bi-people" style="font-size: 4rem; color: var(--warning-color); margin-bottom: 2rem;"></i>
                <h3>No hay estudiantes registrados</h3>
                <p class="text-muted mb-4">
                    No se encontraron estudiantes para el grado <strong><?php echo htmlspecialchars($grado_actual['nivel_nombre'] . ' ' . $grado_actual['nombre'] . ' - ' . $grado_actual['seccion']); ?></strong>.
                </p>
                <?php if ($auth->hasPermission(['Administrador', 'Coordinadora'])): ?>
                    <a href="admin/estudiantes.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Agregar Estudiantes
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif (empty($competencias)): ?>
            <!-- Sin competencias -->
            <div class="modern-card text-center p-5 mt-4">
                <i class="bi bi-list-check" style="font-size: 4rem; color: var(--warning-color); margin-bottom: 2rem;"></i>
                <h3>No hay competencias configuradas</h3>
                <p class="text-muted mb-4">
                    No se encontraron competencias para el área <strong><?php echo htmlspecialchars($area_actual['nombre']); ?></strong>.
                </p>
                <?php if ($auth->hasPermission(['Administrador'])): ?>
                    <a href="admin/competencias.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Configurar Competencias
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Matriz de calificaciones -->
            <div class="matriz-container" id="matrizContainer">
                <div class="table-responsive">
                    <table class="matriz-table" role="table" aria-label="Matriz de calificaciones por competencias">
                        <thead>
                            <tr>
                                <th class="estudiante-header-cell" scope="col">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-people-fill me-2"></i>
                                        <div>
                                            <div class="fw-bold">Estudiantes</div>
                                            <div class="small opacity-75 mt-1">
                                                <?php echo count($estudiantes); ?> registrados
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <?php foreach ($competencias as $competencia): ?>
                                    <?php
                                    // Calcular progreso de la competencia
                                    $estudiantes_evaluados = 0;
                                    $total_estudiantes = count($estudiantes);
                                    foreach ($estudiantes as $est) {
                                        if (isset($est['calificaciones'][$competencia['id']]) && $est['calificaciones'][$competencia['id']]) {
                                            $estudiantes_evaluados++;
                                        }
                                    }
                                    $progreso_competencia = $total_estudiantes > 0 ? round(($estudiantes_evaluados / $total_estudiantes) * 100) : 0;
                                    ?>
                                    <th class="competencia-header-cell" scope="col" tabindex="0" 
                                        data-competencia="<?php echo $competencia['id']; ?>"
                                        aria-describedby="tooltip-competencia-<?php echo $competencia['id']; ?>">
                                        <div class="competencia-codigo"><?php echo htmlspecialchars($competencia['codigo']); ?></div>
                                        <div class="competencia-progreso">
                                            <?php echo $estudiantes_evaluados; ?>/<?php echo $total_estudiantes; ?> 
                                            (<?php echo $progreso_competencia; ?>%)
                                        </div>
                                        
                                        <div class="tooltip-competencia" id="tooltip-competencia-<?php echo $competencia['id']; ?>" role="tooltip">
                                            <strong><?php echo htmlspecialchars($competencia['codigo']); ?></strong><br>
                                            <?php echo htmlspecialchars(substr($competencia['descripcion'], 0, 200)); ?>
                                            <?php if (strlen($competencia['descripcion']) > 200): ?>...<?php endif; ?>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes as $estudiante_id => $estudiante): ?>
                                <?php
                                // Calcular progreso del estudiante
                                $competencias_evaluadas = 0;
                                foreach ($estudiante['calificaciones'] as $cal) {
                                    if ($cal) $competencias_evaluadas++;
                                }
                                $total_competencias = count($competencias);
                                $progreso_estudiante = $total_competencias > 0 ? round(($competencias_evaluadas / $total_competencias) * 100) : 0;
                                ?>
                                <tr class="estudiante-row" data-estudiante="<?php echo $estudiante_id; ?>" role="row">
                                    <td class="estudiante-info-cell" role="rowheader">
                                        <div class="estudiante-nombre">
                                            <?php echo htmlspecialchars($estudiante['info']['apellidos'] . ', ' . $estudiante['info']['nombres']); ?>
                                        </div>
                                        <div class="estudiante-dni">
                                            DNI: <?php echo htmlspecialchars($estudiante['info']['dni'] ?: 'No registrado'); ?>
                                        </div>
                                        <div class="progress-estudiante">
                                            <div class="progress-bar-estudiante">
                                                <div class="progress-fill-estudiante" style="width: <?php echo $progreso_estudiante; ?>%"></div>
                                            </div>
                                            <div class="progress-text-estudiante"><?php echo $progreso_estudiante; ?>%</div>
                                        </div>
                                    </td>
                                    <?php foreach ($competencias as $competencia): ?>
                                        <td class="calificacion-cell" role="gridcell">
                                            <div class="calificacion-group" role="group" 
                                                 aria-label="Calificaciones para <?php echo htmlspecialchars($competencia['codigo']); ?>">
                                                <?php 
                                                $calificacion_actual = $estudiante['calificaciones'][$competencia['id']] ?? null;
                                                $calificaciones_posibles = ['AD', 'A', 'B', 'C'];
                                                $calificaciones_desc = [
                                                    'AD' => 'Logro Destacado',
                                                    'A' => 'Logro Esperado', 
                                                    'B' => 'En Proceso',
                                                    'C' => 'En Inicio'
                                                ];
                                                ?>
                                                <?php foreach ($calificaciones_posibles as $calif): ?>
                                                    <button class="calificacion-btn <?php echo $calificacion_actual === $calif ? 'active' : ''; ?>"
                                                            type="button"
                                                            data-estudiante="<?php echo $estudiante_id; ?>"
                                                            data-competencia="<?php echo $competencia['id']; ?>"
                                                            data-calificacion="<?php echo $calif; ?>"
                                                            <?php echo !$puede_editar ? 'disabled' : ''; ?>
                                                            aria-label="<?php echo $calif . ' - ' . $calificaciones_desc[$calif]; ?>"
                                                            data-tooltip="<?php echo $calificaciones_desc[$calif]; ?>">
                                                        <?php echo $calif; ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Panel de Estado Flotante -->
        <aside class="estado-panel" role="complementary" aria-label="Panel de estadísticas">
            <div class="estado-content">
                <div class="estado-grupo">
                    <div class="estado-item">
                        <div class="estado-valor" style="color: var(--success-color);" id="evaluadas-count">
                            <?php echo $estadisticas['evaluaciones_realizadas']; ?>
                        </div>
                        <div class="estado-label">Evaluadas</div>
                    </div>
                    <div class="estado-item">
                        <div class="estado-valor" style="color: var(--warning-color);" id="pendientes-count">
                            <?php echo $estadisticas['total_evaluaciones_posibles'] - $estadisticas['evaluaciones_realizadas']; ?>
                        </div>
                        <div class="estado-label">Pendientes</div>
                    </div>
                </div>
                
                <div class="estado-item">
                    <div class="circular-progress" style="--progress: <?php echo $porcentaje_completitud * 3.6; ?>deg;">
                        <span class="progress-text"><?php echo $porcentaje_completitud; ?>%</span>
                    </div>
                    <div class="estado-label">Completitud General</div>
                </div>
                
                <div class="estado-grupo estado-derecha">
                    <div class="estado-item">
                        <div class="estado-valor" style="color: var(--success-color);" id="ad-count">
                            <?php echo $estadisticas['destacado']; ?>
                        </div>
                        <div class="estado-label">AD</div>
                    </div>
                    <div class="estado-item">
                        <div class="estado-valor" style="color: var(--primary-color);" id="a-count">
                            <?php echo $estadisticas['esperado']; ?>
                        </div>
                        <div class="estado-label">A</div>
                    </div>
                    <div class="estado-item">
                        <div class="estado-valor" style="color: var(--warning-color);" id="b-count">
                            <?php echo $estadisticas['proceso']; ?>
                        </div>
                        <div class="estado-label">B</div>
                    </div>
                    <div class="estado-item">
                        <div class="estado-valor" style="color: var(--danger-color);" id="c-count">
                            <?php echo $estadisticas['inicio']; ?>
                        </div>
                        <div class="estado-label">C</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Información adicional flotante -->
        <aside class="info-flotante" role="complementary" aria-label="Información del sistema">
            <div class="fw-bold mb-2">
                <i class="bi bi-info-circle me-1"></i>
                Leyenda de Calificaciones
            </div>
            <div><strong>AD:</strong> Logro Destacado</div>
            <div><strong>A:</strong> Logro Esperado</div>
            <div><strong>B:</strong> En Proceso</div>
            <div><strong>C:</strong> En Inicio</div>
            <hr class="my-2">
            <div class="small">
                <i class="bi bi-keyboard me-1"></i>
                Presiona F1 para ver atajos de teclado
            </div>
            <?php if ($puede_editar): ?>
                <div class="small">
                    <i class="bi bi-save me-1"></i>
                    Los cambios se guardan automáticamente
                </div>
            <?php else: ?>
                <div class="small text-warning">
                    <i class="bi bi-lock me-1"></i>
                    Solo lectura para tu rol
                </div>
            <?php endif; ?>
            <div class="small last-update">
                Actualizado: <span id="last-update-time"><?php echo date('H:i:s'); ?></span>
            </div>
        </aside>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="assets/js/main.js?v=<?php echo filemtime(__DIR__ . '/assets/js/main.js'); ?>"></script>
    
    <!-- Configuración de la aplicación -->
    <script>
        // Configuración global de la matriz
        window.matrizConfig = {
            periodoId: <?php echo json_encode((int)$periodo_id); ?>,
            gradoId: <?php echo json_encode((int)$grado_id); ?>,
            areaId: <?php echo json_encode((int)$area_id); ?>,
            canEdit: <?php echo json_encode($puede_editar); ?>,
            competencias: <?php echo json_encode($competencias); ?>,
            estudiantes: <?php echo json_encode(array_keys($estudiantes)); ?>,
            estadisticas: <?php echo json_encode($estadisticas); ?>,
            user: <?php echo json_encode($user_data); ?>,
            urls: {
                save: 'ajax/guardar_calificacion.php',
                export: 'export/excel.php',
                admin: 'admin/index.php'
            }
        };

        
                // Funciones de utilidad para filtros
        function cambiarFiltro() {
            if (SystemJS && SystemJS.Loading) {
                SystemJS.Loading.showMatriz('Actualizando matriz...');
            }
            
            // Guardar filtros en localStorage
            if (SystemJS && SystemJS.Storage) {
                const filters = {
                    periodo_id: document.querySelector('[name="periodo_id"]')?.value,
                    grado_id: document.querySelector('[name="grado_id"]')?.value,
                    area_id: document.querySelector('[name="area_id"]')?.value
                };
                SystemJS.Storage.set('lastFilters', filters);
            }
            
            document.getElementById('filtrosForm').submit();
        }

        // Clase principal para manejar la matriz de calificaciones
        class MatrizCalificaciones {
            constructor() {
                this.config = window.matrizConfig;
                this.pendingChanges = new Set();
                this.autoSaveEnabled = true;
                this.lastSaveTime = Date.now();
                this.saveQueue = [];
                this.isProcessingSave = false;
                
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.initializeUI();
                this.startPeriodicTasks();
                this.loadAutoSaveSettings();
                
                console.log('✅ Matriz de calificaciones inicializada');
                console.log('📊 Estadísticas iniciales:', this.config.estadisticas);
                
                // Mostrar notificación de carga
                if (SystemJS && SystemJS.Notifications) {
                    const message = this.config.canEdit ? 
                        'Matriz cargada - Lista para editar' : 
                        'Matriz cargada - Solo lectura';
                    SystemJS.Notifications.success(message, 3000);
                }
            }

            setupEventListeners() {
                // Event listener para botones de calificación
                document.addEventListener('click', this.handleCalificacionClick.bind(this));
                
                // Event listeners para atajos de teclado
                document.addEventListener('app:save', this.handleSave.bind(this));
                document.addEventListener('app:export', this.handleExport.bind(this));
                document.addEventListener('app:refresh', this.handleRefresh.bind(this));
                
                // Event listener para cambios de conectividad
                document.addEventListener('connection:lost', this.handleOffline.bind(this));
                document.addEventListener('connection:restored', this.handleOnline.bind(this));
                
                // Event listener para validación
                window.validarTodo = this.validarCompletitud.bind(this);
                
                // Event listeners para competencias (hover effects)
                document.querySelectorAll('.competencia-header-cell').forEach(header => {
                    const competenciaId = header.dataset.competencia;
                    if (competenciaId) {
                        header.addEventListener('mouseenter', () => {
                            this.highlightCompetency(competenciaId, true);
                        });
                        header.addEventListener('mouseleave', () => {
                            this.highlightCompetency(competenciaId, false);
                        });
                    }
                });
            }

            async handleCalificacionClick(e) {
                if (!e.target.classList.contains('calificacion-btn') || !this.config.canEdit) {
                    return;
                }

                e.preventDefault();
                
                const button = e.target;
                const estudianteId = parseInt(button.dataset.estudiante);
                const competenciaId = parseInt(button.dataset.competencia);
                const calificacion = button.dataset.calificacion;

                if (!estudianteId || !competenciaId || !calificacion) {
                    console.error('Datos incompletos para calificación');
                    return;
                }

                // Verificar si ya está procesando esta calificación
                const changeKey = `${estudianteId}-${competenciaId}`;
                if (this.pendingChanges.has(changeKey)) {
                    console.log('Ya se está procesando esta calificación');
                    return;
                }

                // Feedback visual inmediato
                this.showButtonFeedback(button);
                
                try {
                    // Marcar como cambio pendiente
                    this.pendingChanges.add(changeKey);
                    
                    // Actualizar UI inmediatamente (optimistic update)
                    this.updateButtonStatesOptimistic(estudianteId, competenciaId, calificacion);
                    
                    // Agregar a cola de guardado
                    this.addToSaveQueue({
                        estudiante_id: estudianteId,
                        competencia_id: competenciaId,
                        periodo_id: this.config.periodoId,
                        calificacion: calificacion
                    });
                    
                    // Procesar cola de guardado
                    this.processSaveQueue();
                    
                } catch (error) {
                    console.error('Error al procesar calificación:', error);
                    
                    if (SystemJS && SystemJS.Notifications) {
                        SystemJS.Notifications.error('Error al procesar la calificación: ' + error.message);
                    }
                    
                    // Revertir estado del botón
                    this.revertButtonState(button);
                    this.pendingChanges.delete(changeKey);
                }
            }

            showButtonFeedback(button) {
                // Agregar clase de feedback
                button.classList.add('saving');
                
                // Efecto visual inmediato
                const originalTransform = button.style.transform;
                button.style.transform = 'scale(0.9)';
                button.style.opacity = '0.7';
                
                setTimeout(() => {
                    button.style.transform = originalTransform;
                    button.style.opacity = '';
                }, 150);
            }

            updateButtonStatesOptimistic(estudianteId, competenciaId, nuevaCalificacion) {
                // Encontrar todos los botones de esta celda
                const buttons = document.querySelectorAll(
                    `[data-estudiante="${estudianteId}"][data-competencia="${competenciaId}"]`
                );
                
                buttons.forEach(btn => {
                    btn.classList.remove('active');
                    
                    if (btn.dataset.calificacion === nuevaCalificacion) {
                        btn.classList.add('active');
                    }
                });

                // Actualizar progreso del estudiante
                this.updateStudentProgressOptimistic(estudianteId);
                
                // Actualizar estadísticas generales
                this.updateGeneralStatsOptimistic();
            }

            addToSaveQueue(data) {
                // Remover duplicados de la cola
                this.saveQueue = this.saveQueue.filter(item => 
                    !(item.estudiante_id === data.estudiante_id && item.competencia_id === data.competencia_id)
                );
                
                // Agregar nuevo item
                this.saveQueue.push({
                    ...data,
                    timestamp: Date.now(),
                    retries: 0
                });
            }

            async processSaveQueue() {
                if (this.isProcessingSave || this.saveQueue.length === 0) {
                    return;
                }

                this.isProcessingSave = true;

                while (this.saveQueue.length > 0) {
                    const item = this.saveQueue.shift();
                    
                    try {
                        await this.saveCalificacion(item);
                        
                        // Marcar como guardado exitosamente
                        const changeKey = `${item.estudiante_id}-${item.competencia_id}`;
                        this.pendingChanges.delete(changeKey);
                        
                        // Mostrar confirmación visual
                        this.showSaveConfirmation(item.estudiante_id, item.competencia_id);
                        
                    } catch (error) {
                        console.error('Error guardando calificación:', error);
                        
                        // Reintentar si no ha alcanzado el límite
                        if (item.retries < 3) {
                            item.retries++;
                            this.saveQueue.push(item);
                            
                            // Esperar antes del siguiente intento
                            await new Promise(resolve => setTimeout(resolve, 1000 * item.retries));
                        } else {
                            // Falló después de varios intentos
                            const changeKey = `${item.estudiante_id}-${item.competencia_id}`;
                            this.pendingChanges.delete(changeKey);
                            
                            if (SystemJS && SystemJS.Notifications) {
                                SystemJS.Notifications.error(
                                    'Error guardando calificación después de varios intentos',
                                    5000
                                );
                            }
                        }
                    }
                }

                this.isProcessingSave = false;
            }

            async saveCalificacion(data) {
                // Validar datos
                const requiredFields = ['estudiante_id', 'competencia_id', 'periodo_id', 'calificacion'];
                for (const field of requiredFields) {
                    if (!data[field]) {
                        throw new Error(`Campo requerido: ${field}`);
                    }
                }

                if (SystemJS && SystemJS.API) {
                    return await SystemJS.API.guardarCalificacion(data);
                } else {
                    // Fallback si SystemJS no está disponible
                    const response = await fetch(this.config.urls.save, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(data)
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const result = await response.json();
                    
                    if (!result.success) {
                        throw new Error(result.message || 'Error desconocido');
                    }

                    return result;
                }
            }

            showSaveConfirmation(estudianteId, competenciaId) {
                const buttons = document.querySelectorAll(
                    `[data-estudiante="${estudianteId}"][data-competencia="${competenciaId}"].calificacion-btn.active`
                );
                
                buttons.forEach(btn => {
                    btn.classList.remove('saving');
                    btn.classList.add('success');
                    
                    // Crear indicador de guardado
                    const indicator = document.createElement('div');
                    indicator.className = 'save-indicator';
                    indicator.innerHTML = '<i class="bi bi-check"></i>';
                    
                    const container = btn.closest('.calificacion-cell');
                    if (container) {
                        container.style.position = 'relative';
                        container.appendChild(indicator);
                        
                        // Remover después de 2 segundos
                        setTimeout(() => {
                            if (indicator.parentNode) {
                                indicator.remove();
                            }
                            btn.classList.remove('success');
                        }, 2000);
                    }
                });
            }

            updateStudentProgressOptimistic(estudianteId) {
                const totalCompetencias = this.config.competencias.length;
                const activeButtons = document.querySelectorAll(`[data-estudiante="${estudianteId}"].calificacion-btn.active`);
                const evaluadas = activeButtons.length;
                const porcentaje = Math.round((evaluadas / totalCompetencias) * 100);

                // Actualizar barra de progreso del estudiante
                const row = document.querySelector(`[data-estudiante="${estudianteId}"]`);
                if (row) {
                    const progressFill = row.querySelector('.progress-fill-estudiante');
                    const progressText = row.querySelector('.progress-text-estudiante');
                    
                    if (progressFill) {
                        progressFill.style.width = porcentaje + '%';
                        
                        // Cambiar color según progreso
                        let color;
                        if (porcentaje >= 90) color = 'var(--success-color)';
                        else if (porcentaje >= 70) color = 'var(--primary-color)';
                        else if (porcentaje >= 50) color = 'var(--warning-color)';
                        else color = 'var(--danger-color)';
                        
                        progressFill.style.background = color;
                        
                        // Animación de actualización
                        if (SystemJS && SystemJS.Effects) {
                            SystemJS.Effects.pulse(progressFill);
                        }
                    }
                    
                    if (progressText) {
                        progressText.textContent = porcentaje + '%';
                    }
                }
            }

            updateGeneralStatsOptimistic() {
                // Contar calificaciones actuales
                const stats = { AD: 0, A: 0, B: 0, C: 0, total: 0 };
                
                document.querySelectorAll('.calificacion-btn.active').forEach(btn => {
                    const calif = btn.dataset.calificacion;
                    if (calif && stats.hasOwnProperty(calif)) {
                        stats[calif]++;
                        stats.total++;
                    }
                });

                // Actualizar contadores en el panel
                this.animateCounterUpdate('ad-count', stats.AD);
                this.animateCounterUpdate('a-count', stats.A);
                this.animateCounterUpdate('b-count', stats.B);
                this.animateCounterUpdate('c-count', stats.C);
                this.animateCounterUpdate('evaluadas-count', stats.total);

                // Actualizar pendientes
                const totalPosibles = this.config.estudiantes.length * this.config.competencias.length;
                const pendientes = totalPosibles - stats.total;
                this.animateCounterUpdate('pendientes-count', pendientes);

                // Actualizar progreso general
                const porcentajeGeneral = totalPosibles > 0 ? Math.round((stats.total / totalPosibles) * 100) : 0;
                this.updateCircularProgress(porcentajeGeneral);
                
                // Actualizar progreso de competencias
                this.updateCompetenciasProgress();
            }

            animateCounterUpdate(elementId, newValue) {
                const element = document.getElementById(elementId);
                if (!element) return;

                const currentValue = parseInt(element.textContent) || 0;
                
                if (currentValue !== newValue) {
                    // Animación de escala
                    element.style.transform = 'scale(1.15)';
                    element.style.transition = 'transform 0.3s ease';
                    
                    // Animar contador si SystemJS está disponible
                    if (SystemJS && SystemJS.Effects) {
                        SystemJS.Effects.animateCounter(element, newValue, 800);
                    } else {
                        element.textContent = newValue;
                    }
                    
                    setTimeout(() => {
                        element.style.transform = '';
                    }, 300);
                }
            }

            updateCircularProgress(porcentaje) {
                const progressCircle = document.querySelector('.circular-progress');
                const progressText = document.querySelector('.progress-text');
                const progressFill = document.querySelector('.progress-fill');
                
                if (progressCircle) {
                    progressCircle.style.setProperty('--progress', (porcentaje * 3.6) + 'deg');
                }
                if (progressText) {
                    progressText.textContent = porcentaje + '%';
                }
                if (progressFill) {
                    progressFill.style.width = porcentaje + '%';
                }
            }

            updateCompetenciasProgress() {
                this.config.competencias.forEach((competencia) => {
                    const activeButtons = document.querySelectorAll(`[data-competencia="${competencia.id}"].calificacion-btn.active`);
                    const totalEstudiantes = this.config.estudiantes.length;
                    const evaluados = activeButtons.length;
                    const porcentaje = totalEstudiantes > 0 ? Math.round((evaluados / totalEstudiantes) * 100) : 0;
                    
                    // Actualizar el texto de progreso en el header
                    const headerCell = document.querySelector(`[data-competencia="${competencia.id}"]`);
                    if (headerCell) {
                        const progressElement = headerCell.querySelector('.competencia-progreso');
                        if (progressElement) {
                            progressElement.textContent = `${evaluados}/${totalEstudiantes} (${porcentaje}%)`;
                        }
                    }
                });
            }

            highlightCompetency(competencyId, highlight = true) {
                // Resaltar celdas de calificación
                const cells = document.querySelectorAll(`[data-competencia="${competencyId}"]`);
                cells.forEach(cell => {
                    const parentCell = cell.closest('.calificacion-cell');
                    if (parentCell) {
                        if (highlight) {
                            parentCell.classList.add('competencia-activa');
                        } else {
                            parentCell.classList.remove('competencia-activa');
                        }
                    }
                });

                // Resaltar header de competencia
                const headerCell = document.querySelector(`.competencia-header-cell[data-competencia="${competencyId}"]`);
                if (headerCell) {
                    if (highlight) {
                        headerCell.classList.add('competencia-activa');
                    } else {
                        headerCell.classList.remove('competencia-activa');
                    }
                }
            }

            validarCompletitud() {
                const totalCells = this.config.estudiantes.length * this.config.competencias.length;
                const filledCells = document.querySelectorAll('.calificacion-btn.active').length;
                const porcentaje = totalCells > 0 ? Math.round((filledCells / totalCells) * 100) : 0;
                
                let message, type, options = {};
                
                if (porcentaje === 100) {
                    message = '🎉 ¡Excelente! Todas las calificaciones están completas.';
                    type = 'success';
                    options.sound = true;
                } else if (porcentaje >= 80) {
                    message = `✅ Muy bien. ${porcentaje}% completado. Faltan ${totalCells - filledCells} calificaciones.`;
                    type = 'success';
                } else if (porcentaje >= 50) {
                    message = `⚠️ Progreso moderado. ${porcentaje}% completado. Faltan ${totalCells - filledCells} calificaciones.`;
                    type = 'warning';
                } else {
                    message = `📋 Necesitas completar más evaluaciones. Solo ${porcentaje}% completado.`;
                    type = 'warning';
                }
                
                if (SystemJS && SystemJS.Notifications) {
                    SystemJS.Notifications.create(message, type, 5000, options);
                } else {
                    alert(message);
                }

                // Mostrar estadísticas adicionales
                const stats = this.getDetailedStats();
                console.log('📊 Estadísticas detalladas:', stats);
            }

            getDetailedStats() {
                const grades = { AD: 0, A: 0, B: 0, C: 0 };
                document.querySelectorAll('.calificacion-btn.active').forEach(btn => {
                    const grade = btn.dataset.calificacion;
                    if (grades.hasOwnProperty(grade)) {
                        grades[grade]++;
                    }
                });

                return {
                    totalEvaluaciones: grades.AD + grades.A + grades.B + grades.C,
                    porLogro: Math.round(((grades.AD + grades.A) / (grades.AD + grades.A + grades.B + grades.C)) * 100) || 0,
                    distribucion: grades,
                    estudiantesCompletos: this.getCompletedStudents(),
                    competenciasCompletas: this.getCompletedCompetencies()
                };
            }

            getCompletedStudents() {
                let completed = 0;
                this.config.estudiantes.forEach(estudianteId => {
                    const activeButtons = document.querySelectorAll(`[data-estudiante="${estudianteId}"].calificacion-btn.active`);
                    if (activeButtons.length === this.config.competencias.length) {
                        completed++;
                    }
                });
                return completed;
            }

            getCompletedCompetencies() {
                let completed = 0;
                this.config.competencias.forEach(competencia => {
                    const activeButtons = document.querySelectorAll(`[data-competencia="${competencia.id}"].calificacion-btn.active`);
                    if (activeButtons.length === this.config.estudiantes.length) {
                        completed++;
                    }
                });
                return completed;
            }

            handleSave() {
                if (this.pendingChanges.size > 0) {
                    if (SystemJS && SystemJS.Notifications) {
                        SystemJS.Notifications.info(`Guardando ${this.pendingChanges.size} cambios pendientes...`);
                    }
                    this.processSaveQueue();
                } else {
                    if (SystemJS && SystemJS.Notifications) {
                        SystemJS.Notifications.success('No hay cambios pendientes para guardar');
                    }
                }
            }

            handleExport() {
                const currentUrl = new URL(window.location);
                const exportUrl = `${this.config.urls.export}?${currentUrl.searchParams.toString()}`;
                
                if (SystemJS && SystemJS.Notifications) {
                    SystemJS.Notifications.info('Generando archivo de exportación...', 3000);
                }
                
                window.open(exportUrl, '_blank');
            }

            handleRefresh() {
                this.updateGeneralStatsOptimistic();
                this.updateAllStudentProgress();
                this.updateCompetenciasProgress();
                
                if (SystemJS && SystemJS.Notifications) {
                    SystemJS.Notifications.success('Matriz actualizada correctamente');
                }
            }

            handleOffline() {
                console.log('📴 Modo offline - las calificaciones se guardarán cuando se restaure la conexión');
                if (SystemJS && SystemJS.Notifications) {
                    SystemJS.Notifications.warning('Modo offline activado - los cambios se sincronizarán automáticamente', 0);
                }
            }

            handleOnline() {
                console.log('🌐 Conexión restaurada - sincronizando cambios pendientes');
                if (this.saveQueue.length > 0) {
                    this.processSaveQueue();
                }
            }

            updateAllStudentProgress() {
                this.config.estudiantes.forEach(estudianteId => {
                    this.updateStudentProgressOptimistic(estudianteId);
                });
            }

            loadAutoSaveSettings() {
                if (SystemJS && SystemJS.Storage) {
                    const autoSave = SystemJS.Storage.get('autoSave', true);
                    this.autoSaveEnabled = autoSave;
                }
            }

            hasUnsavedChanges() {
                return this.pendingChanges.size > 0 || this.saveQueue.length > 0;
            }

            initializeUI() {
                // Inicializar tooltips para competencias
                document.querySelectorAll('.competencia-header-cell').forEach(cell => {
                    if (cell.querySelector('.tooltip-competencia')) {
                        cell.setAttribute('tabindex', '0');
                    }
                });

                // Actualizar estadísticas iniciales
                this.updateGeneralStatsOptimistic();

                // Animación de entrada para las filas
                const filas = document.querySelectorAll('.estudiante-row');
                filas.forEach((fila, index) => {
                    fila.style.opacity = '0';
                    fila.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        fila.style.transition = 'all 0.3s ease';
                        fila.style.opacity = '1';
                        fila.style.transform = 'translateY(0)';
                    }, index * 50);
                });

                // Configurar accessibilidad
                this.setupAccessibility();
            }

            setupAccessibility() {
                // Agregar roles ARIA
                const table = document.querySelector('.matriz-table');
                if (table) {
                    table.setAttribute('role', 'grid');
                    table.setAttribute('aria-label', 'Matriz de calificaciones por competencias');
                }

                // Configurar navegación por teclado
                document.addEventListener('keydown', (e) => {
                    if (e.target.classList.contains('calificacion-btn')) {
                        this.handleKeyboardNavigation(e);
                    }
                });
            }

            handleKeyboardNavigation(e) {
                const button = e.target;
                
                switch (e.key) {
                    case 'ArrowRight':
                        e.preventDefault();
                        this.focusNextButton(button, 'horizontal', 1);
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.focusNextButton(button, 'horizontal', -1);
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        this.focusNextButton(button, 'vertical', 1);
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        this.focusNextButton(button, 'vertical', -1);
                        break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        button.click();
                        break;
                }
            }

            focusNextButton(currentButton, direction, offset) {
                const allButtons = Array.from(document.querySelectorAll('.calificacion-btn'));
                const currentIndex = allButtons.indexOf(currentButton);
                
                if (currentIndex === -1) return;

                let targetIndex;
                
                if (direction === 'horizontal') {
                    targetIndex = currentIndex + offset;
                } else if (direction === 'vertical') {
                    const buttonsPerRow = 4; // AD, A, B, C
                    const competenciasCount = this.config.competencias.length;
                    const currentRow = Math.floor(currentIndex / (competenciasCount * buttonsPerRow));
                    const currentCol = currentIndex % (competenciasCount * buttonsPerRow);
                    
                    targetIndex = (currentRow + offset) * (competenciasCount * buttonsPerRow) + currentCol;
                }

                if (targetIndex >= 0 && targetIndex < allButtons.length) {
                    allButtons[targetIndex].focus();
                }
            }

            startPeriodicTasks() {
                // Actualizar timestamp cada minuto
                setInterval(() => {
                    const timestampElement = document.getElementById('last-update-time');
                    if (timestampElement) {
                        timestampElement.textContent = new Date().toLocaleTimeString();
                    }
                }, 60000);

                // Auto-guardar cambios pendientes cada 30 segundos
                setInterval(() => {
                    if (this.autoSaveEnabled && this.saveQueue.length > 0 && !this.isProcessingSave) {
                        console.log('⏰ Auto-guardado de cambios pendientes...');
                        this.processSaveQueue();
                    }
                }, 30000);

                // Verificar conectividad cada 2 minutos
                setInterval(() => {
                    if (SystemJS && SystemJS.Connection) {
                        SystemJS.Connection.checkConnection();
                    }
                }, 120000);
            }

            revertButtonState(button) {
                button.classList.remove('active', 'saving', 'success');
                if (SystemJS && SystemJS.Effects) {
                    SystemJS.Effects.shake(button);
                }
            }

            // Métodos públicos para interacción externa
            getStats() {
                return this.getDetailedStats();
            }

            getCompleteness() {
                const totalCells = this.config.estudiantes.length * this.config.competencias.length;
                const filledCells = document.querySelectorAll('.calificacion-btn.active').length;
                const percentage = totalCells > 0 ? Math.round((filledCells / totalCells) * 100) : 0;
                
                return {
                    total: totalCells,
                    filled: filledCells,
                    pending: totalCells - filledCells,
                    percentage,
                    isComplete: percentage === 100
                };
            }

            exportData() {
                return SystemJS.MatrizUtils ? SystemJS.MatrizUtils.exportMatrixData() : [];
            }

            refresh() {
                this.handleRefresh();
            }

            saveAll() {
                this.handleSave();
            }

            export() {
                this.handleExport();
            }
        }

        // Inicializar la matriz cuando el DOM y SystemJS estén listos
        document.addEventListener('DOMContentLoaded', () => {
            const initMatriz = () => {
                if (typeof SystemJS !== 'undefined') {
                    window.matrizInstance = new MatrizCalificaciones();
                    
                    // Eventos adicionales del sistema
                    document.addEventListener('system:ready', () => {
                        console.log('🚀 Sistema completamente inicializado');
                        
                        // Cargar datos guardados si existen
                        if (SystemJS.Storage) {
                            const savedData = SystemJS.Storage.get('unsavedChanges');
                            if (savedData && savedData.length > 0) {
                                console.log('📄 Datos no guardados encontrados:', savedData.length);
                                SystemJS.Notifications.info(
                                    `Se encontraron ${savedData.length} cambios no guardados. Se restaurarán automáticamente.`,
                                    5000
                                );
                            }
                        }
                    });
                } else {
                    setTimeout(initMatriz, 100);
                }
            };
            
            initMatriz();
        });

        // Advertir antes de salir si hay cambios pendientes
        window.addEventListener('beforeunload', (e) => {
            if (window.matrizInstance && window.matrizInstance.hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
                return e.returnValue;
            }
        });

        // Manejar errores específicos de la matriz
        window.addEventListener('error', (e) => {
            if (e.filename && e.filename.includes('matriz')) {
                console.error('Error en matriz de calificaciones:', e.error);
                
                if (SystemJS && SystemJS.Notifications) {
                    SystemJS.Notifications.error(
                        'Error en la matriz de calificaciones. La página se recargará automáticamente.',
                        5000
                    );
                    
                    setTimeout(() => {
                        location.reload();
                    }, 5000);
                }
            }
        });

        // Performance monitoring específico
        if (window.performance && window.performance.mark) {
            performance.mark('matriz-script-start');
            
            window.addEventListener('load', () => {
                performance.mark('matriz-script-end');
                performance.measure('matriz-script-duration', 'matriz-script-start', 'matriz-script-end');
                
                const measure = performance.getEntriesByName('matriz-script-duration')[0];
                if (measure && measure.duration > 1000) {
                    console.warn('⚠️ Carga lenta de matriz detectada:', measure.duration + 'ms');
                }
            });
        }

        console.log('✅ Matriz de calificaciones por competencias lista para usar');
        console.log('📚 Configuración cargada:', window.matrizConfig);
    </script>

    <!-- Schema.org structured data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Sistema de Calificaciones por Competencias",
        "applicationCategory": "EducationalApplication",
        "operatingSystem": "Web",
        "description": "Sistema de evaluación por competencias para instituciones educativas",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "PEN"
        },
        "creator": {
            "@type": "Organization",
            "name": "Sistema de Calificaciones por Competencias"
        }
    }
    </script>

    <!-- Preload next likely pages -->
    <link rel="prefetch" href="admin/index.php">
    <link rel="prefetch" href="export/excel.php">
    
    <!-- Service Worker registration -->
    <script>
        if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('✅ Service Worker registrado:', registration.scope);
                    })
                    .catch(error => {
                        console.log('❌ Error registrando Service Worker:', error);
                    });
            });
        }
    </script>

    <!-- Analytics placeholder (if needed) -->
    <!-- 
    <script>
        // Google Analytics or other analytics code here
        // gtag('config', 'GA_MEASUREMENT_ID');
    </script>
    -->
</body>
</html>