/**
 * ========================================
 * SISTEMA DE CALIFICACIONES POR COMPETENCIAS
 * JavaScript principal - main.js (corregido)
 * Versión: 1.0.2
 * Autor: Sistema de Calificaciones
 * ========================================
 */

// Namespace principal del sistema
window.SystemJS = window.SystemJS || {};
const SystemJS = window.SystemJS;

/**
 * Configuración global del sistema
 */
SystemJS.Config = {
  apiBaseUrl: '',
  debug: true,
  autoSaveInterval: 30000, // 30 segundos
  animationDuration: 300,
  notificationDuration: 5000,
  version: '1.0.2'
};

/**
 * Utilidades del sistema
 */
SystemJS.Utils = {
  /** Obtiene el elemento más cercano que coincida con el selector */
  getClosestElement: function(element, selector) {
    // Asegurar que tenemos un elemento válido
    if (!element || typeof element.closest !== 'function') {
      // Si es un nodo de texto, obtener su elemento padre
      if (element && element.nodeType === Node.TEXT_NODE) {
        element = element.parentElement;
      }
      // Si aún no es un elemento válido, retornar null
      if (!element || typeof element.closest !== 'function') {
        return null;
      }
    }
    return element.closest(selector);
  },

  /** Verifica si un elemento es válido para operaciones DOM */
  isValidElement: function(element) {
    return element && 
           element.nodeType === Node.ELEMENT_NODE && 
           typeof element.closest === 'function';
  },

  /** Formatea una fecha */
  formatDate: function (date, format = 'DD/MM/YYYY') {
    if (!date) return '';
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();

    return format.replace('DD', day).replace('MM', month).replace('YYYY', year);
  },

  /** Formatea un número */
  formatNumber: function (number, decimals = 0) {
    if (isNaN(number)) return '0';
    return new Intl.NumberFormat('es-PE', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(number);
  },

  /** Debounce */
  debounce: function (func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        timeout = null;
        if (!immediate) func(...args);
      };
      const callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func(...args);
    };
  },

  /** Throttle */
  throttle: function (func, limit) {
    let inThrottle = false;
    return function (...args) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => (inThrottle = false), limit);
      }
    };
  },

  /** Genera un ID único */
  generateId: function () {
    return 'id_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  },

  /** Valida email */
  validateEmail: function (email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  },

  /** Sanitiza HTML */
  sanitizeHtml: function (str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  },

  /** Obtiene parámetros de URL */
  getUrlParams: function () {
    const params = {};
    const urlSearchParams = new URLSearchParams(window.location.search);
    for (const [key, value] of urlSearchParams) {
      params[key] = value;
    }
    return params;
  },

  /** Copia texto al portapapeles */
  copyToClipboard: function (text) {
    if (navigator.clipboard) {
      return navigator.clipboard.writeText(text);
    } else {
      // Fallback para navegadores antiguos
      const textArea = document.createElement('textarea');
      textArea.value = text;
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      try {
        document.execCommand('copy');
        document.body.removeChild(textArea);
        return Promise.resolve();
      } catch (err) {
        document.body.removeChild(textArea);
        return Promise.reject(err);
      }
    }
  }
};

/**
 * Sistema de notificaciones
 */
