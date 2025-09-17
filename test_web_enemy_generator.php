<?php
// Тест генератора противников через веб-интерфейс
echo "=== ТЕСТ ГЕНЕРАТОРА ПРОТИВНИКОВ ЧЕРЕЗ ВЕБ-ИНТЕРФЕЙС ===\n\n";

// Симулируем POST запрос
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'threat_level' => 'medium',
    'count' => 1,
    'enemy_type' => '',
    'environment' => '',
    'use_ai' => 'on'
];

// Захватываем вывод
ob_start();

// Подключаем генератор
require_once 'public/api/generate-enemies.php';

$output = ob_get_clean();

echo "--- Результат генерации ---\n";
echo $output . "\n";

// Парсим JSON ответ
$result = json_decode($output, true);

if ($result && $result['success']) {
    echo "✅ Генерация успешна!\n";
    echo "Количество противников: " . $result['count'] . "\n";
    echo "Уровень сложности: " . $result['threat_level_display'] . "\n";
    
    if (!empty($result['enemies'])) {
        $enemy = $result['enemies'][0];
        echo "\n--- Первый противник ---\n";
        echo "Название: " . $enemy['name'] . "\n";
        echo "CR: " . $enemy['challenge_rating'] . "\n";
        echo "Тип: " . $enemy['type'] . "\n";
        echo "HP: " . $enemy['hit_points'] . "\n";
        echo "AC: " . $enemy['armor_class'] . "\n";
        
        if (isset($enemy['description'])) {
            echo "Описание: " . substr($enemy['description'], 0, 100) . "...\n";
        }
        
        if (isset($enemy['tactics'])) {
            echo "Тактика: " . substr($enemy['tactics'], 0, 100) . "...\n";
        }
    }
} else {
    echo "❌ Ошибка генерации: " . ($result['error'] ?? 'Неизвестная ошибка') . "\n";
}

echo "\n--- Тест разных уровней сложности ---\n";

$test_levels = ['easy', 'hard', 'deadly', '5', '10'];

foreach ($test_levels as $level) {
    $_POST['threat_level'] = $level;
    
    ob_start();
    require_once 'public/api/generate-enemies.php';
    $output = ob_get_clean();
    
    $result = json_decode($output, true);
    
    if ($result && $result['success']) {
        $enemy = $result['enemies'][0];
        echo "✅ $level: " . $enemy['name'] . " (CR " . $enemy['challenge_rating'] . ")\n";
    } else {
        echo "❌ $level: " . ($result['error'] ?? 'Ошибка') . "\n";
    }
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";
?>
