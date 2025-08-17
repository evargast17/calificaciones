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
    <title>Sistema de Calificaciones por Competencias - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            padding: 2rem;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            overflow: hidden;
            position: relative;
        }

        .login-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="2.5" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.7;
        }

        .login-header > * {
            position: relative;
            z-index: 1;
        }

        .login-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .login-body {
            padding: 2.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
        }

        .btn-login {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
            color: white;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .demo-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid #e5e7eb;
        }

        .demo-title {
            font-weight: 700;
            color: #374151;
            margin-bottom: 1rem;
            text-align: center;
        }

        .demo-user {
            background: white;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e5e7eb;
            font-size: 0.875rem;
        }

        .demo-email {
            font-weight: 600;
            color: #3b82f6;
        }

        .demo-role {
            color: #6b7280;
            font-size: 0.8rem;
        }

        .demo-password {
            text-align: center;
            margin-top: 1rem;
            padding: 0.5rem;
            background: #fef3cd;
            border-radius: 8px;
            border: 1px solid #fbbf24;
            font-size: 0.875rem;
            color: #92400e;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-body {
                padding: 2rem 1.5rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h1 class="login-title">Sistema de Calificaciones</h1>
                <p class="login-subtitle">Evaluación por Competencias</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i>
                            Correo Electrónico
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="usuario@colegio.edu.pe"
                               required 
                               autocomplete="email">
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock me-1"></i>
                            Contraseña
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Ingresa tu contraseña"
                               required 
                               autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Iniciar Sesión
                    </button>
                </form>
                
                <div class="demo-section">
                    <div class="demo-title">
                        <i class="bi bi-info-circle me-1"></i>
                        Usuarios de Demostración
                    </div>
                    
                    <div class="demo-user">
                        <div class="demo-email">admin@colegio.edu.pe</div>
                        <div class="demo-role">Administrador del Sistema</div>
                    </div>
                    
                    <div class="demo-user">
                        <div class="demo-email">coordinadora@colegio.edu.pe</div>
                        <div class="demo-role">Coordinadora Académica</div>
                    </div>
                    
                    <div class="demo-user">
                        <div class="demo-email">kelly.correa@colegio.edu.pe</div>
                        <div class="demo-role">Tutor del Aula</div>
                    </div>
                    
                    <div class="demo-user">
                        <div class="demo-email">docente.area@colegio.edu.pe</div>
                        <div class="demo-role">Docente de Área</div>
                    </div>
                    
                    <div class="demo-user">
                        <div class="demo-email">docente.taller@colegio.edu.pe</div>
                        <div class="demo-role">Docente de Taller</div>
                    </div>
                    
                    <div class="demo-password">
                        <i class="bi bi-key-fill me-1"></i>
                        <strong>Contraseña para todos:</strong> 123456
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus en el campo email
        document.getElementById('email').focus();
        
        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.login-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
        
        // Demo login rápido
        document.addEventListener('click', function(e) {
            if (e.target.closest('.demo-user')) {
                const emailDiv = e.target.closest('.demo-user').querySelector('.demo-email');
                if (emailDiv) {
                    document.getElementById('email').value = emailDiv.textContent;
                    document.getElementById('password').value = '123456';
                    document.getElementById('password').focus();
                }
            }
        });
    </script>
</body>
</html>