<?php
// Прямой тест API D&D 5e
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ПРЯМОЙ ТЕСТ D&D 5e API ===\n\n";

$url = 'https://www.dnd5eapi.co/api/magic-items';

echo "1. Тестируем URL: $url\n\n";

// Пробуем file_get_contents с контекстом
echo "2. Пробуем file_get_contents...\n";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: DnD-Copilot/1.0',
            'Accept: application/json'
        ],
        'timeout' => 30
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "✗ file_get_contents не удался\n";
    echo "Ошибка: " . error_get_last()['message'] . "\n\n";
} else {
    echo "✓ file_get_contents успешен\n";
    echo "Длина ответа: " . strlen($response) . " байт\n";
    echo "Первые 200 символов:\n";
    echo substr($response, 0, 200) . "...\n\n";
}

// Пробуем cURL
echo "3. Пробуем cURL...\n";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $curl_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "✗ cURL ошибка: $curl_error\n\n";
    } else {
        echo "✓ cURL успешен\n";
        echo "HTTP код: $http_code\n";
        echo "Длина ответа: " . strlen($curl_response) . " байт\n";
        echo "Первые 200 символов:\n";
        echo substr($curl_response, 0, 200) . "...\n\n";
    }
} else {
    echo "✗ cURL недоступен\n\n";
}

echo "=== КОНЕЦ ТЕСТА ===\n";
?>
