<?php
/**
 * Сервис для работы с персонажами D&D 5e
 * Обеспечивает загрузку, генерацию и форматирование персонажей
 */

class CharacterService {
    private $racesData = null;
    private $classesData = null;
    private $backgroundsData = null;
    private $namesData = null;
    
    private $racesFile;
    private $classesDir;
    private $backgroundsFile;
    private $namesDir;
    
    public function __construct() {
        $this->racesFile = __DIR__ . '/../../data/персонажи/расы/races.json';
        $this->classesDir = __DIR__ . '/../../data/персонажи/классы/';
        $this->backgroundsFile = __DIR__ . '/../../data/персонажи/предыстории/предыстории.json';
        $this->namesDir = __DIR__ . '/../../data/персонажи/имена/';
    }
    
    /**
     * Загружает данные о расах из JSON файла
     */
    private function loadRacesData() {
        if ($this->racesData !== null) {
            return $this->racesData;
        }
        
        if (!file_exists($this->racesFile)) {
            throw new Exception('Файл с расами не найден');
        }
        
        $jsonContent = file_get_contents($this->racesFile);
        $this->racesData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка при чтении файла рас: ' . json_last_error_msg());
        }
        
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
     * Загружает данные о предысториях
     */
    private function loadBackgroundsData() {
        if ($this->backgroundsData !== null) {
            return $this->backgroundsData;
        }
        
        if (!file_exists($this->backgroundsFile)) {
            throw new Exception('Файл с предысториями не найден');
        }
        
        $jsonContent = file_get_contents($this->backgroundsFile);
        $this->backgroundsData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка при чтении файла предысторий: ' . json_last_error_msg());
        }
        
