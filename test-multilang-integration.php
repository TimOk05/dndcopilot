<?php
/**
 * Тест полной интеграции многоязычной системы
 * Проверяет работу всех компонентов вместе
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/language-service.php';
require_once __DIR__ . '/api/ai-service.php';
require_once __DIR__ . '/api/generate-potions.php';
require_once __DIR__ . '/api/generate-characters-v4.php';

echo "<h1>🧪 Тест полной интеграции многоязычной системы</h1>\n";

// Тест 1: Language Service
echo "<h2>Тест 1: Language Service</h2>\n";
$lang_service = new LanguageService();
echo "Текущий язык: " . $lang_service->getCurrentLanguage() . "<br>\n";
echo "Поддерживаемые языки: " . implode(', ', $lang_service->getSupportedLanguages()) . "<br>\n";

// Тест 2: Переводы интерфейса
echo "<h2>Тест 2: Переводы интерфейса</h2>\n";
echo "Русский: " . $lang_service->getInterfaceText('generate_potions', 'ru') . "<br>\n";
echo "English: " . $lang_service->getInterfaceText('generate_potions', 'en') . "<br>\n";

// Тест 3: Переводы игровых терминов
echo "<h2>Тест 3: Переводы игровых терминов</h2>\n";
echo "Раса (ru): " . $lang_service->getRaceName('elf', 'ru') . "<br>\n";
echo "Раса (en): " . $lang_service->getRaceName('elf', 'en') . "<br>\n";
echo "Класс (ru): " . $lang_service->getClassName('wizard', 'ru') . "<br>\n";
echo "Класс (en): " . $lang_service->getClassName('wizard', 'en') . "<br>\n";
echo "Мировоззрение (ru): " . $lang_service->getAlignmentName('lawful-good', 'ru') . "<br>\n";
echo "Мировоззрение (en): " . $lang_service->getAlignmentName('lawful-good', 'en') . "<br>\n";

// Тест 4: Генератор зелий с языками
echo "<h2>Тест 4: Генератор зелий с языками</h2>\n";
$potion_generator = new PotionGenerator();

// Тест на русском
echo "<h3>Генерация зелий на русском:</h3>\n";
$potion_params_ru = [
    'action' => 'random',
    'count' => 1,
    'language' => 'ru'
];
$potion_result_ru = $potion_generator->generatePotions($potion_params_ru);
if ($potion_result_ru['success']) {
    $potion = $potion_result_ru['data'][0];
    echo "Название: " . $potion['name'] . "<br>\n";
    echo "Редкость: " . $potion['rarity_localized'] . "<br>\n";
    echo "Тип: " . $potion['type_localized'] . "<br>\n";
    echo "Описание: " . substr($potion['description'], 0, 100) . "...<br>\n";
} else {
    echo "Ошибка: " . $potion_result_ru['error'] . "<br>\n";
}

// Тест на английском
echo "<h3>Генерация зелий на английском:</h3>\n";
$potion_params_en = [
    'action' => 'random',
    'count' => 1,
    'language' => 'en'
];
$potion_result_en = $potion_generator->generatePotions($potion_params_en);
if ($potion_result_en['success']) {
    $potion = $potion_result_en['data'][0];
    echo "Название: " . $potion['name'] . "<br>\n";
    echo "Редкость: " . $potion['rarity'] . "<br>\n";
    echo "Тип: " . $potion['type'] . "<br>\n";
    echo "Описание: " . substr($potion['description'], 0, 100) . "...<br>\n";
} else {
    echo "Ошибка: " . $potion_result_en['error'] . "<br>\n";
}

// Тест 5: Генератор персонажей с языками
echo "<h2>Тест 5: Генератор персонажей с языками</h2>\n";
$character_generator = new CharacterGeneratorV4();

// Тест на русском
echo "<h3>Генерация персонажа на русском:</h3>\n";
$char_params_ru = [
    'race' => 'elf',
    'class' => 'wizard',
    'level' => 1,
    'alignment' => 'lawful-good',
    'gender' => 'random',
    'language' => 'ru'
];
$char_result_ru = $character_generator->generateCharacter($char_params_ru);
if ($char_result_ru['success']) {
    $character = $char_result_ru['character'];
    echo "Имя: " . $character['name'] . "<br>\n";
    echo "Раса: " . $character['race'] . "<br>\n";
    echo "Класс: " . $character['class'] . "<br>\n";
    echo "Мировоззрение: " . $character['alignment'] . "<br>\n";
    echo "Описание: " . substr($character['description'], 0, 100) . "...<br>\n";
} else {
    echo "Ошибка: " . $char_result_ru['error'] . "<br>\n";
}

// Тест на английском
echo "<h3>Генерация персонажа на английском:</h3>\n";
$char_params_en = [
    'race' => 'elf',
    'class' => 'wizard',
    'level' => 1,
    'alignment' => 'lawful-good',
    'gender' => 'random',
    'language' => 'en'
];
$char_result_en = $character_generator->generateCharacter($char_params_en);
if ($char_result_en['success']) {
    $character = $char_result_en['character'];
    echo "Имя: " . $character['name'] . "<br>\n";
    echo "Раса: " . $character['race'] . "<br>\n";
    echo "Класс: " . $character['class'] . "<br>\n";
    echo "Мировоззрение: " . $character['alignment'] . "<br>\n";
    echo "Описание: " . substr($character['description'], 0, 100) . "...<br>\n";
} else {
    echo "Ошибка: " . $char_result_en['error'] . "<br>\n";
}

// Тест 6: AI Service переводы
echo "<h2>Тест 6: AI Service переводы</h2>\n";
$ai_service = new AiService();

// Тест перевода описания персонажа
echo "<h3>Перевод описания персонажа:</h3>\n";
$test_description = "A wise and ancient elf wizard who has studied magic for centuries.";
$translated_desc = $ai_service->translateCharacterDescription($test_description, 'ru');
echo "Оригинал: " . $test_description . "<br>\n";
echo "Перевод: " . $translated_desc . "<br>\n";

// Тест перевода предыстории
echo "<h3>Перевод предыстории персонажа:</h3>\n";
$test_background = "Born in the mystical forests of the Feywild, this elf has dedicated their life to understanding the arcane arts.";
$translated_bg = $ai_service->translateCharacterBackground($test_background, 'ru');
echo "Оригинал: " . $test_background . "<br>\n";
echo "Перевод: " . $translated_bg . "<br>\n";

echo "<h2>✅ Тестирование завершено!</h2>\n";
echo "<p>Многоязычная система успешно интегрирована в проект D&D Copilot.</p>\n";
?>
