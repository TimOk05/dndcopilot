<?php
/**
 * Улучшенный сервис для генерации персонажей на основе JSON данных
 * Использует полную базу данных рас, классов, заклинаний, зелий и снаряжения
 */

class EnhancedCharacterService {
    private $racesData = null;
    private $classesData = null;
    private $namesData = null;
    private $spellsData = null;
    private $potionsData = null;
    private $equipmentData = null;
    
    private $dataDir;
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/../../data/';
    }
    
    /**
     * Загружает данные о расах
     */
    private function loadRacesData() {
        if ($this->racesData !== null) {
            return $this->racesData;
        }
        
        $file = $this->dataDir . 'персонажи/расы/races.json';
        if (!file_exists($file)) {
            logMessage('ERROR', 'Races file not found: ' . $file);
            return [];
        }
        
        $data = json_decode(file_get_contents($file), true);
        if (!$data || !isset($data['races'])) {
            logMessage('ERROR', 'Invalid races data');
            return [];
        }
        
        $this->racesData = $data['races'];
        logMessage('INFO', 'Races data loaded', ['count' => count($this->racesData)]);
        
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
        $classesDir = $this->dataDir . 'персонажи/классы/';
        
        if (!is_dir($classesDir)) {
            logMessage('ERROR', 'Classes directory not found: ' . $classesDir);
            return [];
        }
        
        $classFiles = glob($classesDir . '*/' . '*.json');
        
        foreach ($classFiles as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['class'])) {
                $classInfo = $data['class'];
                if (isset($classInfo['id'])) {
                    $this->classesData[$classInfo['id']] = $classInfo;
                }
            }
        }
        
        logMessage('INFO', 'Classes data loaded', ['count' => count($this->classesData)]);
        return $this->classesData;
    }
    
    /**
     * Загружает данные о заклинаниях
     */
    private function loadSpellsData() {
        if ($this->spellsData !== null) {
            return $this->spellsData;
        }
        
        $file = $this->dataDir . 'заклинания/заклинания.json';
        if (!file_exists($file)) {
            logMessage('ERROR', 'Spells file not found: ' . $file);
            return [];
        }
        
        $this->spellsData = json_decode(file_get_contents($file), true);
        if (!$this->spellsData) {
            logMessage('ERROR', 'Invalid spells data');
            return [];
        }
        
        logMessage('INFO', 'Spells data loaded', ['count' => count($this->spellsData)]);
        return $this->spellsData;
    }
    
    /**
     * Загружает данные о зельях
     */
    private function loadPotionsData() {
        if ($this->potionsData !== null) {
            return $this->potionsData;
        }
        
        $file = $this->dataDir . 'зелья/зелья.json';
        if (!file_exists($file)) {
            logMessage('ERROR', 'Potions file not found: ' . $file);
            return [];
        }
        
        $data = json_decode(file_get_contents($file), true);
        if (!$data || !isset($data['items'])) {
            logMessage('ERROR', 'Invalid potions data');
            return [];
        }
        
        $this->potionsData = $data['items'];
        logMessage('INFO', 'Potions data loaded', ['count' => count($this->potionsData)]);
        return $this->potionsData;
    }
    
    /**
     * Загружает данные о снаряжении
     */
    private function loadEquipmentData() {
        if ($this->equipmentData !== null) {
            return $this->equipmentData;
        }
        
        $file = $this->dataDir . 'персонажи/снаряжение/снаряжение.json';
        if (!file_exists($file)) {
            logMessage('ERROR', 'Equipment file not found: ' . $file);
            return [];
        }
        
        $this->equipmentData = json_decode(file_get_contents($file), true);
        if (!$this->equipmentData) {
            logMessage('ERROR', 'Invalid equipment data');
            return [];
        }
        
        logMessage('INFO', 'Equipment data loaded', ['count' => count($this->equipmentData)]);
        return $this->equipmentData;
    }
    
    /**
     * Загружает данные об именах
     */
    private function loadNamesData($raceId = null) {
        if ($raceId === null) {
            if ($this->namesData !== null) {
                return $this->namesData;
            }
            
            $file = $this->dataDir . 'персонажи/имена/имена.json';
            if (!file_exists($file)) {
                return [];
            }
            
            $this->namesData = json_decode(file_get_contents($file), true);
            return $this->namesData;
        }
        
        // Пытаемся загрузить расовые имена
        $raceNamesFile = __DIR__ . '/../../names/' . $raceId . '_names.json';
        if (file_exists($raceNamesFile)) {
            $data = json_decode(file_get_contents($raceNamesFile), true);
            if ($data) {
                return $data;
            }
        }
        
        // Fallback к общим именам
        return $this->loadNamesData();
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
        
        if (isset($races[$raceId])) {
            return $races[$raceId];
        }
        
        foreach ($races as $race) {
            if (isset($race['id']) && $race['id'] === $raceId) {
                return $race;
            }
        }
        
        return null;
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
        
        if (isset($classes[$classId])) {
            return $classes[$classId];
        }
        
        return null;
    }
    
    /**
     * Генерирует случайное имя для расы
     */
    public function generateRandomName($raceId, $gender = 'random') {
        $namesData = $this->loadNamesData($raceId);
        
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
                $base = 8;
                $points = 27;
                $scores = [];
                $abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
                
                foreach ($abilities as $ability) {
                    $cost = rand(0, min($points, 9));
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
                    array_shift($rolls);
                    $scores[$ability] = array_sum($rolls);
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
        
        logMessage('INFO', 'Starting enhanced character generation', [
            'race' => $raceId,
            'class' => $classId,
            'level' => $level
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
        
        // Генерируем характеристики
        $abilities = $this->generateAbilities('standard_array');
        
        // Применяем бонусы расы
        if (isset($race['ability_bonuses'])) {
            foreach ($race['ability_bonuses'] as $bonus) {
                $abilityKey = strtolower($bonus['ability']);
                $abilityMap = [
                    'str' => 'str', 'dex' => 'dex', 'con' => 'con',
                    'int' => 'int', 'wis' => 'wis', 'cha' => 'cha'
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
        $hitDie = 8;
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
        
        // Генерируем заклинания
        $spells = $this->generateSpells($class, $level);
        
        // Генерируем зелья
        $potions = $this->generatePotions(2);
        
        // Генерируем предысторию
        $background = $this->generateBackground($race, $class);
        
        // Генерируем черты характера
        $personality = $this->generatePersonalityTraits();
        
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
            'background_story' => $this->generateBackgroundStory($race, $class),
            'race_traits' => $this->getRaceTraits($race),
            'class_features' => $this->getClassFeatures($class, $level)
        ];
        
        logMessage('INFO', 'Enhanced character generated successfully', [
            'name' => $character['name'],
            'race' => $character['race'],
            'class' => $character['class']
        ]);
        
        return $character;
    }
    
    /**
     * Генерирует снаряжение на основе данных из JSON
     */
    private function generateEquipment($class) {
        $equipment = [
            'weapons' => [],
            'armor' => [],
            'tools' => [],
            'items' => [],
            'money' => '2к4 × 10 зм'
        ];
        
        $equipmentData = $this->loadEquipmentData();
        
        // Обрабатываем стартовое снаряжение класса
        if (isset($class['starting_equipment'])) {
            $this->processStartingEquipment($class['starting_equipment'], $equipment, $equipmentData);
        }
        
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
     * Обрабатывает стартовое снаряжение класса
     */
    private function processStartingEquipment($startingEquipment, &$equipment, $equipmentData) {
        if (isset($startingEquipment['fixed'])) {
            foreach ($startingEquipment['fixed'] as $item) {
                $equipment['items'][] = $item;
            }
        }
        
        if (isset($startingEquipment['choices'])) {
            foreach ($startingEquipment['choices'] as $choice) {
                if (isset($choice['choose']) && isset($choice['options'])) {
                    $chooseCount = $choice['choose'];
                    $options = $choice['options'];
                    
                    $selectedOptions = array_rand($options, min($chooseCount, count($options)));
                    if (!is_array($selectedOptions)) {
                        $selectedOptions = [$selectedOptions];
                    }
                    
                    foreach ($selectedOptions as $optionIndex) {
                        $option = $options[$optionIndex];
                        if (is_string($option)) {
                            $equipment['items'][] = $option;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Генерирует заклинания на основе JSON данных
     */
    private function generateSpells($class, $level) {
        $spells = [];
        $spellsData = $this->loadSpellsData();
        
        if (empty($spellsData)) {
            return $this->getDefaultSpells($class);
        }
        
        $classId = $class['id'] ?? '';
        $classSpells = [];
        
        // Фильтруем заклинания по классу
        foreach ($spellsData as $spell) {
            if (isset($spell['classes']) && in_array($classId, $spell['classes'])) {
                $classSpells[] = $spell;
            }
        }
        
        // Генерируем заговоры
        $cantrips = $this->getSpellsByLevel($classSpells, 0, 3);
        if (!empty($cantrips)) {
            $spells['cantrips'] = array_column($cantrips, 'name');
        }
        
        // Генерируем заклинания 1-го уровня
        if ($level >= 1) {
            $level1Spells = $this->getSpellsByLevel($classSpells, 1, 2);
            if (!empty($level1Spells)) {
                $spells['level_1'] = array_column($level1Spells, 'name');
            }
        }
        
        return $spells;
    }
    
    /**
     * Получает заклинания по уровню
     */
    private function getSpellsByLevel($spells, $level, $count) {
        $levelSpells = array_filter($spells, function($spell) use ($level) {
            return isset($spell['level']) && $spell['level'] == $level;
        });
        
        if (count($levelSpells) <= $count) {
            return array_values($levelSpells);
        }
        
        return array_slice(array_values($levelSpells), 0, $count);
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
                    'level_1' => ['Магическая стрела', 'Щит', 'Обнаружение магии']
                ];
            case 'sorcerer':
                return [
                    'cantrips' => ['Огненная стрела', 'Свет', 'Волшебная рука'],
                    'level_1' => ['Магическая стрела', 'Щит']
                ];
            case 'cleric':
                return [
                    'cantrips' => ['Свет', 'Чудотворство', 'Направленный удар'],
                    'level_1' => ['Лечение ран', 'Священное пламя', 'Благословение']
                ];
            default:
                return [];
        }
    }
    
    /**
     * Генерирует зелья на основе JSON данных
     */
    private function generatePotions($count = 2) {
        $potionsData = $this->loadPotionsData();
        
        if (empty($potionsData)) {
            return $this->getDefaultPotions($count);
        }
        
        // Фильтруем зелья по редкости
        $commonPotions = array_filter($potionsData, function($potion) {
            return isset($potion['rarity']) && $potion['rarity'] === 'uncommon';
        });
        
        if (count($commonPotions) <= $count) {
            return array_values($commonPotions);
        }
        
        $selectedPotions = array_rand($commonPotions, $count);
        if (!is_array($selectedPotions)) {
            $selectedPotions = [$selectedPotions];
        }
        
        $result = [];
        foreach ($selectedPotions as $index) {
            $result[] = $commonPotions[$index];
        }
        
        return $result;
    }
    
    /**
     * Возвращает зелья по умолчанию
     */
    private function getDefaultPotions($count) {
        $potions = [
            ['name' => 'Зелье лечения', 'effect' => 'Восстанавливает 2к4+2 хитов'],
            ['name' => 'Зелье силы', 'effect' => 'Увеличивает силу на 1 час'],
            ['name' => 'Зелье ловкости', 'effect' => 'Увеличивает ловкость на 1 час']
        ];
        
        $selectedPotions = [];
        for ($i = 0; $i < min($count, count($potions)); $i++) {
            $selectedPotions[] = $potions[array_rand($potions)];
        }
        
        return $selectedPotions;
    }
    
    /**
     * Получает расовые черты
     */
    private function getRaceTraits($race) {
        if (!isset($race['traits'])) {
            return [];
        }
        
        $traits = [];
        foreach ($race['traits'] as $trait) {
            $traits[] = [
                'name' => $trait['name'],
                'description' => $trait['description']
            ];
        }
        
        return $traits;
    }
    
    /**
     * Получает классовые способности
     */
    private function getClassFeatures($class, $level) {
        if (!isset($class['class_features'])) {
            return [];
        }
        
        $features = [];
        foreach ($class['class_features'] as $feature) {
            if ($feature['level'] <= $level) {
                $features[] = [
                    'name' => $feature['name'],
                    'level' => $feature['level'],
                    'description' => $feature['description'] ?? ''
                ];
            }
        }
        
        return $features;
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
