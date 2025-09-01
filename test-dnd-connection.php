<?php
echo "Тестирование подключения к D&D API...\n\n";

$url = 'https://www.dnd5eapi.co/api/monsters';
echo "URL: $url\n";

// Тест 1: Простой CURL запрос
echo "\n=== Тест 1: CURL запрос ===\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/1.0');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Total time: " . $info['total_time'] . "s\n";
echo "Connect time: " . $info['connect_time'] . "s\n";

if ($error) {
    echo "CURL Error: $error\n";
} else {
    echo "Response length: " . strlen($response) . " bytes\n";
    
    if ($response) {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✓ JSON успешно декодирован\n";
            if (isset($decoded['results'])) {
                echo "✓ Найдено монстров: " . count($decoded['results']) . "\n";
                if (count($decoded['results']) > 0) {
                    echo "Первый монстр: " . $decoded['results'][0]['name'] . " (CR " . $decoded['results'][0]['challenge_rating'] . ")\n";
                }
            } else {
                echo "✗ Структура ответа неверная\n";
                echo "Ключи: " . implode(', ', array_keys($decoded)) . "\n";
            }
        } else {
            echo "✗ Ошибка декодирования JSON: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "✗ Пустой ответ\n";
    }
}

// Тест 2: Проверка через file_get_contents
echo "\n=== Тест 2: file_get_contents ===\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'user_agent' => 'DnD-Copilot/1.0'
    ]
]);

$start_time = microtime(true);
$file_response = @file_get_contents($url, false, $context);
$end_time = microtime(true);

if ($file_response === false) {
    echo "✗ file_get_contents не удался\n";
    $error = error_get_last();
    if ($error) {
        echo "Ошибка: " . $error['message'] . "\n";
    }
} else {
    echo "✓ file_get_contents успешен\n";
    echo "Время: " . round(($end_time - $start_time) * 1000, 2) . "ms\n";
    echo "Размер ответа: " . strlen($file_response) . " байт\n";
}

// Тест 3: Проверка DNS
echo "\n=== Тест 3: DNS разрешение ===\n";
$host = 'www.dnd5eapi.co';
$ip = gethostbyname($host);
if ($ip !== $host) {
    echo "✓ DNS разрешен: $host -> $ip\n";
} else {
    echo "✗ DNS не разрешен для $host\n";
}
?>
