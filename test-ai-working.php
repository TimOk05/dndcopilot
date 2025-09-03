<?php
// Тест работоспособности AI API
require_once 'config.php';
require_once 'api/ai-service.php';

echo "<h1>🧪 Тест работоспособности AI API</h1>";
echo "<p>Этот тест проверяет, что AI API работает корректно.</p>";

// Создаем экземпляр AI сервиса
$ai_service = new AiService();

// Тестируем генерацию описания персонажа
echo "<h2>1. Тест генерации описания персонажа</h2>";
$character = [
    'name' => 'Тестовый персонаж',
    'race' => 'human',
    'class' => 'fighter',
    'level' => 5,
    'gender' => 'male',
    'occupation' => 'Стражник'
];

echo "<h3>Персонаж для теста:</h3>";
echo "<pre>" . json_encode($character, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

$description = $ai_service->generateCharacterDescription($character, true);

if (isset($description['error'])) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;'>";
    echo "<strong>❌ ОШИБКА AI API:</strong><br>";
    echo "<strong>Сообщение:</strong> " . $description['message'] . "<br>";
    if (isset($description['details'])) {
        echo "<strong>Детали:</strong> " . $description['details'] . "<br>";
    }
    if (isset($description['debug_info'])) {
        echo "<strong>Отладочная информация:</strong><br>";
        echo "<pre>" . json_encode($description['debug_info'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0;'>";
    echo "<strong>✅ AI API работает!</strong><br>";
    echo "<strong>Описание персонажа:</strong><br>";
    echo "<p>" . htmlspecialchars($description) . "</p>";
    echo "</div>";
}

// Тестируем генерацию предыстории
echo "<h2>2. Тест генерации предыстории персонажа</h2>";
$background = $ai_service->generateCharacterBackground($character, true);

if (isset($background['error'])) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;'>";
    echo "<strong>❌ ОШИБКА AI API:</strong><br>";
    echo "<strong>Сообщение:</strong> " . $background['message'] . "<br>";
    if (isset($background['details'])) {
        echo "<strong>Детали:</strong> " . $background['details'] . "<br>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0;'>";
    echo "<strong>✅ AI API работает!</strong><br>";
    echo "<strong>Предыстория персонажа:</strong><br>";
    echo "<p>" . htmlspecialchars($background) . "</p>";
    echo "</div>";
}

// Проверяем API ключи
echo "<h2>3. Проверка API ключей</h2>";
$deepseek_key = getApiKey('deepseek');
$openai_key = getApiKey('openai');
$google_key = getApiKey('google');

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Сервис</th><th>API ключ</th><th>Статус</th></tr>";
echo "<tr><td>DeepSeek</td><td>" . (empty($deepseek_key) ? 'Не настроен' : substr($deepseek_key, 0, 10) . '...') . "</td><td>" . (empty($deepseek_key) ? '❌' : '✅') . "</td></tr>";
echo "<tr><td>OpenAI</td><td>" . (empty($openai_key) ? 'Не настроен' : substr($openai_key, 0, 10) . '...') . "</td><td>" . (empty($openai_key) ? '❌' : '✅') . "</td></tr>";
echo "<tr><td>Google</td><td>" . (empty($google_key) ? 'Не настроен' : substr($google_key, 0, 10) . '...') . "</td><td>" . (empty($google_key) ? '❌' : '✅') . "</td></tr>";
echo "</table>";

// Проверяем системные требования
echo "<h2>4. Проверка системных требований</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Требование</th><th>Статус</th><th>Детали</th></tr>";

$curl_available = function_exists('curl_init');
echo "<tr><td>cURL расширение</td><td>" . ($curl_available ? '✅' : '❌') . "</td><td>" . ($curl_available ? 'Доступно' : 'Недоступно') . "</td></tr>";

$ssl_available = extension_loaded('openssl');
echo "<tr><td>OpenSSL расширение</td><td>" . ($ssl_available ? '✅' : '❌') . "</td><td>" . ($ssl_available ? 'Доступно' : 'Недоступно') . "</td></tr>";

$json_available = function_exists('json_encode');
echo "<tr><td>JSON расширение</td><td>" . ($json_available ? '✅' : '❌') . "</td><td>" . ($json_available ? 'Доступно' : 'Недоступно') . "</td></tr>";

echo "</table>";

// Тест подключения к интернету
echo "<h2>5. Тест подключения к интернету</h2>";
$test_url = 'https://api.deepseek.com';
$test_response = @file_get_contents($test_url, false, stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'DnD-Copilot/2.0'
    ]
]));

if ($test_response === false) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0;'>";
    echo "<strong>⚠️ Проблема с подключением к интернету</strong><br>";
    echo "Не удается подключиться к {$test_url}<br>";
    echo "Проверьте подключение к интернету и настройки прокси";
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0;'>";
    echo "<strong>✅ Подключение к интернету работает</strong><br>";
    echo "Успешно подключились к {$test_url}";
    echo "</div>";
}

echo "<hr>";
echo "<h2>📋 Рекомендации</h2>";

if (isset($description['error']) || isset($background['error'])) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Для исправления проблем с AI API:</strong><br>";
    echo "1. Убедитесь, что у вас есть действующий API ключ DeepSeek<br>";
    echo "2. Проверьте подключение к интернету<br>";
    echo "3. Убедитесь, что PHP поддерживает cURL и OpenSSL<br>";
    echo "4. Проверьте логи в logs/app.log для детальной информации";
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0;'>";
    echo "<strong>🎉 AI API работает корректно!</strong><br>";
    echo "Все тесты прошли успешно. AI генерация доступна.";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Время выполнения теста:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
