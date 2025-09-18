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
    private $weapons = [];
    
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
        
        // Загружаем оружие
        $this->loadWeapons();
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
            $subrace = $params['subrace'] ?? null;
            $class = $params['class'] ?? 'fighter';
            $level = (int)($params['level'] ?? 1);
            $alignment = $params['alignment'] ?? 'random';
            $gender = $params['gender'] ?? 'random';
            $background = $params['background'] ?? 'random';
            $ability_method = $params['ability_method'] ?? 'standard_array';
            $use_ai = isset($params['use_ai']) && $params['use_ai'] === 'on';
            $language = $params['language'] ?? $this->language_service->getCurrentLanguage();
            
            // Получаем данные расы из D&D API или библиотек
            logMessage('INFO', "Начинаем получение данных расы: {$race}");
            $race_data = $this->getRaceDataFromApi($race);
            if (isset($race_data['error'])) {
                logMessage('WARNING', "API недоступен для расы: {$race}, используем библиотеки");
                // Используем библиотечные данные как fallback
                $race_library_info = $this->getRaceLibraryInfo($race);
                if (empty($race_library_info)) {
                    return [
                        'success' => false,
                        'error' => 'Раса не найдена',
                        'message' => "Раса '{$race}' не найдена ни в API, ни в библиотеках",
                        'details' => 'Проверьте правильность названия расы'
                    ];
                }
                // Создаем базовые данные расы из библиотеки
                $race_data = $this->createRaceDataFromLibrary($race, $race_library_info);
            }
            logMessage('INFO', "Получены данные расы: " . json_encode($race_data, JSON_UNESCAPED_UNICODE));
            
            // Получаем данные класса из D&D API или создаем базовые
            logMessage('INFO', "Начинаем получение данных класса: {$class}");
            $class_data = $this->getClassDataFromApi($class);
            if (isset($class_data['error'])) {
                logMessage('WARNING', "API недоступен для класса: {$class}, создаем базовые данные");
                // Создаем базовые данные класса
                $class_data = $this->createBasicClassData($class);
            }
            logMessage('INFO', "Получены данные класса: " . json_encode($class_data, JSON_UNESCAPED_UNICODE));
            
            // Получаем дополнительную информацию о расе из библиотек
            $race_library_info = $this->getRaceLibraryInfo($race);
            logMessage('INFO', "Получена дополнительная информация о расе: " . json_encode($race_library_info, JSON_UNESCAPED_UNICODE));
            
            // Генерируем характеристики
            $abilities = $this->generateAbilities($race_data, $level, $ability_method);
            
            // Генерируем заклинания по классу и уровню
            $spells = $this->generateSpellsForClass($class, $level);
            
            // Генерируем полное стартовое снаряжение
            $equipment = $this->generateStartingEquipment($class, $background);
            
            $features = $this->dnd_api_service->getClassFeatures($class, $level);
            if (isset($features['error'])) {
                logMessage('WARNING', "Не удалось получить способности: " . $features['message']);
                $features = [];
            }
            
            // Создаем персонажа
            $character = [
                'name' => $this->generateName($race, $gender),
                'race' => $this->getRaceDisplayName($race, $race_data),
                'subrace' => $subrace ? $this->getSubraceDisplayName($subrace) : null,
                'class' => $this->getClassDisplayName($class, $class_data),
                'level' => $level,
                'alignment' => $this->getAlignmentText($alignment),
                'gender' => $this->getGenderText($gender),
                'background' => $this->getBackgroundText($background),
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
            
            // Генерируем описание и предысторию с AI (всегда включено согласно предпочтениям пользователя)
            logMessage('INFO', 'Начинаем AI генерацию описания и предыстории');
            
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
                logMessage('INFO', 'AI описание успешно сгенерировано');
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
                logMessage('INFO', 'AI предыстория успешно сгенерирована');
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
     * Загрузка оружия из библиотеки механик
     */
    private function loadWeapons() {
        try {
            $mechanics_file = __DIR__ . '/../../data/pdf/dnd_npc_mechanics_context_v2.json';
            if (file_exists($mechanics_file)) {
                $mechanics_data = json_decode(file_get_contents($mechanics_file), true);
                if (isset($mechanics_data['weapons'])) {
                    $this->weapons = $mechanics_data['weapons'];
                    logMessage('INFO', 'Загружено ' . count($this->weapons) . ' видов оружия');
                } else {
                    logMessage('WARNING', 'Структура файла механик некорректна - нет секции weapons');
                }
            } else {
                logMessage('WARNING', 'Файл с механиками не найден: ' . $mechanics_file);
            }
        } catch (Exception $e) {
            logMessage('ERROR', 'Ошибка загрузки оружия: ' . $e->getMessage());
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
        
        $valid_races = [
            'aarakocra', 'aasimar', 'astral elf', 'autognome', 'bugbear', 'chromatic dragonborn', 
            'custom lineage', 'dhampir', 'dragonborn', 'drow', 'duergar', 'dwarf', 'eladrin', 
            'elf', 'firbolg', 'gem dragonborn', 'genasi', 'giff', 'githyanki', 'githzerai', 
            'gnome', 'goblin', 'goliath', 'hadozee', 'half-elf', 'half-orc', 'halfling', 
            'hexblood', 'hobgoblin', 'human', 'kenku', 'kobold', 'lizardfolk', 'metallic dragonborn', 
            'orc', 'owlin', 'plasmoid', 'reborn', 'shadar-kai', 'tabaxi', 'thri-kreen', 
            'tiefling', 'triton', 'yuan-ti pureblood'
        ];
        $race = $params['race'] ?? 'human';
        if (!in_array($race, $valid_races)) {
            throw new Exception('Неверная раса персонажа');
        }
        
        $valid_classes = [
            'artificer', 'barbarian', 'bard', 'cleric', 'druid', 'fighter', 'monk', 
            'paladin', 'ranger', 'rogue', 'sorcerer', 'warlock', 'wizard'
        ];
        $class = $params['class'] ?? 'fighter';
        if (!in_array($class, $valid_classes)) {
            throw new Exception('Неверный класс персонажа');
        }
    }
    
    /**
     * Генерация характеристик
     */
    private function generateAbilities($race_data, $level = 1, $method = 'standard_array') {
        // Получаем ключ расы для определения особенностей
        $race_key = strtolower($race_data['name'] ?? 'human');
        
        // Выбираем метод генерации характеристик
        switch ($method) {
            case 'point_buy':
                $abilities = $this->generatePointBuyAbilities();
                break;
            case 'roll_4d6':
                $abilities = $this->generate4d6Abilities();
                break;
            case 'standard_array':
            default:
                $abilities = $this->generateStandardArrayAbilities();
                break;
        }
        
        // Определяем приоритеты расы для перераспределения характеристик
        $race_priorities = [
            // Основные расы из PHB
            'human' => ['str', 'dex', 'con', 'int', 'wis', 'cha'],
            'elf' => ['dex', 'int', 'wis', 'str', 'con', 'cha'],
            'dwarf' => ['con', 'str', 'wis', 'dex', 'int', 'cha'],
            'halfling' => ['dex', 'con', 'cha', 'str', 'wis', 'int'],
            'gnome' => ['int', 'dex', 'con', 'str', 'wis', 'cha'],
            'half-elf' => ['cha', 'dex', 'con', 'str', 'int', 'wis'],
            'half-orc' => ['str', 'con', 'dex', 'wis', 'int', 'cha'],
            'tiefling' => ['cha', 'int', 'dex', 'con', 'str', 'wis'],
            'dragonborn' => ['str', 'cha', 'con', 'dex', 'wis', 'int'],
            
            // Расы из Volo's Guide
            'aarakocra' => ['dex', 'wis', 'con', 'str', 'int', 'cha'],
            'aasimar' => ['cha', 'wis', 'con', 'str', 'dex', 'int'],
            'bugbear' => ['str', 'dex', 'con', 'int', 'wis', 'cha'],
            'firbolg' => ['wis', 'str', 'con', 'dex', 'int', 'cha'],
            'goblin' => ['dex', 'con', 'int', 'str', 'wis', 'cha'],
            'goliath' => ['str', 'con', 'dex', 'wis', 'int', 'cha'],
            'hobgoblin' => ['int', 'con', 'str', 'dex', 'wis', 'cha'],
            'kenku' => ['dex', 'wis', 'con', 'int', 'str', 'cha'],
            'kobold' => ['dex', 'int', 'con', 'str', 'wis', 'cha'],
            'lizardfolk' => ['con', 'wis', 'str', 'dex', 'int', 'cha'],
            'orc' => ['str', 'con', 'dex', 'wis', 'int', 'cha'],
            'tabaxi' => ['dex', 'cha', 'con', 'wis', 'str', 'int'],
            'triton' => ['cha', 'con', 'str', 'dex', 'wis', 'int'],
            'yuan-ti pureblood' => ['cha', 'int', 'con', 'dex', 'str', 'wis'],
            
            // Расы из Mordenkainen's Tome of Foes
            'drow' => ['dex', 'cha', 'int', 'str', 'con', 'wis'],
            'duergar' => ['con', 'str', 'int', 'dex', 'wis', 'cha'],
            'eladrin' => ['dex', 'cha', 'int', 'str', 'con', 'wis'],
            'genasi' => ['con', 'dex', 'str', 'int', 'wis', 'cha'],
            'githyanki' => ['str', 'int', 'con', 'dex', 'wis', 'cha'],
            'githzerai' => ['int', 'wis', 'con', 'dex', 'str', 'cha'],
            'shadar-kai' => ['dex', 'con', 'int', 'str', 'wis', 'cha'],
            
            // Расы из Tasha's Cauldron
            'custom lineage' => ['str', 'dex', 'con', 'int', 'wis', 'cha'],
            
            // Расы из Spelljammer
            'astral elf' => ['dex', 'int', 'wis', 'str', 'con', 'cha'],
            'autognome' => ['con', 'int', 'dex', 'str', 'wis', 'cha'],
            'giff' => ['str', 'con', 'dex', 'int', 'wis', 'cha'],
            'hadozee' => ['dex', 'str', 'con', 'int', 'wis', 'cha'],
            'plasmoid' => ['con', 'dex', 'str', 'int', 'wis', 'cha'],
            'thri-kreen' => ['dex', 'wis', 'con', 'str', 'int', 'cha'],
            
            // Расы из Fizban's Treasury
            'chromatic dragonborn' => ['str', 'cha', 'con', 'dex', 'wis', 'int'],
            'gem dragonborn' => ['str', 'cha', 'con', 'dex', 'wis', 'int'],
            'metallic dragonborn' => ['str', 'cha', 'con', 'dex', 'wis', 'int'],
            
            // Расы из Van Richten's Guide
            'dhampir' => ['dex', 'con', 'str', 'int', 'wis', 'cha'],
            'hexblood' => ['cha', 'con', 'int', 'dex', 'str', 'wis'],
            'reborn' => ['con', 'str', 'dex', 'int', 'wis', 'cha'],
            
            // Расы из Strixhaven
            'owlin' => ['dex', 'wis', 'con', 'str', 'int', 'cha'],
            
            // Расы из Radiant Citadel
            'hadozee' => ['dex', 'str', 'con', 'int', 'wis', 'cha'],
            
            // Расы из Bigby's Glory
            'giff' => ['str', 'con', 'dex', 'int', 'wis', 'cha'],
        ];
        
        $priorities = $race_priorities[$race_key] ?? ['str', 'dex', 'con', 'int', 'wis', 'cha'];
        
        // Перераспределяем значения согласно приоритетам расы
        // Самое высокое значение (15) идет в первую характеристику приоритета
        $new_abilities = [];
        $sorted_values = [15, 14, 13, 12, 10, 8]; // Уже отсортированы по убыванию
        
        foreach ($priorities as $index => $ability) {
            $new_abilities[$ability] = $sorted_values[$index];
        }
        
        // Применяем расовые бонусы если есть
        if (isset($race_data['ability_bonuses'])) {
            foreach ($race_data['ability_bonuses'] as $ability => $bonus) {
                if (isset($new_abilities[$ability])) {
                    $new_abilities[$ability] += $bonus;
                    $new_abilities[$ability] = min(20, $new_abilities[$ability]);
                }
            }
        }
        
        logMessage('INFO', "Сгенерированы характеристики для расы {$race_key}: " . json_encode($new_abilities, JSON_UNESCAPED_UNICODE));
        
        return $new_abilities;
    }
    
    /**
     * Генерация характеристик методом Standard Array
     */
    private function generateStandardArrayAbilities() {
        $standard_array = [15, 14, 13, 12, 10, 8];
        shuffle($standard_array); // Перемешиваем для случайности
        
        return [
            'str' => $standard_array[0],
            'dex' => $standard_array[1], 
            'con' => $standard_array[2],
            'int' => $standard_array[3],
            'wis' => $standard_array[4],
            'cha' => $standard_array[5]
        ];
    }
    
    /**
     * Генерация характеристик методом Point Buy (27 очков)
     */
    private function generatePointBuyAbilities() {
        // Point Buy costs: 8=0, 9=1, 10=2, 11=3, 12=4, 13=5, 14=7, 15=9
        $point_costs = [8 => 0, 9 => 1, 10 => 2, 11 => 3, 12 => 4, 13 => 5, 14 => 7, 15 => 9];
        $total_points = 27;
        
        // Начинаем с базовых значений 8
        $abilities = [
            'str' => 8,
            'dex' => 8,
            'con' => 8,
            'int' => 8,
            'wis' => 8,
            'cha' => 8
        ];
        
        $points_used = 0;
        
        // Распределяем очки случайным образом
        while ($points_used < $total_points) {
            $ability = array_rand($abilities);
            $current_value = $abilities[$ability];
            
            // Не повышаем выше 15
            if ($current_value >= 15) continue;
            
            $new_value = $current_value + 1;
            $cost = $point_costs[$new_value] - $point_costs[$current_value];
            
            if ($points_used + $cost <= $total_points) {
                $abilities[$ability] = $new_value;
                $points_used += $cost;
            } else {
                break; // Не хватает очков
            }
        }
        
        return $abilities;
    }
    
    /**
     * Генерация характеристик методом 4d6 drop lowest
     */
    private function generate4d6Abilities() {
        return [
            'str' => $this->rollAbilityScore(),
            'dex' => $this->rollAbilityScore(),
            'con' => $this->rollAbilityScore(),
            'int' => $this->rollAbilityScore(),
            'wis' => $this->rollAbilityScore(),
            'cha' => $this->rollAbilityScore()
        ];
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
     * Генерация имени из библиотеки имен
     */
    private function generateName($race, $gender) {
        $race = strtolower($race);
        $gender = strtolower($gender);
        
        // Если пол не определен, выбираем случайно
        if ($gender === 'random') {
            $gender = rand(0, 1) ? 'male' : 'female';
        }
        
        logMessage('INFO', "Генерируем имя для расы '{$race}' и пола '{$gender}'");
        logMessage('INFO', "Загружено рас с именами: " . count($this->race_names));
        
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
            
            // НЕ используем имена другого пола - это неправильно
            logMessage('WARNING', "Нет подходящих имен для пола '{$gender}' в расе '{$race}'");
            return "Имя не найдено";
        }
        
        // Если имена не найдены в библиотеке, возвращаем ошибку
        logMessage('WARNING', "Имена для расы '{$race}' и пола '{$gender}' не найдены в библиотеке");
        return "Имя не найдено";
    }
    
    /**
     * Получение случайной профессии из библиотеки
     */
    private function getRandomOccupation() {
        logMessage('INFO', "Загружено профессий: " . count($this->occupations));
        
        if (!empty($this->occupations) && is_array($this->occupations)) {
            $occupation = $this->occupations[array_rand($this->occupations)];
            
            // Обрабатываем разные форматы данных профессий
            if (is_string($occupation)) {
                $occupation_name = $occupation;
            } elseif (is_array($occupation) && isset($occupation['name_ru'])) {
                $occupation_name = $occupation['name_ru'];
            } elseif (is_array($occupation) && isset($occupation['name'])) {
                $occupation_name = $occupation['name'];
            } else {
                $occupation_name = 'Авантюрист';
            }
            
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
     * Расчет инициативы (модификатор ловкости)
     */
    private function calculateInitiative($dex_score) {
        $modifier = floor(($dex_score - 10) / 2);
        return $modifier >= 0 ? "+{$modifier}" : (string)$modifier;
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
     * Получение основного оружия из библиотеки механик
     */
    private function getMainWeaponFromApi($class_data) {
        $weapon_proficiencies = $class_data['proficiencies']['weapons'] ?? [];
        
        logMessage('INFO', 'Проверяем владения оружием: ' . json_encode($weapon_proficiencies));
        logMessage('INFO', 'Загружено оружия: ' . count($this->weapons));
        
        if (empty($weapon_proficiencies)) {
            logMessage('WARNING', 'У класса нет владений оружием');
            return "Оружие не найдено";
        }
        
        // Используем библиотеку оружия из механик
        if (!empty($this->weapons)) {
            $available_weapons = [];
            
            foreach ($weapon_proficiencies as $proficiency) {
                foreach ($this->weapons as $weapon_name => $weapon_data) {
                    $weapon_type = $weapon_data['type'] ?? '';
                    $weapon_category = $weapon_data['category'] ?? '';
                    
                    // Проверяем соответствие владений и категории оружия
                    if ($proficiency === 'simple weapons' && $weapon_category === 'simple') {
                        $available_weapons[] = $weapon_name;
                    } elseif ($proficiency === 'martial weapons' && $weapon_category === 'martial') {
                        $available_weapons[] = $weapon_name;
                    } elseif (strpos($proficiency, strtolower($weapon_name)) !== false) {
                        $available_weapons[] = $weapon_name;
                    }
                }
            }
            
            if (!empty($available_weapons)) {
                $selected_weapon = $available_weapons[array_rand($available_weapons)];
                logMessage('INFO', "Выбрано оружие из библиотеки: {$selected_weapon}");
                return $selected_weapon;
            }
        }
        
        // Если оружие не найдено в библиотеке, возвращаем ошибку
        logMessage('WARNING', "Оружие для класса '{$class_data['name']}' не найдено в библиотеке механик");
        return "Оружие не найдено";
    }
    
    /**
     * Получение оружия из библиотеки механик (без fallback)
     */
    private function getFallbackWeapon($class_name) {
        // НЕ используем fallback - возвращаем ошибку если оружие не найдено
        logMessage('WARNING', "Оружие для класса '{$class_name}' не найдено в библиотеке");
        return "Оружие не найдено";
    }
    
    /**
     * Генерация полного стартового снаряжения
     */
    private function generateStartingEquipment($class, $background) {
        $equipment = [];
        
        // Добавляем оружие
        $weapon = $this->getMainWeaponFromApi(['name' => $class]);
        if ($weapon && $weapon !== 'Базовое оружие') {
            $equipment['weapons'][] = $weapon;
        }
        
        // Добавляем броню
        $armor = $this->getStartingArmor($class);
        if ($armor) {
            $equipment['armor'][] = $armor;
        }
        
        // Добавляем щит (если доступен)
        $shield = $this->getStartingShield($class);
        if ($shield) {
            $equipment['shields'][] = $shield;
        }
        
        // Добавляем инструменты и снаряжение
        $tools = $this->getStartingTools($class);
        if (!empty($tools)) {
            $equipment['tools'] = $tools;
        }
        
        // Добавляем базовое снаряжение
        $equipment['items'] = $this->getBasicEquipment();
        
        // Добавляем снаряжение от происхождения
        $background_equipment = $this->getBackgroundEquipment($background);
        if (!empty($background_equipment)) {
            $equipment['background_items'] = $background_equipment;
        }
        
        // Добавляем деньги
        $equipment['money'] = $this->getStartingMoney($class);
        
        logMessage('INFO', "Сгенерировано стартовое снаряжение: " . json_encode($equipment, JSON_UNESCAPED_UNICODE));
        
        return $equipment;
    }
    
    /**
     * Получение стартовой брони из библиотеки механик
     */
    private function getStartingArmor($class) {
        // Получаем данные класса из библиотеки механик
        $mechanics_file = __DIR__ . '/../../data/pdf/dnd_npc_mechanics_context_v2.json';
        if (file_exists($mechanics_file)) {
            $mechanics_data = json_decode(file_get_contents($mechanics_file), true);
            $class_mechanics = $mechanics_data['classes'][$class] ?? [];
            
            if (isset($class_mechanics['armor_proficiencies'])) {
                $armor_proficiencies = $class_mechanics['armor_proficiencies'];
                
                // Выбираем подходящую броню на основе владений
                if (in_array('heavy armor', $armor_proficiencies)) {
                    return 'Кольчуга';
                } elseif (in_array('medium armor', $armor_proficiencies)) {
                    return 'Кожаный доспех';
                } elseif (in_array('light armor', $armor_proficiencies)) {
                    return 'Кожаный доспех';
                }
            }
        }
        
        // Если данные не найдены в библиотеке, возвращаем ошибку
        logMessage('WARNING', "Броня для класса '{$class}' не найдена в библиотеке механик");
        return "Броня не найдена";
    }
    
    /**
     * Получение стартового щита из библиотеки механик
     */
    private function getStartingShield($class) {
        // Получаем данные класса из библиотеки механик
        $mechanics_file = __DIR__ . '/../../data/pdf/dnd_npc_mechanics_context_v2.json';
        if (file_exists($mechanics_file)) {
            $mechanics_data = json_decode(file_get_contents($mechanics_file), true);
            $class_mechanics = $mechanics_data['classes'][$class] ?? [];
            
            if (isset($class_mechanics['armor_proficiencies'])) {
                $armor_proficiencies = $class_mechanics['armor_proficiencies'];
                
                // Проверяем владение щитами
                if (in_array('shields', $armor_proficiencies)) {
                    return rand(0, 1) ? 'Щит' : null; // 50% шанс получить щит
                }
            }
        }
        
        return null;
    }
    
    /**
     * Получение стартовых инструментов из библиотеки механик
     */
    private function getStartingTools($class) {
        $tools = [];
        
        // Получаем данные класса из библиотеки механик
        $mechanics_file = __DIR__ . '/../../data/pdf/dnd_npc_mechanics_context_v2.json';
        if (file_exists($mechanics_file)) {
            $mechanics_data = json_decode(file_get_contents($mechanics_file), true);
            $class_mechanics = $mechanics_data['classes'][$class] ?? [];
            
            if (isset($class_mechanics['tool_proficiencies'])) {
                $tool_proficiencies = $class_mechanics['tool_proficiencies'];
                
                // Выбираем один инструмент из доступных владений
                if (!empty($tool_proficiencies)) {
                    $selected_tool = $tool_proficiencies[array_rand($tool_proficiencies)];
                    $tools[] = $selected_tool;
                }
            }
        }
        
        return $tools;
    }
    
    /**
     * Получение базового снаряжения
     */
    private function getBasicEquipment() {
        return [
            'Рюкзак',
            'Веревка (50 футов)',
            'Трутница',
            'Котелок',
            '10 факелов',
            '10 дней пайков',
            'Винные мехи',
            'Спальный мешок',
            'Мешок с 50 штуками мела'
        ];
    }
    
    /**
     * Получение снаряжения от происхождения из библиотеки механик
     */
    private function getBackgroundEquipment($background) {
        // Получаем данные происхождения из библиотеки механик
        $mechanics_file = __DIR__ . '/../../data/pdf/dnd_npc_mechanics_context_v2.json';
        if (file_exists($mechanics_file)) {
            $mechanics_data = json_decode(file_get_contents($mechanics_file), true);
            $background_mechanics = $mechanics_data['backgrounds'][$background] ?? [];
            
            if (isset($background_mechanics['starting_equipment'])) {
                return $background_mechanics['starting_equipment'];
            }
        }
        
        // Если данные не найдены в библиотеке, возвращаем пустой массив
        logMessage('WARNING', "Снаряжение происхождения '{$background}' не найдено в библиотеке механик");
        return [];
    }
    
    /**
     * Получение стартовых денег из библиотеки механик
     */
    private function getStartingMoney($class) {
        // Получаем данные класса из библиотеки механик
        $mechanics_file = __DIR__ . '/../../data/pdf/dnd_npc_mechanics_context_v2.json';
        if (file_exists($mechanics_file)) {
            $mechanics_data = json_decode(file_get_contents($mechanics_file), true);
            $class_mechanics = $mechanics_data['classes'][$class] ?? [];
            
            if (isset($class_mechanics['starting_money'])) {
                $money_data = $class_mechanics['starting_money'];
                
                // Обрабатываем разные форматы данных о деньгах
                if (is_string($money_data)) {
                    return $money_data;
                } elseif (is_array($money_data) && isset($money_data['dice'])) {
                    $dice = $money_data['dice'];
                    $currency = $money_data['currency'] ?? 'золотых';
                    
                    // Простой расчет для d4
                    if (preg_match('/(\d+)d4/', $dice, $matches)) {
                        $num_dice = (int)$matches[1];
                        $total = 0;
                        for ($i = 0; $i < $num_dice; $i++) {
                            $total += rand(1, 4);
                        }
                        return "{$total} {$currency}";
                    }
                }
            }
        }
        
        // Если данные не найдены в библиотеке, возвращаем ошибку
        logMessage('WARNING', "Данные о стартовых деньгах для класса '{$class}' не найдены в библиотеке механик");
        return "Деньги не найдены";
    }
    
    /**
     * Генерация заклинаний для класса и уровня
     */
    private function generateSpellsForClass($class, $level) {
        $spellcasters = ['wizard', 'cleric', 'druid', 'bard', 'sorcerer', 'warlock', 'artificer'];
        $half_spellcasters = ['paladin', 'ranger'];
        
        if (!in_array($class, $spellcasters) && !in_array($class, $half_spellcasters)) {
            return []; // Класс не использует заклинания
        }
        
        $spells = [];
        $spell_slots = $this->getSpellSlots($class, $level);
        
        // Определяем способность заклинаний
        $spellcasting_ability = $this->getSpellcastingAbility($class);
        
        // Получаем заклинания по уровням
        $spells_by_level = $this->getSpellsByLevel($class, $level);
        
        // Выбираем известные заклинания
        $known_spells = $this->selectKnownSpells($spells_by_level, $class, $level);
        
        return [
            'spellcasting_ability' => $spellcasting_ability,
            'spell_slots' => $spell_slots,
            'known_spells' => $known_spells,
            'spells_by_level' => $spells_by_level
        ];
    }
    
    /**
     * Получение способности заклинаний
     */
    private function getSpellcastingAbility($class) {
        $abilities = [
            'wizard' => 'int',
            'cleric' => 'wis',
            'druid' => 'wis',
            'bard' => 'cha',
            'sorcerer' => 'cha',
            'warlock' => 'cha',
            'artificer' => 'int',
            'paladin' => 'cha',
            'ranger' => 'wis'
        ];
        
        return $abilities[$class] ?? 'int';
    }
    
    /**
     * Получение заклинаний по уровням для класса
     */
    private function getSpellsByLevel($class, $level) {
        $spells = [];
        
        // Заклинания 1 уровня
        if ($level >= 1) {
            $spells[1] = $this->getLevel1Spells($class);
        }
        
        // Заклинания 2 уровня
        if ($level >= 3) {
            $spells[2] = $this->getLevel2Spells($class);
        }
        
        // Заклинания 3 уровня
            if ($level >= 5) {
            $spells[3] = $this->getLevel3Spells($class);
        }
        
        // Заклинания 4 уровня
        if ($level >= 7) {
            $spells[4] = $this->getLevel4Spells($class);
        }
        
        // Заклинания 5 уровня
        if ($level >= 9) {
            $spells[5] = $this->getLevel5Spells($class);
        }
        
        return $spells;
    }
    
    /**
     * Заклинания 1 уровня из D&D API
     */
    private function getLevel1Spells($class) {
        // Получаем заклинания из D&D API
        $spells = $this->dnd_api_service->getSpellsForClass($class, 1);
        
        if (isset($spells['error'])) {
            logMessage('WARNING', "Не удалось получить заклинания 1 уровня для класса '{$class}': " . $spells['message']);
            return [];
        }
        
        return $spells;
    }
    
    /**
     * Заклинания 2 уровня из D&D API
     */
    private function getLevel2Spells($class) {
        // Получаем заклинания из D&D API
        $spells = $this->dnd_api_service->getSpellsForClass($class, 2);
        
        if (isset($spells['error'])) {
            logMessage('WARNING', "Не удалось получить заклинания 2 уровня для класса '{$class}': " . $spells['message']);
            return [];
        }
        
        return $spells;
    }
    
    /**
     * Заклинания 3 уровня из D&D API
     */
    private function getLevel3Spells($class) {
        // Получаем заклинания из D&D API
        $spells = $this->dnd_api_service->getSpellsForClass($class, 3);
        
        if (isset($spells['error'])) {
            logMessage('WARNING', "Не удалось получить заклинания 3 уровня для класса '{$class}': " . $spells['message']);
            return [];
        }
        
        return $spells;
    }
    
    /**
     * Заклинания 4 уровня из D&D API
     */
    private function getLevel4Spells($class) {
        // Получаем заклинания из D&D API
        $spells = $this->dnd_api_service->getSpellsForClass($class, 4);
        
        if (isset($spells['error'])) {
            logMessage('WARNING', "Не удалось получить заклинания 4 уровня для класса '{$class}': " . $spells['message']);
            return [];
        }
        
        return $spells;
    }
    
    /**
     * Заклинания 5 уровня из D&D API
     */
    private function getLevel5Spells($class) {
        // Получаем заклинания из D&D API
        $spells = $this->dnd_api_service->getSpellsForClass($class, 5);
        
        if (isset($spells['error'])) {
            logMessage('WARNING', "Не удалось получить заклинания 5 уровня для класса '{$class}': " . $spells['message']);
            return [];
        }
        
        return $spells;
    }
    
    /**
     * Выбор известных заклинаний
     */
    private function selectKnownSpells($spells_by_level, $class, $level) {
        $known_spells = [];
        
        // Определяем количество известных заклинаний по классу
        $spells_known = $this->getSpellsKnown($class, $level);
        
        // Для каждого уровня заклинаний
        foreach ($spells_by_level as $spell_level => $spells) {
            $available_spells = $spells;
            
            // Количество заклинаний для выбора на этом уровне
            $num_to_select = min($spells_known[$spell_level] ?? 0, count($available_spells));
            
            if ($num_to_select > 0 && count($available_spells) > 0) {
                // Выбираем случайные заклинания
                if ($num_to_select >= count($available_spells)) {
                    // Если нужно выбрать все доступные заклинания
                    $selected_indices = range(0, count($available_spells) - 1);
                } else {
                    // Выбираем случайные индексы
                    $selected_indices = array_rand($available_spells, $num_to_select);
                    if (!is_array($selected_indices)) {
                        $selected_indices = [$selected_indices];
                    }
                }
                
                foreach ($selected_indices as $index) {
                    $known_spells[] = [
                        'name' => $available_spells[$index],
                        'level' => $spell_level
                    ];
                }
            }
        }
        
        return $known_spells;
    }
    
    /**
     * Получение количества известных заклинаний из библиотеки механик
     */
    private function getSpellsKnown($class, $level) {
        // Получаем данные класса из библиотеки механик
        $mechanics_file = __DIR__ . '/../../data/pdf/dnd_npc_mechanics_context_v2.json';
        if (file_exists($mechanics_file)) {
            $mechanics_data = json_decode(file_get_contents($mechanics_file), true);
            $class_mechanics = $mechanics_data['classes'][$class] ?? [];
            
            if (isset($class_mechanics['spellcasting']['spells_known'])) {
                $spells_known_data = $class_mechanics['spellcasting']['spells_known'];
                
                // Если данные есть для конкретного уровня
                if (isset($spells_known_data[$level])) {
                    return $spells_known_data[$level];
                }
                
                // Если есть формула или общие данные
                if (isset($spells_known_data['formula'])) {
                    // Здесь можно добавить логику расчета по формуле
                    logMessage('INFO', "Найдена формула для известных заклинаний: " . $spells_known_data['formula']);
                }
            }
        }
        
        // Если данные не найдены в библиотеке, возвращаем пустой массив
        logMessage('WARNING', "Данные о известных заклинаниях для класса '{$class}' уровня {$level} не найдены в библиотеке механик");
        return [];
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
     * Получение текста происхождения
     */
    private function getBackgroundText($background) {
        if ($background === 'random') {
            $backgrounds = [
                'acolyte' => 'Служитель культа',
                'charlatan' => 'Мошенник',
                'criminal' => 'Преступник',
                'entertainer' => 'Артист',
                'folk_hero' => 'Народный герой',
                'guild_artisan' => 'Гильдейский ремесленник',
                'hermit' => 'Отшельник',
                'noble' => 'Дворянин',
                'outlander' => 'Чужеземец',
                'sage' => 'Мудрец',
                'sailor' => 'Моряк',
                'soldier' => 'Солдат',
                'spy' => 'Шпион',
                'urchin' => 'Бродяга'
            ];
            return $backgrounds[array_rand($backgrounds)];
        }
        
        $background_map = [
            'acolyte' => 'Служитель культа',
            'charlatan' => 'Мошенник',
            'criminal' => 'Преступник',
            'entertainer' => 'Артист',
            'folk_hero' => 'Народный герой',
            'guild_artisan' => 'Гильдейский ремесленник',
            'hermit' => 'Отшельник',
            'noble' => 'Дворянин',
            'outlander' => 'Чужеземец',
            'sage' => 'Мудрец',
            'sailor' => 'Моряк',
            'soldier' => 'Солдат',
            'spy' => 'Шпион',
            'urchin' => 'Бродяга'
        ];
        
        return $background_map[$background] ?? 'Случайное';
    }
    
    /**
     * Получение названия подрасы
     */
    private function getSubraceDisplayName($subrace) {
        $subrace_map = [
            // Эльфы
            'high_elf' => 'Высший эльф',
            'wood_elf' => 'Лесной эльф',
            'dark_elf' => 'Темный эльф (Дроу)',
            'eladrin' => 'Эладрин',
            'sea_elf' => 'Морской эльф',
            
            // Дворфы
            'mountain_dwarf' => 'Горный дворф',
            'hill_dwarf' => 'Холмовой дворф',
            'duergar' => 'Дуэргар',
            
            // Полурослики
            'lightfoot_halfling' => 'Легконогий полурослик',
            'stout_halfling' => 'Крепкий полурослик',
            'ghostwise_halfling' => 'Призрачно-мудрый полурослик',
            
            // Гномы
            'forest_gnome' => 'Лесной гном',
            'rock_gnome' => 'Скальный гном',
            'deep_gnome' => 'Глубинный гном',
            
            // Драконорожденные
            'black_dragonborn' => 'Черный драконорожденный',
            'blue_dragonborn' => 'Синий драконорожденный',
            'brass_dragonborn' => 'Латунный драконорожденный',
            'bronze_dragonborn' => 'Бронзовый драконорожденный',
            'copper_dragonborn' => 'Медный драконорожденный',
            'gold_dragonborn' => 'Золотой драконорожденный',
            'green_dragonborn' => 'Зеленый драконорожденный',
            'red_dragonborn' => 'Красный драконорожденный',
            'silver_dragonborn' => 'Серебряный драконорожденный',
            'white_dragonborn' => 'Белый драконорожденный',
            
            // Тифлинги
            'standard_tiefling' => 'Стандартный тифлинг',
            'variant_tiefling' => 'Вариантный тифлинг',
            'feral_tiefling' => 'Дикий тифлинг',
            
            // Генаси
            'air_genasi' => 'Воздушный генаси',
            'earth_genasi' => 'Земной генаси',
            'fire_genasi' => 'Огненный генаси',
            'water_genasi' => 'Водный генаси',
            
            // Гиты
            'standard_githyanki' => 'Стандартный гитиянки',
            'standard_githzerai' => 'Стандартный гитзерэи'
        ];
        
        return $subrace_map[$subrace] ?? ucfirst(str_replace('_', ' ', $subrace));
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
     * Создание базовых данных расы из библиотеки
     */
    private function createRaceDataFromLibrary($race_key, $library_info) {
        return [
            'name' => ucfirst($race_key),
            'size' => 'Medium',
            'speed' => 30,
            'traits' => [
                [
                    'name' => 'Особенности расы',
                    'description' => $library_info['notes_ru'] ?? 'Особенности расы из библиотеки'
                ]
            ],
            'languages' => ['Общий'],
            'subraces' => []
        ];
    }
    
    /**
     * Создание базовых данных класса из библиотеки механик
     */
    private function createBasicClassData($class_key) {
        // Загружаем библиотеку механик
        $mechanics_file = __DIR__ . '/../../data/pdf/dnd_npc_mechanics_context_v2.json';
        $mechanics_data = [];
        
        if (file_exists($mechanics_file)) {
            $mechanics_data = json_decode(file_get_contents($mechanics_file), true);
        }
        
        // Получаем данные класса из библиотеки
        $class_mechanics = $mechanics_data['classes'][$class_key] ?? [];
        
        // Базовые характеристики классов
        $hit_dice = [
            'fighter' => 10, 'paladin' => 10, 'ranger' => 10,
            'barbarian' => 12, 'sorcerer' => 6, 'wizard' => 6,
            'bard' => 8, 'cleric' => 8, 'druid' => 8,
            'monk' => 8, 'rogue' => 8, 'warlock' => 8
        ];
        
        // Определяем основные характеристики на основе механик
        $primary_abilities = [
            'fighter' => 'str', 'paladin' => 'str', 'ranger' => 'dex',
            'barbarian' => 'str', 'sorcerer' => 'cha', 'wizard' => 'int',
            'bard' => 'cha', 'cleric' => 'wis', 'druid' => 'wis',
            'monk' => 'dex', 'rogue' => 'dex', 'warlock' => 'cha'
        ];
        
        // Создаем базовые данные
        $class_data = [
            'name' => ucfirst($class_key),
            'hit_die' => $hit_dice[$class_key] ?? 8,
            'primary_ability' => $primary_abilities[$class_key] ?? 'str',
            'saving_throws' => $class_mechanics['saving_throws'] ?? ['str', 'con'],
            'casting_category' => $class_mechanics['casting_category'] ?? 'none',
            'spellcasting_ability' => $class_mechanics['spellcasting_ability'] ?? null,
            'proficiencies' => [
                'armor' => $this->getClassArmorProficiencies($class_key),
                'weapons' => $this->getClassWeaponProficiencies($class_key),
                'tools' => $this->getClassToolProficiencies($class_key),
                'saving_throws' => $class_mechanics['saving_throws'] ?? ['str', 'con']
            ],
            'features' => $this->getClassFeatures($class_key),
            'fighting_styles' => $class_mechanics['martial']['fighting_styles'] ?? []
        ];
        
        return $class_data;
    }
    
    /**
     * Получение владения доспехами для класса
     */
    private function getClassArmorProficiencies($class_key) {
        $armor_proficiencies = [
            'fighter' => ['light armor', 'medium armor', 'heavy armor', 'shields'],
            'paladin' => ['light armor', 'medium armor', 'heavy armor', 'shields'],
            'ranger' => ['light armor', 'medium armor', 'shields'],
            'barbarian' => ['light armor', 'medium armor', 'shields'],
            'monk' => [],
            'rogue' => ['light armor'],
            'bard' => ['light armor'],
            'cleric' => ['light armor', 'medium armor', 'shields'],
            'druid' => ['light armor', 'medium armor', 'shields'],
            'sorcerer' => [],
            'wizard' => [],
            'warlock' => ['light armor']
        ];
        
        return $armor_proficiencies[$class_key] ?? ['light armor'];
    }
    
    /**
     * Получение владения оружием для класса
     */
    private function getClassWeaponProficiencies($class_key) {
        $weapon_proficiencies = [
            'fighter' => ['simple weapons', 'martial weapons'],
            'paladin' => ['simple weapons', 'martial weapons'],
            'ranger' => ['simple weapons', 'martial weapons'],
            'barbarian' => ['simple weapons', 'martial weapons'],
            'monk' => ['simple weapons', 'shortsword'],
            'rogue' => ['simple weapons', 'hand crossbow', 'longsword', 'rapier', 'shortsword'],
            'bard' => ['simple weapons', 'hand crossbow', 'longsword', 'rapier', 'shortsword'],
            'cleric' => ['simple weapons'],
            'druid' => ['clubs', 'daggers', 'darts', 'javelins', 'maces', 'quarterstaffs', 'scimitars', 'sickles', 'slings', 'spears'],
            'sorcerer' => ['daggers', 'darts', 'slings', 'quarterstaffs', 'light crossbows'],
            'wizard' => ['daggers', 'darts', 'slings', 'quarterstaffs', 'light crossbows'],
            'warlock' => ['simple weapons']
        ];
        
        return $weapon_proficiencies[$class_key] ?? ['simple weapons'];
    }
    
    /**
     * Получение владения инструментами для класса
     */
    private function getClassToolProficiencies($class_key) {
        $tool_proficiencies = [
            'bard' => ['three musical instruments'],
            'rogue' => ['thieves\' tools'],
            'ranger' => ['one type of musical instrument'],
            'druid' => ['herbalism kit'],
            'cleric' => ['one type of musical instrument']
        ];
        
        return $tool_proficiencies[$class_key] ?? [];
    }
    
    /**
     * Получение способностей класса
     */
    private function getClassFeatures($class_key) {
        $features = [
            'fighter' => [
                ['name' => 'Второе дыхание', 'description' => 'Восстановление хитов в бою'],
                ['name' => 'Стиль боя', 'description' => 'Особые техники боя']
            ],
            'paladin' => [
                ['name' => 'Божественный смысл', 'description' => 'Способность чувствовать нежить'],
                ['name' => 'Наложение рук', 'description' => 'Исцеление через божественную силу']
            ],
            'ranger' => [
                ['name' => 'Любимый враг', 'description' => 'Бонус против определенных типов существ'],
                ['name' => 'Естественный исследователь', 'description' => 'Бонусы в дикой природе']
            ],
            'barbarian' => [
                ['name' => 'Ярость', 'description' => 'Боевое безумие с бонусами'],
                ['name' => 'Неукротимость', 'description' => 'Сопротивление урону']
            ],
            'monk' => [
                ['name' => 'Мастерство в рукопашном бою', 'description' => 'Бонусы к атакам без оружия'],
                ['name' => 'Ци', 'description' => 'Мистическая энергия для способностей']
            ],
            'rogue' => [
                ['name' => 'Скрытность', 'description' => 'Бонусы к скрытности и ловкости рук'],
                ['name' => 'Скрытая атака', 'description' => 'Дополнительный урон при преимуществе']
            ],
            'bard' => [
                ['name' => 'Вдохновение барда', 'description' => 'Магические бонусы союзникам'],
                ['name' => 'Заклинания', 'description' => 'Магические способности']
            ],
            'cleric' => [
                ['name' => 'Заклинания', 'description' => 'Божественная магия'],
                ['name' => 'Божественный домен', 'description' => 'Особые способности божества']
            ],
            'druid' => [
                ['name' => 'Заклинания', 'description' => 'Магия природы'],
                ['name' => 'Дикая форма', 'description' => 'Превращение в животных']
            ],
            'sorcerer' => [
                ['name' => 'Заклинания', 'description' => 'Врожденная магия'],
                ['name' => 'Магическое происхождение', 'description' => 'Источник магической силы']
            ],
            'wizard' => [
                ['name' => 'Заклинания', 'description' => 'Изученная магия'],
                ['name' => 'Книга заклинаний', 'description' => 'Коллекция изученных заклинаний']
            ],
            'warlock' => [
                ['name' => 'Заклинания', 'description' => 'Пактовая магия'],
                ['name' => 'Пакт с покровителем', 'description' => 'Договор с могущественным существом']
            ]
        ];
        
        return $features[$class_key] ?? [
            ['name' => 'Базовые способности класса', 'description' => 'Стандартные способности класса']
        ];
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
        if (in_array($class_name, ['wizard', 'cleric', 'druid', 'bard', 'sorcerer', 'artificer'])) {
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
        elseif (in_array($class_name, ['paladin', 'ranger'])) {
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
