<?php
// Тест fsockopen для HTTPS
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ТЕСТ FSOCKOPEN ===\n\n";

$host = 'www.dnd5eapi.co';
$port = 443;
$path = '/api/magic-items';

echo "1. Тестируем fsockopen к $host:$port\n\n";

// Пробуем разные варианты подключения
$connection_methods = [
    ['ssl://' . $host, 443],
    ['tls://' . $host, 443],
    [$host, 443]
];

foreach ($connection_methods as $i => $method) {
    echo ($i + 2) . ". Пробуем подключение к {$method[0]}:{$method[1]}...\n";
    
    $fp = @fsockopen($method[0], $method[1], $errno, $errstr, 10);
    if ($fp) {
        echo "✓ Успешное подключение!\n";
        
        // Устанавливаем таймаут
        stream_set_timeout($fp, 30);
        
        // Формируем HTTPS запрос
        $request = "GET $path HTTP/1.1\r\n";
        $request .= "Host: $host\r\n";
        $request .= "User-Agent: DnD-Copilot/1.0\r\n";
        $request .= "Accept: application/json\r\n";
        $request .= "Connection: close\r\n";
        $request .= "\r\n";
        
        echo "3. Отправляем запрос...\n";
        fwrite($fp, $request);
        
        echo "4. Читаем ответ...\n";
        $response = '';
        $start_time = time();
        
        while (!feof($fp) && (time() - $start_time) < 30) {
            $chunk = fgets($fp, 1024);
            if ($chunk === false) {
                break;
            }
            $response .= $chunk;
        }
        
        fclose($fp);
        
        echo "5. Ответ получен, длина: " . strlen($response) . " байт\n";
        
        if (empty($response)) {
            echo "✗ Пустой ответ\n";
        } else {
            echo "✓ Ответ получен\n";
            echo "Первые 200 символов:\n";
            echo substr($response, 0, 200) . "...\n";
            
            // Парсим HTTP ответ
            $parts = explode("\r\n\r\n", $response, 2);
            if (count($parts) >= 2) {
                $headers = $parts[0];
                $body = $parts[1];
                
                echo "\n6. HTTP заголовки:\n";
                echo $headers . "\n";
                
                echo "\n7. Тело ответа (первые 200 символов):\n";
                echo substr($body, 0, 200) . "...\n";
                
                // Проверяем JSON
                $json = json_decode($body, true);
                if ($json) {
                    echo "\n8. ✓ JSON валиден\n";
                    if (isset($json['results'])) {
                        echo "Найдено предметов: " . count($json['results']) . "\n";
                    }
                } else {
                    echo "\n8. ✗ JSON невалиден: " . json_last_error_msg() . "\n";
                }
            }
        }
        
        break; // Выходим из цикла при успешном подключении
    } else {
        echo "✗ Ошибка подключения: $errstr ($errno)\n";
    }
    echo "\n";
}

echo "\n=== КОНЕЦ ТЕСТА ===\n";
?>
