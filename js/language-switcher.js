/**
 * Language Switcher - Универсальный переключатель языков
 */

class LanguageSwitcher {
    constructor() {
        this.currentLanguage = this.getCurrentLanguage();
        this.init();
    }

    /**
     * Инициализация переключателя языков
     */
    init() {
        this.createLanguageSelector();
        this.updatePageLanguage();
        this.bindEvents();
    }

    /**
     * Получение текущего языка
     */
    getCurrentLanguage() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('lang') || 'ru';
    }

    /**
     * Создание селектора языков
     */
    createLanguageSelector() {
        // Ищем существующий селектор или создаем новый
        let selector = document.getElementById('language-select');

        if (!selector) {
            // Создаем контейнер для переключателя языков
            const container = document.createElement('div');
            container.id = 'language-switcher';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                background: rgba(255, 255, 255, 0.9);
                padding: 10px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                font-family: Arial, sans-serif;
            `;

            // Создаем селектор
            selector = document.createElement('select');
            selector.id = 'language-select';
            selector.style.cssText = `
                padding: 5px 10px;
                border: 1px solid #ccc;
                border-radius: 4px;
                background: white;
                font-size: 14px;
                cursor: pointer;
            `;

            // Добавляем опции
            const options = [
                { value: 'ru', text: 'Русский' },
                { value: 'en', text: 'English' }
            ];

            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.text;
                if (option.value === this.currentLanguage) {
                    optionElement.selected = true;
                }
                selector.appendChild(optionElement);
            });

            container.appendChild(selector);
            document.body.appendChild(container);
        }

        this.selector = selector;
    }

    /**
     * Привязка событий
     */
    bindEvents() {
        if (this.selector) {
            this.selector.addEventListener('change', (e) => {
                this.changeLanguage(e.target.value);
            });
        }
    }

    /**
     * Смена языка
     */
    changeLanguage(language) {
        const url = new URL(window.location);
        url.searchParams.set('lang', language);
        window.location.href = url.toString();
    }

    /**
     * Обновление языка страницы
     */
    updatePageLanguage() {
        // Обновляем атрибут lang у html элемента
        document.documentElement.lang = this.currentLanguage;

        // Обновляем заголовок страницы
        this.updatePageTitle();

        // Обновляем элементы с data-translate атрибутом
        this.updateTranslatableElements();
    }

    /**
     * Обновление заголовка страницы
     */
    updatePageTitle() {
        const titleElement = document.getElementById('page-title');
        if (titleElement) {
            const titles = {
                'ru': 'D&D Копайлот - AI Ассистент',
                'en': 'D&D Copilot - AI Assistant'
            };
            titleElement.textContent = titles[this.currentLanguage] || titles['ru'];
        }
    }

    /**
     * Обновление переводимых элементов
     */
    updateTranslatableElements() {
        const elements = document.querySelectorAll('[data-translate]');
        elements.forEach(element => {
            const key = element.getAttribute('data-translate');
            const translation = this.getTranslation(key);
            if (translation) {
                element.textContent = translation;
            }
        });

        // Обновляем placeholder атрибуты
        const placeholderElements = document.querySelectorAll('[data-translate-placeholder]');
        placeholderElements.forEach(element => {
            const key = element.getAttribute('data-translate-placeholder');
            const translation = this.getTranslation(key);
            if (translation) {
                element.placeholder = translation;
            }
        });
    }

    /**
     * Получение перевода по ключу
     */
    getTranslation(key) {
        const translations = {
            'ru': {
                'app_name': 'D&D Копайлот',
                'app_title': 'AI Ассистент для D&D',
                'loading': 'Загрузка...',
                'error': 'Ошибка',
                'success': 'Успешно',
                'generate': 'Сгенерировать',
                'characters': 'Персонажи',
                'enemies': 'Противники',
                'potions': 'Зелья',
                'ai_chat': 'AI Чат',
                'notes': 'Заметки',
                'dice': 'Кости',
                'settings': 'Настройки',
                'language': 'Язык',
                'theme': 'Тема',
                'back': 'Назад',
                'next': 'Далее',
                'close': 'Закрыть',
                'save': 'Сохранить',
                'delete': 'Удалить',
                'edit': 'Редактировать',
                'cancel': 'Отмена',
                'yes': 'Да',
                'no': 'Нет'
            },
            'en': {
                'app_name': 'D&D Copilot',
                'app_title': 'AI Assistant for D&D',
                'loading': 'Loading...',
                'error': 'Error',
                'success': 'Success',
                'generate': 'Generate',
                'characters': 'Characters',
                'enemies': 'Enemies',
                'potions': 'Potions',
                'ai_chat': 'AI Chat',
                'notes': 'Notes',
                'dice': 'Dice',
                'settings': 'Settings',
                'language': 'Language',
                'theme': 'Theme',
                'back': 'Back',
                'next': 'Next',
                'close': 'Close',
                'save': 'Save',
                'delete': 'Delete',
                'edit': 'Edit',
                'cancel': 'Cancel',
                'yes': 'Yes',
                'no': 'No'
            }
        };

        return translations[this.currentLanguage] ? .[key] || translations['ru'][key];
    }

    /**
     * Получение текущего языка для использования в других скриптах
     */
    static getCurrentLanguage() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('lang') || 'ru';
    }

    /**
     * Получение перевода для использования в других скриптах
     */
    static t(key) {
        const switcher = new LanguageSwitcher();
        return switcher.getTranslation(key);
    }
}

// Автоматическая инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    new LanguageSwitcher();
});

// Экспорт для использования в других модулях
window.LanguageSwitcher = LanguageSwitcher;