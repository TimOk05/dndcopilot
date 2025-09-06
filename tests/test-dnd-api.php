<?php
// Тест подключения к D&D 5e API
header('Content-Type: text/plain; charset=utf-8');

echo "Тестируем подключение к D&D 5e API...\n\n";

// Тест 1: Проверяем доступность config.php
if (file_exists('config.php')) {
    echo "✓ config.php найден\n";
    require_once 'config.php';
} else {
    echo "✗ config.php не найден\n";
    exit;
}

// Тест 2: Проверяем папку cache
$cache_dir = __DIR__ . '/logs/cache';
if (is_dir($cache_dir)) {
    echo "✓ Папка cache существует\n";
} else {
    echo "✗ Папка cache не существует\n";
}

// Тест 3: Прямой запрос к D&D API
echo "\nТестируем прямое подключение к D&D 5e API...\n";

$dnd_api_url = 'https://www.dnd5eapi.co/api/magic-items';
echo "URL: $dnd_api_url\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 30,
        'user_agent' => 'DnD-Copilot/1.0'
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

echo "Отправляем запрос...\n";

$response = @file_get_contents($dnd_api_url, false, $context);

if ($response === false) {
    echo "✗ Ошибка при запросе к D&D API\n";
    $error = error_get_last();
    if ($error) {
        echo "Детали ошибки: " . print_r($error, true) . "\n";
    }
} else {
    echo "✓ Ответ получен от D&D API\n";
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "✗ Ошибка парсинга JSON: " . json_last_error_msg() . "\n";
    } else {
        echo "✓ JSON успешно распарсен\n";
        if (isset($data['results'])) {
            echo "✓ Найдено магических предметов: " . count($data['results']) . "\n";
            
            // Показываем первые несколько предметов
            echo "\nПервые 5 предметов:\n";
            for ($i = 0; $i < min(5, count($data['results'])); $i++) {
                $item = $data['results'][$i];
                echo "- {$item['name']} ({$item['rarity']})\n";
            }
            
            // Ищем зелья
            echo "\nИщем зелья...\n";
            $potions = [];
            foreach ($data['results'] as $item) {
                $name = strtolower($item['name']);
                if (strpos($name, 'potion') !== false || 
                    strpos($name, 'elixir') !== false || 
                    strpos($name, 'philter') !== false ||
                    strpos($name, 'oil') !== false) {
                    $potions[] = $item;
                }
            }
            
            echo "✓ Найдено зелий: " . count($potions) . "\n";
            if (!empty($potions)) {
                echo "Примеры зелий:\n";
                foreach (array_slice($potions, 0, 3) as $potion) {
                    echo "- {$potion['name']} ({$potion['rarity']})\n";
                }
            }
        } else {
            echo "✗ Неверная структура ответа от D&D API\n";
            echo "Структура ответа: " . print_r(array_keys($data), true) . "\n";
        }
    }
}

echo "\nТест завершен.\n";
?>
