<?php
require_once '../config/config.php';
require_once '../public/api/users.php';

// Запускаем сессию
configureSession();

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$userData = getCurrentUserData();
$users = loadUsers();

// Статистика приложения
$totalUsers = count($users);
$totalLogins = 0;
$activeUsers = 0;

foreach ($users as $user) {
    if (isset($user['login_count'])) {
        $totalLogins += $user['login_count'];
    }
    if (isset($user['last_login'])) {
        $lastLogin = strtotime($user['last_login']);
        if ($lastLogin > (time() - 86400)) { // Активны за последние 24 часа
            $activeUsers++;
        }
    }
}

// Статистика пользователя
if ($userData) {
    $userLoginCount = $userData['login_count'] ?? 0;
    $userCreatedAt = $userData['created_at'] ?? 'Неизвестно';
    $userLastLogin = $userData['last_login'] ?? 'Никогда';
} else {
    $userLoginCount = 0;
    $userCreatedAt = 'Неизвестно';
    $userLastLogin = 'Никогда';
}

// Вычисляем время с регистрации
$daysSinceRegistration = 0;
if ($userCreatedAt !== 'Неизвестно') {
    $created = strtotime($userCreatedAt);
    $daysSinceRegistration = floor((time() - $created) / 86400);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - DnD Copilot</title>
    <style>
        :root {
            /* Светлая тема */
            --bg-primary: #e8d8b0;
            --bg-secondary: #f0e6c0;
            --bg-tertiary: #e0d0a0;
            --text-primary: #2d1b00;
            --text-secondary: #3d2a0a;
            --text-tertiary: #7c4a02;
            --accent-primary: #a67c52;
            --accent-secondary: #7c4a02;
            --border-primary: #a67c52;
            --shadow-primary: #0002;
        }
        
        [data-theme="dark"] {
            /* Исправленные цвета тёмной темы для соответствия основному интерфейсу */
            --bg-primary: #050505;
            --bg-secondary: #0a0a0a;
            --bg-tertiary: #0f0f0f;
            --text-primary: #e0e0e0;
            --text-secondary: #cc9999;
            --text-tertiary: #bb8888;
            --accent-primary: #660000;
            --accent-secondary: #440000;
            --border-primary: #660000;
            --shadow-primary: rgba(102, 0, 0, 0.2);
        }
        
        [data-theme="medium"] {
            /* Средняя тема (коричневая) */
            --bg-primary: #2d1810;
            --bg-secondary: #3d2418;
            --bg-tertiary: #4d2a20;
            --text-primary: #f4e4d6;
            --text-secondary: #e6d4c0;
            --text-tertiary: #d8c4aa;
            --accent-primary: #d2691e;
            --accent-secondary: #ff8c00;
            --border-primary: #d2691e;
            --shadow-primary: rgba(210, 105, 30, 0.2);
        }
        
        [data-theme="mystic"] {
            /* Мистическая тема (фиолетовая) */
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a3a;
            --bg-tertiary: #252550;
            --text-primary: #e8e8ff;
            --text-secondary: #c8c8ff;
            --text-tertiary: #a8a8ff;
            --accent-primary: #6b46c1;
            --accent-secondary: #553c9a;
            --border-primary: #6b46c1;
            --shadow-primary: rgba(107, 70, 193, 0.2);
        }
        
        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }
        
        .theme-switcher {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .theme-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .theme-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--bg-secondary);
            border: 2px solid var(--border-primary);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            min-width: 150px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1001;
        }
        
        .theme-dropdown:hover .theme-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .theme-option {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--border-primary);
        }
        
        .theme-option:last-child {
            border-bottom: none;
        }
        
        .theme-option:hover {
            background: var(--accent-primary);
            color: white;
        }
        
        .theme-option.active {
            background: var(--accent-primary);
            color: white;
        }
        
        .theme-icon {
            margin-right: 8px;
            font-size: 1.1em;
        }
        
        .theme-name {
            font-size: 0.9em;
            font-weight: 500;
        }
        

        
        .theme-btn {
            background: var(--accent-primary);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 5px;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        
        .theme-btn:hover {
            background: var(--accent-secondary);
        }
        
        .theme-btn.active {
            background: var(--accent-secondary);
            box-shadow: 0 0 10px var(--accent-primary);
        }
        
        .stats-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--bg-secondary) url('https://www.transparenttextures.com/patterns/checkered-pattern.png');
            border-radius: 10px;
            box-shadow: 0 4px 20px var(--shadow-primary);
            overflow: hidden;
            border: 2px solid var(--border-primary);
        }
        
        /* Сетка для средней темы (коричневая) */
        [data-theme="medium"] .stats-container {
            background: var(--bg-secondary) url('https://www.transparenttextures.com/patterns/dark-wood.png');
        }
        
        /* Сетка для тёмной темы */
        [data-theme="dark"] .stats-container {
            background: var(--bg-secondary) url('https://www.transparenttextures.com/patterns/dark-mosaic.png');
        }
        
        /* Сетка для мистической темы */
        [data-theme="mystic"] .stats-container {
            background: var(--bg-secondary) url('https://www.transparenttextures.com/patterns/dark-mosaic.png');
        }
        
        .stats-header {
            background: var(--accent-primary);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .stats-header h1 {
            margin: 0;
            font-size: 2em;
        }
        
        .stats-content {
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-tertiary) url('https://www.transparenttextures.com/patterns/checkered-pattern.png');
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--border-primary);
        }
        
        /* Сетка для средней темы (коричневая) */
        [data-theme="medium"] .stat-card {
            background: var(--bg-tertiary) url('https://www.transparenttextures.com/patterns/dark-wood.png');
        }
        
        /* Сетка для тёмной темы */
        [data-theme="dark"] .stat-card {
            background: var(--bg-tertiary) url('https://www.transparenttextures.com/patterns/dark-mosaic.png');
        }
        
        /* Сетка для мистической темы */
        [data-theme="mystic"] .stat-card {
            background: var(--bg-tertiary) url('https://www.transparenttextures.com/patterns/dark-mosaic.png');
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 1.1em;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: var(--text-primary);
            border-bottom: 2px solid var(--accent-primary);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .user-info {
            background: var(--bg-tertiary) url('https://www.transparenttextures.com/patterns/checkered-pattern.png');
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--accent-primary);
        }
        
        /* Сетка для средней темы (коричневая) */
        [data-theme="medium"] .user-info {
            background: var(--bg-tertiary) url('https://www.transparenttextures.com/patterns/dark-wood.png');
        }
        
        /* Сетка для тёмной темы */
        [data-theme="dark"] .user-info {
            background: var(--bg-tertiary) url('https://www.transparenttextures.com/patterns/dark-mosaic.png');
        }
        
        /* Сетка для мистической темы */
        [data-theme="mystic"] .user-info {
            background: var(--bg-tertiary) url('https://www.transparenttextures.com/patterns/dark-mosaic.png');
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-primary);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            color: var(--text-primary);
        }
        
        .info-value {
            color: var(--text-secondary);
        }
        
        .google-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            margin-left: 8px;
        }
        
        .google-badge img {
            width: 16px;
            height: 16px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--accent-primary);
            text-decoration: none;
            padding: 10px 20px;
            background: var(--bg-tertiary);
            border-radius: 5px;
            border: 1px solid var(--border-primary);
            transition: all 0.3s ease;
        }
        .back-link:hover {
            background: var(--accent-primary);
            color: white;
        }
        .achievement {
            background: var(--accent-secondary);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: center;
        }
        .achievement h3 {
            margin: 0 0 10px 0;
        }
    </style>
