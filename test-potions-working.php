<?php
/**
 * Тест рабочего генератора зелий
 */

// Устанавливаем режим тестирования
define('TESTING_MODE', true);

echo "<h1>🧪 Тест рабочего генератора зелий</h1>";
echo "<p><strong>Время:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    require_once 'api/generate-potions.php';
    echo "<p style='color: green;'>✅ Класс PotionGenerator загружен</p>";
    
    $generator = new PotionGenerator();
    echo "<p style='color: green;'>✅ Объект генератора создан</p>";
    
    // Тестируем генерацию
    echo "<h2>🎲 Тест генерации зелий</h2>";
    
    $params = ['count' => 1];
    echo "<p><strong>Параметры:</strong> " . json_encode($params) . "</p>";
    
    $result = $generator->generatePotions($params);
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ Генерация успешна!</p>";
        echo "<p><strong>Сгенерировано зелий:</strong> " . $result['count'] . "</p>";
        
        foreach ($result['data'] as $index => $potion) {
            echo "<h3>🧪 Зелье " . ($index + 1) . "</h3>";
            echo "<ul>";
            echo "<li><strong>Название:</strong> " . htmlspecialchars($potion['name']) . "</li>";
            echo "<li><strong>Редкость:</strong> " . htmlspecialchars($potion['rarity']) . "</li>";
            echo "<li><strong>Тип:</strong> " . htmlspecialchars($potion['type']) . "</li>";
            echo "<li><strong>Описание:</strong> " . htmlspecialchars($potion['description']) . "</li>";
            if (!empty($potion['effects'])) {
                echo "<li><strong>Эффекты:</strong> " . implode(', ', $potion['effects']) . "</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Ошибка генерации: " . htmlspecialchars($result['error']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Критическая ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Файл:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Строка:</strong> " . $e->getLine() . "</p>";
}

echo "<p><strong>Тест завершен:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