SystemJS.Notifications = {
  container: null,
  stack: [],

  init: function () {
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'notifications-container';
      this.container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
        pointer-events: none;
      `;
      document.body.appendChild(this.container);
    }
  },

  create: function (message, type = 'info', duration = SystemJS.Config.notificationDuration) {
    this.init();

    const notification = document.createElement('div');
    const id = SystemJS.Utils.generateId();

    notification.id = id;
    notification.className = `notification ${type}`;
    notification.style.cssText = `
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      padding: 16px 20px;
      margin-bottom: 12px;
      border-left: 4px solid;
      animation: slideInRight 0.3s ease;
      pointer-events: auto;
      cursor: pointer;
      transition: all 0.3s ease;
      word-wrap: break-word;
    `;

    const colors = {
      success: '#10b981',
      error: '#ef4444',
      warning: '#f59e0b',
      info: '#06b6d4'
    };
    notification.style.borderLeftColor = colors[type] || colors.info;

    const icons = {
      success: '✅',
      error: '❌',
      warning: '⚠️',
      info: 'ℹ️'
    };

    notification.innerHTML = `
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 18px;">${icons[type] || icons.info}</span>
        <div style="flex: 1;">
          <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px;">
            ${type.charAt(0).toUpperCase() + type.slice(1)}
          </div>
          <div style="color: #6b7280; font-size: 14px;">
            ${message}
          </div>
        </div>
        <button style="
          background: none;
          border: none;
          color: #9ca3af;
          cursor: pointer;
          font-size: 18px;
          padding: 0;
          margin-left: 8px;"
          aria-label="Cerrar"
          onclick="SystemJS.Notifications.remove('${id}')">&times;</button>
      </div>
    `;

    // Hover effects
    notification.addEventListener('mouseenter', function () {
      this.style.transform = 'translateX(-4px)';
      this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.2)';
    });

    notification.addEventListener('mouseleave', function () {
      this.style.transform = 'translateX(0)';
      this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    });

    this.container.appendChild(notification);
    this.stack.push(id);

    if (duration > 0) {
      setTimeout(() => this.remove(id), duration);
    }

    return id;
  },

  remove: function (id) {
    const notification = document.getElementById(id);
    if (notification) {
      notification.style.animation = 'slideOutRight 0.3s ease';
      setTimeout(() => {
        if (notification.parentNode) notification.parentNode.removeChild(notification);
        this.stack = this.stack.filter((item) => item !== id);
      }, 300);
    }
  },

  success: function (message, duration) {
    return this.create(message, 'success', duration);
  },
  error: function (message, duration) {
    return this.create(message, 'error', duration);
  },
  warning: function (message, duration) {
    return this.create(message, 'warning', duration);
  },
  info: function (message, duration) {
    return this.create(message, 'info', duration);
  },
  clear: function () {
    this.stack.forEach((id) => this.remove(id));
  }
};

/**
 * Sistema de carga/loading
 */
SystemJS.Loading = {
  overlay: null,
  active: false,

  show: function (message = 'Cargando...') {
    if (this.active) return;

    this.overlay = document.createElement('div');
    this.overlay.id = 'loading-overlay';
    this.overlay.style.cssText = `
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      backdrop-filter: blur(4px);
    `;

    this.overlay.innerHTML = `
      <div style="
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        text-align: center;
        max-width: 300px;">
        <div style="
          width: 40px; height: 40px;
          border: 4px solid #e5e7eb;
          border-top: 4px solid #6366f1;
          border-radius: 50%;
          animation: spin 1s linear infinite;
          margin: 0 auto 1rem auto;
        "></div>
        <div style="color: #374151; font-weight: 600; font-size: 16px;">${message}</div>
      </div>`;

    if (!document.getElementById('loading-animation-styles')) {
      const style = document.createElement('style');
      style.id = 'loading-animation-styles';
      style.textContent = `
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
      `;
      document.head.appendChild(style);
    }

    document.body.appendChild(this.overlay);
    this.active = true;
    document.body.style.overflow = 'hidden';
  },

  hide: function () {
    if (this.overlay && this.overlay.parentNode) {
      this.overlay.style.opacity = '0';
      setTimeout(() => {
        if (this.overlay && this.overlay.parentNode) this.overlay.parentNode.removeChild(this.overlay);
        this.overlay = null;
        this.active = false;
        document.body.style.overflow = '';
      }, 300);
    }
  },

  showMatriz: function (message = 'Actualizando matriz...') {
    this.show(message);
  }
};

/**
 * Sistema de API
 */
SystemJS.API = {
  get baseUrl() {
    return SystemJS.Config.apiBaseUrl || '';
  },

  /** Realiza una petición HTTP */
  request: async function (url, options = {}) {
    const defaultOptions = {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    };

    const finalOptions = { ...defaultOptions, ...options };

    // Serializar body si es un objeto y el Content-Type es JSON
    if (finalOptions.body && typeof finalOptions.body === 'object' && !(finalOptions.body instanceof FormData)) {
      finalOptions.body = JSON.stringify(finalOptions.body);
    }

    try {
      const response = await fetch(this.baseUrl + url, finalOptions);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return await response.json();
      } else {
        return await response.text();
      }
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  },

  /** Guarda una calificación */
  guardarCalificacion: async function (data) {
    return await this.request('ajax/guardar_calificacion.php', {
      method: 'POST',
      body: data
    });
  },

  /** Obtiene estadísticas */
  getEstadisticas: async function (filtros = {}) {
    const params = new URLSearchParams(filtros);
    return await this.request(`ajax/estadisticas.php?${params}`);
  }
};

/**
 * Sistema de almacenamiento local
 */
SystemJS.Storage = {
  prefix: 'scc_', // Sistema Calificaciones Competencias

  set: function (key, value) {
    try {
      localStorage.setItem(this.prefix + key, JSON.stringify(value));
      return true;
    } catch (error) {
      console.warn('Error guardando en localStorage:', error);
      return false;
    }
  },

  get: function (key, defaultValue = null) {
    try {
      const item = localStorage.getItem(this.prefix + key);
      return item ? JSON.parse(item) : defaultValue;
    } catch (error) {
      console.warn('Error leyendo de localStorage:', error);
      return defaultValue;
    }
  },

  remove: function (key) {
    try {
      localStorage.removeItem(this.prefix + key);
      return true;
    } catch (error) {
      console.warn('Error eliminando de localStorage:', error);
      return false;
    }
  },

  clear: function () {
    try {
      const keys = Object.keys(localStorage);
      keys.forEach((key) => {
        if (key.startsWith(this.prefix)) localStorage.removeItem(key);
      });
      return true;
    } catch (error) {
      console.warn('Error limpiando localStorage:', error);
      return false;
    }
  }
};

/**
 * Efectos visuales
 */
SystemJS.Effects = {
  /** Anima un elemento con efecto pulse */
  pulse: function (element, duration = 300) {
    if (!element) return;
    element.style.transition = `transform ${duration}ms ease`;
    element.style.transform = 'scale(1.05)';
    setTimeout(() => {
      element.style.transform = 'scale(1)';
    }, duration / 2);
    setTimeout(() => {
      element.style.transition = '';
    }, duration);
  },

  /** Anima un elemento con efecto shake */
  shake: function (element, duration = 500) {
    if (!element) return;
    const keyframes = [
      { transform: 'translateX(0)' },
      { transform: 'translateX(-10px)' },
      { transform: 'translateX(10px)' },
      { transform: 'translateX(-10px)' },
      { transform: 'translateX(10px)' },
      { transform: 'translateX(0)' }
    ];
    element.animate(keyframes, { duration: duration, easing: 'ease-in-out' });
  },

  /** Anima un contador */
  animateCounter: function (element, targetValue, duration = 1000) {
    if (!element) return;
    const startValue = parseInt(element.textContent) || 0;
    const difference = targetValue - startValue;
    const startTime = performance.now();

    const step = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const easeOut = 1 - Math.pow(1 - progress, 3);
      const currentValue = Math.round(startValue + difference * easeOut);
      element.textContent = currentValue;
      if (progress < 1) requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
  },

  /** Fade in */
  fadeIn: function (element, duration = 300) {
    if (!element) return;
    element.style.opacity = '0';
    element.style.transition = `opacity ${duration}ms ease`;
    setTimeout(() => {
      element.style.opacity = '1';
    }, 10);
    setTimeout(() => {
      element.style.transition = '';
    }, duration);
  },

  /** Fade out */
  fadeOut: function (element, duration = 300, callback = null) {
    if (!element) return;
    element.style.transition = `opacity ${duration}ms ease`;
    element.style.opacity = '0';
    setTimeout(() => {
      element.style.transition = '';
      if (callback) callback();
    }, duration);
  }
};

/**
 * Sistema de confirmación (modal simple)
 */
SystemJS.Confirm = {
  show: function (message, title = '¿Confirmar acción?', options = {}) {
    return new Promise((resolve) => {
      const defaultOptions = {
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        type: 'info'
      };
      const opts = { ...defaultOptions, ...options };

      const overlay = document.createElement('div');
      overlay.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex; align-items: center; justify-content: center;
        z-index: 10001; backdrop-filter: blur(4px);
      `;

      const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#06b6d4' };

      overlay.innerHTML = `
        <div style="
          background: white; padding: 2rem; border-radius: 12px;
          box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
          max-width: 500px; width: 90%; animation: modalSlideIn 0.3s ease;">
          <div style="color: ${colors[opts.type]}; font-size: 24px; margin-bottom: 1rem; text-align: center;">
            ${opts.type === 'warning' ? '⚠️' : opts.type === 'error' ? '❌' : opts.type === 'success' ? '✅' : 'ℹ️'}
          </div>
          <h3 style="color: #1f2937; margin-bottom: 1rem; text-align: center; font-size: 18px; font-weight: 600;">${title}</h3>
          <p style="color: #6b7280; margin-bottom: 2rem; text-align: center; line-height: 1.5;">${message}</p>
          <div style="display: flex; gap: 1rem; justify-content: center;">
            <button id="confirm-cancel" style="background: #f3f4f6; color: #374151; border: none; padding: 0.75rem 2rem; border-radius: 8px; cursor: pointer; font-weight: 600;">${opts.cancelText}</button>
            <button id="confirm-ok" style="background: ${colors[opts.type]}; color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; cursor: pointer; font-weight: 600;">${opts.confirmText}</button>
          </div>
        </div>`;

      document.body.appendChild(overlay);

      const confirmBtn = overlay.querySelector('#confirm-ok');
      const cancelBtn = overlay.querySelector('#confirm-cancel');

      const cleanup = () => {
        if (overlay.parentNode) {
          overlay.style.animation = 'modalSlideOut 0.3s ease';
          setTimeout(() => overlay.parentNode && overlay.parentNode.removeChild(overlay), 300);
        }
      };

      confirmBtn.onclick = () => {
        cleanup();
        resolve(true);
      };
      cancelBtn.onclick = () => {
        cleanup();
        resolve(false);
      };

      const keyHandler = (e) => {
        if (e.key === 'Escape') {
          cleanup();
          resolve(false);
          document.removeEventListener('keydown', keyHandler);
        }
      };
      document.addEventListener('keydown', keyHandler);

      overlay.onclick = (e) => {
        if (e.target === overlay) {
          cleanup();
          resolve(false);
        }
      };
    });
  }
};

