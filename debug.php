<?php
// Диагностический скрипт
header('Content-Type: text/plain; charset=utf-8');

echo "=== ДИАГНОСТИКА API ЗЕЛИЙ ===\n\n";

// Проверка 1: Основные файлы
echo "1. Проверка файлов:\n";
echo "config.php: " . (file_exists('config.php') ? "✓" : "✗") . "\n";
echo "api/generate-potions.php: " . (file_exists('api/generate-potions.php') ? "✓" : "✗") . "\n";

// Проверка 2: Папки
echo "\n2. Проверка папок:\n";
$logs_dir = __DIR__ . '/logs';
echo "logs/: " . (is_dir($logs_dir) ? "✓" : "✗") . "\n";
if (is_dir($logs_dir)) {
    echo "  - Права записи: " . (is_writable($logs_dir) ? "✓" : "✗") . "\n";
}

$cache_dir = __DIR__ . '/logs/cache';
echo "logs/cache/: " . (is_dir($cache_dir) ? "✓" : "✗") . "\n";
if (is_dir($cache_dir)) {
    echo "  - Права записи: " . (is_writable($cache_dir) ? "✓" : "✗") . "\n";
}

// Проверка 3: Подключение к интернету
echo "\n3. Проверка подключения к D&D API:\n";
$url = 'https://www.dnd5eapi.co/api/magic-items';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'user_agent' => 'DnD-Copilot/1.0'
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

echo "URL: $url\n";
$start = microtime(true);
$response = @file_get_contents($url, false, $context);
$end = microtime(true);

if ($response === false) {
    echo "Результат: ✗ Ошибка\n";
    $error = error_get_last();
    if ($error) {
        echo "Детали: " . $error['message'] . "\n";
    }
} else {
    echo "Результат: ✓ Успешно\n";
    echo "Время ответа: " . round(($end - $start) * 1000, 2) . " мс\n";
    echo "Размер ответа: " . strlen($response) . " символов\n";
    
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($data['results'])) {
        echo "Найдено предметов: " . count($data['results']) . "\n";
        
        // Ищем зелья
        $potions = 0;
        foreach ($data['results'] as $item) {
            $name = strtolower($item['name']);
            if (strpos($name, 'potion') !== false || 
                strpos($name, 'elixir') !== false || 
                strpos($name, 'philter') !== false ||
                strpos($name, 'oil') !== false) {
                $potions++;
            }
        }
        echo "Найдено зелий: $potions\n";
    } else {
        echo "Ошибка парсинга JSON: " . json_last_error_msg() . "\n";
    }
}

// Проверка 4: Тест API зелий
echo "\n4. Тест API зелий (action=rarities):\n";
try {
    // Включаем config.php
    if (file_exists('config.php')) {
        require_once 'config.php';
    }
    
    // Подключаем API
    if (file_exists('api/generate-potions.php')) {
        // Симулируем GET запрос
        $_GET['action'] = 'rarities';
        
        // Захватываем вывод
        ob_start();
        include 'api/generate-potions.php';
        $api_output = ob_get_clean();
        
        echo "Ответ API:\n";
        echo $api_output . "\n";
    } else {
        echo "✗ Файл API не найден\n";
    }
} catch (Exception $e) {
    echo "✗ Ошибка при тестировании API: " . $e->getMessage() . "\n";
}

echo "\n=== КОНЕЦ ДИАГНОСТИКИ ===\n";
?>
