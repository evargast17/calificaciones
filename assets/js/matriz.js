/**
 * Matriz de Calificaciones - JavaScript específico
 */

class MatrizCalificaciones {
    constructor() {
        this.periodoId = null;
        this.gradoId = null;
        this.areaId = null;
        this.autoSaveEnabled = true;
        this.pendingChanges = new Set();
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeFilters();
        this.setupAutoSave();
        this.initializeTooltips();
        this.startPeriodicUpdates();
        
        console.log('📊 Matriz de calificaciones inicializada');
    }

    setupEventListeners() {
        // Eventos de calificación
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('calificacion-btn')) {
                this.handleCalificacionClick(e.target);
            }
        });

        // Filtros
        document.addEventListener('change', (e) => {
            if (e.target.matches('select[name="periodo_id"], select[name="grado_id"], select[name="area_id"]')) {
                this.handleFilterChange();
            }
        });

        // Exportación
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-export')) {
                this.handleExport(e.target.closest('.btn-export'));
            }
        });

        // Teclado shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    }

    async handleCalificacionClick(button) {
        const estudianteId = button.dataset.estudiante;
        const competenciaId = button.dataset.competencia;
        const calificacion = button.dataset.calificacion;

        if (!estudianteId || !competenciaId || !calificacion) {
            console.error('Datos incompletos para calificación');
            return;
        }

        // Feedback visual inmediato
        this.updateButtonVisual(button, calificacion);
        
        // Agregar a cambios pendientes
        this.pendingChanges.add(`${estudianteId}-${competenciaId}`);

        try {
            // Guardar calificación
            await this.saveCalificacion(estudianteId, competenciaId, calificacion);
            
            // Actualizar estadísticas
            this.updateEstadisticas(estudianteId);
            
            // Mostrar confirmación
            this.showSaveConfirmation(button);
            
        } catch (error) {
            console.error('Error al guardar calificación:', error);
            SystemJS.Notifications.error('Error al guardar la calificación');
            
            // Revertir cambio visual
            this.revertButtonVisual(button);
        } finally {
            this.pendingChanges.delete(`${estudianteId}-${competenciaId}`);
        }
    }

    updateButtonVisual(clickedButton, newCalificacion) {
        const estudianteId = clickedButton.dataset.estudiante;
        const competenciaId = clickedButton.dataset.competencia;
        
        // Encontrar todos los botones de esta celda
        const buttons = document.querySelectorAll(
            `[data-estudiante="${estudianteId}"][data-competencia="${competenciaId}"]`
        );
        
        buttons.forEach(btn => {
            // Remover todas las clases de calificación
            btn.classList.remove('calificacion-AD', 'calificacion-A', 'calificacion-B', 'calificacion-C');
            btn.classList.add('calificacion-empty');
            
            // Aplicar la nueva calificación al botón clickeado
            if (btn === clickedButton) {
                btn.classList.remove('calificacion-empty');
                btn.classList.add(`calificacion-${newCalificacion}`);
                
                // Efecto de pulso
                SystemJS.Effects.pulse(btn);
            }
        });
    }

    async saveCalificacion(estudianteId, competenciaId, calificacion) {
        const data = {
            estudiante_id: parseInt(estudianteId),
            competencia_id: parseInt(competenciaId),
            periodo_id: this.getCurrentPeriodoId(),
            calificacion: calificacion
        };

        const response = await fetch('ajax/guardar_calificacion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Error desconocido');
        }

        return result;
    }

    showSaveConfirmation(button) {
        // Crear indicador de guardado
        const indicator = document.createElement('div');
        indicator.className = 'save-indicator position-absolute';
        indicator.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
        indicator.style.cssText = `
            top: -5px;
            right: -5px;
            font-size: 0.8rem;
            z-index: 10;
        `;
        
        button.style.position = 'relative';
        button.appendChild(indicator);
        
        // Remover después de 2 segundos
        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.remove();
            }
        }, 2000);
    }

    updateEstadisticas(estudianteId) {
        // Contar calificaciones del estudiante
        const buttons = document.querySelectorAll(`[data-estudiante="${estudianteId}"]`);
        const calificaciones = Array.from(buttons)
            .filter(btn => !btn.classList.contains('calificacion-empty'))
            .map(btn => {
                const classes = btn.className;
                if (classes.includes('calificacion-AD')) return 'AD';
                if (classes.includes('calificacion-A')) return 'A';
                if (classes.includes('calificacion-B')) return 'B';
                if (classes.includes('calificacion-C')) return 'C';
                return null;
            })
            .filter(cal => cal !== null);

        // Actualizar progreso del estudiante
        this.updateStudentProgress(estudianteId, calificaciones);
        
        // Actualizar estadísticas generales
        this.updateGeneralStats();
    }

    updateStudentProgress(estudianteId, calificaciones) {
        const progressElement = document.querySelector(`[data-student-progress="${estudianteId}"]`);
        if (!progressElement) return;

        const total = document.querySelectorAll(`[data-estudiante="${estudianteId}"]`).length / 4; // 4 botones por competencia
        const completed = calificaciones.length;
        const percentage = Math.round((completed / total) * 100);

        // Actualizar barra de progreso
        const progressBar = progressElement.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
            progressBar.textContent = `${percentage}%`;
            
            // Cambiar color según progreso
            progressBar.className = 'progress-bar';
            if (percentage >= 90) progressBar.classList.add('bg-success');
            else if (percentage >= 70) progressBar.classList.add('bg-info');
            else if (percentage >= 50) progressBar.classList.add('bg-warning');
            else progressBar.classList.add('bg-danger');
        }

        // Efecto de conteo animado
        const numberElement = progressElement.querySelector('.progress-number');
        if (numberElement) {
            SystemJS.Effects.countUp(numberElement, percentage);
        }
    }

    updateGeneralStats() {
        const allButtons = document.querySelectorAll('.calificacion-btn:not(.calificacion-empty)');
        const stats = { AD: 0, A: 0, B: 0, C: 0, total: 0 };

        allButtons.forEach(btn => {
            const classes = btn.className;
            if (classes.includes('calificacion-AD')) stats.AD++;
            else if (classes.includes('calificacion-A')) stats.A++;
            else if (classes.includes('calificacion-B')) stats.B++;
            else if (classes.includes('calificacion-C')) stats.C++;
        });

        stats.total = stats.AD + stats.A + stats.B + stats.C;

        // Actualizar elementos en la UI
        this.updateStatsDisplay(stats);
    }

    updateStatsDisplay(stats) {
        // Actualizar badges de estadísticas
        const elements = {
            'stats-ad': stats.AD,
            'stats-a': stats.A,
            'stats-b': stats.B,
            'stats-c': stats.C,
            'stats-total': stats.total
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                SystemJS.Effects.countUp(element, value);
            }
        });

        // Actualizar porcentaje general
        const totalPossible = document.querySelectorAll('.calificacion-btn').length / 4;
        const completitud = totalPossible > 0 ? Math.round((stats.total / totalPossible) * 100) : 0;
        
        const completitudElement = document.getElementById('completitud-general');
        if (completitudElement) {
            SystemJS.Effects.countUp(completitudElement, completitud);
        }
    }

    handleFilterChange() {
        // Mostrar loading
        const loading = SystemJS.Loading.show(document.body, 'Cargando matriz...');
        
        // Guardar cambios pendientes antes del filtro
        if (this.pendingChanges.size > 0) {
            SystemJS.Notifications.warning('Guardando cambios pendientes...');
        }
        
        // Aplicar filtros (la página se recargará)
        setTimeout(() => {
            const form = document.querySelector('form');
            if (form) {
                form.submit();
            }
        }, 500);
    }

    setupAutoSave() {
        // Guardar automáticamente cada 30 segundos si hay cambios
        setInterval(() => {
            if (this.pendingChanges.size > 0) {
                console.log('⏰ Auto-guardando cambios pendientes...');
                // Los cambios se guardan individualmente, solo notificar
                SystemJS.Notifications.info('Cambios guardados automáticamente');
            }
        }, 30000);
    }

    initializeTooltips() {
        // Tooltips para competencias
        document.querySelectorAll('.competencia-card').forEach(card => {
            const description = card.querySelector('.competencia-description');
            if (description) {
                card.setAttribute('title', description.textContent);
                card.setAttribute('data-bs-toggle', 'tooltip');
                card.setAttribute('data-bs-placement', 'top');
            }
        });

        // Tooltips para calificaciones
        document.querySelectorAll('.calificacion-btn').forEach(btn => {
            const calificacion = btn.textContent.trim();
            const descripcion = SystemJS.CalificacionesUtils.getCalificacionDescripcion(calificacion);
            btn.setAttribute('title', descripcion);
            btn.setAttribute('data-bs-toggle', 'tooltip');
        });
    }

    startPeriodicUpdates() {
        // Actualizar timestamp cada minuto
        setInterval(() => {
            const timestamp = document.querySelector('.last-update');
            if (timestamp) {
                timestamp.textContent = `Actualizado: ${new Date().toLocaleTimeString()}`;
            }
        }, 60000);
    }

    handleKeyboardShortcuts(e) {
        // Ctrl + S: Guardar cambios pendientes
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            if (this.pendingChanges.size > 0) {
                SystemJS.Notifications.info('Guardando cambios...');
            } else {
                SystemJS.Notifications.success('No hay cambios pendientes');
            }
        }

        // Ctrl + E: Exportar
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            this.handleExport();
        }

        // Escape: Cancelar selección
        if (e.key === 'Escape') {
            document.querySelectorAll('.calificacion-btn:focus').forEach(btn => {
                btn.blur();
            });
        }
    }

    handleExport(button = null) {
        const currentFilters = this.getCurrentFilters();
        const exportUrl = `export/excel.php?${new URLSearchParams(currentFilters).toString()}`;
        
        SystemJS.Notifications.info('Generando archivo de exportación...');
        
        // Abrir en nueva ventana
        window.open(exportUrl, '_blank');
    }

    getCurrentFilters() {
        return {
            grado_id: this.getCurrentGradoId(),
            area_id: this.getCurrentAreaId(),
            periodo_id: this.getCurrentPeriodoId()
        };
    }

    getCurrentPeriodoId() {
        const select = document.querySelector('select[name="periodo_id"]');
        return select ? select.value : null;
    }

    getCurrentGradoId() {
        const select = document.querySelector('select[name="grado_id"]');
        return select ? select.value : null;
    }

    getCurrentAreaId() {
        const select = document.querySelector('select[name="area_id"]');
        return select ? select.value : null;
    }

    revertButtonVisual(button) {
        // Implementar reversión si es necesario
        button.classList.add('calificacion-empty');
        SystemJS.Effects.highlight(button, 1000);
    }

    // Método público para forzar actualización
    refresh() {
        this.updateGeneralStats();
        SystemJS.Notifications.success('Matriz actualizada');
    }

    // Método público para verificar cambios pendientes
    hasPendingChanges() {
        return this.pendingChanges.size > 0;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.matriz-container')) {
        window.matriz = new MatrizCalificaciones();
    }
});

// Advertir antes de salir si hay cambios pendientes
window.addEventListener('beforeunload', (e) => {
    if (window.matriz && window.matriz.hasPendingChanges()) {
        e.preventDefault();
        e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
        return e.returnValue;
    }
});