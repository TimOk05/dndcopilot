<?php
/**
 * Простой тест API генерации зелий
 * Используйте для проверки работоспособности
 */

echo "<h1>🧪 Тест API генерации зелий</h1>";

// Проверяем доступность D&D API
echo "<h2>1. Проверка D&D API</h2>";
$dnd_api_url = 'https://www.dnd5eapi.co/api';

try {
    $ch = curl_init($dnd_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>❌ Ошибка CURL: $error</p>";
    } elseif ($http_code === 200) {
        echo "<p style='color: green;'>✅ D&D API доступен (HTTP $http_code)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ D&D API вернул код $http_code</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Исключение: " . $e->getMessage() . "</p>";
}

// Проверяем наш API
echo "<h2>2. Проверка нашего API</h2>";

if (file_exists('api/generate-potions.php')) {
    echo "<p style='color: green;'>✅ Файл API найден</p>";
    
    // Проверяем синтаксис PHP
    $syntax_check = shell_exec('php -l api/generate-potions.php 2>&1');
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "<p style='color: green;'>✅ Синтаксис PHP корректен</p>";
    } else {
        echo "<p style='color: red;'>❌ Ошибки синтаксиса PHP:</p>";
        echo "<pre>$syntax_check</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ Файл API не найден</p>";
}

// Проверяем директорию кеша
echo "<h2>3. Проверка директорий</h2>";

$cache_dir = 'logs/cache';
if (is_dir($cache_dir)) {
    echo "<p style='color: green;'>✅ Директория кеша существует</p>";
    
    if (is_writable($cache_dir)) {
        echo "<p style='color: green;'>✅ Директория кеша доступна для записи</p>";
    } else {
        echo "<p style='color: red;'>❌ Директория кеша недоступна для записи</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Директория кеша не существует</p>";
    
    // Пытаемся создать
    if (mkdir($cache_dir, 0755, true)) {
        echo "<p style='color: green;'>✅ Директория кеша создана</p>";
    } else {
        echo "<p style='color: red;'>❌ Не удалось создать директорию кеша</p>";
    }
}

// Тестируем API
echo "<h2>4. Тест API</h2>";

if (function_exists('curl_init')) {
    echo "<p style='color: green;'>✅ CURL доступен</p>";
    
    // Тестируем простой запрос
    try {
        $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/generate-potions.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'count=1');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "<p style='color: red;'>❌ Ошибка CURL при тестировании API: $error</p>";
        } elseif ($http_code === 200) {
            echo "<p style='color: green;'>✅ API отвечает (HTTP $http_code)</p>";
            
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                if ($data['success']) {
                    echo "<p style='color: green;'>✅ API вернул успешный ответ</p>";
                    echo "<p>Найдено зелий: " . count($data['data']) . "</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ API вернул ошибку: " . ($data['error'] ?? 'Неизвестная ошибка') . "</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ API вернул неожиданный формат данных</p>";
                echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
            }
        } else {
            echo "<p style='color: red;'>❌ API вернул код $http_code</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Исключение при тестировании API: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ CURL недоступен</p>";
}

// Проверяем конфигурацию PHP
echo "<h2>5. Конфигурация PHP</h2>";

$required_extensions = ['curl', 'json', 'fileinfo'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ Расширение $ext загружено</p>";
    } else {
        echo "<p style='color: red;'>❌ Расширение $ext не загружено</p>";
    }
}

echo "<p><strong>Версия PHP:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Максимальное время выполнения:</strong> " . ini_get('max_execution_time') . " сек</p>";
echo "<p><strong>Лимит памяти:</strong> " . ini_get('memory_limit') . "</p>";

// Рекомендации
echo "<h2>6. Рекомендации</h2>";

if (!extension_loaded('curl')) {
    echo "<p style='color: red;'>❌ Установите расширение CURL для PHP</p>";
}

if (ini_get('max_execution_time') < 30) {
    echo "<p style='color: orange;'>⚠️ Увеличьте max_execution_time до 30+ секунд</p>";
}

if (ini_get('memory_limit') < '128M') {
    echo "<p style='color: orange;'>⚠️ Увеличьте memory_limit до 128M+</p>";
}

echo "<hr>";
echo "<p><a href='potion-generator.html'>Открыть генератор зелий</a> | ";
echo "<a href='test-potion-api.html'>Открыть тестовый интерфейс</a></p>";
?>