/**
 * Utilidades para la matriz de calificaciones
 */
SystemJS.MatrizUtils = {
  /** Cuenta calificaciones por tipo */
  countGradesByType: function () {
    const stats = { AD: 0, A: 0, B: 0, C: 0, total: 0 };
    document.querySelectorAll('.calificacion-btn.active').forEach((btn) => {
      const grade = btn.dataset.calificacion;
      if (stats.hasOwnProperty(grade)) {
        stats[grade]++;
        stats.total++;
      }
    });
    return stats;
  },

  /** Valida completitud de la matriz */
  validateMatrixCompleteness: function () {
    const totalCells = document.querySelectorAll('.calificacion-group').length;
    const filledCells = document.querySelectorAll('.calificacion-btn.active').length;
    const percentage = totalCells > 0 ? Math.round((filledCells / totalCells) * 100) : 0;
    return { total: totalCells, filled: filledCells, pending: totalCells - filledCells, percentage, isComplete: percentage === 100 };
  },

  /** Obtiene estadísticas por estudiante */
  getStudentStats: function (studentId) {
    const buttons = document.querySelectorAll(`[data-estudiante="${studentId}"].calificacion-btn`);
    const activeButtons = document.querySelectorAll(`[data-estudiante="${studentId}"].calificacion-btn.active`);
    const stats = { AD: 0, A: 0, B: 0, C: 0 };
    activeButtons.forEach((btn) => {
      const grade = btn.dataset.calificacion;
      if (stats.hasOwnProperty(grade)) stats[grade]++;
    });
    const totalCompetencies = buttons.length / 4; // 4 botones por competencia
    const evaluated = activeButtons.length;
    const percentage = totalCompetencies > 0 ? Math.round((evaluated / totalCompetencies) * 100) : 0;
    return { ...stats, total: totalCompetencies, evaluated, pending: totalCompetencies - evaluated, percentage };
  },

  /** Obtiene estadísticas por competencia */
  getCompetencyStats: function (competencyId) {
    const buttons = document.querySelectorAll(`[data-competencia="${competencyId}"].calificacion-btn`);
    const activeButtons = document.querySelectorAll(`[data-competencia="${competencyId}"].calificacion-btn.active`);
    const stats = { AD: 0, A: 0, B: 0, C: 0 };
    activeButtons.forEach((btn) => {
      const grade = btn.dataset.calificacion;
      if (stats.hasOwnProperty(grade)) stats[grade]++;
    });
    const totalStudents = buttons.length / 4; // 4 botones por estudiante
    const evaluated = activeButtons.length;
    const percentage = totalStudents > 0 ? Math.round((evaluated / totalStudents) * 100) : 0;
    return { ...stats, total: totalStudents, evaluated, pending: totalStudents - evaluated, percentage };
  },

  /** Exporta datos de la matriz */
  exportMatrixData: function () {
    const data = [];
    const students = document.querySelectorAll('.estudiante-row');
    students.forEach((row) => {
      const studentName = row.querySelector('.estudiante-nombre')?.textContent || '';
      const studentDni = row.querySelector('.estudiante-dni')?.textContent || '';
      const activeButtons = row.querySelectorAll('.calificacion-btn.active');

      const studentData = { nombre: studentName, dni: studentDni, calificaciones: {} };
      activeButtons.forEach((btn) => {
        const competencyId = btn.dataset.competencia;
        const grade = btn.dataset.calificacion;
        studentData.calificaciones[competencyId] = grade;
      });
      data.push(studentData);
    });
    return data;
  }
};

