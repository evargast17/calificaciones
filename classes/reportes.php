<?php
require_once __DIR__ . '/../config/database.php';

class Reportes {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getReporteMatriz($grado_id, $area_id, $periodo_id) {
        $query = "SELECT 
                    e.nombres,
                    e.apellidos,
                    e.dni,
                    g.nombre as grado,
                    g.seccion,
                    n.nombre as nivel,
                    ac.nombre as area,
                    p.nombre as periodo,
                    comp.codigo,
                    comp.descripcion as competencia,
                    cal.calificacion,
                    cal.fecha_evaluacion,
                    cal.observaciones,
                    CONCAT(u.nombre, ' ', u.apellidos) as docente
                  FROM estudiantes e
                  INNER JOIN grados g ON e.grado_id = g.id
                  INNER JOIN niveles n ON g.nivel_id = n.id
                  CROSS JOIN competencias comp
                  INNER JOIN areas_curriculares ac ON comp.area_curricular_id = ac.id
                  INNER JOIN periodos p ON p.id = ?
                  LEFT JOIN calificaciones cal ON e.id = cal.estudiante_id 
                    AND comp.id = cal.competencia_id 
                    AND cal.periodo_id = p.id
                  LEFT JOIN usuarios u ON cal.docente_id = u.id
                  WHERE e.grado_id = ? AND ac.id = ? AND e.activo = 1
                  ORDER BY e.apellidos, e.nombres, comp.codigo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$periodo_id, $grado_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRendimientoDocente($docente_id, $periodo_id = null) {
        $where_periodo = $periodo_id ? "AND cal.periodo_id = ?" : "";
        $params = [$docente_id];
        if ($periodo_id) $params[] = $periodo_id;
        
        $query = "SELECT 
                    COUNT(*) as total_evaluaciones,
                    SUM(CASE WHEN cal.calificacion = 'AD' THEN 1 ELSE 0 END) as destacado,
                    SUM(CASE WHEN cal.calificacion = 'A' THEN 1 ELSE 0 END) as esperado,
                    SUM(CASE WHEN cal.calificacion = 'B' THEN 1 ELSE 0 END) as proceso,
                    SUM(CASE WHEN cal.calificacion = 'C' THEN 1 ELSE 0 END) as inicio,
                    ac.nombre as area,
                    g.nombre as grado,
                    g.seccion,
                    p.nombre as periodo
                  FROM calificaciones cal
                  INNER JOIN competencias comp ON cal.competencia_id = comp.id
                  INNER JOIN areas_curriculares ac ON comp.area_curricular_id = ac.id
                  INNER JOIN estudiantes e ON cal.estudiante_id = e.id
                  INNER JOIN grados g ON e.grado_id = g.id
                  INNER JOIN periodos p ON cal.periodo_id = p.id
                  WHERE cal.docente_id = ? $where_periodo
                  GROUP BY ac.id, g.id, p.id
                  ORDER BY p.numero, ac.nombre, g.nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function exportarExcel($datos, $tipo = 'matriz') {
        // Esta función requeriría una librería como PhpSpreadsheet
        // Por simplicidad, retornamos el formato para CSV
        return $this->exportarCSV($datos, $tipo);
    }
    
    public function exportarCSV($datos, $tipo = 'matriz') {
        $csv = '';
        
        if ($tipo === 'matriz' && !empty($datos)) {
            // Headers CSV para matriz
            $csv = "Estudiante,DNI,Grado,Área,Período,Competencia,Código,Calificación,Fecha Evaluación,Observaciones,Docente\n";
            
            foreach ($datos as $fila) {
                $csv .= sprintf('"%s","%s","%s %s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                    $fila['apellidos'] . ', ' . $fila['nombres'],
                    $fila['dni'] ?? '',
                    $fila['nivel'] . ' ' . $fila['grado'],
                    $fila['seccion'] ?? '',
                    $fila['area'],
                    $fila['periodo'],
                    substr($fila['competencia'], 0, 50),
                    $fila['codigo'],
                    $fila['calificacion'] ?? 'No evaluado',
                    $fila['fecha_evaluacion'] ?? '',
                    $fila['observaciones'] ?? '',
                    $fila['docente'] ?? ''
                );
            }
        }
        
        return $csv;
    }
    
    public function getResumenGeneral($periodo_id = null) {
        $where_periodo = $periodo_id ? "WHERE p.id = ?" : "";
        $params = $periodo_id ? [$periodo_id] : [];
        
        $query = "SELECT 
                    p.nombre as periodo,
                    n.nombre as nivel,
                    COUNT(DISTINCT e.id) as total_estudiantes,
                    COUNT(DISTINCT cal.id) as total_evaluaciones,
                    SUM(CASE WHEN cal.calificacion = 'AD' THEN 1 ELSE 0 END) as destacado,
                    SUM(CASE WHEN cal.calificacion = 'A' THEN 1 ELSE 0 END) as esperado,
                    SUM(CASE WHEN cal.calificacion = 'B' THEN 1 ELSE 0 END) as proceso,
                    SUM(CASE WHEN cal.calificacion = 'C' THEN 1 ELSE 0 END) as inicio
                  FROM periodos p
                  CROSS JOIN niveles n
                  LEFT JOIN grados g ON n.id = g.nivel_id
                  LEFT JOIN estudiantes e ON g.id = e.grado_id AND e.activo = 1
                  LEFT JOIN calificaciones cal ON e.id = cal.estudiante_id AND cal.periodo_id = p.id
                  $where_periodo
                  GROUP BY p.id, n.id
                  ORDER BY p.numero, n.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProgresoCompetencias($area_id, $periodo_id) {
        $query = "SELECT 
                    comp.codigo,
                    comp.descripcion,
                    COUNT(*) as total_estudiantes,
                    SUM(CASE WHEN cal.calificacion = 'AD' THEN 1 ELSE 0 END) as destacado,
                    SUM(CASE WHEN cal.calificacion = 'A' THEN 1 ELSE 0 END) as esperado,
                    SUM(CASE WHEN cal.calificacion = 'B' THEN 1 ELSE 0 END) as proceso,
                    SUM(CASE WHEN cal.calificacion = 'C' THEN 1 ELSE 0 END) as inicio,
                    SUM(CASE WHEN cal.calificacion IS NOT NULL THEN 1 ELSE 0 END) as evaluados
                  FROM competencias comp
                  CROSS JOIN estudiantes e
                  INNER JOIN grados g ON e.grado_id = g.id
                  LEFT JOIN calificaciones cal ON comp.id = cal.competencia_id 
                    AND e.id = cal.estudiante_id 
                    AND cal.periodo_id = ?
                  WHERE comp.area_curricular_id = ? AND e.activo = 1
                  GROUP BY comp.id
                  ORDER BY comp.codigo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$periodo_id, $area_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getEstadisticasDocente($docente_id, $periodo_id = null) {
        $where_periodo = $periodo_id ? "AND cal.periodo_id = ?" : "";
        $params = [$docente_id];
        if ($periodo_id) $params[] = $periodo_id;
        
        $query = "SELECT 
                    ac.nombre as area,
                    g.nombre as grado,
                    g.seccion,
                    COUNT(cal.id) as total_calificaciones,
                    SUM(CASE WHEN cal.calificacion = 'AD' THEN 1 ELSE 0 END) as destacado,
                    SUM(CASE WHEN cal.calificacion = 'A' THEN 1 ELSE 0 END) as esperado,
                    SUM(CASE WHEN cal.calificacion = 'B' THEN 1 ELSE 0 END) as proceso,
                    SUM(CASE WHEN cal.calificacion = 'C' THEN 1 ELSE 0 END) as inicio,
                    ROUND(AVG(CASE 
                        WHEN cal.calificacion = 'AD' THEN 4
                        WHEN cal.calificacion = 'A' THEN 3
                        WHEN cal.calificacion = 'B' THEN 2
                        WHEN cal.calificacion = 'C' THEN 1
                        ELSE 0
                    END), 2) as promedio_numerico
                  FROM calificaciones cal
                  INNER JOIN competencias comp ON cal.competencia_id = comp.id
                  INNER JOIN areas_curriculares ac ON comp.area_curricular_id = ac.id
                  INNER JOIN estudiantes e ON cal.estudiante_id = e.id
                  INNER JOIN grados g ON e.grado_id = g.id
                  WHERE cal.docente_id = ? $where_periodo
                  GROUP BY ac.id, g.id
                  ORDER BY ac.nombre, g.nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getComparativoGrados($area_id, $periodo_id) {
        $query = "SELECT 
                    CONCAT(n.nombre, ' ', g.nombre, ' - ', g.seccion) as grado_completo,
                    COUNT(DISTINCT e.id) as total_estudiantes,
                    COUNT(cal.id) as total_evaluaciones,
                    SUM(CASE WHEN cal.calificacion = 'AD' THEN 1 ELSE 0 END) as destacado,
                    SUM(CASE WHEN cal.calificacion = 'A' THEN 1 ELSE 0 END) as esperado,
                    SUM(CASE WHEN cal.calificacion = 'B' THEN 1 ELSE 0 END) as proceso,
                    SUM(CASE WHEN cal.calificacion = 'C' THEN 1 ELSE 0 END) as inicio,
                    ROUND((SUM(CASE WHEN cal.calificacion IN ('AD', 'A') THEN 1 ELSE 0 END) / 
                           NULLIF(COUNT(cal.id), 0)) * 100, 1) as porcentaje_satisfactorio
                  FROM grados g
                  INNER JOIN niveles n ON g.nivel_id = n.id
                  LEFT JOIN estudiantes e ON g.id = e.grado_id AND e.activo = 1
                  LEFT JOIN calificaciones cal ON e.id = cal.estudiante_id AND cal.periodo_id = ?
                  LEFT JOIN competencias comp ON cal.competencia_id = comp.id AND comp.area_curricular_id = ?
                  WHERE g.activo = 1
                  GROUP BY g.id
                  ORDER BY n.id, g.nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$periodo_id, $area_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getReporteDetallado($filtros = []) {
        $where_conditions = ["e.activo = 1"];
        $params = [];
        
        if (!empty($filtros['grado_id'])) {
            $where_conditions[] = "e.grado_id = ?";
            $params[] = $filtros['grado_id'];
        }
        
        if (!empty($filtros['area_id'])) {
            $where_conditions[] = "comp.area_curricular_id = ?";
            $params[] = $filtros['area_id'];
        }
        
        if (!empty($filtros['periodo_id'])) {
            $where_conditions[] = "cal.periodo_id = ?";
            $params[] = $filtros['periodo_id'];
        }
        
        if (!empty($filtros['docente_id'])) {
            $where_conditions[] = "cal.docente_id = ?";
            $params[] = $filtros['docente_id'];
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $query = "SELECT 
                    CONCAT(e.apellidos, ', ', e.nombres) as estudiante_completo,
                    e.dni,
                    CONCAT(n.nombre, ' ', g.nombre, ' - ', g.seccion) as grado_completo,
                    ac.nombre as area,
                    comp.codigo,
                    comp.descripcion as competencia,
                    cal.calificacion,
                    cal.observaciones,
                    cal.fecha_evaluacion,
                    p.nombre as periodo,
                    CONCAT(u.nombre, ' ', u.apellidos) as docente,
                    r.nombre as rol_docente
                  FROM estudiantes e
                  INNER JOIN grados g ON e.grado_id = g.id
                  INNER JOIN niveles n ON g.nivel_id = n.id
                  LEFT JOIN calificaciones cal ON e.id = cal.estudiante_id
                  LEFT JOIN competencias comp ON cal.competencia_id = comp.id
                  LEFT JOIN areas_curriculares ac ON comp.area_curricular_id = ac.id
                  LEFT JOIN periodos p ON cal.periodo_id = p.id
                  LEFT JOIN usuarios u ON cal.docente_id = u.id
                  LEFT JOIN roles r ON u.rol_id = r.id
                  WHERE $where_clause
                  ORDER BY e.apellidos, e.nombres, ac.nombre, comp.codigo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>