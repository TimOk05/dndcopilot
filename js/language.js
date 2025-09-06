/**
 * Модуль управления языками
 * Обеспечивает переключение языков и переводы интерфейса
 */

class LanguageManager {
    constructor() {
        this.currentLanguage = 'ru';
        this.translations = {
            'ru': {
                'welcome': 'Добро пожаловать',
                'logout': '🚪 Выйти',
                'character_generator': '🎭 Генератор персонажей',
                'character_subtitle': 'Создайте полноценного персонажа с использованием D&D API и AI',
                'enemy_generator': '👹 Генератор противников',
                'enemy_subtitle': 'Создайте подходящих противников для вашей группы',
                'potion_generator': '🧪 Генератор зелий',
                'potion_subtitle': 'Создайте магические зелья различных типов и редкости',
                'initiative': '⚡ Инициатива',
                'participants': 'Участников',
                'round': 'Раунд',
                'notes': '📝 Заметки',
                'dice': '🎲 Кости',
                'generate': '🎲 Сгенерировать',
                'loading': 'Загрузка...',
                'error': 'Ошибка',
                'success': 'Успешно',
                'race': 'Раса',
                'class': 'Класс',
                'level': 'Уровень',
                'gender': 'Пол'
            },
            'en': {
                'welcome': 'Welcome',
                'logout': '🚪 Logout',
                'character_generator': '🎭 Character Generator',
                'character_subtitle': 'Create a full character using D&D API and AI',
                'enemy_generator': '👹 Enemy Generator',
                'enemy_subtitle': 'Create suitable enemies for your group',
                'potion_generator': '🧪 Potion Generator',
                'potion_subtitle': 'Create magical potions of various types and rarity',
                'initiative': '⚡ Initiative',
                'participants': 'Participants',
                'round': 'Round',
                'notes': '📝 Notes',
                'dice': '🎲 Dice',
                'generate': '🎲 Generate',
                'loading': 'Loading...',
                'error': 'Error',
                'success': 'Success',
                'race': 'Race',
                'class': 'Class',
                'level': 'Level',
                'gender': 'Gender'
            }
        };

        this.init();
    }

    init() {
        this.loadLanguageFromStorage();
        this.loadLanguageFromURL();
        this.updateLanguageSelector();
        this.updateInterface();
    }

    /**
     * Получить перевод по ключу
     */
    t(key) {
        return this.translations[this.currentLanguage] ? .[key] ||
            this.translations['ru'][key] ||
            key;
    }

    /**
     * Загрузить язык из localStorage
     */
    loadLanguageFromStorage() {
        try {
            const savedLanguage = localStorage.getItem('dnd_language');
            if (savedLanguage && this.translations[savedLanguage]) {
                this.currentLanguage = savedLanguage;
                console.log('Язык из localStorage:', this.currentLanguage);
            }
        } catch (e) {
            console.log('localStorage недоступен');
        }
    }

