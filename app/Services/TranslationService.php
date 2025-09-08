<?php

/**
 * Централизованный сервис переводов для D&D терминов
 * Устраняет дублирование переводов по всему проекту
 */
class TranslationService {
    private static $instance = null;
    private $translations = [];
    
    private function __construct() {
        $this->loadTranslations();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Загрузка всех переводов
     */
    private function loadTranslations() {
        $this->translations = [
            'creature_types' => [
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
                'swarm' => 'Рой'
            ],
            
            'environments' => [
                'forest' => 'Лес',
                'mountain' => 'Горы',
                'desert' => 'Пустыня',
                'swamp' => 'Болото',
                'underdark' => 'Подземелье',
                'water' => 'Вода',
                'urban' => 'Город',
                'arctic' => 'Арктика',
                'coastal' => 'Побережье',
                'grassland' => 'Равнины',
                'hill' => 'Холмы',
                'jungle' => 'Джунгли',
                'cave' => 'Пещера',
                'underground' => 'Подземелье',
                'aquatic' => 'Водная',
                'ocean' => 'Океан',
                'sea' => 'Море',
                'city' => 'Город',
                'town' => 'Город',
                'beach' => 'Пляж',
                'shore' => 'Берег',
                'tundra' => 'Тундра',
                'cold' => 'Холодная',
                'arid' => 'Засушливая',
                'marsh' => 'Болото',
                'wetland' => 'Водно-болотные угодья',
                'highland' => 'Нагорье',
                'woodland' => 'Лесная местность',
                'celestial' => 'Небесная',
                'elemental' => 'Стихийная'
            ],
            
            'sizes' => [
                'tiny' => 'Крошечный',
                'small' => 'Маленький',
                'medium' => 'Средний',
                'large' => 'Большой',
                'huge' => 'Огромный',
                'gargantuan' => 'Гигантский'
            ],
            
            'damage_types' => [
                'acid' => 'Кислота',
                'bludgeoning' => 'Дробящий',
                'cold' => 'Холод',
                'fire' => 'Огонь',
                'force' => 'Силовой',
                'lightning' => 'Молния',
                'necrotic' => 'Некротический',
                'piercing' => 'Колющий',
                'poison' => 'Яд',
                'psychic' => 'Психический',
                'radiant' => 'Излучение',
                'slashing' => 'Режущий',
                'thunder' => 'Звуковой'
            ],
            
            'conditions' => [
                'blinded' => 'Ослепленный',
                'charmed' => 'Очарованный',
                'deafened' => 'Оглушенный',
                'exhaustion' => 'Истощенный',
                'frightened' => 'Испуганный',
                'grappled' => 'Захваченный',
                'incapacitated' => 'Недееспособный',
                'invisible' => 'Невидимый',
                'paralyzed' => 'Парализованный',
                'petrified' => 'Окаменевший',
                'poisoned' => 'Отравленный',
                'prone' => 'Опрокинутый',
                'restrained' => 'Скованный',
                'stunned' => 'Оглушенный',
                'unconscious' => 'Без сознания'
            ],
            
            'senses' => [
                'blindsight' => 'Слепозрение',
                'darkvision' => 'Темновидение',
                'tremorsense' => 'Чувство вибрации',
                'truesight' => 'Истинное зрение'
            ],
            
            'speed_types' => [
                'walk' => 'Ходьба',
                'fly' => 'Полёт',
                'swim' => 'Плавание',
                'climb' => 'Лазание',
                'burrow' => 'Рытьё',
                'hover' => 'Парение'
            ],
            
            'rarities' => [
                'common' => 'Обычное',
                'uncommon' => 'Необычное',
                'rare' => 'Редкое',
                'very_rare' => 'Очень редкое',
                'legendary' => 'Легендарное',
                'artifact' => 'Артефакт'
            ],
            
            'potion_types' => [
                'potion' => 'Зелье',
                'elixir' => 'Эликсир',
                'tonic' => 'Тоник',
                'brew' => 'Напиток',
                'draught' => 'Отвар'
            ],
            
            'classes' => [
                'fighter' => 'Воин',
                'wizard' => 'Маг',
                'rogue' => 'Плут',
                'cleric' => 'Жрец',
                'ranger' => 'Следопыт',
                'barbarian' => 'Варвар',
                'bard' => 'Бард',
                'druid' => 'Друид',
                'monk' => 'Монах',
                'paladin' => 'Паладин',
                'sorcerer' => 'Чародей',
                'warlock' => 'Колдун',
                'artificer' => 'Изобретатель'
            ],
            
            'races' => [
                'human' => 'Человек',
                'elf' => 'Эльф',
                'dwarf' => 'Дварф',
                'halfling' => 'Полурослик',
                'gnome' => 'Гном',
                'dragonborn' => 'Драконорожденный',
                'tiefling' => 'Тифлинг',
                'half-orc' => 'Полуорк',
                'half-elf' => 'Полуэльф'
            ]
        ];
    }
    
    /**
     * Перевод типа существа
     */
    public function translateCreatureType($type) {
        $type = strtolower($type);
        return $this->translations['creature_types'][$type] ?? $type;
    }
    
    /**
     * Перевод среды обитания
     */
    public function translateEnvironment($environment) {
        $environment = strtolower($environment);
        return $this->translations['environments'][$environment] ?? ucfirst($environment);
    }
    
    /**
     * Перевод размера
     */
    public function translateSize($size) {
        $size = strtolower($size);
        return $this->translations['sizes'][$size] ?? $size;
    }
    
    /**
     * Перевод типа урона
     */
    public function translateDamageType($type) {
        $type = strtolower($type);
        return $this->translations['damage_types'][$type] ?? $type;
    }
    
    /**
     * Перевод состояния
     */
    public function translateCondition($condition) {
        $condition = strtolower($condition);
        return $this->translations['conditions'][$condition] ?? $condition;
    }
    
    /**
     * Перевод чувства
     */
    public function translateSense($sense) {
        $sense = strtolower($sense);
        return $this->translations['senses'][$sense] ?? $sense;
    }
    
    /**
     * Перевод типа скорости
     */
    public function translateSpeedType($type) {
        $type = strtolower($type);
        return $this->translations['speed_types'][$type] ?? $type;
    }
    
    /**
     * Перевод редкости
     */
    public function translateRarity($rarity) {
        $rarity = strtolower($rarity);
        return $this->translations['rarities'][$rarity] ?? $rarity;
    }
    
    /**
     * Перевод типа зелья
     */
    public function translatePotionType($type) {
        $type = strtolower($type);
        return $this->translations['potion_types'][$type] ?? $type;
    }
    
    /**
     * Перевод класса
     */
    public function translateClass($class) {
        $class = strtolower($class);
        return $this->translations['classes'][$class] ?? $class;
    }
    
    /**
     * Перевод расы
     */
    public function translateRace($race) {
        $race = strtolower($race);
        return $this->translations['races'][$race] ?? $race;
    }
    
    /**
     * Перевод значения скорости (замена ft на фт)
     */
    public function translateSpeedValue($value) {
        $value = str_replace('ft.', 'фт.', $value);
        $value = str_replace('ft', 'фт', $value);
        return trim($value);
    }
    
    /**
     * Обратный перевод - с русского на английский
     */
    public function reverseTranslate($category, $russianTerm) {
        if (!isset($this->translations[$category])) {
            return $russianTerm;
        }
        
        $englishTerm = array_search($russianTerm, $this->translations[$category]);
        return $englishTerm !== false ? $englishTerm : $russianTerm;
    }
    
    /**
     * Получение всех переводов для категории
     */
    public function getTranslations($category) {
        return $this->translations[$category] ?? [];
    }
    
    /**
     * Проверка существования перевода
     */
    public function hasTranslation($category, $term) {
        return isset($this->translations[$category][strtolower($term)]);
    }
}
