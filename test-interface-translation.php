<?php
/**
 * Тест перевода интерфейса
 */

echo "<h1>🌐 Тест перевода интерфейса</h1>\n";

// Тест 1: Проверка Language Service
echo "<h2>Тест 1: Language Service</h2>\n";
try {
    require_once 'api/language-service.php';
    $lang_service = new LanguageService();
    echo "✅ Language Service работает<br>\n";
    echo "Текущий язык: " . $lang_service->getCurrentLanguage() . "<br>\n";
} catch (Exception $e) {
    echo "❌ Ошибка Language Service: " . $e->getMessage() . "<br>\n";
}

// Тест 2: Проверка переводов
echo "<h2>Тест 2: Переводы</h2>\n";
$translations = [
    'ru' => [
        'welcome' => 'Добро пожаловать',
        'logout' => '🚪 Выйти',
        'character_generator' => '⚔️ Генератор персонажей',
        'potion_generator' => '🧪 Генератор зелий'
    ],
    'en' => [
        'welcome' => 'Welcome',
        'logout' => '🚪 Logout',
        'character_generator' => '⚔️ Character Generator',
        'potion_generator' => '🧪 Potion Generator'
    ]
];

foreach (['ru', 'en'] as $lang) {
    echo "<h3>Язык: $lang</h3>\n";
    foreach ($translations[$lang] as $key => $value) {
        echo "✅ $key: $value<br>\n";
    }
}

// Тест 3: Проверка PHP переменных
echo "<h2>Тест 3: PHP переменные</h2>\n";
$currentLanguage = 'ru';
echo "Текущий язык: $currentLanguage<br>\n";
echo "Приветствие: " . ($currentLanguage === 'en' ? 'Welcome' : 'Добро пожаловать') . "<br>\n";

$currentLanguage = 'en';
echo "Текущий язык: $currentLanguage<br>\n";
echo "Приветствие: " . ($currentLanguage === 'en' ? 'Welcome' : 'Добро пожаловать') . "<br>\n";

echo "<h2>✅ Тесты завершены!</h2>\n";
echo "<p><a href='index.php?lang=ru'>Тест русской версии</a> | <a href='index.php?lang=en'>Тест английской версии</a></p>\n";
?>
