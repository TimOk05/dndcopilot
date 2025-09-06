// ===== ПРОСТОЙ МОБИЛЬНЫЙ ИНТЕРФЕЙС =====

// Определяем мобильное устройство
const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

// Инициализация мобильных функций
document.addEventListener('DOMContentLoaded', function() {
    if (isMobile || isTouch) {
        initSimpleMobile();
    }
});

function initSimpleMobile() {
    // Добавляем класс для мобильных устройств
    document.body.classList.add('mobile-device');

    // Простое исправление лайаута
    fixSimpleLayout();

    // Инициализируем пролистывание
    initSmoothScrolling();

    // Инициализируем мобильные формы
    initMobileForms();

    // Создаем боковое меню
    createSideMenu();
}

// ===== ПРОСТОЕ ИСПРАВЛЕНИЕ ЛАЙАУТА =====

function fixSimpleLayout() {
    // Переключатель темы - в правом верхнем углу
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.style.position = 'fixed';
        themeToggle.style.top = '20px';
        themeToggle.style.right = '20px';
        themeToggle.style.zIndex = '1000';
    }

    // Админ-ссылка - рядом с переключателем темы
    const adminLink = document.querySelector('.admin-link');
    if (adminLink) {
        adminLink.style.position = 'fixed';
        adminLink.style.top = '20px';
        adminLink.style.right = '80px';
        adminLink.style.zIndex = '1000';
    }

    // Убираем приветствие - скрываем пользовательскую информацию
    const userInfo = document.querySelector('.user-info');
    if (userInfo) {
        userInfo.style.display = 'none';
    }

    // Основной контент - отступ сверху
    const parchment = document.querySelector('.parchment');
    if (parchment) {
        parchment.style.marginTop = '60px';
        parchment.style.paddingTop = '20px';
    }
}

// ===== БОКОВОЕ МЕНЮ =====

function createSideMenu() {
    // Создаем кнопку меню
    const menuButton = document.createElement('div');
    menuButton.className = 'mobile-menu-button';
    menuButton.innerHTML = '☰';
    menuButton.onclick = toggleSideMenu;
    document.body.appendChild(menuButton);

    // Создаем боковое меню
    const sideMenu = document.createElement('div');
    sideMenu.className = 'mobile-side-menu';
    sideMenu.innerHTML = `
        <div class="side-menu-header">
            <h3>Меню</h3>
            <button class="close-menu" onclick="toggleSideMenu()">×</button>
        </div>
        <div class="side-menu-content">
            <a href="stats.php" class="menu-item">
                <span class="menu-icon">📊</span>
                <span class="menu-text">Статистика</span>
            </a>
            <a href="#" class="menu-item" onclick="toggleThemeMobile(); toggleSideMenu();">
                <span class="menu-icon" id="theme-menu-icon">🌙</span>
                <span class="menu-text">Сменить тему</span>
            </a>
            <div class="menu-item logout-item" onclick="logout()">
                <span class="menu-icon">🚪</span>
                <span class="menu-text">Выйти</span>
            </div>
        </div>
    `;
    document.body.appendChild(sideMenu);

    // Добавляем затемнение
    const overlay = document.createElement('div');
    overlay.className = 'mobile-menu-overlay';
    overlay.onclick = toggleSideMenu;
    document.body.appendChild(overlay);

    // Устанавливаем правильную иконку темы
    setTimeout(() => {
        const body = document.body;
        const currentTheme = body.getAttribute('data-theme') || 'light';
        const themeMenuIcon = document.getElementById('theme-menu-icon');
        if (themeMenuIcon) {
            themeMenuIcon.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
        }
    }, 100);
}

function toggleSideMenu() {
    const sideMenu = document.querySelector('.mobile-side-menu');
    const overlay = document.querySelector('.mobile-menu-overlay');

    if (sideMenu.classList.contains('active')) {
        sideMenu.classList.remove('active');
        overlay.classList.remove('active');
    } else {
        sideMenu.classList.add('active');
        overlay.classList.add('active');
    }
}

