<?php
// Простой тест для отладки API зелий
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ТЕСТ API ЗЕЛИЙ ===\n\n";

// Подключаем класс
require_once 'api/generate-potions.php';

try {
    echo "1. Создаем экземпляр PotionGenerator...\n";
    $generator = new PotionGenerator();
    echo "✓ Класс загружен успешно\n\n";
    
    echo "2. Тестируем генерацию 1 зелья...\n";
    $result = $generator->generatePotions(1);
    
    echo "3. Результат:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if ($result['success']) {
        echo "✓ Генерация зелий работает!\n";
        echo "Количество зелий: " . count($result['data']) . "\n";
    } else {
        echo "✗ Ошибка: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Критическая ошибка: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . "\n";
    echo "Строка: " . $e->getLine() . "\n";
}

echo "\n=== КОНЕЦ ТЕСТА ===\n";
?>
