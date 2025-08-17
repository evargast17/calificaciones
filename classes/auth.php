<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($email, $password) {
        $query = "SELECT u.*, r.nombre as rol_nombre 
                  FROM usuarios u 
                  INNER JOIN roles r ON u.rol_id = r.id 
                  WHERE u.email = ? AND u.activo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if(md5($password) === $user['password']) {
                $this->startSession();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellidos'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_rol'] = $user['rol_nombre'];
                $_SESSION['rol_id'] = $user['rol_id'];
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        $this->startSession();
        session_unset();
        session_destroy();
    }
    
    public function isLoggedIn() {
        $this->startSession();
        return isset($_SESSION['user_id']);
    }
    
    public function hasPermission($required_roles) {
        $this->startSession();
        if(!$this->isLoggedIn()) {
            return false;
        }
        
        if(is_string($required_roles)) {
            $required_roles = [$required_roles];
        }
        
        return in_array($_SESSION['user_rol'], $required_roles);
    }
}
?>