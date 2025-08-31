<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/dnd-api-service.php';
require_once __DIR__ . '/ai-service.php';

class CharacterGeneratorV3 {
    private $dnd_api_service;
    private $ai_service;
    private $occupations = [];
    private $race_names = [];
    
    public function __construct() {
        $this->dnd_api_service = new DndApiService();
        $this->ai_service = new AiService();
        $this->loadData();
    }
    
    /**
     * Загрузка всех необходимых данных
     */
    private function loadData() {
        // Загружаем профессии
        $this->loadOccupations();
        
        // Загружаем имена
        $this->loadRaceNames();
    }
    
    /**
     * Загрузка профессий из JSON файла
     */
    private function loadOccupations() {
        try {
            $jsonFile = __DIR__ . '/../pdf/d100_unique_traders.json';
            if (file_exists($jsonFile)) {
                $jsonData = json_decode(file_get_contents($jsonFile), true);
                if (isset($jsonData['data']['occupations'])) {
                    $this->occupations = $jsonData['data']['occupations'];
                }
            }
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to load occupations: ' . $e->getMessage());
        }
    }
    
    /**
     * Загрузка имен из JSON файла
     */
    private function loadRaceNames() {
        try {
            $jsonFile = __DIR__ . '/../pdf/dnd_race_names_ru_v2.json';
            if (file_exists($jsonFile)) {
                $jsonData = json_decode(file_get_contents($jsonFile), true);
                if (isset($jsonData['data'])) {
                    foreach ($jsonData['data'] as $raceData) {
                        $raceKey = strtolower($raceData['race']);
                        $this->race_names[$raceKey] = $raceData;
                    }
                }
            }
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to load race names: ' . $e->getMessage());
        }
    }
    
