<?php
/**
 * Простой тест переключения языков
 */

echo "<h1>🌐 Тест переключения языков</h1>\n";

// Получаем язык из URL
$lang = $_GET['lang'] ?? 'ru';
echo "<p>Текущий язык: <strong>$lang</strong></p>\n";

// Словарь переводов
$translations = [
    'ru' => [
        'welcome' => 'Добро пожаловать',
        'logout' => '🚪 Выйти',
        'character_generator' => '🎭 Генератор персонажей',
        'generate' => '🎲 Сгенерировать',
        'race' => 'Раса',
        'class' => 'Класс',
        'level' => 'Уровень',
        'gender' => 'Пол'
    ],
    'en' => [
        'welcome' => 'Welcome',
        'logout' => '🚪 Logout',
        'character_generator' => '🎭 Character Generator',
        'generate' => '🎲 Generate',
        'race' => 'Race',
        'class' => 'Class',
        'level' => 'Level',
        'gender' => 'Gender'
    ]
];

function t($key, $lang, $translations) {
    return $translations[$lang][$key] ?? $translations['ru'][$key] ?? $key;
}

echo "<h2>Переводы для языка: $lang</h2>\n";
echo "<ul>\n";
foreach ($translations[$lang] as $key => $value) {
    echo "<li><strong>$key:</strong> $value</li>\n";
}
echo "</ul>\n";

echo "<h2>Тест переключения</h2>\n";
echo "<p><a href='?lang=ru'>🇷🇺 Русский</a> | <a href='?lang=en'>🇺🇸 English</a></p>\n";

echo "<h2>Тест элементов интерфейса</h2>\n";
echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0;'>\n";
echo "<h3>" . t('character_generator', $lang, $translations) . "</h3>\n";
echo "<form>\n";
echo "<label>" . t('race', $lang, $translations) . ":</label><br>\n";
echo "<select><option>Human</option><option>Elf</option></select><br><br>\n";
echo "<label>" . t('class', $lang, $translations) . ":</label><br>\n";
echo "<select><option>Fighter</option><option>Wizard</option></select><br><br>\n";
echo "<label>" . t('level', $lang, $translations) . ":</label><br>\n";
echo "<input type='number' value='1' min='1' max='20'><br><br>\n";
echo "<label>" . t('gender', $lang, $translations) . ":</label><br>\n";
echo "<select><option>Male</option><option>Female</option></select><br><br>\n";
echo "<button type='button'>" . t('generate', $lang, $translations) . "</button>\n";
echo "</form>\n";
echo "</div>\n";

echo "<h2>✅ Тест завершен!</h2>\n";
echo "<p><a href='index.php'>Вернуться на главную</a></p>\n";
?>