// ===== ПЛАВНОЕ ПРОЛИСТЫВАНИЕ =====

function initSmoothScrolling() {
    // Добавляем плавное пролистывание для всех ссылок
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Плавное пролистывание для кнопок навигации
    const navButtons = document.querySelectorAll('.fast-btn');
    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Плавно прокручиваем к результату после действия
            setTimeout(() => {
                const resultElement = document.querySelector('.chat-box') ||
                    document.querySelector('.notes-block') ||
                    document.querySelector('.modal');
                if (resultElement) {
                    resultElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }
            }, 500);
        });
    });
}

// ===== МОБИЛЬНЫЕ ФОРМЫ =====

function initMobileForms() {
    // Улучшаем поля ввода
    const inputs = document.querySelectorAll('input[type="text"], input[type="number"], select');
    inputs.forEach(input => {
        input.style.fontSize = '16px'; // Предотвращает зум на iOS
        input.style.padding = '12px';
        input.style.minHeight = '44px';
        input.style.borderRadius = '8px';
        input.style.border = '2px solid var(--border-primary)';
    });

    // Улучшаем кнопки
    const buttons = document.querySelectorAll('.fast-btn, button[type="submit"]');
    buttons.forEach(button => {
        button.style.minHeight = '44px';
        button.style.padding = '12px 20px';
        button.style.fontSize = '16px';
        button.style.borderRadius = '8px';
    });
}

// ===== ПРОСТЫЕ МОДАЛЬНЫЕ ОКНА =====

function openSimpleDiceModal() {
    const content = `
        <div style="text-align: center; padding: 20px;">
            <h3 style="margin-bottom: 20px;">🎲 Бросок костей</h3>
            <div style="margin-bottom: 15px;">
                <input type="text" id="dice-input" value="1d20" 
                       style="width: 100px; text-align: center; font-size: 18px; padding: 10px;">
            </div>
            <div style="margin-bottom: 20px;">
                <input type="text" id="dice-label" placeholder="Комментарий" 
                       style="width: 200px; padding: 10px;">
            </div>
            <button class="fast-btn" onclick="rollDice()" 
                    style="width: 100%; padding: 15px; font-size: 18px;">
                🎲 Бросить
            </button>
        </div>
    `;
    showModal(content);
    setTimeout(() => document.getElementById('dice-input').focus(), 100);
}

