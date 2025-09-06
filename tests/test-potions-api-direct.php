<?php
/**
 * Прямой тест API зелий
 */

echo "<h1>🧪 Прямой тест API зелий</h1>";
echo "<p><strong>Время:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Тестируем прямой вызов API
$url = __DIR__ . '/api/generate-potions.php?action=random&count=1';

echo "<h2>🌐 Тест API вызова</h2>";
echo "<p><strong>URL:</strong> <code>$url</code></p>";

// Создаем контекст для запроса
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: DnD-Copilot/1.0',
            'Accept: application/json'
        ],
        'timeout' => 30
    ]
]);

try {
    echo "<p>Отправляем запрос...</p>";
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>❌ Ошибка получения ответа</p>";
    } else {
        echo "<p style='color: green;'>✅ Ответ получен</p>";
        echo "<p><strong>Размер ответа:</strong> " . strlen($response) . " байт</p>";
        
        // Пытаемся декодировать JSON
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✅ JSON успешно декодирован</p>";
            
            if ($data['success']) {
                echo "<p style='color: green;'>✅ API вернул успешный ответ</p>";
                echo "<p><strong>Количество зелий:</strong> " . $data['count'] . "</p>";
                
                if (isset($data['data'][0])) {
                    $potion = $data['data'][0];
                    echo "<h3>🧪 Первое зелье:</h3>";
                    echo "<ul>";
                    echo "<li><strong>Название:</strong> " . htmlspecialchars($potion['name']) . "</li>";
                    echo "<li><strong>Редкость:</strong> " . htmlspecialchars($potion['rarity']) . "</li>";
                    echo "<li><strong>Тип:</strong> " . htmlspecialchars($potion['type']) . "</li>";
                    echo "<li><strong>Описание:</strong> " . htmlspecialchars($potion['description']) . "</li>";
                    echo "</ul>";
                }
            } else {
                echo "<p style='color: red;'>❌ API вернул ошибку: " . htmlspecialchars($data['error']) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Ошибка декодирования JSON: " . json_last_error_msg() . "</p>";
            echo "<p><strong>Сырой ответ:</strong></p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Исключение: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><strong>Тест завершен:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
