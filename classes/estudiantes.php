<?php
require_once __DIR__ . '/../config/database.php';

class Estudiantes {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAll($grado_id = null) {
        $where = $grado_id ? "WHERE e.grado_id = :grado_id AND e.activo = 1" : "WHERE e.activo = 1";
        
        $query = "SELECT e.*, g.nombre as grado_nombre, g.seccion, n.nombre as nivel_nombre
                  FROM estudiantes e
                  INNER JOIN grados g ON e.grado_id = g.id
                  INNER JOIN niveles n ON g.nivel_id = n.id
                  $where
                  ORDER BY e.apellidos, e.nombres";
        
        $stmt = $this->conn->prepare($query);
        if ($grado_id) {
            $stmt->bindParam(':grado_id', $grado_id);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $query = "SELECT e.*, g.nombre as grado_nombre, g.seccion, n.nombre as nivel_nombre
                  FROM estudiantes e
                  INNER JOIN grados g ON e.grado_id = g.id
                  INNER JOIN niveles n ON g.nivel_id = n.id
                  WHERE e.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $query = "INSERT INTO estudiantes (nombres, apellidos, dni, fecha_nacimiento, grado_id)
                  VALUES (:nombres, :apellidos, :dni, :fecha_nacimiento, :grado_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombres', $data['nombres']);
        $stmt->bindParam(':apellidos', $data['apellidos']);
        $stmt->bindParam(':dni', $data['dni']);
        $stmt->bindParam(':fecha_nacimiento', $data['fecha_nacimiento']);
        $stmt->bindParam(':grado_id', $data['grado_id']);
        
        return $stmt->execute();
    }
    
    public function update($id, $data) {
        $query = "UPDATE estudiantes 
                  SET nombres = :nombres, apellidos = :apellidos, dni = :dni, 
                      fecha_nacimiento = :fecha_nacimiento, grado_id = :grado_id
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombres', $data['nombres']);
        $stmt->bindParam(':apellidos', $data['apellidos']);
        $stmt->bindParam(':dni', $data['dni']);
        $stmt->bindParam(':fecha_nacimiento', $data['fecha_nacimiento']);
        $stmt->bindParam(':grado_id', $data['grado_id']);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $query = "UPDATE estudiantes SET activo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    public function getCalificacionesEstudiante($estudiante_id, $periodo_id = null) {
        $where_periodo = $periodo_id ? "AND cal.periodo_id = :periodo_id" : "";
        
        $query = "SELECT 
                    ac.nombre as area_nombre,
                    comp.codigo,
                    comp.descripcion as competencia_desc,
                    cal.calificacion,
                    cal.fecha_evaluacion,
                    cal.observaciones,
                    p.nombre as periodo_nombre
                  FROM calificaciones cal
                  INNER JOIN competencias comp ON cal.competencia_id = comp.id
                  INNER JOIN areas_curriculares ac ON comp.area_curricular_id = ac.id
                  INNER JOIN periodos p ON cal.periodo_id = p.id
                  WHERE cal.estudiante_id = :estudiante_id $where_periodo
                  ORDER BY ac.nombre, comp.codigo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estudiante_id', $estudiante_id);
        if ($periodo_id) {
            $stmt->bindParam(':periodo_id', $periodo_id);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProgresoEstudiante($estudiante_id) {
        $query = "SELECT 
                    p.nombre as periodo,
                    COUNT(*) as total_competencias,
                    SUM(CASE WHEN cal.calificacion = 'AD' THEN 1 ELSE 0 END) as destacado,
                    SUM(CASE WHEN cal.calificacion = 'A' THEN 1 ELSE 0 END) as esperado,
                    SUM(CASE WHEN cal.calificacion = 'B' THEN 1 ELSE 0 END) as proceso,
                    SUM(CASE WHEN cal.calificacion = 'C' THEN 1 ELSE 0 END) as inicio,
                    SUM(CASE WHEN cal.calificacion IS NOT NULL THEN 1 ELSE 0 END) as evaluadas
                  FROM periodos p
                  CROSS JOIN competencias comp
                  LEFT JOIN calificaciones cal ON comp.id = cal.competencia_id 
                    AND cal.estudiante_id = :estudiante_id 
                    AND cal.periodo_id = p.id
                  WHERE p.activo = 1
                  GROUP BY p.id, p.nombre
                  ORDER BY p.numero";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estudiante_id', $estudiante_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>