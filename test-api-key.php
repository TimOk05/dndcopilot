<?php
require_once 'config.php';

echo "=== Тест загрузки API ключа ===\n\n";

// Проверяем API ключ
$apiKey = getApiKey('deepseek');
echo "API ключ DeepSeek: " . (empty($apiKey) ? 'НЕ НАЙДЕН' : 'НАЙДЕН') . "\n";
echo "Длина ключа: " . strlen($apiKey) . " символов\n";
echo "Первые 10 символов: " . substr($apiKey, 0, 10) . "...\n\n";

// Проверяем, что возвращает функция
echo "Тип данных: " . gettype($apiKey) . "\n";
echo "Пустой ли: " . (empty($apiKey) ? 'ДА' : 'НЕТ') . "\n";
echo "NULL ли: " . (is_null($apiKey) ? 'ДА' : 'НЕТ') . "\n";
echo "Строка ли: " . (is_string($apiKey) ? 'ДА' : 'НЕТ') . "\n\n";

// Проверяем конструктор генератора
echo "=== Тест конструктора генератора ===\n";
require_once 'api/generate-characters.php';

try {
    $generator = new CharacterGenerator();
    echo "Генератор создан успешно\n";
    
    // Используем reflection для доступа к приватному свойству
    $reflection = new ReflectionClass($generator);
    $property = $reflection->getProperty('deepseek_api_key');
    $property->setAccessible(true);
    $key = $property->getValue($generator);
    
    echo "API ключ в генераторе: " . (empty($key) ? 'НЕ НАЙДЕН' : 'НАЙДЕН') . "\n";
    echo "Длина ключа в генераторе: " . strlen($key) . " символов\n";
    
} catch (Exception $e) {
    echo "Ошибка создания генератора: " . $e->getMessage() . "\n";
}

echo "\n=== Тест завершен ===\n";
?>