/**
 * Gestión de eventos del teclado
 */
SystemJS.Keyboard = {
  init: function () {
    document.addEventListener('keydown', this.handleKeyDown.bind(this));
  },

  handleKeyDown: function (e) {
    // Ctrl + S: Guardar
    if (e.ctrlKey && e.key === 's') {
      e.preventDefault();
      document.dispatchEvent(new CustomEvent('app:save'));
    }
    // Ctrl + E: Exportar
    if (e.ctrlKey && e.key === 'e') {
      e.preventDefault();
      document.dispatchEvent(new CustomEvent('app:export'));
    }
    // Ctrl + R: Refrescar estadísticas
    if (e.ctrlKey && e.key === 'r') {
      e.preventDefault();
      document.dispatchEvent(new CustomEvent('app:refresh'));
    }
    // ESC: Cerrar modales
    if (e.key === 'Escape') {
      document.dispatchEvent(new CustomEvent('app:escape'));
    }
    // F1: Ayuda
    if (e.key === 'F1') {
      e.preventDefault();
      this.showHelp();
    }
  },

  showHelp: function () {
    const helpContent = `
      <h4>Atajos de Teclado</h4>
      <ul style="text-align: left; margin: 1rem 0;">
        <li><strong>Ctrl + S</strong>: Guardar cambios</li>
        <li><strong>Ctrl + E</strong>: Exportar datos</li>
        <li><strong>Ctrl + R</strong>: Refrescar estadísticas</li>
        <li><strong>ESC</strong>: Cerrar modales</li>
        <li><strong>F1</strong>: Mostrar esta ayuda</li>
      </ul>
      <h4>Uso de la Matriz</h4>
      <ul style="text-align: left; margin: 1rem 0;">
        <li>Haz clic en los botones AD, A, B, C para calificar</li>
        <li>Los cambios se guardan automáticamente</li>
        <li>El progreso se actualiza en tiempo real</li>
        <li>Usa los filtros para cambiar período/aula/área</li>
      </ul>`;

    SystemJS.Modal.show('Ayuda del Sistema', helpContent);
  }
};

