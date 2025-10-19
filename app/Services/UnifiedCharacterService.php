<?php
/**
 * Унифицированный сервис для работы с персонажами D&D 5e
 * Объединяет лучшие части CharacterService и ImprovedCharacterService
 * Версия 3.0
 */

class UnifiedCharacterService {
    private $racesData = null;
    private $classesData = null;
    private $namesData = null;
    
    private $racesFile;
    private $classesDir;
    private $namesFile;
    private $equipmentFile;
    private $spellsFile;
    private $potionsFile;
    
    public function __construct() {
        $this->racesFile = __DIR__ . '/../../data/персонажи/расы/races.json';
        $this->classesDir = __DIR__ . '/../../data/персонажи/классы/';
        $this->namesFile = __DIR__ . '/../../data/персонажи/имена/имена.json';
        $this->equipmentFile = __DIR__ . '/../../data/персонажи/снаряжение/снаряжение.json';
        $this->spellsFile = __DIR__ . '/../../data/заклинания/заклинания.json';
        $this->potionsFile = __DIR__ . '/../../data/зелья/зелья.json';
    }
    
    /**
     * Загружает данные о расах из JSON файла
     */
    private function loadRacesData() {
        if ($this->racesData !== null) {
            return $this->racesData;
        }
        
        if (!file_exists($this->racesFile)) {
            logMessage('ERROR', 'Races file not found: ' . $this->racesFile);
            $this->racesData = [];
            return $this->racesData;
        }
        
        $jsonContent = file_get_contents($this->racesFile);
        if ($jsonContent === false) {
            logMessage('ERROR', 'Failed to read races file: ' . $this->racesFile);
            $this->racesData = [];
            return $this->racesData;
        }
        
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('ERROR', 'JSON decode error in races file: ' . json_last_error_msg());
            $this->racesData = [];
            return $this->racesData;
        }
        
        $this->racesData = $data['races'] ?? [];
        
        logMessage('INFO', 'Races data loaded successfully', [
            'count' => count($this->racesData)
        ]);
        
