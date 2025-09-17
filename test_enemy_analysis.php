<?php
// Анализ генератора противников с использованием кэшированных данных
require_once 'config/config.php';

echo "=== АНАЛИЗ ГЕНЕРАТОРА ПРОТИВНИКОВ ===\n\n";

// Загружаем кэшированный список монстров
$monsters_cache_file = 'data/logs/cache/monsters_list.json';
if (!file_exists($monsters_cache_file)) {
    echo "❌ Файл кэша монстров не найден: $monsters_cache_file\n";
    exit(1);
}

$monsters_data = json_decode(file_get_contents($monsters_cache_file), true);
echo "✅ Загружено " . $monsters_data['count'] . " монстров из кэша\n\n";

// Анализируем распределение монстров по CR
echo "--- Анализ распределения монстров ---\n";

// Создаем массив для анализа CR
$cr_distribution = [];
$monsters_by_cr = [];

foreach ($monsters_data['results'] as $monster) {
    // Извлекаем CR из имени монстра (примерная логика)
    $name = $monster['name'];
    
    // Простая логика определения CR на основе имени
    $cr = 'unknown';
    
    if (strpos($name, 'Wyrmling') !== false) {
        $cr = '1/4';
    } elseif (strpos($name, 'Young') !== false) {
        $cr = '10';
    } elseif (strpos($name, 'Adult') !== false) {
        $cr = '15';
    } elseif (strpos($name, 'Ancient') !== false) {
        $cr = '20';
    } elseif (strpos($name, 'Goblin') !== false || strpos($name, 'Kobold') !== false) {
        $cr = '1/4';
    } elseif (strpos($name, 'Orc') !== false || strpos($name, 'Bandit') !== false) {
        $cr = '1/2';
    } elseif (strpos($name, 'Ogre') !== false) {
        $cr = '2';
    } elseif (strpos($name, 'Troll') !== false) {
        $cr = '5';
    } elseif (strpos($name, 'Giant') !== false) {
        $cr = '7';
    } elseif (strpos($name, 'Dragon') !== false) {
        $cr = '10';
    } elseif (strpos($name, 'Lich') !== false) {
        $cr = '21';
    } elseif (strpos($name, 'Tarrasque') !== false) {
        $cr = '30';
    } else {
        // Попробуем извлечь CR из URL или других признаков
        $cr = '1'; // По умолчанию
    }
    
    if (!isset($cr_distribution[$cr])) {
        $cr_distribution[$cr] = 0;
        $monsters_by_cr[$cr] = [];
    }
    $cr_distribution[$cr]++;
    $monsters_by_cr[$cr][] = $name;
}

// Сортируем CR для лучшего отображения
ksort($cr_distribution);

echo "Распределение монстров по CR:\n";
foreach ($cr_distribution as $cr => $count) {
    echo "  CR $cr: $count монстров\n";
}

echo "\n--- Тестирование логики генератора ---\n";

// Тестируем логику определения CR диапазонов
$test_cases = [
    'easy' => [0, 3],
    'medium' => [1, 4], 
    'hard' => [2, 6],
    'deadly' => [5, 12]
];

foreach ($test_cases as $threat_level => $cr_range) {
    echo "\nТестирование уровня сложности: $threat_level (CR $cr_range[0]-$cr_range[1])\n";
    
    $suitable_monsters = [];
    foreach ($monsters_by_cr as $cr => $monsters) {
        $cr_numeric = parseCR($cr);
        if ($cr_numeric !== null && $cr_numeric >= $cr_range[0] && $cr_numeric <= $cr_range[1]) {
            $suitable_monsters = array_merge($suitable_monsters, $monsters);
        }
    }
    
    echo "  Найдено подходящих монстров: " . count($suitable_monsters) . "\n";
    
    if (count($suitable_monsters) > 0) {
        echo "  Примеры: " . implode(', ', array_slice($suitable_monsters, 0, 5)) . "\n";
    } else {
        echo "  ⚠️  Нет подходящих монстров для этого уровня сложности!\n";
    }
}

echo "\n--- Анализ проблем ---\n";

// Проверяем наличие монстров для разных CR
$problematic_cr = [];
foreach ($test_cases as $threat_level => $cr_range) {
    $has_monsters = false;
    for ($cr = $cr_range[0]; $cr <= $cr_range[1]; $cr++) {
        if (isset($monsters_by_cr[$cr]) && count($monsters_by_cr[$cr]) > 0) {
            $has_monsters = true;
            break;
        }
    }
    
    if (!$has_monsters) {
        $problematic_cr[] = $threat_level;
    }
}

if (count($problematic_cr) > 0) {
    echo "❌ Проблемы найдены для уровней сложности: " . implode(', ', $problematic_cr) . "\n";
    echo "   Возможные причины:\n";
    echo "   1. Неправильная логика определения CR из имени монстра\n";
    echo "   2. Отсутствие монстров для определенных CR в базе данных\n";
    echo "   3. Ошибки в логике фильтрации по CR\n";
} else {
    echo "✅ Все уровни сложности имеют подходящих монстров\n";
}

echo "\n--- Рекомендации ---\n";
echo "1. Нужно получить реальные данные CR для каждого монстра из API\n";
echo "2. Проверить логику определения CR диапазонов в генераторе\n";
echo "3. Добавить fallback логику для случаев отсутствия монстров\n";

/**
 * Парсинг CR в числовое значение
 */
function parseCR($cr) {
    if (is_numeric($cr)) {
        return (float)$cr;
    }
    
    // Обработка дробных CR (1/4, 1/2, 1/8)
    if (strpos($cr, '/') !== false) {
        $parts = explode('/', $cr);
        if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            return (float)$parts[0] / (float)$parts[1];
        }
    }
    
    return null;
}
