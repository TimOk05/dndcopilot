<?php
// Простой тест генератора противников
require_once 'config/config.php';
require_once 'public/api/generate-enemies.php';

echo "=== ПРОСТОЙ ТЕСТ ГЕНЕРАТОРА ПРОТИВНИКОВ ===\n\n";

$generator = new EnemyGenerator();

// Тест 1: Легкий уровень
echo "--- Тест 1: Легкий уровень ---\n";
$params = [
    'threat_level' => 'easy',
    'count' => 1,
    'enemy_type' => '',
    'environment' => '',
    'use_ai' => 'on'
];

$result = $generator->generateEnemies($params);

if ($result['success']) {
    echo "✅ Успешно: " . $result['enemies'][0]['name'] . " (CR " . $result['enemies'][0]['challenge_rating'] . ")\n";
} else {
    echo "❌ Ошибка: " . $result['error'] . "\n";
}

// Тест 2: Сложный уровень
echo "\n--- Тест 2: Сложный уровень ---\n";
$params['threat_level'] = 'hard';

$result = $generator->generateEnemies($params);

if ($result['success']) {
    echo "✅ Успешно: " . $result['enemies'][0]['name'] . " (CR " . $result['enemies'][0]['challenge_rating'] . ")\n";
} else {
    echo "❌ Ошибка: " . $result['error'] . "\n";
}

// Тест 3: Конкретный CR
echo "\n--- Тест 3: CR 5 ---\n";
$params['threat_level'] = '5';

$result = $generator->generateEnemies($params);

if ($result['success']) {
    echo "✅ Успешно: " . $result['enemies'][0]['name'] . " (CR " . $result['enemies'][0]['challenge_rating'] . ")\n";
} else {
    echo "❌ Ошибка: " . $result['error'] . "\n";
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";
?>
