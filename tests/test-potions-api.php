<?php
/**
 * Тест API зелий D&D
 * Проверяет доступность D&D 5e API и генерацию зелий
 */

require_once 'config.php';

echo "<h1>🧪 Тест API зелий D&D</h1>";
echo "<p><strong>Время выполнения теста:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Проверяем системные требования
echo "<h2>🔧 Проверка системных требований</h2>";

$curl_available = function_exists('curl_init');
$openssl_available = function_exists('openssl_encrypt');
$json_available = function_exists('json_encode');

echo "<p><strong>cURL:</strong> " . ($curl_available ? "✅ Доступен" : "❌ Недоступен") . "</p>";
echo "<p><strong>OpenSSL:</strong> " . ($openssl_available ? "✅ Доступен" : "❌ Недоступен") . "</p>";
echo "<p><strong>JSON:</strong> " . ($openssl_available ? "✅ Доступен" : "❌ Недоступен") . "</p>";

if (!$curl_available) {
    echo "<p style='color: red;'>❌ cURL недоступен. Генерация зелий невозможна.</p>";
    exit;
}

// Проверяем подключение к D&D API
echo "<h2>🌐 Проверка подключения к D&D API</h2>";

$dnd_api_url = 'https://www.dnd5eapi.co/api';
$test_url = $dnd_api_url . '/magic-items';

echo "<p><strong>Тестируемый URL:</strong> <a href='$test_url' target='_blank'>$test_url</a></p>";

// Тестируем подключение
$ch = curl_init($test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/1.0');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$curl_info = curl_getinfo($ch);
curl_close($ch);

echo "<p><strong>HTTP код:</strong> $http_code</p>";
echo "<p><strong>Ошибка cURL:</strong> " . ($error ?: "Нет") . "</p>";
echo "<p><strong>Время ответа:</strong> " . round($curl_info['total_time'], 3) . " сек</p>";

if ($error) {
    echo "<p style='color: red;'>❌ Ошибка cURL: $error</p>";
} elseif ($http_code !== 200) {
    echo "<p style='color: red;'>❌ HTTP ошибка: $http_code</p>";
                } else {
    echo "<p style='color: green;'>✅ Подключение к D&D API успешно</p>";
}

// Анализируем ответ
if ($response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ JSON ответ успешно декодирован</p>";
        
        if (isset($data['count'])) {
            echo "<p><strong>Всего магических предметов:</strong> " . $data['count'] . "</p>";
        }
        
        if (isset($data['results']) && is_array($data['results'])) {
            echo "<p><strong>Получено результатов:</strong> " . count($data['results']) . "</p>";
            
            // Показываем первые несколько предметов
            echo "<h3>📋 Первые магические предметы:</h3>";
            echo "<ul>";
            for ($i = 0; $i < min(5, count($data['results'])); $i++) {
                $item = $data['results'][$i];
                echo "<li><strong>" . htmlspecialchars($item['name']) . "</strong> - <a href='{$item['url']}' target='_blank'>API ссылка</a></li>";
            }
            echo "</ul>";
            
            // Ищем зелья
            echo "<h3>🧪 Поиск зелий:</h3>";
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
                echo "<ul>";
                foreach (array_slice($potions, 0, 5) as $potion) {
                    echo "<li><strong>" . htmlspecialchars($potion['name']) . "</strong> - <a href='{$potion['url']}' target='_blank'>API ссылка</a></li>";
                }
                echo "</ul>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Ошибка декодирования JSON: " . json_last_error_msg() . "</p>";
        echo "<p><strong>Сырой ответ:</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ Пустой ответ от API</p>";
}

// Тестируем генератор зелий
echo "<h2>🧪 Тест генератора зелий</h2>";

try {
    require_once 'api/generate-potions.php';
    $generator = new PotionGenerator();
    
    echo "<p style='color: green;'>✅ Класс PotionGenerator загружен</p>";
    
    // Тестируем генерацию
    $params = ['count' => 1];
    $result = $generator->generatePotions($params);
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ Генерация зелий успешна</p>";
        echo "<p><strong>Сгенерировано зелий:</strong> " . $result['count'] . "</p>";
        
        if (isset($result['data'][0])) {
            $potion = $result['data'][0];
            echo "<h3>🎯 Пример зелья:</h3>";
            echo "<ul>";
            echo "<li><strong>Название:</strong> " . htmlspecialchars($potion['name']) . "</li>";
            echo "<li><strong>Редкость:</strong> " . htmlspecialchars($potion['rarity']) . "</li>";
            echo "<li><strong>Тип:</strong> " . htmlspecialchars($potion['type']) . "</li>";
            echo "<li><strong>Описание:</strong> " . htmlspecialchars($potion['description']) . "</li>";
            echo "</ul>";
        }
                } else {
        echo "<p style='color: red;'>❌ Ошибка генерации зелий: " . htmlspecialchars($result['error']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Ошибка загрузки генератора зелий: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Проверяем кеш
echo "<h2>💾 Проверка кеша</h2>";

$cache_file = 'logs/cache/potions_cache.json';
if (file_exists($cache_file)) {
    $cache_size = filesize($cache_file);
    $cache_time = filemtime($cache_file);
    echo "<p><strong>Файл кеша:</strong> Существует</p>";
    echo "<p><strong>Размер:</strong> " . round($cache_size / 1024, 2) . " KB</p>";
    echo "<p><strong>Время изменения:</strong> " . date('Y-m-d H:i:s', $cache_time) . "</p>";
    
    $cache_data = json_decode(file_get_contents($cache_file), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ Кеш корректно декодируется</p>";
        if (isset($cache_data['potions'])) {
            echo "<p><strong>Зелий в кеше:</strong> " . count($cache_data['potions']) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Ошибка декодирования кеша</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Файл кеша не найден</p>";
}

echo "<h2>📝 Рекомендации</h2>";

if (!$curl_available) {
    echo "<p style='color: red;'>❌ Установите cURL расширение для PHP</p>";
} elseif ($http_code !== 200) {
    echo "<p style='color: orange;'>⚠️ Проблемы с подключением к D&D API. Проверьте интернет-соединение.</p>";
} elseif (empty($potions)) {
    echo "<p style='color: orange;'>⚠️ Зелья не найдены в API. Возможно, изменилась структура данных.</p>";
} else {
    echo "<p style='color: green;'>✅ API зелий работает корректно</p>";
}

echo "<p><strong>Тест завершен:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