/**
 * Sistema de modal simple
 */
SystemJS.Modal = {
  show: function (title, content, options = {}) {
    const defaultOptions = { width: '600px', closable: true };
    const opts = { ...defaultOptions, ...options };

    const overlay = document.createElement('div');
    overlay.style.cssText = `
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex; align-items: center; justify-content: center;
      z-index: 10002; backdrop-filter: blur(4px);`;

    overlay.innerHTML = `
      <div style="
        background: white; padding: 2rem; border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: ${opts.width}; width: 90%; max-height: 80vh; overflow-y: auto;
        animation: modalSlideIn 0.3s ease;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
          <h3 style="color: #1f2937; margin: 0; font-size: 18px; font-weight: 600;">${title}</h3>
          ${opts.closable ? `<button id="modal-close" style="background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 24px; padding: 0; margin: 0; line-height: 1;" aria-label="Cerrar">&times;</button>` : ''}
        </div>
        <div style="color: #374151; line-height: 1.6;">${content}</div>
      </div>`;

    document.body.appendChild(overlay);

    if (opts.closable) {
      const closeBtn = overlay.querySelector('#modal-close');
      const cleanup = () => {
        if (overlay.parentNode) {
          overlay.style.animation = 'modalSlideOut 0.3s ease';
          setTimeout(() => overlay.parentNode && overlay.parentNode.removeChild(overlay), 300);
        }
      };

      if (closeBtn) closeBtn.onclick = cleanup;

      overlay.onclick = (e) => { if (e.target === overlay) cleanup(); };

      const keyHandler = (e) => {
        if (e.key === 'Escape') {
          cleanup();
          document.removeEventListener('keydown', keyHandler);
        }
      };
      document.addEventListener('keydown', keyHandler);
    }
  }
};

