<?php
// Тест альтернативных методов HTTP запросов
echo "=== ТЕСТ АЛЬТЕРНАТИВНЫХ МЕТОДОВ HTTP ===\n\n";

$url = 'https://www.dnd5eapi.co/api/monsters';

echo "Тестируем URL: $url\n\n";

// Метод 1: fsockopen
echo "--- Метод 1: fsockopen ---\n";
if (function_exists('fsockopen')) {
    echo "✅ fsockopen доступен\n";
    
    $parsed_url = parse_url($url);
    $host = $parsed_url['host'];
    $path = $parsed_url['path'] ?? '/';
    $port = $parsed_url['port'] ?? 443;
    
    echo "Подключаемся к $host:$port\n";
    
    $fp = @fsockopen("ssl://$host", $port, $errno, $errstr, 30);
    if ($fp) {
        echo "✅ SSL соединение установлено\n";
        
        $request = "GET $path HTTP/1.1\r\n";
        $request .= "Host: $host\r\n";
        $request .= "User-Agent: DnD-Copilot/1.0\r\n";
        $request .= "Accept: application/json\r\n";
        $request .= "Connection: close\r\n\r\n";
        
        fwrite($fp, $request);
        
        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }
        fclose($fp);
        
        if (strpos($response, 'HTTP/1.1 200') !== false) {
            echo "✅ Успешный HTTP ответ\n";
            $json_start = strpos($response, '{');
            if ($json_start !== false) {
                $json_data = substr($response, $json_start);
                $data = json_decode($json_data, true);
                if ($data && isset($data['count'])) {
                    echo "✅ JSON декодирован успешно, найдено {$data['count']} монстров\n";
                } else {
                    echo "❌ Ошибка декодирования JSON\n";
                }
            }
        } else {
            echo "❌ HTTP ошибка\n";
        }
    } else {
        echo "❌ Не удалось подключиться: $errstr ($errno)\n";
    }
} else {
    echo "❌ fsockopen недоступен\n";
}

echo "\n--- Метод 2: socket_create ---\n";
if (function_exists('socket_create')) {
    echo "✅ socket_create доступен\n";
    
    $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket) {
        echo "✅ Сокет создан\n";
        
        $result = @socket_connect($socket, 'www.dnd5eapi.co', 443);
        if ($result) {
            echo "✅ Подключение установлено\n";
            
            $request = "GET /api/monsters HTTP/1.1\r\n";
            $request .= "Host: www.dnd5eapi.co\r\n";
            $request .= "User-Agent: DnD-Copilot/1.0\r\n";
            $request .= "Accept: application/json\r\n";
            $request .= "Connection: close\r\n\r\n";
            
            socket_write($socket, $request);
            
            $response = '';
            while ($data = socket_read($socket, 1024)) {
                $response .= $data;
            }
            socket_close($socket);
            
            if (strpos($response, 'HTTP/1.1 200') !== false) {
                echo "✅ Успешный HTTP ответ через socket\n";
            } else {
                echo "❌ HTTP ошибка через socket\n";
            }
        } else {
            echo "❌ Не удалось подключиться через socket\n";
        }
    } else {
        echo "❌ Не удалось создать сокет\n";
    }
} else {
    echo "❌ socket_create недоступен\n";
}

echo "\n--- Метод 3: exec/wget ---\n";
if (function_exists('exec')) {
    echo "✅ exec доступен\n";
    
    // Проверяем доступность wget
    $wget_output = [];
    $wget_return = 0;
    exec('wget --version 2>&1', $wget_output, $wget_return);
    
    if ($wget_return === 0) {
        echo "✅ wget доступен\n";
        
        $temp_file = tempnam(sys_get_temp_dir(), 'dnd_test_');
        $command = "wget -q -O \"$temp_file\" \"$url\"";
        
        exec($command, $output, $return_code);
        
        if ($return_code === 0 && file_exists($temp_file)) {
            $content = file_get_contents($temp_file);
            $data = json_decode($content, true);
            
            if ($data && isset($data['count'])) {
                echo "✅ wget успешно загрузил данные, найдено {$data['count']} монстров\n";
            } else {
                echo "❌ wget загрузил данные, но JSON невалиден\n";
            }
            
            unlink($temp_file);
        } else {
            echo "❌ wget не смог загрузить данные\n";
        }
    } else {
        echo "❌ wget недоступен\n";
    }
} else {
    echo "❌ exec недоступен\n";
}

echo "\n--- Метод 4: PowerShell ---\n";
if (function_exists('exec') && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "✅ Windows система, проверяем PowerShell\n";
    
    $temp_file = tempnam(sys_get_temp_dir(), 'dnd_ps_');
    $ps_command = "powershell -Command \"Invoke-WebRequest -Uri '$url' -OutFile '$temp_file'\"";
    
    exec($ps_command, $output, $return_code);
    
    if ($return_code === 0 && file_exists($temp_file)) {
        $content = file_get_contents($temp_file);
        $data = json_decode($content, true);
        
        if ($data && isset($data['count'])) {
            echo "✅ PowerShell успешно загрузил данные, найдено {$data['count']} монстров\n";
        } else {
            echo "❌ PowerShell загрузил данные, но JSON невалиден\n";
        }
        
        unlink($temp_file);
    } else {
        echo "❌ PowerShell не смог загрузить данные\n";
    }
} else {
    echo "❌ PowerShell недоступен или не Windows\n";
}

echo "\n--- Рекомендации ---\n";
echo "Если ни один метод не работает, можно:\n";
echo "1. Создать локальную базу данных монстров\n";
echo "2. Использовать прокси-сервер для API запросов\n";
echo "3. Модифицировать генератор для работы без внешних API\n";
echo "4. Использовать другой сервер с полной поддержкой PHP\n";
?>
