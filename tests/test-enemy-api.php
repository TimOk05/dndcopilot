<?php
// Тест API генерации противников
require_once 'api/generate-enemies.php';

echo "Тестирование API генерации противников...\n";

// Создаем экземпляр генератора
$generator = new EnemyGenerator();

// Тестовые параметры
$test_params = [
    'threat_level' => 'medium',
    'count' => 1,
    'enemy_type' => '',
    'environment' => '',
    'use_ai' => 'on'
];

echo "Параметры: " . json_encode($test_params, JSON_UNESCAPED_UNICODE) . "\n";

try {
    $result = $generator->generateEnemies($test_params);
    echo "Результат: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
