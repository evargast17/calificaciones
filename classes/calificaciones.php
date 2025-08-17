<?php
require_once __DIR__ . '/../config/database.php';

class Calificaciones {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getMatrizCalificaciones($grado_id, $area_id, $periodo_id) {
        $query = "SELECT 
                    e.id as estudiante_id,
                    e.nombres,
                    e.apellidos,
                    e.dni,
                    comp.id as competencia_id,
                    comp.codigo,
                    comp.descripcion as competencia_desc,
                    cal.calificacion,
                    cal.fecha_evaluacion,
                    cal.observaciones
                  FROM estudiantes e
                  CROSS JOIN competencias comp
                  LEFT JOIN calificaciones cal ON e.id = cal.estudiante_id 
                    AND comp.id = cal.competencia_id 
                    AND cal.periodo_id = ?
                  WHERE e.grado_id = ? 
                    AND comp.area_curricular_id = ?
                    AND e.activo = 1
                  ORDER BY e.apellidos, e.nombres, comp.codigo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $periodo_id);
        $stmt->bindParam(2, $grado_id);
        $stmt->bindParam(3, $area_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function saveCalificacion($estudiante_id, $competencia_id, $periodo_id, $calificacion, $docente_id, $observaciones = '') {
        $query = "INSERT INTO calificaciones 
                    (estudiante_id, competencia_id, periodo_id, calificacion, docente_id, observaciones, fecha_evaluacion)
                  VALUES (?, ?, ?, ?, ?, ?, CURDATE())
                  ON DUPLICATE KEY UPDATE 
                    calificacion = VALUES(calificacion),
                    observaciones = VALUES(observaciones),
                    fecha_evaluacion = CURDATE(),
                    updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$estudiante_id, $competencia_id, $periodo_id, $calificacion, $docente_id, $observaciones]);
    }
    
    public function getEstadisticasEstudiante($estudiante_id, $periodo_id, $area_id) {
        $query = "SELECT 
                    COUNT(*) as total_competencias,
                    SUM(CASE WHEN cal.calificacion = 'AD' THEN 1 ELSE 0 END) as destacado,
                    SUM(CASE WHEN cal.calificacion = 'A' THEN 1 ELSE 0 END) as esperado,
                    SUM(CASE WHEN cal.calificacion = 'B' THEN 1 ELSE 0 END) as proceso,
                    SUM(CASE WHEN cal.calificacion = 'C' THEN 1 ELSE 0 END) as inicio,
                    SUM(CASE WHEN cal.calificacion IS NOT NULL THEN 1 ELSE 0 END) as evaluadas
                  FROM competencias comp
                  LEFT JOIN calificaciones cal ON comp.id = cal.competencia_id 
                    AND cal.estudiante_id = ? 
                    AND cal.periodo_id = ?
                  WHERE comp.area_curricular_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$estudiante_id, $periodo_id, $area_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getEstadisticasGenerales($grado_id, $area_id, $periodo_id) {
        $query = "SELECT 
                    COUNT(DISTINCT e.id) as total_estudiantes,
                    COUNT(DISTINCT comp.id) as total_competencias,
                    COUNT(*) as total_evaluaciones_posibles,
                    SUM(CASE WHEN cal.calificacion IS NOT NULL THEN 1 ELSE 0 END) as evaluaciones_realizadas,
                    SUM(CASE WHEN cal.calificacion = 'AD' THEN 1 ELSE 0 END) as destacado,
                    SUM(CASE WHEN cal.calificacion = 'A' THEN 1 ELSE 0 END) as esperado,
                    SUM(CASE WHEN cal.calificacion = 'B' THEN 1 ELSE 0 END) as proceso,
                    SUM(CASE WHEN cal.calificacion = 'C' THEN 1 ELSE 0 END) as inicio
                  FROM estudiantes e
                  CROSS JOIN competencias comp
                  LEFT JOIN calificaciones cal ON e.id = cal.estudiante_id 
                    AND comp.id = cal.competencia_id 
                    AND cal.periodo_id = ?
                  WHERE e.grado_id = ? 
                    AND comp.area_curricular_id = ?
                    AND e.activo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$periodo_id, $grado_id, $area_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getGrados() {
        $query = "SELECT g.*, n.nombre as nivel_nombre 
                  FROM grados g 
                  INNER JOIN niveles n ON g.nivel_id = n.id 
                  ORDER BY n.id, g.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAreas() {
        $query = "SELECT * FROM areas_curriculares ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPeriodos() {
        $query = "SELECT * FROM periodos ORDER BY año DESC, numero";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCompetencias($area_id) {
        $query = "SELECT * FROM competencias WHERE area_curricular_id = ? ORDER BY codigo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$area_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>