<?php
/**
 * Тест названий зелий
 */

require_once 'public/api/generate-potions.php';

echo "=== Анализ названий зелий ===\n\n";

try {
    $generator = new PotionGenerator();
    
    // Получаем несколько зелий без фильтров
    $result = $generator->generatePotions([
        'count' => 10,
        'rarity' => '',
        'type' => '',
        'effect' => ''
    ]);
    
    if ($result['success']) {
        echo "Названия зелий из API:\n";
        foreach ($result['data'] as $potion) {
            echo "  - " . $potion['name'] . "\n";
        }
        
        echo "\nАнализ типов:\n";
        foreach ($result['data'] as $potion) {
            $name = strtolower($potion['name']);
            $type = '';
            
            if (strpos($name, 'potion') !== false) {
                $type = 'potion';
            } elseif (strpos($name, 'elixir') !== false) {
                $type = 'elixir';
            } elseif (strpos($name, 'oil') !== false) {
                $type = 'oil';
            } elseif (strpos($name, 'tincture') !== false) {
                $type = 'tincture';
            } else {
                $type = 'other';
            }
            
            echo "  - " . $potion['name'] . " -> " . $type . "\n";
        }
    } else {
        echo "❌ Ошибка: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Критическая ошибка: " . $e->getMessage() . "\n";
}

echo "\n=== Анализ завершен ===\n";
?>