function openSimpleNpcModal() {
    const content = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px;">🤖 Создать NPC</h3>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Раса:</label>
                <select id="npc-race" style="width: 100%; padding: 12px; font-size: 16px;">
                    <option value="человек">Человек</option>
                    <option value="эльф">Эльф</option>
                    <option value="гном">Гном</option>
                    <option value="полуорк">Полуорк</option>
                    <option value="полурослик">Полурослик</option>
                    <option value="тифлинг">Тифлинг</option>
                    <option value="драконорожденный">Драконорожденный</option>
                    <option value="полуэльф">Полуэльф</option>
                    <option value="дворф">Дворф</option>
                    <option value="гоблин">Гоблин</option>
                    <option value="орк">Орк</option>
                    <option value="кобольд">Кобольд</option>
                    <option value="ящеролюд">Ящеролюд</option>
                    <option value="хоббит">Хоббит</option>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Класс:</label>
                <select id="npc-class" style="width: 100%; padding: 12px; font-size: 16px;">
                    <option value="воин">Воин</option>
                    <option value="маг">Маг</option>
                    <option value="жрец">Жрец</option>
                    <option value="плут">Плут</option>
                    <option value="паладин">Паладин</option>
                    <option value="следопыт">Следопыт</option>
                    <option value="варвар">Варвар</option>
                    <option value="бард">Бард</option>
                    <option value="друид">Друид</option>
                    <option value="монах">Монах</option>
                    <option value="колдун">Колдун</option>
                    <option value="чародей">Чародей</option>
                    <option value="изобретатель">Изобретатель</option>
                    <option value="кровный охотник">Кровный охотник</option>
                    <option value="мистик">Мистик</option>
                    <option value="психоник">Психоник</option>
                    <option value="артифисер">Артифисер</option>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Уровень:</label>
                <input type="number" id="npc-level" value="1" min="1" max="20" 
                       style="width: 100%; padding: 12px; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <button class="fast-btn" onclick="toggleMobileAdvancedSettings()" 
                        style="width: 100%; padding: 12px; font-size: 16px; background: var(--accent-info);">
                    ⚙️ Расширенные настройки
                </button>
            </div>
            
            <div id="mobile-advanced-settings" style="display: none; margin-bottom: 15px; padding: 15px; background: var(--bg-tertiary); border-radius: 8px; border: 1px solid var(--border-tertiary);">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Пол:</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <label><input type="radio" name="gender" value="мужской" checked> Мужской</label>
                        <label><input type="radio" name="gender" value="женский"> Женский</label>
                        <label><input type="radio" name="gender" value="рандом"> Рандом</label>
                    </div>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Мировоззрение:</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <label><input type="radio" name="alignment" value="добрый" checked> Добрый</label>
                        <label><input type="radio" name="alignment" value="нейтральный"> Нейтральный</label>
                        <label><input type="radio" name="alignment" value="злой"> Злой</label>
                        <label><input type="radio" name="alignment" value="рандом"> Рандом</label>
                    </div>
                </div>
            </div>
            
            <button class="fast-btn" onclick="generateSimpleNpc()" 
                    style="width: 100%; padding: 15px; font-size: 18px;">
                🤖 Создать NPC
            </button>
        </div>
    `;
    showModal(content);
}

function openSimpleInitiativeModal() {
    const content = `
        <div style="text-align: center; padding: 20px;">
            <h3 style="margin-bottom: 20px;">⚔️ Добавить в инициативу</h3>
            <div style="margin-bottom: 15px;">
                <input type="text" id="initiative-name" placeholder="Имя персонажа" 
                       style="width: 200px; padding: 10px; font-size: 16px;">
            </div>
            <div style="margin-bottom: 20px;">
                <input type="number" id="initiative-value" placeholder="Инициатива" 
                       style="width: 100px; padding: 10px; font-size: 16px;">
            </div>
            <button class="fast-btn" onclick="addInitiative()" 
                    style="width: 100%; padding: 15px; font-size: 18px;">
                ⚔️ Добавить
            </button>
        </div>
    `;
    showModal(content);
    setTimeout(() => document.getElementById('initiative-name').focus(), 100);
}

// ===== ПРОСТЫЕ ФУНКЦИИ =====

function generateSimpleNpc() {
    const race = document.getElementById('npc-race').value;
    const npcClass = document.getElementById('npc-class').value;
    const level = document.getElementById('npc-level').value;

    if (!race || !npcClass || !level) {
        alert('Заполните все поля');
        return;
    }

    // Собираем расширенные настройки (если есть)
    let advancedSettings = {};

    // Получаем выбранный пол
    const genderRadio = document.querySelector('input[name="gender"]:checked');
    if (genderRadio && genderRadio.value !== 'рандом') {
        advancedSettings.gender = genderRadio.value;
    }

    // Получаем выбранное мировоззрение
    const alignmentRadio = document.querySelector('input[name="alignment"]:checked');
    if (alignmentRadio && alignmentRadio.value !== 'рандом') {
        advancedSettings.alignment = alignmentRadio.value;
    }

    closeModal();
    setTimeout(() => {
        // Используем существующие функции
        window.npcRace = race;
        window.npcClass = npcClass;
        window.npcLevel = parseInt(level);

        // Добавляем отладочную информацию
        console.log('Mobile NPC Generation:', { race, npcClass, level, advancedSettings });

        // Вызываем генерацию напрямую с правильными параметрами
        fetchNpcFromAI(race, npcClass, '', parseInt(level), advancedSettings);
    }, 300);
}

// Функция для переключения темы в мобильном меню
function toggleThemeMobile() {
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.click();
    } else {
        // Альтернативный способ переключения темы
        const body = document.body;
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        body.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        // Обновляем иконку в меню
        const themeMenuIcon = document.getElementById('theme-menu-icon');
        if (themeMenuIcon) {
            themeMenuIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
        }
    }
}

// ===== ПРОСТЫЕ CSS СТИЛИ =====

const simpleMobileStyles = `
<style>
/* Простые мобильные стили - сохраняем дизайн ПК версии */
.mobile-device .parchment {
    margin: 60px 10px 20px 10px;
    padding: 15px;
}

/* Кнопка бокового меню */
.mobile-menu-button {
    position: fixed;
    top: 20px;
    left: 20px;
    width: 40px;
    height: 40px;
    background: var(--accent-primary);
    color: var(--bg-secondary);
    border: 2px solid var(--accent-secondary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    z-index: 1000;
    box-shadow: 0 2px 8px var(--shadow-primary);
    transition: all 0.3s ease;
}

.mobile-menu-button:hover {
    background: var(--accent-secondary);
    transform: scale(1.1);
    box-shadow: 0 4px 12px var(--shadow-secondary);
}

/* Боковое меню */
.mobile-side-menu {
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100vh;
    background: var(--bg-secondary);
    border-right: 2px solid var(--border-primary);
    z-index: 1001;
    transition: left 0.3s ease;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.mobile-side-menu.active {
    left: 0;
}

.side-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid var(--border-primary);
    background: var(--bg-tertiary);
}

.side-menu-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 18px;
}

.close-menu {
    background: none;
    border: none;
    font-size: 24px;
    color: var(--text-primary);
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-menu:hover {
    color: var(--accent-primary);
}

.side-menu-content {
    padding: 20px;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 15px;
    margin: 5px 0;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-primary);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-primary);
    font-size: 16px;
    transition: all 0.3s ease;
}

