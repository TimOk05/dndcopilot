<?php
// Простой тест API D&D 5e
echo "=== ТЕСТ D&D 5e API ===\n\n";

$url = 'https://www.dnd5eapi.co/api/monsters';

echo "Тестируем URL: $url\n\n";

// Тест 1: file_get_contents
echo "--- Тест 1: file_get_contents ---\n";
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
    $error = error_get_last();
    echo "❌ file_get_contents failed: " . ($error ? $error['message'] : 'Unknown error') . "\n";
} else {
    echo "✅ file_get_contents success, размер: " . strlen($response) . " байт\n";
    $data = json_decode($response, true);
    if ($data && isset($data['count'])) {
        echo "   Найдено монстров: " . $data['count'] . "\n";
        if (isset($data['results'][0])) {
            echo "   Первый монстр: " . $data['results'][0]['name'] . "\n";
        }
    } else {
        echo "   ❌ JSON decode failed: " . json_last_error_msg() . "\n";
    }
}

echo "\n";

// Тест 2: cURL
echo "--- Тест 2: cURL ---\n";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || !empty($error)) {
        echo "❌ cURL failed: $error\n";
    } else {
        echo "✅ cURL success, HTTP код: $httpCode, размер: " . strlen($response) . " байт\n";
        $data = json_decode($response, true);
        if ($data && isset($data['count'])) {
            echo "   Найдено монстров: " . $data['count'] . "\n";
            if (isset($data['results'][0])) {
                echo "   Первый монстр: " . $data['results'][0]['name'] . "\n";
            }
        } else {
            echo "   ❌ JSON decode failed: " . json_last_error_msg() . "\n";
        }
    }
} else {
    echo "❌ cURL не доступен\n";
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";
?>
