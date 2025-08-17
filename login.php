<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$error = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($email, $password)) {
        header('Location: matriz_calificaciones.php');
        exit;
    } else {
        $error = 'Credenciales incorrectas';
    }
}

if ($auth->isLoggedIn()) {
    header('Location: matriz_calificaciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Calificaciones - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(45deg, #4f46e5, #7c3aed);
            color: white;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="login-card">
                        <div class="login-header">
                            <h2 class="mb-0">🎓 Sistema de Calificaciones</h2>
                            <p class="mb-0 mt-2">Ingresa tus credenciales</p>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                            </form>
                            
                            <hr class="my-4">
                            <div class="text-center">
                                <small class="text-muted">
                                    <strong>Usuarios de prueba:</strong><br>
                                    Tutor: kelly.correa@colegio.edu.pe<br>
                                    Coordinadora: coordinadora@colegio.edu.pe<br>
                                    Admin: admin@colegio.edu.pe<br>
                                    Contraseña: 123456
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>