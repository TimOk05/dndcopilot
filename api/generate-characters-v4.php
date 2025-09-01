<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/dnd-api-service.php';
require_once __DIR__ . '/ai-service.php';

class CharacterGeneratorV4 {
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
            logMessage('INFO', 'Начинаем генерацию персонажа', $params);
            
            // Валидация параметров
            $this->validateParams($params);
            
            $race = $params['race'] ?? 'human';
            $class = $params['class'] ?? 'fighter';
            $level = (int)($params['level'] ?? 1);
            $alignment = $params['alignment'] ?? 'neutral';
            $gender = $params['gender'] ?? 'random';
            $use_ai = isset($params['use_ai']) && $params['use_ai'] === 'on';
            
            // Получаем данные расы из D&D API
            logMessage('INFO', "Начинаем получение данных расы: {$race}");
            $race_data = $this->dnd_api_service->getRaceData($race);
            if (isset($race_data['error'])) {
                throw new Exception("Не удалось получить данные расы '{$race}': " . $race_data['message']);
            }
            logMessage('INFO', "Получены данные расы: " . json_encode($race_data, JSON_UNESCAPED_UNICODE));
            
            // Получаем данные класса из D&D API
            logMessage('INFO', "Начинаем получение данных класса: {$class}");
            $class_data = $this->dnd_api_service->getClassData($class);
            if (isset($class_data['error'])) {
                throw new Exception("Не удалось получить данные класса '{$class}': " . $class_data['message']);
            }
            logMessage('INFO', "Получены данные класса: " . json_encode($class_data, JSON_UNESCAPED_UNICODE));
            
            // Генерируем характеристики
            $abilities = $this->generateAbilities($race_data, $level);
            
            // Получаем заклинания, снаряжение и способности
            $spells = $this->dnd_api_service->getSpellsForClass($class, $level);
            if (isset($spells['error'])) {
                logMessage('WARNING', "Не удалось получить заклинания: " . $spells['message']);
                $spells = [];
            }
            
            $equipment = $this->dnd_api_service->getEquipmentForClass($class);
            if (isset($equipment['error'])) {
                logMessage('WARNING', "Не удалось получить снаряжение: " . $equipment['message']);
                $equipment = [];
            }
            
