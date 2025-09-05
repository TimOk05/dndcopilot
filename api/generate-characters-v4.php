<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/dnd-api-service.php';
require_once __DIR__ . '/ai-service.php';
require_once __DIR__ . '/ai-service-alternative.php';

class CharacterGeneratorV4 {
    private $dnd_api_service;
    private $ai_service;
    private $occupations = [];
    private $race_names = [];
    private $language_service;
    
    public function __construct() {
        $this->dnd_api_service = new DndApiService();
        $this->ai_service = new AiService(); // Используем основной AI сервис
        
        // Инициализируем языковой сервис
        require_once __DIR__ . '/language-service.php';
        $this->language_service = LanguageService::getInstance();
        
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
                logMessage('WARNING', "API недоступен, используем базовые данные для расы: {$race}");
                $race_data = $this->getBasicRaceData($race);
            }
            logMessage('INFO', "Получены данные расы: " . json_encode($race_data, JSON_UNESCAPED_UNICODE));
            
            // Получаем данные класса из D&D API
            logMessage('INFO', "Начинаем получение данных класса: {$class}");
            $class_data = $this->dnd_api_service->getClassData($class);
            if (isset($class_data['error'])) {
                logMessage('WARNING', "API недоступен, используем базовые данные для класса: {$class}");
                $class_data = $this->getBasicClassData($class);
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
                'race' => $this->getRaceDisplayName($race, $race_data),
                'class' => $this->getClassDisplayName($class, $class_data),
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
                'damage' => $this->calculateDamage($this->getClassDisplayName($class, $class_data), $abilities, $level),
                'main_weapon' => $this->getMainWeapon($this->getClassDisplayName($class, $class_data)),
                'proficiencies' => $this->translateProficiencies($class_data['proficiencies'] ?? []),
                'spells' => $this->processSpells($spells, $class_data, $level),
                'features' => $this->processFeatures($features, $class_data, $level),
                'equipment' => $equipment,
                'saving_throws' => $this->getSavingThrows($class_data, $abilities),
                'race_traits' => $race_data['traits'] ?? [],
                'languages' => $race_data['languages'] ?? ['Общий'],
                'subraces' => $race_data['subraces'] ?? []
            ];
            
            // Генерируем описание и предысторию с AI (всегда включено)
            $description = $this->ai_service->generateCharacterDescription($character, true);
            if (isset($description['error'])) {
                logMessage('ERROR', "AI генерация описания не удалась: " . $description['message']);
                // НЕ используем fallback - возвращаем ошибку
                return [
                    'success' => false,
                    'error' => 'AI API недоступен',
                    'message' => $description['message'],
                    'details' => $description['details'] ?? 'Не удалось сгенерировать описание персонажа',
                    'ai_error' => true
                ];
            } else {
                $character['description'] = $this->cleanTextForJson($description);
            }
            
            $background = $this->ai_service->generateCharacterBackground($character, true);
            if (isset($background['error'])) {
                logMessage('ERROR', "AI генерация предыстории не удалась: " . $background['message']);
                // НЕ используем fallback - возвращаем ошибку
                return [
                    'success' => false,
                    'error' => 'AI API недоступен',
                    'message' => $background['message'],
                    'details' => $background['details'] ?? 'Не удалось сгенерировать предысторию персонажа',
                    'ai_error' => true
                ];
            } else {
                $character['background'] = $this->cleanTextForJson($background);
            }
            
            logMessage('INFO', 'Character generated successfully with API data', [
                'race' => $race,
                'class' => $class,
                'level' => $level,
                'api_data_used' => true,
                'ai_used' => true // AI всегда включен
            ]);
            
