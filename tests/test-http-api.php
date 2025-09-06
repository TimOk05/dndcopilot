<?php
// Тест HTTP API (без SSL)
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ТЕСТ HTTP API ===\n\n";

// Пробуем HTTP версию
$url = 'http://www.dnd5eapi.co/api/magic-items';

echo "1. Тестируем HTTP URL: $url\n\n";

$response = @file_get_contents($url);

if ($response === false) {
    echo "✗ HTTP file_get_contents не удался\n";
    $error = error_get_last();
    echo "Ошибка: " . $error['message'] . "\n\n";
} else {
    echo "✓ HTTP file_get_contents успешен\n";
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

echo "\n2. Пробуем fsockopen для HTTP...\n";

$host = 'www.dnd5eapi.co';
$port = 80;
$path = '/api/magic-items';

$fp = @fsockopen($host, $port, $errno, $errstr, 10);
if ($fp) {
    echo "✓ HTTP fsockopen успешен\n";
    
    // Формируем HTTP запрос
    $request = "GET $path HTTP/1.1\r\n";
    $request .= "Host: $host\r\n";
    $request .= "User-Agent: DnD-Copilot/1.0\r\n";
    $request .= "Accept: application/json\r\n";
    $request .= "Connection: close\r\n";
    $request .= "\r\n";
    
    fwrite($fp, $request);
    
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 1024);
    }
    fclose($fp);
    
    echo "Длина ответа: " . strlen($response) . " байт\n";
    
    // Парсим HTTP ответ
    $parts = explode("\r\n\r\n", $response, 2);
    if (count($parts) >= 2) {
        $headers = $parts[0];
        $body = $parts[1];
        
        echo "HTTP заголовки:\n";
        echo $headers . "\n\n";
        
        echo "Тело ответа (первые 200 символов):\n";
        echo substr($body, 0, 200) . "...\n\n";
        
        // Проверяем JSON
        $json = json_decode($body, true);
        if ($json) {
            echo "✓ JSON валиден\n";
            if (isset($json['results'])) {
                echo "Найдено предметов: " . count($json['results']) . "\n";
            }
        } else {
            echo "✗ JSON невалиден: " . json_last_error_msg() . "\n";
        }
    }
} else {
    echo "✗ HTTP fsockopen не удался: $errstr ($errno)\n";
}

echo "\n=== КОНЕЦ ТЕСТА ===\n";
?>
