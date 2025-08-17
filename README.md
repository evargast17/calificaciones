# Sistema de Calificaciones por Competencias

Sistema web desarrollado en PHP y MySQL para el registro y seguimiento de calificaciones por competencias en instituciones educativas del Perú, basado en el Currículo Nacional.

## 📋 Características Principales

### 🎯 Evaluación por Competencias
- **Matriz Visual Interactiva**: Interfaz intuitiva para registrar calificaciones cualitativas (AD, A, B, C)
- **Competencias por Área**: Gestión de competencias según áreas curriculares del CNB
- **Seguimiento Progresivo**: Monitoreo del avance de cada estudiante por período

### 👥 Gestión de Roles
- **Administrador**: Acceso completo al sistema
- **Coordinadora**: Supervisión y generación de reportes
- **Tutor del Aula**: Gestión integral de su aula asignada
- **Docente Área**: Evaluación específica por área curricular
- **Docente Taller**: Registro de calificaciones en talleres

### 📊 Sistema de Reportes
- **Exportación Excel/CSV**: Descarga de datos con filtros personalizados
- **Estadísticas en Tiempo Real**: Visualización del progreso académico
- **Reportes Comparativos**: Análisis por grados, áreas y períodos
- **Dashboard Administrativo**: Panel de control para directivos

## 🛠️ Tecnologías Utilizadas

### Backend
- **PHP 7.4+**: Lenguaje principal del servidor
- **MySQL 5.7+/MariaDB 10.2+**: Base de datos relacional
- **PDO**: Capa de acceso a datos segura

### Frontend
- **HTML5 & CSS3**: Estructura y estilos modernos
- **Bootstrap 5**: Framework CSS responsivo
- **JavaScript ES6**: Interactividad y funcionalidades dinámicas
- **Bootstrap Icons**: Iconografía consistente

### Características Técnicas
- **Responsive Design**: Compatible con dispositivos móviles y tablets
- **AJAX**: Guardado automático sin recargar página
- **Arquitectura MVC**: Separación clara de responsabilidades
- **Seguridad**: Protección contra inyección SQL y validaciones

## 🎯 Funcionalidades Detalladas

### Matriz de Calificaciones
- **Filtros Dinámicos**: Selección por período, aula y área curricular
- **Registro Rápido**: Click directo en botones de calificación (AD, A, B, C)
- **Guardado Automático**: Sincronización inmediata vía AJAX
- **Progreso Visual**: Barras de completitud por estudiante
- **Estadísticas en Vivo**: Contadores actualizados en tiempo real

### Panel de Administración
- **Dashboard Ejecutivo**: Resumen general del sistema
- **Gestión de Estudiantes**: CRUD completo con filtros
- **Reportes Avanzados**: Múltiples formatos de exportación
- **Estadísticas Detalladas**: Análisis por niveles educativos

### Sistema de Usuarios
- **Autenticación Segura**: Login con roles diferenciados
- **Permisos Granulares**: Acceso controlado por funcionalidad
- **Sesiones Seguras**: Manejo robusto de sesiones PHP

## 📊 Escalas de Calificación

Basado en el Currículo Nacional de la Educación Básica del Perú:

| Calificación | Descripción | Significado |
|--------------|-------------|-------------|
| **AD** | Logro Destacado | El estudiante evidencia un nivel superior a lo esperado |
| **A** | Logro Esperado | El estudiante evidencia el nivel esperado |
| **B** | En Proceso | El estudiante está próximo o cerca al nivel esperado |
| **C** | En Inicio | El estudiante muestra un progreso mínimo |


## 👤 Usuarios de Demostración

| Email | Contraseña | Rol |
|-------|------------|-----|
| admin@colegio.edu.pe | 123456 | Administrador |
| coordinadora@colegio.edu.pe | 123456 | Coordinadora |
| kelly.correa@colegio.edu.pe | 123456 | Tutor del Aula |
| docente.area@colegio.edu.pe | 123456 | Docente Área |
| docente.taller@colegio.edu.pe | 123456 | Docente Taller |

## 📱 Uso del Sistema

### Para Docentes
1. **Acceder al Sistema**: Login con credenciales asignadas
2. **Seleccionar Filtros**: Período, aula y área curricular
3. **Registrar Calificaciones**: Click en botones AD, A, B, C
4. **Seguimiento**: Visualizar progreso en tiempo real

### Para Coordinadores
1. **Dashboard**: Acceso a estadísticas generales
2. **Reportes**: Generación de informes personalizados
3. **Supervisión**: Monitoreo del avance académico
4. **Exportación**: Descarga de datos en Excel/CSV

### Para Administradores
1. **Gestión Completa**: Acceso a todas las funcionalidades
2. **Configuración**: Gestión de usuarios y parámetros
3. **Mantenimiento**: Supervisión del sistema
4. **Reportes Ejecutivos**: Análisis estadísticos avanzados

## 🔒 Características de Seguridad

### Autenticación y Autorización
- **Hash MD5**: Encriptación de contraseñas (recomendable migrar a bcrypt)
- **Control de Sesiones**: Manejo seguro de sesiones PHP
- **Validación de Roles**: Verificación de permisos por funcionalidad

### Protección de Datos
- **Prevención SQL Injection**: Uso de prepared statements
- **Validación de Entrada**: Sanitización de datos del usuario
- **Protección XSS**: Escape de contenido HTML
- **Headers de Seguridad**: Configuración en .htaccess

### Configuración del Servidor
- **Ocultación de Errores**: Sin exposición de información sensible
- **Protección de Archivos**: Restricción de acceso a configuraciones
- **Compresión GZIP**: Optimización de transferencia
- **Cache Headers**: Mejora de rendimiento

## 📁 Estructura del Proyecto

```
sistema-calificaciones/
├── config/
│   ├── database.php          # Configuración de base de datos
│   └── init.php              # Configuración inicial del sistema
├── classes/
│   ├── Auth.php              # Clase de autenticación
│   ├── Calificaciones.php    # Lógica de calificaciones
│   └── Reportes.php          # Generación de reportes
├── ajax/
│   └── guardar_calificacion.php  # Endpoint para AJAX
├── export/
│   └── excel.php             # Exportación a Excel/CSV
├── admin/
│   ├── index.php             # Panel de administración
│   ├── estudiantes.php       # Gestión de estudiantes
│   └── reportes.php          # Reportes avanzados
├── assets/
│   ├── css/
│   └── js/
├── matriz_calificaciones.php  # Matriz principal
├── login.php                 # Página de login
├── index.php                 # Redirección inicial
├── logout.php                # Cerrar sesión
├── database.sql              # Script de base de datos
├── .htaccess                 # Configuración Apache
└── README.md                 # Este archivo
```


## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.


## 🔄 Actualizaciones

### Versión 1.0.0
- ✅ Matriz de calificaciones interactiva
- ✅ Sistema de roles y permisos
- ✅ Exportación a Excel/CSV
- ✅ Dashboard administrativo
- ✅ Responsive design

### Próximas Versiones
- 🔄 Migración a bcrypt para contraseñas
- 🔄 API REST para integración móvil
- 🔄 Reportes PDF automatizados
- 🔄 Notificaciones en tiempo real
- 🔄 Backup automático de datos

---

**Desarrollado con ❤️ para la educación peruana**