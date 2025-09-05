<?php
/**
 * Простая система переключения языков
 * По умолчанию английский, можно переключить на русский
 */

// Запускаем сессию если еще не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Определяем текущий язык
function getCurrentLanguage() {
    // Проверяем параметр в URL
    if (isset($_GET['lang'])) {
        $lang = $_GET['lang'];
        if (in_array($lang, ['en', 'ru'])) {
            $_SESSION['language'] = $lang;
            return $lang;
        }
    }
    
    // Проверяем сессию
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    // По умолчанию английский
    $_SESSION['language'] = 'en';
    return 'en';
}

// Простая функция перевода
function lang($key, $params = []) {
    $currentLang = getCurrentLanguage();
    
    // Простые переводы
    $translations = [
        'en' => [
            'app_name' => 'D&D Copilot',
            'welcome' => 'Welcome to D&D Copilot',
            'character_generator' => 'Character Generator',
            'enemy_generator' => 'Enemy Generator',
            'potion_generator' => 'Potion Generator',
            'combat_system' => 'Combat System',
            'dice_roller' => 'Dice Roller',
            'notes' => 'Notes',
            'login' => 'Login',
            'logout' => 'Logout',
            'settings' => 'Settings',
            'language' => 'Language',
            'english' => 'English',
            'russian' => 'Русский',
            'switch_to_russian' => 'Switch to Russian',
            'switch_to_english' => 'Switch to English',
            'roll_dice' => 'Roll Dice',
            'add_character' => 'Add Character',
            'create_enemy' => 'Create Enemy',
            'create_potion' => 'Create Potion',
            'enter_message' => 'Enter message...',
            'invalid_dice_format' => 'Invalid dice format!',
            'note_saved' => 'Note saved',
            'error_empty_content' => 'Error: empty content',
            'dice_roll' => 'Roll: {dice}',
            'dice_result' => 'Result: {result}',
            'dice_results' => 'Results: {results}',
            'dice_sum' => 'Sum: {sum}',
            'dice_comment' => 'Comment: {comment}',
        ],
        'ru' => [
            'app_name' => 'D&D Копайлот',
            'welcome' => 'Добро пожаловать в D&D Копайлот',
            'character_generator' => 'Генератор персонажей',
            'enemy_generator' => 'Генератор противников',
            'potion_generator' => 'Генератор зелий',
            'combat_system' => 'Система боя',
            'dice_roller' => 'Бросок костей',
            'notes' => 'Заметки',
            'login' => 'Вход',
            'logout' => 'Выйти',
            'settings' => 'Настройки',
            'language' => 'Язык',
            'english' => 'English',
            'russian' => 'Русский',
            'switch_to_russian' => 'Переключить на русский',
            'switch_to_english' => 'Switch to English',
            'roll_dice' => 'Бросить кости',
            'add_character' => 'Добавить персонажа',
            'create_enemy' => 'Создать противника',
            'create_potion' => 'Создать зелье',
            'enter_message' => 'Введите сообщение...',
            'invalid_dice_format' => 'Неверный формат кубов!',
            'note_saved' => 'Заметка сохранена',
            'error_empty_content' => 'Ошибка: пустое содержимое',
            'dice_roll' => 'Бросок: {dice}',
            'dice_result' => 'Результат: {result}',
            'dice_results' => 'Результаты: {results}',
            'dice_sum' => 'Сумма: {sum}',
            'dice_comment' => 'Комментарий: {comment}',
        ]
    ];
    
    $text = $translations[$currentLang][$key] ?? $key;
    
    // Заменяем параметры
    foreach ($params as $param => $value) {
        $text = str_replace('{' . $param . '}', $value, $text);
    }
    
    return $text;
}

// Получаем текущий язык
$current_language = getCurrentLanguage();
?>
