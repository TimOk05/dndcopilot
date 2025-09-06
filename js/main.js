/**
 * Главный модуль приложения
 * Инициализирует все компоненты и координирует их работу
 */

class App {
    constructor() {
        this.languageManager = null;
        this.apiManager = null;
        this.uiManager = null;
        this.isInitialized = false;
    }

    /**
     * Инициализация приложения
     */
    async init() {
        console.log('🚀 Инициализация приложения...');

        try {
            // Ждем загрузки DOM
            if (document.readyState === 'loading') {
                await new Promise(resolve => {
                    document.addEventListener('DOMContentLoaded', resolve);
                });
            }

            // Инициализируем компоненты
            await this.initializeComponents();

            // Настраиваем взаимодействие между компонентами
            this.setupComponentInteraction();

            // Загружаем начальные данные
            await this.loadInitialData();

            this.isInitialized = true;
            console.log('✅ Приложение успешно инициализировано');

        } catch (error) {
            console.error('❌ Ошибка инициализации приложения:', error);
            this.handleInitializationError(error);
        }
    }

    /**
     * Инициализация компонентов
     */
    async initializeComponents() {
        console.log('🔧 Инициализация компонентов...');

        // Инициализируем менеджер языков
        if (window.languageManager) {
            this.languageManager = window.languageManager;
            console.log('✅ LanguageManager инициализирован');
        } else {
            throw new Error('LanguageManager не найден');
        }

        // Инициализируем API менеджер
        if (window.apiManager) {
            this.apiManager = window.apiManager;
            this.apiManager.setLanguage(this.languageManager.currentLanguage);
            console.log('✅ ApiManager инициализирован');
        } else {
            throw new Error('ApiManager не найден');
        }

        // Инициализируем UI менеджер
        if (window.uiManager) {
            this.uiManager = window.uiManager;
            console.log('✅ UIManager инициализирован');
        } else {
            throw new Error('UIManager не найден');
        }
    }

    /**
     * Настройка взаимодействия между компонентами
     */
    setupComponentInteraction() {
        console.log('🔗 Настройка взаимодействия компонентов...');

        // Слушаем изменения языка
        this.languageManager.onLanguageChange = (newLanguage) => {
            console.log('🌐 Язык изменен на:', newLanguage);
            this.apiManager.setLanguage(newLanguage);
            this.uiManager.updateLanguage(newLanguage);
        };

        // Настраиваем обработчики ошибок
        this.apiManager.onError = (error) => {
            console.error('API Error:', error);
            this.uiManager.showError('Ошибка API: ' + error.message);
        };

        console.log('✅ Взаимодействие компонентов настроено');
    }

    /**
     * Загрузка начальных данных
     */
    async loadInitialData() {
        console.log('📊 Загрузка начальных данных...');

        try {
            // Загружаем информацию о языках с сервера
            const languageInfo = await this.apiManager.getLanguageInfo();
            if (languageInfo.success) {
                console.log('✅ Информация о языках загружена');
            }

            console.log('✅ Начальные данные загружены');
        } catch (error) {
            console.warn('⚠️ Ошибка загрузки начальных данных:', error);
        }
    }

    /**
     * Обработка ошибок инициализации
     */
    handleInitializationError(error) {
        console.error('Критическая ошибка инициализации:', error);

        // Показываем пользователю сообщение об ошибке
        const errorMessage = `
            Произошла ошибка при загрузке приложения.
            Пожалуйста, обновите страницу или обратитесь к администратору.
            Ошибка: ${error.message}
        `;

        // Создаем уведомление об ошибке
        this.showCriticalError(errorMessage);
    }

    /**
     * Показать критическую ошибку
     */
    showCriticalError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        `;

        errorDiv.innerHTML = `
            <div style="background: #d32f2f; padding: 30px; border-radius: 10px; max-width: 500px;">
                <h2 style="margin: 0 0 20px 0; color: white;">❌ Критическая ошибка</h2>
                <p style="margin: 0 0 20px 0; line-height: 1.5;">${message}</p>
                <button onclick="location.reload()" style="
                    background: #fff;
                    color: #d32f2f;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                ">🔄 Обновить страницу</button>
            </div>
        `;

        document.body.appendChild(errorDiv);
    }

    /**
     * Получить статус приложения
     */
    getStatus() {
        return {
            isInitialized: this.isInitialized,
            languageManager: !!this.languageManager,
            apiManager: !!this.apiManager,
            uiManager: !!this.uiManager,
            currentLanguage: this.languageManager ? .currentLanguage
        };
    }

    /**
     * Перезапуск приложения
     */
    async restart() {
        console.log('🔄 Перезапуск приложения...');
        this.isInitialized = false;
        await this.init();
    }
}

// Создаем глобальный экземпляр приложения
window.app = new App();

// Инициализируем приложение
window.app.init().catch(error => {
    console.error('Критическая ошибка при инициализации:', error);
});

// Глобальные функции для отладки
window.getAppStatus = () => window.app.getStatus();
window.restartApp = () => window.app.restart();