    /**
     * Загрузить язык из URL параметров
     */
    loadLanguageFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const urlLang = urlParams.get('lang');
        if (urlLang && this.translations[urlLang]) {
            this.currentLanguage = urlLang;
            console.log('Язык из URL:', this.currentLanguage);
        }
    }

    /**
     * Сохранить язык в localStorage
     */
    saveLanguage() {
        try {
            localStorage.setItem('dnd_language', this.currentLanguage);
            console.log('Язык сохранен в localStorage');
        } catch (e) {
            console.log('localStorage недоступен');
        }
    }

    /**
     * Обновить селектор языка
     */
    updateLanguageSelector() {
        const selector = document.getElementById('languageSelector');
        if (selector) {
            selector.value = this.currentLanguage;
            console.log('Селектор языка обновлен на:', this.currentLanguage);
        }
    }

    /**
     * Сменить язык
     */
    changeLanguage() {
        const selector = document.getElementById('languageSelector');
        if (selector) {
            this.currentLanguage = selector.value;
            console.log('Язык изменен на:', this.currentLanguage);

            this.saveLanguage();
            this.updateInterface();

            // Перезагружаем страницу с новым языком
            setTimeout(() => {
                console.log('Перезагружаем страницу...');
                window.location.href = `index.php?lang=${this.currentLanguage}`;
            }, 100);
        }
    }

    /**
     * Обновить интерфейс
     */
    updateInterface() {
        console.log('=== НАЧИНАЕМ ОБНОВЛЕНИЕ ИНТЕРФЕЙСА ===');
        console.log('Текущий язык:', this.currentLanguage);

        let updatedCount = 0;

        // Обновляем приветствие
        const welcomeElement = document.querySelector('.welcome-text');
        if (welcomeElement) {
            console.log('✅ Найден элемент приветствия:', welcomeElement.textContent);
            const currentText = welcomeElement.textContent;
            const username = currentText.replace(/^(Добро пожаловать|Welcome)\s+/, '');
            const newText = this.t('welcome') + ' ' + username;
            welcomeElement.textContent = newText;
            console.log('✅ Обновлено приветствие:', newText);
            updatedCount++;
        } else {
            console.log('❌ Элемент приветствия не найден');
        }

        // Обновляем кнопку выхода
        const logoutBtn = document.querySelector('.logout-btn');
        if (logoutBtn) {
            console.log('✅ Найдена кнопка выхода:', logoutBtn.textContent);
            const newText = this.t('logout');
            logoutBtn.textContent = newText;
            console.log('✅ Обновлена кнопка выхода:', newText);
            updatedCount++;
        } else {
            console.log('❌ Кнопка выхода не найдена');
        }

        // Обновляем заголовок генератора персонажей в модальном окне
        const characterHeader = document.querySelector('#character-modal .modal-header h2');
        if (characterHeader) {
            console.log('✅ Найден заголовок генератора персонажей:', characterHeader.textContent);
            const newText = this.t('character_generator');
            characterHeader.innerHTML = newText;
            console.log('✅ Обновлен заголовок генератора:', newText);
            updatedCount++;
        } else {
            console.log('❌ Заголовок генератора персонажей не найден');
        }

        // Обновляем кнопку генерации персонажа
        const generateBtn = document.querySelector('.generate-btn');
        if (generateBtn) {
            console.log('✅ Найдена кнопка генерации:', generateBtn.textContent);
            const newText = this.t('generate') + ' ' + (this.currentLanguage === 'en' ? 'Character' : 'персонажа');
            generateBtn.textContent = newText;
            console.log('✅ Обновлена кнопка генерации:', newText);
            updatedCount++;
        } else {
            console.log('❌ Кнопка генерации не найдена');
        }

        // Обновляем лейблы форм
        const labels = document.querySelectorAll('label');
        console.log('Найдено лейблов:', labels.length);
        labels.forEach((label, index) => {
            const forAttr = label.getAttribute('for');
            if (forAttr === 'race') {
                const newText = this.t('race') + ':';
                label.textContent = newText;
                console.log(`✅ Обновлен лейбл расы (${index}):`, newText);
                updatedCount++;
            } else if (forAttr === 'class') {
                const newText = this.t('class') + ':';
                label.textContent = newText;
                console.log(`✅ Обновлен лейбл класса (${index}):`, newText);
                updatedCount++;
            } else if (forAttr === 'level') {
                const newText = this.t('level') + ':';
                label.textContent = newText;
                console.log(`✅ Обновлен лейбл уровня (${index}):`, newText);
                updatedCount++;
            } else if (forAttr === 'gender') {
                const newText = this.t('gender') + ':';
                label.textContent = newText;
                console.log(`✅ Обновлен лейбл пола (${index}):`, newText);
                updatedCount++;
            }
        });

        console.log(`=== ОБНОВЛЕНИЕ ЗАВЕРШЕНО. Обновлено элементов: ${updatedCount} ===`);
    }

    /**
     * Загрузить информацию о языках с сервера
     */
    async loadLanguageInfo() {
        try {
            const response = await fetch('api/generate-potions.php?action=languages');
            if (response.ok) {
                const data = await response.json();
                if (data && data.success) {
                    this.currentLanguage = data.data.current;
                    this.updateLanguageSelector();
                }
            }
        } catch (error) {
            console.log('Информация о языках недоступна, используем русский по умолчанию');
        }
    }

    /**
     * Отладочная функция
     */
    debug() {
        console.log('=== ОТЛАДКА ЯЗЫКА ===');
        console.log('Текущий язык:', this.currentLanguage);
        console.log('Переводы доступны:', Object.keys(this.translations[this.currentLanguage] || {}));
        this.updateInterface();
    }

    /**
     * Принудительное обновление интерфейса
     */
    forceUpdate() {
        console.log('=== ПРИНУДИТЕЛЬНОЕ ОБНОВЛЕНИЕ ===');
        this.updateInterface();
    }
}

// Создаем глобальный экземпляр
window.languageManager = new LanguageManager();

// Глобальные функции для совместимости
window.changeLanguage = () => window.languageManager.changeLanguage();
window.updateInterfaceLanguage = () => window.languageManager.updateInterface();
window.updateLanguageSelector = () => window.languageManager.updateLanguageSelector();
window.debugLanguage = () => window.languageManager.debug();
window.forceUpdateInterface = () => window.languageManager.forceUpdate();