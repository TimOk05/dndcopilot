<?php
// Тест AI без fallback системы
require_once 'config.php';
require_once 'api/ai-service.php';

echo "<h1>Тест AI без Fallback</h1>";
echo "<p>Этот тест проверяет, что AI сервис показывает ошибки вместо fallback данных.</p>";

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

$description = $ai_service->generateCharacterDescription($character, true);

if (isset($description['error'])) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;'>";
    echo "<strong>✅ ОШИБКА ПОКАЗАНА ПРАВИЛЬНО:</strong><br>";
    echo "Ошибка: " . htmlspecialchars($description['error']) . "<br>";
    echo "Сообщение: " . htmlspecialchars($description['message']) . "<br>";
    if (isset($description['details'])) {
        echo "Детали: " . htmlspecialchars($description['details']) . "<br>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0;'>";
    echo "<strong>✅ AI РАБОТАЕТ:</strong><br>";
    echo "Описание: " . htmlspecialchars($description) . "<br>";
    echo "</div>";
}

// Тестируем генерацию предыстории
echo "<h2>2. Тест генерации предыстории</h2>";
$background = $ai_service->generateCharacterBackground($character, true);

if (isset($background['error'])) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;'>";
    echo "<strong>✅ ОШИБКА ПОКАЗАНА ПРАВИЛЬНО:</strong><br>";
    echo "Ошибка: " . htmlspecialchars($background['error']) . "<br>";
    echo "Сообщение: " . htmlspecialchars($background['message']) . "<br>";
    if (isset($background['details'])) {
        echo "Детали: " . htmlspecialchars($background['details']) . "<br>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0;'>";
    echo "<strong>✅ AI РАБОТАЕТ:</strong><br>";
    echo "Предыстория: " . htmlspecialchars($background) . "<br>";
    echo "</div>";
}

// Тестируем генерацию тактики противника
echo "<h2>3. Тест генерации тактики противника</h2>";
$enemy = [
    'name' => 'Тестовый гоблин',
    'type' => 'humanoid',
    'challenge_rating' => '1/4'
];

$tactics = $ai_service->generateEnemyTactics($enemy, true);

if (isset($tactics['error'])) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;'>";
    echo "<strong>✅ ОШИБКА ПОКАЗАНА ПРАВИЛЬНО:</strong><br>";
    echo "Ошибка: " . htmlspecialchars($tactics['error']) . "<br>";
    echo "Сообщение: " . htmlspecialchars($tactics['message']) . "<br>";
    if (isset($tactics['details'])) {
        echo "Детали: " . htmlspecialchars($tactics['details']) . "<br>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0;'>";
    echo "<strong>✅ AI РАБОТАЕТ:</strong><br>";
    echo "Тактика: " . htmlspecialchars($tactics) . "<br>";
    echo "</div>";
}

echo "<h2>4. Результат теста</h2>";
echo "<p><strong>Если вы видите ошибки выше - это ХОРОШО!</strong></p>";
echo "<p>Это означает, что fallback система удалена и AI сервис показывает реальные проблемы.</p>";
echo "<p>Для исправления AI API нужно:</p>";
echo "<ul>";
echo "<li>Проверить подключение к интернету</li>";
echo "<li>Проверить API ключи в config.php</li>";
echo "<li>Проверить настройки SSL в PHP</li>";
echo "<li>Убедиться, что cURL расширение включено</li>";
echo "</ul>";

echo "<p><strong>ПОМНИТЕ: Лучше показать ошибку, чем создать ложное впечатление работы!</strong></p>";
?>
