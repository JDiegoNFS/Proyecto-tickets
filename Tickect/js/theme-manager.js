/**
 * SISTEMA DE GESTIÓN DE TEMAS
 * Maneja el cambio dinámico de temas en el sistema de tickets
 */

class ThemeManager {
    constructor() {
        this.themes = {
            'light': {
                name: 'Claro',
                icon: '☀️',
                description: 'Tema clásico y limpio'
            },
            'dark': {
                name: 'Oscuro',
                icon: '🌙',
                description: 'Perfecto para trabajar de noche'
            },
            'executive': {
                name: 'Ejecutivo',
                icon: '💼',
                description: 'Elegante y profesional'
            },
            'corporate': {
                name: 'Corporativo',
                icon: '🏢',
                description: 'Azul empresarial'
            },
            'nature': {
                name: 'Natural',
                icon: '🌿',
                description: 'Verde relajante'
            },
            'sunset': {
                name: 'Atardecer',
                icon: '🌅',
                description: 'Cálido y acogedor'
            }
        };

        this.currentTheme = this.getStoredTheme() || 'light';
        this.init();
    }

    init() {
        this.createThemeSelector();
        this.applyTheme(this.currentTheme);
        this.bindEvents();
        this.showWelcomeMessage();
    }

    createThemeSelector() {
        // Crear botón toggle
        const toggleButton = document.createElement('button');
        toggleButton.className = 'theme-toggle';
        toggleButton.innerHTML = '🎨';
        toggleButton.title = 'Cambiar tema';
        toggleButton.setAttribute('aria-label', 'Selector de temas');

        // Crear selector de temas
        const selector = document.createElement('div');
        selector.className = 'theme-selector hidden';
        selector.innerHTML = `
            <h4><i class="fas fa-palette"></i> Temas</h4>
            <div class="theme-options">
                ${Object.entries(this.themes).map(([key, theme]) => `
                    <div class="theme-option ${key === this.currentTheme ? 'active' : ''}" 
                         data-theme="${key}" 
                         title="${theme.description}">
                        <div class="theme-preview ${key}"></div>
                        <span class="theme-name">${theme.icon} ${theme.name}</span>
                    </div>
                `).join('')}
            </div>
        `;

        // Agregar al DOM
        document.body.appendChild(toggleButton);
        document.body.appendChild(selector);

        this.toggleButton = toggleButton;
        this.selector = selector;
    }

