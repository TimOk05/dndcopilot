/**
 * Простой переключатель языков
 */

class SimpleLangSwitcher {
    constructor() {
        this.currentLang = this.getCurrentLanguage();
        this.init();
    }
    
    init() {
        this.createLanguageSwitcher();
        this.updatePageLanguage();
    }
    
    getCurrentLanguage() {
        // Проверяем URL параметр
        const urlParams = new URLSearchParams(window.location.search);
        const langParam = urlParams.get('lang');
        if (langParam && ['en', 'ru'].includes(langParam)) {
            return langParam;
        }
        
        // Проверяем localStorage
        const savedLang = localStorage.getItem('language');
        if (savedLang && ['en', 'ru'].includes(savedLang)) {
            return savedLang;
        }
        
        // По умолчанию английский
        return 'en';
    }
    
    createLanguageSwitcher() {
        // Создаем переключатель языков
        const switcher = document.createElement('div');
        switcher.id = 'lang-switcher';
        switcher.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(0,0,0,0.8);
            border-radius: 8px;
            padding: 8px;
            display: flex;
            gap: 5px;
        `;
        
        // Кнопка английского
        const enBtn = document.createElement('button');
        enBtn.textContent = 'EN';
        enBtn.style.cssText = `
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            background: ${this.currentLang === 'en' ? '#4a90e2' : '#666'};
            color: white;
        `;
        enBtn.onclick = () => this.switchLanguage('en');
        
        // Кнопка русского
        const ruBtn = document.createElement('button');
        ruBtn.textContent = 'RU';
        ruBtn.style.cssText = `
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            background: ${this.currentLang === 'ru' ? '#4a90e2' : '#666'};
            color: white;
        `;
        ruBtn.onclick = () => this.switchLanguage('ru');
        
        switcher.appendChild(enBtn);
        switcher.appendChild(ruBtn);
        document.body.appendChild(switcher);
    }
    
    switchLanguage(lang) {
        // Сохраняем в localStorage
        localStorage.setItem('language', lang);
        
        // Перезагружаем страницу с новым языком
        const url = new URL(window.location);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    }
    
    updatePageLanguage() {
        // Обновляем элементы с data-lang атрибутами
        const elements = document.querySelectorAll('[data-lang]');
        elements.forEach(element => {
            const key = element.getAttribute('data-lang');
            const translation = this.getTranslation(key);
            if (translation) {
                if (element.tagName === 'INPUT' && element.type === 'text') {
                    element.placeholder = translation;
                } else {
                    element.textContent = translation;
                }
            }
        });
    }
    
    getTranslation(key) {
        const translations = {
            'en': {
                'app_name': 'D&D Copilot',
                'welcome': 'Welcome to D&D Copilot',
                'character_generator': 'Character Generator',
                'enemy_generator': 'Enemy Generator',
                'potion_generator': 'Potion Generator',
                'combat_system': 'Combat System',
                'dice_roller': 'Dice Roller',
                'notes': 'Notes',
                'login': 'Login',
                'logout': 'Logout',
                'settings': 'Settings',
                'roll_dice': 'Roll Dice',
                'add_character': 'Add Character',
                'create_enemy': 'Create Enemy',
                'create_potion': 'Create Potion',
                'enter_message': 'Enter message...',
                'invalid_dice_format': 'Invalid dice format!',
                'note_saved': 'Note saved',
                'error_empty_content': 'Error: empty content',
            },
            'ru': {
                'app_name': 'D&D Копайлот',
                'welcome': 'Добро пожаловать в D&D Копайлот',
                'character_generator': 'Генератор персонажей',
                'enemy_generator': 'Генератор противников',
                'potion_generator': 'Генератор зелий',
                'combat_system': 'Система боя',
                'dice_roller': 'Бросок костей',
                'notes': 'Заметки',
                'login': 'Вход',
                'logout': 'Выйти',
                'settings': 'Настройки',
                'roll_dice': 'Бросить кости',
                'add_character': 'Добавить персонажа',
                'create_enemy': 'Создать противника',
                'create_potion': 'Создать зелье',
                'enter_message': 'Введите сообщение...',
                'invalid_dice_format': 'Неверный формат кубов!',
                'note_saved': 'Заметка сохранена',
                'error_empty_content': 'Ошибка: пустое содержимое',
            }
        };
        
        return translations[this.currentLang]?.[key] || key;
    }
}

// Инициализируем при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    new SimpleLangSwitcher();
});
