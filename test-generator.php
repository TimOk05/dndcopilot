<?php
/**
 * Тестовый файл для проверки работы генераторов
 */

require_once 'config.php';
require_once 'api/fallback-data.php';

echo "<h1>Тест генераторов D&D Copilot</h1>\n";

// Тест 1: Проверка загрузки данных
echo "<h2>1. Тест загрузки данных</h2>\n";
try {
    $fallbackData = new FallbackData();
    $allData = $fallbackData->getAllData();
    
    echo "<p>✅ Данные успешно загружены:</p>\n";
    echo "<ul>\n";
    echo "<li>Рас: " . count($allData['races']) . "</li>\n";
    echo "<li>Классов: " . count($allData['classes']) . "</li>\n";
    echo "<li>Противников: " . count($allData['enemies']) . "</li>\n";
    echo "</ul>\n";
} catch (Exception $e) {
    echo "<p>❌ Ошибка загрузки данных: " . $e->getMessage() . "</p>\n";
}

// Тест 2: Проверка загрузки JSON файлов
echo "<h2>2. Тест загрузки JSON файлов</h2>\n";

// Проверка файла с именами
$namesFile = 'pdf/dnd_race_names_ru_v2.json';
if (file_exists($namesFile)) {
    $namesData = json_decode(file_get_contents($namesFile), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p>✅ Файл с именами загружен успешно</p>\n";
        echo "<p>Доступно рас: " . count($namesData['data']) . "</p>\n";
    } else {
        echo "<p>❌ Ошибка парсинга JSON файла с именами: " . json_last_error_msg() . "</p>\n";
    }
} else {
    echo "<p>❌ Файл с именами не найден: $namesFile</p>\n";
}

// Проверка файла с профессиями
$occupationsFile = 'pdf/d100_unique_traders.json';
if (file_exists($occupationsFile)) {
    $occupationsData = json_decode(file_get_contents($occupationsFile), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p>✅ Файл с профессиями загружен успешно</p>\n";
        if (isset($occupationsData['data']['occupations'])) {
            echo "<p>Доступно профессий: " . count($occupationsData['data']['occupations']) . "</p>\n";
        }
    } else {
        echo "<p>❌ Ошибка парсинга JSON файла с профессиями: " . json_last_error_msg() . "</p>\n";
    }
} else {
    echo "<p>❌ Файл с профессиями не найден: $occupationsFile</p>\n";
}

