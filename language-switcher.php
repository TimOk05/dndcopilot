<?php
// Компонент переключателя языков
require_once 'config.php';

$currentLang = getCurrentLanguage();
$translations = loadTranslations($currentLang);
?>

<div class="language-switcher">
    <button class="lang-btn" onclick="switchLanguage()" title="<?= t('language_switch') ?>">
        <?php if ($currentLang === 'ru'): ?>
            🇷🇺 <?= t('language_russian') ?>
        <?php else: ?>
            🇺🇸 <?= t('language_english') ?>
        <?php endif; ?>
    </button>
</div>

<style>
.language-switcher {
    position: relative;
    display: inline-block;
}

.lang-btn {
    background: var(--bg-secondary, #f8f9fa);
    border: 2px solid var(--border-primary, #dee2e6);
    border-radius: 8px;
    padding: 8px 12px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 500;
    color: var(--text-primary, #333);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.lang-btn:hover {
    background: var(--accent-primary, #a67c52);
    color: white;
    border-color: var(--accent-primary, #a67c52);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(166, 124, 82, 0.3);
}

.lang-btn:active {
    transform: translateY(0);
}

/* Адаптивность для мобильных устройств */
@media (max-width: 768px) {
    .lang-btn {
        padding: 6px 10px;
        font-size: 0.8em;
    }
}

/* Темная тема */
[data-theme="dark"] .lang-btn {
    background: var(--bg-secondary, #2d3748);
    border-color: var(--border-primary, #4a5568);
    color: var(--text-primary, #e2e8f0);
}

[data-theme="dark"] .lang-btn:hover {
    background: var(--accent-primary, #a67c52);
    border-color: var(--accent-primary, #a67c52);
}

/* Мистическая тема */
[data-theme="mystic"] .lang-btn {
    background: linear-gradient(135deg, rgba(114, 9, 183, 0.1), rgba(166, 124, 82, 0.1));
    border-color: var(--accent-primary, #7209b7);
    color: var(--accent-primary, #7209b7);
}

[data-theme="mystic"] .lang-btn:hover {
    background: linear-gradient(135deg, var(--accent-primary, #7209b7), var(--accent-secondary, #a67c52));
    color: white;
}
</style>

<script>
function switchLanguage() {
    const currentLang = '<?= $currentLang ?>';
    const newLang = currentLang === 'ru' ? 'en' : 'ru';
    
    // Обновляем URL с новым языком
    const url = new URL(window.location);
    url.searchParams.set('lang', newLang);
    
    // Перенаправляем на новую страницу с выбранным языком
    window.location.href = url.toString();
}

// Функция для получения текущего языка (для использования в других скриптах)
function getCurrentLanguage() {
    return '<?= $currentLang ?>';
}

// Функция для получения перевода (для использования в JavaScript)
function t(key, params = {}) {
    const translations = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    let text = translations[key] || key;
    
    // Заменяем параметры
    for (const [param, value] of Object.entries(params)) {
        text = text.replace(new RegExp(`\\{\\{${param}\\}\\}`, 'g'), value);
    }
    
    return text;
}
</script>
