<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Reportes.php';
require_once __DIR__ . '/../classes/Calificaciones.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (!$auth->hasPermission(['Administrador', 'Coordinadora'])) {
    header('Location: ../matriz_calificaciones.php');
    exit;
}

$reportes = new Reportes();
$calificaciones = new Calificaciones();

$grados = $calificaciones->getGrados();
$areas = $calificaciones->getAreas();
$periodos = $calificaciones->getPeriodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Avanzados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 2rem 0;
        }
        .reporte-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .reporte-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-light">
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-0">📊 Reportes Avanzados</h1>
                    <p class="mb-0">Generar reportes y estadísticas detalladas</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-light me-2">← Volver al Panel</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <!-- Reporte por Matriz -->
            <div class="col-md-6 mb-4">
                <div class="card reporte-card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Reporte de Matriz</h5>
                    </div>
                    <div class="card-body">
                        <p>Exportar calificaciones por grado, área y período</p>
                        <form action="../export/excel.php" method="GET" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Período</label>
                                <select name="periodo_id" class="form-select" required>
                                    <?php foreach ($periodos as $periodo): ?>
                                        <option value="<?php echo $periodo['id']; ?>">
                                            <?php echo $periodo['nombre'] . ' - ' . $periodo['año']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Grado</label>
                                <select name="grado_id" class="form-select" required>
                                    <?php foreach ($grados as $grado): ?>
                                        <option value="<?php echo $grado['id']; ?>">
                                            <?php echo $grado['nivel_nombre'] . ' ' . $grado['nombre'] . ' - ' . $grado['seccion']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Área</label>
                                <select name="area_id" class="form-select" required>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?php echo $area['id']; ?>">
                                            <?php echo $area['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-download"></i> Exportar Excel
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Generales -->
            <div class="col-md-6 mb-4">
                <div class="card reporte-card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Estadísticas Generales</h5>
                    </div>
                    <div class="card-body">
                        <p>Ver estadísticas completas del sistema</p>
                        <?php 
                        $resumen = $reportes->getResumenGeneral();
                        $total_estudiantes = 0;
                        $total_evaluaciones = 0;
                        $total_destacado = 0;
                        $total_esperado = 0;
                        
                        foreach ($resumen as $nivel) {
                            $total_estudiantes += $nivel['total_estudiantes'];
                            $total_evaluaciones += $nivel['total_evaluaciones'];
                            $total_destacado += $nivel['destacado'];
                            $total_esperado += $nivel['esperado'];
                        }
                        ?>
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <h4 class="text-primary"><?php echo $total_estudiantes; ?></h4>
                                <small>Estudiantes</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success"><?php echo $total_evaluaciones; ?></h4>
                                <small>Evaluaciones</small>
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col-6">
                                <h5 class="text-warning"><?php echo $total_destacado; ?></h5>
                                <small>AD (Destacado)</small>
                            </div>
                            <div class="col-6">
                                <h5 class="text-info"><?php echo $total_esperado; ?></h5>
                                <small>A (Esperado)</small>
                            </div>
                        </div>
                        <hr>
                        <button class="btn btn-info w-100" onclick="window.print()">
                            <i class="bi bi-printer"></i> Imprimir Estadísticas
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reporte por Competencias -->
            <div class="col-md-6 mb-4">
                <div class="card reporte-card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-list-check"></i> Progreso por Competencias</h5>
                    </div>
                    <div class="card-body">
                        <p>Analizar el progreso por competencias específicas</p>
                        <form id="formCompetencias">
                            <div class="mb-3">
                                <label class="form-label">Área Curricular</label>
                                <select name="area_id" class="form-select" required>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?php echo $area['id']; ?>">
                                            <?php echo $area['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Período</label>
                                <select name="periodo_id" class="form-select" required>
                                    <?php foreach ($periodos as $periodo): ?>
                                        <option value="<?php echo $periodo['id']; ?>">
                                            <?php echo $periodo['nombre'] . ' - ' . $periodo['año']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="btn btn-warning w-100" onclick="verCompetencias()">
                                <i class="bi bi-search"></i> Ver Progreso
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Acceso Rápido -->
            <div class="col-md-6 mb-4">
                <div class="card reporte-card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Acceso Rápido</h5>
                    </div>
                    <div class="card-body">
                        <p>Herramientas de acceso rápido y utilidades</p>
                        <div class="d-grid gap-2">
                            <a href="../matriz_calificaciones.php" class="btn btn-outline-primary">
                                <i class="bi bi-grid-3x3-gap"></i> Ir a Matriz
                            </a>
                            <a href="estudiantes.php" class="btn btn-outline-info">
                                <i class="bi bi-people"></i> Gestionar Estudiantes
                            </a>
                            <button class="btn btn-outline-success" onclick="exportarTodo()">
                                <i class="bi bi-download"></i> Exportar Todo
                            </button>
                            <button class="btn btn-outline-warning" onclick="limpiarCache()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar Datos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen Detallado -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">📈 Resumen Detallado por Nivel</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nivel</th>
                                        <th>Estudiantes</th>
                                        <th>Evaluaciones</th>
                                        <th>AD</th>
                                        <th>A</th>
                                        <th>B</th>
                                        <th>C</th>
                                        <th>% Logro Satisfactorio</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumen as $nivel): ?>
                                        <?php 
                                        $total_calif = $nivel['destacado'] + $nivel['esperado'] + $nivel['proceso'] + $nivel['inicio'];
                                        $porcentaje_logro = $total_calif > 0 ? round((($nivel['destacado'] + $nivel['esperado']) / $total_calif) * 100, 1) : 0;
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
                                                <div class="progress" style="height: 20px; width: 100px;">
                                                    <div class="progress-bar <?php echo $porcentaje_logro >= 80 ? 'bg-success' : ($porcentaje_logro >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                         style="width: <?php echo $porcentaje_logro; ?>%">
                                                        <?php echo $porcentaje_logro; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="verDetalleNivel('<?php echo $nivel['nivel']; ?>')">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verCompetencias() {
            const form = document.getElementById('formCompetencias');
            const formData = new FormData(form);
            const areaId = formData.get('area_id');
            const periodoId = formData.get('periodo_id');
            
            // Abrir en nueva ventana con parámetros
            window.open(`../matriz_calificaciones.php?area_id=${areaId}&periodo_id=${periodoId}`, '_blank');
        }

        function exportarTodo() {
            if (confirm('¿Desea exportar todas las calificaciones del sistema? Esto puede tomar unos minutos.')) {
                // Implementar exportación completa
                alert('Funcionalidad en desarrollo. Use los reportes individuales por ahora.');
            }
        }

        function limpiarCache() {
            alert('Datos actualizados correctamente.');
            location.reload();
        }

        function verDetalleNivel(nivel) {
            alert(`Mostrando detalles para ${nivel}.\nEsta funcionalidad estará disponible en la próxima versión.`);
        }

        // Función para imprimir
        function imprimirReporte() {
            window.print();
        }

        // Auto-actualizar cada 5 minutos
        setInterval(function() {
            const now = new Date();
            document.getElementById('ultima-actualizacion').textContent = 
                `Última actualización: ${now.toLocaleTimeString()}`;
        }, 300000);
    </script>

    <style>
        @media print {
            .btn, .card-header { display: none !important; }
            .card { border: 1px solid #000 !important; }
            body { font-size: 12px; }
        }
    </style>
</body>
</html>