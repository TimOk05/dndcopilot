<?php
/**
 * Тест DeepSeek API
 * Проверяет работу интеграции с DeepSeek AI
 */

require_once 'config.php';
require_once 'api/ai-service.php';

// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🧪 Тест DeepSeek API</h1>\n";

// Проверяем статус OpenSSL
echo "<h2>🔐 Статус OpenSSL</h2>\n";
echo "<p><strong>OpenSSL доступен:</strong> " . (OPENSSL_AVAILABLE ? 'Да' : 'Нет') . "</p>\n";
echo "<p><strong>OpenSSL поддержка:</strong> " . (extension_loaded('openssl') ? 'enabled' : 'disabled') . "</p>\n";

// Проверяем API ключи
echo "<h2>🔑 API Ключи</h2>\n";
$deepseekKey = getApiKey('deepseek');
$openaiKey = getApiKey('openai');
$googleKey = getApiKey('google');

echo "<p><strong>DeepSeek API ключ:</strong> " . ($deepseekKey ? 'Установлен (' . substr($deepseekKey, 0, 10) . '...)' : 'Не установлен') . "</p>\n";
echo "<p><strong>OpenAI API ключ:</strong> " . ($openaiKey ? 'Установлен' : 'Не установлен') . "</p>\n";
echo "<p><strong>Google API ключ:</strong> " . ($googleKey ? 'Установлен' : 'Не установлен') . "</p>\n";

// Проверяем cURL
echo "<h2>🌐 Статус cURL</h2>\n";
echo "<p><strong>cURL доступен:</strong> " . (function_exists('curl_init') ? 'Да' : 'Нет') . "</p>\n";

if (!OPENSSL_AVAILABLE) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h3>⚠️ Внимание: OpenSSL не доступен!</h3>\n";
    echo "<p>Без OpenSSL HTTPS запросы к DeepSeek API не будут работать.</p>\n";
    echo "<p><strong>Решение:</strong> Включите расширение OpenSSL в php.ini</p>\n";
    echo "</div>\n";
}

if (!$deepseekKey) {
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h3>⚠️ Внимание: API ключ DeepSeek не установлен!</h3>\n";
    echo "<p>Добавьте ваш API ключ в config.php</p>\n";
    echo "</div>\n";
}

if (!function_exists('curl_init')) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h3>⚠️ Внимание: cURL не доступен!</h3>\n";
    echo "<p>Без cURL HTTP запросы не будут работать.</p>\n";
    echo "</div>\n";
}

// Тестируем AI Service
echo "<h2>🤖 Тест AI Service</h2>\n";

