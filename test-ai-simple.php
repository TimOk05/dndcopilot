<?php
// Простой тест AI API
require_once 'config.php';

echo "<h1>Тест AI API</h1>";

// Проверяем OpenSSL
echo "<h2>1. Проверка OpenSSL</h2>";
if (extension_loaded('openssl')) {
    echo "✅ OpenSSL включен<br>";
    echo "OpenSSL версия: " . OPENSSL_VERSION_TEXT . "<br>";
} else {
    echo "❌ OpenSSL НЕ включен<br>";
    echo "Это основная причина проблем с AI!<br>";
}

// Проверяем cURL
echo "<h2>2. Проверка cURL</h2>";
if (function_exists('curl_init')) {
    echo "✅ cURL доступен<br>";
    $curl_version = curl_version();
    echo "cURL версия: " . $curl_version['version'] . "<br>";
    echo "SSL версия: " . $curl_version['ssl_version'] . "<br>";
} else {
    echo "❌ cURL НЕ доступен<br>";
}

// Проверяем API ключи
echo "<h2>3. Проверка API ключей</h2>";
$deepseek_key = getApiKey('deepseek');
if ($deepseek_key) {
    echo "✅ DeepSeek API ключ: " . substr($deepseek_key, 0, 10) . "...<br>";
} else {
    echo "❌ DeepSeek API ключ отсутствует<br>";
}

$openai_key = getApiKey('openai');
if ($openai_key) {
    echo "✅ OpenAI API ключ: " . substr($openai_key, 0, 10) . "...<br>";
} else {
    echo "❌ OpenAI API ключ отсутствует<br>";
}

$google_key = getApiKey('google');
if ($google_key) {
    echo "✅ Google API ключ: " . substr($google_key, 0, 10) . "...<br>";
} else {
    echo "❌ Google API ключ отсутствует<br>";
}

// Тестируем AI API
echo "<h2>4. Тест AI API</h2>";
if ($deepseek_key && function_exists('curl_init')) {
    echo "Тестируем DeepSeek API...<br>";
    
    $data = [
        'model' => 'deepseek-chat',
        'messages' => [
            ['role' => 'system', 'content' => 'Ты помощник мастера D&D.'],
            ['role' => 'user', 'content' => 'Привет! Как дела?']
        ],
        'max_tokens' => 50,
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $deepseek_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        echo "❌ cURL ошибка: $error<br>";
    } elseif ($httpCode !== 200) {
        echo "❌ HTTP ошибка: $httpCode<br>";
        echo "Ответ: " . htmlspecialchars($response) . "<br>";
    } else {
        echo "✅ AI API работает!<br>";
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            echo "AI ответ: " . htmlspecialchars($result['choices'][0]['message']['content']) . "<br>";
        }
    }
} else {
    echo "❌ Нельзя протестировать AI API - отсутствует ключ или cURL<br>";
}

// Проверяем HTTPS соединения
echo "<h2>5. Тест HTTPS соединений</h2>";
$test_urls = [
    'https://api.deepseek.com' => 'DeepSeek API',
    'https://www.dnd5eapi.co' => 'D&D 5e API',
    'https://www.google.com' => 'Google'
];

foreach ($test_urls as $url => $name) {
    echo "Тестируем $name ($url)...<br>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'HEAD',
            'timeout' => 5
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    if ($result !== false) {
        echo "✅ $name доступен<br>";
    } else {
        echo "❌ $name НЕ доступен<br>";
        $error = error_get_last();
        if ($error) {
            echo "Ошибка: " . $error['message'] . "<br>";
        }
    }
}

echo "<h2>6. Рекомендации</h2>";
if (!extension_loaded('openssl')) {
    echo "<strong>🔴 КРИТИЧЕСКАЯ ПРОБЛЕМА:</strong><br>";
    echo "1. Откройте файл C:\\Windows\\php.ini<br>";
    echo "2. Найдите строку: ;extension=openssl<br>";
    echo "3. Уберите точку с запятой: extension=openssl<br>";
    echo "4. Сохраните файл и перезапустите веб-сервер<br>";
    echo "<br>";
}

if (!$deepseek_key) {
    echo "<strong>🟡 ПРОБЛЕМА:</strong><br>";
    echo "Добавьте DeepSeek API ключ в config.php<br>";
    echo "<br>";
}

if (extension_loaded('openssl') && $deepseek_key) {
    echo "<strong>✅ ВСЕ РАБОТАЕТ!</strong><br>";
    echo "AI генерация должна работать корректно!<br>";
}
?>
