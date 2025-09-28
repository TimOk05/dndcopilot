<?php
/**
 * Простой тест API для проверки загрузки данных
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../app/Services/CharacterService.php';
    
    $characterService = new CharacterService();
    
    // Тест загрузки рас
    $races = $characterService->getRaces();
    echo "Расы загружены: " . count($races) . "\n";
    
    if (count($races) > 0) {
        echo "Первая раса: " . json_encode($races[0], JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    // Тест загрузки классов
    $classes = $characterService->getClasses();
    echo "Классы загружены: " . count($classes) . "\n";
    
    if (count($classes) > 0) {
        echo "Первый класс: " . json_encode($classes[0], JSON_UNESCAPED_UNICODE) . "\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    echo "Трассировка: " . $e->getTraceAsString() . "\n";
}
?>
