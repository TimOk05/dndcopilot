<?php
// Тестирование генератора противников без AI
require_once 'config/config.php';

// Подключаем генератор противников
require_once 'public/api/generate-enemies.php';

echo "=== ТЕСТИРОВАНИЕ ГЕНЕРАТОРА ПРОТИВНИКОВ ===\n\n";

$generator = new EnemyGenerator();

// Тестовые параметры для разных уровней сложности
$test_cases = [
    ['name' => 'Легкий уровень (CR 0-3)', 'threat_level' => 'easy'],
    ['name' => 'Средний уровень (CR 1-4)', 'threat_level' => 'medium'],
    ['name' => 'Сложный уровень (CR 2-6)', 'threat_level' => 'hard'],
    ['name' => 'Смертельный уровень (CR 5-12)', 'threat_level' => 'deadly'],
    ['name' => 'Конкретный CR 1', 'threat_level' => '1'],
    ['name' => 'Конкретный CR 5', 'threat_level' => '5'],
    ['name' => 'Конкретный CR 10', 'threat_level' => '10'],
];

foreach ($test_cases as $test_case) {
    echo "--- Тестирование: {$test_case['name']} ---\n";
    
    $params = [
        'threat_level' => $test_case['threat_level'],
        'count' => 1,
        'enemy_type' => '',
        'environment' => '',
        'use_ai' => 'off' // Отключаем AI для чистого тестирования API
    ];
    
    try {
        $result = $generator->generateEnemies($params);
        
        if ($result['success']) {
            echo "✅ УСПЕХ: Найдено " . count($result['enemies']) . " противников\n";
            echo "   Уровень угрозы: {$result['threat_level_display']}\n";
            echo "   CR диапазон: {$result['cr_range']['display']}\n";
            
            if (!empty($result['enemies'])) {
                $enemy = $result['enemies'][0];
                echo "   Пример противника: {$enemy['name']} (CR: {$enemy['challenge_rating']})\n";
            }
        } else {
            echo "❌ ОШИБКА: {$result['error']}\n";
        }
        
    } catch (Exception $e) {
        echo "❌ ИСКЛЮЧЕНИЕ: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== ТЕСТИРОВАНИЕ ЗАВЕРШЕНО ===\n";
?>
