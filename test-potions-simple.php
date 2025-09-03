<?php
/**
 * Простой тест генератора зелий без config.php
 */

echo "<h1>🧪 Простой тест генератора зелий</h1>";
echo "<p><strong>Время:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Проверяем доступные функции
echo "<h2>🔧 Проверка функций</h2>";
echo "<p><strong>file_get_contents:</strong> " . (function_exists('file_get_contents') ? "✅ Доступен" : "❌ Недоступен") . "</p>";
echo "<p><strong>stream_context_create:</strong> " . (function_exists('stream_context_create') ? "✅ Доступен" : "❌ Недоступен") . "</p>";
echo "<p><strong>json_decode:</strong> " . (function_exists('json_decode') ? "✅ Доступен" : "❌ Недоступен") . "</p>";

// Тестируем прямое подключение к D&D API
echo "<h2>🌐 Тест подключения к D&D API</h2>";

$test_url = 'https://www.dnd5eapi.co/api/magic-items';

try {
    // Создаем контекст для HTTPS
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: DnD-Copilot/1.0',
                'Accept: application/json',
                'Connection: close'
            ],
            'timeout' => 30,
            'follow_location' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    echo "<p><strong>Тестируемый URL:</strong> <a href='$test_url' target='_blank'>$test_url</a></p>";
    
    // Пытаемся получить данные
    $response = @file_get_contents($test_url, false, $context);
    
    if ($response !== false) {
        echo "<p style='color: green;'>✅ file_get_contents работает с HTTPS</p>";
        
        // Декодируем JSON
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✅ JSON успешно декодирован</p>";
            
            if (isset($data['count'])) {
                echo "<p><strong>Всего магических предметов:</strong> " . $data['count'] . "</p>";
            }
            
            if (isset($data['results']) && is_array($data['results'])) {
                echo "<p><strong>Получено результатов:</strong> " . count($data['results']) . "</p>";
                
                // Ищем зелья
                $potions = [];
                foreach ($data['results'] as $item) {
                    $name = strtolower($item['name']);
                    $potion_keywords = ['potion', 'elixir', 'philter', 'oil', 'tincture', 'essence', 'brew', 'concoction', 'draught', 'tonic', 'extract'];
                    
                    foreach ($potion_keywords as $keyword) {
                        if (strpos($name, $keyword) !== false) {
                            $potions[] = $item;
                            break;
                        }
                    }
                }
                
                echo "<p><strong>Найдено зелий:</strong> " . count($potions) . "</p>";
                
                if (!empty($potions)) {
                    echo "<h3>🧪 Примеры зелий:</h3>";
                    echo "<ul>";
                    foreach (array_slice($potions, 0, 5) as $potion) {
                        echo "<li><strong>" . htmlspecialchars($potion['name']) . "</strong></li>";
                    }
                    echo "</ul>";
                    
                    // Тестируем получение деталей первого зелья
                    if (!empty($potions)) {
                        echo "<h3>🔍 Тест получения деталей зелья</h3>";
                        $first_potion = $potions[0];
                        $detail_url = 'https://www.dnd5eapi.co' . $first_potion['url'];
                        
                        echo "<p><strong>URL деталей:</strong> <a href='$detail_url' target='_blank'>$detail_url</a></p>";
                        
                        $detail_response = @file_get_contents($detail_url, false, $context);
                        if ($detail_response !== false) {
                            $detail_data = json_decode($detail_response, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                echo "<p style='color: green;'>✅ Детали зелья получены</p>";
                                echo "<p><strong>Название:</strong> " . htmlspecialchars($detail_data['name']) . "</p>";
                                if (isset($detail_data['rarity']['name'])) {
                                    echo "<p><strong>Редкость:</strong> " . htmlspecialchars($detail_data['rarity']['name']) . "</p>";
                                }
                                if (isset($detail_data['desc']) && is_array($detail_data['desc'])) {
                                    echo "<p><strong>Описание:</strong> " . htmlspecialchars(implode(' ', $detail_data['desc'])) . "</p>";
                                }
                            } else {
                                echo "<p style='color: red;'>❌ Ошибка декодирования деталей</p>";
                            }
                        } else {
                            echo "<p style='color: red;'>❌ Не удалось получить детали зелья</p>";
                        }
                    }
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Ошибка декодирования JSON: " . json_last_error_msg() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ file_get_contents не работает с HTTPS</p>";
        
        // Пробуем альтернативный метод
        echo "<h3>🔄 Альтернативный метод через fsockopen</h3>";
        
        $host = 'www.dnd5eapi.co';
        $port = 443;
        
        $fp = @fsockopen($host, $port, $errno, $errstr, 10);
        if ($fp) {
            echo "<p style='color: green;'>✅ fsockopen работает с $host:$port</p>";
            fclose($fp);
        } else {
            echo "<p style='color: red;'>❌ fsockopen не работает: $errstr ($errno)</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Ошибка: " . $e->getMessage() . "</p>";
}

echo "<h2>📝 Рекомендации</h2>";

if (function_exists('file_get_contents') && function_exists('stream_context_create')) {
    echo "<p style='color: green;'>✅ Основные функции доступны</p>";
    echo "<p>Проблема может быть в настройках PHP или блокировке внешних соединений</p>";
} else {
    echo "<p style='color: red;'>❌ Критические функции недоступны</p>";
}

echo "<p><strong>Тест завершен:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
