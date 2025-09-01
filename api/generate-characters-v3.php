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
            logMessage('INFO', 'Начинаем генерацию персонажа', $params);
            
            // Валидация параметров
            $this->validateParams($params);
            
            $race = $params['race'] ?? 'human';
            $class = $params['class'] ?? 'fighter';
            $level = (int)($params['level'] ?? 1);
            $alignment = $params['alignment'] ?? 'neutral';
            $gender = $params['gender'] ?? 'random';
            $use_ai = isset($params['use_ai']) && $params['use_ai'] === 'on';
            
            // Получаем данные расы и класса из D&D API
            logMessage('INFO', "Начинаем получение данных расы: {$race}");
            $race_data = $this->dnd_api_service->getRaceData($race);
            logMessage('INFO', "Получены данные расы: " . json_encode($race_data, JSON_UNESCAPED_UNICODE));
            
            if (isset($race_data['error'])) {
                logMessage('ERROR', "Ошибка получения данных расы: {$race_data['message']}");
                throw new Exception("Ошибка получения данных расы: {$race_data['message']}");
            }
            
            logMessage('INFO', "Начинаем получение данных класса: {$class}");
            $class_data = $this->dnd_api_service->getClassData($class);
            logMessage('INFO', "Получены данные класса: " . json_encode($class_data, JSON_UNESCAPED_UNICODE));
            
            if (isset($class_data['error'])) {
                logMessage('ERROR', "Ошибка получения данных класса: {$class_data['message']}");
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
            
            $equipment = $this->getEquipmentData($class);
            
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
            
            // Генерируем описание и предысторию
            $description = $this->generateDescription($character, $use_ai);
            $character['description'] = $this->cleanTextForJson($description);
            
            $background = $this->generateBackground($character, $use_ai);
            $character['background'] = $this->cleanTextForJson($background);
            
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
        
        // Fallback имена по расам
        $race_names = [
            'human' => [
                'male' => ['Торин', 'Арагорн', 'Боромир', 'Фродо', 'Сэм', 'Пиппин', 'Мерри', 'Гэндальф', 'Арагорн', 'Леголас'],
                'female' => ['Арвен', 'Галадриэль', 'Эовин', 'Розалинда', 'Морвен', 'Лютиэн', 'Идриль', 'Аэрин', 'Нимродель'],
                'unisex' => ['Аэлис', 'Киран', 'Рейвен', 'Скай', 'Тейлор', 'Морган', 'Алексис', 'Джордан', 'Кейси']
            ],
            'elf' => [
                'male' => ['Леголас', 'Элронд', 'Глорфиндель', 'Эрестор', 'Келеборн', 'Халдир', 'Румил', 'Орофин'],
                'female' => ['Галадриэль', 'Арвен', 'Нимродель', 'Идриль', 'Аэрин', 'Лютиэн', 'Мелиан', 'Элвинг'],
                'unisex' => ['Аэлис', 'Киран', 'Рейвен', 'Скай', 'Тейлор', 'Морган', 'Алексис', 'Джордан', 'Кейси']
            ],
            'dwarf' => [
                'male' => ['Торин', 'Гимли', 'Балин', 'Двалин', 'Оин', 'Глоин', 'Бифур', 'Бофур', 'Бомбур'],
                'female' => ['Дис', 'Фрида', 'Хельда', 'Ингрид', 'Сигрид', 'Тордис', 'Брунхильда'],
                'unisex' => ['Торин', 'Гимли', 'Балин', 'Двалин', 'Оин', 'Глоин', 'Бифур', 'Бофур', 'Бомбур']
            ],
            'halfling' => [
                'male' => ['Фродо', 'Сэм', 'Пиппин', 'Мерри', 'Бильбо', 'Фродо', 'Сэмуайз', 'Перегрин', 'Мериадок'],
                'female' => ['Розалинда', 'Примула', 'Эсмеральда', 'Белладонна', 'Примула', 'Розалинда'],
                'unisex' => ['Фродо', 'Сэм', 'Пиппин', 'Мерри', 'Бильбо', 'Розалинда', 'Примула']
            ],
            'orc' => [
                'male' => ['Грумш', 'Азуг', 'Ургаш', 'Могка', 'Рагнак', 'Крул', 'Гарш', 'Мок'],
                'female' => ['Урга', 'Могка', 'Рагна', 'Крула', 'Гарша', 'Мока', 'Азуга'],
                'unisex' => ['Грумш', 'Азуг', 'Ургаш', 'Могка', 'Рагнак', 'Крул', 'Гарш', 'Мок']
            ],
            'tiefling' => [
                'male' => ['Мальфеас', 'Азмо', 'Крул', 'Демон', 'Инферно', 'Хеллфайр', 'Блейз', 'Эмбер'],
                'female' => ['Лилит', 'Малис', 'Нокс', 'Тени', 'Тьма', 'Звезда', 'Луна', 'Солнце'],
                'unisex' => ['Мальфеас', 'Азмо', 'Крул', 'Демон', 'Инферно', 'Хеллфайр', 'Блейз', 'Эмбер']
            ],
            'dragonborn' => [
                'male' => ['Дракс', 'Рекс', 'Торн', 'Клау', 'Зефир', 'Блейз', 'Эмбер', 'Кримсон'],
                'female' => ['Драксия', 'Рексия', 'Торния', 'Клаудия', 'Зефира', 'Блейза', 'Эмбер', 'Кримсона'],
                'unisex' => ['Дракс', 'Рекс', 'Торн', 'Клау', 'Зефир', 'Блейз', 'Эмбер', 'Кримсон']
            ],
            'gnome' => [
                'male' => ['Гимли', 'Балин', 'Двалин', 'Оин', 'Глоин', 'Бифур', 'Бофур', 'Бомбур', 'Торин'],
                'female' => ['Дис', 'Фрида', 'Хельда', 'Ингрид', 'Сигрид', 'Тордис', 'Брунхильда', 'Гимли'],
                'unisex' => ['Гимли', 'Балин', 'Двалин', 'Оин', 'Глоин', 'Бифур', 'Бофур', 'Бомбур', 'Торин']
            ],
            'half-elf' => [
                'male' => ['Арагорн', 'Леголас', 'Элронд', 'Глорфиндель', 'Эрестор', 'Келеборн', 'Халдир'],
                'female' => ['Арвен', 'Галадриэль', 'Нимродель', 'Идриль', 'Аэрин', 'Лютиэн', 'Мелиан'],
                'unisex' => ['Аэлис', 'Киран', 'Рейвен', 'Скай', 'Тейлор', 'Морган', 'Алексис', 'Джордан', 'Кейси']
            ],
            'half-orc' => [
                'male' => ['Грумш', 'Азуг', 'Ургаш', 'Могка', 'Рагнак', 'Крул', 'Гарш', 'Мок', 'Торин'],
                'female' => ['Урга', 'Могка', 'Рагна', 'Крула', 'Гарша', 'Мока', 'Азуга', 'Дис'],
                'unisex' => ['Грумш', 'Азуг', 'Ургаш', 'Могка', 'Рагнак', 'Крул', 'Гарш', 'Мок', 'Торин']
            ]
        ];
        
        // Выбираем имена для конкретной расы
        if (isset($race_names[$race])) {
            $race_specific_names = $race_names[$race];
            if (isset($race_specific_names[$gender]) && !empty($race_specific_names[$gender])) {
                return $race_specific_names[$gender][array_rand($race_specific_names[$gender])];
            }
        }
        
        // Если ничего не найдено, используем человеческие имена
        $human_names = $race_names['human'];
        if (isset($human_names[$gender]) && !empty($human_names[$gender])) {
            return $human_names[$gender][array_rand($human_names[$gender])];
        }
        
        // Последний fallback
        return $gender === 'male' ? 'Торин' : 'Арвен';
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
    
    /**
     * Очистка текста для безопасного JSON
     */
    private function cleanTextForJson($text) {
        if (!is_string($text)) {
            return "Текст недоступен";
        }
        
        // Удаляем управляющие символы, кроме переносов строк
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Заменяем кавычки на безопасные
        $text = str_replace(['"', '"', '"', '"'], '"', $text);
        
        // Заменяем апострофы на безопасные
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9A", "\xE2\x80\x9B", "\xE2\x80\xB9", "\xE2\x80\xBA", "\xE2\x80\x9C", "\xE2\x80\x9D"], "'", $text);
        
        // Удаляем множественные пробелы и переносы строк
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Обрезаем пробелы в начале и конце
        $text = trim($text);
        
        // Ограничиваем длину текста
        if (strlen($text) > 1000) {
            $text = substr($text, 0, 1000) . '...';
        }
        
        return $text;
    }
    
    /**
     * Получение данных расы с fallback
     */
    private function getRaceDataWithFallback($race) {
        try {
            $race_data = $this->dnd_api_service->getRaceData($race);
            if (!isset($race_data['error'])) {
                return $race_data;
            }
        } catch (Exception $e) {
            logMessage('WARNING', "API error for race {$race}: " . $e->getMessage());
        }
        
        // Fallback данные для рас
        $fallback_races = [
            'human' => [
                'name' => 'Человек',
                'speed' => 30,
                'ability_bonuses' => ['str' => 1, 'dex' => 1, 'con' => 1, 'int' => 1, 'wis' => 1, 'cha' => 1],
                'traits' => ['Версатильность'],
                'languages' => ['Общий', 'Любой один'],
                'subraces' => []
            ],
            'elf' => [
                'name' => 'Эльф',
                'speed' => 30,
                'ability_bonuses' => ['dex' => 2],
                'traits' => ['Темновидение', 'Келебромантия', 'Транс'],
                'languages' => ['Общий', 'Эльфийский'],
                'subraces' => ['Высший эльф', 'Лесной эльф', 'Темный эльф']
            ],
            'dwarf' => [
                'name' => 'Дварф',
                'speed' => 25,
                'ability_bonuses' => ['con' => 2],
                'traits' => ['Темновидение', 'Устойчивость к яду'],
                'languages' => ['Общий', 'Дварфийский'],
                'subraces' => ['Горный дварф', 'Холмовой дварф']
            ]
        ];
        
        return $fallback_races[$race] ?? $fallback_races['human'];
    }
    
    /**
     * Получение данных класса с fallback
     */
    private function getClassDataWithFallback($class) {
        try {
            $class_data = $this->dnd_api_service->getClassData($class);
            if (!isset($class_data['error'])) {
                return $class_data;
            }
        } catch (Exception $e) {
            logMessage('WARNING', "API error for class {$class}: " . $e->getMessage());
        }
        
        // Fallback данные для классов
        $fallback_classes = [
            'fighter' => [
                'name' => 'Воин',
                'hit_die' => 10,
                'proficiencies' => ['Все доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие']
            ],
            'wizard' => [
                'name' => 'Волшебник',
                'hit_die' => 6,
                'proficiencies' => ['Кинжалы', 'Посохи', 'Легкие доспехи']
            ],
            'rogue' => [
                'name' => 'Плут',
                'hit_die' => 8,
                'proficiencies' => ['Легкие доспехи', 'Простое оружие', 'Короткие мечи', 'Длинные мечи', 'Рапиры']
            ]
        ];
        
        return $fallback_classes[$class] ?? $fallback_classes['fighter'];
    }
    
    /**
     * Получение заклинаний с fallback
     */
    private function getSpellsWithFallback($class, $level) {
        try {
            $spells = $this->dnd_api_service->getSpellsForClass($class, $level);
            if (!isset($spells['error'])) {
                return $spells;
            }
        } catch (Exception $e) {
            logMessage('WARNING', "API error for spells: " . $e->getMessage());
        }
        
        // Fallback заклинания
        if (in_array($class, ['wizard', 'sorcerer'])) {
            return ['Магическая стрела', 'Щит', 'Волшебный замок'];
        } elseif (in_array($class, ['cleric', 'paladin'])) {
            return ['Лечение ран', 'Священное пламя', 'Благословение'];
        }
        
        return [];
    }
    
    /**
     * Получение снаряжения с fallback
     */
    private function getEquipmentWithFallback($class) {
        try {
            $equipment = $this->dnd_api_service->getEquipmentForClass($class);
            if (!isset($equipment['error'])) {
                return $equipment;
            }
        } catch (Exception $e) {
            logMessage('WARNING', "API error for equipment: " . $e->getMessage());
        }
        
        // Fallback снаряжение
        $fallback_equipment = [
            'fighter' => ['Кольчуга', 'Длинный меч', 'Щит', 'Рюкзак', 'Спальный мешок'],
            'wizard' => ['Посох', 'Книга заклинаний', 'Компонентный мешочек', 'Рюкзак'],
            'rogue' => ['Кожаные доспехи', 'Короткий меч', 'Лук', 'Рюкзак']
        ];
        
        return $fallback_equipment[$class] ?? $fallback_equipment['fighter'];
    }
    
    /**
     * Получение способностей с fallback
     */
    private function getFeaturesWithFallback($class, $level) {
        try {
            $features = $this->dnd_api_service->getClassFeatures($class, $level);
            if (!isset($features['error'])) {
                return $features;
            }
        } catch (Exception $e) {
            logMessage('WARNING', "API error for features: " . $e->getMessage());
        }
        
        // Fallback способности
        $fallback_features = [
            'fighter' => ['Боевой стиль', 'Second Wind'],
            'wizard' => ['Магическое восстановление', 'Школа магии'],
            'rogue' => ['Скрытность', 'Expertise']
        ];
        
        return $fallback_features[$class] ?? $fallback_features['fighter'];
    }
    
    /**
     * Генерация описания с fallback
     */
    private function generateDescriptionWithFallback($character, $use_ai) {
        if ($use_ai) {
            try {
                $description = $this->ai_service->generateCharacterDescription($character, true);
                if (!isset($description['error'])) {
                    return $this->cleanTextForJson($description);
                }
            } catch (Exception $e) {
                logMessage('WARNING', "AI description generation failed: " . $e->getMessage());
            }
        }
        
        // Fallback описание
        $race = $character['race'];
        $class = $character['class'];
        $gender = $character['gender'];
        
        $descriptions = [
            'Человек' => "{$gender} {$race} с решительным взглядом и уверенной походкой. {$class} с опытом и навыками.",
            'Эльф' => "Грациозный {$race} с острыми чертами лица. {$class} с врожденным чувством магии.",
            'Дварф' => "Крепкий {$race} с густой бородой и сильными руками. {$class} с традициями предков."
        ];
        
        return $descriptions[$race] ?? $descriptions['Человек'];
    }
    
    /**
     * Генерация предыстории с fallback
     */
    private function generateBackgroundWithFallback($character, $use_ai) {
        if ($use_ai) {
            try {
                $background = $this->ai_service->generateCharacterBackground($character, true);
                if (!isset($background['error'])) {
                    return $this->cleanTextForJson($background);
                }
            } catch (Exception $e) {
                logMessage('WARNING', "AI background generation failed: " . $e->getMessage());
            }
        }
        
        // Fallback предыстория
        $occupation = $character['occupation'];
        $race = $character['race'];
        $class = $character['class'];
        
        $backgrounds = [
            'Кузнец' => "Родился в семье кузнецов. Изучал ремесло, но жажда приключений привела к изучению {$class}.",
            'Торговец' => "Путешествовал по миру, торгуя товарами. Научился защищаться и стал {$class}.",
            'Охотник' => "Проводил дни в лесах, выслеживая добычу. Навыки охоты помогли стать {$class}."
        ];
        
        return $backgrounds[$occupation] ?? $backgrounds['Кузнец'];
    }

    /**
     * Генерация предыстории персонажа
     */
    private function generateBackground($character, $use_ai) {
        if ($use_ai) {
            try {
                $background = $this->ai_service->generateCharacterBackground($character, $use_ai);
                if (!isset($background['error'])) {
                    return $background;
                }
            } catch (Exception $e) {
                logMessage('WARNING', "AI недоступен для предыстории: " . $e->getMessage());
            }
        }
        
        // Fallback предыстория с большим разнообразием
        $occupation = $character['occupation'];
        $race = strtolower($character['race']);
        $class = strtolower($character['class']);
        $level = $character['level'];
        
        // Базовые предыстории по профессиям
        $occupation_backgrounds = [
            'Кузнец' => [
                'Родился в семье кузнецов, с детства учился ремеслу. Однажды понял, что его призвание - защищать слабых.',
                'Работал в кузнеце, создавая оружие для воинов. Решил сам испытать то, что создавал.',
                'Мастер-кузнец, чьи клинки известны далеко за пределами родного города. Теперь ищет новые вызовы.'
            ],
            'Торговец' => [
                'Путешествовал по миру, торгуя различными товарами. Встречал множество людей и узнал их слабости.',
                'Успешный торговец, разбогатевший на сделках. Теперь ищет приключений для души.',
                'Торговал в разных городах, изучая обычаи народов. Накопленные знания пригодились в приключениях.'
            ],
            'Охотник' => [
                'Вырос в лесу, научился читать следы и понимать природу. Теперь использует эти навыки в бою.',
                'Охотился на опасных зверей в глухих лесах. Опыт выслеживания пригодился в приключениях.',
                'Лучший охотник в своем крае, знающий каждую тропинку. Решил расширить свои горизонты.'
            ],
            'Фермер' => [
                'Простая жизнь на ферме научила его упорству и выносливости. Когда пришла беда, взял в руки оружие.',
                'Работал на земле, выращивая урожай. Однажды понял, что может вырастить не только растения.',
                'Фермер, чьи земли были разорены. Теперь ищет справедливости и способа восстановить хозяйство.'
            ],
            'Стражник' => [
                'Служил в городской страже, защищая мирных жителей. Опыт пригодился в приключениях.',
                'Был стражником в богатом городе, видел много несправедливости. Решил бороться с ней.',
                'Ветеран городской стражи, знающий все уловки преступников. Теперь применяет знания в бою.'
            ],
            'Солдат' => [
                'Служил в армии, участвовал во многих битвах. Военный опыт пригодился в приключениях.',
                'Был солдатом в пограничных войсках, защищая границы от набегов.',
                'Ветеран многих войн, знающий цену жизни и смерти. Ищет мирного применения своим навыкам.'
            ],
            'Ученый' => [
                'Изучал древние тексты и артефакты. Однажды понял, что лучший способ изучения - личное участие.',
                'Академик, специализирующийся на истории и археологии. Решил сам исследовать древние руины.',
                'Исследователь, изучающий магию и древние цивилизации. Теперь ищет знания в опасных местах.'
            ],
            'Авантюрист' => [
                'Всегда мечтал о приключениях. Оставил родной дом в поисках славы и богатства.',
                'Родился в семье авантюристов, с детства слышал истории о подвигах.',
                'Бродяга, ищущий свою судьбу в мире. Каждое приключение - шаг к цели.'
            ]
        ];
        
        // Выбираем базовую предысторию
        $base_background = 'Вырос в обычной семье, но судьба привела на путь приключений.';
        if (isset($occupation_backgrounds[$occupation])) {
            $backgrounds = $occupation_backgrounds[$occupation];
            $base_background = is_array($backgrounds) ? $backgrounds[array_rand($backgrounds)] : $backgrounds;
        }
        
        // Добавляем детали по расе
        $race_details = [
            'human' => 'Как человек, он быстро адаптируется к любым условиям и учится на своих ошибках.',
            'elf' => 'Будучи эльфом, он обладает долголетием и мудростью, накопленной за долгие годы.',
            'dwarf' => 'Как дварф, он ценит традиции и мастерство, передаваемые из поколения в поколение.',
            'halfling' => 'Будучи полуросликом, он оптимистичен и находчив, даже в самых сложных ситуациях.',
            'orc' => 'Как орк, он обладает природной силой и выносливостью, унаследованными от предков.',
            'tiefling' => 'Будучи тифлингом, он знает, что значит быть изгоем, и использует это в своих целях.'
        ];
        
        $race_detail = $race_details[$race] ?? 'Его происхождение наложило отпечаток на характер и способности.';
        
        // Добавляем детали по классу
        $class_details = [
            'fighter' => 'Воинское мастерство пришло не сразу - годы тренировок и боев закалили его дух.',
            'wizard' => 'Магические способности проявились в детстве, и с тех пор он постоянно изучает новые заклинания.',
            'rogue' => 'Ловкость и скрытность развивались с годами, каждый день приносил новые навыки.',
            'cleric' => 'Божественная сила пришла к нему в момент отчаяния, изменив жизнь навсегда.',
            'ranger' => 'Связь с природой возникла естественно, как и способность выживать в диких местах.',
            'barbarian' => 'Первобытная ярость всегда была в его крови, но теперь он научился её контролировать.',
            'bard' => 'Творческий дар проявился в раннем возрасте, а приключения дают материал для новых песен.',
            'druid' => 'Связь с природой была сильна с детства, и с годами она только крепла.',
            'monk' => 'Духовные практики начались в юности, каждый день приносил новые озарения.',
            'paladin' => 'Святость пришла к нему в момент испытания, определив жизненный путь.',
            'sorcerer' => 'Магическая кровь проявилась неожиданно, и с тех пор он учится контролировать силу.',
            'warlock' => 'Пакт с потусторонним существом был заключен в обмен на знания и силу.',
            'artificer' => 'Инженерный талант проявился в детстве, а магия пришла позже, объединившись с мастерством.'
        ];
        
        $class_detail = $class_details[$class] ?? 'Его навыки развивались годами упорных тренировок.';
        
        // Добавляем детали по уровню
        $level_details = [
            1 => 'Только начинает свой путь, но уже показывает потенциал.',
            2 => 'Первые успехи окрылили его, показав правильность выбранного пути.',
            3 => 'Несколько подвигов за плечами дают уверенность в своих силах.',
            4 => 'Опыт в боях делает его более осторожным и расчетливым.',
            5 => 'Репутация растет, а вместе с ней и ответственность.',
            6 => 'Известность приносит как друзей, так и врагов.',
            7 => 'Легенды о его подвигах распространяются по миру.',
            8 => 'Мастерство достигло высокого уровня, но есть куда расти.',
            9 => 'Слава героя предшествует ему во всех землях.',
            10 => 'Живая легенда, чьи подвиги вдохновляют других.',
            11 => 'Эпический герой, чье имя знают во всех уголках мира.',
            12 => 'Бессмертная легенда, чьи дела будут помнить веками.',
            13 => 'Мифический персонаж, чья сила сравнима с богами.',
            14 => 'Божественный герой, стоящий на пороге бессмертия.',
            15 => 'Полубог, чья сила превосходит понимание смертных.',
            16 => 'Почти бог, чье влияние распространяется на весь мир.',
            17 => 'Божественный воин, защищающий смертных от угроз.',
            18 => 'Бессмертный герой, чья слава никогда не померкнет.',
            19 => 'Божественная сущность, стоящая выше смертных.',
            20 => 'Бог среди смертных, достигший вершины могущества.'
        ];
        
        $level_detail = $level_details[$level] ?? 'Его опыт растет с каждым днем.';
        
        return $base_background . ' ' . $race_detail . ' ' . $class_detail . ' ' . $level_detail;
    }
    
    /**
     * Генерация описания персонажа
     */
    private function generateDescription($character, $use_ai) {
        if ($use_ai) {
            try {
                $description = $this->ai_service->generateCharacterDescription($character, $use_ai);
                if (!isset($description['error'])) {
                    return $description;
                }
            } catch (Exception $e) {
                logMessage('WARNING', "AI недоступен для описания: " . $e->getMessage());
            }
        }
        
        // Fallback описание с большим разнообразием
        $race = strtolower($character['race']);
        $class = strtolower($character['class']);
        $gender = strtolower($character['gender']);
        $level = $character['level'];
        
        // Базовые описания по расам
        $race_descriptions = [
            'human' => [
                'male' => [
                    'Высокий, крепко сложенный человек с решительным взглядом. Его руки покрыты мозолями от долгих лет тренировок.',
                    'Среднего роста мужчина с проницательными глазами и уверенной походкой. В его движениях чувствуется опыт.',
                    'Крепкий человек с густыми волосами и бородой. Его взгляд говорит о непоколебимой решимости.'
                ],
                'female' => [
                    'Стройная женщина с проницательными глазами. Её движения грациозны, но в них чувствуется скрытая сила.',
                    'Высокая женщина с длинными волосами и уверенной осанкой. Её взгляд излучает мудрость и опыт.',
                    'Крепко сложенная женщина с короткими волосами и решительным выражением лица.'
                ]
            ],
            'elf' => [
                'male' => [
                    'Высокий эльф с острыми чертами лица и длинными светлыми волосами. Его глаза излучают древнюю мудрость.',
                    'Элегантный эльф с серебристыми волосами и изящными движениями. В его взгляде читается спокойствие.',
                    'Стройный эльф с темными волосами и проницательными глазами. Его походка легка и грациозна.'
                ],
                'female' => [
                    'Элегантная эльфийка с серебристыми волосами и изящной походкой. Её красота вне времени.',
                    'Высокая эльфийка с золотистыми волосами и мудрым взглядом. Её движения полны грации.',
                    'Стройная эльфийка с темными волосами и острыми чертами лица. Её взгляд пронзителен.'
                ]
            ],
            'dwarf' => [
                'male' => [
                    'Крепкий дварф с густой бородой и мускулистыми руками. Его взгляд говорит о непоколебимой решимости.',
                    'Низкорослый дварф с длинными волосами, заплетенными в косы. Его руки покрыты мозолями от работы.',
                    'Мускулистый дварф с короткой бородой и проницательными глазами. В его взгляде читается опыт.'
                ],
                'female' => [
                    'Крепкая дварфийка с заплетенными в косы волосами. Её сила скрыта за внешней суровостью.',
                    'Низкорослая дварфийка с густыми волосами и решительным взглядом. Её руки говорят о мастерстве.',
                    'Мускулистая дварфийка с короткими волосами и проницательными глазами.'
                ]
            ],
            'halfling' => [
                'male' => [
                    'Маленький полурослик с кудрявыми волосами и добродушным выражением лица. Его глаза полны любопытства.',
                    'Низкорослый полурослик с аккуратной бородкой и веселым взглядом. В его движениях чувствуется ловкость.',
                    'Крепко сложенный полурослик с короткими волосами и решительным выражением лица.'
                ],
                'female' => [
                    'Маленькая полуросличка с длинными кудрявыми волосами и добрыми глазами. Её улыбка заразительна.',
                    'Низкорослая полуросличка с аккуратными косами и веселым взглядом. Её движения грациозны.',
                    'Крепко сложенная полуросличка с короткими волосами и решительным выражением лица.'
                ]
            ],
            'orc' => [
                'male' => [
                    'Мощный орк с зеленой кожей и клыками. Его взгляд говорит о свирепости и силе.',
                    'Крепко сложенный орк с темной кожей и густыми волосами. В его движениях чувствуется мощь.',
                    'Высокий орк с мускулистым телом и решительным выражением лица.'
                ],
                'female' => [
                    'Мощная орчиха с зеленой кожей и острыми чертами лица. Её взгляд излучает силу.',
                    'Крепко сложенная орчиха с темной кожей и длинными волосами. В её движениях чувствуется мощь.',
                    'Высокая орчиха с мускулистым телом и решительным выражением лица.'
                ]
            ],
            'tiefling' => [
                'male' => [
                    'Темнокожий тифлинг с рогами и хвостом. Его глаза светятся адским огнем.',
                    'Стройный тифлинг с острыми чертами лица и длинными волосами. В его взгляде читается тайна.',
                    'Крепко сложенный тифлинг с рогами и проницательными глазами.'
                ],
                'female' => [
                    'Темнокожая тифлинка с рогами и хвостом. Её глаза светятся адским огнем.',
                    'Стройная тифлинка с острыми чертами лица и длинными волосами. В её взгляде читается тайна.',
                    'Крепко сложенная тифлинка с рогами и проницательными глазами.'
                ]
            ]
        ];
        
        // Выбираем описание для расы и пола
        $race_desc = $race_descriptions[$race] ?? $race_descriptions['human'];
        $gender_desc = $race_desc[$gender] ?? $race_desc['male'];
        
        // Выбираем случайное описание
        $base_description = is_array($gender_desc) ? $gender_desc[array_rand($gender_desc)] : $gender_desc;
        
        // Добавляем детали по классу
        $class_details = [
            'fighter' => 'Этот воин носит следы множества битв на своем теле и доспехах.',
            'wizard' => 'В его глазах читается жажда знаний, а руки покрыты следами магических экспериментов.',
            'rogue' => 'Его движения тихи и незаметны, а взгляд постоянно изучает окружающее пространство.',
            'cleric' => 'В его взгляде читается божественная мудрость, а осанка говорит о духовной силе.',
            'ranger' => 'Его кожа загорела от долгих путешествий, а глаза привыкли к темноте леса.',
            'barbarian' => 'В его взгляде читается первобытная ярость, а тело покрыто боевыми шрамами.',
            'bard' => 'Его движения грациозны, а в глазах читается творческий огонь и жажда приключений.',
            'druid' => 'Его связь с природой очевидна - в волосах видны листья, а руки покрыты землей.',
            'monk' => 'Его осанка идеальна, а движения точны и выверены годами тренировок.',
            'paladin' => 'В его взгляде читается святость, а доспехи сияют божественным светом.',
            'sorcerer' => 'В его глазах мерцает магическая энергия, а кожа иногда светится изнутри.',
            'warlock' => 'В его взгляде читается тайна, а иногда вокруг него мерцают тени.',
            'artificer' => 'Его руки покрыты следами работы с механизмами, а в глазах читается изобретательность.'
        ];
        
        $class_detail = $class_details[$class] ?? 'Этот авантюрист готов к любым испытаниям.';
        
        // Добавляем детали по уровню
        $level_details = [
            1 => 'Новичок, только начинающий свой путь.',
            2 => 'Уже имеет некоторый опыт в приключениях.',
            3 => 'Опытный авантюрист с несколькими подвигами за плечами.',
            4 => 'Закаленный в боях воин.',
            5 => 'Ветеран многих сражений.',
            6 => 'Известный герой в своих краях.',
            7 => 'Легендарный авантюрист.',
            8 => 'Мастер своего дела.',
            9 => 'Прославленный герой.',
            10 => 'Живая легенда.',
            11 => 'Эпический герой.',
            12 => 'Бессмертная легенда.',
            13 => 'Мифический персонаж.',
            14 => 'Божественный герой.',
            15 => 'Полубог.',
            16 => 'Почти бог.',
            17 => 'Божественный воин.',
            18 => 'Бессмертный герой.',
            19 => 'Божественная сущность.',
            20 => 'Бог среди смертных.'
        ];
        
        $level_detail = $level_details[$level] ?? 'Опытный авантюрист.';
        
        return $base_description . ' ' . $class_detail . ' ' . $level_detail;
    }
}

// Обработка запроса
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $generator = new CharacterGeneratorV3();
        $result = $generator->generateCharacter($_POST);
        
        // Проверяем, что результат можно закодировать в JSON
        $json_result = json_encode($result, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('ERROR', 'JSON encoding failed: ' . json_last_error_msg());
            echo json_encode([
                'success' => false,
                'error' => 'Ошибка формирования ответа: ' . json_last_error_msg()
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo $json_result;
        }
    } catch (Exception $e) {
        logMessage('ERROR', 'Character generation exception: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка генерации: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Метод не поддерживается'
    ], JSON_UNESCAPED_UNICODE);
}
?>