    bindEvents() {
        // Toggle del selector
        this.toggleButton.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleSelector();
        });

        // Selección de tema
        this.selector.addEventListener('click', (e) => {
            const themeOption = e.target.closest('.theme-option');
            if (themeOption) {
                const theme = themeOption.dataset.theme;
                this.changeTheme(theme);
            }
        });

        // Cerrar selector al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!this.selector.contains(e.target) && !this.toggleButton.contains(e.target)) {
                this.hideSelector();
            }
        });

        // Atajos de teclado
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                e.preventDefault();
                this.toggleSelector();
            }

            if (e.key === 'Escape' && !this.selector.classList.contains('hidden')) {
                this.hideSelector();
            }
        });

        // Auto-cambio según hora del día (opcional)
        this.setupAutoTheme();
    }

    toggleSelector() {
        this.selector.classList.toggle('hidden');

        if (!this.selector.classList.contains('hidden')) {
            // Animar entrada
            this.selector.style.opacity = '0';
            this.selector.style.transform = 'translateY(-10px)';

            requestAnimationFrame(() => {
                this.selector.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                this.selector.style.opacity = '1';
                this.selector.style.transform = 'translateY(0)';
            });
        }
    }

    hideSelector() {
        this.selector.classList.add('hidden');
    }

    changeTheme(themeName) {
        if (!this.themes[themeName]) return;

        const oldTheme = this.currentTheme;
        this.currentTheme = themeName;

        // Aplicar tema
        this.applyTheme(themeName);

        // Actualizar UI
        this.updateActiveTheme();

        // Guardar preferencia
        this.storeTheme(themeName);

        // Mostrar notificación
        this.showThemeChangeNotification(oldTheme, themeName);

        // Ocultar selector
        this.hideSelector();

        // Trigger evento personalizado
        this.dispatchThemeChangeEvent(oldTheme, themeName);
    }

    applyTheme(themeName) {
        document.documentElement.setAttribute('data-theme', themeName);

        // Actualizar meta theme-color para móviles
        this.updateMetaThemeColor(themeName);

        // Aplicar clase al body para compatibilidad
        document.body.className = document.body.className.replace(/theme-\w+/g, '');
        document.body.classList.add(`theme-${themeName}`);
    }

    updateActiveTheme() {
        const options = this.selector.querySelectorAll('.theme-option');
        options.forEach(option => {
            option.classList.toggle('active', option.dataset.theme === this.currentTheme);
        });
    }

    updateMetaThemeColor(themeName) {
        let themeColor = '#4a90e2'; // default

        const themeColors = {
            'light': '#4a90e2',
            'dark': '#2c3e50',
            'executive': '#1c1c1c',
            'corporate': '#1e3a8a',
            'nature': '#16a085',
            'sunset': '#e67e22'
        };

        themeColor = themeColors[themeName] || themeColor;

        let metaTheme = document.querySelector('meta[name="theme-color"]');
        if (!metaTheme) {
            metaTheme = document.createElement('meta');
            metaTheme.name = 'theme-color';
            document.head.appendChild(metaTheme);
        }
        metaTheme.content = themeColor;
    }

    storeTheme(themeName) {
        try {
            localStorage.setItem('preferred-theme', themeName);
            localStorage.setItem('theme-change-time', Date.now().toString());
        } catch (e) {
            console.warn('No se pudo guardar la preferencia de tema:', e);
        }
    }

    getStoredTheme() {
        try {
            return localStorage.getItem('preferred-theme');
        } catch (e) {
            console.warn('No se pudo recuperar la preferencia de tema:', e);
            return null;
        }
    }

    getNotificationPosition() {
        // Calcular posición dinámica para evitar conflictos
        const selectorHeight = this.selector.offsetHeight || 250;
        const isOpen = !this.selector.classList.contains('hidden');

        if (isOpen) {
            return Math.max(80 + selectorHeight + 10, 280); // 10px de margen
        }
        return 80; // Posición por defecto
    }

    showThemeChangeNotification(oldTheme, newTheme) {
        const notification = document.createElement('div');
        notification.className = 'toast-notification theme-change-toast';
        notification.innerHTML = `
            <i class="fas fa-palette"></i>
            <span>Tema cambiado a <strong>${this.themes[newTheme].name}</strong></span>
        `;

        // Ajustar posición dinámicamente
        notification.style.top = this.getNotificationPosition() + 'px';

        document.body.appendChild(notification);

        // Animar entrada
        setTimeout(() => notification.classList.add('show'), 100);

        // Remover después de 3 segundos
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    showWelcomeMessage() {
        // Solo mostrar la primera vez o si han pasado más de 24 horas
        const lastShown = localStorage.getItem('theme-welcome-shown');
        const now = Date.now();
        const dayInMs = 24 * 60 * 60 * 1000;

        if (!lastShown || (now - parseInt(lastShown)) > dayInMs) {
            setTimeout(() => {
                this.showThemeChangeNotification('', this.currentTheme);
                localStorage.setItem('theme-welcome-shown', now.toString());
            }, 1000);
        }
    }

    setupAutoTheme() {
        // Cambio automático según la hora (mejorado)
        const autoThemeEnabled = localStorage.getItem('auto-theme-enabled') === 'true';

        if (autoThemeEnabled) {
            const hour = new Date().getHours();
            let suggestedTheme = 'light';

            // Lógica mejorada para sugerencias de tema
            if (hour >= 22 || hour <= 5) {
                suggestedTheme = 'dark'; // Noche profunda
            } else if (hour >= 18 && hour <= 21) {
                suggestedTheme = 'sunset'; // Atardecer
            } else if (hour >= 9 && hour <= 17) {
                suggestedTheme = 'corporate'; // Horario laboral
            } else if (hour >= 6 && hour <= 8) {
                suggestedTheme = 'nature'; // Mañana fresca
            }

            // Solo cambiar si es diferente al actual y no se ha sugerido recientemente
            const lastSuggestion = localStorage.getItem('last-theme-suggestion');
            const now = Date.now();
            const hourInMs = 60 * 60 * 1000;

            if (suggestedTheme !== this.currentTheme &&
                (!lastSuggestion || (now - parseInt(lastSuggestion)) > hourInMs)) {
                setTimeout(() => {
                    this.showAutoThemeSuggestion(suggestedTheme);
                    localStorage.setItem('last-theme-suggestion', now.toString());
                }, 2000);
            }
        }

        // Detectar preferencia del sistema
        this.detectSystemPreference();
    }

    detectSystemPreference() {
        // Detectar si el usuario prefiere modo oscuro del sistema
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            const hasUserPreference = localStorage.getItem('preferred-theme');

            // Solo aplicar si es la primera vez
            if (!hasUserPreference && this.currentTheme === 'light') {
                setTimeout(() => {
                    this.showSystemPreferenceSuggestion('dark');
                }, 1500);
            }
        }

        // Escuchar cambios en la preferencia del sistema
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                const autoSystemEnabled = localStorage.getItem('auto-system-theme') === 'true';
                if (autoSystemEnabled) {
                    const newTheme = e.matches ? 'dark' : 'light';
                    this.changeTheme(newTheme);
                }
            });
        }
    }

    showSystemPreferenceSuggestion(suggestedTheme) {
        const notification = document.createElement('div');
        notification.className = 'toast-notification system-preference-suggestion';
        notification.innerHTML = `
            <i class="fas fa-desktop"></i>
            <div>
                <div>Tu sistema prefiere el modo oscuro. ¿Aplicar tema <strong>${this.themes[suggestedTheme].name}</strong>?</div>
                <div style="margin-top: 8px;">
                    <button onclick="themeManager.changeTheme('${suggestedTheme}'); localStorage.setItem('auto-system-theme', 'true')" 
                            style="background: var(--success-color); color: white; border: none; padding: 4px 8px; border-radius: 4px; margin-right: 8px; cursor: pointer; font-size: 0.8rem;">
                        Sí, y seguir sistema
                    </button>
                    <button onclick="themeManager.changeTheme('${suggestedTheme}')" 
                            style="background: var(--primary-color); color: white; border: none; padding: 4px 8px; border-radius: 4px; margin-right: 8px; cursor: pointer; font-size: 0.8rem;">
                        Solo ahora
                    </button>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                            style="background: var(--secondary-color); color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                        No
                    </button>
                </div>
            </div>
        `;

        // Ajustar posición dinámicamente
        notification.style.top = this.getNotificationPosition() + 'px';

        document.body.appendChild(notification);

        setTimeout(() => notification.classList.add('show'), 100);

        // Auto-remover después de 15 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 300);
            }
        }, 15000);
    }

    showAutoThemeSuggestion(suggestedTheme) {
        const notification = document.createElement('div');
        notification.className = 'toast-notification auto-theme-suggestion';
        notification.innerHTML = `
            <i class="fas fa-lightbulb"></i>
            <div>
                <div>¿Cambiar a tema <strong>${this.themes[suggestedTheme].name}</strong>?</div>
                <div style="margin-top: 8px;">
                    <button onclick="themeManager.changeTheme('${suggestedTheme}')" 
                            style="background: var(--success-color); color: white; border: none; padding: 4px 8px; border-radius: 4px; margin-right: 8px; cursor: pointer;">
                        Sí
                    </button>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                            style="background: var(--secondary-color); color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                        No
                    </button>
                </div>
            </div>
        `;

        // Ajustar posición dinámicamente
        notification.style.top = this.getNotificationPosition() + 'px';

        document.body.appendChild(notification);

        setTimeout(() => notification.classList.add('show'), 100);

        // Auto-remover después de 10 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 300);
            }
        }, 10000);
    }

    dispatchThemeChangeEvent(oldTheme, newTheme) {
        const event = new CustomEvent('themeChanged', {
            detail: {
                oldTheme,
                newTheme,
                themes: this.themes
            }
        });
        document.dispatchEvent(event);
    }

    // Métodos públicos para uso externo
    getCurrentTheme() {
        return this.currentTheme;
    }

    getAvailableThemes() {
        return this.themes;
    }

    setTheme(themeName) {
        this.changeTheme(themeName);
    }

    // Método para exportar/importar configuración
    exportThemeConfig() {
        return {
            currentTheme: this.currentTheme,
            autoThemeEnabled: localStorage.getItem('auto-theme-enabled') === 'true',
            lastChanged: localStorage.getItem('theme-change-time')
        };
    }

    importThemeConfig(config) {
        if (config.currentTheme && this.themes[config.currentTheme]) {
            this.changeTheme(config.currentTheme);
        }

        if (typeof config.autoThemeEnabled === 'boolean') {
            localStorage.setItem('auto-theme-enabled', config.autoThemeEnabled.toString());
        }
    }
}

// Estilos adicionales para las notificaciones
const additionalStyles = `
<style>
.toast-notification {
    position: fixed;
    top: 80px;
    right: 20px;
    background: var(--primary-color);
    color: var(--text-inverse);
    padding: 15px 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    z-index: 1002;
    display: flex;
    align-items: center;
    gap: 10px;
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s ease;
    max-width: 300px;
    font-size: 0.9rem;
}

.toast-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.toast-notification.fade-out {
    transform: translateX(100%);
    opacity: 0;
}

.theme-change-toast {
    background: var(--success-color);
}

.auto-theme-suggestion {
    background: var(--info-color);
    max-width: 350px;
}

@media (max-width: 768px) {
    .toast-notification {
        top: 70px;
        right: 10px;
        left: 10px;
        max-width: none;
        transform: translateY(-100%);
    }
    
    .toast-notification.show {
        transform: translateY(0);
    }
    
    .toast-notification.fade-out {
        transform: translateY(-100%);
    }
}
</style>
`;

// Inyectar estilos adicionales
document.head.insertAdjacentHTML('beforeend', additionalStyles);

// Inicializar el gestor de temas cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeManager = new ThemeManager();
    });
} else {
    window.themeManager = new ThemeManager();
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}