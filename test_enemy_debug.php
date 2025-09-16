<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/public/api/generate-enemies.php';

echo "=== Диагностика генерации противников ===\n";

// Симулируем локальную разработку
$_SERVER['HTTP_HOST'] = 'localhost';

try {
    $generator = new EnemyGenerator();
    
    // Тест 1: Простая генерация
    echo "\n--- Тест 1: Простая генерация ---\n";
    $result = $generator->generateEnemies([
        'threat_level' => 'easy',
        'count' => 1,
        'enemy_type' => '',
        'environment' => '',
        'use_ai' => 'on'
    ]);
    
    echo "Результат: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    
    // Тест 2: Проверка метода getMonstersListWithRetry
    echo "\n--- Тест 2: Проверка списка монстров ---\n";
    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('getMonstersListWithRetry');
    $method->setAccessible(true);
    
    $monstersList = $method->invoke($generator);
    echo "Список монстров получен: " . (is_array($monstersList) ? "Да, " . count($monstersList) . " монстров" : "Нет") . "\n";
    
    if (is_array($monstersList) && isset($monstersList['results'])) {
        echo "Первые 3 монстра:\n";
        for ($i = 0; $i < min(3, count($monstersList['results'])); $i++) {
            $monster = $monstersList['results'][$i];
            echo "  - " . $monster['name'] . " (index: " . $monster['index'] . ")\n";
        }
    }
    
    // Тест 3: Проверка фильтрации
    echo "\n--- Тест 3: Проверка фильтрации ---\n";
    $method = $reflection->getMethod('filterMonstersByCriteria');
    $method->setAccessible(true);
    
    $filtered = $method->invoke($generator, $monstersList, [
        'threat_level' => 'easy',
        'enemy_type' => '',
        'environment' => ''
    ]);
    
    echo "Отфильтровано монстров: " . (is_array($filtered) ? count($filtered) : "Ошибка") . "\n";
    
    if (is_array($filtered) && count($filtered) > 0) {
        echo "Первый отфильтрованный монстр: " . $filtered[0]['name'] . "\n";
    }
    
    // Тест 4: Проверка генерации одного противника
    echo "\n--- Тест 4: Генерация одного противника ---\n";
    if (is_array($filtered) && count($filtered) > 0) {
        $method = $reflection->getMethod('generateSingleEnemy');
        $method->setAccessible(true);
        
        $enemy = $method->invoke($generator, $filtered[0], [
            'threat_level' => 'easy',
            'enemy_type' => '',
            'environment' => '',
            'use_ai' => 'on'
        ]);
        
        echo "Противник сгенерирован: " . (is_array($enemy) ? "Да" : "Нет") . "\n";
        if (is_array($enemy)) {
            echo "Название: " . ($enemy['name'] ?? 'Не указано') . "\n";
            echo "Тип: " . ($enemy['type'] ?? 'Не указан') . "\n";
            echo "CR: " . ($enemy['challenge_rating'] ?? 'Не указан') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Исключение: " . $e->getMessage() . "\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Стек вызовов:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Диагностика завершена ===\n";
