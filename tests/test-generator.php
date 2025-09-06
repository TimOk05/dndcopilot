<?php
/**
 * Тест генератора персонажей
 */

require_once 'config.php';
require_once 'api/CharacterService.php';

echo "=== Тест генератора персонажей ===\n\n";

try {
    $characterService = new CharacterService();
    
    // Тестируем генерацию персонажа
    $params = [
        'race' => 'human',
        'class' => 'fighter',
        'level' => 5
    ];
    
    $result = $characterService->generateCharacter($params);
    
    if ($result['success']) {
        echo "✅ Персонаж успешно сгенерирован!\n";
        echo "Имя: " . $result['character']['name'] . "\n";
        echo "Раса: " . $result['character']['race'] . "\n";
        echo "Класс: " . $result['character']['class'] . "\n";
        echo "Уровень: " . $result['character']['level'] . "\n";
        echo "Описание: " . $result['character']['description'] . "\n";
        echo "Предыстория: " . $result['character']['background'] . "\n";
    } else {
        echo "❌ Ошибка генерации: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Исключение: " . $e->getMessage() . "\n";
}
?>