try {
    $aiService = new AiService();
    echo "<p>✅ AI Service создан успешно</p>\n";
    
    // Тестовый персонаж
    $testCharacter = [
        'name' => 'Тестовый Персонаж',
        'race' => 'Человек',
        'class' => 'Воин',
        'level' => 5,
        'occupation' => 'Страж',
        'gender' => 'Мужчина',
        'alignment' => 'Законно-добрый',
        'abilities' => ['str' => 16, 'dex' => 14, 'con' => 15, 'int' => 10, 'wis' => 12, 'cha' => 8]
    ];
    
    echo "<h3>Тестовый персонаж:</h3>\n";
    echo "<pre>" . json_encode($testCharacter, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
    
    // Тест генерации описания
    echo "<h3>🎯 Тест генерации описания персонажа:</h3>\n";
    $description = $aiService->generateCharacterDescription($testCharacter, true);
    
    if (is_array($description) && isset($description['error'])) {
        echo "<p style='color: red;'><strong>❌ Ошибка:</strong> " . htmlspecialchars($description['error']) . "</p>\n";
        if (isset($description['message'])) {
            echo "<p><strong>Сообщение:</strong> " . htmlspecialchars($description['message']) . "</p>\n";
        }
    } else {
        echo "<p style='color: green;'><strong>✅ Успешно!</strong></p>\n";
        echo "<p><strong>Описание:</strong> " . htmlspecialchars($description) . "</p>\n";
    }
    
    // Тест генерации предыстории
    echo "<h3>📖 Тест генерации предыстории персонажа:</h3>\n";
    $background = $aiService->generateCharacterBackground($testCharacter, true);
    
    if (is_array($background) && isset($background['error'])) {
        echo "<p style='color: red;'><strong>❌ Ошибка:</strong> " . htmlspecialchars($background['error']) . "</p>\n";
        if (isset($background['message'])) {
            echo "<p><strong>Сообщение:</strong> " . htmlspecialchars($background['message']) . "</p>\n";
        }
    } else {
        echo "<p style='color: green;'><strong>✅ Успешно!</strong></p>\n";
        echo "<p><strong>Предыстория:</strong> " . htmlspecialchars($background) . "</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Исключение:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Файл:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>\n";
}

// Тест прямого вызова DeepSeek API
echo "<h2>🔗 Тест прямого вызова DeepSeek API</h2>\n";

if (OPENSSL_AVAILABLE && $deepseekKey && function_exists('curl_init')) {
    try {
        echo "<h3>Тестируем прямое обращение к DeepSeek API...</h3>\n";
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты помощник для создания персонажей D&D 5e. Отвечай кратко на русском языке.'],
                ['role' => 'user', 'content' => 'Создай краткое описание человека-воина 5 уровня в 2 предложениях.']
            ],
            'max_tokens' => 150,
            'temperature' => 0.7
        ];
        
        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $deepseekKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        echo "<p>Отправляем запрос к DeepSeek API...</p>\n";
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "<p style='color: red;'><strong>❌ cURL ошибка:</strong> " . htmlspecialchars($error) . "</p>\n";
        } elseif ($httpCode !== 200) {
            echo "<p style='color: red;'><strong>❌ HTTP ошибка:</strong> " . $httpCode . "</p>\n";
            echo "<p><strong>Ответ:</strong> " . htmlspecialchars($response) . "</p>\n";
        } else {
            $result = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($result['choices'][0]['message']['content'])) {
                echo "<p style='color: green;'><strong>✅ API запрос успешен!</strong></p>\n";
                echo "<p><strong>Ответ DeepSeek:</strong> " . htmlspecialchars($result['choices'][0]['message']['content']) . "</p>\n";
                echo "<p><strong>Использовано токенов:</strong> " . ($result['usage']['total_tokens'] ?? 'N/A') . "</p>\n";
            } else {
                echo "<p style='color: red;'><strong>❌ Ошибка парсинга ответа:</strong> " . json_last_error_msg() . "</p>\n";
                echo "<p><strong>Сырой ответ:</strong> " . htmlspecialchars($response) . "</p>\n";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ Исключение:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Прямой тест API пропущен - отсутствуют необходимые компоненты</p>\n";
}

// Итоговая информация
echo "<h2>📊 Итоги тестирования</h2>\n";

if (OPENSSL_AVAILABLE && $deepseekKey && function_exists('curl_init')) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h3>✅ Все компоненты готовы!</h3>\n";
    echo "<p>DeepSeek API должен работать корректно.</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h3>❌ Требуется настройка</h3>\n";
    echo "<ul>\n";
    if (!OPENSSL_AVAILABLE) echo "<li>Включить OpenSSL в PHP</li>\n";
    if (!$deepseekKey) echo "<li>Установить API ключ DeepSeek</li>\n";
    if (!function_exists('curl_init')) echo "<li>Включить cURL в PHP</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
}

echo "<h3>🔧 Рекомендации по настройке:</h3>\n";
echo "<ol>\n";
echo "<li><strong>OpenSSL:</strong> Раскомментируйте <code>extension=openssl</code> в php.ini</li>\n";
echo "<li><strong>cURL:</strong> Убедитесь, что расширение cURL включено</li>\n";
echo "<li><strong>API ключ:</strong> Проверьте правильность ключа DeepSeek в config.php</li>\n";
echo "<li><strong>Перезапуск:</strong> Перезапустите веб-сервер после изменений</li>\n";
echo "</ol>\n";

echo "<h3>📚 Документация DeepSeek API:</h3>\n";
echo "<p><a href='https://platform.deepseek.com/docs' target='_blank'>https://platform.deepseek.com/docs</a></p>\n";
echo "<p><a href='https://platform.deepseek.com/api-docs' target='_blank'>https://platform.deepseek.com/api-docs</a></p>\n";
?>
