<?php
/**
 * Простой тест генератора зелий
 */

// Устанавливаем режим тестирования
define('TESTING_MODE', true);

echo "<h1>🧪 Простой тест генератора зелий</h1>";
echo "<p><strong>Время:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // Загружаем класс напрямую
    require_once 'api/generate-potions.php';
    echo "<p style='color: green;'>✅ Класс PotionGenerator загружен</p>";
    
    // Создаем объект
    $generator = new PotionGenerator();
    echo "<p style='color: green;'>✅ Объект генератора создан</p>";
    
    // Тестируем генерацию напрямую
    echo "<h2>🎲 Тест generatePotions</h2>";
    $params = ['count' => 1];
    echo "<p>Параметры: " . json_encode($params) . "</p>";
    
    $result = $generator->generatePotions($params);
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ Генерация успешна!</p>";
        echo "<p>Сгенерировано зелий: " . $result['count'] . "</p>";
        
        if (isset($result['data'][0])) {
            $potion = $result['data'][0];
            echo "<h3>🧪 Сгенерированное зелье:</h3>";
            echo "<ul>";
            echo "<li><strong>Название:</strong> " . htmlspecialchars($potion['name']) . "</li>";
            echo "<li><strong>Редкость:</strong> " . htmlspecialchars($potion['rarity']) . "</li>";
            echo "<li><strong>Тип:</strong> " . htmlspecialchars($potion['type']) . "</li>";
            echo "<li><strong>Описание:</strong> " . htmlspecialchars($potion['description']) . "</li>";
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>❌ Ошибка генерации: " . htmlspecialchars($result['error']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Критическая ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Файл:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Строка:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Трейс:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<p><strong>Тест завершен:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
