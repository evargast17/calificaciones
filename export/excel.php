<?php
// Iniciar sesión si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Reportes.php';
require_once __DIR__ . '/../classes/Calificaciones.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$reportes = new Reportes();
$calificaciones = new Calificaciones();

// Obtener parámetros
$grado_id = $_GET['grado_id'] ?? 1;
$area_id = $_GET['area_id'] ?? 1;
$periodo_id = $_GET['periodo_id'] ?? 1;

// Obtener datos
$datos = $reportes->getReporteMatriz($grado_id, $area_id, $periodo_id);
$grado = $calificaciones->getGrados();
$area = $calificaciones->getAreas();
$periodo = $calificaciones->getPeriodos();

// Filtrar datos específicos
$grado_info = array_filter($grado, function($g) use ($grado_id) { return $g['id'] == $grado_id; });
$grado_info = reset($grado_info);
$area_info = array_filter($area, function($a) use ($area_id) { return $a['id'] == $area_id; });
$area_info = reset($area_info);
$periodo_info = array_filter($periodo, function($p) use ($periodo_id) { return $p['id'] == $periodo_id; });
$periodo_info = reset($periodo_info);

// Configurar headers para descarga
$filename = sprintf(
    'matriz_calificaciones_%s_%s_%s_%s.csv',
    str_replace(' ', '_', $grado_info['nivel_nombre']),
    str_replace(' ', '_', $grado_info['nombre']),
    str_replace(' ', '_', $area_info['nombre']),
    str_replace(' ', '_', $periodo_info['nombre'])
);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Crear el output handle
$output = fopen('php://output', 'w');

// BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers del CSV
fputcsv($output, [
    'Estudiante',
    'DNI',
    'Grado',
    'Sección',
    'Nivel',
    'Área',
    'Período',
    'Competencia',
    'Código',
    'Calificación',
    'Fecha Evaluación',
    'Observaciones',
    'Docente'
], ',');

// Datos
foreach ($datos as $fila) {
    fputcsv($output, [
        $fila['apellidos'] . ', ' . $fila['nombres'],
        $fila['dni'] ?? '',
        $fila['grado'],
        $fila['seccion'] ?? '',
        $fila['nivel'],
        $fila['area'],
        $fila['periodo'],
        $fila['competencia'] ?? '',
        $fila['codigo'] ?? '',
        $fila['calificacion'] ?? 'No evaluado',
        $fila['fecha_evaluacion'] ?? '',
        $fila['observaciones'] ?? '',
        $fila['docente'] ?? 'No asignado'
    ], ',');
}

fclose($output);
exit;
?>