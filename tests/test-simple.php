<?php
// Простой тест API зелий
echo "Тестируем API зелий...\n";

// Тест 1: Проверяем доступность config.php
if (file_exists('config.php')) {
    echo "✓ config.php найден\n";
    require_once 'config.php';
} else {
    echo "✗ config.php не найден\n";
    exit;
}

// Тест 2: Проверяем папку cache
$cache_dir = __DIR__ . '/logs/cache';
if (is_dir($cache_dir)) {
    echo "✓ Папка cache существует\n";
} else {
    echo "✗ Папка cache не существует\n";
}

// Тест 3: Тестируем API напрямую
echo "\nТестируем API generate-potions.php...\n";

$url = 'http://localhost:8000/api/generate-potions.php?action=rarities';
echo "URL: $url\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 30
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "✗ Ошибка при запросе к API\n";
    $error = error_get_last();
    if ($error) {
        echo "Детали ошибки: " . print_r($error, true) . "\n";
    }
} else {
    echo "✓ Ответ получен:\n";
    echo $response . "\n";
}
?>
