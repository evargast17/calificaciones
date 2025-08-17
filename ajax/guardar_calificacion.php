<?php
header('Content-Type: application/json');

// Iniciar sesión si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Calificaciones.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar permisos
if (!$auth->hasPermission(['Administrador', 'Tutor del Aula', 'Docente Área', 'Docente Taller'])) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos para editar']);
    exit;
}

// Leer datos JSON del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$estudiante_id = $input['estudiante_id'] ?? null;
$competencia_id = $input['competencia_id'] ?? null;
$periodo_id = $input['periodo_id'] ?? null;
$calificacion = $input['calificacion'] ?? null;
$observaciones = $input['observaciones'] ?? '';

// Validar datos requeridos
if (!$estudiante_id || !$competencia_id || !$periodo_id || !$calificacion) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

// Validar calificación
if (!in_array($calificacion, ['AD', 'A', 'B', 'C'])) {
    echo json_encode(['success' => false, 'message' => 'Calificación inválida']);
    exit;
}

try {
    $calificaciones = new Calificaciones();
    $result = $calificaciones->saveCalificacion(
        $estudiante_id, 
        $competencia_id, 
        $periodo_id, 
        $calificacion, 
        $_SESSION['user_id'], 
        $observaciones
    );
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Calificación guardada correctamente',
            'data' => [
                'estudiante_id' => $estudiante_id,
                'competencia_id' => $competencia_id,
                'calificacion' => $calificacion,
                'fecha' => date('Y-m-d')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>