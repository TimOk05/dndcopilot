/**
 * Управление темами для мобильной версии D&D Copilot
 */

class ThemeManager {
    constructor() {
        this.themes = ['light', 'dark', 'mystic', 'orange'];
        this.currentTheme = this.getStoredTheme() || 'light';
        this.init();
    }

    init() {
        this.applyTheme(this.currentTheme);
        this.createThemeSwitcher();
        this.createThemeIndicator();
        this.bindEvents();
    }

    /**
     * Получение сохраненной темы из localStorage
     */
    getStoredTheme() {
        try {
            return localStorage.getItem('dnd-mobile-theme');
        } catch (e) {
            console.warn('Не удалось получить тему из localStorage:', e);
            return 'light';
        }
    }

    /**
     * Сохранение темы в localStorage
     */
    setStoredTheme(theme) {
        try {
            localStorage.setItem('dnd-mobile-theme', theme);
        } catch (e) {
            console.warn('Не удалось сохранить тему в localStorage:', e);
        }
    }

    /**
     * Применение темы
     */
    applyTheme(theme) {
        if (!this.themes.includes(theme)) {
            theme = 'light';
        }

        document.documentElement.setAttribute('data-theme', theme);
        this.currentTheme = theme;
        this.setStoredTheme(theme);
        this.updateThemeIndicator();

        // Обновляем meta theme-color для мобильных браузеров
        this.updateMetaThemeColor(theme);
    }

    /**
     * Обновление meta theme-color
     */
    updateMetaThemeColor(theme) {
        let metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (!metaThemeColor) {
            metaThemeColor = document.createElement('meta');
            metaThemeColor.name = 'theme-color';
            document.head.appendChild(metaThemeColor);
        }

        const colors = {
            light: '#a67c52',
            dark: '#8b4513',
            mystic: '#6a0dad',
            orange: '#ff8c00'
        };

        metaThemeColor.content = colors[theme] || colors.light;
    }

    /**
     * Переключение на следующую тему
     */
    nextTheme() {
        const currentIndex = this.themes.indexOf(this.currentTheme);
        const nextIndex = (currentIndex + 1) % this.themes.length;
        this.applyTheme(this.themes[nextIndex]);
    }

    /**
     * Создание переключателя тем
     */
    createThemeSwitcher() {
        let switcher = document.querySelector('.theme-switcher');
        if (!switcher) {
            switcher = document.createElement('div');
            switcher.className = 'theme-switcher';
            switcher.title = 'Переключить тему';
            document.body.appendChild(switcher);
        }

        switcher.addEventListener('click', () => {
            this.nextTheme();
            this.showThemeNotification();
        });
    }

    /**
     * Создание индикатора темы
     */
    createThemeIndicator() {
        let indicator = document.querySelector('.theme-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'theme-indicator';
            document.body.appendChild(indicator);
        }

        this.updateThemeIndicator();
    }

    /**
     * Обновление индикатора темы
     */
    updateThemeIndicator() {
        const indicator = document.querySelector('.theme-indicator');
        if (indicator) {
            const themeNames = {
                light: 'Светлая',
                dark: 'Темная',
                mystic: 'Мистическая',
                orange: 'Оранжевая'
            };

            indicator.textContent = 'Тема: ' + (themeNames[this.currentTheme] || 'Светлая');
        }
    }

    /**
     * Показ уведомления о смене темы
     */
    showThemeNotification() {
        const themeNames = {
            light: 'Светлая',
            dark: 'Темная',
            mystic: 'Мистическая',
            orange: 'Оранжевая'
        };

        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.textContent = `Тема изменена на: ${themeNames[this.currentTheme]}`;

        // Стили для уведомления
        Object.assign(notification.style, {
            position: 'fixed',
            top: '80px',
            right: '20px',
            background: 'var(--bg-secondary)',
            color: 'var(--text-primary)',
            padding: '12px 16px',
            borderRadius: 'var(--border-radius)',
            border: '1px solid var(--accent-primary)',
            boxShadow: 'var(--shadow)',
            zIndex: '1001',
            fontSize: '0.9rem',
            opacity: '0',
            transform: 'translateX(100%)',
            transition: 'all 0.3s ease'
        });

        document.body.appendChild(notification);

        // Анимация появления
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Удаление через 3 секунды
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Привязка событий
     */
    bindEvents() {
        // Обработка изменения системной темы
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener((e) => {
                // Если пользователь не выбрал тему вручную, следуем системной
                if (!this.getStoredTheme()) {
                    this.applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }

        // Обработка клавиш (для тестирования)
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                this.nextTheme();
                this.showThemeNotification();
            }
        });
    }

    /**
     * Получение текущей темы
     */
    getCurrentTheme() {
        return this.currentTheme;
    }

    /**
     * Получение списка доступных тем
     */
    getAvailableThemes() {
        return this.themes.map(theme => ({
            id: theme,
            name: {
                light: 'Светлая',
                dark: 'Темная',
                mystic: 'Мистическая',
                orange: 'Оранжевая'
            }[theme]
        }));
    }

    /**
     * Установка конкретной темы
     */
    setTheme(theme) {
        if (this.themes.includes(theme)) {
            this.applyTheme(theme);
            this.showThemeNotification();
        }
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();

    // Добавляем информацию о темах в консоль для разработчиков
    console.log('🎨 D&D Copilot Mobile Themes loaded');
    console.log('Available themes:', window.themeManager.getAvailableThemes());
    console.log('Current theme:', window.themeManager.getCurrentTheme());
    console.log('Press Ctrl+T to switch themes');
});

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}