/**
 * Gestión de rendimiento y optimización
 */
SystemJS.Performance = {
  observers: [],
  init: function () {
   this.setupIntersectionObserver();
   this.setupPerformanceMonitoring();
 },

 setupIntersectionObserver: function () {
   if ('IntersectionObserver' in window) {
     const observer = new IntersectionObserver(
       (entries) => {
         entries.forEach((entry) => {
           if (entry.isIntersecting) entry.target.classList.add('visible');
         });
       },
       { threshold: 0.1, rootMargin: '50px 0px' }
     );

     this.observers.push(observer);
     document.querySelectorAll('.lazy-load').forEach((el) => observer.observe(el));
   }
 },

 setupPerformanceMonitoring: function () {
   if ('PerformanceObserver' in window) {
     try {
       const observer = new PerformanceObserver((list) => {
         const entries = list.getEntries();
         entries.forEach((entry) => {
           if (entry.duration > 100) {
             console.warn(`Slow operation detected: ${entry.name} took ${entry.duration}ms`);
           }
         });
       });
       observer.observe({ entryTypes: ['navigation', 'measure'] });
       this.observers.push(observer);
     } catch (error) {
       console.log('Performance monitoring not available');
     }
   }
 },

 measureFunction: function (name, fn) {
   const start = performance.now();
   const result = fn();
   const end = performance.now();
   if (SystemJS.Config.debug) console.log(`${name} execution time: ${(end - start).toFixed(2)}ms`);
   return result;
 },

 cleanup: function () {
   this.observers.forEach((observer) => observer && observer.disconnect && observer.disconnect());
   this.observers = [];
 }
};

/**
* Gestión de errores global
*/
SystemJS.ErrorHandler = {
 init: function () {
   window.addEventListener('error', this.handleError.bind(this));
   window.addEventListener('unhandledrejection', this.handlePromiseError.bind(this));
 },

 handleError: function (event) {
   console.error('JavaScript Error:', event.error);
   if (SystemJS.Config.debug) {
     SystemJS.Notifications.error(`Error en la aplicación: ${event.error?.message || event.message}`, 10000);
   }
   this.logError({
     type: 'javascript',
     message: event.error?.message || String(event.message),
     stack: event.error?.stack,
     url: event.filename,
     line: event.lineno,
     column: event.colno,
     timestamp: new Date().toISOString()
   });
 },

 handlePromiseError: function (event) {
   console.error('Unhandled Promise Rejection:', event.reason);
   if (SystemJS.Config.debug) {
     SystemJS.Notifications.error(`Error de promesa: ${event.reason}`, 10000);
   }
   this.logError({ 
     type: 'promise', 
     message: event.reason?.toString?.() || String(event.reason), 
     timestamp: new Date().toISOString() 
   });
 },

 logError: function (errorData) {
   if (SystemJS.Config.debug) {
     const errors = SystemJS.Storage.get('errors', []);
     errors.push(errorData);
     if (errors.length > 50) errors.splice(0, errors.length - 50);
     SystemJS.Storage.set('errors', errors);
   }
 },

 getStoredErrors: function () {
   return SystemJS.Storage.get('errors', []);
 },

 clearStoredErrors: function () {
   SystemJS.Storage.remove('errors');
 }
};

