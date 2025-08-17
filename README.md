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

## 📦 Instalación

### Requisitos Previos

- Servidor web (Apache/Nginx)
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Extensiones PHP: PDO, PDO_MySQL

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
   ```bash
   git clone [url-repositorio]
   cd sistema-calificaciones
   ```

2. **Configurar la base de datos**
   - Crear una base de datos MySQL
   - Importar el archivo `database.sql`
   - Configurar credenciales en `config/database.php`

3. **Configurar permisos**
   ```bash
   chmod 755 ajax/
   chmod 755 export/
   chmod 644 .htaccess
   ```

4. **Configuración del servidor web**
   - Apuntar el DocumentRoot al directorio del proyecto
   - Habilitar mod_rewrite para Apache

## 🔧 Configuración

### Base de Datos

Editar el archivo `config/database.php`:

```php
private $host = 'localhost';
private $dbname = 'sistema_calificaciones';
private $username = 'tu_usuario';
private $password = 'tu_contraseña';
```

### Usuarios por Defecto

El sistema incluye usuarios de prueba:

- **Administrador**: admin@colegio.edu.pe
- **Coordinadora**: coordinadora@colegio.edu.pe  
- **Tutor**: kelly.correa@colegio.edu.pe
- **Contraseña**: 123456

## 📁 Estructura de Archivos

```
sistema-calificaciones/
├── config/
│   └── database.php          # Configuración de BD
├── classes/
│   ├── Auth.php             # Autenticación
│   ├── Calificaciones.php   # Gestión de calificaciones
│   ├── Estudiantes.php      # Gestión de estudiantes
│   └── Reportes.php         # Generación de reportes
├── admin/
│   └── index.php            # Panel de administración
├── ajax/
│   └── guardar_calificacion.php # API para guardar calificaciones
├── export/
│   └── excel.php            # Exportación a Excel/CSV
├── database.sql             # Script de base de datos
├── index.php               # Página de inicio
├── login.php               # Página de login
├── logout.php              # Cerrar sesión
├── matriz_calificaciones.php # Matriz principal
├── .htaccess               # Configuración Apache
└── README.md               # Este archivo
```

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