    /**
     * Генерация персонажа
     */
    public function generateCharacter($params) {
        try {
            // Валидация параметров
            $this->validateParams($params);
            
            $race = $params['race'] ?? 'human';
            $class = $params['class'] ?? 'fighter';
            $level = (int)($params['level'] ?? 1);
            $alignment = $params['alignment'] ?? 'neutral';
            $gender = $params['gender'] ?? 'random';
            $use_ai = isset($params['use_ai']) && $params['use_ai'] === 'on';
            
            // Получаем данные расы и класса из D&D API
            $race_data = $this->dnd_api_service->getRaceData($race);
            if (isset($race_data['error'])) {
                throw new Exception("Ошибка получения данных расы: {$race_data['message']}");
            }
            
            $class_data = $this->dnd_api_service->getClassData($class);
            if (isset($class_data['error'])) {
                throw new Exception("Ошибка получения данных класса: {$class_data['message']}");
            }
            
            // Генерируем характеристики
            $abilities = $this->generateAbilities($race_data, $level);
            
            // Получаем заклинания, снаряжение и способности из API
            $spells = $this->dnd_api_service->getSpellsForClass($class, $level);
            if (isset($spells['error'])) {
                logMessage('WARNING', "Ошибка получения заклинаний: {$spells['message']}");
                $spells = [];
            }
            
            $equipment = $this->dnd_api_service->getEquipmentForClass($class);
            if (isset($equipment['error'])) {
                logMessage('WARNING', "Ошибка получения снаряжения: {$equipment['message']}");
                $equipment = [];
            }
            
            $features = $this->dnd_api_service->getClassFeatures($class, $level);
            if (isset($features['error'])) {
                logMessage('WARNING', "Ошибка получения способностей: {$features['message']}");
                $features = [];
            }
            
            // Создаем персонажа
            $character = [
                'name' => $this->generateName($race, $gender),
                'race' => $race_data['name'],
                'class' => $class_data['name'],
                'level' => $level,
                'alignment' => $this->getAlignmentText($alignment),
                'gender' => $this->getGenderText($gender),
                'occupation' => $this->getRandomOccupation(),
                'abilities' => $abilities,
                'hit_points' => $this->calculateHP($class_data, $abilities['con'], $level),
                'armor_class' => $this->calculateAC($class_data, $abilities['dex']),
                'speed' => $race_data['speed'] ?? 30,
                'initiative' => $this->calculateInitiative($abilities['dex']),
                'proficiency_bonus' => $this->calculateProficiencyBonus($level),
                'attack_bonus' => $this->calculateAttackBonus($class_data, $abilities, $level),
                'damage' => $this->calculateDamage($class_data, $abilities, $level),
                'main_weapon' => $this->getMainWeapon($class_data),
                'proficiencies' => $class_data['proficiencies'] ?? [],
                'spells' => $spells,
                'features' => $features,
                'equipment' => $equipment,
                'saving_throws' => $this->getSavingThrows($class_data, $abilities),
                'race_traits' => $race_data['traits'] ?? [],
                'languages' => $race_data['languages'] ?? ['Общий'],
                'subraces' => $race_data['subraces'] ?? []
            ];
            
            // Генерируем описание и предысторию с помощью AI
            $description = $this->ai_service->generateCharacterDescription($character, $use_ai);
            if (isset($description['error'])) {
                logMessage('WARNING', "Ошибка генерации описания: {$description['message']}");
                $character['description'] = "Описание персонажа недоступно";
            } else {
                $character['description'] = $description;
            }
            
            $background = $this->ai_service->generateCharacterBackground($character, $use_ai);
            if (isset($background['error'])) {
                logMessage('WARNING', "Ошибка генерации предыстории: {$background['message']}");
                $character['background'] = "Предыстория персонажа недоступна";
            } else {
                $character['background'] = $background;
            }
            
            logMessage('INFO', 'Character generated successfully with API data', [
                'race' => $race,
                'class' => $class,
                'level' => $level,
                'api_data_used' => true
            ]);
            
            return [
                'success' => true,
                'character' => $character,
                'api_info' => [
                    'dnd_api_used' => true,
                    'ai_api_used' => $use_ai,
                    'data_source' => 'External D&D APIs + AI'
                ]
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Character generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Валидация параметров
     */
    private function validateParams($params) {
        $level = (int)($params['level'] ?? 1);
        if ($level < 1 || $level > 20) {
            throw new Exception('Уровень персонажа должен быть от 1 до 20');
        }
        
        $valid_races = ['human', 'elf', 'dwarf', 'halfling', 'orc', 'tiefling', 'dragonborn', 'gnome', 'half-elf', 'half-orc', 'tabaxi', 'aarakocra', 'goblin', 'kenku', 'lizardfolk', 'triton', 'yuan-ti', 'goliath', 'firbolg', 'bugbear', 'hobgoblin', 'kobold'];
        $race = $params['race'] ?? 'human';
        if (!in_array($race, $valid_races)) {
            throw new Exception('Неверная раса персонажа');
        }
        
        $valid_classes = ['fighter', 'wizard', 'rogue', 'cleric', 'ranger', 'barbarian', 'bard', 'druid', 'monk', 'paladin', 'sorcerer', 'warlock', 'artificer'];
        $class = $params['class'] ?? 'fighter';
        if (!in_array($class, $valid_classes)) {
            throw new Exception('Неверный класс персонажа');
        }
    }
    
    /**
     * Генерация характеристик
     */
    private function generateAbilities($race_data, $level = 1) {
        $abilities = [
            'str' => $this->rollAbilityScore(),
            'dex' => $this->rollAbilityScore(),
            'con' => $this->rollAbilityScore(),
            'int' => $this->rollAbilityScore(),
            'wis' => $this->rollAbilityScore(),
            'cha' => $this->rollAbilityScore()
        ];
        
        // Применяем бонусы расы из API данных
        if (isset($race_data['ability_bonuses'])) {
            foreach ($race_data['ability_bonuses'] as $ability => $bonus) {
                if (isset($abilities[$ability])) {
                    $abilities[$ability] += $bonus;
                    $abilities[$ability] = min(20, $abilities[$ability]);
                }
            }
        }
        
        // Улучшение характеристик с уровнем
        $ability_improvements = floor(($level - 1) / 4);
        if ($ability_improvements > 0) {
            $ability_names = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
            for ($i = 0; $i < $ability_improvements; $i++) {
                $ability = $ability_names[array_rand($ability_names)];
                $abilities[$ability] += 2;
                $abilities[$ability] = min(20, $abilities[$ability]);
            }
        }
        
        return $abilities;
    }
    
    /**
     * Бросок характеристики (4d6, убираем минимальный)
     */
    private function rollAbilityScore() {
        $rolls = [];
        for ($i = 0; $i < 4; $i++) {
            $rolls[] = rand(1, 6);
        }
        sort($rolls);
        array_shift($rolls); // Убираем минимальный
        return array_sum($rolls);
    }
    
    /**
     * Генерация имени
     */
    private function generateName($race, $gender) {
        $race = strtolower($race);
        $gender = strtolower($gender);
        
        // Пытаемся получить имена из загруженных данных
        if (isset($this->race_names[$race])) {
            $raceData = $this->race_names[$race];
            
            // Сначала ищем имена для конкретного пола
            if ($gender === 'male' && isset($raceData['male_names']) && !empty($raceData['male_names'])) {
                return $raceData['male_names'][array_rand($raceData['male_names'])];
            } elseif ($gender === 'female' && isset($raceData['female_names']) && !empty($raceData['female_names'])) {
                return $raceData['female_names'][array_rand($raceData['female_names'])];
            }
            
            // Затем унисекс имена
            if (isset($raceData['unisex_names']) && !empty($raceData['unisex_names'])) {
                return $raceData['unisex_names'][array_rand($raceData['unisex_names'])];
            }
            
            // В крайнем случае имена другого пола
            if ($gender === 'male' && isset($raceData['female_names']) && !empty($raceData['female_names'])) {
                return $raceData['female_names'][array_rand($raceData['female_names'])];
            } elseif ($gender === 'female' && isset($raceData['male_names']) && !empty($raceData['male_names'])) {
                return $raceData['male_names'][array_rand($raceData['male_names'])];
            }
        }
        
        // Fallback имена
        $fallback_names = [
            'male' => ['Торин', 'Арагорн', 'Леголас', 'Гимли', 'Боромир', 'Фродо', 'Сэм', 'Пиппин', 'Мерри'],
            'female' => ['Арвен', 'Галадриэль', 'Эовин', 'Розалинда', 'Морвен', 'Лютиэн', 'Идриль', 'Аэрин'],
            'unisex' => ['Аэлис', 'Киран', 'Рейвен', 'Скай', 'Тейлор', 'Морган', 'Алексис', 'Джордан']
        ];
        
        if ($gender === 'male') {
            return $fallback_names['male'][array_rand($fallback_names['male'])];
        } elseif ($gender === 'female') {
            return $fallback_names['female'][array_rand($fallback_names['female'])];
        } else {
            return $fallback_names['unisex'][array_rand($fallback_names['unisex'])];
        }
    }
    
    /**
     * Получение случайной профессии
     */
    private function getRandomOccupation() {
        if (!empty($this->occupations) && is_array($this->occupations)) {
            $occupation = $this->occupations[array_rand($this->occupations)];
            return is_string($occupation) ? $occupation : 'Авантюрист';
        }
        
        $fallback_occupations = [
            'Кузнец', 'Торговец', 'Охотник', 'Рыбак', 'Фермер', 'Шахтер', 
            'Плотник', 'Каменщик', 'Повар', 'Трактирщик', 'Ткач', 'Авантюрист'
        ];
        
        return $fallback_occupations[array_rand($fallback_occupations)];
    }
    
    /**
     * Расчет HP
     */
    private function calculateHP($class_data, $con_modifier, $level) {
        $hit_die = $class_data['hit_die'] ?? 8;
        $con_bonus = floor(($con_modifier - 10) / 2);
        
        // Первый уровень - максимум HP
        $hp = $hit_die + $con_bonus;
        
        // Дополнительные уровни
        for ($i = 2; $i <= $level; $i++) {
            $hp += rand(1, $hit_die) + $con_bonus;
        }
        
        return max(1, $hp);
    }
    
    /**
     * Расчет AC
     */
    private function calculateAC($class_data, $dex_modifier) {
        $dex_bonus = floor(($dex_modifier - 10) / 2);
        
        // Базовый AC зависит от класса
        $base_ac = 10;
        if (in_array('Все доспехи', $class_data['proficiencies'] ?? [])) {
            $base_ac = 16; // Кольчуга
        } elseif (in_array('Легкие доспехи', $class_data['proficiencies'] ?? [])) {
            $base_ac = 12; // Кожаные доспехи
        }
        
        return $base_ac + $dex_bonus;
    }
    
    /**
     * Расчет инициативы
     */
    private function calculateInitiative($dex_modifier) {
        return floor(($dex_modifier - 10) / 2);
    }
    
    /**
     * Расчет бонуса владения
     */
    private function calculateProficiencyBonus($level) {
        return floor(($level - 1) / 4) + 2;
    }
    
    /**
     * Расчет бонуса атаки
     */
    private function calculateAttackBonus($class_data, $abilities, $level) {
        $proficiency_bonus = $this->calculateProficiencyBonus($level);
        
        // Определяем основную характеристику для атаки
        $primary_ability = 'str';
        if (in_array($class_data['name'], ['Плут', 'Следопыт', 'Монах'])) {
            $primary_ability = 'dex';
        } elseif (in_array($class_data['name'], ['Волшебник', 'Артифисер'])) {
            $primary_ability = 'int';
        } elseif (in_array($class_data['name'], ['Жрец', 'Друид'])) {
            $primary_ability = 'wis';
        } elseif (in_array($class_data['name'], ['Бард', 'Чародей', 'Колдун', 'Паладин'])) {
            $primary_ability = 'cha';
        }
        
        $ability_modifier = floor(($abilities[$primary_ability] - 10) / 2);
        return $proficiency_bonus + $ability_modifier;
    }
    
    /**
     * Расчет урона
     */
    private function calculateDamage($class_data, $abilities, $level) {
        $primary_ability = 'str';
        if (in_array($class_data['name'], ['Плут', 'Следопыт', 'Монах'])) {
            $primary_ability = 'dex';
        }
        
        $ability_modifier = floor(($abilities[$primary_ability] - 10) / 2);
        
        // Базовый урон зависит от класса
        $base_damage = '1d8';
        if (in_array($class_data['name'], ['Воин', 'Варвар', 'Паладин'])) {
            $base_damage = '1d10';
        } elseif (in_array($class_data['name'], ['Плут'])) {
            $base_damage = '1d6';
        }
        
        return $base_damage . ($ability_modifier >= 0 ? '+' . $ability_modifier : $ability_modifier);
    }
    
    /**
     * Получение основного оружия
     */
    private function getMainWeapon($class_data) {
        $weapons = [
            'Воин' => 'Длинный меч',
            'Плут' => 'Короткий меч',
            'Волшебник' => 'Посох',
            'Жрец' => 'Булава',
            'Следопыт' => 'Длинный лук',
            'Варвар' => 'Боевой топор',
            'Бард' => 'Рапира',
            'Друид' => 'Посох',
            'Монах' => 'Кулаки',
            'Паладин' => 'Длинный меч',
            'Чародей' => 'Кинжал',
            'Колдун' => 'Кинжал',
            'Артифисер' => 'Кинжал'
        ];
        
        return $weapons[$class_data['name']] ?? 'Короткий меч';
    }
    
    /**
     * Получение спасбросков
     */
    private function getSavingThrows($class_data, $abilities) {
        $saving_throws = [];
        
        // Базовые спасброски для всех
        $saving_throws[] = ['name' => 'Сила', 'modifier' => floor(($abilities['str'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Ловкость', 'modifier' => floor(($abilities['dex'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Телосложение', 'modifier' => floor(($abilities['con'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Интеллект', 'modifier' => floor(($abilities['int'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Мудрость', 'modifier' => floor(($abilities['wis'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Харизма', 'modifier' => floor(($abilities['cha'] - 10) / 2)];
        
        return $saving_throws;
    }
    
    /**
     * Получение текста пола
     */
    private function getGenderText($gender) {
        return $gender === 'male' ? 'Мужчина' : 'Женщина';
    }
    
    /**
     * Получение текста мировоззрения
     */
    private function getAlignmentText($alignment) {
        $alignments = [
            'lawful-good' => 'Законно-добрый',
            'neutral-good' => 'Нейтрально-добрый',
            'chaotic-good' => 'Хаотично-добрый',
            'lawful-neutral' => 'Законно-нейтральный',
            'neutral' => 'Нейтральный',
            'chaotic-neutral' => 'Хаотично-нейтральный',
            'lawful-evil' => 'Законно-злой',
            'neutral-evil' => 'Нейтрально-злой',
            'chaotic-evil' => 'Хаотично-злой'
        ];
        
        return $alignments[$alignment] ?? 'Нейтральный';
    }
}

// Обработка запроса
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $generator = new CharacterGeneratorV3();
    $result = $generator->generateCharacter($_POST);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Метод не поддерживается'
    ]);
}
?>
