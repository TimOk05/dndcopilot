<?php
require_once 'config.php';

// Инициализируем языковую систему
$currentLang = getCurrentLanguage();
$translations = loadTranslations($currentLang);

// Загружаем переключатель языков
ob_start();
include 'language-switcher.php';
$languageSwitcher = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('app_title') ?> - <?= t('settings_language') ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #a67c52;
        }
        .test-section h3 {
            margin-top: 0;
            color: #a67c52;
        }
        .translation-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .key {
            font-weight: bold;
            color: #666;
            font-family: monospace;
        }
        .value {
            margin-top: 5px;
            color: #333;
        }
        .current-lang {
            background: #e8f5e8;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= t('app_title') ?> - <?= t('settings_language') ?></h1>
            <?= $languageSwitcher ?>
        </div>

        <div class="current-lang">
            <strong><?= t('settings_language') ?>:</strong> 
            <?php if ($currentLang === 'ru'): ?>
                🇷🇺 <?= t('language_russian') ?>
            <?php else: ?>
                🇺🇸 <?= t('language_english') ?>
            <?php endif; ?>
        </div>

        <div class="info">
            <h3>ℹ️ <?= t('welcome') ?></h3>
            <p>Эта страница демонстрирует работу системы языков в DnD Copilot. Вы можете переключаться между русским и английским языками с помощью кнопки в заголовке.</p>
        </div>

        <div class="test-section">
            <h3>🏠 <?= t('nav_home') ?></h3>
            <div class="translation-item">
                <div class="key">home_welcome</div>
                <div class="value"><?= t('home_welcome') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">home_subtitle</div>
                <div class="value"><?= t('home_subtitle') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">home_create_character</div>
                <div class="value"><?= t('home_create_character') ?></div>
            </div>
        </div>

        <div class="test-section">
            <h3>👤 <?= t('nav_characters') ?></h3>
            <div class="translation-item">
                <div class="key">character_generator</div>
                <div class="value"><?= t('character_generator') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">character_race</div>
                <div class="value"><?= t('character_race') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">character_class</div>
                <div class="value"><?= t('character_class') ?></div>
            </div>
        </div>

        <div class="test-section">
            <h3>⚔️ <?= t('nav_enemies') ?></h3>
            <div class="translation-item">
                <div class="key">enemy_generator</div>
                <div class="value"><?= t('enemy_generator') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">enemy_difficulty</div>
                <div class="value"><?= t('enemy_difficulty') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">enemy_easy</div>
                <div class="value"><?= t('enemy_easy') ?></div>
            </div>
        </div>

        <div class="test-section">
            <h3>🧪 <?= t('nav_potions') ?></h3>
            <div class="translation-item">
                <div class="key">potion_generator</div>
                <div class="value"><?= t('potion_generator') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">potion_rarity</div>
                <div class="value"><?= t('potion_rarity') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">potion_common</div>
                <div class="value"><?= t('potion_common') ?></div>
            </div>
        </div>

        <div class="test-section">
            <h3>🎲 <?= t('dice_roll') ?></h3>
            <div class="translation-item">
                <div class="key">dice_result</div>
                <div class="value"><?= t('dice_result') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">dice_comment</div>
                <div class="value"><?= t('dice_comment') ?></div>
            </div>
        </div>

        <div class="test-section">
            <h3>📝 <?= t('notes_title') ?></h3>
            <div class="translation-item">
                <div class="key">notes_add</div>
                <div class="value"><?= t('notes_add') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">notes_edit</div>
                <div class="value"><?= t('notes_edit') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">notes_delete</div>
                <div class="value"><?= t('notes_delete') ?></div>
            </div>
        </div>

        <div class="test-section">
            <h3>⚙️ <?= t('settings_title') ?></h3>
            <div class="translation-item">
                <div class="key">settings_theme</div>
                <div class="value"><?= t('settings_theme') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">settings_theme_light</div>
                <div class="value"><?= t('settings_theme_light') ?></div>
            </div>
            <div class="translation-item">
                <div class="key">settings_theme_dark</div>
                <div class="value"><?= t('settings_theme_dark') ?></div>
            </div>
        </div>

        <div class="info">
            <h3>🔧 Техническая информация</h3>
            <p><strong>Текущий язык:</strong> <?= $currentLang ?></p>
            <p><strong>Поддерживаемые языки:</strong> <?= implode(', ', SUPPORTED_LANGUAGES) ?></p>
            <p><strong>Язык по умолчанию:</strong> <?= DEFAULT_LANGUAGE ?></p>
            <p><strong>Всего переводов:</strong> <?= count($translations) ?></p>
        </div>
    </div>
</body>
</html>
