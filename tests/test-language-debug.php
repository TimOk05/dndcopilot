<?php
/**
 * Тест отладки переключения языков
 */

echo "<h1>🔍 Тест отладки переключения языков</h1>\n";

// Получаем язык из URL
$lang = $_GET['lang'] ?? 'ru';
echo "<p><strong>Текущий язык из URL:</strong> $lang</p>\n";

// Проверяем Language Service
echo "<h2>Проверка Language Service</h2>\n";
try {
    require_once 'api/language-service.php';
    $lang_service = new LanguageService();
    $detected_lang = $lang_service->getCurrentLanguage();
    echo "<p>✅ Language Service работает. Определенный язык: <strong>$detected_lang</strong></p>\n";
} catch (Exception $e) {
    echo "<p>❌ Ошибка Language Service: " . $e->getMessage() . "</p>\n";
}

// Проверяем сессию
echo "<h2>Проверка сессии</h2>\n";
session_start();
echo "<p>Сессия активна: " . (session_status() === PHP_SESSION_ACTIVE ? 'Да' : 'Нет') . "</p>\n";
echo "<p>Язык в сессии: " . ($_SESSION['dnd_app_language'] ?? 'Не установлен') . "</p>\n";

// Тест переводов
echo "<h2>Тест переводов</h2>\n";
$translations = [
    'ru' => [
        'welcome' => 'Добро пожаловать',
        'logout' => '🚪 Выйти',
        'character_generator' => '🎭 Генератор персонажей'
    ],
    'en' => [
        'welcome' => 'Welcome',
        'logout' => '🚪 Logout',
        'character_generator' => '🎭 Character Generator'
    ]
];

echo "<h3>Переводы для языка: $lang</h3>\n";
echo "<ul>\n";
foreach ($translations[$lang] as $key => $value) {
    echo "<li><strong>$key:</strong> $value</li>\n";
}
echo "</ul>\n";

// Тест элементов интерфейса
echo "<h2>Тест элементов интерфейса</h2>\n";
echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0;'>\n";
echo "<div class='welcome-text'>" . $translations[$lang]['welcome'] . " TimOk!</div>\n";
echo "<button class='logout-btn'>" . $translations[$lang]['logout'] . "</button>\n";
echo "<h3>" . $translations[$lang]['character_generator'] . "</h3>\n";
echo "</div>\n";

// JavaScript тест
echo "<h2>JavaScript тест</h2>\n";
echo "<div id='js-test'></div>\n";
echo "<button onclick='testJS()'>Тест JavaScript</button>\n";

echo "<h2>Навигация</h2>\n";
echo "<p><a href='?lang=ru'>🇷🇺 Русский</a> | <a href='?lang=en'>🇺🇸 English</a></p>\n";
echo "<p><a href='index.php?lang=ru'>Главная (RU)</a> | <a href='index.php?lang=en'>Главная (EN)</a></p>\n";
echo "<p><a href='test-interface-live.html'>Тест интерфейса в реальном времени</a></p>\n";

echo "<script>\n";
echo "function testJS() {\n";
echo "    const testDiv = document.getElementById('js-test');\n";
echo "    testDiv.innerHTML = '<p>✅ JavaScript работает!</p>';\n";
echo "    console.log('JavaScript тест выполнен');\n";
echo "}\n";
echo "</script>\n";

echo "<h2>✅ Тест завершен!</h2>\n";
?>
