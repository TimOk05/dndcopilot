<?php
/**
 * Сервис для работы с персонажами D&D 5e
 * Обеспечивает загрузку, генерацию и форматирование персонажей
 */

class CharacterService {
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
            $this->racesData = [];
            return $this->racesData;
        }
        
        $jsonContent = file_get_contents($this->racesFile);
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->racesData = [];
            return $this->racesData;
        }
        
        $this->racesData = $data['races'] ?? [];
        
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
        $classFiles = glob($this->classesDir . '*/' . '*.json');
        
        foreach ($classFiles as $file) {
            $jsonContent = file_get_contents($file);
            $classData = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($classData['class'])) {
                $classInfo = $classData['class'];
                if (isset($classInfo['id'])) {
                    $this->classesData[$classInfo['id']] = $classInfo;
                }
            }
        }
        
        return $this->classesData;
    }
    
    
    /**
     * Загружает данные об именах для конкретной расы
     */
    private function loadNamesData($raceId = null) {
        if ($raceId === null) {
            return $this->namesData;
        }
        
        // Пытаемся загрузить расовые имена
        $raceNamesFile = __DIR__ . '/../../data/персонажи/имена/' . $raceId . '/мужские_имена.json';
        if (file_exists($raceNamesFile)) {
            $jsonContent = file_get_contents($raceNamesFile);
            $data = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        
        // Fallback к общим именам
        if (!file_exists($this->namesFile)) {
            return [];
        }
        
        $jsonContent = file_get_contents($this->namesFile);
        $this->namesData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->namesData = [];
        }
        
        return $this->namesData;
    }
    
    /**
     * Загружает данные о снаряжении
     */
    private function loadEquipmentData() {
        if (!file_exists($this->equipmentFile)) {
            return [];
        }
        
        $jsonContent = file_get_contents($this->equipmentFile);
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        return $data;
    }
    
    /**
     * Загружает данные о заклинаниях
     */
    private function loadSpellsData() {
        if (!file_exists($this->spellsFile)) {
            return [];
        }
        
        $jsonContent = file_get_contents($this->spellsFile);
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        return $data;
    }
    
    /**
     * Загружает данные о зельях
     */
    private function loadPotionsData() {
        if (!file_exists($this->potionsFile)) {
            return [];
        }
        
        $jsonContent = file_get_contents($this->potionsFile);
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        return $data;
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
        
        // Ищем по ключу (например, "gnome" для race_gnome)
        if (isset($races[$raceId])) {
            return $races[$raceId];
        }
        
        // Ищем по ID в значениях (например, "race_gnome")
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
        
        // Ищем по ключу (например, "fighter")
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
        
        // Загружаем данные о снаряжении
        $equipmentData = $this->loadEquipmentData();
        
        // Генерируем стартовое снаряжение на основе класса
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
        // Обрабатываем фиксированное снаряжение
        if (isset($startingEquipment['fixed'])) {
            foreach ($startingEquipment['fixed'] as $item) {
                $equipment['items'][] = $item;
            }
        }
        
        // Обрабатываем выборы снаряжения
        if (isset($startingEquipment['choices'])) {
            foreach ($startingEquipment['choices'] as $choice) {
                if (isset($choice['choose']) && isset($choice['options'])) {
                    $chooseCount = $choice['choose'];
                    $options = $choice['options'];
                    
                    // Случайно выбираем опции
                    $selectedOptions = array_rand($options, min($chooseCount, count($options)));
                    if (!is_array($selectedOptions)) {
                        $selectedOptions = [$selectedOptions];
                    }
                    
                    foreach ($selectedOptions as $optionIndex) {
                        $option = $options[$optionIndex];
                        if (is_string($option)) {
                            $equipment['items'][] = $option;
                        } elseif (is_array($option) && isset($option['type'])) {
                            $this->processEquipmentOption($option, $equipment, $equipmentData);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Обрабатывает опцию снаряжения
     */
    private function processEquipmentOption($option, &$equipment, $equipmentData) {
        switch ($option['type']) {
            case 'bundle':
                if (isset($option['items'])) {
                    foreach ($option['items'] as $item) {
                        if (isset($item['ref']) && isset($equipmentData['weapons'])) {
                            // Ищем оружие по ссылке
                            foreach ($equipmentData['weapons'] as $weapon) {
                                if (isset($weapon['id']) && $weapon['id'] === $item['ref']) {
                                    $equipment['weapons'][] = $weapon['name'];
                                    break;
                                }
                            }
                        } elseif (isset($item['ref']) && isset($equipmentData['armors'])) {
                            // Ищем доспех по ссылке
                            foreach ($equipmentData['armors'] as $armor) {
                                if (isset($armor['id']) && $armor['id'] === $item['ref']) {
                                    $equipment['armor'][] = $armor['name'];
                                    break;
                                }
                            }
                        }
                    }
                }
                break;
        }
    }
    
    /**
     * Генерирует заклинания для персонажа
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
        
        // Добавляем информацию о книге заклинаний для волшебника
        if ($classId === 'wizard') {
            $spells['spellbook'] = 'Книга заклинаний с 6 заклинаниями 1-го уровня';
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
     * Генерирует описание персонажа с помощью AI
     */
    private function generateDescription($race, $class) {
        try {
            // Проверяем, доступен ли AIService
            if (class_exists('AIService')) {
                $aiService = new \AIService();
                $character = [
                    'name' => 'Персонаж',
                    'race' => $race['name'] ?? 'Неизвестная раса',
                    'class' => $class['name']['ru'] ?? $class['name']['en'] ?? 'Неизвестный класс',
                    'level' => 1,
                    'gender' => 'неизвестен',
                    'alignment' => 'нейтральный',
                    'background' => 'Случайная',
                    'abilities' => ['str' => 10, 'dex' => 10, 'con' => 10, 'int' => 10, 'wis' => 10, 'cha' => 10]
                ];
                
                return $aiService->generateCharacterDescription($character);
            }
        } catch (Exception $e) {
            // Логируем ошибку, но продолжаем работу
            error_log('AI description generation failed: ' . $e->getMessage());
        }
        
        // Fallback к статическим описаниям
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
     * Генерирует предысторию персонажа с помощью AI
     */
    private function generateBackgroundStory($race, $class) {
        try {
            // Проверяем, доступен ли AIService
            if (class_exists('AIService')) {
                $aiService = new \AIService();
                $character = [
                    'name' => 'Персонаж',
                    'race' => $race['name'] ?? 'Неизвестная раса',
                    'class' => $class['name']['ru'] ?? $class['name']['en'] ?? 'Неизвестный класс',
                    'level' => 1,
                    'gender' => 'неизвестен',
                    'alignment' => 'нейтральный',
                    'background' => 'Случайная',
                    'abilities' => ['str' => 10, 'dex' => 10, 'con' => 10, 'int' => 10, 'wis' => 10, 'cha' => 10]
                ];
                
                return $aiService->generateCharacterBackground($character);
            }
        } catch (Exception $e) {
            // Логируем ошибку, но продолжаем работу
            error_log('AI background generation failed: ' . $e->getMessage());
        }
        
        // Fallback к статическим историям
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
        $potionsData = $this->loadPotionsData();
        
        if (empty($potionsData) || !isset($potionsData['items'])) {
            return [];
        }
        
        $potions = $potionsData['items'];
        $commonPotions = array_filter($potions, function($potion) {
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
