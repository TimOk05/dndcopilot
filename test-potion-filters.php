<?php
/**
 * Тест фильтров зелий
 */

require_once 'public/api/generate-potions.php';

echo "=== Тест фильтров зелий ===\n\n";

try {
    $generator = new PotionGenerator();
    
    // Тест 1: Фильтр по редкости "Редкая"
    echo "Тест 1: Фильтр по редкости 'Редкая'\n";
    $result1 = $generator->generatePotions([
        'count' => 3,
        'rarity' => 'Редкая',
        'type' => '',
        'effect' => ''
    ]);
    
    if ($result1['success']) {
        echo "✅ Успешно найдено " . count($result1['data']) . " редких зелий\n";
        foreach ($result1['data'] as $potion) {
            echo "  - " . $potion['name'] . " (" . $potion['rarity'] . ")\n";
        }
    } else {
        echo "❌ Ошибка: " . $result1['error'] . "\n";
    }
    
    echo "\n";
    
    // Тест 2: Фильтр по типу "Зелье" (должно найти все potion)
    echo "Тест 2: Фильтр по типу 'Зелье'\n";
    echo "Отладка: 'Зелье' должно преобразоваться в 'potion'\n";
    $result2 = $generator->generatePotions([
        'count' => 3,
        'rarity' => '',
        'type' => 'Зелье',
        'effect' => ''
    ]);
    
    if ($result2['success']) {
        echo "✅ Успешно найдено " . count($result2['data']) . " зелий типа 'Зелье'\n";
        foreach ($result2['data'] as $potion) {
            echo "  - " . $potion['name'] . " (" . $potion['type'] . ")\n";
        }
    } else {
        echo "❌ Ошибка: " . $result2['error'] . "\n";
    }
    
    echo "\n";
    
    // Тест 3: Комбинированный фильтр
    echo "Тест 3: Комбинированный фильтр (Редкая + Зелье)\n";
    $result3 = $generator->generatePotions([
        'count' => 2,
        'rarity' => 'Редкая',
        'type' => 'Зелье',
        'effect' => ''
    ]);
    
    if ($result3['success']) {
        echo "✅ Успешно найдено " . count($result3['data']) . " редких зелий\n";
        foreach ($result3['data'] as $potion) {
            echo "  - " . $potion['name'] . " (" . $potion['rarity'] . ", " . $potion['type'] . ")\n";
        }
    } else {
        echo "❌ Ошибка: " . $result3['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Критическая ошибка: " . $e->getMessage() . "\n";
}

echo "\n=== Тест завершен ===\n";
?>
