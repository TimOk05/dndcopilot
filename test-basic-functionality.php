<?php
/**
 * Базовый тест функциональности
 */

echo "<h1>🧪 Базовый тест функциональности</h1>\n";

// Тест 1: Проверка основных файлов
echo "<h2>Тест 1: Проверка файлов</h2>\n";
$files_to_check = [
    'config.php',
    'auth.php',
    'template.html',
    'api/language-service.php',
    'api/ai-service.php',
    'api/generate-potions.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file существует<br>\n";
    } else {
        echo "❌ $file не найден<br>\n";
    }
}

// Тест 2: Проверка config.php
echo "<h2>Тест 2: Проверка config.php</h2>\n";
try {
    require_once 'config.php';
    echo "✅ config.php загружен успешно<br>\n";
    
    if (defined('DND_API_URL')) {
        echo "✅ DND_API_URL определен: " . DND_API_URL . "<br>\n";
    } else {
        echo "❌ DND_API_URL не определен<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка в config.php: " . $e->getMessage() . "<br>\n";
}

// Тест 3: Проверка auth.php
echo "<h2>Тест 3: Проверка auth.php</h2>\n";
try {
    require_once 'auth.php';
    echo "✅ auth.php загружен успешно<br>\n";
    
    if (function_exists('isLoggedIn')) {
        echo "✅ Функция isLoggedIn существует<br>\n";
    } else {
        echo "❌ Функция isLoggedIn не найдена<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка в auth.php: " . $e->getMessage() . "<br>\n";
}

// Тест 4: Проверка Language Service
echo "<h2>Тест 4: Проверка Language Service</h2>\n";
try {
    require_once 'api/language-service.php';
    echo "✅ Language Service загружен<br>\n";
    
    $lang_service = new LanguageService();
    echo "✅ Language Service инициализирован<br>\n";
    echo "Текущий язык: " . $lang_service->getCurrentLanguage() . "<br>\n";
} catch (Exception $e) {
    echo "❌ Ошибка в Language Service: " . $e->getMessage() . "<br>\n";
}

echo "<h2>✅ Базовые тесты завершены!</h2>\n";
?>
