<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Services/dnd-api-service.php';
require_once __DIR__ . '/../../app/Services/ai-service.php';
require_once __DIR__ . '/../../app/Services/language-service.php';

class CharacterGeneratorV4 {
    private $dnd_api_service;
    private $ai_service;
    private $language_service;
    private $occupations = [];
    private $race_names = [];
    
    // Переводы будут получаться из внешних API через LanguageService
    
    public function __construct() {
        try {
            error_log("Creating DndApiService...");
            $this->dnd_api_service = new DndApiService();
            error_log("DndApiService created successfully");
            
            error_log("Creating AiService...");
            $this->ai_service = new AiService(); // Используем основной AI сервис
            error_log("AiService created successfully");
            
            error_log("Creating LanguageService...");
            $this->language_service = new LanguageService(); // Добавляем Language Service
            error_log("LanguageService created successfully");
            
            error_log("Loading data...");
            $this->loadData();
            error_log("Data loaded successfully");
        } catch (Exception $e) {
            error_log("Error in CharacterGeneratorV4 constructor: " . $e->getMessage());
            error_log("Constructor error file: " . $e->getFile() . " line: " . $e->getLine());
            throw $e;
        }
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
            $jsonFile = __DIR__ . '/../../data/pdf/d100_unique_traders.json';
            if (file_exists($jsonFile)) {
                $jsonData = json_decode(file_get_contents($jsonFile), true);
                if (isset($jsonData['data']['occupations'])) {
                    $this->occupations = $jsonData['data']['occupations'];
                    logMessage('INFO', 'Загружено ' . count($this->occupations) . ' профессий');
                } else {
                    logMessage('WARNING', 'Структура файла профессий некорректна');
                }
            } else {
                logMessage('WARNING', 'Файл с профессиями не найден: ' . $jsonFile);
            }
        } catch (Exception $e) {
            logMessage('ERROR', 'Ошибка загрузки профессий: ' . $e->getMessage());
        }
    }
    
    /**
     * Загрузка имен из JSON файла
     */
    private function loadRaceNames() {
        try {
            $jsonFile = __DIR__ . '/../../data/pdf/dnd_race_names_ru_v2.json';
            if (file_exists($jsonFile)) {
                $jsonData = json_decode(file_get_contents($jsonFile), true);
                if (isset($jsonData['data'])) {
                    foreach ($jsonData['data'] as $raceData) {
                        $raceKey = strtolower($raceData['race']);
                        $this->race_names[$raceKey] = $raceData;
                    }
                    logMessage('INFO', 'Загружены имена для ' . count($this->race_names) . ' рас');
                } else {
                    logMessage('WARNING', 'Структура файла имен рас некорректна');
                }
            } else {
                logMessage('WARNING', 'Файл с именами рас не найден: ' . $jsonFile);
            }
        } catch (Exception $e) {
            logMessage('ERROR', 'Ошибка загрузки имен рас: ' . $e->getMessage());
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
            $language = $params['language'] ?? $this->language_service->getCurrentLanguage();
            
            // Получаем данные расы из D&D API
            logMessage('INFO', "Начинаем получение данных расы: {$race}");
            $race_data = $this->getRaceDataFromApi($race);
            if (isset($race_data['error'])) {
                logMessage('ERROR', "API недоступен для расы: {$race}");
                logMessage('ERROR', "Детали ошибки расы: " . json_encode($race_data, JSON_UNESCAPED_UNICODE));
                return [
                    'success' => false,
                    'error' => 'API недоступен',
                    'message' => "Не удалось получить данные расы '{$race}' из внешних API",
                    'details' => $race_data['message'] ?? 'D&D API недоступен',
                    'race_error' => $race_data
                ];
            }
            logMessage('INFO', "Получены данные расы: " . json_encode($race_data, JSON_UNESCAPED_UNICODE));
            
            // Получаем данные класса из D&D API
            logMessage('INFO', "Начинаем получение данных класса: {$class}");
            $class_data = $this->getClassDataFromApi($class);
            if (isset($class_data['error'])) {
                logMessage('ERROR', "API недоступен для класса: {$class}");
                logMessage('ERROR', "Детали ошибки класса: " . json_encode($class_data, JSON_UNESCAPED_UNICODE));
                return [
                    'success' => false,
                    'error' => 'API недоступен',
                    'message' => "Не удалось получить данные класса '{$class}' из внешних API",
                    'details' => $class_data['message'] ?? 'D&D API недоступен',
                    'class_error' => $class_data
                ];
            }
            logMessage('INFO', "Получены данные класса: " . json_encode($class_data, JSON_UNESCAPED_UNICODE));
            
            // Получаем дополнительную информацию о расе из библиотек
            $race_library_info = $this->getRaceLibraryInfo($race);
            logMessage('INFO', "Получена дополнительная информация о расе: " . json_encode($race_library_info, JSON_UNESCAPED_UNICODE));
            
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
                'damage' => $this->calculateDamage($class_data, $abilities, $level),
                'main_weapon' => $this->getMainWeaponFromApi($class_data),
                'proficiencies' => $this->processProficiencies($class_data['proficiencies'] ?? []),
                'spells' => $this->processSpells($spells, $class_data, $level),
                'features' => $this->processFeatures($features, $class_data, $level),
                'equipment' => $equipment,
                'saving_throws' => $this->getSavingThrows($class_data, $abilities),
                'race_traits' => $race_data['traits'] ?? [],
                'languages' => $race_data['languages'] ?? ['Общий'],
                'subraces' => $race_data['subraces'] ?? [],
                'race_style' => $race_library_info['style'] ?? '',
                'race_notes' => $race_library_info['notes_ru'] ?? '',
                'race_surnames' => $race_library_info['surnames'] ?? [],
                'race_clans' => $race_library_info['clans'] ?? []
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
            
            // Переводим персонажа на русский язык
            $translated_character = $this->translateCharacter($character, 'ru');
            
            return [
                'success' => true,
                'character' => $translated_character,
                'language' => $language,
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
        
        logMessage('INFO', "Генерируем имя для расы '{$race}' и пола '{$gender}'");
        
        // Пытаемся получить имена из загруженных данных
        if (isset($this->race_names[$race])) {
            $raceData = $this->race_names[$race];
            logMessage('INFO', "Найдены данные для расы '{$race}': " . json_encode(array_keys($raceData), JSON_UNESCAPED_UNICODE));
            
            // Сначала ищем имена для конкретного пола
            if ($gender === 'male' && isset($raceData['male']) && !empty($raceData['male'])) {
                $name = $raceData['male'][array_rand($raceData['male'])];
                logMessage('INFO', "Выбрано мужское имя: {$name}");
                return $name;
            } elseif ($gender === 'female' && isset($raceData['female']) && !empty($raceData['female'])) {
                $name = $raceData['female'][array_rand($raceData['female'])];
                logMessage('INFO', "Выбрано женское имя: {$name}");
                return $name;
            }
            
            // Затем унисекс имена
            if (isset($raceData['unisex']) && !empty($raceData['unisex'])) {
                $name = $raceData['unisex'][array_rand($raceData['unisex'])];
                logMessage('INFO', "Выбрано унисекс имя: {$name}");
                return $name;
            }
            
            // В крайнем случае имена другого пола
            if ($gender === 'male' && isset($raceData['female']) && !empty($raceData['female'])) {
                $name = $raceData['female'][array_rand($raceData['female'])];
                logMessage('INFO', "Выбрано женское имя для мужского персонажа: {$name}");
                return $name;
            } elseif ($gender === 'female' && isset($raceData['male']) && !empty($raceData['male'])) {
                $name = $raceData['male'][array_rand($raceData['male'])];
                logMessage('INFO', "Выбрано мужское имя для женского персонажа: {$name}");
                return $name;
            }
        }
        
        // Если имена не найдены в библиотеке, возвращаем ошибку
        logMessage('WARNING', "Имена для расы '{$race}' и пола '{$gender}' не найдены в библиотеке");
        return "Имя не найдено";
    }
    
    /**
     * Получение случайной профессии
     */
    private function getRandomOccupation() {
        if (!empty($this->occupations) && is_array($this->occupations)) {
            $occupation = $this->occupations[array_rand($this->occupations)];
            $occupation_name = is_string($occupation) ? $occupation : 'Авантюрист';
            logMessage('INFO', "Выбрана профессия: {$occupation_name}");
            return $occupation_name;
        }
        
        // Если профессии не найдены в библиотеке, возвращаем ошибку
        logMessage('WARNING', "Профессии не найдены в библиотеке");
        return "Профессия не найдена";
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
        $proficiencies = $class_data['proficiencies'] ?? [];
        
        // Проверяем владения доспехами
        foreach ($proficiencies as $prof) {
            $prof_lower = strtolower($prof);
            if (strpos($prof_lower, 'heavy armor') !== false || strpos($prof_lower, 'all armor') !== false) {
            $base_ac = 16; // Кольчуга
                break;
            } elseif (strpos($prof_lower, 'medium armor') !== false) {
                $base_ac = 14; // Кожаные доспехи
            } elseif (strpos($prof_lower, 'light armor') !== false) {
            $base_ac = 12; // Кожаные доспехи
            }
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
        $class_name = $class_data['name'] ?? 'unknown';
        
        // Определяем основную характеристику для атаки на основе класса
        $primary_ability = 'str';
        if (in_array(strtolower($class_name), ['rogue', 'ranger', 'monk'])) {
            $primary_ability = 'dex';
        } elseif (in_array(strtolower($class_name), ['wizard', 'artificer'])) {
            $primary_ability = 'int';
        } elseif (in_array(strtolower($class_name), ['cleric', 'druid'])) {
            $primary_ability = 'wis';
        } elseif (in_array(strtolower($class_name), ['bard', 'sorcerer', 'warlock', 'paladin'])) {
            $primary_ability = 'cha';
        }
        
        $ability_modifier = floor(($abilities[$primary_ability] - 10) / 2);
        return $proficiency_bonus + $ability_modifier;
    }
    
    /**
     * Расчет урона
     */
    private function calculateDamage($class_data, $abilities, $level) {
        $class_name = $class_data['name'] ?? 'unknown';
        
        // Определяем основную характеристику для атаки на основе класса
        $primary_ability = 'str';
        if (in_array(strtolower($class_name), ['rogue', 'ranger', 'monk'])) {
            $primary_ability = 'dex';
        } elseif (in_array(strtolower($class_name), ['wizard', 'artificer'])) {
            $primary_ability = 'int';
        } elseif (in_array(strtolower($class_name), ['cleric', 'druid'])) {
            $primary_ability = 'wis';
        } elseif (in_array(strtolower($class_name), ['bard', 'sorcerer', 'warlock', 'paladin'])) {
            $primary_ability = 'cha';
        }
        
        $ability_modifier = floor(($abilities[$primary_ability] - 10) / 2);
        
        // Получаем урон из API данных
        $base_damage = $this->getWeaponDamageFromApi($class_data, $level);
        
        // Форматируем модификатор правильно
        if ($ability_modifier >= 0) {
            return $base_damage . '+' . $ability_modifier;
        } else {
            return $base_damage . $ability_modifier; // Минус уже есть в числе
        }
    }
    
    /**
     * Получение урона оружия из API данных класса
     */
    private function getWeaponDamageFromApi($class_data, $level) {
        // Пытаемся получить снаряжение из API
        $equipment = $this->dnd_api_service->getEquipmentForClass($class_data['name'] ?? 'unknown');
        
        if (isset($equipment['error'])) {
            logMessage('WARNING', "Не удалось получить снаряжение для расчета урона");
            return '1d6'; // Минимальный урон как fallback
        }
        
        // Здесь можно добавить логику анализа снаряжения для определения урона
        // Пока возвращаем базовый урон
        return '1d6';
    }
    
    /**
     * Получение основного оружия из API данных
     */
    private function getMainWeaponFromApi($class_data) {
        // Пытаемся получить снаряжение из API
        $equipment = $this->dnd_api_service->getEquipmentForClass($class_data['name'] ?? 'unknown');
        
        if (isset($equipment['error'])) {
            logMessage('WARNING', "Не удалось получить снаряжение для определения оружия");
            return "Оружие не определено";
        }
        
        // Здесь можно добавить логику анализа снаряжения для определения основного оружия
        // Пока возвращаем базовое оружие
        return "Базовое оружие";
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
        // Используем данные из API
        if (isset($race_data['name'])) {
            return $race_data['name'];
        }
        
        // Если нет данных из API, возвращаем ошибку
        logMessage('WARNING', "Название расы не найдено для: {$race_key}");
        return "Раса не определена";
    }
    
    /**
     * Получение отображаемого названия класса
     */
    private function getClassDisplayName($class_key, $class_data) {
        // Используем данные из API
        if (isset($class_data['name'])) {
            return $class_data['name'];
        }
        
        // Если нет данных из API, возвращаем ошибку
        logMessage('WARNING', "Название класса не найдено для: {$class_key}");
        return "Класс не определён";
    }
    
    /**
     * Обработка владений (переводы будут получаться из LanguageService)
     */
    private function processProficiencies($proficiencies) {
        // Возвращаем владения как есть, переводы будут обрабатываться через LanguageService
        return is_array($proficiencies) ? $proficiencies : [];
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
        if (empty($features) || isset($features['error'])) {
            // Пытаемся получить способности из API
            $class_name = $class_data['name'] ?? 'unknown';
            $api_features = $this->getClassFeaturesFromApi($class_name, $level);
            
            if (!isset($api_features['error'])) {
                return $api_features;
            }
            
            // Если API недоступен, возвращаем пустой массив
            logMessage('WARNING', "Способности класса '{$class_name}' уровня {$level} не найдены в API");
            return [];
        }
        
        return $features;
    }
    
    /**
     * Получение способностей класса из API (без fallback)
     */
    private function getClassFeaturesFromApi($class_name, $level) {
        return $this->dnd_api_service->getClassFeatures($class_name, $level);
    }
    
    /**
     * Получение данных расы из API (без fallback)
     */
    private function getRaceDataFromApi($race) {
        return $this->dnd_api_service->getRaceData($race);
    }
    
    /**
     * Получение дополнительной информации о расе из библиотек
     */
    private function getRaceLibraryInfo($race) {
        $race = strtolower($race);
        
        if (isset($this->race_names[$race])) {
            $raceData = $this->race_names[$race];
            
            return [
                'style' => $raceData['style'] ?? '',
                'notes_ru' => $raceData['notes_ru'] ?? '',
                'surnames' => $raceData['surnames'] ?? [],
                'clans' => $raceData['clans'] ?? [],
                'subraces' => $raceData['subraces'] ?? []
            ];
        }
        
        return [];
    }
    
    /**
     * Получение данных класса из API (без fallback)
     */
    private function getClassDataFromApi($class) {
        return $this->dnd_api_service->getClassData($class);
    }
    
    /**
     * Перевод персонажа на русский язык
     */
    private function translateCharacter($character, $target_language) {
        if ($target_language !== 'ru') {
            return $character; // Переводим только на русский
        }
        
        logMessage('INFO', "Начинаем перевод персонажа на русский язык");
        
        try {
            // Переводим основные поля
            $translated_character = $character;
            
            // Переводим расу и класс
            $translated_character['race'] = $this->language_service->getRaceName($character['race'], $target_language);
            $translated_character['class'] = $this->language_service->getClassName($character['class'], $target_language);
            
            // Переводим описание и предысторию через AI
            if (isset($character['description'])) {
                $translated_description = $this->ai_service->translateCharacterDescription($character['description'], $target_language);
                if ($translated_description && !isset($translated_description['error'])) {
                    $translated_character['description'] = $translated_description;
                } else {
                    $translated_character['translation_error'] = 'Ошибка перевода описания';
                }
            }
            
            if (isset($character['background'])) {
                $translated_background = $this->ai_service->translateCharacterBackground($character['background'], $target_language);
                if ($translated_background && !isset($translated_background['error'])) {
                    $translated_character['background'] = $translated_background;
                } else {
                    $translated_character['translation_error'] = 'Ошибка перевода предыстории';
                }
            }
            
            logMessage('INFO', "Перевод персонажа завершен успешно");
            return $translated_character;
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка перевода персонажа: " . $e->getMessage());
            $character['translation_error'] = 'Ошибка перевода: ' . $e->getMessage();
            return $character;
        }
    }
    
}

// Обработка запроса
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Проверяем доступность всех файлов
        if (!file_exists(__DIR__ . '/../../config/config.php')) {
            throw new Exception("Config file not found");
        }
        if (!file_exists(__DIR__ . '/../../app/Services/dnd-api-service.php')) {
            throw new Exception("DndApiService file not found");
        }
        if (!file_exists(__DIR__ . '/../../app/Services/ai-service.php')) {
            throw new Exception("AiService file not found");
        }
        if (!file_exists(__DIR__ . '/../../app/Services/language-service.php')) {
            throw new Exception("LanguageService file not found");
        }
        
        error_log("All required files exist");
        
        $generator = new CharacterGeneratorV4();
        error_log("CharacterGeneratorV4 created successfully");
        
        $result = $generator->generateCharacter($_POST);
        error_log("Character generation completed");
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Character generation error: " . $e->getMessage());
        error_log("Error file: " . $e->getFile() . " line: " . $e->getLine());
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
    }
} elseif (isset($_SERVER['REQUEST_METHOD'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Только POST запросы поддерживаются'
    ], JSON_UNESCAPED_UNICODE);
}
?>
