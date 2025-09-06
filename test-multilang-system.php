<?php
/**
 * Тест многоязычной системы
 * Проверяет работу Language Service и AI перевода
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/language-service.php';
require_once __DIR__ . '/api/ai-service.php';

echo "<h1>🧪 Тест многоязычной системы</h1>\n";

// Тест 1: Language Service
echo "<h2>Тест 1: Language Service</h2>\n";
$lang_service = new LanguageService();
echo "Текущий язык: " . $lang_service->getCurrentLanguage() . "<br>\n";
echo "Поддерживаемые языки: " . implode(', ', $lang_service->getSupportedLanguages()) . "<br>\n";

// Тест 2: Смена языка
echo "<h2>Тест 2: Смена языка</h2>\n";
$lang_service->setLanguage('en');
echo "Язык после смены на 'en': " . $lang_service->getCurrentLanguage() . "<br>\n";
$lang_service->setLanguage('ru');
echo "Язык после смены на 'ru': " . $lang_service->getCurrentLanguage() . "<br>\n";

// Тест 3: Локализованные строки
echo "<h2>Тест 3: Локализованные строки</h2>\n";
echo "Русский: " . $lang_service->getInterfaceText('generate_potions', 'ru') . "<br>\n";
echo "English: " . $lang_service->getInterfaceText('generate_potions', 'en') . "<br>\n";

// Тест 4: AI Service
echo "<h2>Тест 4: AI Service</h2>\n";
$ai_service = new AiService();

// Тест перевода названия зелья
echo "<h3>Тест перевода названия зелья</h3>\n";
$potion_name = "Potion of Healing";
echo "Оригинальное название: {$potion_name}<br>\n";

$translated_name = $ai_service->translatePotionName($potion_name, 'ru');
if (is_string($translated_name)) {
    echo "Переведенное название: {$translated_name}<br>\n";
} else {
    echo "Ошибка перевода: " . json_encode($translated_name, JSON_UNESCAPED_UNICODE) . "<br>\n";
}

// Тест перевода описания
echo "<h3>Тест перевода описания</h3>\n";
$potion_desc = "A character who drinks the magical red fluid in this vial regains 2d4 + 2 hit points. Drinking or administering a potion takes an action.";
echo "Оригинальное описание: {$potion_desc}<br>\n";

$translated_desc = $ai_service->translatePotionDescription($potion_desc, 'ru');
if (is_string($translated_desc)) {
    echo "Переведенное описание: {$translated_desc}<br>\n";
} else {
    echo "Ошибка перевода: " . json_encode($translated_desc, JSON_UNESCAPED_UNICODE) . "<br>\n";
}

// Тест 5: Полный перевод зелья
echo "<h2>Тест 5: Полный перевод зелья</h2>\n";
$potion_data = [
    'name' => 'Potion of Healing',
    'description' => 'A character who drinks the magical red fluid in this vial regains 2d4 + 2 hit points.',
    'effects' => ['Heal', 'Hit Points'],
    'rarity' => 'Common',
    'type' => 'Восстановление'
];

echo "Оригинальные данные зелья:<br>\n";
echo "<pre>" . json_encode($potion_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>\n";

$translated_potion = $ai_service->translatePotion($potion_data, 'ru');
echo "Переведенные данные зелья:<br>\n";
echo "<pre>" . json_encode($translated_potion, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>\n";

echo "<h2>✅ Тест завершен</h2>\n";
echo "<p>Если вы видите переведенные названия и описания выше, то многоязычная система работает корректно!</p>\n";
?>
