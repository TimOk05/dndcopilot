<?php
echo "=== Тест PHP функций ===\n";
echo "Время: " . date('Y-m-d H:i:s') . "\n\n";

// Проверяем основные функции
echo "cURL доступен: " . (function_exists('curl_init') ? 'ДА' : 'НЕТ') . "\n";
echo "file_get_contents доступен: " . (function_exists('file_get_contents') ? 'ДА' : 'НЕТ') . "\n";
echo "fopen доступен: " . (function_exists('fopen') ? 'ДА' : 'НЕТ') . "\n";
echo "fsockopen доступен: " . (function_exists('fsockopen') ? 'ДА' : 'НЕТ') . "\n";
echo "stream_context_create доступен: " . (function_exists('stream_context_create') ? 'ДА' : 'НЕТ') . "\n";

echo "\n=== Проверка allow_url_fopen ===\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ВКЛЮЧЕНО' : 'ВЫКЛЮЧЕНО') . "\n";

echo "\n=== Тест file_get_contents с URL ===\n";
$test_url = 'https://www.dnd5eapi.co/api/magic-items';
try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'DnD-Copilot/1.0'
        ]
    ]);
    
    $response = @file_get_contents($test_url, false, $context);
    if ($response !== false) {
        echo "✅ file_get_contents работает с URL\n";
        $data = json_decode($response, true);
        if ($data && isset($data['count'])) {
            echo "Найдено магических предметов: " . $data['count'] . "\n";
        }
    } else {
        echo "❌ file_get_contents не работает с URL\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n=== Тест fsockopen ===\n";
try {
    $fp = @fsockopen('www.dnd5eapi.co', 80, $errno, $errstr, 10);
    if ($fp) {
        echo "✅ fsockopen работает\n";
        fclose($fp);
    } else {
        echo "❌ fsockopen не работает: $errstr ($errno)\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка fsockopen: " . $e->getMessage() . "\n";
}

echo "\n=== Тест завершен ===\n";
?>
