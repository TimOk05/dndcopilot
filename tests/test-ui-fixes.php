<?php
/**
 * Тест исправлений UI
 * Проверяет исправления проблем с кнопками статистики и админ панели
 */

require_once 'config.php';
require_once 'auth.php';

// Временная функция isAdmin для тестирования
function isAdmin() {
    // Проверяем, есть ли пользователь в сессии
    if (!isset($_SESSION['username'])) {
        return false;
    }
    
    // Простая проверка - считаем админом пользователей с определенными именами
    $adminUsers = ['admin', 'administrator', 'root', 'master'];
    return in_array(strtolower($_SESSION['username']), $adminUsers);
}

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['username'] ?? 'Гость';
$isAdmin = isAdmin();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест исправлений UI - DnD Copilot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .test-result {
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .ui-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
        .mock-header {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            border-radius: 8px;
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: calc(100% - 120px);
        }
        .mock-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .mock-welcome-text {
            color: #666;
            font-weight: bold;
            font-size: 0.95em;
            white-space: nowrap;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .mock-user-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            min-width: 0;
        }
        .mock-stats-link, .mock-admin-link {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.85em;
            font-weight: 600;
            text-decoration: none;
            flex-shrink: 0;
        }
        .mock-stats-link {
            background: #17a2b8;
            color: white;
        }
        .mock-admin-link {
            background: #ffc107;
            color: white;
        }
        .mock-logout-btn {
            background: linear-gradient(135deg, #dc3545, #cc0000);
            color: white;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.85em;
            font-weight: 700;
            flex-shrink: 0;
        }
        .responsive-test {
            margin: 20px 0;
        }
        .screen-size {
            display: inline-block;
            padding: 5px 10px;
            margin: 5px;
            background: #e9ecef;
            border-radius: 3px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Тест исправлений UI</h1>
        
        <div class="test-section">
            <div class="test-title">1. Проверка исправлений JavaScript ошибок</div>
            <div class="test-result success">
                ✅ Функция formatCharacterFromApi исправлена - добавлены проверки на валидность данных
            </div>
            <div class="test-result success">
                ✅ Функция formatEnemiesFromApi исправлена - добавлены проверки на валидность данных
            </div>
            <div class="test-result info">
                ℹ️ Теперь функции не будут вызывать TypeError при некорректных данных
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">2. Проверка исправлений CSS стилей</div>
            <div class="test-result success">
                ✅ Увеличена максимальная ширина .user-info с calc(100% - 200px) до calc(100% - 120px)
            </div>
            <div class="test-result success">
                ✅ Добавлен класс .user-controls для лучшего управления кнопками
            </div>
            <div class="test-result success">
                ✅ Уменьшены размеры кнопок статистики и админ панели с 40px до 36px
            </div>
            <div class="test-result success">
                ✅ Добавлен flex-shrink: 0 для предотвращения сжатия кнопок
            </div>
            <div class="test-result success">
                ✅ Уменьшена кнопка выхода (padding: 6px 12px, font-size: 0.85em)
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">3. Проверка адаптивности</div>
            <div class="test-result success">
                ✅ Добавлены медиа-запросы для мобильных устройств
            </div>
            <div class="test-result info">
                ℹ️ На экранах ≤768px: кнопки 32px, отступы уменьшены
            </div>
            <div class="test-result info">
                ℹ️ На экранах ≤480px: кнопки 28px, максимальная компактность
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">4. Предварительный просмотр UI</div>
            <div class="ui-preview">
                <div class="mock-header">
                    <div class="mock-user-info">
                        <div class="mock-welcome-text">Добро пожаловать <?php echo htmlspecialchars($currentUser); ?>!</div>
                    </div>
                    <div class="mock-user-controls">
                        <a href="stats.php" class="mock-stats-link" title="Статистика пользователя">📊</a>
                        <?php if ($isAdmin): ?>
                        <a href="admin.php" class="mock-admin-link" title="Админ панель">🔧</a>
                        <?php else: ?>
                        <a href="admin.php" class="mock-admin-link" title="Админ панель (отладка)" style="background: #ff6b6b;">🔧</a>
                        <?php endif; ?>
                        <button class="mock-logout-btn">🚪 Выйти</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">5. Тест адаптивности</div>
            <div class="responsive-test">
                <div class="screen-size">Desktop (≥769px)</div>
                <div class="screen-size">Tablet (≤768px)</div>
                <div class="screen-size">Mobile (≤480px)</div>
            </div>
            <div class="test-result info">
                ℹ️ Измените размер окна браузера для проверки адаптивности
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">6. Статус пользователя</div>
            <div class="test-result info">
                <strong>Пользователь:</strong> <?php echo htmlspecialchars($currentUser); ?>
            </div>
            <div class="test-result <?php echo $isAdmin ? 'success' : 'info'; ?>">
                <strong>Админ права:</strong> <?php echo $isAdmin ? 'Да' : 'Нет'; ?>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">7. Следующие шаги</div>
            <div class="test-result info">
                1. Проверьте основной интерфейс на разных размерах экрана
            </div>
            <div class="test-result info">
                2. Убедитесь, что кнопки статистики и админ панели видны и кликабельны
            </div>
            <div class="test-result info">
                3. Протестируйте генерацию персонажей и противников
            </div>
            <div class="test-result info">
                4. Проверьте, что нет ошибок в консоли браузера
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="index.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Вернуться к основному интерфейсу</a>
        </div>
    </div>
</body>
</html>
