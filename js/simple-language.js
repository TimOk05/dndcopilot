/**
 * Простой модуль управления языками
 * Совместим со всеми браузерами
 */

// Глобальные переменные
var currentLanguage = 'ru';
var languageManager = null;

// Словарь переводов
var interfaceTranslations = {
    'ru': {
        'welcome': 'Добро пожаловать',
        'logout': '🚪 Выйти',
        'character_generator': '🎭 Генератор персонажей',
        'generate': '🎲 Сгенерировать',
        'race': 'Раса',
        'class': 'Класс',
        'level': 'Уровень',
        'gender': 'Пол'
    },
    'en': {
        'welcome': 'Welcome',
        'logout': '🚪 Logout',
        'character_generator': '🎭 Character Generator',
        'generate': '🎲 Generate',
        'race': 'Race',
        'class': 'Class',
        'level': 'Level',
        'gender': 'Gender'
    }
};

// Функция получения перевода
function t(key) {
    if (interfaceTranslations[currentLanguage] && interfaceTranslations[currentLanguage][key]) {
        return interfaceTranslations[currentLanguage][key];
    }
    if (interfaceTranslations['ru'] && interfaceTranslations['ru'][key]) {
        return interfaceTranslations['ru'][key];
    }
    return key;
}

// Обновление селектора языка
function updateLanguageSelector() {
    var selector = document.getElementById('languageSelector');
    if (selector) {
        selector.value = currentLanguage;
        console.log('Селектор языка обновлен на:', currentLanguage);
    }
}

// Смена языка
function changeLanguage() {
    var selector = document.getElementById('languageSelector');
    if (selector) {
        currentLanguage = selector.value;
        console.log('Язык изменен на:', currentLanguage);

        // Сохраняем выбор в localStorage
        try {
            localStorage.setItem('dnd_language', currentLanguage);
            console.log('Язык сохранен в localStorage');
        } catch (e) {
            console.log('localStorage недоступен');
        }

        // Обновляем интерфейс
        updateInterfaceLanguage();

        // Перезагружаем страницу с новым языком
        setTimeout(function() {
            console.log('Перезагружаем страницу...');
            window.location.href = 'index.php?lang=' + currentLanguage;
        }, 100);
    }
}

// Обновление интерфейса
function updateInterfaceLanguage() {
    console.log('=== НАЧИНАЕМ ОБНОВЛЕНИЕ ИНТЕРФЕЙСА ===');
    console.log('Текущий язык:', currentLanguage);

    var updatedCount = 0;

    // Обновляем приветствие
    var welcomeElement = document.querySelector('.welcome-text');
    if (welcomeElement) {
        console.log('✅ Найден элемент приветствия:', welcomeElement.textContent);
        var currentText = welcomeElement.textContent;
        var username = currentText.replace(/^(Добро пожаловать|Welcome)\s+/, '');
        var newText = t('welcome') + ' ' + username;
        welcomeElement.textContent = newText;
        console.log('✅ Обновлено приветствие:', newText);
        updatedCount++;
    } else {
        console.log('❌ Элемент приветствия не найден');
    }

    // Обновляем кнопку выхода
    var logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        console.log('✅ Найдена кнопка выхода:', logoutBtn.textContent);
        var newText = t('logout');
        logoutBtn.textContent = newText;
        console.log('✅ Обновлена кнопка выхода:', newText);
        updatedCount++;
    } else {
        console.log('❌ Кнопка выхода не найдена');
    }

    // Обновляем заголовок генератора персонажей
    var characterHeader = document.querySelector('#character-modal .modal-header h2');
    if (characterHeader) {
        console.log('✅ Найден заголовок генератора персонажей:', characterHeader.textContent);
        var newText = t('character_generator');
        characterHeader.innerHTML = newText;
        console.log('✅ Обновлен заголовок генератора:', newText);
        updatedCount++;
    } else {
        console.log('❌ Заголовок генератора персонажей не найден');
    }

    // Обновляем кнопку генерации персонажа
    var generateBtn = document.querySelector('.generate-btn');
    if (generateBtn) {
        console.log('✅ Найдена кнопка генерации:', generateBtn.textContent);
        var newText = t('generate') + ' ' + (currentLanguage === 'en' ? 'Character' : 'персонажа');
        generateBtn.textContent = newText;
        console.log('✅ Обновлена кнопка генерации:', newText);
        updatedCount++;
    } else {
        console.log('❌ Кнопка генерации не найдена');
    }

    // Обновляем лейблы форм
    var labels = document.querySelectorAll('label');
    console.log('Найдено лейблов:', labels.length);
    for (var i = 0; i < labels.length; i++) {
        var label = labels[i];
        var forAttr = label.getAttribute('for');
        if (forAttr === 'race') {
            var newText = t('race') + ':';
            label.textContent = newText;
            console.log('✅ Обновлен лейбл расы (' + i + '):', newText);
            updatedCount++;
        } else if (forAttr === 'class') {
            var newText = t('class') + ':';
            label.textContent = newText;
            console.log('✅ Обновлен лейбл класса (' + i + '):', newText);
            updatedCount++;
        } else if (forAttr === 'level') {
            var newText = t('level') + ':';
            label.textContent = newText;
            console.log('✅ Обновлен лейбл уровня (' + i + '):', newText);
            updatedCount++;
        } else if (forAttr === 'gender') {
            var newText = t('gender') + ':';
            label.textContent = newText;
            console.log('✅ Обновлен лейбл пола (' + i + '):', newText);
            updatedCount++;
        }
    }

    console.log('=== ОБНОВЛЕНИЕ ЗАВЕРШЕНО. Обновлено элементов: ' + updatedCount + ' ===');
}

// Инициализация
function initLanguage() {
    console.log('Инициализация языкового модуля...');

    // Проверяем сохраненный язык
    try {
        var savedLanguage = localStorage.getItem('dnd_language');
        if (savedLanguage) {
            currentLanguage = savedLanguage;
            console.log('Язык из localStorage:', currentLanguage);
        }
    } catch (e) {
        console.log('localStorage недоступен');
    }

    // Проверяем язык из URL
    var urlParams = new URLSearchParams(window.location.search);
    var urlLang = urlParams.get('lang');
    if (urlLang) {
        currentLanguage = urlLang;
        console.log('Язык из URL:', currentLanguage);
    }

    // Обновляем селектор языка
    updateLanguageSelector();

    // Обновляем интерфейс
    updateInterfaceLanguage();

    console.log('Языковой модуль инициализирован');
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLanguage);
} else {
    initLanguage();
}