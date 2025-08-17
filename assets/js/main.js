/**
 * Sistema de Calificaciones - JavaScript Principal
 * Funciones globales y utilidades
 */

// Configuración global
const CONFIG = {
    API_BASE: '/calificaciones/ajax/',
    ANIMATION_DURATION: 300,
    NOTIFICATION_DURATION: 5000,
    AUTO_SAVE_DELAY: 1000
};

// Utilidades globales
const Utils = {
    // Debounce function para optimizar llamadas
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Formatear fecha
    formatDate(date) {
        return new Date(date).toLocaleDateString('es-PE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    },

    // Formatear tiempo
    formatTime(date) {
        return new Date(date).toLocaleTimeString('es-PE', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    // Sanitizar entrada
    sanitizeInput(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    // Generar ID único
    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },

    // Copiar al portapapeles
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            Notifications.success('Copiado al portapapeles');
        } catch (err) {
            console.error('Error al copiar:', err);
            Notifications.error('Error al copiar al portapapeles');
        }
    }
};

// Sistema de notificaciones
const Notifications = {
    container: null,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'notifications-container';
            this.container.className = 'position-fixed top-0 end-0 p-3';
            this.container.style.zIndex = '1055';
            document.body.appendChild(this.container);
        }
    },

    create(message, type = 'info', duration = CONFIG.NOTIFICATION_DURATION) {
        this.init();

        const notification = document.createElement('div');
        const id = Utils.generateId();
        
        notification.className = `alert alert-${type} alert-dismissible fade show animate-slide-up`;
        notification.setAttribute('role', 'alert');
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-${this.getIcon(type)} me-2"></i>
                <span>${Utils.sanitizeInput(message)}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        `;

        this.container.appendChild(notification);

        // Auto-remove
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 150);
            }
        }, duration);

        return notification;
    },

    getIcon(type) {
        const icons = {
            success: 'check-circle-fill',
            error: 'exclamation-triangle-fill',
            warning: 'exclamation-triangle-fill',
            info: 'info-circle-fill',
            danger: 'exclamation-triangle-fill'
        };
        return icons[type] || 'info-circle-fill';
    },

    success(message, duration) {
        return this.create(message, 'success', duration);
    },

    error(message, duration) {
        return this.create(message, 'danger', duration);
    },

    warning(message, duration) {
        return this.create(message, 'warning', duration);
    },

    info(message, duration) {
        return this.create(message, 'info', duration);
    }
};

// Sistema de loading
const Loading = {
    show(target = document.body, message = 'Cargando...') {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay position-fixed w-100 h-100 d-flex align-items-center justify-content-center';
        overlay.style.cssText = `
            top: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            z-index: 1050;
        `;
        
        overlay.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="fw-medium text-muted">${message}</div>
            </div>
        `;

        overlay.id = 'global-loading';
        document.body.appendChild(overlay);
        
        return overlay;
    },

    hide() {
        const overlay = document.getElementById('global-loading');
        if (overlay) {
            overlay.classList.add('fade-out');
            setTimeout(() => overlay.remove(), 200);
        }
    }
};

// Confirmaciones modernas
const Confirm = {
    show(message, title = '¿Estás seguro?', options = {}) {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'confirmModal';
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold text-dark">
                                <i class="bi bi-question-circle-fill text-warning me-2"></i>
                                ${title}
                            </h5>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-0">${message}</p>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                ${options.cancelText || 'Cancelar'}
                            </button>
                            <button type="button" class="btn btn-primary" id="confirmButton">
                                ${options.confirmText || 'Confirmar'}
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            
            modal.querySelector('#confirmButton').addEventListener('click', () => {
                bsModal.hide();
                resolve(true);
            });

            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
                resolve(false);
            });

            bsModal.show();
        });
    }
};

// API Helper
const API = {
    async request(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        try {
            const response = await fetch(CONFIG.API_BASE + endpoint, {
                ...defaultOptions,
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
};

// Gestión de formularios
const Forms = {
    // Validar formulario
    validate(form) {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                this.showFieldError(input, 'Este campo es obligatorio');
                isValid = false;
            } else {
                this.clearFieldError(input);
            }
        });

        return isValid;
    },

    // Mostrar error en campo
    showFieldError(input, message) {
        this.clearFieldError(input);
        
        input.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        input.parentNode.appendChild(feedback);
    },

    // Limpiar error en campo
    clearFieldError(input) {
        input.classList.remove('is-invalid');
        const feedback = input.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    },

    // Serializar formulario
    serialize(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    },

    // Auto-guardado
    enableAutoSave(form, saveFunction, delay = CONFIG.AUTO_SAVE_DELAY) {
        const debouncedSave = Utils.debounce(saveFunction, delay);
        
        form.addEventListener('input', debouncedSave);
        form.addEventListener('change', debouncedSave);
    }
};

// Efectos visuales
const Effects = {
    // Pulso de actualización
    pulse(element) {
        element.classList.add('pulse-on-update');
        setTimeout(() => {
            element.classList.remove('pulse-on-update');
        }, 600);
    },

    // Destacar elemento
    highlight(element, duration = 2000) {
        element.style.backgroundColor = '#fef3cd';
        element.style.transition = 'background-color 0.3s ease';
        
        setTimeout(() => {
            element.style.backgroundColor = '';
            setTimeout(() => {
                element.style.transition = '';
            }, 300);
        }, duration);
    },

    // Smooth scroll
    scrollTo(element, offset = 0) {
        const elementPosition = element.offsetTop - offset;
        
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    },

    // Contar números animado
    countUp(element, target, duration = 1000) {
        const start = parseInt(element.textContent) || 0;
        const increment = (target - start) / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= target) || (increment < 0 && current <= target)) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    }
};

