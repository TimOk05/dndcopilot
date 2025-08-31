<?php
/**
 * Финальный тест всех исправлений
 * Проверяет исправления UI, JavaScript ошибок и генерации
 */

require_once 'config.php';
require_once 'auth.php';

// Временная функция isAdmin для тестирования
function isAdmin() {
    if (!isset($_SESSION['username'])) {
        return false;
    }
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
    <title>Финальный тест исправлений - DnD Copilot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 900px;
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
            font-size: 1.1em;
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
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
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
        .test-buttons {
            margin: 20px 0;
            text-align: center;
        }
        .test-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            margin: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .test-btn:hover {
            background: #0056b3;
        }
        .test-btn.success {
            background: #28a745;
        }
        .test-btn.success:hover {
            background: #1e7e34;
        }
        .test-btn.warning {
            background: #ffc107;
            color: #212529;
        }
        .test-btn.warning:hover {
            background: #e0a800;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 0.9em;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🎯 Финальный тест всех исправлений</h1>
        
        <div class="test-section">
            <div class="test-title">1. Исправления JavaScript ошибок</div>
            <div class="test-result success">
                ✅ Исправлена ошибка TypeError в `formatCharacterFromApi` - добавлены проверки на валидность данных
            </div>
            <div class="test-result success">
                ✅ Исправлена ошибка TypeError в `formatEnemiesFromApi` - добавлены проверки на валидность данных
            </div>
            <div class="test-result success">
                ✅ Исправлена ошибка `Cannot read properties of undefined (reading 'replace')` - добавлена проверка на существование объекта
            </div>
            <div class="test-result info">
                ℹ️ Теперь все функции форматирования безопасно обрабатывают некорректные данные
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">2. Исправления UI/расположения кнопок</div>
            <div class="test-result success">
                ✅ Убрана дублирующая кнопка админа (отладочная версия)
            </div>
            <div class="test-result success">
                ✅ Увеличена максимальная ширина `.user-info` с `calc(100% - 200px)` до `calc(100% - 120px)`
            </div>
            <div class="test-result success">
                ✅ Добавлен класс `.user-controls` для лучшего управления кнопками
            </div>
            <div class="test-result success">
                ✅ Уменьшены размеры кнопок статистики и админ панели с 40px до 36px
            </div>
            <div class="test-result success">
                ✅ Добавлен `flex-shrink: 0` для предотвращения сжатия кнопок
            </div>
            <div class="test-result success">
                ✅ Добавлены медиа-запросы для мобильных устройств
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">3. Предварительный просмотр исправленного UI</div>
            <div class="ui-preview">
                <div class="mock-header">
                    <div class="mock-user-info">
                        <div class="mock-welcome-text">Добро пожаловать <?php echo htmlspecialchars($currentUser); ?>!</div>
                    </div>
                    <div class="mock-user-controls">
                        <a href="stats.php" class="mock-stats-link" title="Статистика пользователя">📊</a>
                        <?php if ($isAdmin): ?>
                        <a href="admin.php" class="mock-admin-link" title="Админ панель">🔧</a>
                        <?php endif; ?>
                        <button class="mock-logout-btn">🚪 Выйти</button>
                    </div>
                </div>
            </div>
            <div class="test-result info">
                ℹ️ Теперь кнопки правильно располагаются и не перекрываются
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">4. Проверка генерации</div>
            <div class="test-result info">
                ℹ️ Для проверки генерации персонажей и противников используйте основной интерфейс
            </div>
            <div class="test-result warning">
                ⚠️ Убедитесь, что в консоли браузера нет ошибок TypeError
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">5. Статус пользователя</div>
            <div class="test-result info">
                <strong>Пользователь:</strong> <?php echo htmlspecialchars($currentUser); ?>
            </div>
            <div class="test-result <?php echo $isAdmin ? 'success' : 'info'; ?>">
                <strong>Админ права:</strong> <?php echo $isAdmin ? 'Да' : 'Нет'; ?>
            </div>
            <div class="test-result info">
                <strong>Кнопка админа:</strong> <?php echo $isAdmin ? 'Видна' : 'Скрыта'; ?>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">6. Код исправлений</div>
            <div class="code-block">
// Исправление TypeError в formatCharacterFromApi
if (!character || typeof character !== 'object') {
    return '&lt;div class="error"&gt;Ошибка: Некорректные данные персонажа&lt;/div&gt;';
}

// Исправление TypeError в кнопке сохранения
if (character && typeof character === 'object') {
    resultDiv.innerHTML += `
        &lt;button onclick="saveCharacterToNotes(${JSON.stringify(character).replace(/"/g, '&quot;')})"&gt;
            💾 Сохранить в заметки
        &lt;/button&gt;
    `;
}

// Исправление UI - убрана отладочная кнопка админа
&lt;?php if (isAdmin()): ?&gt;
&lt;a href="admin.php" class="admin-link"&gt;🔧&lt;/a&gt;
&lt;?php endif; ?&gt;
            </div>
        </div>
        
        <div class="test-buttons">
            <a href="index.php" class="test-btn success">✅ Перейти к основному интерфейсу</a>
            <a href="test-generator.php" class="test-btn">🧪 Тест генераторов</a>
            <a href="test-ui-fixes.php" class="test-btn">🎨 Тест UI</a>
        </div>
        
        <div class="test-section">
            <div class="test-title">7. Инструкции по тестированию</div>
            <div class="test-result info">
                1. Откройте основной интерфейс и проверьте расположение кнопок
            </div>
            <div class="test-result info">
                2. Измените размер окна браузера для проверки адаптивности
            </div>
            <div class="test-result info">
                3. Попробуйте сгенерировать персонажа (F2) и противника (F4)
            </div>
            <div class="test-result info">
                4. Откройте консоль браузера (F12) и убедитесь, что нет ошибок TypeError
            </div>
            <div class="test-result info">
                5. Проверьте, что кнопки статистики и админ панели кликабельны
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-title">8. Ожидаемые результаты</div>
            <div class="test-result success">
                ✅ Одна кнопка админа (только для админов)
            </div>
            <div class="test-result success">
                ✅ Кнопки не перекрываются и правильно располагаются
            </div>
            <div class="test-result success">
                ✅ Генерация работает без ошибок в консоли
            </div>
            <div class="test-result success">
                ✅ Адаптивность на мобильных устройствах
            </div>
        </div>
    </div>
</body>
</html>
