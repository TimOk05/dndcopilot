<?php
/**
 * Тест кнопок интерфейса
 */

echo "<h1>🔘 Тест кнопок интерфейса</h1>\n";

// Тест 1: Проверка функций
echo "<h2>Тест 1: Проверка функций</h2>\n";
try {
    require_once 'auth.php';
    echo "✅ auth.php загружен<br>\n";
    
    if (function_exists('isAdmin')) {
        echo "✅ Функция isAdmin существует<br>\n";
        $isAdmin = isAdmin();
        echo "Пользователь админ: " . ($isAdmin ? 'Да' : 'Нет') . "<br>\n";
    } else {
        echo "❌ Функция isAdmin не найдена<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка в auth.php: " . $e->getMessage() . "<br>\n";
}

// Тест 2: Проверка сессии
echo "<h2>Тест 2: Проверка сессии</h2>\n";
session_start();
echo "Сессия активна: " . (session_status() === PHP_SESSION_ACTIVE ? 'Да' : 'Нет') . "<br>\n";
echo "Имя пользователя: " . ($_SESSION['username'] ?? 'Не установлено') . "<br>\n";
echo "Роль пользователя: " . ($_SESSION['role'] ?? 'Не установлена') . "<br>\n";

// Тест 3: Проверка кнопок
echo "<h2>Тест 3: Проверка кнопок</h2>\n";
echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>\n";
echo "<h3>Кнопка статистики:</h3>\n";
echo "<a href='stats.php' class='stats-link' style='display: block !important; visibility: visible !important; opacity: 1 !important; background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>📊 Статистика</a>\n";
echo "</div>\n";

echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>\n";
echo "<h3>Кнопка админ панели:</h3>\n";
if (isAdmin()) {
    echo "<a href='admin.php' class='admin-link' style='display: block !important; visibility: visible !important; opacity: 1 !important; background: #ffc107; color: black; padding: 10px; text-decoration: none; border-radius: 5px;'>🔧 Админ панель</a>\n";
} else {
    echo "<span style='color: gray;'>Кнопка админ панели скрыта (пользователь не админ)</span>\n";
}
echo "</div>\n";

echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>\n";
echo "<h3>Кнопка выхода:</h3>\n";
echo "<button class='logout-btn' onclick='alert(\"Тест кнопки выхода\")' style='background: #dc3545; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer;'>🚪 Выйти</button>\n";
echo "</div>\n";

echo "<h2>✅ Тесты завершены!</h2>\n";
echo "<p><a href='index.php'>Вернуться на главную</a></p>\n";
?>