            return [
                'success' => true,
                'character' => $character,
                'api_info' => [
                    'dnd_api_used' => true,
                    'ai_api_used' => true, // AI всегда включен
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
    private function calculateDamage($class_name, $abilities, $level) {
        $primary_ability = 'str';
        if (in_array($class_name, ['Плут', 'Следопыт', 'Монах'])) {
            $primary_ability = 'dex';
        }
        
        $ability_modifier = floor(($abilities[$primary_ability] - 10) / 2);
        
        // Определяем базовый урон оружия в зависимости от уровня
        $base_damage = $this->getBaseWeaponDamage($class_name, $level);
        
        // Форматируем модификатор правильно
        if ($ability_modifier >= 0) {
            return $base_damage . '+' . $ability_modifier;
        } else {
            return $base_damage . $ability_modifier; // Минус уже есть в числе
        }
    }
    
    /**
     * Получение базового урона оружия по классу и уровню
     */
    private function getBaseWeaponDamage($class_name, $level) {
        // Базовый урон для разных классов
        $base_damage = [
            'Варвар' => '1d12', // Топор
            'Воин' => '1d8',    // Меч
            'Плут' => '1d4',    // Кинжал
            'Монах' => '1d6',   // Кулаки
            'Паладин' => '1d8', // Меч
            'Следопыт' => '1d8', // Лук
            'Волшебник' => '1d6', // Посох
            'Жрец' => '1d8',    // Булава
            'Бард' => '1d8',    // Рапира
            'Друид' => '1d6',   // Посох
            'Чародей' => '1d6', // Посох
            'Колдун' => '1d4',  // Кинжал
            'Артифисер' => '1d8' // Молот
        ];
        
        $damage = $base_damage[$class_name] ?? '1d6';
        
        // Увеличиваем урон с уровнем для воинских классов
        if (in_array($class_name, ['Варвар', 'Воин', 'Паладин', 'Следопыт'])) {
            if ($level >= 5) {
                // Дополнительная атака на 5 уровне
                $damage = '2' . substr($damage, 1);
            }
            if ($level >= 11) {
                // Третья атака для Воина на 11 уровне
                if ($class_name === 'Воин') {
                    $damage = '3' . substr($damage, 1);
                }
            }
            if ($level >= 20) {
                // Четвертая атака для Воина на 20 уровне
                if ($class_name === 'Воин') {
                    $damage = '4' . substr($damage, 1);
                }
            }
        }
        
        return $damage;
    }
    
    /**
     * Получение основного оружия
     */
    private function getMainWeapon($class_name) {
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
        
        return $weapons[$class_name] ?? 'Меч';
    }
    
    /**
     * Получение спасбросков
     */
    private function getSavingThrows($class_data, $abilities) {
        $saving_throws = [];
        $proficiency_bonus = $this->calculateProficiencyBonus(1); // Для 1 уровня
        
        if (isset($class_data['saving_throws'])) {
            foreach ($class_data['saving_throws'] as $ability) {
                // Обрабатываем разные форматы названий характеристик
                $ability_key = strtolower($ability);
                if (isset($abilities[$ability_key])) {
                    $ability_modifier = floor(($abilities[$ability_key] - 10) / 2);
                    $saving_throws[$ability] = $ability_modifier + $proficiency_bonus;
                } else {
                    // Пробуем альтернативные названия
                    $alt_names = [
                        'str' => 'strength',
                        'dex' => 'dexterity', 
                        'con' => 'constitution',
                        'int' => 'intelligence',
                        'wis' => 'wisdom',
                        'cha' => 'charisma'
                    ];
                    
                    foreach ($alt_names as $short => $long) {
                        if (isset($abilities[$short])) {
                            $ability_modifier = floor(($abilities[$short] - 10) / 2);
                            $saving_throws[$ability] = $ability_modifier + $proficiency_bonus;
                            break;
                        } elseif (isset($abilities[$long])) {
                            $ability_modifier = floor(($abilities[$long] - 10) / 2);
                            $saving_throws[$ability] = $ability_modifier + $proficiency_bonus;
                            break;
                        }
                    }
                }
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
    
    /**
     * Получение отображаемого названия расы
     */
    private function getRaceDisplayName($race_key, $race_data) {
        $race_key_lower = strtolower($race_key);
        
        // Используем языковой сервис для перевода
        $translated_race = $this->language_service->translateRace($race_key_lower);
        
        // Если перевод не найден, используем данные из API
        if ($translated_race === $race_key_lower && isset($race_data['name'])) {
            return $race_data['name'];
        }
        
        return $translated_race;
    }
    
    /**
     * Получение отображаемого названия класса
     */
    private function getClassDisplayName($class_key, $class_data) {
        $class_key_lower = strtolower($class_key);
        
        // Используем языковой сервис для перевода
        $translated_class = $this->language_service->translateClass($class_key_lower);
        
        // Если перевод не найден, используем данные из API
        if ($translated_class === $class_key_lower && isset($class_data['name'])) {
            return $class_data['name'];
        }
        
        return $translated_class;
    }
    
    /**
     * Перевод владений на русский язык
     */
    private function translateProficiencies($proficiencies) {
        $translations = [
            'Light Armor' => 'Легкие доспехи',
            'Medium Armor' => 'Средние доспехи',
            'Heavy Armor' => 'Тяжелые доспехи',
            'Shields' => 'Щиты',
            'Simple Weapons' => 'Простое оружие',
            'Martial Weapons' => 'Воинское оружие',
            'Saving Throw: STR' => 'Спасбросок: СИЛ',
            'Saving Throw: DEX' => 'Спасбросок: ЛОВ',
            'Saving Throw: CON' => 'Спасбросок: ТЕЛ',
            'Saving Throw: INT' => 'Спасбросок: ИНТ',
            'Saving Throw: WIS' => 'Спасбросок: МДР',
            'Saving Throw: CHA' => 'Спасбросок: ХАР'
        ];
        
        $translated = [];
        foreach ($proficiencies as $prof) {
            $translated[] = $translations[$prof] ?? $prof;
        }
        
        return $translated;
    }
    
    /**
     * Обработка заклинаний с учетом уровня персонажа
     */
    private function processSpells($spells, $class_data, $level) {
        if (empty($spells) || !isset($class_data['spellcasting']) || !$class_data['spellcasting']) {
            return [];
        }
        
        // Определяем количество заклинаний по уровню
        $spell_slots = $this->getSpellSlots($class_data['name'], $level);
        
        // Фильтруем заклинания по доступным уровням
        $available_spells = [];
        foreach ($spells as $spell) {
            $spell_level = $spell['level'] ?? 0;
            if ($spell_level <= $level) {
                $available_spells[] = $spell;
            }
        }
        
        return [
            'spells' => $available_spells,
            'spell_slots' => $spell_slots,
            'spellcasting_ability' => $class_data['spellcasting_ability'] ?? 'int'
        ];
    }
    
    /**
     * Получение слотов заклинаний по классу и уровню
     */
    private function getSpellSlots($class_name, $level) {
        $spell_slots = [];
        
        // Базовые слоты для полных заклинателей (Волшебник, Жрец, Друид, Бард, Чародей)
        if (in_array($class_name, ['Волшебник', 'Жрец', 'Друид', 'Бард', 'Чародей'])) {
            $spell_slots = [
                1 => [4, 2, 0, 0, 0, 0, 0, 0, 0],
                2 => [4, 3, 0, 0, 0, 0, 0, 0, 0],
                3 => [4, 4, 2, 0, 0, 0, 0, 0, 0],
                4 => [4, 4, 3, 0, 0, 0, 0, 0, 0],
                5 => [4, 4, 3, 2, 0, 0, 0, 0, 0],
                6 => [4, 4, 3, 3, 0, 0, 0, 0, 0],
                7 => [4, 4, 3, 3, 1, 0, 0, 0, 0],
                8 => [4, 4, 3, 3, 2, 0, 0, 0, 0],
                9 => [4, 4, 3, 3, 3, 1, 0, 0, 0],
                10 => [4, 4, 3, 3, 3, 2, 0, 0, 0],
                11 => [4, 4, 3, 3, 3, 2, 1, 0, 0],
                12 => [4, 4, 3, 3, 3, 2, 1, 0, 0],
                13 => [4, 4, 3, 3, 3, 2, 1, 1, 0],
                14 => [4, 4, 3, 3, 3, 2, 1, 1, 0],
                15 => [4, 4, 3, 3, 3, 2, 1, 1, 1],
                16 => [4, 4, 3, 3, 3, 2, 1, 1, 1],
                17 => [4, 4, 3, 3, 3, 2, 1, 1, 1],
                18 => [4, 4, 3, 3, 3, 2, 1, 1, 1],
                19 => [4, 4, 3, 3, 3, 2, 1, 1, 1],
                20 => [4, 4, 3, 3, 3, 2, 1, 1, 1]
            ];
        }
        // Полузаклинатели (Паладин, Следопыт)
        elseif (in_array($class_name, ['Паладин', 'Следопыт'])) {
            $spell_slots = [
                1 => [0, 0, 0, 0, 0, 0, 0, 0, 0],
                2 => [2, 0, 0, 0, 0, 0, 0, 0, 0],
                3 => [3, 0, 0, 0, 0, 0, 0, 0, 0],
                4 => [3, 0, 0, 0, 0, 0, 0, 0, 0],
                5 => [4, 2, 0, 0, 0, 0, 0, 0, 0],
                6 => [4, 2, 0, 0, 0, 0, 0, 0, 0],
                7 => [4, 3, 0, 0, 0, 0, 0, 0, 0],
                8 => [4, 3, 0, 0, 0, 0, 0, 0, 0],
                9 => [4, 3, 2, 0, 0, 0, 0, 0, 0],
                10 => [4, 3, 2, 0, 0, 0, 0, 0, 0],
                11 => [4, 3, 3, 0, 0, 0, 0, 0, 0],
                12 => [4, 3, 3, 0, 0, 0, 0, 0, 0],
                13 => [4, 3, 3, 1, 0, 0, 0, 0, 0],
                14 => [4, 3, 3, 1, 0, 0, 0, 0, 0],
                15 => [4, 3, 3, 2, 0, 0, 0, 0, 0],
                16 => [4, 3, 3, 2, 0, 0, 0, 0, 0],
                17 => [4, 3, 3, 3, 1, 0, 0, 0, 0],
                18 => [4, 3, 3, 3, 1, 0, 0, 0, 0],
                19 => [4, 3, 3, 3, 2, 0, 0, 0, 0],
                20 => [4, 3, 3, 3, 2, 0, 0, 0, 0]
            ];
        }
        // Третьезаклинатели (Бард, Колдун)
        elseif (in_array($class_name, ['Бард', 'Колдун'])) {
            $spell_slots = [
                1 => [2, 0, 0, 0, 0, 0, 0, 0, 0],
                2 => [3, 0, 0, 0, 0, 0, 0, 0, 0],
                3 => [4, 2, 0, 0, 0, 0, 0, 0, 0],
                4 => [4, 3, 0, 0, 0, 0, 0, 0, 0],
                5 => [4, 3, 2, 0, 0, 0, 0, 0, 0],
                6 => [4, 3, 3, 0, 0, 0, 0, 0, 0],
                7 => [4, 3, 3, 1, 0, 0, 0, 0, 0],
                8 => [4, 3, 3, 2, 0, 0, 0, 0, 0],
                9 => [4, 3, 3, 3, 1, 0, 0, 0, 0],
                10 => [4, 3, 3, 3, 2, 0, 0, 0, 0],
                11 => [4, 3, 3, 3, 2, 1, 0, 0, 0],
                12 => [4, 3, 3, 3, 2, 1, 0, 0, 0],
                13 => [4, 3, 3, 3, 2, 1, 1, 0, 0],
                14 => [4, 3, 3, 3, 2, 1, 1, 0, 0],
                15 => [4, 3, 3, 3, 2, 1, 1, 1, 0],
                16 => [4, 3, 3, 3, 2, 1, 1, 1, 0],
                17 => [4, 3, 3, 3, 2, 1, 1, 1, 1],
                18 => [4, 3, 3, 3, 2, 1, 1, 1, 1],
                19 => [4, 3, 3, 3, 2, 1, 1, 1, 1],
                20 => [4, 3, 3, 3, 2, 1, 1, 1, 1]
            ];
        }
        
        return $spell_slots[$level] ?? [0, 0, 0, 0, 0, 0, 0, 0, 0];
    }
    
    /**
     * Обработка способностей класса
     */
    private function processFeatures($features, $class_data, $level) {
        if (empty($features)) {
            // Генерируем базовые способности для класса
            $class_name = $this->getClassDisplayName($class_data['name'], $class_data);
            return $this->generateBasicFeatures($class_name, $level);
        }
        
        return $features;
    }
    
    /**
     * Генерация базовых способностей для класса
     */
    private function generateBasicFeatures($class_name, $level) {
        $features = [];
        
        switch ($class_name) {
            case 'Варвар':
                if ($level >= 1) $features[] = ['name' => 'Ярость', 'description' => 'В бою вы можете впасть в ярость, получая преимущества в атаке и сопротивлении к урону.'];
                if ($level >= 2) $features[] = ['name' => 'Безрассудная атака', 'description' => 'Вы можете атаковать безрассудно, получая преимущество, но давая врагам преимущество против вас.'];
                if ($level >= 3) $features[] = ['name' => 'Путь варвара', 'description' => 'Вы выбираете путь, который формирует природу вашей ярости.'];
                if ($level >= 5) $features[] = ['name' => 'Дополнительная атака', 'description' => 'Вы можете атаковать дважды вместо одного раза, когда используете действие Атака.'];
                if ($level >= 7) $features[] = ['name' => 'Дикий инстинкт', 'description' => 'Вы получаете преимущество к инициативе.'];
                if ($level >= 9) $features[] = ['name' => 'Брутальный критический удар', 'description' => 'Вы можете бросить дополнительную кость урона оружия при критическом ударе.'];
                break;
                
            case 'Воин':
                if ($level >= 1) $features[] = ['name' => 'Боевой стиль', 'description' => 'Вы выбираете стиль боя, который отражает вашу специализацию.'];
                if ($level >= 2) $features[] = ['name' => 'Второе дыхание', 'description' => 'Вы можете использовать второе дыхание, чтобы восстановить здоровье.'];
                if ($level >= 3) $features[] = ['name' => 'Боевой мастер', 'description' => 'Вы выбираете архетип, который отражает вашу специализацию.'];
                if ($level >= 5) $features[] = ['name' => 'Дополнительная атака', 'description' => 'Вы можете атаковать дважды вместо одного раза, когда используете действие Атака.'];
                if ($level >= 7) $features[] = ['name' => 'Боевой инстинкт', 'description' => 'Вы получаете преимущество к инициативе.'];
                if ($level >= 9) $features[] = ['name' => 'Улучшенная критическая атака', 'description' => 'Вы можете бросить дополнительную кость урона оружия при критическом ударе.'];
                break;
                
            case 'Плут':
                if ($level >= 1) $features[] = ['name' => 'Скрытность', 'description' => 'Вы получаете бонус к проверкам Скрытности и Атлетики.'];
                if ($level >= 2) $features[] = ['name' => 'Хитрость', 'description' => 'Вы можете использовать бонусное действие для выполнения определенных действий.'];
                if ($level >= 3) $features[] = ['name' => 'Архетип плута', 'description' => 'Вы выбираете архетип, который отражает вашу специализацию.'];
                if ($level >= 5) $features[] = ['name' => 'Неуловимость', 'description' => 'Вы получаете сопротивление к урону от заклинаний.'];
                if ($level >= 7) $features[] = ['name' => 'Уклонение', 'description' => 'Вы можете использовать реакцию, чтобы уменьшить урон от атаки.'];
                if ($level >= 9) $features[] = ['name' => 'Мастерство', 'features' => 'Вы получаете бонус к проверкам характеристик.'];
                break;
                
            case 'Монах':
                if ($level >= 1) $features[] = ['name' => 'Боевые искусства', 'description' => 'Вы можете использовать боевые искусства для увеличения урона.'];
                if ($level >= 2) $features[] = ['name' => 'Ки', 'description' => 'Вы получаете доступ к ки-способностям.'];
                if ($level >= 3) $features[] = ['name' => 'Путь монаха', 'description' => 'Вы выбираете путь, который формирует ваши способности.'];
                if ($level >= 5) $features[] = ['name' => 'Дополнительная атака', 'description' => 'Вы можете атаковать дважды вместо одного раза, когда используете действие Атака.'];
                if ($level >= 7) $features[] = ['name' => 'Уклонение', 'description' => 'Вы можете использовать реакцию, чтобы уменьшить урон от атаки.'];
                if ($level >= 9) $features[] = ['name' => 'Улучшенное движение', 'description' => 'Вы получаете бонус к скорости.'];
                break;
        }
        
        return $features;
    }
    
    /**
     * Базовые данные расы для fallback
     */
    private function getBasicRaceData($race) {
        $race_key = strtolower($race);
        
        $basic_races = [
            'aarakocra' => [
                'name' => 'Aarakocra',
                'speed' => 25,
                'ability_bonuses' => ['dex' => 2, 'wis' => 1],
                'traits' => ['Flight', 'Talons'],
                'languages' => ['Common', 'Aarakocra'],
                'subraces' => []
            ],
            'human' => [
                'name' => 'Human',
                'speed' => 30,
                'ability_bonuses' => ['str' => 1, 'dex' => 1, 'con' => 1, 'int' => 1, 'wis' => 1, 'cha' => 1],
                'traits' => ['Versatile'],
                'languages' => ['Common'],
                'subraces' => []
            ],
            'elf' => [
                'name' => 'Elf',
                'speed' => 30,
                'ability_bonuses' => ['dex' => 2],
                'traits' => ['Darkvision', 'Keen Senses'],
                'languages' => ['Common', 'Elvish'],
                'subraces' => []
            ],
            'dwarf' => [
                'name' => 'Dwarf',
                'speed' => 25,
                'ability_bonuses' => ['con' => 2],
                'traits' => ['Darkvision', 'Dwarven Resilience'],
                'languages' => ['Common', 'Dwarvish'],
                'subraces' => []
            ]
        ];
        
        return $basic_races[$race_key] ?? $basic_races['human'];
    }
    
    /**
     * Базовые данные класса для fallback
     */
    private function getBasicClassData($class) {
        $class_key = strtolower($class);
        
        $basic_classes = [
            'barbarian' => [
                'name' => 'Barbarian',
                'hit_die' => 12,
                'proficiencies' => ['Light Armor', 'Medium Armor', 'Shields', 'Simple Weapons', 'Martial Weapons'],
                'saving_throws' => ['STR', 'CON'],
                'spellcasting' => false,
                'spellcasting_ability' => null
            ],
            'fighter' => [
                'name' => 'Fighter',
                'hit_die' => 10,
                'proficiencies' => ['All Armor', 'Shields', 'Simple Weapons', 'Martial Weapons'],
                'saving_throws' => ['STR', 'CON'],
                'spellcasting' => false,
                'spellcasting_ability' => null
            ],
            'wizard' => [
                'name' => 'Wizard',
                'hit_die' => 6,
                'proficiencies' => ['Simple Weapons'],
                'saving_throws' => ['INT', 'WIS'],
                'spellcasting' => true,
                'spellcasting_ability' => 'int'
            ],
            'cleric' => [
                'name' => 'Cleric',
                'hit_die' => 8,
                'proficiencies' => ['Light Armor', 'Medium Armor', 'Shields', 'Simple Weapons'],
                'saving_throws' => ['WIS', 'CHA'],
                'spellcasting' => true,
                'spellcasting_ability' => 'wis'
            ]
        ];
        
        return $basic_classes[$class_key] ?? $basic_classes['fighter'];
    }
}

// Обработка запроса
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
} elseif (isset($_SERVER['REQUEST_METHOD'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Только POST запросы поддерживаются'
    ], JSON_UNESCAPED_UNICODE);
}
?>
