<?php
/**
 * Простой тест API генератора зелий
 */

echo "<h1>🧪 Тест API генератора зелий</h1>\n";

// Тест 1: Базовая генерация
echo "<h2>Тест 1: Базовая генерация</h2>\n";
try {
    $url = 'http://localhost/api/generate-potions.php?action=random&count=2';
    $response = file_get_contents($url);
    
    if ($response === false) {
        echo "<p style='color: red;'>❌ Ошибка: не удалось получить ответ от API</p>\n";
    } else {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "<p style='color: green;'>✅ API работает! Сгенерировано зелий: " . count($data['data']) . "</p>\n";
                echo "<pre>" . print_r($data, true) . "</pre>\n";
            } else {
                echo "<p style='color: orange;'>⚠️ API вернул ошибку: " . ($data['error'] ?? 'неизвестная ошибка') . "</p>\n";
            }
        } else {
            echo "<p style='color: red;'>❌ Ошибка: неверный формат ответа</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Исключение: " . $e->getMessage() . "</p>\n";
}

// Тест 2: Проверка D&D API
echo "<h2>Тест 2: Проверка D&D API</h2>\n";
try {
    $dnd_url = 'https://www.dnd5eapi.co/api/magic-items';
    $dnd_response = file_get_contents($dnd_url);
    
    if ($dnd_response === false) {
        echo "<p style='color: red;'>❌ Ошибка: D&D API недоступен</p>\n";
    } else {
        $dnd_data = json_decode($dnd_response, true);
        if ($dnd_data && isset($dnd_data['results'])) {
            echo "<p style='color: green;'>✅ D&D API доступен! Найдено предметов: " . count($dnd_data['results']) . "</p>\n";
            
            // Ищем зелья
            $potions = 0;
            foreach ($dnd_data['results'] as $item) {
                $name = strtolower($item['name']);
                if (strpos($name, 'potion') !== false || 
                    strpos($name, 'elixir') !== false || 
                    strpos($name, 'philter') !== false) {
                    $potions++;
                }
            }
            echo "<p>Найдено потенциальных зелий: $potions</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Ошибка: неверный формат ответа D&D API</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Исключение при обращении к D&D API: " . $e->getMessage() . "</p>\n";
}

// Тест 3: Проверка файлов
echo "<h2>Тест 3: Проверка файлов</h2>\n";

$files_to_check = [
    'api/generate-potions.php' => 'API генератора зелий',
    'index.php' => 'Основной файл сайта',
    'utilities.css' => 'Стили',
    'test-potion-integration.html' => 'Тестовый файл'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $description: $file</p>\n";
    } else {
        echo "<p style='color: red;'>❌ $description: $file - не найден</p>\n";
    }
}

// Тест 4: Проверка директорий
echo "<h2>Тест 4: Проверка директорий</h2>\n";

$dirs_to_check = [
    'logs/cache' => 'Директория кеша',
    'api' => 'Директория API'
];

foreach ($dirs_to_check as $dir => $description) {
    if (is_dir($dir)) {
        echo "<p style='color: green;'>✅ $description: $dir</p>\n";
        
        // Проверяем права на запись
        if (is_writable($dir)) {
            echo "<p style='color: green;'>  ✅ Права на запись есть</p>\n";
        } else {
            echo "<p style='color: orange;'>  ⚠️ Нет прав на запись</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ $description: $dir - не найдена</p>\n";
    }
}

echo "<hr>\n";
echo "<p><strong>Тест завершен.</strong></p>\n";
echo "<p>Для полного тестирования откройте <a href='test-potion-integration.html'>test-potion-integration.html</a></p>\n";
?>