// LocalStorage helper
const Storage = {
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error('Error saving to localStorage:', error);
        }
    },

    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('Error reading from localStorage:', error);
            return defaultValue;
        }
    },

    remove(key) {
        try {
            localStorage.removeItem(key);
        } catch (error) {
            console.error('Error removing from localStorage:', error);
        }
    },

    clear() {
        try {
            localStorage.clear();
        } catch (error) {
            console.error('Error clearing localStorage:', error);
        }
    }
};

// Gestión de estado global
const State = {
    data: {},
    listeners: {},

    set(key, value) {
        const oldValue = this.data[key];
        this.data[key] = value;
        
        if (this.listeners[key]) {
            this.listeners[key].forEach(callback => {
                callback(value, oldValue);
            });
        }
    },

    get(key) {
        return this.data[key];
    },

    subscribe(key, callback) {
        if (!this.listeners[key]) {
            this.listeners[key] = [];
        }
        this.listeners[key].push(callback);
    },

    unsubscribe(key, callback) {
        if (this.listeners[key]) {
            this.listeners[key] = this.listeners[key].filter(cb => cb !== callback);
        }
    }
};

// Inicialización de la aplicación
const App = {
    init() {
        this.setupGlobalEventListeners();
        this.initializeComponents();
        this.loadUserPreferences();
        
        // Marcar como cargado
        document.body.classList.add('app-loaded');
        
        console.log('✅ Sistema de Calificaciones iniciado correctamente');
    },

    setupGlobalEventListeners() {
        // Prevenir envío de formularios vacíos
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.hasAttribute('data-validate')) {
                if (!Forms.validate(form)) {
                    e.preventDefault();
                    Notifications.error('Por favor completa todos los campos obligatorios');
                }
            }
        });

        // Confirmar acciones destructivas
        document.addEventListener('click', async (e) => {
            if (e.target.hasAttribute('data-confirm')) {
                e.preventDefault();
                const message = e.target.getAttribute('data-confirm');
                const confirmed = await Confirm.show(message);
                
                if (confirmed) {
                    if (e.target.tagName === 'A') {
                        window.location.href = e.target.href;
                    } else if (e.target.type === 'submit') {
                        e.target.closest('form').submit();
                    }
                }
            }
        });

        // Auto-resize textareas
        document.addEventListener('input', (e) => {
            if (e.target.tagName === 'TEXTAREA' && e.target.hasAttribute('data-auto-resize')) {
                e.target.style.height = 'auto';
                e.target.style.height = e.target.scrollHeight + 'px';
            }
        });

        // Tooltips globales
        document.addEventListener('mouseenter', (e) => {
            if (e.target.hasAttribute('title') && !e.target.hasAttribute('data-bs-toggle')) {
                new bootstrap.Tooltip(e.target);
            }
        });
    },

    initializeComponents() {
        // Inicializar tooltips existentes
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Inicializar popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
    },

    loadUserPreferences() {
        // Cargar tema
        const theme = Storage.get('theme', 'light');
        document.documentElement.setAttribute('data-theme', theme);

        // Cargar configuración de tabla
        const tableConfig = Storage.get('tableConfig', {});
        this.applyTableConfig(tableConfig);
    },

    applyTableConfig(config) {
        // Aplicar configuración guardada de tablas
        Object.keys(config).forEach(tableId => {
            const table = document.getElementById(tableId);
            if (table && config[tableId].pageSize) {
                // Aplicar configuración si existe
            }
        });
    }
};

// Utilidades específicas para el sistema de calificaciones
const CalificacionesUtils = {
    // Colores para calificaciones
    getCalificacionColor(calificacion) {
        const colors = {
            'AD': '#10b981',
            'A': '#3b82f6',
            'B': '#f59e0b',
            'C': '#ef4444'
        };
        return colors[calificacion] || '#6b7280';
    },

    // Obtener descripción de calificación
    getCalificacionDescripcion(calificacion) {
        const descripciones = {
            'AD': 'Logro destacado',
            'A': 'Logro esperado',
            'B': 'En proceso',
            'C': 'En inicio'
        };
        return descripciones[calificacion] || 'No evaluado';
    },

    // Calcular porcentaje de progreso
    calcularProgreso(calificaciones) {
        const total = calificaciones.length;
        if (total === 0) return 0;

        const evaluadas = calificaciones.filter(cal => cal !== null && cal !== '').length;
        return Math.round((evaluadas / total) * 100);
    },

    // Calcular estadísticas
    calcularEstadisticas(calificaciones) {
        const stats = { AD: 0, A: 0, B: 0, C: 0, total: 0 };
        
        calificaciones.forEach(cal => {
            if (cal && ['AD', 'A', 'B', 'C'].includes(cal)) {
                stats[cal]++;
                stats.total++;
            }
        });

        return stats;
    },

    // Generar color de progreso
    getProgresoColor(porcentaje) {
        if (porcentaje >= 90) return '#10b981';
        if (porcentaje >= 70) return '#3b82f6';
        if (porcentaje >= 50) return '#f59e0b';
        return '#ef4444';
    }
};

// Event listeners específicos del DOM
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Manejo de errores globales
window.addEventListener('error', (e) => {
    console.error('Error global:', e.error);
    Notifications.error('Ha ocurrido un error inesperado');
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Promesa rechazada:', e.reason);
    Notifications.error('Error de conectividad');
});

// Exportar para uso global
window.SystemJS = {
    Utils,
    Notifications,
    Loading,
    Confirm,
    API,
    Forms,
    Effects,
    Storage,
    State,
    CalificacionesUtils,
    CONFIG
};