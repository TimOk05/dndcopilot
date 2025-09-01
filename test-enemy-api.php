<?php
// Тестовый файл для проверки API генерации противников
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Тест API генерации противников</h1>";

// Тестируем подключение к D&D API
echo "<h2>1. Тест подключения к D&D API</h2>";
$dnd_url = 'https://www.dnd5eapi.co/api/monsters';
echo "<p>URL: $dnd_url</p>";

$ch = curl_init($dnd_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/1.0');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$start_time = microtime(true);
$response = curl_exec($ch);
$end_time = microtime(true);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

$request_time = round(($end_time - $start_time) * 1000, 2);

echo "<p>HTTP код: $http_code</p>";
echo "<p>Время запроса: {$request_time}ms</p>";
echo "<p>Размер ответа: " . strlen($response) . " байт</p>";

if ($error) {
    echo "<p style='color: red;'>Ошибка CURL: $error</p>";
} else {
    echo "<p style='color: green;'>CURL запрос выполнен успешно</p>";
}

if ($http_code === 200 && $response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>JSON успешно декодирован</p>";
        if (isset($data['results'])) {
            echo "<p>Найдено монстров: " . count($data['results']) . "</p>";
            echo "<p>Первые 5 монстров:</p><ul>";
            for ($i = 0; $i < min(5, count($data['results'])); $i++) {
                $monster = $data['results'][$i];
                echo "<li>{$monster['name']} (CR: {$monster['challenge_rating']}, Тип: {$monster['type']})</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>Структура ответа неожиданная: " . json_encode(array_keys($data)) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Ошибка JSON: " . json_last_error_msg() . "</p>";
    }
} else {
    echo "<p style='color: red;'>HTTP ошибка: $http_code</p>";
}

// Тестируем наш API
echo "<h2>2. Тест нашего API генерации противников</h2>";

// Создаем POST данные
$post_data = [
    'threat_level' => 'medium',
    'count' => 1,
    'enemy_type' => '',
    'environment' => '',
    'use_ai' => 'on'
];

echo "<p>Тестовые данные: " . json_encode($post_data, JSON_UNESCAPED_UNICODE) . "</p>";

// Вызываем наш API
$ch = curl_init('http://localhost/dndcopilot/api/generate-enemies.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$start_time = microtime(true);
$api_response = curl_exec($ch);
$end_time = microtime(true);
$api_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$api_error = curl_error($ch);
curl_close($ch);

$api_request_time = round(($end_time - $start_time) * 1000, 2);

echo "<p>HTTP код нашего API: $api_http_code</p>";
echo "<p>Время запроса: {$api_request_time}ms</p>";
echo "<p>Размер ответа: " . strlen($api_response) . " байт</p>";

if ($api_error) {
    echo "<p style='color: red;'>Ошибка CURL нашего API: $api_error</p>";
} else {
    echo "<p style='color: green;'>CURL запрос к нашему API выполнен успешно</p>";
}

if ($api_http_code === 200 && $api_response) {
    $api_data = json_decode($api_response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>JSON нашего API успешно декодирован</p>";
        echo "<p>Ответ API:</p>";
        echo "<pre>" . json_encode($api_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<p style='color: red;'>Ошибка JSON нашего API: " . json_last_error_msg() . "</p>";
        echo "<p>Сырой ответ: " . htmlspecialchars($api_response) . "</p>";
    }
} else {
    echo "<p style='color: red;'>HTTP ошибка нашего API: $api_http_code</p>";
    echo "<p>Сырой ответ: " . htmlspecialchars($api_response) . "</p>";
}

// Проверяем логи
echo "<h2>3. Проверка логов</h2>";
$log_file = __DIR__ . '/logs/app.log';
if (file_exists($log_file)) {
    echo "<p>Файл логов найден: $log_file</p>";
    $log_content = file_get_contents($log_file);
    $log_lines = explode("\n", $log_content);
    $recent_logs = array_slice($log_lines, -20); // Последние 20 строк
    
    echo "<p>Последние записи в логах:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto;'>";
    foreach ($recent_logs as $line) {
        if (trim($line)) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>Файл логов не найден: $log_file</p>";
}

// Проверяем директорию кэша
echo "<h2>4. Проверка кэша</h2>";
$cache_dir = __DIR__ . '/logs/cache';
if (is_dir($cache_dir)) {
    echo "<p>Директория кэша найдена: $cache_dir</p>";
    $cache_files = scandir($cache_dir);
    $cache_files = array_filter($cache_files, function($file) {
        return $file !== '.' && $file !== '..';
    });
    
    if (empty($cache_files)) {
        echo "<p>Файлы кэша отсутствуют</p>";
    } else {
        echo "<p>Файлы кэша:</p><ul>";
        foreach ($cache_files as $file) {
            $file_path = $cache_dir . '/' . $file;
            $file_size = filesize($file_path);
            $file_time = date('Y-m-d H:i:s', filemtime($file_path));
            echo "<li>$file ($file_size байт, изменен: $file_time)</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: orange;'>Директория кэша не найдена: $cache_dir</p>";
}

echo "<hr>";
echo "<p><small>Тест выполнен: " . date('Y-m-d H:i:s') . "</small></p>";
?>