/**
* Gestión de conectividad
*/
SystemJS.Connection = {
 isOnline: navigator.onLine,
 callbacks: [],

 init: function () {
   window.addEventListener('online', this.handleOnline.bind(this));
   window.addEventListener('offline', this.handleOffline.bind(this));
   setInterval(this.checkConnection.bind(this), 30000);
 },

 handleOnline: function () {
   this.isOnline = true;
   SystemJS.Notifications.success('Conexión restaurada', 3000);
   this.executeCallbacks('online');
 },

 handleOffline: function () {
   this.isOnline = false;
   SystemJS.Notifications.warning('Sin conexión a internet', 5000);
   this.executeCallbacks('offline');
 },

 checkConnection: function () {
   fetch('/ping.php', { method: 'HEAD', cache: 'no-cache' })
     .then(() => { if (!this.isOnline) this.handleOnline(); })
     .catch(() => { if (this.isOnline) this.handleOffline(); });
 },

 onStatusChange: function (callback) { 
   this.callbacks.push(callback); 
 },

 executeCallbacks: function (status) {
   this.callbacks.forEach((callback) => {
     try { 
       callback(status, this.isOnline); 
     } catch (error) { 
       console.error('Error in connection callback:', error); 
     }
   });
 }
};

/**
* Inyectar estilos CSS necesarios
*/
SystemJS.injectStyles = function () {
 if (!document.getElementById('systemjs-styles')) {
   const style = document.createElement('style');
   style.id = 'systemjs-styles';
   style.textContent = `
     @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
     @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
     @keyframes modalSlideIn { from { opacity: 0; transform: translateY(-20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
     @keyframes modalSlideOut { from { opacity: 1; transform: translateY(0) scale(1); } to { opacity: 0; transform: translateY(-20px) scale(0.95); } }
     .lazy-load { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease, transform 0.6s ease; }
     .lazy-load.visible { opacity: 1; transform: translateY(0); }
     .systemjs-tooltip { position: absolute; background: #1f2937; color: white; padding: 8px 12px; border-radius: 6px; font-size: 12px; white-space: nowrap; z-index: 10000; opacity: 0; visibility: hidden; transition: all 0.2s ease; pointer-events: none; }
     .systemjs-tooltip.show { opacity: 1; visibility: visible; }
     .systemjs-tooltip::after { content: ''; position: absolute; top: 100%; left: 50%; margin-left: -5px; border: 5px solid transparent; border-top-color: #1f2937; }
   `;
   document.head.appendChild(style);
 }
};

/**
* Configurar eventos globales (CORREGIDO)
*/
SystemJS.setupGlobalEvents = function () {
 document.addEventListener('dragstart', function (e) {
   if (!e.target.hasAttribute('draggable')) e.preventDefault();
 });

 // Event listeners para tooltips (CORREGIDOS)
 document.addEventListener('mouseenter', function (e) {
   // Verificar que e.target es un elemento válido
   if (SystemJS.Utils.isValidElement(e.target) && e.target.hasAttribute('data-tooltip')) {
     SystemJS.showTooltip(e.target);
   }
 }, true);

 document.addEventListener('mouseleave', function (e) {
   // Verificar que e.target es un elemento válido
   if (SystemJS.Utils.isValidElement(e.target) && e.target.hasAttribute('data-tooltip')) {
     SystemJS.hideTooltip();
   }
 }, true);

 // Event listener para demo login (CORREGIDO)
 document.addEventListener('click', function (e) {
   if (SystemJS.Config.debug) {
     console.log('Click event:', e.target);
   }

   // Verificar que e.target es válido antes de usar closest
   if (SystemJS.Utils.isValidElement(e.target)) {
     const demoUser = SystemJS.Utils.getClosestElement(e.target, '.demo-user');
     if (demoUser) {
       const emailDiv = demoUser.querySelector('.demo-email');
       if (emailDiv) {
         const emailField = document.getElementById('email');
         const passwordField = document.getElementById('password');
         if (emailField && passwordField) {
           emailField.value = emailDiv.textContent;
           passwordField.value = '123456';
           passwordField.focus();
         }
       }
     }
   }
 });
};

