<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Estudiantes.php';
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

$estudiantes = new Estudiantes();
$calificaciones = new Calificaciones();

// Manejar acciones CRUD
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'nombres' => sanitizeInput($_POST['nombres']),
            'apellidos' => sanitizeInput($_POST['apellidos']),
            'dni' => sanitizeInput($_POST['dni']),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'],
            'grado_id' => $_POST['grado_id']
        ];
        
        if ($estudiantes->create($data)) {
            $mensaje = "Estudiante creado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear estudiante";
            $tipo_mensaje = "danger";
        }
    }
    
    if ($action === 'update') {
        $id = $_POST['id'];
        $data = [
            'nombres' => sanitizeInput($_POST['nombres']),
            'apellidos' => sanitizeInput($_POST['apellidos']),
            'dni' => sanitizeInput($_POST['dni']),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'],
            'grado_id' => $_POST['grado_id']
        ];
        
        if ($estudiantes->update($id, $data)) {
            $mensaje = "Estudiante actualizado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar estudiante";
            $tipo_mensaje = "danger";
        }
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        if ($estudiantes->delete($id)) {
            $mensaje = "Estudiante eliminado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar estudiante";
            $tipo_mensaje = "danger";
        }
    }
}

$grado_filtro = $_GET['grado_id'] ?? null;
$lista_estudiantes = $estudiantes->getAll($grado_filtro);
$grados = $calificaciones->getGrados();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-0">👥 Gestión de Estudiantes</h1>
                    <p class="mb-0">Administrar información de estudiantes</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-light me-2">← Volver al Panel</a>
                    <a href="../matriz_calificaciones.php" class="btn btn-outline-light">Ver Matriz</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">➕ Agregar Estudiante</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create">
                            <div class="mb-3">
                                <label class="form-label">Nombres</label>
                                <input type="text" class="form-control" name="nombres" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control" name="apellidos" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">DNI</label>
                                <input type="text" class="form-control" name="dni" required maxlength="8">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" name="fecha_nacimiento" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Grado</label>
                                <select class="form-select" name="grado_id" required>
                                    <option value="">Seleccionar grado</option>
                                    <?php foreach ($grados as $grado): ?>
                                        <option value="<?php echo $grado['id']; ?>">
                                            <?php echo $grado['nivel_nombre'] . ' ' . $grado['nombre'] . ' - ' . $grado['seccion']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Agregar Estudiante</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">📋 Lista de Estudiantes</h5>
                        <div>
                            <select class="form-select form-select-sm" onchange="filtrarGrado(this.value)">
                                <option value="">Todos los grados</option>
                                <?php foreach ($grados as $grado): ?>
                                    <option value="<?php echo $grado['id']; ?>" <?php echo $grado_filtro == $grado['id'] ? 'selected' : ''; ?>>
                                        <?php echo $grado['nivel_nombre'] . ' ' . $grado['nombre'] . ' - ' . $grado['seccion']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>DNI</th>
                                        <th>Grado</th>
                                        <th>Edad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lista_estudiantes as $estudiante): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 35px; height: 35px;">
                                                        <i class="bi bi-person-fill text-white"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo $estudiante['apellidos'] . ', ' . $estudiante['nombres']; ?></div>
                                                        <small class="text-muted"><?php echo $estudiante['nivel_nombre']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $estudiante['dni']; ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $estudiante['grado_nombre'] . ' - ' . $estudiante['seccion']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($estudiante['fecha_nacimiento']) {
                                                    $edad = date_diff(date_create($estudiante['fecha_nacimiento']), date_create('today'))->y;
                                                    echo $edad . ' años';
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editarEstudiante(<?php echo htmlspecialchars(json_encode($estudiante)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger ms-1" 
                                                        onclick="eliminarEstudiante(<?php echo $estudiante['id']; ?>, '<?php echo $estudiante['nombres'] . ' ' . $estudiante['apellidos']; ?>')">
                                                    <i class="bi bi-trash"></i>
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

    <!-- Modal Editar -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Editar Estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEditar">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control" name="nombres" id="editNombres" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control" name="apellidos" id="editApellidos" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">DNI</label>
                            <input type="text" class="form-control" name="dni" id="editDni" required maxlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" id="editFecha" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grado</label>
                            <select class="form-select" name="grado_id" id="editGrado" required>
                                <?php foreach ($grados as $grado): ?>
                                    <option value="<?php echo $grado['id']; ?>">
                                        <?php echo $grado['nivel_nombre'] . ' ' . $grado['nombre'] . ' - ' . $grado['seccion']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filtrarGrado(gradoId) {
            window.location.href = gradoId ? `?grado_id=${gradoId}` : '?';
        }

        function editarEstudiante(estudiante) {
            document.getElementById('editId').value = estudiante.id;
            document.getElementById('editNombres').value = estudiante.nombres;
            document.getElementById('editApellidos').value = estudiante.apellidos;
            document.getElementById('editDni').value = estudiante.dni;
            document.getElementById('editFecha').value = estudiante.fecha_nacimiento;
            document.getElementById('editGrado').value = estudiante.grado_id;
            
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        }

        function eliminarEstudiante(id, nombre) {
            if (confirm(`¿Está seguro de eliminar al estudiante ${nombre}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>