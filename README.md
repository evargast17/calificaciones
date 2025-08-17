# Sistema de Calificaciones por Competencias

Sistema web desarrollado en PHP y MySQL para el registro y seguimiento de calificaciones por competencias en instituciones educativas.

## 📋 Características

- **Gestión de Roles**: Administrador, Coordinadora, Tutor del Aula, Docente Área, Docente Taller
- **Matriz de Evaluación**: Interfaz visual para registro de calificaciones
- **Sistema de Competencias**: Gestión de competencias por áreas curriculares
- **Reportes**: Exportación a Excel/CSV y reportes estadísticos
- **Panel de Administración**: Gestión completa del sistema
- **Responsive Design**: Compatible con dispositivos móviles

## 🛠️ Tecnologías

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: Bootstrap 5, JavaScript
- **Iconos**: Bootstrap Icons

## 🎯 Funcionalidades

### Matriz de Calificaciones
- Visualización en tiempo real de calificaciones
- Filtros por período, grado y área curricular
- Guardado automático vía AJAX
- Estadísticas de progreso por estudiante
- Leyenda visual de niveles de logro (AD, A, B, C)

### Gestión de Roles
- **Administrador**: Acceso completo al sistema
- **Coordinadora**: Supervisión y reportes
- **Tutor del Aula**: Edición de calificaciones del aula
- **Docente Área**: Edición por área específica
- **Docente Taller**: Edición de talleres

### Reportes y Exportación
- Exportación a Excel/CSV
- Reportes por estudiante, grado y período
- Estadísticas generales del sistema
- Progreso por competencias

## 📊 Uso del Sistema

### Acceso Inicial
1. Acceder a la URL del sistema
2. Iniciar sesión con credenciales
3. Ser redirigido según el rol

### Registro de Calificaciones
1. Seleccionar filtros (período, grado, área)
2. Hacer clic en los botones de calificación (AD, A, B, C)
3. Las calificaciones se guardan automáticamente
4. Ver estadísticas en tiempo real

### Generación de Reportes
1. Acceder al panel de administración
2. Seleccionar tipo de reporte
3. Configurar filtros necesarios
4. Exportar en formato deseado

## 🔒 Seguridad

- Autenticación obligatoria para todas las páginas
- Control de permisos por rol
- Protección contra inyección SQL con PDO
- Validación de datos en frontend y backend
- Configuración de seguridad en .htaccess