        return $this->racesData;
    }
    
    /**
     * Загружает данные о классах
     */
    private function loadClassesData() {
        if ($this->classesData !== null) {
            return $this->classesData;
        }
        
        $this->classesData = [];
        
        if (!is_dir($this->classesDir)) {
            logMessage('ERROR', 'Classes directory not found: ' . $this->classesDir);
            return $this->classesData;
        }
        
        $classFiles = glob($this->classesDir . '*/' . '*.json');
        
        foreach ($classFiles as $file) {
            $jsonContent = file_get_contents($file);
            if ($jsonContent === false) {
                logMessage('WARNING', 'Failed to read class file: ' . $file);
                continue;
            }
            
            $classData = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                logMessage('WARNING', 'JSON decode error in class file ' . $file . ': ' . json_last_error_msg());
                continue;
            }
            
            if (isset($classData['class'])) {
                $classInfo = $classData['class'];
                if (isset($classInfo['id'])) {
                    $this->classesData[$classInfo['id']] = $classInfo;
                }
            }
        }
        
        logMessage('INFO', 'Classes data loaded successfully', [
            'count' => count($this->classesData)
        ]);
        
        return $this->classesData;
    }
    
    /**
     * Загружает данные об именах для конкретной расы
     */
    private function loadNamesData($raceId = null) {
        if ($raceId === null) {
            return $this->namesData;
        }
        
        // Пытаемся загрузить расовые имена из папки names
        $raceNamesFile = __DIR__ . '/../../names/' . $raceId . '_names.json';
        if (file_exists($raceNamesFile)) {
            $jsonContent = file_get_contents($raceNamesFile);
            if ($jsonContent !== false) {
                $data = json_decode($jsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }
        }
        
        // Fallback к общим именам
        if (!file_exists($this->namesFile)) {
            logMessage('WARNING', 'Names file not found: ' . $this->namesFile);
            return [];
        }
        
        $jsonContent = file_get_contents($this->namesFile);
        if ($jsonContent === false) {
            logMessage('WARNING', 'Failed to read names file: ' . $this->namesFile);
            return [];
        }
        
        $this->namesData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('WARNING', 'JSON decode error in names file: ' . json_last_error_msg());
            $this->namesData = [];
        }
        
        return $this->namesData;
    }
    
    /**
     * Получает все расы
     */
    public function getRaces() {
        $races = $this->loadRacesData();
        return array_values($races);
    }
    
    /**
     * Получает расу по ID
     */
    public function getRaceById($raceId) {
        $races = $this->loadRacesData();
        
        // Ищем по ключу (например, "aarakocra")
        if (isset($races[$raceId])) {
            return $races[$raceId];
        }
        
        // Ищем по ID в значениях (например, "race_aarakocra")
        foreach ($races as $race) {
            if (isset($race['id']) && $race['id'] === $raceId) {
                return $race;
            }
        }
        
        return null;
    }
    
    /**
     * Получает подрасы для указанной расы
     */
    public function getSubraces($raceId) {
        $race = $this->getRaceById($raceId);
        if ($race && isset($race['subraces'])) {
            return $race['subraces'];
        }
        return [];
    }
    
    /**
     * Получает все классы
     */
    public function getClasses() {
        $classes = $this->loadClassesData();
        return array_values($classes);
    }
    
    /**
     * Получает класс по ID
     */
    public function getClassById($classId) {
        $classes = $this->loadClassesData();
        
        // Ищем по ключу
        if (isset($classes[$classId])) {
            return $classes[$classId];
        }
        
        return null;
    }
    
    /**
     * Получает архетипы для указанного класса
     */
    public function getArchetypes($classId) {
        $class = $this->getClassById($classId);
        if ($class && isset($class['subclasses'])) {
            return $class['subclasses'];
        }
        return [];
    }
    
    /**
     * Генерирует случайное имя для расы
     */
    public function generateRandomName($raceId, $gender = 'random') {
        // Сначала пытаемся загрузить расовые имена
        $namesData = $this->loadNamesData($raceId);
        
        if (empty($namesData)) {
            // Fallback к общим именам
            $namesData = $this->loadNamesData();
        }
        
        if (empty($namesData)) {
            return 'Неизвестное имя';
        }
        
        if ($gender === 'random') {
            $gender = (rand(0, 1) === 0) ? 'male' : 'female';
        }
        
        $names = $namesData[$gender] ?? $namesData['male'] ?? [];
        
        if (empty($names)) {
            return 'Неизвестное имя';
        }
        
        return $names[array_rand($names)];
    }
    
    /**
     * Генерирует характеристики персонажа
     */
    public function generateAbilities($method = 'standard_array') {
        switch ($method) {
            case 'standard_array':
                $scores = [15, 14, 13, 12, 10, 8];
                shuffle($scores);
                return [
                    'str' => $scores[0],
                    'dex' => $scores[1],
                    'con' => $scores[2],
                    'int' => $scores[3],
                    'wis' => $scores[4],
                    'cha' => $scores[5]
                ];
                
            case 'point_buy':
                // Упрощенная система покупки очков (27 очков)
                $base = 8;
                $points = 27;
                $scores = [];
                $abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
                
                foreach ($abilities as $ability) {
                    $cost = rand(0, min($points, 9)); // Максимум 9 очков на характеристику
                    $scores[$ability] = $base + $cost;
                    $points -= $cost;
                }
                
                return $scores;
                
            case 'roll_4d6':
                $scores = [];
                $abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
                
                foreach ($abilities as $ability) {
                    $rolls = [];
                    for ($i = 0; $i < 4; $i++) {
                        $rolls[] = rand(1, 6);
                    }
                    sort($rolls);
                    array_shift($rolls); // Убираем наименьший
                    $scores[$ability] = array_sum($rolls);
                }
                
                return $scores;
                
            case 'roll_3d6':
                $scores = [];
                $abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
                
                foreach ($abilities as $ability) {
                    $scores[$ability] = rand(1, 6) + rand(1, 6) + rand(1, 6);
                }
                
                return $scores;
                
            default:
                return $this->generateAbilities('standard_array');
        }
    }
    
    /**
     * Вычисляет модификатор характеристики
     */
    public function getAbilityModifier($score) {
        return floor(($score - 10) / 2);
    }
    
    /**
     * Генерирует полного персонажа
     */
    public function generateCharacter($params = []) {
        $raceId = $params['race'] ?? 'human';
        $classId = $params['class'] ?? 'fighter';
        $level = $params['level'] ?? 1;
        $gender = $params['gender'] ?? 'random';
        $alignment = $params['alignment'] ?? 'random';
        $subraceId = $params['subrace'] ?? '';
        $archetypeId = $params['archetype'] ?? '';
        
        logMessage('INFO', 'Starting character generation', [
            'race' => $raceId,
            'class' => $classId,
            'level' => $level,
            'gender' => $gender
        ]);
        
        // Получаем данные о расе и классе
        $race = $this->getRaceById($raceId);
        $class = $this->getClassById($classId);
        
        if (!$race) {
            throw new Exception("Раса не найдена: $raceId");
        }
        if (!$class) {
            throw new Exception("Класс не найден: $classId");
        }
        
        // Генерируем имя
        $name = $this->generateRandomName($raceId, $gender);
        
        // Генерируем характеристики (используем стандартный массив)
        $abilities = $this->generateAbilities('standard_array');
        
        // Применяем бонусы расы
        if (isset($race['ability_bonuses'])) {
            foreach ($race['ability_bonuses'] as $bonus) {
                $abilityKey = strtolower($bonus['ability']);
                // Преобразуем английские названия в русские ключи
                $abilityMap = [
                    'str' => 'str',
                    'dex' => 'dex', 
                    'con' => 'con',
                    'int' => 'int',
                    'wis' => 'wis',
                    'cha' => 'cha'
                ];
                
                if (isset($abilityMap[$abilityKey]) && isset($abilities[$abilityMap[$abilityKey]])) {
                    $abilities[$abilityMap[$abilityKey]] += $bonus['bonus'];
                }
            }
        }
        
        // Вычисляем модификаторы
        $modifiers = [];
        foreach ($abilities as $ability => $score) {
            $modifiers[$ability] = $this->getAbilityModifier($score);
        }
        
        // Генерируем хиты
        $hitDie = 8; // По умолчанию
        if (isset($class['hit_die'])) {
            $hitDie = (int)str_replace('d', '', $class['hit_die']);
        }
        $hitPoints = $hitDie + $modifiers['con'];
        
        // Генерируем КД
        $armorClass = 10 + $modifiers['dex'];
        
        // Генерируем инициативу
        $initiative = $modifiers['dex'];
        
        // Генерируем бонус мастерства
        $proficiencyBonus = 2; // Для 1-4 уровня
        
        // Генерируем снаряжение
        $equipment = $this->generateEquipment($class);
        
        // Генерируем заклинания (если есть)
        $spells = $this->generateSpells($class, $level);
        
        // Генерируем предысторию
        $background = $this->generateBackground($race, $class);
        
        // Генерируем черты характера
        $personality = $this->generatePersonalityTraits();
        
        // Генерируем случайные зелья
        $potions = $this->getRandomPotions(2);
        
        // Создаем персонажа
        $character = [
            'name' => $name,
            'race' => $race['name'] ?? 'Неизвестная раса',
            'class' => $class['name']['ru'] ?? $class['name']['en'] ?? 'Неизвестный класс',
            'level' => $level,
            'gender' => $gender,
            'alignment' => $this->getRandomAlignment($alignment),
            'background' => $background,
            'abilities' => $abilities,
            'modifiers' => $modifiers,
            'hit_points' => $hitPoints,
            'armor_class' => $armorClass,
            'speed' => $race['speed']['walk'] ?? 30,
            'initiative' => $initiative,
            'proficiency_bonus' => $proficiencyBonus,
            'equipment' => $equipment,
            'spells' => $spells,
            'potions' => $potions,
            'personality' => $personality,
            'description' => $this->generateDescription($race, $class),
            'background_story' => $this->generateBackgroundStory($race, $class)
        ];
        
        logMessage('INFO', 'Character generated successfully', [
            'name' => $character['name'],
            'race' => $character['race'],
            'class' => $character['class']
        ]);
        
        return $character;
    }
    
    /**
     * Генерирует снаряжение персонажа
     */
    private function generateEquipment($class) {
        $equipment = [
            'weapons' => [],
            'armor' => [],
            'tools' => [],
            'items' => [],
            'money' => '2к4 × 10 зм'
        ];
        
        // Добавляем базовое снаряжение
        $equipment['items'][] = 'Рюкзак';
        $equipment['items'][] = 'Спальный мешок';
        $equipment['items'][] = 'Столовые принадлежности';
        $equipment['items'][] = 'Кремень и огниво';
        $equipment['items'][] = 'Факел (10 штук)';
        $equipment['items'][] = 'Веревка (50 футов)';
        $equipment['items'][] = 'Дневной рацион (10 дней)';
        $equipment['items'][] = 'Бурдюк';
        
        return $equipment;
    }
    
    /**
     * Генерирует заклинания для персонажа
     */
    private function generateSpells($class, $level) {
        $spells = [];
        
        $classId = $class['id'] ?? '';
        
        // Генерируем заклинания по умолчанию
        $spells = $this->getDefaultSpells($class);
        
        return $spells;
    }
    
    /**
     * Возвращает заклинания по умолчанию
     */
    private function getDefaultSpells($class) {
        $classId = $class['id'] ?? '';
        switch ($classId) {
            case 'wizard':
                return [
                    'cantrips' => ['Волшебная рука', 'Свет', 'Чудотворство'],
                    'level_1' => ['Магическая стрела', 'Щит', 'Обнаружение магии'],
                    'spellbook' => 'Книга заклинаний с 6 заклинаниями 1-го уровня'
                ];
            case 'sorcerer':
                return [
                    'cantrips' => ['Огненная стрела', 'Свет', 'Волшебная рука'],
                    'level_1' => ['Магическая стрела', 'Щит']
                ];
            case 'bard':
                return [
                    'cantrips' => ['Чудотворство', 'Злая насмешка'],
                    'level_1' => ['Лечение ран', 'Очарование личности']
                ];
            case 'cleric':
                return [
                    'cantrips' => ['Свет', 'Чудотворство', 'Направленный удар'],
                    'level_1' => ['Лечение ран', 'Священное пламя', 'Благословение']
                ];
            case 'druid':
                return [
                    'cantrips' => ['Друидотворство', 'Направленный удар'],
                    'level_1' => ['Лечение ран', 'Добро животных', 'Волшебные ягоды']
                ];
            case 'warlock':
                return [
                    'cantrips' => ['Мистический взрыв', 'Свет'],
                    'level_1' => ['Огненные руки', 'Очарование личности'],
                    'pact' => 'Покровитель предоставляет особые способности'
                ];
            default:
                return [];
        }
    }
    
    /**
     * Генерирует описание персонажа
     */
    private function generateDescription($race, $class) {
        $raceName = $race['name'] ?? 'Неизвестная раса';
        $className = $class['name']['ru'] ?? $class['name']['en'] ?? 'Неизвестный класс';
        
        $descriptions = [
            "{$raceName} {$className} с загадочным прошлым",
            "Опытный {$className} из народа {$raceName}",
            "Молодой {$raceName}, изучающий искусство {$className}",
            "Ветеран-{$className} с благородным происхождением"
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    /**
     * Генерирует предысторию персонажа
     */
    private function generateBackgroundStory($race, $class) {
        $stories = [
            "Родился в небольшой деревне и с детства мечтал о приключениях",
            "Происходит из знатной семьи, но предпочел жизнь странника",
            "Был учеником мастера, который научил его основам боевого искусства",
            "Пережил трагедию в прошлом, что заставило его искать справедливости"
        ];
        
        return $stories[array_rand($stories)];
    }
    
    /**
     * Получает случайное мировоззрение
     */
    private function getRandomAlignment($alignment) {
        if ($alignment !== 'random') {
            return $alignment;
        }
        
        $alignments = [
            'Законопослушный добрый', 'Нейтральный добрый', 'Хаотичный добрый',
            'Законопослушный нейтральный', 'Истинно нейтральный', 'Хаотичный нейтральный',
            'Законопослушный злой', 'Нейтральный злой', 'Хаотичный злой'
        ];
        
        return $alignments[array_rand($alignments)];
    }
    
    /**
     * Получает случайные зелья
     */
    public function getRandomPotions($count = 2) {
        $potions = [
            ['name' => 'Зелье лечения', 'description' => 'Восстанавливает 2к4+2 хитов'],
            ['name' => 'Зелье силы', 'description' => 'Увеличивает силу на 1 час'],
            ['name' => 'Зелье ловкости', 'description' => 'Увеличивает ловкость на 1 час']
        ];
        
        $selectedPotions = [];
        for ($i = 0; $i < min($count, count($potions)); $i++) {
            $selectedPotions[] = $potions[array_rand($potions)];
        }
        
        return $selectedPotions;
    }
    
    /**
     * Генерирует предысторию персонажа
     */
    public function generateBackground($race, $class) {
        $backgrounds = [
            'Аколит' => 'Служитель храма, изучающий религиозные тексты',
            'Преступник' => 'Бывший вор или мошенник, знающий тёмные стороны города',
            'Народный герой' => 'Простолюдин, ставший героем благодаря храбрости',
            'Дворянин' => 'Представитель знати с богатым наследством',
            'Солдат' => 'Ветеран военных действий с боевым опытом',
            'Мудрец' => 'Учёный или исследователь, ищущий знания',
            'Матрос' => 'Опытный моряк, знающий океаны и порты',
            'Гильдейский ремесленник' => 'Мастер своего дела с профессиональными связями',
            'Отшельник' => 'Затворник, ищущий духовного просветления',
            'Бродяга' => 'Странник, знающий дороги и тайны мира'
        ];
        
        $background = array_rand($backgrounds);
        return [
            'name' => $background,
            'description' => $backgrounds[$background]
        ];
    }
    
    /**
     * Генерирует черты характера
     */
    public function generatePersonalityTraits() {
        $traits = [
            'Идеал' => [
                'Доброта' => 'Всегда помогаю тем, кто в беде',
                'Справедливость' => 'Нарушители закона должны быть наказаны',
                'Свобода' => 'Цепочки рабства должны быть разорваны',
                'Власть' => 'Я должен править, чтобы принести порядок',
                'Самосохранение' => 'Я должен выжить любой ценой',
                'Самосовершенствование' => 'Я должен стать сильнее'
            ],
            'Привязанность' => [
                'Семья' => 'Моя семья - самое важное в жизни',
                'Наставник' => 'Я обязан своему учителю всем',
                'Родина' => 'Моя земля нуждается в защите',
                'Друг' => 'Мой лучший друг всегда поддержит меня',
                'Любовь' => 'Я влюблён и готов на всё ради любимого',
                'Месть' => 'Я должен отомстить за несправедливость'
            ],
            'Недостаток' => [
                'Гордыня' => 'Я считаю себя лучше других',
                'Жадность' => 'Я не могу устоять перед золотом',
                'Трусость' => 'Я боюсь опасности',
                'Гнев' => 'Я легко впадаю в ярость',
                'Зависть' => 'Я завидую успехам других',
                'Лень' => 'Я избегаю тяжёлой работы'
            ]
        ];
        
        $result = [];
        foreach ($traits as $category => $options) {
            $option = array_rand($options);
            $result[$category] = [
                'name' => $option,
                'description' => $options[$option]
            ];
        }
        
        return $result;
    }
}
?>