// Тест 3: Тест генерации персонажа
echo "<h2>3. Тест генерации персонажа</h2>\n";
try {
    require_once 'api/generate-characters-v2.php';
    $generator = new CharacterGeneratorV2();
    
    $testParams = [
        'race' => 'human',
        'class' => 'fighter',
        'level' => 1,
        'alignment' => 'neutral',
        'gender' => 'random',
        'use_ai' => 'off'
    ];
    
    $result = $generator->generateCharacter($testParams);
    
    if ($result['success']) {
        echo "<p>✅ Персонаж успешно сгенерирован:</p>\n";
        $character = $result['character'];
        echo "<ul>\n";
        echo "<li>Имя: " . $character['name'] . "</li>\n";
        echo "<li>Раса: " . $character['race'] . "</li>\n";
        echo "<li>Класс: " . $character['class'] . "</li>\n";
        echo "<li>Уровень: " . $character['level'] . "</li>\n";
        echo "<li>Хиты: " . $character['hit_points'] . "</li>\n";
        echo "<li>КД: " . $character['armor_class'] . "</li>\n";
        echo "<li>Профессия: " . $character['occupation'] . "</li>\n";
        echo "</ul>\n";
        
        echo "<p><strong>Характеристики:</strong></p>\n";
        echo "<ul>\n";
        foreach ($character['abilities'] as $ability => $value) {
            $modifier = floor(($value - 10) / 2);
            $modStr = $modifier >= 0 ? "+$modifier" : $modifier;
            echo "<li>" . strtoupper($ability) . ": $value ($modStr)</li>\n";
        }
        echo "</ul>\n";
        
        echo "<p><strong>Описание:</strong> " . $character['description'] . "</p>\n";
        echo "<p><strong>Предыстория:</strong> " . $character['background'] . "</p>\n";
        
    } else {
        echo "<p>❌ Ошибка генерации персонажа: " . $result['error'] . "</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Ошибка теста генерации персонажа: " . $e->getMessage() . "</p>\n";
}

// Тест 4: Тест генерации противника
echo "<h2>4. Тест генерации противника</h2>\n";
try {
    require_once 'api/generate-enemies.php';
    $enemyGenerator = new EnemyGenerator();
    
    $testParams = [
        'threat_level' => 'medium',
        'count' => 1,
        'enemy_type' => '',
        'environment' => '',
        'use_ai' => 'off'
    ];
    
    $result = $enemyGenerator->generateEnemies($testParams);
    
    if ($result['success']) {
        echo "<p>✅ Противник успешно сгенерирован:</p>\n";
        $enemy = $result['enemies'][0];
        echo "<ul>\n";
        echo "<li>Имя: " . $enemy['name'] . "</li>\n";
        echo "<li>Тип: " . $enemy['type'] . "</li>\n";
        echo "<li>CR: " . $enemy['cr'] . "</li>\n";
        echo "<li>Хиты: " . $enemy['hp'] . "</li>\n";
        echo "<li>КД: " . $enemy['ac'] . "</li>\n";
        echo "</ul>\n";
        
        if (isset($enemy['description'])) {
            echo "<p><strong>Описание:</strong> " . $enemy['description'] . "</p>\n";
        }
        if (isset($enemy['tactics'])) {
            echo "<p><strong>Тактика:</strong> " . $enemy['tactics'] . "</p>\n";
        }
        
    } else {
        echo "<p>❌ Ошибка генерации противника: " . $result['error'] . "</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Ошибка теста генерации противника: " . $e->getMessage() . "</p>\n";
}

// Тест 5: Тест мобильного API
echo "<h2>5. Тест мобильного API</h2>\n";
try {
    require_once 'mobile-api.php';
    
    // Симулируем POST запрос
    $_POST = [
        'action' => 'generate_character',
        'race' => 'elf',
        'class' => 'wizard',
        'level' => '3'
    ];
    
    // Захватываем вывод
    ob_start();
    include 'mobile-api.php';
    $output = ob_get_clean();
    
    $response = json_decode($output, true);
    
    if ($response && $response['success']) {
        echo "<p>✅ Мобильный API работает корректно:</p>\n";
        $data = $response['data'];
        echo "<ul>\n";
        echo "<li>Имя: " . $data['name'] . "</li>\n";
        echo "<li>Раса: " . $data['race'] . "</li>\n";
        echo "<li>Класс: " . $data['class'] . "</li>\n";
        echo "<li>Уровень: " . $data['level'] . "</li>\n";
        echo "<li>Хиты: " . $data['hp'] . "</li>\n";
        echo "<li>КД: " . $data['ac'] . "</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p>❌ Ошибка мобильного API: " . ($response['message'] ?? 'Неизвестная ошибка') . "</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Ошибка теста мобильного API: " . $e->getMessage() . "</p>\n";
}

// Тест 6: Проверка логов
echo "<h2>6. Проверка системы логирования</h2>\n";
try {
    $logFile = 'logs/app.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        if (strlen($logContent) > 0) {
            echo "<p>✅ Лог файл существует и содержит данные</p>\n";
            echo "<p>Размер файла: " . strlen($logContent) . " байт</p>\n";
        } else {
            echo "<p>⚠️ Лог файл пуст</p>\n";
        }
    } else {
        echo "<p>❌ Лог файл не найден</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Ошибка проверки логов: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Результаты тестирования</h2>\n";
echo "<p>Тестирование завершено. Проверьте результаты выше.</p>\n";
echo "<p><a href='index.php'>Вернуться к главной странице</a></p>\n";
?>
