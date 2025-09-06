<?php
/**
 * Language Service - Централизованный сервис для управления языками
 * Обеспечивает определение, хранение и переключение языков в приложении
 */

class LanguageService {
    private $current_language = 'ru';
    private $supported_languages = ['ru', 'en'];
    private $default_language = 'ru';
    private $session_key = 'dnd_app_language';
    
    public function __construct() {
        $this->initializeLanguage();
        logMessage('INFO', "Language Service инициализирован. Текущий язык: {$this->current_language}");
    }
    
    /**
     * Инициализация языка из различных источников
     */
    private function initializeLanguage() {
        // 1. Проверяем параметр запроса (приоритет 1)
        if (isset($_GET['lang']) && $this->isLanguageSupported($_GET['lang'])) {
            $this->current_language = $_GET['lang'];
            $this->saveLanguageToSession($this->current_language);
            return;
        }
        
        // 2. Проверяем POST параметр
        if (isset($_POST['language']) && $this->isLanguageSupported($_POST['language'])) {
            $this->current_language = $_POST['language'];
            $this->saveLanguageToSession($this->current_language);
            return;
        }
        
        // 3. Проверяем сессию
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION[$this->session_key]) && $this->isLanguageSupported($_SESSION[$this->session_key])) {
            $this->current_language = $_SESSION[$this->session_key];
            return;
        }
        
        // 4. Проверяем заголовки браузера
        $browser_lang = $this->detectBrowserLanguage();
        if ($browser_lang && $this->isLanguageSupported($browser_lang)) {
            $this->current_language = $browser_lang;
            $this->saveLanguageToSession($this->current_language);
            return;
        }
        
        // 5. Используем язык по умолчанию
        $this->current_language = $this->default_language;
        $this->saveLanguageToSession($this->current_language);
    }
    
    /**
     * Определение языка браузера
     */
    private function detectBrowserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }
        
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $languages = explode(',', $accept_language);
        
        foreach ($languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang_code = substr($lang, 0, 2);
            
            if ($this->isLanguageSupported($lang_code)) {
                return $lang_code;
            }
        }
        
        return null;
    }
    
    /**
     * Проверка поддержки языка
     */
    public function isLanguageSupported($language) {
        return in_array($language, $this->supported_languages);
    }
    
    /**
     * Получение текущего языка
     */
    public function getCurrentLanguage() {
        return $this->current_language;
    }
    
    /**
     * Установка языка
     */
    public function setLanguage($language) {
        if (!$this->isLanguageSupported($language)) {
            logMessage('WARNING', "Попытка установить неподдерживаемый язык: {$language}");
            return false;
        }
        
        $this->current_language = $language;
        $this->saveLanguageToSession($language);
        logMessage('INFO', "Язык изменен на: {$language}");
        return true;
    }
    
    /**
     * Сохранение языка в сессию
     */
    private function saveLanguageToSession($language) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[$this->session_key] = $language;
    }
    
    /**
     * Получение списка поддерживаемых языков
     */
    public function getSupportedLanguages() {
        return $this->supported_languages;
    }
    
    /**
     * Получение информации о языках
     */
    public function getLanguageInfo() {
        return [
            'current' => $this->current_language,
            'supported' => $this->supported_languages,
            'default' => $this->default_language,
            'names' => [
                'ru' => 'Русский',
                'en' => 'English'
            ]
        ];
    }
    
    /**
     * Проверка, нужен ли перевод
     */
    public function needsTranslation($target_language = null) {
        $target = $target_language ?? $this->current_language;
        return $target !== 'en'; // Английский - исходный язык API
    }
    
    /**
     * Получение направления текста
     */
    public function getTextDirection($language = null) {
        $lang = $language ?? $this->current_language;
        return $lang === 'ar' ? 'rtl' : 'ltr'; // Для будущего расширения
    }
    
    /**
     * Получение локализованного названия языка
     */
    public function getLanguageName($language_code, $display_language = null) {
        $display_lang = $display_language ?? $this->current_language;
        
        $names = [
            'ru' => [
                'ru' => 'Русский',
                'en' => 'Английский'
            ],
            'en' => [
                'ru' => 'Russian',
                'en' => 'English'
            ]
        ];
        
        return $names[$display_lang][$language_code] ?? $language_code;
    }
    
    /**
     * Получение локализованных строк интерфейса
     */
    public function getInterfaceText($key, $language = null) {
        $lang = $language ?? $this->current_language;
        
        $translations = [
            'ru' => [
                'language_selector' => 'Язык',
                'generate_potions' => 'Генерировать зелья',
                'potion_name' => 'Название',
                'potion_rarity' => 'Редкость',
                'potion_type' => 'Тип',
                'potion_description' => 'Описание',
                'potion_effects' => 'Эффекты',
                'potion_value' => 'Стоимость',
                'loading' => 'Загрузка...',
                'error' => 'Ошибка',
                'success' => 'Успешно',
                'count' => 'Количество',
                'rarity_common' => 'Обычное',
                'rarity_uncommon' => 'Необычное',
                'rarity_rare' => 'Редкое',
                'rarity_very_rare' => 'Очень редкое',
                'rarity_legendary' => 'Легендарное'
            ],
            'en' => [
                'language_selector' => 'Language',
                'generate_potions' => 'Generate Potions',
                'potion_name' => 'Name',
                'potion_rarity' => 'Rarity',
                'potion_type' => 'Type',
                'potion_description' => 'Description',
                'potion_effects' => 'Effects',
                'potion_value' => 'Value',
                'loading' => 'Loading...',
                'error' => 'Error',
                'success' => 'Success',
                'count' => 'Count',
                'rarity_common' => 'Common',
                'rarity_uncommon' => 'Uncommon',
                'rarity_rare' => 'Rare',
                'rarity_very_rare' => 'Very Rare',
                'rarity_legendary' => 'Legendary'
            ]
        ];
        
        return $translations[$lang][$key] ?? $key;
    }
    
    /**
     * Получение локализованных названий редкости
     */
    public function getRarityName($rarity, $language = null) {
        $lang = $language ?? $this->current_language;
        
        $rarity_translations = [
            'ru' => [
                'Common' => 'Обычное',
                'Uncommon' => 'Необычное',
                'Rare' => 'Редкое',
                'Very Rare' => 'Очень редкое',
                'Legendary' => 'Легендарное'
            ],
            'en' => [
                'Common' => 'Common',
                'Uncommon' => 'Uncommon',
                'Rare' => 'Rare',
                'Very Rare' => 'Very Rare',
                'Legendary' => 'Legendary'
            ]
        ];
        
        return $rarity_translations[$lang][$rarity] ?? $rarity;
    }
    
    /**
     * Получение локализованных названий типов зелий
     */
    public function getPotionTypeName($type, $language = null) {
        $lang = $language ?? $this->current_language;
        
        $type_translations = [
            'ru' => [
                'Восстановление' => 'Восстановление',
                'Усиление' => 'Усиление',
                'Защита' => 'Защита',
                'Иллюзия' => 'Иллюзия',
                'Трансмутация' => 'Трансмутация',
                'Некромантия' => 'Некромантия',
                'Прорицание' => 'Прорицание',
                'Эвокация' => 'Эвокация',
                'Универсальное' => 'Универсальное'
            ],
            'en' => [
                'Восстановление' => 'Restoration',
                'Усиление' => 'Enhancement',
                'Защита' => 'Protection',
                'Иллюзия' => 'Illusion',
                'Трансмутация' => 'Transmutation',
                'Некромантия' => 'Necromancy',
                'Прорицание' => 'Divination',
                'Эвокация' => 'Evocation',
                'Универсальное' => 'Universal'
            ]
        ];
        
        return $type_translations[$lang][$type] ?? $type;
    }
    
    /**
     * Получение локализованного названия расы
     */
    public function getRaceName($race, $language = null) {
        $lang = $language ?? $this->current_language;
        
        $race_translations = [
            'ru' => [
                'aarakocra' => 'Ааракокра',
                'aasimar' => 'Аасимар',
                'bugbear' => 'Багбир',
                'dragonborn' => 'Драконорожденный',
                'dwarf' => 'Дварф',
                'elf' => 'Эльф',
                'firbolg' => 'Фирболг',
                'genasi' => 'Генаси',
                'gnome' => 'Гном',
                'goblin' => 'Гоблин',
                'goliath' => 'Голиаф',
                'half-elf' => 'Полуэльф',
                'half-orc' => 'Полуорк',
                'halfling' => 'Полурослик',
                'human' => 'Человек',
                'kenku' => 'Кенку',
                'kobold' => 'Кобольд',
                'lizardfolk' => 'Людоящер',
                'orc' => 'Орк',
                'tabaxi' => 'Табакси',
                'tiefling' => 'Тифлинг',
                'triton' => 'Тритон',
                'yuan-ti' => 'Юань-ти'
            ],
            'en' => [
                'aarakocra' => 'Aarakocra',
                'aasimar' => 'Aasimar',
                'bugbear' => 'Bugbear',
                'dragonborn' => 'Dragonborn',
                'dwarf' => 'Dwarf',
                'elf' => 'Elf',
                'firbolg' => 'Firbolg',
                'genasi' => 'Genasi',
                'gnome' => 'Gnome',
                'goblin' => 'Goblin',
                'goliath' => 'Goliath',
                'half-elf' => 'Half-Elf',
                'half-orc' => 'Half-Orc',
                'halfling' => 'Halfling',
                'human' => 'Human',
                'kenku' => 'Kenku',
                'kobold' => 'Kobold',
                'lizardfolk' => 'Lizardfolk',
                'orc' => 'Orc',
                'tabaxi' => 'Tabaxi',
                'tiefling' => 'Tiefling',
                'triton' => 'Triton',
                'yuan-ti' => 'Yuan-ti'
            ]
        ];
        
        return $race_translations[$lang][$race] ?? $race;
    }
    
    /**
     * Получение локализованного названия класса
     */
    public function getClassName($class, $language = null) {
        $lang = $language ?? $this->current_language;
        
        $class_translations = [
            'ru' => [
                'barbarian' => 'Варвар',
                'bard' => 'Бард',
                'cleric' => 'Жрец',
                'druid' => 'Друид',
                'fighter' => 'Воин',
                'monk' => 'Монах',
                'paladin' => 'Паладин',
                'ranger' => 'Следопыт',
                'rogue' => 'Плут',
                'sorcerer' => 'Чародей',
                'warlock' => 'Колдун',
                'wizard' => 'Волшебник',
                'artificer' => 'Артифисер'
            ],
            'en' => [
                'barbarian' => 'Barbarian',
                'bard' => 'Bard',
                'cleric' => 'Cleric',
                'druid' => 'Druid',
                'fighter' => 'Fighter',
                'monk' => 'Monk',
                'paladin' => 'Paladin',
                'ranger' => 'Ranger',
                'rogue' => 'Rogue',
                'sorcerer' => 'Sorcerer',
                'warlock' => 'Warlock',
                'wizard' => 'Wizard',
                'artificer' => 'Artificer'
            ]
        ];
        
        return $class_translations[$lang][$class] ?? $class;
    }
    
    /**
     * Получение локализованного названия мировоззрения
     */
    public function getAlignmentName($alignment, $language = null) {
        $lang = $language ?? $this->current_language;
        
        $alignment_translations = [
            'ru' => [
                'lawful-good' => 'Законопослушный добрый',
                'neutral-good' => 'Нейтральный добрый',
                'chaotic-good' => 'Хаотичный добрый',
                'lawful-neutral' => 'Законопослушный нейтральный',
                'neutral' => 'Нейтральный',
                'chaotic-neutral' => 'Хаотичный нейтральный',
                'lawful-evil' => 'Законопослушный злой',
                'neutral-evil' => 'Нейтральный злой',
                'chaotic-evil' => 'Хаотичный злой'
            ],
            'en' => [
                'lawful-good' => 'Lawful Good',
                'neutral-good' => 'Neutral Good',
                'chaotic-good' => 'Chaotic Good',
                'lawful-neutral' => 'Lawful Neutral',
                'neutral' => 'Neutral',
                'chaotic-neutral' => 'Chaotic Neutral',
                'lawful-evil' => 'Lawful Evil',
                'neutral-evil' => 'Neutral Evil',
                'chaotic-evil' => 'Chaotic Evil'
            ]
        ];
        
        return $alignment_translations[$lang][$alignment] ?? $alignment;
    }
    
    /**
     * Логирование действий
     */
    private function logLanguageAction($action, $details = '') {
        logMessage('INFO', "Language Service: {$action}" . ($details ? " - {$details}" : ''));
    }
}

// Глобальная функция для быстрого доступа
function getCurrentLanguage() {
    static $language_service = null;
    if ($language_service === null) {
        $language_service = new LanguageService();
    }
    return $language_service->getCurrentLanguage();
}

// Глобальная функция для получения текста интерфейса
function t($key, $language = null) {
    static $language_service = null;
    if ($language_service === null) {
        $language_service = new LanguageService();
    }
    return $language_service->getInterfaceText($key, $language);
}
?>
