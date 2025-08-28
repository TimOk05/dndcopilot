<?php
require_once 'config.php';

echo "<h1>Тест Google OAuth настроек</h1>";

// Проверяем константы
echo "<h2>Константы:</h2>";
echo "GOOGLE_CLIENT_ID: " . (defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : 'НЕ ОПРЕДЕЛЕНА') . "<br>";
echo "GOOGLE_CLIENT_SECRET: " . (defined('GOOGLE_CLIENT_SECRET') ? '***' . substr(GOOGLE_CLIENT_SECRET, -4) : 'НЕ ОПРЕДЕЛЕНА') . "<br>";

// Проверяем переменные окружения
echo "<h2>Переменные окружения:</h2>";
echo "GOOGLE_CLIENT_ID: " . (getenv('GOOGLE_CLIENT_ID') ?: 'НЕ НАЙДЕН') . "<br>";
echo "GOOGLE_CLIENT_SECRET: " . (getenv('GOOGLE_CLIENT_SECRET') ? '***' . substr(getenv('GOOGLE_CLIENT_SECRET'), -4) : 'НЕ НАЙДЕН') . "<br>";

// Проверяем .env файл
echo "<h2>Файл .env:</h2>";
if (file_exists('.env')) {
    echo ".env файл существует<br>";
    $env_content = file_get_contents('.env');
    if (strpos($env_content, 'GOOGLE_CLIENT_ID') !== false) {
        echo "GOOGLE_CLIENT_ID найден в .env<br>";
    } else {
        echo "GOOGLE_CLIENT_ID НЕ найден в .env<br>";
    }
    if (strpos($env_content, 'GOOGLE_CLIENT_SECRET') !== false) {
        echo "GOOGLE_CLIENT_SECRET найден в .env<br>";
    } else {
        echo "GOOGLE_CLIENT_SECRET НЕ найден в .env<br>";
    }
} else {
    echo ".env файл НЕ существует<br>";
}

// Тест Google OAuth класса
echo "<h2>Тест Google OAuth:</h2>";
if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID) {
    echo "✅ Google OAuth настроен правильно<br>";
    echo "<a href='google-auth.php'>Тест Google OAuth</a><br>";
} else {
    echo "❌ Google OAuth НЕ настроен<br>";
    echo "Проверьте файл .env и переменные окружения<br>";
}
?>
