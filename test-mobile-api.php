<?php
// Тест мобильного API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ТЕСТ МОБИЛЬНОГО API ===\n\n";

// Подключаем mobile-api.php
require_once 'mobile-api.php';

echo "1. Тестируем генерацию персонажа...\n";
try {
    $character = generateMobileCharacter('human', 'fighter', 1);
    echo "✓ Персонаж создан: " . $character['name'] . "\n";
    echo "  Раса: " . $character['race'] . "\n";
    echo "  Класс: " . $character['class'] . "\n";
    echo "  Уровень: " . $character['level'] . "\n";
    echo "  HP: " . $character['hp'] . "\n";
    echo "  AC: " . $character['ac'] . "\n";
    echo "  Описание: " . substr($character['description'], 0, 100) . "...\n\n";
} catch (Exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n\n";
}

echo "2. Тестируем AI чат...\n";
try {
    $aiResponse = askMobileAI("Какие классы есть в D&D?");
    echo "✓ AI ответ получен: " . substr($aiResponse['response'], 0, 100) . "...\n\n";
} catch (Exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n\n";
}

echo "3. Тестируем генерацию противника...\n";
try {
    $enemy = generateMobileEnemy('easy', 1, '', '', true);
    if (is_array($enemy) && isset($enemy['name'])) {
        echo "✓ Противник создан: " . $enemy['name'] . "\n";
        echo "  CR: " . $enemy['cr'] . "\n";
        echo "  HP: " . $enemy['hp'] . "\n";
        echo "  AC: " . $enemy['ac'] . "\n";
    } else {
        echo "✗ Неверный формат ответа\n";
    }
} catch (Exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n\n";
}

echo "\n=== КОНЕЦ ТЕСТА ===\n";
?>