            $features = $this->dnd_api_service->getClassFeatures($class, $level);
            if (isset($features['error'])) {
                logMessage('WARNING', "Не удалось получить способности: " . $features['message']);
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
            
            // Генерируем описание и предысторию с AI
            if ($use_ai) {
                $description = $this->ai_service->generateCharacterDescription($character, true);
                if (isset($description['error'])) {
                    logMessage('WARNING', "AI генерация описания не удалась: " . $description['message']);
                    $character['description'] = $this->generateBasicDescription($character);
                } else {
                    $character['description'] = $this->cleanTextForJson($description);
                }
                
                $background = $this->ai_service->generateCharacterBackground($character, true);
                if (isset($background['error'])) {
                    logMessage('WARNING', "AI генерация предыстории не удалась: " . $background['message']);
                    $character['background'] = $this->generateBasicBackground($character);
                } else {
                    $character['background'] = $this->cleanTextForJson($background);
                }
            } else {
                $character['description'] = $this->generateBasicDescription($character);
                $character['background'] = $this->generateBasicBackground($character);
            }
            
            logMessage('INFO', 'Character generated successfully with API data', [
                'race' => $race,
                'class' => $class,
                'level' => $level,
                'api_data_used' => true,
                'ai_used' => $use_ai
            ]);
            
            return [
                'success' => true,
                'character' => $character,
                'api_info' => [
                    'dnd_api_used' => true,
                    'ai_api_used' => $use_ai,
                    'data_source' => 'External D&D APIs + AI',
                    'cache_info' => 'Enhanced caching system active'
                ]
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Character generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'details' => 'Generation failed due to API unavailability or data processing error'
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
        
        // Если пол не определен, выбираем случайно
        if ($gender === 'random') {
            $gender = rand(0, 1) ? 'male' : 'female';
        }
        
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
        
        // Если ничего не найдено, используем базовые имена
        $basic_names = [
            'male' => ['Торин', 'Арагорн', 'Боромир', 'Фродо', 'Сэм'],
            'female' => ['Арвен', 'Галадриэль', 'Эовин', 'Розалинда', 'Морвен']
        ];
        
        return $basic_names[$gender][array_rand($basic_names[$gender])];
    }
    
    /**
     * Получение случайной профессии
     */
    private function getRandomOccupation() {
        if (!empty($this->occupations) && is_array($this->occupations)) {
            $occupation = $this->occupations[array_rand($this->occupations)];
            return is_string($occupation) ? $occupation : 'Авантюрист';
        }
        
        $basic_occupations = [
            'Кузнец', 'Торговец', 'Охотник', 'Рыбак', 'Фермер', 'Шахтер', 
            'Плотник', 'Каменщик', 'Повар', 'Трактирщик', 'Ткач', 'Авантюрист'
        ];
        
        return $basic_occupations[array_rand($basic_occupations)];
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
        $proficiency_bonus = $this->calculateProficiencyBonus($level);
        
        return $ability_modifier + $proficiency_bonus;
    }
    
    /**
     * Получение основного оружия
     */
    private function getMainWeapon($class_data) {
        $weapons = [
            'Воин' => 'Меч',
            'Плут' => 'Кинжал',
            'Волшебник' => 'Посох',
            'Жрец' => 'Булава',
            'Следопыт' => 'Лук',
            'Варвар' => 'Топор',
            'Бард' => 'Рапира',
            'Друид' => 'Посох',
            'Монах' => 'Кулаки',
            'Паладин' => 'Меч',
            'Чародей' => 'Посох',
            'Колдун' => 'Кинжал',
            'Артифисер' => 'Молот'
        ];
        
        return $weapons[$class_data['name']] ?? 'Меч';
    }
    
    /**
     * Получение спасбросков
     */
    private function getSavingThrows($class_data, $abilities) {
        $saving_throws = [];
        $proficiency_bonus = 2; // Для 1 уровня
        
        if (isset($class_data['saving_throws'])) {
            foreach ($class_data['saving_throws'] as $ability) {
                $ability_modifier = floor(($abilities[$ability] - 10) / 2);
                $saving_throws[$ability] = $ability_modifier + $proficiency_bonus;
            }
        }
        
        return $saving_throws;
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
    
    /**
     * Получение текста пола
     */
    private function getGenderText($gender) {
        if ($gender === 'random') {
            $gender = rand(0, 1) ? 'male' : 'female';
        }
        
        return $gender === 'male' ? 'Мужчина' : 'Женщина';
    }
    
    /**
     * Генерация базового описания (без AI)
     */
    private function generateBasicDescription($character) {
        $race = $character['race'];
        $class = $character['class'];
        $gender = $character['gender'];
        
        $descriptions = [
            'Человек' => "{$gender} {$race} с решительным взглядом и уверенной походкой. {$class} с опытом и навыками.",
            'Эльф' => "Грациозный {$race} с острыми чертами лица. {$class} с врожденным чувством магии.",
            'Дварф' => "Крепкий {$race} с густой бородой и сильными руками. {$class} с традициями предков.",
            'Полурослик' => "Маленький {$race} с кудрявыми волосами и добродушным выражением лица. {$class} с ловкостью и хитростью.",
            'Орк' => "Мощный {$race} с зеленой кожей и клыками. {$class} с первобытной силой и яростью.",
            'Тифлинг' => "Темнокожий {$race} с рогами и хвостом. {$class} с адским наследием и тайной.",
            'Драконорожденный' => "Величественный {$race} с чешуей и дыханием дракона. {$class} с древней силой предков.",
            'Гном' => "Низкорослый {$race} с острым умом и хитростью. {$class} с врожденными способностями.",
            'Полуэльф' => "Грациозный {$race} с острыми чертами лица. {$class} с двойственным наследием.",
            'Полуорк' => "Мощный {$race} с зеленой кожей и клыками. {$class} с первобытной силой и яростью."
        ];
        
        return $descriptions[$race] ?? $descriptions['Человек'];
    }
    
    /**
     * Генерация базовой предыстории (без AI)
     */
    private function generateBasicBackground($character) {
        $occupation = $character['occupation'];
        $race = $character['race'];
        $class = $character['class'];
        
        $backgrounds = [
            'Кузнец' => "Родился в семье кузнецов. Изучал ремесло, но жажда приключений привела к изучению {$class}.",
            'Торговец' => "Путешествовал по миру, торгуя товарами. Научился защищаться и стал {$class}.",
            'Охотник' => "Проводил дни в лесах, выслеживая добычу. Навыки охоты помогли стать {$class}.",
            'Фермер' => "Работал на земле, выращивая урожай. Однажды понял, что может вырастить не только растения.",
            'Стражник' => "Служил в городской страже, защищая мирных жителей. Опыт пригодился в приключениях.",
            'Солдат' => "Служил в армии, участвовал во многих битвах. Военный опыт пригодился в приключениях.",
            'Ученый' => "Изучал древние тексты и артефакты. Однажды понял, что лучший способ изучения - личное участие.",
            'Авантюрист' => "Всегда мечтал о приключениях. Оставил родной дом в поисках славы и богатства.",
            'Рыбак' => "Проводил дни на воде, ловя рыбу. Навыки навигации пригодились в приключениях.",
            'Плотник' => "Работал с деревом, создавая мебель и строения. Теперь использует навыки для создания оружия.",
            'Каменщик' => "Строил дома и крепости. Опыт работы с камнем пригодился в бою.",
            'Повар' => "Готовил пищу для многих людей. Научился понимать их характеры и слабости.",
            'Трактирщик' => "Слушал истории путешественников. Теперь сам создает легенды.",
            'Ткач' => "Создавал ткани и одежду. Навыки точности пригодились в бою."
        ];
        
        return $backgrounds[$occupation] ?? $backgrounds['Кузнец'];
    }
    
    /**
     * Очистка текста для JSON
     */
    private function cleanTextForJson($text) {
        if (is_array($text)) {
            $text = $text['data'] ?? $text['message'] ?? json_encode($text);
        }
        
        if (!is_string($text)) {
            $text = (string)$text;
        }
        
        // Удаляем проблемные символы
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        $text = str_replace(['"', '"', '"', '"'], '"', $text);
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
        
        return trim($text);
    }
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $generator = new CharacterGeneratorV4();
        $result = $generator->generateCharacter($_POST);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Только POST запросы поддерживаются'
    ], JSON_UNESCAPED_UNICODE);
}
?>