</head>
<body>
    <!-- Переключатель тем -->
    <div class="theme-switcher">
        <div class="theme-dropdown">
            <button class="theme-btn active" id="theme-toggle">
                <span class="theme-icon">☀️</span>
            </button>
            <div class="theme-menu" id="theme-menu">
                <div class="theme-option active" data-theme="light">
                    <span class="theme-icon">☀️</span>
                    <span class="theme-name">Светлая</span>
                </div>
                <div class="theme-option" data-theme="medium">
                    <span class="theme-icon">🌅</span>
                    <span class="theme-name">Средняя</span>
                </div>
                <div class="theme-option" data-theme="dark">
                    <span class="theme-icon">🌙</span>
                    <span class="theme-name">Тёмная</span>
                </div>
                <div class="theme-option" data-theme="mystic">
                    <span class="theme-icon">🔮</span>
                    <span class="theme-name">Мистическая</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="stats-container">
        <div class="stats-header">
            <h1>📊 Статистика</h1>
            <p>Ваша активность и статистика приложения</p>
        </div>
        
        <div class="stats-content">
            <a href="index.php" class="back-link">← Вернуться к приложению</a>
            
            <!-- Статистика пользователя -->
            <div class="section">
                <h2>👤 Ваша статистика</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $userLoginCount; ?></div>
                        <div class="stat-label">Количество входов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $daysSinceRegistration; ?></div>
                        <div class="stat-label">Дней с регистрации</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php echo $userLoginCount > 0 ? round($userLoginCount / max(1, $daysSinceRegistration), 1) : 0; ?>
                        </div>
                        <div class="stat-label">Входов в день</div>
                    </div>
                </div>
                
                <div class="user-info">
                    <div class="info-row">
                        <span class="info-label">Имя пользователя:</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($currentUser); ?>
                            <?php if (isset($userData['auth_method']) && $userData['auth_method'] === 'google'): ?>
                                <span class="google-badge" title="Вход через Google аккаунт">
                                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAxOCAxOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE3LjY0IDkuMjA0NTVDMTcuNjQgOC41NjY0IDE3LjU4MjcgNy45NTI3MyAxNy40NzI3IDcuMzYzNjRIMTlWMTBIMTcuNjRWOS4yMDQ1NVoiIGZpbGw9IiNGQjQwMzEiLz4KPHBhdGggZD0iTTkgMTguMDAwMUMxMS40MyAxOC4wMDAxIDEzLjQ2NzMgMTcuMTk0NSAxNC45NjM2IDE1Ljc4MzZMMTIuNzA5MSAxNC4wNjM2QzExLjk3MjcgMTQuNzYzNiAxMC44NzI3IDE1LjIyNzMgOS41IDE1LjIyNzNDNy4xNDU0NSAxNS4yMjczIDUuMjcyNzMgMTMuNjM2NCA0LjU0NTQ1IDExLjU0NTVIMi4wOTA5MVYxMy44MTgySDQuNTQ1NDVDNC44ODE4MiAxNC42MzY0IDUuNjM2MzYgMTUuMjcyNyA2LjU5MDkxIDE1LjI3MjdDNi44MTgxOCAxNS4yNzI3IDcuMDMxODIgMTUuMjMxOCA3LjIzMTgyIDE1LjE1NDVDNy40MzE4MiAxNS4wNzcyIDcuNjE4MTggMTQuOTY4MiA3Ljc4MTgyIDE0LjgyNzNDNy45NDU0NSAxNC42ODY0IDguMDgxODIgMTQuNTE4MiA4LjE5MDkxIDE0LjMyNzNDOC4zIDE0LjEzNjQgOC4zNjM2NCAxMy45MjcyIDguMzkwOTEgMTMuNzA5MUM4LjQxODE4IDEzLjQ5MDkgOC40MTgxOCAxMy4yNjM2IDguMzkwOTEgMTMuMDQ1NUg4LjM2MzY0SDQuNTQ1NDVDNC41NDU0NSAxMi45NTQ1IDQuNTQ1NDUgMTIuODYzNiA0LjU0NTQ1IDEyLjc3MjdDNC41NDU0NSAxMi42ODE4IDQuNTQ1NDUgMTIuNTkwOSA0LjU0NTQ1IDEyLjVIMTlWMTQuNUg4LjM5MDkxQzguMzYzNjQgMTQuMjgxOCA4LjMgMTQuMDcyNyA4LjE5MDkxIDEzLjg4MThDOC4wODE4MiAxMy42OTA5IDcuOTQ1NDUgMTMuNTIyNyA3Ljc4MTgyIDEzLjM4MThDNy42MTgxOCAxMy4yNDA5IDcuNDMxODIgMTMuMTMxOCA3LjIzMTgyIDEzLjA1NDVDNy4wMzE4MiAxMi45NzcyIDYuODE4MTggMTIuOTM2NCA2LjU5MDkxIDEyLjkzNjRDNi4zNjM2NCAxMi45MzY0IDYuMTUwOTEgMTIuOTc3MiA1Ljk1MDkxIDEzLjA1NDVDNS43NTA5MSAxMy4xMzE4IDUuNTY4MTggMTMuMjQwOSA1LjQwNDU1IDEzLjM4MThDNS4yNDA5MSAxMy41MjI3IDUuMTA0NTUgMTMuNjkwOSA0Ljk5NTQ1IDEzLjg4MThDNC44ODYzNiAxNC4wNzI3IDQuODIyNzMgMTQuMjgxOCA0Ljc5NTQ1IDE0LjVIMi4wOTA5MVYxNi43NzI3SDQuNTQ1NDVDNS4yNzI3MyAxNC42MzY0IDcuMTQ1NDUgMTMuMDQ1NSA5LjUgMTMuMDQ1NUMxMC44NzI3IDEzLjA0NTUgMTEuOTcyNyAxMy41MDkxIDEyLjcwOTEgMTQuMjA5MUwxNC45NjM2IDEyLjQ4OTFDMTMuNDY3MyAxMS4wNzgxIDExLjQzIDEwLjI3MjcgOSAxMC4yNzI3QzYuNTY5MDkgMTAuMjcyNyA0LjUzMTgyIDExLjA3ODEgMy4wMzYzNiAxMi40ODkxQzEuNTQwOTEgMTMuODk5MSAwLjc3MjcyNyAxNS44MzE4IDAuNzcyNzI3IDE4SDBWMTYuNzI3M0MwIDE0LjQ1NDUgMC43NzI3MjcgMTIuNTIyNyAyLjI2ODE4IDEwLjk5MDlDMy43NjM2NCA5LjQ1OTA5IDUuNzY5MDkgOC42ODE4MiA4LjI4MTgyIDguNjgxODJDMTAuNzk0NSA4LjY4MTgyIDEyLjgwMTggOS40NTkwOSAxNC4yOTczIDEwLjk5MDlDMTUuNzkyNyAxMi41MjI3IDE2LjU0NTUgMTQuNDU0NSAxNi41NDU1IDE2Ljc3MjNWMThIMTlWMTYuNzI3M0MxOSAxNC40NTQ1IDE4LjIyNzMgMTIuNTIyNyAxNi43MzE4IDEwLjk5MDlDMTUuMjM2NCA5LjQ1OTA5IDEzLjIzMDkgOC42ODE4MiAxMC43MTgyIDguNjgxODJaIiBmaWxsPSIjRkZDMTA3Ii8+CjxwYXRoIGQ9Ik0xNy42NCA5LjIwNDU1QzE3LjY0IDguNTY2NCAxNy41ODI3IDcuOTUyNzMgMTcuNDcyNyA3LjM2MzY0SDE5VjEwSDE3LjY0VjkuMjA0NTVaIiBmaWxsPSIjRkI0MDMxIi8+CjxwYXRoIGQ9Ik0xNy42NCA5LjIwNDU1QzE3LjY0IDguNTY2NCAxNy41ODI3IDcuOTUyNzMgMTcuNDcyNyA3LjM2MzY0SDE5VjEwSDE3LjY0VjkuMjA0NTVaIiBmaWxsPSIjRkI0MDMxIi8+Cjwvc3ZnPgo=" alt="Google" width="16" height="16">
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Дата регистрации:</span>
                        <span class="info-value"><?php echo htmlspecialchars($userCreatedAt); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Последний вход:</span>
                        <span class="info-value"><?php echo htmlspecialchars($userLastLogin); ?></span>
                    </div>
                </div>
                
                <!-- Достижения -->
                <?php if ($userLoginCount >= 10): ?>
                    <div class="achievement">
                        <h3>🏆 Постоянный пользователь</h3>
                        <p>Вы вошли в систему более 10 раз!</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($daysSinceRegistration >= 7): ?>
                    <div class="achievement">
                        <h3>📅 Неделя с нами</h3>
                        <p>Вы используете приложение уже неделю!</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($userLoginCount >= 5 && $daysSinceRegistration >= 3): ?>
                    <div class="achievement">
                        <h3>🎯 Активный игрок</h3>
                        <p>Вы регулярно используете DnD Copilot!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Статистика приложения -->
            <div class="section">
                <h2>🌐 Статистика приложения</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalUsers; ?></div>
                        <div class="stat-label">Всего пользователей</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $activeUsers; ?></div>
                        <div class="stat-label">Активных за 24 часа</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalLogins; ?></div>
                        <div class="stat-label">Всего входов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php echo $totalUsers > 0 ? round($totalLogins / $totalUsers, 1) : 0; ?>
                        </div>
                        <div class="stat-label">Среднее входов на пользователя</div>
                    </div>
                </div>
            </div>
            
            <!-- Советы -->
            <div class="section">
                <h2>💡 Советы по использованию</h2>
                <div class="user-info">
                    <p><strong>🎲 Бросок костей:</strong> Используйте F1 для быстрого доступа к броскам костей</p>
                    <p><strong>🗣️ Генерация NPC:</strong> Нажмите F2 для создания новых персонажей</p>
                    <p><strong>⚡ Инициатива:</strong> F3 поможет управлять инициативой в бою</p>
                    <p><strong>💬 Чат:</strong> Ctrl+Enter для быстрой отправки сообщений</p>
                    <p><strong>🌙 Тема:</strong> Переключайте между светлой, средней и темной темами</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Функция для переключения тем
        const themeOptions = document.querySelectorAll('.theme-option');
        const themeIcon = document.querySelector('#theme-toggle .theme-icon');
        
        themeOptions.forEach(option => {
            option.addEventListener('click', function() {
                const selectedTheme = this.getAttribute('data-theme');
                document.documentElement.setAttribute('data-theme', selectedTheme);
                localStorage.setItem('theme', selectedTheme);
                updateThemeIcon(selectedTheme);
                updateActiveOption(selectedTheme);
            });
        });
        
        function updateActiveOption(theme) {
            themeOptions.forEach(option => {
                option.classList.remove('active');
                if (option.getAttribute('data-theme') === theme) {
                    option.classList.add('active');
                }
            });
        }
        
        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.textContent = '🌙';
            } else if (theme === 'medium') {
                themeIcon.textContent = '🌅';
            } else if (theme === 'mystic') {
                themeIcon.textContent = '🔮';
            } else {
                themeIcon.textContent = '☀️';
            }
        }
        
        // Загружаем сохраненную тему при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
            updateActiveOption(savedTheme);
        });
    </script>
</body>
</html>
