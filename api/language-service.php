<?php
/**
 * Language Service - Централизованная система управления языками
 * Поддерживает английский (по умолчанию) и русский языки
 */

class LanguageService {
    private static $instance = null;
    private $current_language = 'en';
    private $supported_languages = ['en', 'ru'];
    private $translations = [];
    
    private function __construct() {
        $this->loadLanguageFromSession();
        $this->loadTranslations();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Загрузка языка из сессии
     */
    private function loadLanguageFromSession() {
        // Проверяем, что мы не в CLI режиме
        if (php_sapi_name() !== 'cli') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION['language']) && in_array($_SESSION['language'], $this->supported_languages)) {
                $this->current_language = $_SESSION['language'];
            } else {
                // По умолчанию английский
                $this->current_language = 'en';
                $_SESSION['language'] = 'en';
            }
        } else {
            // В CLI режиме используем английский по умолчанию
            $this->current_language = 'en';
        }
    }
    
    /**
     * Загрузка переводов
     */
    private function loadTranslations() {
        $this->translations = [
            'en' => [
                // Races
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
                'yuan-ti' => 'Yuan-ti',
                
                // Classes
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
                'artificer' => 'Artificer',
                
                // Potion Types
                'healing' => 'Healing',
                'enhancement' => 'Enhancement',
                'protection' => 'Protection',
                'illusion' => 'Illusion',
                'transmutation' => 'Transmutation',
                'necromancy' => 'Necromancy',
                'divination' => 'Divination',
                'evocation' => 'Evocation',
                'universal' => 'Universal',
                
                // Monster Types
                'beast' => 'Beast',
                'humanoid' => 'Humanoid',
                'dragon' => 'Dragon',
                'giant' => 'Giant',
                'undead' => 'Undead',
                'fiend' => 'Fiend',
                'celestial' => 'Celestial',
                'elemental' => 'Elemental',
                'fey' => 'Fey',
                'monstrosity' => 'Monstrosity',
                'ooze' => 'Ooze',
                'plant' => 'Plant',
                'construct' => 'Construct',
                'aberration' => 'Aberration',
                'swarm' => 'Swarm',
                
                // Common
                'common' => 'Common',
                'uncommon' => 'Uncommon',
                'rare' => 'Rare',
                'very rare' => 'Very Rare',
                'legendary' => 'Legendary',
                'unknown' => 'Unknown',
                'description not available' => 'Description not available',
                'gold pieces' => 'gold pieces',
                'potion' => 'Potion',
                'magical' => 'Magical',
                'poison' => 'Poison'
            ],
            'ru' => [
                // Races
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
                'yuan-ti' => 'Юань-ти',
                
                // Classes
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
                'artificer' => 'Артифисер',
                
                // Potion Types
                'healing' => 'Восстановление',
                'enhancement' => 'Усиление',
                'protection' => 'Защита',
                'illusion' => 'Иллюзия',
                'transmutation' => 'Трансмутация',
                'necromancy' => 'Некромантия',
                'divination' => 'Прорицание',
                'evocation' => 'Эвокация',
                'universal' => 'Универсальное',
                
                // Monster Types
                'beast' => 'Зверь',
                'humanoid' => 'Гуманоид',
                'dragon' => 'Дракон',
                'giant' => 'Великан',
                'undead' => 'Нежить',
                'fiend' => 'Исчадие',
                'celestial' => 'Небожитель',
                'elemental' => 'Элементаль',
                'fey' => 'Фей',
                'monstrosity' => 'Чудовище',
                'ooze' => 'Слизь',
                'plant' => 'Растение',
                'construct' => 'Конструкт',
                'aberration' => 'Аберрация',
                'swarm' => 'Рой',
                
                // Common
                'common' => 'Обычный',
                'uncommon' => 'Необычный',
                'rare' => 'Редкий',
                'very rare' => 'Очень редкий',
                'legendary' => 'Легендарный',
                'unknown' => 'Неизвестно',
                'description not available' => 'Описание недоступно',
                'gold pieces' => 'золотых',
                'potion' => 'Зелье',
                'magical' => 'Магическое',
                'poison' => 'Яд'
            ]
        ];
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
        if (in_array($language, $this->supported_languages)) {
            $this->current_language = $language;
            
            // Сохраняем в сессию только если не в CLI режиме
            if (php_sapi_name() !== 'cli') {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['language'] = $language;
            }
            
            return true;
        }
        return false;
    }
    
    /**
     * Получение перевода
     */
    public function translate($key, $default = null) {
        $key = strtolower($key);
        
        if (isset($this->translations[$this->current_language][$key])) {
            return $this->translations[$this->current_language][$key];
        }
        
        // Если перевод не найден, возвращаем оригинал или значение по умолчанию
        return $default !== null ? $default : $key;
    }
    
    /**
     * Получение перевода расы
     */
    public function translateRace($race) {
        return $this->translate($race, $race);
    }
    
    /**
     * Получение перевода класса
     */
    public function translateClass($class) {
        return $this->translate($class, $class);
    }
    
    /**
     * Получение перевода типа зелья
     */
    public function translatePotionType($type) {
        return $this->translate($type, $type);
    }
    
    /**
     * Получение перевода типа монстра
     */
    public function translateMonsterType($type) {
        return $this->translate($type, $type);
    }
    
    /**
     * Получение перевода редкости
     */
    public function translateRarity($rarity) {
        return $this->translate($rarity, $rarity);
    }
    
    /**
     * Получение перевода стоимости
     */
    public function translateValue($value, $rarity = 'common') {
        if ($this->current_language === 'ru') {
            $rarity_translated = $this->translateRarity($rarity);
            $gold_translated = $this->translate('gold pieces');
            
            // Извлекаем числовое значение
            $numeric_value = preg_replace('/[^0-9]/', '', $value);
            if (empty($numeric_value)) {
                $numeric_value = $this->getDefaultValue($rarity);
            }
            
            return $numeric_value . ' ' . $gold_translated;
        }
        
        return $value;
    }
    
    /**
     * Получение значения по умолчанию для редкости
     */
    private function getDefaultValue($rarity) {
        $values = [
            'common' => '50',
            'uncommon' => '150',
            'rare' => '500',
            'very rare' => '1000',
            'legendary' => '5000'
        ];
        
        return $values[strtolower($rarity)] ?? '100';
    }
    
    /**
     * Получение системного промпта для AI
     */
    public function getAISystemPrompt() {
        if ($this->current_language === 'ru') {
            return 'Ты опытный мастер D&D, создающий атмосферные описания и истории. Отвечай на русском языке.';
        } else {
            return 'You are an experienced D&D master, creating atmospheric descriptions and stories. Respond in English.';
        }
    }
    
    /**
     * Получение поддерживаемых языков
     */
    public function getSupportedLanguages() {
        return $this->supported_languages;
    }
    
    /**
     * Проверка, является ли текущий язык русским
     */
    public function isRussian() {
        return $this->current_language === 'ru';
    }
    
    /**
     * Проверка, является ли текущий язык английским
     */
    public function isEnglish() {
        return $this->current_language === 'en';
    }
    
    /**
     * Получение названия языка
     */
    public function getLanguageName($language = null) {
        if ($language === null) {
            $language = $this->current_language;
        }
        
        $names = [
            'en' => 'English',
            'ru' => 'Русский'
        ];
        
        return $names[$language] ?? $language;
    }
}
?>
