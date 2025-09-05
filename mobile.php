<?php
session_start();
require_once 'auth.php';
require_once 'config.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Инициализируем языковую систему
$currentLang = getCurrentLanguage();
$translations = loadTranslations($currentLang);

// Загружаем переключатель языков
ob_start();
include 'language-switcher.php';
$languageSwitcher = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#a67c52">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= t('app_title') ?>">
    <title><?= t('app_title') ?> - <?= t('home_subtitle') ?></title>
    <link rel="icon" type="image/svg+xml" href="./favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=UnifrakturCook:wght@700&display=swap" rel="stylesheet">

    <style>
        /* Базовые стили и сброс */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
         :root {
            /* Мобильные размеры */
            --header-height: 60px;
            --nav-height: 50px;
            --content-padding: 16px;
            --border-radius: 12px;
            --border-radius-large: 16px;
            --spacing-xs: 8px;
            --spacing-sm: 12px;
            --spacing-md: 16px;
            --spacing-lg: 24px;
            --spacing-xl: 32px;
            /* Типографика */
            --font-primary: 'Roboto', system-ui, sans-serif;
            --font-display: 'UnifrakturCook', serif;
            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;
            /* Тени */
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.2);
            /* Z-index */
            --z-header: 1000;
            --z-nav: 900;
            --z-modal: 1100;
            --z-dropdown: 1000;
            /* Цвета */
            --bg-primary: #f8f9fa;
            --bg-secondary: #ffffff;
            --bg-tertiary: #e9ecef;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --text-muted: #adb5bd;
            --border-primary: #dee2e6;
            --border-secondary: #e9ecef;
            --accent-primary: #a67c52;
            --accent-secondary: #8b6914;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        /* Темная тема */
        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --bg-tertiary: #3a3a3a;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --text-muted: #808080;
            --border-primary: #404040;
            --border-secondary: #333333;
            --accent-primary: #d4af37;
            --accent-secondary: #b8941f;
        }
        
        /* Мистическая тема */
        [data-theme="mystic"] {
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a3a;
            --bg-tertiary: #2a2a4a;
            --text-primary: #e0e0ff;
            --text-secondary: #b0b0d0;
            --text-muted: #8080a0;
            --border-primary: #4a4a6a;
            --border-secondary: #3a3a5a;
            --accent-primary: #7209b7;
            --accent-secondary: #a67c52;
        }
        
        body {
            font-family: var(--font-primary);
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Заголовок */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 var(--content-padding);
            z-index: var(--z-header);
            box-shadow: var(--shadow-sm);
        }
        
        .logo {
            font-family: var(--font-display);
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--accent-primary);
        }
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .theme-toggle {
            background: none;
            border: none;
            font-size: var(--text-lg);
            cursor: pointer;
            padding: var(--spacing-xs);
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            background: var(--bg-tertiary);
        }
        
        /* Основной контент */
        .main-content {
            margin-top: var(--header-height);
            padding: var(--content-padding);
            min-height: calc(100vh - var(--header-height) - var(--nav-height));
        }
        
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
        /* Карточки */
        .card {
            background: var(--bg-secondary);
            border-radius: var(--border-radius-large);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-primary);
        }
        
        .card-title {
            font-size: var(--text-xl);
            font-weight: 600;
            margin-bottom: var(--spacing-md);
            color: var(--text-primary);
        }
        
        /* Кнопки */
        .btn {
            background: var(--accent-primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: var(--spacing-sm) var(--spacing-md);
            font-size: var(--text-base);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn:hover {
            background: var(--accent-secondary);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-group {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--spacing-sm);
        }
        
        .btn-group .btn {
            width: 100%;
            padding: var(--spacing-md);
            font-size: var(--text-lg);
        }
        
        /* Навигация */
        .nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--nav-height);
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-primary);
            display: flex;
            z-index: var(--z-nav);
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: var(--text-xs);
            transition: all 0.3s ease;
            padding: var(--spacing-xs);
        }
        
        .nav-item.active {
            color: var(--accent-primary);
        }
        
        .nav-item:hover {
            color: var(--accent-primary);
            background: var(--bg-tertiary);
        }
        
        .nav-icon {
            font-size: var(--text-lg);
            margin-bottom: 2px;
        }
        
        /* Утилиты */
        .text-center {
            text-align: center;
        }
        
        .mb-md {
            margin-bottom: var(--spacing-md);
        }
        
        .hidden {
            display: none !important;
        }
        
        /* Адаптивность */
        @media (min-width: 768px) {
            .btn-group {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .btn-group {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Анимации */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .content-section.active {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Переключатель языков в заголовке */
        .header .language-switcher {
            margin-right: var(--spacing-sm);
        }
        
        .header .lang-btn {
            padding: 6px 10px;
            font-size: var(--text-sm);
        }
    </style>
</head>

<body>
    <!-- Заголовок -->
    <header class="header">
        <div class="logo"><?= t('app_title') ?></div>
        <div class="header-controls">
            <?= $languageSwitcher ?>
            <button class="theme-toggle" onclick="cycleTheme()">🎨</button>
        </div>
    </header>

    <!-- Основной контент -->
    <main class="main-content">
        <!-- Главная страница -->
        <section id="home" class="content-section active">
            <div class="card">
                <h2 class="card-title">🎲 <?= t('home_welcome') ?></h2>
                <p class="text-center mb-md"><?= t('home_subtitle') ?></p>

                <div class="btn-group">
                    <button class="btn" onclick="showSection('characters')">
                        👤 <?= t('home_create_character') ?>
                    </button>
                    <button class="btn" onclick="showSection('enemies')">
                        ⚔️ <?= t('home_generate_enemies') ?>
                    </button>
                    <button class="btn" onclick="showSection('potions')">
                        🧪 <?= t('home_generate_potions') ?>
                    </button>
                    <button class="btn" onclick="showSection('combat')">
                        🗡️ <?= t('home_combat_system') ?>
                    </button>
                    <button class="btn" onclick="showSection('ai-chat')">
                        🤖 <?= t('home_ai_assistant') ?>
                    </button>
                    <button class="btn" onclick="showSection('notes')">
                        📝 <?= t('home_notes') ?>
                    </button>
                </div>
            </div>
        </section>

        <!-- Остальные секции будут добавлены позже -->
        <section id="characters" class="content-section">
            <div class="card">
                <h2 class="card-title"><?= t('character_generator') ?></h2>
                <p><?= t('loading') ?>...</p>
            </div>
        </section>

        <section id="enemies" class="content-section">
            <div class="card">
                <h2 class="card-title"><?= t('enemy_generator') ?></h2>
                <p><?= t('loading') ?>...</p>
            </div>
        </section>

        <section id="potions" class="content-section">
            <div class="card">
                <h2 class="card-title"><?= t('potion_generator') ?></h2>
                <p><?= t('loading') ?>...</p>
            </div>
        </section>

        <section id="combat" class="content-section">
            <div class="card">
                <h2 class="card-title"><?= t('combat_system') ?></h2>
                <p><?= t('loading') ?>...</p>
            </div>
        </section>

        <section id="ai-chat" class="content-section">
            <div class="card">
                <h2 class="card-title"><?= t('ai_chat') ?></h2>
                <p><?= t('loading') ?>...</p>
            </div>
        </section>

        <section id="notes" class="content-section">
            <div class="card">
                <h2 class="card-title"><?= t('notes_title') ?></h2>
                <p><?= t('loading') ?>...</p>
            </div>
        </section>
    </main>

    <!-- Навигация -->
    <nav class="nav">
        <a href="#home" class="nav-item active" onclick="showSection('home')">
            <div class="nav-icon">🏠</div>
            <div><?= t('nav_home') ?></div>
        </a>
        <a href="#characters" class="nav-item" onclick="showSection('characters')">
            <div class="nav-icon">👤</div>
            <div><?= t('nav_characters') ?></div>
        </a>
        <a href="#enemies" class="nav-item" onclick="showSection('enemies')">
            <div class="nav-icon">⚔️</div>
            <div><?= t('nav_enemies') ?></div>
        </a>
        <a href="#potions" class="nav-item" onclick="showSection('potions')">
            <div class="nav-icon">🧪</div>
            <div><?= t('nav_potions') ?></div>
        </a>
        <a href="#combat" class="nav-item" onclick="showSection('combat')">
            <div class="nav-icon">🗡️</div>
            <div><?= t('nav_combat') ?></div>
        </a>
        <a href="#ai-chat" class="nav-item" onclick="showSection('ai-chat')">
            <div class="nav-icon">🤖</div>
            <div><?= t('nav_ai_chat') ?></div>
        </a>
        <a href="#notes" class="nav-item" onclick="showSection('notes')">
            <div class="nav-icon">📝</div>
            <div><?= t('nav_notes') ?></div>
        </a>
    </nav>

    <script>
        // Функция переключения секций
        function showSection(sectionId) {
            // Скрываем все секции
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Показываем выбранную секцию
            document.getElementById(sectionId).classList.add('active');
            
            // Обновляем навигацию
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            const activeNavItem = document.querySelector(`[href="#${sectionId}"]`);
            if (activeNavItem) {
                activeNavItem.classList.add('active');
            }
        }
        
        // Функция переключения тем
        const themes = ['light', 'dark', 'mystic'];
        let currentThemeIndex = 0;
        
        function cycleTheme() {
            currentThemeIndex = (currentThemeIndex + 1) % themes.length;
            const newTheme = themes[currentThemeIndex];
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }
        
        // Загружаем сохраненную тему
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
            currentThemeIndex = themes.indexOf(savedTheme);
        }
        
        // Функция для получения текущего языка
        function getCurrentLanguage() {
            return '<?= $currentLang ?>';
        }
        
        // Функция для получения перевода
        function t(key, params = {}) {
            const translations = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
            let text = translations[key] || key;
            
            // Заменяем параметры
            for (const [param, value] of Object.entries(params)) {
                text = text.replace(new RegExp(`\\{\\{${param}\\}\\}`, 'g'), value);
            }
            
            return text;
        }
    </script>
</body>

</html>