/**
* Cargar configuración guardada
*/
SystemJS.loadSavedConfig = function () {
 const savedConfig = this.Storage.get('config', {});
 if (savedConfig.theme) document.body.classList.add(`theme-${savedConfig.theme}`);
 if (savedConfig.language) this.Config.language = savedConfig.language;

 const lastFilters = this.Storage.get('lastFilters');
 if (lastFilters && window.location.pathname.includes('matriz')) {
   Object.keys(lastFilters).forEach((key) => {
     const select = document.querySelector(`[name="${key}"]`);
     if (select && lastFilters[key]) select.value = lastFilters[key];
   });
 }
};

/**
* Sistema de tooltips simple
*/
SystemJS.currentTooltip = null;

SystemJS.showTooltip = function (element) {
 if (!SystemJS.Utils.isValidElement(element)) return;
 
 const text = element.getAttribute('data-tooltip');
 if (!text) return;
 
 this.hideTooltip();
 
 const tooltip = document.createElement('div');
 tooltip.className = 'systemjs-tooltip';
 tooltip.textContent = text;
 document.body.appendChild(tooltip);
 
 const rect = element.getBoundingClientRect();
 const tooltipRect = tooltip.getBoundingClientRect();
 tooltip.style.left = rect.left + rect.width / 2 - tooltipRect.width / 2 + 'px';
 tooltip.style.top = rect.top - tooltipRect.height - 8 + 'px';
 
 setTimeout(() => tooltip.classList.add('show'), 10);
 this.currentTooltip = tooltip;
};

SystemJS.hideTooltip = function () {
 if (this.currentTooltip) {
   this.currentTooltip.classList.remove('show');
   setTimeout(() => {
     if (this.currentTooltip && this.currentTooltip.parentNode) {
       this.currentTooltip.parentNode.removeChild(this.currentTooltip);
     }
     this.currentTooltip = null;
   }, 200);
 }
};

/**
* Funciones globales de compatibilidad
*/
window.validarTodo = function () {
 if (window.matrizInstance && window.matrizInstance.validarCompletitud) {
   window.matrizInstance.validarCompletitud();
 } else {
   SystemJS.Notifications.warning('Función no disponible en este momento');
 }
};

window.exportarDatos = function () {
 document.dispatchEvent(new CustomEvent('app:export'));
};

window.refrescarEstadisticas = function () {
 if (window.matrizInstance && window.matrizInstance.updateGeneralStats) {
   window.matrizInstance.updateGeneralStats();
   SystemJS.Notifications.success('Estadísticas actualizadas');
 } else {
   location.reload();
 }
};

/**
* Cleanup al salir de la página
*/
window.addEventListener('beforeunload', function () {
 SystemJS.Performance.cleanup();
});

/**
* Inicialización del sistema
*/
SystemJS.init = function () {
 console.log(`🚀 Iniciando Sistema de Calificaciones v${this.Config.version}`);
 this.ErrorHandler.init();
 this.Connection.init();
 this.Performance.init();
 this.Keyboard.init();
 this.injectStyles();
 this.setupGlobalEvents();
 this.loadSavedConfig();
 console.log('✅ Sistema inicializado correctamente');
};

/**
* Auto-inicialización cuando el DOM esté listo
*/
if (document.readyState === 'loading') {
 document.addEventListener('DOMContentLoaded', function () {
   SystemJS.init();
 });
} else {
 SystemJS.init();
}

/** Exportar para uso global */
window.SystemJS = SystemJS;

// Mensaje de carga exitosa
console.log('✅ Sistema de Calificaciones por Competencias v1.0.2 cargado correctamente');
console.log('📚 Para ver la ayuda, presiona F1 en la matriz de calificaciones');
console.log('🔧 Modo debug:', SystemJS.Config.debug ? 'ACTIVADO' : 'DESACTIVADO');