.menu-item:hover {
    background: var(--bg-quaternary);
    transform: translateX(5px);
    text-decoration: none;
    color: var(--text-primary);
}

.menu-icon {
    margin-right: 15px;
    font-size: 20px;
    width: 24px;
    text-align: center;
}

.menu-text {
    flex: 1;
}

.logout-item {
    background: var(--accent-danger);
    color: white;
    border-color: var(--accent-danger);
}

.logout-item:hover {
    background: var(--bg-secondary);
    color: var(--accent-danger);
}

/* Затемнение */
.mobile-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.mobile-menu-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Улучшенные поля ввода - сохраняем стили ПК */
.mobile-device input[type="text"],
.mobile-device input[type="number"],
.mobile-device select {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 2px solid var(--border-primary);
    border-radius: 8px;
    background: var(--bg-tertiary);
    color: var(--text-primary);
    margin: 5px 0;
}

.mobile-device input:focus,
.mobile-device select:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 5px rgba(0, 124, 186, 0.3);
}

/* Улучшенный чат - сохраняем стили ПК */
.mobile-device .chat-box {
    max-height: 60vh;
    overflow-y: auto;
    padding: 15px;
    background: var(--bg-tertiary);
    border-radius: 10px;
    border: 1px solid var(--border-primary);
    margin: 10px 0;
}

/* Улучшенные заметки - сохраняем стили ПК */
.mobile-device .notes-block {
    margin-top: 20px;
    padding: 15px;
    background: var(--bg-tertiary);
    border-radius: 10px;
    border: 1px solid var(--border-primary);
}

