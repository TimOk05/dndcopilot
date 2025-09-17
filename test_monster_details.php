<?php
// Тест получения детальной информации о монстрах
require_once 'config/config.php';

echo "=== ТЕСТ ПОЛУЧЕНИЯ ДЕТАЛЬНОЙ ИНФОРМАЦИИ О МОНСТРАХ ===\n\n";

// Загружаем кэшированный список монстров
$monsters_cache_file = 'data/logs/cache/monsters_list.json';
if (!file_exists($monsters_cache_file)) {
    echo "❌ Файл кэша монстров не найден: $monsters_cache_file\n";
    exit(1);
}

$monsters_data = json_decode(file_get_contents($monsters_cache_file), true);
echo "✅ Загружено " . $monsters_data['count'] . " монстров из кэша\n\n";

// Выбираем несколько монстров для детального анализа
$test_monsters = [
    'goblin',      // CR 1/4
    'orc',         // CR 1/2  
    'ogre',        // CR 2
    'troll',       // CR 5
    'fire-giant',  // CR 9
    'ancient-red-dragon' // CR 24
];

echo "--- Тестирование получения CR для конкретных монстров ---\n";

foreach ($test_monsters as $monster_index) {
    echo "\nТестирование монстра: $monster_index\n";
    
    // Ищем монстра в кэше
    $monster_found = null;
    foreach ($monsters_data['results'] as $monster) {
        if ($monster['index'] === $monster_index) {
            $monster_found = $monster;
            break;
        }
    }
    
    if (!$monster_found) {
        echo "  ❌ Монстр не найден в кэше\n";
        continue;
    }
    
    echo "  Название: " . $monster_found['name'] . "\n";
    echo "  URL: " . $monster_found['url'] . "\n";
    
    // Пытаемся получить детальную информацию
    $monster_url = 'https://www.dnd5eapi.co' . $monster_found['url'];
    echo "  Полный URL: $monster_url\n";
    
    // Проверяем, есть ли кэшированная детальная информация
    $cache_file = 'data/cache/dnd_api/' . $monster_index . '.json';
    if (file_exists($cache_file)) {
        echo "  ✅ Найден кэш: $cache_file\n";
        $monster_details = json_decode(file_get_contents($cache_file), true);
        
        if (isset($monster_details['challenge_rating'])) {
            echo "  CR: " . $monster_details['challenge_rating'] . "\n";
        } else {
            echo "  ⚠️  CR не найден в кэше\n";
        }
        
        if (isset($monster_details['type'])) {
            echo "  Тип: " . $monster_details['type'] . "\n";
        }
        
        if (isset($monster_details['size'])) {
            echo "  Размер: " . $monster_details['size'] . "\n";
        }
        
        if (isset($monster_details['hit_points'])) {
            echo "  HP: " . $monster_details['hit_points'] . "\n";
        }
        
        if (isset($monster_details['armor_class'])) {
            echo "  AC: " . $monster_details['armor_class'][0]['value'] . "\n";
        }
        
    } else {
        echo "  ❌ Кэш не найден: $cache_file\n";
    }
}

echo "\n--- Анализ кэшированных данных ---\n";

// Проверяем, какие монстры имеют кэшированную информацию
$cache_dir = 'data/cache/dnd_api/';
if (is_dir($cache_dir)) {
    $cached_files = glob($cache_dir . '*.json');
    echo "Найдено " . count($cached_files) . " кэшированных файлов монстров\n";
    
    if (count($cached_files) > 0) {
        echo "\nАнализ CR из кэшированных данных:\n";
        
        $cr_distribution = [];
        $monsters_with_cr = 0;
        
        foreach ($cached_files as $file) {
            $monster_data = json_decode(file_get_contents($file), true);
            if (isset($monster_data['challenge_rating'])) {
                $cr = $monster_data['challenge_rating'];
                if (!isset($cr_distribution[$cr])) {
                    $cr_distribution[$cr] = 0;
                }
                $cr_distribution[$cr]++;
                $monsters_with_cr++;
            }
        }
        
        echo "Монстров с известным CR: $monsters_with_cr\n";
        
        if (count($cr_distribution) > 0) {
            echo "\nРаспределение по CR:\n";
            ksort($cr_distribution);
            foreach ($cr_distribution as $cr => $count) {
                echo "  CR $cr: $count монстров\n";
            }
        }
    }
} else {
    echo "❌ Директория кэша не найдена: $cache_dir\n";
}

echo "\n--- Рекомендации ---\n";
echo "1. Нужно получить детальную информацию о всех монстрах из API\n";
echo "2. Создать полную базу данных CR для всех монстров\n";
echo "3. Использовать реальные CR данные вместо приблизительных\n";
