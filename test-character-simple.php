<?php
require_once 'config.php';

echo "=== Тест генерации персонажа ===\n\n";

// Создаем тестовые данные
$testData = [
    'race' => 'human',
    'class' => 'fighter',
    'level' => 1,
    'gender' => 'male',
    'alignment' => 'lawful-good'
];

echo "Тестовые данные: " . json_encode($testData, JSON_UNESCAPED_UNICODE) . "\n\n";

// Проверяем API ключ
$apiKey = getApiKey('deepseek');
echo "API ключ DeepSeek: " . (empty($apiKey) ? 'НЕ НАЙДЕН' : 'НАЙДЕН') . "\n";

// Тестируем AI запрос
echo "\nТестируем AI запрос...\n";

$data = [
    'model' => 'deepseek-chat',
    'messages' => [
        ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Создавай интересных и атмосферных персонажей.'],
        ['role' => 'user', 'content' => 'Опиши внешность и характер персонажа: Человек Боец 1 уровня. Пол: Мужчина. Мировоззрение: Законно-добрый.']
    ],
    'max_tokens' => 200,
    'temperature' => 0.8
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        'content' => json_encode($data),
        'timeout' => 10
    ]
]);

try {
    $response = file_get_contents('https://api.deepseek.com/v1/chat/completions', false, $context);
    
    if ($response === false) {
        echo "❌ Ошибка: file_get_contents вернул false\n";
    } else {
        echo "✅ Ответ получен, размер: " . strlen($response) . " байт\n";
        
        $result = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($result['choices'][0]['message']['content'])) {
                echo "\n🎯 AI ответ:\n";
                echo $result['choices'][0]['message']['content'] . "\n";
            } else {
                echo "\n❌ Ошибка в структуре ответа:\n";
                print_r($result);
            }
        } else {
            echo "\n❌ Ошибка декодирования JSON: " . json_last_error_msg() . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Исключение: " . $e->getMessage() . "\n";
}

echo "\n=== Тест завершен ===\n";
?>
