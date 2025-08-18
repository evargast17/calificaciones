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
    <title>Matriz de Evaluación por Competencias - <?php echo $area_actual['nombre']; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Custom -->
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
    <!-- Header Principal -->
    <div class="header-principal">
        <div class="container">
            <div class="header-info">
                <div class="d-flex align-items-center gap-3">
                    <div class="area-badge">
                        <i class="bi bi-book-fill me-2"></i>
                        <?php echo $area_actual['nombre']; ?>
                    </div>
                    <div class="context-info">
                        <div>
                            <i class="bi bi-mortarboard me-1"></i>
                            <?php echo $grado_actual['nivel_nombre'] . ' ' . $grado_actual['nombre'] . ' - ' . $grado_actual['seccion']; ?>
                        </div>
                        <div>
                            <i class="bi bi-calendar3 me-1"></i>
                            <?php echo $periodo_actual['nombre']; ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-4">
                    <div class="progress-global">
                        <span><?php echo count($estudiantes); ?> estudiantes</span>
                        <div class="progress-bar-header">
                            <div class="progress-fill" style="width: <?php echo $porcentaje_completitud; ?>%"></div>
                        </div>
                        <span class="fw-bold"><?php echo $porcentaje_completitud; ?>%</span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle fw-semibold" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo $_SESSION['user_name']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small fw-semibold text-primary"><?php echo $_SESSION['user_rol']; ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($auth->hasPermission(['Administrador', 'Coordinadora'])): ?>
                                <li><a class="dropdown-item" href="admin/index.php">
                                    <i class="bi bi-speedometer2 me-2"></i>Panel Admin
                                </a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-container">
        <div class="container">
            <form method="GET" class="filtros-grid">
                <div class="filtro-grupo">
                    <label class="filtro-label">
                        <i class="bi bi-calendar3 me-1"></i>
                        Período Bimestre
                    </label>
                    <select name="periodo_id" class="filtro-select" onchange="this.form.submit()">
                        <?php foreach ($periodos as $periodo): ?>
                            <option value="<?php echo $periodo['id']; ?>" <?php echo $periodo_id == $periodo['id'] ? 'selected' : ''; ?>>
                                <?php echo $periodo['nombre'] . ' - ' . $periodo['año']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label class="filtro-label">
                        <i class="bi bi-mortarboard me-1"></i>
                        Grado y Sección
                    </label>
                    <select name="grado_id" class="filtro-select" onchange="this.form.submit()">
                        <?php foreach ($grados as $grado): ?>
                            <option value="<?php echo $grado['id']; ?>" <?php echo $grado_id == $grado['id'] ? 'selected' : ''; ?>>
                                <?php echo $grado['nivel_nombre'] . ' ' . $grado['nombre'] . ' - ' . $grado['seccion']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label class="filtro-label">
                        <i class="bi bi-book me-1"></i>
                        Área Curricular
                    </label>
                    <select name="area_id" class="filtro-select" onchange="this.form.submit()">
                        <?php foreach ($areas as $area): ?>
                            <option value="<?php echo $area['id']; ?>" <?php echo $area_id == $area['id'] ? 'selected' : ''; ?>>
                                <?php echo $area['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-modern" style="background: var(--gradient-success); color: white;" onclick="validarTodo()">
                            <i class="bi bi-check-circle"></i>
                            Validar
                        </button>
                        <a href="export/excel.php?<?php echo http_build_query($_GET); ?>" class="btn btn-modern" style="background: var(--gradient-info); color: white;" target="_blank">
                            <i class="bi bi-download"></i>
                            Exportar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Matriz de Calificaciones -->
    <div class="container">
        <div class="matriz-container">
            <table class="matriz-table">
                <thead>
                    <tr>
                        <th class="estudiante-header-cell">
                            <i class="bi bi-people-fill me-2"></i>
                            Estudiantes
                            <div class="small opacity-75 mt-1">
                                <?php echo count($estudiantes); ?> registrados
                            </div>
                        </th>
                        <?php foreach ($competencias as $competencia): ?>
                            <?php
                            // Calcular progreso de la competencia
                            $estudiantes_evaluados = 0;
                            $total_estudiantes = count($estudiantes);
                            foreach ($estudiantes as $est) {
                                if (isset($est['calificaciones'][$competencia['id']])) {
                                    $estudiantes_evaluados++;
                                }
                            }
                            $progreso_competencia = $total_estudiantes > 0 ? round(($estudiantes_evaluados / $total_estudiantes) * 100) : 0;
                            ?>
                            <th class="competencia-header-cell" tabindex="0">
                                <div class="competencia-codigo"><?php echo $competencia['codigo']; ?></div>
                                <div class="competencia-progreso"><?php echo $estudiantes_evaluados; ?>/<?php echo $total_estudiantes; ?> (<?php echo $progreso_competencia; ?>%)</div>
                                
                                <div class="tooltip-competencia">
                                    <strong><?php echo $competencia['codigo']; ?></strong><br>
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
                        $competencias_evaluadas = count($estudiante['calificaciones']);
                        $total_competencias = count($competencias);
                        $progreso_estudiante = $total_competencias > 0 ? round(($competencias_evaluadas / $total_competencias) * 100) : 0;
                        ?>
                        <tr class="estudiante-row" data-estudiante="<?php echo $estudiante_id; ?>">
                            <td class="estudiante-info-cell">
                                <div class="estudiante-nombre">
                                    <?php echo $estudiante['info']['apellidos'] . ', ' . $estudiante['info']['nombres']; ?>
                                </div>
                                <div class="estudiante-dni">
                                    DNI: <?php echo $estudiante['info']['dni'] ?: 'No registrado'; ?>
                                </div>
                                <div class="progress-estudiante">
                                    <div class="progress-bar-estudiante">
                                        <div class="progress-fill-estudiante" style="width: <?php echo $progreso_estudiante; ?>%"></div>
                                    </div>
                                    <div class="progress-text-estudiante"><?php echo $progreso_estudiante; ?>%</div>
                                </div>
                            </td>
                            <?php foreach ($competencias as $competencia): ?>
                                <td class="calificacion-cell">
                                    <div class="calificacion-group">
                                        <?php 
                                        $calificacion_actual = $estudiante['calificaciones'][$competencia['id']] ?? null;
                                        $calificaciones_posibles = ['AD', 'A', 'B', 'C'];
                                        ?>
                                        <?php foreach ($calificaciones_posibles as $calif): ?>
                                            <button class="calificacion-btn <?php echo $calificacion_actual === $calif ? 'active' : ''; ?>"
                                                    data-estudiante="<?php echo $estudiante_id; ?>"
                                                    data-competencia="<?php echo $competencia['id']; ?>"
                                                    data-calificacion="<?php echo $calif; ?>"
                                                    <?php echo !$puede_editar ? 'disabled' : ''; ?>
                                                    data-tooltip="<?php echo $calif === 'AD' ? 'Logro Destacado' : ($calif === 'A' ? 'Logro Esperado' : ($calif === 'B' ? 'En Proceso' : 'En Inicio')); ?>">
                                                <?php echo $calif; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($estudiantes)): ?>
                        <tr>
                            <td colspan="<?php echo count($competencias) + 1; ?>" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-info-circle" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3">No hay estudiantes registrados</h5>
                                    <p>No se encontraron estudiantes para el grado seleccionado.</p>
                                    <?php if ($auth->hasPermission(['Administrador', 'Coordinadora'])): ?>
                                        <a href="admin/estudiantes.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>
                                            Agregar Estudiantes
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panel de Estado Flotante -->
    <div class="estado-panel">
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
    </div>

    <!-- Información adicional flotante -->
    <div style="position: fixed; bottom: 20px; left: 20px; background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); font-size: 0.8rem; color: #6b7280; max-width: 250px;">
        <div class="fw-bold mb-2">Leyenda de Calificaciones</div>
        <div><strong>AD:</strong> Logro Destacado</div>
        <div><strong>A:</strong> Logro Esperado</div>
        <div><strong>B:</strong> En Proceso</div>
        <div><strong>C:</strong> En Inicio</div>
        <hr class="my-2">
        <div class="small">
            <i class="bi bi-keyboard me-1"></i>
            Presiona F1 para ver atajos de teclado
        </div>
        <div class="small last-update">
            Actualizado: <?php echo date('H:i:s'); ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        // Configuración global de la matriz
        window.matrizConfig = {
            periodoId: <?php echo $periodo_id; ?>,
            gradoId: <?php echo $grado_id; ?>,
            areaId: <?php echo $area_id; ?>,
            canEdit: <?php echo $puede_editar ? 'true' : 'false'; ?>,
            competencias: <?php echo json_encode($competencias); ?>,
            estudiantes: <?php echo json_encode(array_keys($estudiantes)); ?>
        };

        // Clase principal para manejar la matriz de calificaciones
        class MatrizCalificaciones {
            constructor() {
                this.config = window.matrizConfig;
                this.pendingChanges = new Set();
                this.autoSaveEnabled = true;
                this.lastSaveTime = Date.now();
                
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.initializeUI();
                this.startPeriodicTasks();
                
                console.log('✅ Matriz de calificaciones inicializada');
                
                // Mostrar notificación de carga
                if (SystemJS && SystemJS.Notifications) {
                    SystemJS.Notifications.success('Matriz cargada correctamente', 3000);
                }
            }

            setupEventListeners() {
                // Event listener para botones de calificación
                document.addEventListener('click', this.handleCalificacionClick.bind(this));
                
                // Event listeners para atajos de teclado
                document.addEventListener('app:save', this.handleSave.bind(this));
                document.addEventListener('app:export', this.handleExport.bind(this));
                
                // Event listener para cambios de filtros
                document.addEventListener('change', this.handleFilterChange.bind(this));
                
                // Event listener para validación
                window.validarTodo = this.validarCompletitud.bind(this);
            }

            async handleCalificacionClick(e) {
                if (!e.target.classList.contains('calificacion-btn') || !this.config.canEdit) {
                    return;
                }

                e.preventDefault();
                
                const button = e.target;
                const estudianteId = button.dataset.estudiante;
                const competenciaId = button.dataset.competencia;
                const calificacion = button.dataset.calificacion;

                if (!estudianteId || !competenciaId || !calificacion) {
                    console.error('Datos incompletos para calificación');
                    return;
                }

                // Feedback visual inmediato
                this.showButtonFeedback(button);
                
                try {
                    // Marcar como cambio pendiente
                    const changeKey = `${estudianteId}-${competenciaId}`;
                    this.pendingChanges.add(changeKey);
                    
                    // Guardar calificación
                    await this.saveCalificacion(estudianteId, competenciaId, calificacion);
                    
                    // Actualizar UI
                    this.updateButtonStates(estudianteId, competenciaId, calificacion);
                    this.updateStudentProgress(estudianteId);
                    this.updateGeneralStats();
                    
                    // Mostrar confirmación
                    this.showSaveConfirmation(button);
                    
                    // Remover de cambios pendientes
                    this.pendingChanges.delete(changeKey);
                    
                } catch (error) {
                    console.error('Error al guardar calificación:', error);
                    
                    if (SystemJS && SystemJS.Notifications) {
                        SystemJS.Notifications.error('Error al guardar la calificación: ' + error.message);
                    }
                    
                    // Revertir estado del botón
                    this.revertButtonState(button);
                    this.pendingChanges.delete(changeKey);
                }
            }

            showButtonFeedback(button) {
                button.style.transform = 'scale(0.9)';
                button.style.opacity = '0.7';
                
                setTimeout(() => {
                    button.style.transform = '';
                    button.style.opacity = '';
                }, 150);
            }

            async saveCalificacion(estudianteId, competenciaId, calificacion) {
                const data = {
                    estudiante_id: parseInt(estudianteId),
                    competencia_id: parseInt(competenciaId),
                    periodo_id: this.config.periodoId,
                    calificacion: calificacion
                };

                if (SystemJS && SystemJS.API) {
                    return await SystemJS.API.guardarCalificacion(data);
                } else {
                    // Fallback si SystemJS no está disponible
                    const response = await fetch('ajax/guardar_calificacion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
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

            updateButtonStates(estudianteId, competenciaId, nuevaCalificacion) {
                // Encontrar todos los botones de esta celda
                const buttons = document.querySelectorAll(
                    `[data-estudiante="${estudianteId}"][data-competencia="${competenciaId}"]`
                );
                
                buttons.forEach(btn => {
                    btn.classList.remove('active');
                    
                    if (btn.dataset.calificacion === nuevaCalificacion) {
                        btn.classList.add('active');
                        btn.classList.add('saving');
                        
                        setTimeout(() => {
                            btn.classList.remove('saving');
                        }, 500);
                    }
                });
            }

            updateStudentProgress(estudianteId) {
                const totalCompetencias = this.config.competencias.length;
                const activeButtons = document.querySelectorAll(`[data-estudiante="${estudianteId}"].calificacion-btn.active`);
                const evaluadas = activeButtons.length;
                const porcentaje = Math.round((evaluadas / totalCompetencias) * 100);

                // Actualizar barra de progreso del estudiante
                const progressFill = document.querySelector(`[data-estudiante="${estudianteId}"] .progress-fill-estudiante`);
                const progressText = document.querySelector(`[data-estudiante="${estudianteId}"] .progress-text-estudiante`);
                
                if (progressFill) {
                    progressFill.style.width = porcentaje + '%';
                    
                    // Cambiar color según progreso
                    let color;
                    if (porcentaje >= 90) color = 'var(--success-color)';
                    else if (porcentaje >= 70) color = 'var(--primary-color)';
                    else if (porcentaje >= 50) color = 'var(--warning-color)';
                    else color = 'var(--danger-color)';
                    
                    progressFill.style.backgroundColor = color;
                    
                    // Animación de actualización
                    if (SystemJS && SystemJS.Effects) {
                        SystemJS.Effects.pulse(progressFill);
                    }
                }
                
                if (progressText) {
                    progressText.textContent = porcentaje + '%';
                }
            }

            updateGeneralStats() {
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
                const totalPosibles = document.querySelectorAll('.calificacion-group').length;
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

                // Animación de escala
                element.style.transform = 'scale(1.15)';
                element.textContent = newValue;
                
                setTimeout(() => {
                    element.style.transform = '';
                }, 300);
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
                this.config.competencias.forEach((competencia, index) => {
                    const activeButtons = document.querySelectorAll(`[data-competencia="${competencia.id}"].calificacion-btn.active`);
                    const totalButtons = document.querySelectorAll(`[data-competencia="${competencia.id}"]`).length / 4; // 4 botones por estudiante
                    const evaluados = activeButtons.length;
                    const porcentaje = totalButtons > 0 ? Math.round((evaluados / totalButtons) * 100) : 0;
                    
                    // Actualizar el texto de progreso en el header
                    const progressElement = document.querySelectorAll('.competencia-progreso')[index];
                    if (progressElement) {
                        progressElement.textContent = `${evaluados}/${Math.round(totalButtons)} (${porcentaje}%)`;
                    }
                });
            }

            showSaveConfirmation(button) {
                // Crear indicador de guardado
                const indicator = document.createElement('div');
                indicator.className = 'save-indicator';
                indicator.innerHTML = '<i class="bi bi-check"></i>';
                
                const buttonRect = button.getBoundingClientRect();
                const container = button.closest('.calificacion-cell');
                
                if (container) {
                    container.style.position = 'relative';
                    container.appendChild(indicator);
                    
                    // Remover después de 2 segundos
                    setTimeout(() => {
                        if (indicator.parentNode) {
                            indicator.remove();
                        }
                    }, 2000);
                }
            }

            revertButtonState(button) {
                button.classList.remove('active', 'saving');
                if (SystemJS && SystemJS.Effects) {
                    SystemJS.Effects.shake(button);
                }
            }

            validarCompletitud() {
                const totalCells = document.querySelectorAll('.calificacion-group').length;
                const filledCells = document.querySelectorAll('.calificacion-btn.active').length;
                const porcentaje = totalCells > 0 ? Math.round((filledCells / totalCells) * 100) : 0;
                
                let message, type;
                if (porcentaje === 100) {
                    message = '✅ ¡Excelente! Todas las calificaciones están completas.';
                    type = 'success';
                } else if (porcentaje >= 80) {
                    message = `✅ Muy bien. ${porcentaje}% completado. Faltan ${totalCells - filledCells} calificaciones.`;
                    type = 'success';
                } else if (porcentaje >= 50) {
                    message = `⚠️ Progreso moderado. ${porcentaje}% completado. Faltan ${totalCells - filledCells} calificaciones.`;
                    type = 'warning';
                } else {
                    message = `❌ Necesitas completar más evaluaciones. Solo ${porcentaje}% completado.`;
                    type = 'warning';
                }
                
                if (SystemJS && SystemJS.Notifications) {
                    SystemJS.Notifications.create(message, type, 5000);
                } else {
                    alert(message);
                }
            }

            handleSave() {
                if (this.pendingChanges.size > 0) {
                    if (SystemJS && SystemJS.Notifications) {
                        SystemJS.Notifications.info(`Guardando ${this.pendingChanges.size} cambios pendientes...`);
                    }
                } else {
                    if (SystemJS && SystemJS.Notifications) {
                        SystemJS.Notifications.success('No hay cambios pendientes para guardar');
                    }
                }
            }

            handleExport() {
                const currentUrl = new URL(window.location);
                const exportUrl = `export/excel.php?${currentUrl.searchParams.toString()}`;
                
                if (SystemJS && SystemJS.Notifications) {
                    SystemJS.Notifications.info('Generando archivo de exportación...', 3000);
                }
                
                window.open(exportUrl, '_blank');
            }

            handleFilterChange(e) {
                if (e.target.matches('.filtro-select')) {
                    // Mostrar loading
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
                }
            }

            initializeUI() {
                // Inicializar tooltips para competencias
                document.querySelectorAll('.competencia-header-cell').forEach(cell => {
                    if (cell.querySelector('.tooltip-competencia')) {
                        cell.setAttribute('tabindex', '0');
                    }
                });

                // Actualizar estadísticas iniciales
                this.updateGeneralStats();

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
            }

            startPeriodicTasks() {
                // Actualizar timestamp cada minuto
                setInterval(() => {
                    const timestamp = document.querySelector('.last-update');
                    if (timestamp) {
                        timestamp.textContent = `Actualizado: ${new Date().toLocaleTimeString()}`;
                    }
                }, 60000);

                // Auto-guardar cambios pendientes cada 30 segundos
                setInterval(() => {
                    if (this.autoSaveEnabled && this.pendingChanges.size > 0) {
                        console.log('⏰ Verificando cambios pendientes...');
                    }
                }, 30000);
            }

            // Métodos públicos para interacción externa
            getStats() {
                return SystemJS.MatrizUtils.countGradesByType();
            }

            getCompleteness() {
                return SystemJS.MatrizUtils.validateMatrixCompleteness();
            }

            exportData() {
                return SystemJS.MatrizUtils.exportMatrixData();
            }

            refresh() {
                this.updateGeneralStats();
                if (SystemJS && SystemJS.Notifications) {
                    SystemJS.Notifications.success('Matriz actualizada');
                }
            }
        }

        // Inicializar la matriz cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', () => {
            // Esperar a que SystemJS esté disponible
            const initMatriz = () => {
                if (typeof SystemJS !== 'undefined') {
                    window.matrizInstance = new MatrizCalificaciones();
                } else {
                    setTimeout(initMatriz, 100);
                }
            };
            
            initMatriz();
        });

        // Advertir antes de salir si hay cambios pendientes
        window.addEventListener('beforeunload', (e) => {
            if (window.matrizInstance && window.matrizInstance.pendingChanges.size > 0) {
                e.preventDefault();
                e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
                return e.returnValue;
            }
        });

        console.log('✅ Matriz de calificaciones por competencias lista para usar');
    </script>
</body>
</html>