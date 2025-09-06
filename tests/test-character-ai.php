<?php
require_once 'config.php';

echo "=== Тест AI генерации персонажей ===\n\n";

// Проверяем API ключ
$apiKey = getApiKey('deepseek');
echo "API ключ DeepSeek: " . (empty($apiKey) ? 'НЕ НАЙДЕН' : 'НАЙДЕН') . "\n";

if (empty($apiKey)) {
    echo "Ошибка: API ключ DeepSeek не найден!\n";
    exit;
}

// Тестируем прямое обращение к DeepSeek API
echo "\nТестируем прямое обращение к DeepSeek API...\n";

$data = [
    'model' => 'deepseek-chat',
    'messages' => [
        ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Создавай интересных и атмосферных персонажей.'],
        ['role' => 'user', 'content' => 'Опиши внешность и характер персонажа: Гандальф, Эльф Волшебник 5 уровня. Профессия: Мудрец. Пол: Мужчина. Мировоззрение: Нейтрально-добрый.']
    ],
    'max_tokens' => 200,
    'temperature' => 0.8
];

$ch = curl_init('https://api.deepseek.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_VERBOSE, true);

echo "Отправляем запрос к DeepSeek API...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP код: $httpCode\n";
if ($error) {
    echo "Ошибка cURL: $error\n";
}

if ($response === false) {
    echo "Ошибка: Не удалось получить ответ от API\n";
} else {
    echo "Ответ получен, размер: " . strlen($response) . " байт\n";
    echo "Ответ:\n$response\n";
    
    $result = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($result['choices'][0]['message']['content'])) {
            echo "\n✅ Успешно! AI ответ:\n";
            echo $result['choices'][0]['message']['content'] . "\n";
        } else {
            echo "\n❌ Ошибка в структуре ответа:\n";
            print_r($result);
        }
    } else {
        echo "\n❌ Ошибка декодирования JSON: " . json_last_error_msg() . "\n";
    }
}

echo "\n=== Тест завершен ===\n";
?>
