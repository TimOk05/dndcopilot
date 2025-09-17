<?php
// Отладочный тест генератора противников
require_once 'config/config.php';
require_once 'public/api/generate-enemies.php';

echo "=== ОТЛАДОЧНЫЙ ТЕСТ ГЕНЕРАТОРА ПРОТИВНИКОВ ===\n\n";

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
        'use_ai' => 'on'
    ];
    
    echo "Параметры: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n";
    
    try {
        $result = $generator->generateEnemies($params);
        
        if ($result['success']) {
            echo "✅ Успешно сгенерировано " . $result['count'] . " противников\n";
            echo "CR диапазон: " . $result['cr_range']['display'] . "\n";
            
            if (!empty($result['enemies'])) {
                $enemy = $result['enemies'][0];
                echo "Первый противник: " . $enemy['name'] . " (CR " . $enemy['challenge_rating'] . ")\n";
            }
        } else {
            echo "❌ Ошибка: " . $result['error'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Исключение: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "--- Анализ проблем ---\n";

// Проверяем доступность функций
echo "Доступные функции:\n";
echo "  curl_init: " . (function_exists('curl_init') ? '✅' : '❌') . "\n";
echo "  file_get_contents: " . (function_exists('file_get_contents') ? '✅' : '❌') . "\n";
echo "  stream_context_create: " . (function_exists('stream_context_create') ? '✅' : '❌') . "\n";

// Проверяем настройки PHP
echo "\nНастройки PHP:\n";
echo "  allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "\n";
echo "  user_agent: " . ini_get('user_agent') . "\n";
echo "  default_socket_timeout: " . ini_get('default_socket_timeout') . "\n";

// Проверяем кэш
echo "\nКэш:\n";
$cache_dir = 'data/cache/dnd_api/';
if (is_dir($cache_dir)) {
    $cache_files = glob($cache_dir . '*.json');
    echo "  Кэшированных файлов: " . count($cache_files) . "\n";
    
    if (count($cache_files) > 0) {
        echo "  Примеры файлов:\n";
        foreach (array_slice($cache_files, 0, 5) as $file) {
            echo "    - " . basename($file) . "\n";
        }
    }
} else {
    echo "  ❌ Директория кэша не найдена\n";
}

echo "\n--- Рекомендации ---\n";
echo "1. Проблема: Отсутствуют расширения curl и openssl в PHP\n";
echo "2. Решение: Установить расширения или использовать сервер с полной поддержкой PHP\n";
echo "3. Альтернатива: Создать локальную базу данных монстров с CR\n";
echo "4. Временное решение: Использовать fallback данные (нарушает NO_FALLBACK политику)\n";
