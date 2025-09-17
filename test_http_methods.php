<?php
// Тест различных методов HTTP запросов в PHP
echo "=== ТЕСТ HTTP МЕТОДОВ В PHP ===\n\n";

$url = 'https://www.dnd5eapi.co/api/monsters';

// Проверим настройки PHP
echo "--- Настройки PHP ---\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "\n";
echo "user_agent: " . ini_get('user_agent') . "\n";
echo "default_socket_timeout: " . ini_get('default_socket_timeout') . "\n";

// Проверим доступные функции
echo "\n--- Доступные функции ---\n";
echo "curl_init: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";
echo "file_get_contents: " . (function_exists('file_get_contents') ? 'YES' : 'NO') . "\n";
echo "fsockopen: " . (function_exists('fsockopen') ? 'YES' : 'NO') . "\n";
echo "stream_context_create: " . (function_exists('stream_context_create') ? 'YES' : 'NO') . "\n";

// Попробуем простой HTTP запрос через fsockopen
echo "\n--- Тест fsockopen ---\n";
if (function_exists('fsockopen')) {
    $parsed_url = parse_url($url);
    $host = $parsed_url['host'];
    $path = $parsed_url['path'] ?? '/';
    $port = $parsed_url['port'] ?? 443;
    
    echo "Подключаемся к: $host:$port\n";
    echo "Путь: $path\n";
    
    $fp = @fsockopen("ssl://$host", $port, $errno, $errstr, 30);
    if (!$fp) {
        echo "❌ fsockopen failed: $errstr ($errno)\n";
    } else {
        echo "✅ fsockopen success\n";
        
        $request = "GET $path HTTP/1.1\r\n";
        $request .= "Host: $host\r\n";
        $request .= "User-Agent: DnD-Copilot/1.0\r\n";
        $request .= "Accept: application/json\r\n";
        $request .= "Connection: close\r\n\r\n";
        
        fwrite($fp, $request);
        
        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 1024);
        }
        fclose($fp);
        
        echo "Размер ответа: " . strlen($response) . " байт\n";
        
        // Парсим HTTP заголовки
        $header_end = strpos($response, "\r\n\r\n");
        if ($header_end !== false) {
            $headers = substr($response, 0, $header_end);
            $body = substr($response, $header_end + 4);
            
            echo "Заголовки:\n" . $headers . "\n";
            echo "Тело ответа (первые 200 символов):\n" . substr($body, 0, 200) . "...\n";
            
            $data = json_decode($body, true);
            if ($data && isset($data['count'])) {
                echo "✅ JSON успешно декодирован, найдено монстров: " . $data['count'] . "\n";
            } else {
                echo "❌ JSON decode failed: " . json_last_error_msg() . "\n";
            }
        }
    }
} else {
    echo "❌ fsockopen не доступен\n";
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";
?>