/* Улучшенные модальные окна - сохраняем стили ПК */
.mobile-device .modal {
    width: 95vw;
    max-width: 400px;
    height: auto;
    max-height: 90vh;
    margin: 5vh auto;
    border-radius: 15px;
    overflow-y: auto;
    background: var(--bg-secondary);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.mobile-device .modal-content {
    padding: 20px;
}

/* Улучшенная форма чата - сохраняем стили ПК */
.mobile-device form {
    display: flex;
    gap: 10px;
    margin: 15px 0;
    align-items: center;
}

.mobile-device form input[type="text"] {
    flex: 1;
    margin: 0;
}

.mobile-device form button {
    padding: 12px 20px;
    font-size: 16px;
    border-radius: 8px;
    border: none;
    background: var(--accent-primary);
    color: var(--bg-secondary);
    white-space: nowrap;
}

/* Улучшенные ссылки - сохраняем стили ПК */
.mobile-device .reset-link {
    color: var(--accent-primary);
    text-decoration: none;
    font-size: 14px;
    margin-left: 10px;
}

.mobile-device .reset-link:hover {
    text-decoration: underline;
}

/* Плавная прокрутка */
.mobile-device * {
    scroll-behavior: smooth;
}

/* Улучшенные заголовки - сохраняем стили ПК */
.mobile-device h1 {
    text-align: center;
    margin: 20px 0;
    color: var(--text-primary);
    font-size: 24px;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
}

/* Адаптивная сетка кнопок - сохраняем стили ПК */
.mobile-device .fast-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin: 15px 0;
}

/* Улучшенные подсказки - сохраняем стили ПК */
.mobile-device .hotkeys-hint {
    text-align: center;
    margin: 10px 0;
    font-size: 12px;
    color: var(--text-tertiary);
    opacity: 0.8;
}

/* Анимации */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.mobile-device .parchment {
    animation: fadeIn 0.5s ease-out;
}

/* Улучшенная доступность */
.mobile-device button:focus,
.mobile-device input:focus,
.mobile-device select:focus {
    outline: 2px solid var(--accent-primary);
    outline-offset: 2px;
}

/* Стили для сворачиваемых блоков в мобильной версии */
.mobile-device .npc-collapsible-header {
    cursor: pointer;
    user-select: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s ease;
    padding: 8px 0;
}

.mobile-device .npc-collapsible-header:hover {
    opacity: 0.8;
}

.mobile-device .npc-collapsible-header .toggle-icon {
    font-size: 0.8em;
    transition: transform 0.3s ease;
    margin-left: 8px;
}

.mobile-device .npc-collapsible-header.collapsed .toggle-icon {
    transform: rotate(-90deg);
}

.mobile-device .npc-collapsible-content {
    max-height: 1000px;
    overflow: hidden;
    transition: max-height 0.3s ease, opacity 0.3s ease;
    opacity: 1;
}

.mobile-device .npc-collapsible-content.collapsed {
    max-height: 0;
    opacity: 0;
}
</style>
`;

// Добавляем стили в head
document.head.insertAdjacentHTML('beforeend', simpleMobileStyles);

// --- Функция для переключения сворачиваемых технических параметров (мобильная версия) ---
function toggleTechnicalParams(headerElement) {
    const contentElement = headerElement.nextElementSibling;
    const isCollapsed = headerElement.classList.contains('collapsed');

    if (isCollapsed) {
        // Разворачиваем
        headerElement.classList.remove('collapsed');
        contentElement.classList.remove('collapsed');
    } else {
        // Сворачиваем
        headerElement.classList.add('collapsed');
        contentElement.classList.add('collapsed');
    }
}

// --- Функция переключения расширенных настроек в мобильной версии ---
function toggleMobileAdvancedSettings() {
    const panel = document.getElementById('mobile-advanced-settings');
    const button = document.querySelector('button[onclick="toggleMobileAdvancedSettings()"]');

    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        button.innerHTML = '⚙️ Скрыть расширенные настройки';
        button.style.background = 'var(--accent-warning)';
    } else {
        panel.style.display = 'none';
        button.innerHTML = '⚙️ Расширенные настройки';
        button.style.background = 'var(--accent-info)';
    }
}