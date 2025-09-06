/**
 * Модуль для работы с API
 * Обеспечивает взаимодействие с серверными API
 */

class ApiManager {
    constructor() {
        this.baseUrl = '';
        this.currentLanguage = 'ru';
    }

    /**
     * Установить текущий язык
     */
    setLanguage(language) {
        this.currentLanguage = language;
    }

    /**
     * Генерация зелий
     */
    async generatePotions(count = 1, language = null) {
        const lang = language || this.currentLanguage;
        try {
            const response = await fetch(`api/generate-potions.php?count=${count}&language=${lang}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Ошибка генерации зелий:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Генерация персонажей
     */
    async generateCharacter(characterData, language = null) {
        const lang = language || this.currentLanguage;
        try {
            const formData = new FormData();
            formData.append('race', characterData.race || '');
            formData.append('class', characterData.class || '');
            formData.append('level', characterData.level || '');
            formData.append('gender', characterData.gender || '');
            formData.append('language', lang);

            const response = await fetch('api/generate-characters-v4.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Ошибка генерации персонажа:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Получить информацию о языках
     */
    async getLanguageInfo() {
        try {
            const response = await fetch('api/generate-potions.php?action=languages');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Ошибка получения информации о языках:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Универсальный метод для API запросов
     */
    async request(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Ошибка API запроса:', error);
            return { success: false, error: error.message };
        }
    }
}

// Создаем глобальный экземпляр
window.apiManager = new ApiManager();