        return $this->backgroundsData;
    }
    
    /**
     * Загружает данные об именах
     */
    private function loadNamesData() {
        if ($this->namesData !== null) {
            return $this->namesData;
        }
        
        $this->namesData = [];
        $nameFiles = glob($this->namesDir . '*.json');
        
        foreach ($nameFiles as $file) {
            $jsonContent = file_get_contents($file);
            $nameData = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $filename = basename($file, '.json');
                $this->namesData[$filename] = $nameData;
            }
        }
        
        return $this->namesData;
    }
    
    /**
     * Получает все расы
     */
    public function getRaces() {
        $data = $this->loadRacesData();
        return $data['races'] ?? [];
    }
    
    /**
     * Получает расу по ID
     */
    public function getRaceById($raceId) {
        $data = $this->loadRacesData();
        $races = $data['races'] ?? [];
        
        // Ищем по ключу (например, "human" для race_human)
        if (isset($races[$raceId])) {
            return $races[$raceId];
        }
        
        // Ищем по ID в значениях
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
        return $classes[$classId] ?? null;
    }
    
    /**
     * Получает архетипы для указанного класса
     */
    public function getArchetypes($classId) {
        $class = $this->getClassById($classId);
        if ($class && isset($class['archetypes'])) {
            return $class['archetypes'];
        }
        return [];
    }
    
    /**
     * Получает все предыстории
     */
    public function getBackgrounds() {
        $data = $this->loadBackgroundsData();
        return $data['backgrounds'] ?? [];
    }
    
    /**
     * Получает предысторию по ID
     */
    public function getBackgroundById($backgroundId) {
        $backgrounds = $this->getBackgrounds();
        foreach ($backgrounds as $background) {
            if ($background['id'] === $backgroundId) {
                return $background;
            }
        }
        return null;
    }
    
    /**
     * Генерирует случайное имя для расы
     */
    public function generateRandomName($raceId, $gender = 'random') {
        $namesData = $this->loadNamesData();
        
        // Ищем файл с именами для данной расы
        $raceNameFile = strtolower($raceId) . '_names.json';
        
        // Если нет специального файла для расы, используем общий
        if (!isset($namesData[$raceNameFile])) {
            $raceNameFile = 'common_names.json';
        }
        
        // Если и общего файла нет, используем человеческие имена
        if (!isset($namesData[$raceNameFile])) {
            $raceNameFile = 'human_names.json';
        }
        
        if (!isset($namesData[$raceNameFile])) {
            return 'Неизвестное имя';
        }
        
        $nameData = $namesData[$raceNameFile];
        
        if ($gender === 'random') {
            $gender = (rand(0, 1) === 0) ? 'male' : 'female';
        }
        
        $names = $nameData[$gender] ?? $nameData['male'] ?? [];
        
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
        
        if (!$race || !$class) {
            throw new Exception('Не найдены данные о расе или классе');
        }
        
        // Генерируем имя
        $name = $this->generateRandomName($raceId, $gender);
        
        // Генерируем характеристики (используем стандартный массив)
        $abilities = $this->generateAbilities('standard_array');
        
        // Применяем бонусы расы
        if (isset($race['ability_bonuses'])) {
            foreach ($race['ability_bonuses'] as $bonus) {
                if (isset($abilities[$bonus['ability']])) {
                    $abilities[$bonus['ability']] += $bonus['bonus'];
                }
            }
        }
        
        // Вычисляем модификаторы
        $modifiers = [];
        foreach ($abilities as $ability => $score) {
            $modifiers[$ability] = $this->getAbilityModifier($score);
        }
        
        // Генерируем хиты
        $hitDie = $class['hit_die'] ?? 8;
        $hitPoints = $hitDie + $modifiers['con'];
        
        // Генерируем КД
        $armorClass = 10 + $modifiers['dex'];
        
        // Генерируем инициативу
        $initiative = $modifiers['dex'];
        
        // Генерируем бонус мастерства
        $proficiencyBonus = 2; // Для 1-4 уровня
        
        // Генерируем снаряжение
        $equipment = $this->generateEquipment($class, null);
        
        // Генерируем заклинания (если есть)
        $spells = $this->generateSpells($class, $level);
        
        // Создаем персонажа
        $character = [
            'name' => $name,
            'race' => $race['name_ru'] ?? $race['name'],
            'class' => $class['name_ru'] ?? $class['name'],
            'level' => $level,
            'gender' => $gender,
            'alignment' => $this->getRandomAlignment($alignment),
            'background' => 'Случайная',
            'abilities' => $abilities,
            'modifiers' => $modifiers,
            'hit_points' => $hitPoints,
            'armor_class' => $armorClass,
            'speed' => $race['speed'] ?? 30,
            'initiative' => $initiative,
            'proficiency_bonus' => $proficiencyBonus,
            'equipment' => $equipment,
            'spells' => $spells,
            'description' => $this->generateDescription($race, $class, null),
            'background_story' => $this->generateBackgroundStory($race, $class, null)
        ];
        
        return $character;
    }
    
    /**
     * Генерирует снаряжение персонажа
     */
    private function generateEquipment($class, $background) {
        $equipment = [
            'weapons' => [],
            'armor' => [],
            'tools' => [],
            'items' => [],
            'money' => '10 зм'
        ];
        
        // Добавляем стартовое снаряжение класса
        if (isset($class['starting_equipment'])) {
            foreach ($class['starting_equipment'] as $item) {
                if (strpos($item, 'оружие') !== false || strpos($item, 'меч') !== false) {
                    $equipment['weapons'][] = $item;
                } elseif (strpos($item, 'доспех') !== false || strpos($item, 'броня') !== false) {
                    $equipment['armor'][] = $item;
                } else {
                    $equipment['items'][] = $item;
                }
            }
        }
        
        // Добавляем снаряжение предыстории
        if ($background && isset($background['starting_equipment'])) {
            foreach ($background['starting_equipment'] as $item) {
                $equipment['items'][] = $item;
            }
        }
        
        return $equipment;
    }
    
    /**
     * Генерирует заклинания для персонажа
     */
    private function generateSpells($class, $level) {
        $spells = [];
        
        // Простая логика для заклинаний
        if (isset($class['spellcasting']) && $class['spellcasting']) {
            $spellCount = min($level, 3); // Максимум 3 заклинания для простоты
            
            $commonSpells = [
                'Магическая стрела', 'Обнаружение магии', 'Лечение ран',
                'Щит', 'Огненный шар', 'Молния', 'Исцеление'
            ];
            
            for ($i = 0; $i < $spellCount; $i++) {
                $spells[] = $commonSpells[array_rand($commonSpells)];
            }
        }
        
        return $spells;
    }
    
    /**
     * Генерирует описание персонажа с помощью AI
     */
    private function generateDescription($race, $class, $background) {
        try {
            $aiService = new \AIService();
            $character = [
                'name' => 'Персонаж',
                'race' => $race['name_ru'] ?? $race['name'],
                'class' => $class['name_ru'] ?? $class['name'],
                'level' => 1,
                'gender' => 'неизвестен',
                'alignment' => 'нейтральный',
                'background' => $background ? ($background['name_ru'] ?? $background['name']) : 'Случайная',
                'abilities' => ['str' => 10, 'dex' => 10, 'con' => 10, 'int' => 10, 'wis' => 10, 'cha' => 10]
            ];
            
            return $aiService->generateCharacterDescription($character);
        } catch (Exception $e) {
            logMessage('WARNING', 'AI description generation failed', [
                'error' => $e->getMessage()
            ]);
            
            // Fallback к статическим описаниям
            $descriptions = [
                "{$race['name_ru']} {$class['name_ru']} с загадочным прошлым",
                "Опытный {$class['name_ru']} из народа {$race['name_ru']}",
                "Молодой {$race['name_ru']}, изучающий искусство {$class['name_ru']}",
                "Ветеран-{$class['name_ru']} с благородным происхождением"
            ];
            
            return $descriptions[array_rand($descriptions)];
        }
    }
    
    /**
     * Генерирует предысторию персонажа с помощью AI
     */
    private function generateBackgroundStory($race, $class, $background) {
        try {
            $aiService = new \AIService();
            $character = [
                'name' => 'Персонаж',
                'race' => $race['name_ru'] ?? $race['name'],
                'class' => $class['name_ru'] ?? $class['name'],
                'level' => 1,
                'gender' => 'неизвестен',
                'alignment' => 'нейтральный',
                'background' => $background ? ($background['name_ru'] ?? $background['name']) : 'Случайная',
                'abilities' => ['str' => 10, 'dex' => 10, 'con' => 10, 'int' => 10, 'wis' => 10, 'cha' => 10]
            ];
            
            return $aiService->generateCharacterBackground($character);
        } catch (Exception $e) {
            logMessage('WARNING', 'AI background generation failed', [
                'error' => $e->getMessage()
            ]);
            
            // Fallback к статическим историям
            $stories = [
                "Родился в небольшой деревне и с детства мечтал о приключениях",
                "Происходит из знатной семьи, но предпочел жизнь странника",
                "Был учеником мастера, который научил его основам боевого искусства",
                "Пережил трагедию в прошлом, что заставило его искать справедливости"
            ];
            
            return $stories[array_rand($stories)];
        }
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
}
?>
