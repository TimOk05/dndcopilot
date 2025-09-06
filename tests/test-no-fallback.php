<?php
/**
 * Тест системы без fallback
 * Проверяет работу генерации персонажей только через AI API
 */

require_once 'config.php';
require_once 'api/CharacterService.php';

// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Тест Системы Без Fallback</h1>\n";

// Проверяем статус OpenSSL
echo "<h2>Статус OpenSSL</h2>\n";
echo "<p><strong>OpenSSL доступен:</strong> " . (OPENSSL_AVAILABLE ? 'Да' : 'Нет') . "</p>\n";
echo "<p><strong>OpenSSL поддержка:</strong> " . (extension_loaded('openssl') ? 'enabled' : 'disabled') . "</p>\n";

// Проверяем API ключи
echo "<h2>API Ключи</h2>\n";
echo "<p><strong>DeepSeek API ключ:</strong> " . (getApiKey('deepseek') ? 'Установлен' : 'Не установлен') . "</p>\n";
echo "<p><strong>OpenAI API ключ:</strong> " . (getApiKey('openai') ? 'Установлен' : 'Не установлен') . "</p>\n";
echo "<p><strong>Google API ключ:</strong> " . (getApiKey('google') ? 'Установлен' : 'Не установлен') . "</p>\n";

// Создаем тестовый персонаж
$testCharacter = [
    'name' => 'Тестовый Персонаж',
    'race' => 'human',
    'class' => 'fighter',
    'level' => 5,
    'gender' => 'male',
    'occupation' => 'страж',
    'alignment' => 'законно-добрый',
    'abilities' => [
        'str' => 16,
        'dex' => 14,
        'con' => 15,
        'int' => 10,
        'wis' => 12,
        'cha' => 8
    ]
];

echo "<h2>Тестовый Персонаж</h2>\n";
echo "<pre>" . json_encode($testCharacter, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";

// Тестируем генерацию
try {
    $characterService = new CharacterService();
    
    echo "<h2>Тест Генерации</h2>\n";
    
    // Генерируем описание
    echo "<h3>Генерация Описания</h3>\n";
    $description = $characterService->generateCharacter($testCharacter);
    
    if ($description['success']) {
        $character = $description['character'];
        echo "<p><strong>Описание:</strong> " . htmlspecialchars($character['description']) . "</p>\n";
        echo "<p><strong>Предыстория:</strong> " . htmlspecialchars($character['background']) . "</p>\n";
        
        echo "<h3>Полный Персонаж</h3>\n";
        echo "<pre>" . json_encode($character, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
    } else {
        echo "<p><strong>Ошибка:</strong> " . htmlspecialchars($description['error']) . "</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Исключение:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Файл:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>\n";
    echo "<p><strong>Стек вызовов:</strong></p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "<h2>Статус Системы</h2>\n";
if (!OPENSSL_AVAILABLE) {
    echo "<p style='color: red;'><strong>❌ OpenSSL не доступен!</strong></p>\n";
    echo "<p>Система будет показывать ошибки вместо генерации описаний и предысторий.</p>\n";
} else {
    echo "<p style='color: green;'><strong>✅ OpenSSL доступен!</strong></p>\n";
    echo "<p>AI API должен работать корректно.</p>\n";
}

if (!getApiKey('deepseek')) {
    echo "<p style='color: orange;'><strong>⚠️ API ключ DeepSeek не установлен!</strong></p>\n";
    echo "<p>Система будет показывать ошибки вместо генерации описаний и предысторий.</p>\n";
} else {
    echo "<p style='color: green;'><strong>✅ API ключ DeepSeek установлен!</strong></p>\n";
}

echo "<p><strong>Fallback система:</strong> Полностью удалена. Система работает только через AI API.</p>\n";
echo "<p><strong>Примечание:</strong> Без OpenSSL или API ключей система будет показывать сообщения об ошибках вместо генерации контента.</p>\n";
?>
