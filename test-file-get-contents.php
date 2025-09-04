<?php
// Тест file_get_contents для HTTPS
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ТЕСТ FILE_GET_CONTENTS ===\n\n";

$url = 'https://www.dnd5eapi.co/api/magic-items';

echo "1. Тестируем URL: $url\n\n";

// Создаем контекст для HTTPS
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: DnD-Copilot/1.0',
            'Accept: application/json',
            'Connection: close'
        ],
        'timeout' => 30,
        'follow_location' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

echo "2. Пробуем file_get_contents с контекстом...\n";
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "✗ file_get_contents не удался\n";
    $error = error_get_last();
    echo "Ошибка: " . $error['message'] . "\n";
    echo "Файл: " . $error['file'] . "\n";
    echo "Строка: " . $error['line'] . "\n\n";
} else {
    echo "✓ file_get_contents успешен\n";
    echo "Длина ответа: " . strlen($response) . " байт\n";
    echo "Первые 200 символов:\n";
    echo substr($response, 0, 200) . "...\n\n";
    
    // Проверяем JSON
    $json = json_decode($response, true);
    if ($json) {
        echo "✓ JSON валиден\n";
        if (isset($json['results'])) {
            echo "Найдено предметов: " . count($json['results']) . "\n";
        }
    } else {
        echo "✗ JSON невалиден: " . json_last_error_msg() . "\n";
    }
}

echo "\n3. Пробуем без контекста...\n";
$response2 = @file_get_contents($url);

if ($response2 === false) {
    echo "✗ file_get_contents без контекста не удался\n";
    $error = error_get_last();
    echo "Ошибка: " . $error['message'] . "\n\n";
} else {
    echo "✓ file_get_contents без контекста успешен\n";
    echo "Длина ответа: " . strlen($response2) . " байт\n";
}

echo "\n=== КОНЕЦ ТЕСТА ===\n";
?>
