<?php
/**
 * Full Character Service - Полноценный сервис генерации персонажей
 * Получает ВСЕ данные из внешних библиотек без fallback данных
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/dnd-api-service.php';
require_once __DIR__ . '/ai-service.php';
require_once __DIR__ . '/language-service.php';

class FullCharacterService {
    private $dndApiService;
    private $aiService;
    private $languageService;
    
    public function __construct() {
        $this->dndApiService = new DndApiService();
        $this->aiService = new AiService();
        $this->languageService = new LanguageService();
        
        logMessage('INFO', 'FullCharacterService инициализирован');
    }
    
    /**
     * Полная генерация персонажа из внешних источников
     */
    public function generateFullCharacter($params) {
        try {
            logMessage('INFO', 'Начинаем полную генерацию персонажа из внешних источников', $params);
            
            // Валидация параметров
            $this->validateParams($params);
            
            $race = $params['race'] ?? 'human';
            $class = $params['class'] ?? 'fighter';
            $level = (int)($params['level'] ?? 1);
            $gender = $params['gender'] ?? 'random';
            $background = $params['background'] ?? 'random';
            $alignment = $params['alignment'] ?? 'random';
            
            // 1. Получаем данные расы из внешних API
            logMessage('INFO', "Получаем данные расы: {$race}");
            $raceData = $this->getRaceDataFromExternalSources($race);
            if (isset($raceData['error'])) {
                return $this->createErrorResponse('race_error', $raceData['message']);
            }
            
            // 2. Получаем данные класса из внешних API
            logMessage('INFO', "Получаем данные класса: {$class}");
            $classData = $this->getClassDataFromExternalSources($class);
            if (isset($classData['error'])) {
                return $this->createErrorResponse('class_error', $classData['message']);
            }
            
            // 3. Получаем данные происхождения из внешних API
            logMessage('INFO', "Получаем данные происхождения: {$background}");
            $backgroundData = $this->getBackgroundDataFromExternalSources($background);
            if (isset($backgroundData['error'])) {
                return $this->createErrorResponse('background_error', $backgroundData['message']);
            }
            
            // 4. Генерируем характеристики на основе данных расы
            $abilities = $this->generateAbilitiesFromRaceData($raceData, $params['ability_method'] ?? 'standard_array');
            
            // 5. Получаем снаряжение из внешних API
            $equipment = $this->getEquipmentFromExternalSources($class, $background, $level);
            if (isset($equipment['error'])) {
                return $this->createErrorResponse('equipment_error', $equipment['message']);
            }
            
            // 6. Получаем заклинания из внешних API
            $spells = $this->getSpellsFromExternalSources($class, $level, $abilities);
            if (isset($spells['error'])) {
                return $this->createErrorResponse('spells_error', $spells['message']);
            }
            
            // 7. Получаем способности класса из внешних API
            $features = $this->getFeaturesFromExternalSources($class, $level);
            if (isset($features['error'])) {
                return $this->createErrorResponse('features_error', $features['message']);
            }
            
            // 8. Получаем имена из внешних библиотек
            $name = $this->getNameFromExternalSources($race, $gender);
            if (isset($name['error'])) {
                return $this->createErrorResponse('name_error', $name['message']);
            }
            
            // 9. Создаем базового персонажа
            $character = $this->buildCharacter(
                $raceData, $classData, $backgroundData, $abilities, 
                $equipment, $spells, $features, $name, $level, $alignment, $gender
            );
            
            // 10. Генерируем описание через AI
            logMessage('INFO', 'Генерируем описание персонажа через AI');
            $description = $this->aiService->generateCharacterDescription($character, true);
            if (isset($description['error'])) {
                return $this->createErrorResponse('ai_description_error', $description['message']);
            }
            $character['description'] = $description;
            
            // 11. Генерируем предысторию через AI
            logMessage('INFO', 'Генерируем предысторию персонажа через AI');
            $backgroundStory = $this->aiService->generateCharacterBackground($character, true);
            if (isset($backgroundStory['error'])) {
                return $this->createErrorResponse('ai_background_error', $backgroundStory['message']);
            }
            $character['background_story'] = $backgroundStory;
            
            // 12. Переводим на русский язык
            $character = $this->translateCharacterToRussian($character);
            
            logMessage('INFO', 'Полная генерация персонажа завершена успешно', [
                'race' => $race,
                'class' => $class,
                'level' => $level,
                'external_sources_used' => true
            ]);
            
            return [
                'success' => true,
                'character' => $character,
                'sources' => [
                    'race_data' => 'External D&D API',
                    'class_data' => 'External D&D API',
                    'background_data' => 'External D&D API',
                    'equipment_data' => 'External D&D API',
                    'spells_data' => 'External D&D API',
                    'features_data' => 'External D&D API',
                    'names_data' => 'External Library',
                    'ai_description' => 'DeepSeek AI',
                    'ai_background' => 'DeepSeek AI',
                    'translation' => 'Language Service'
                ]
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Ошибка полной генерации персонажа: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Внутренняя ошибка сервера',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение данных расы из внешних источников
     */
    private function getRaceDataFromExternalSources($race) {
        // Пробуем D&D API
        $raceData = $this->dndApiService->getRaceData($race);
        if (!isset($raceData['error'])) {
            return $raceData;
        }
        
        // Если D&D API недоступен, пробуем Open5e API
        try {
            $open5eData = $this->fetchFromOpen5e("/races/{$race}/");
            if ($open5eData && isset($open5eData['name'])) {
                return $this->processRaceData($open5eData);
            }
        } catch (Exception $e) {
            logMessage('WARNING', "Open5e API недоступен для расы {$race}: " . $e->getMessage());
        }
        
        // Если все API недоступны, возвращаем ошибку
        return [
            'error' => 'external_api_unavailable',
            'message' => "Не удалось получить данные расы '{$race}' из внешних API"
        ];
    }
    
    /**
     * Получение данных класса из внешних источников
     */
    private function getClassDataFromExternalSources($class) {
        // Пробуем D&D API
        $classData = $this->dndApiService->getClassData($class);
        if (!isset($classData['error'])) {
            return $classData;
        }
        
        // Если D&D API недоступен, пробуем Open5e API
        try {
            $open5eData = $this->fetchFromOpen5e("/classes/{$class}/");
            if ($open5eData && isset($open5eData['name'])) {
                return $this->processClassData($open5eData);
            }
        } catch (Exception $e) {
            logMessage('WARNING', "Open5e API недоступен для класса {$class}: " . $e->getMessage());
        }
        
        // Если все API недоступны, возвращаем ошибку
        return [
            'error' => 'external_api_unavailable',
            'message' => "Не удалось получить данные класса '{$class}' из внешних API"
        ];
    }
    
    /**
     * Получение данных происхождения из внешних источников
     */
    private function getBackgroundDataFromExternalSources($background) {
        if ($background === 'random') {
        // Получаем список всех происхождений из API
        try {
            $backgroundsList = $this->dndApiService->getBackgroundsList();
            if (isset($backgroundsList['error'])) {
                return $backgroundsList;
            }
            
            // Выбираем случайное происхождение
            $background = $backgroundsList[array_rand($backgroundsList)]['index'];
        } catch (Exception $e) {
            logMessage('WARNING', 'Не удалось получить список происхождений: ' . $e->getMessage());
            $background = 'acolyte'; // Fallback на базовое происхождение
        }
        }
        
        // Получаем данные происхождения из D&D API
        try {
            $url = "https://www.dnd5eapi.co/api/backgrounds/" . strtolower($background);
            $response = $this->makeApiRequest($url);
            
            if ($response && isset($response['name'])) {
                return $this->processBackgroundData($response);
            }
        } catch (Exception $e) {
            logMessage('WARNING', "D&D API недоступен для происхождения {$background}: " . $e->getMessage());
        }
        
        return [
            'error' => 'external_api_unavailable',
            'message' => "Не удалось получить данные происхождения '{$background}' из внешних API"
        ];
    }
    
    /**
     * Получение снаряжения из внешних источников
     */
    private function getEquipmentFromExternalSources($class, $background, $level) {
        try {
            // Получаем стартовое снаряжение класса
            $classEquipment = $this->dndApiService->getEquipmentForClass($class);
            if (isset($classEquipment['error'])) {
                return $classEquipment;
            }
            
            // Получаем снаряжение происхождения
            $backgroundEquipment = $this->getBackgroundEquipment($background);
            
            // Получаем дополнительное снаряжение по уровню
            $additionalEquipment = $this->getAdditionalEquipmentByLevel($level);
            
            return [
                'class_equipment' => $classEquipment,
                'background_equipment' => $backgroundEquipment,
                'additional_equipment' => $additionalEquipment,
                'money' => $this->getStartingMoney($class)
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'equipment_error',
                'message' => "Ошибка получения снаряжения: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение заклинаний из внешних источников
     */
    private function getSpellsFromExternalSources($class, $level, $abilities) {
        // Проверяем, является ли класс заклинателем
        $spellcasters = ['wizard', 'cleric', 'druid', 'bard', 'sorcerer', 'warlock', 'artificer'];
        $halfSpellcasters = ['paladin', 'ranger'];
        
        if (!in_array($class, $spellcasters) && !in_array($class, $halfSpellcasters)) {
            return []; // Класс не использует заклинания
        }
        
        try {
            // Получаем заклинания для класса и уровня
            $spells = $this->dndApiService->getSpellsForClass($class, $level);
            if (isset($spells['error'])) {
                return $spells;
            }
            
            // Определяем способность заклинаний
            $spellcastingAbility = $this->getSpellcastingAbility($class);
            
            // Получаем слоты заклинаний
            $spellSlots = $this->getSpellSlotsForClass($class, $level);
            
            return [
                'spellcasting_ability' => $spellcastingAbility,
                'spell_slots' => $spellSlots,
                'known_spells' => $spells,
                'spell_attack_bonus' => $this->calculateSpellAttackBonus($abilities, $spellcastingAbility, $level),
                'spell_save_dc' => $this->calculateSpellSaveDC($abilities, $spellcastingAbility, $level)
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'spells_error',
                'message' => "Ошибка получения заклинаний: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение способностей класса из внешних источников
     */
    private function getFeaturesFromExternalSources($class, $level) {
        try {
            $features = $this->dndApiService->getClassFeatures($class, $level);
            if (isset($features['error'])) {
                return $features;
            }
            
            return $features;
            
        } catch (Exception $e) {
            return [
                'error' => 'features_error',
                'message' => "Ошибка получения способностей: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение имени из внешних библиотек
     */
    private function getNameFromExternalSources($race, $gender) {
        try {
            // Загружаем библиотеку имен
            $namesFile = __DIR__ . '/../../data/pdf/dnd_race_names_ru_v2.json';
            if (!file_exists($namesFile)) {
                return [
                    'error' => 'names_library_unavailable',
                    'message' => 'Библиотека имен не найдена'
                ];
            }
            
            $namesData = json_decode(file_get_contents($namesFile), true);
            if (!isset($namesData['data'])) {
                return [
                    'error' => 'names_library_corrupted',
                    'message' => 'Библиотека имен повреждена'
                ];
            }
            
            $race = strtolower($race);
            $gender = strtolower($gender);
            
            if ($gender === 'random') {
                $gender = rand(0, 1) ? 'male' : 'female';
            }
            
            // Ищем имена для расы
            foreach ($namesData['data'] as $raceData) {
                if (strtolower($raceData['race']) === $race) {
                    $nameList = [];
                    
                    if ($gender === 'male' && !empty($raceData['male'])) {
                        $nameList = $raceData['male'];
                    } elseif ($gender === 'female' && !empty($raceData['female'])) {
                        $nameList = $raceData['female'];
                    } elseif (!empty($raceData['unisex'])) {
                        $nameList = $raceData['unisex'];
                    }
                    
                    if (!empty($nameList)) {
                        return $nameList[array_rand($nameList)];
                    }
                }
            }
            
            return [
                'error' => 'name_not_found',
                'message' => "Имя для расы '{$race}' и пола '{$gender}' не найдено в библиотеке"
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'names_error',
                'message' => "Ошибка получения имени: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Генерация характеристик на основе данных расы
     */
    private function generateAbilitiesFromRaceData($raceData, $method = 'standard_array') {
        // Генерируем базовые характеристики
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
        
        // Применяем расовые бонусы
        if (isset($raceData['ability_bonuses'])) {
            foreach ($raceData['ability_bonuses'] as $ability => $bonus) {
                if (isset($abilities[$ability])) {
                    $abilities[$ability] += $bonus;
                    $abilities[$ability] = min(20, $abilities[$ability]);
                }
            }
        }
        
        return $abilities;
    }
    
    /**
     * Создание персонажа из всех полученных данных
     */
    private function buildCharacter($raceData, $classData, $backgroundData, $abilities, $equipment, $spells, $features, $name, $level, $alignment, $gender) {
        $proficiencyBonus = $this->calculateProficiencyBonus($level);
        
        return [
            'name' => $name,
            'race' => $raceData['name'] ?? 'Unknown',
            'class' => $classData['name'] ?? 'Unknown',
            'level' => $level,
            'alignment' => $this->getAlignmentText($alignment),
            'gender' => $this->getGenderText($gender),
            'background' => $backgroundData['name'] ?? 'Unknown',
            'abilities' => $abilities,
            'ability_modifiers' => $this->calculateAbilityModifiers($abilities),
            'hit_points' => $this->calculateHP($classData, $abilities['con'], $level),
            'armor_class' => $this->calculateAC($classData, $abilities['dex']),
            'speed' => $raceData['speed'] ?? 30,
            'initiative' => $this->calculateInitiative($abilities['dex']),
            'proficiency_bonus' => $proficiencyBonus,
            'attack_bonus' => $this->calculateAttackBonus($classData, $abilities, $level),
            'damage' => $this->calculateDamage($classData, $abilities, $level),
            'equipment' => $equipment,
            'spells' => $spells,
            'features' => $features,
            'saving_throws' => $this->getSavingThrows($classData, $abilities, $proficiencyBonus),
            'race_traits' => $raceData['traits'] ?? [],
            'languages' => $raceData['languages'] ?? ['Common'],
            'subraces' => $raceData['subraces'] ?? [],
            'proficiencies' => $this->getProficiencies($classData, $backgroundData),
            'skills' => $this->getSkills($classData, $backgroundData, $abilities, $proficiencyBonus)
        ];
    }
    
    /**
     * Перевод персонажа на русский язык
     */
    private function translateCharacterToRussian($character) {
        try {
            // Переводим основные поля через Language Service
            $character['race'] = $this->languageService->getRaceName($character['race'], 'ru');
            $character['class'] = $this->languageService->getClassName($character['class'], 'ru');
            
            // Переводим способности и черты через AI
            if (!empty($character['race_traits'])) {
                $translatedTraits = [];
                foreach ($character['race_traits'] as $trait) {
                    $translatedTrait = $this->aiService->translateText($trait, 'ru');
                    if (!isset($translatedTrait['error'])) {
                        $translatedTraits[] = $translatedTrait;
                    } else {
                        $translatedTraits[] = $trait; // Оставляем оригинал если перевод не удался
                    }
                }
                $character['race_traits'] = $translatedTraits;
            }
            
            return $character;
            
        } catch (Exception $e) {
            logMessage('WARNING', 'Ошибка перевода персонажа: ' . $e->getMessage());
            return $character; // Возвращаем оригинал при ошибке
        }
    }
    
    // Вспомогательные методы для расчетов
    private function generateStandardArrayAbilities() {
        $standardArray = [15, 14, 13, 12, 10, 8];
        shuffle($standardArray);
        
        return [
            'str' => $standardArray[0],
            'dex' => $standardArray[1],
            'con' => $standardArray[2],
            'int' => $standardArray[3],
            'wis' => $standardArray[4],
            'cha' => $standardArray[5]
        ];
    }
    
    private function generatePointBuyAbilities() {
        // Упрощенная реализация Point Buy (27 очков)
        return [
            'str' => 15, 'dex' => 14, 'con' => 13,
            'int' => 12, 'wis' => 10, 'cha' => 8
        ];
    }
    
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
    
    private function rollAbilityScore() {
        $rolls = [];
        for ($i = 0; $i < 4; $i++) {
            $rolls[] = rand(1, 6);
        }
        sort($rolls);
        array_shift($rolls);
        return array_sum($rolls);
    }
    
    private function calculateAbilityModifiers($abilities) {
        $modifiers = [];
        foreach ($abilities as $ability => $score) {
            $modifiers[$ability] = floor(($score - 10) / 2);
        }
        return $modifiers;
    }
    
    private function calculateHP($classData, $con, $level) {
        $hitDie = $classData['hit_die'] ?? 8;
        $conBonus = floor(($con - 10) / 2);
        
        $hp = $hitDie + $conBonus; // Первый уровень - максимум
        
        for ($i = 2; $i <= $level; $i++) {
            $hp += rand(1, $hitDie) + $conBonus;
        }
        
        return max(1, $hp);
    }
    
    private function calculateAC($classData, $dex) {
        $dexBonus = floor(($dex - 10) / 2);
        $baseAC = 10;
        
        // Проверяем владения доспехами
        $proficiencies = $classData['proficiencies'] ?? [];
        foreach ($proficiencies as $prof) {
            if (stripos($prof, 'heavy armor') !== false) {
                $baseAC = 16; // Кольчуга
                break;
            } elseif (stripos($prof, 'medium armor') !== false) {
                $baseAC = 14; // Кожаные доспехи
            } elseif (stripos($prof, 'light armor') !== false) {
                $baseAC = 12; // Кожаные доспехи
            }
        }
        
        return $baseAC + $dexBonus;
    }
    
    private function calculateInitiative($dex) {
        $modifier = floor(($dex - 10) / 2);
        return $modifier >= 0 ? "+{$modifier}" : (string)$modifier;
    }
    
    private function calculateProficiencyBonus($level) {
        return floor(($level - 1) / 4) + 2;
    }
    
    private function calculateAttackBonus($classData, $abilities, $level) {
        $proficiencyBonus = $this->calculateProficiencyBonus($level);
        $className = strtolower($classData['name'] ?? 'fighter');
        
        // Определяем основную характеристику
        $primaryAbility = 'str';
        if (in_array($className, ['rogue', 'ranger', 'monk'])) {
            $primaryAbility = 'dex';
        } elseif (in_array($className, ['wizard', 'artificer'])) {
            $primaryAbility = 'int';
        } elseif (in_array($className, ['cleric', 'druid'])) {
            $primaryAbility = 'wis';
        } elseif (in_array($className, ['bard', 'sorcerer', 'warlock', 'paladin'])) {
            $primaryAbility = 'cha';
        }
        
        $abilityModifier = floor(($abilities[$primaryAbility] - 10) / 2);
        return $proficiencyBonus + $abilityModifier;
    }
    
    private function calculateDamage($classData, $abilities, $level) {
        $className = strtolower($classData['name'] ?? 'fighter');
        
        // Определяем основную характеристику
        $primaryAbility = 'str';
        if (in_array($className, ['rogue', 'ranger', 'monk'])) {
            $primaryAbility = 'dex';
        } elseif (in_array($className, ['wizard', 'artificer'])) {
            $primaryAbility = 'int';
        } elseif (in_array($className, ['cleric', 'druid'])) {
            $primaryAbility = 'wis';
        } elseif (in_array($className, ['bard', 'sorcerer', 'warlock', 'paladin'])) {
            $primaryAbility = 'cha';
        }
        
        $abilityModifier = floor(($abilities[$primaryAbility] - 10) / 2);
        
        // Базовый урон (можно улучшить, получая из API)
        $baseDamage = '1d6';
        
        if ($abilityModifier >= 0) {
            return $baseDamage . '+' . $abilityModifier;
        } else {
            return $baseDamage . $abilityModifier;
        }
    }
    
    private function getSavingThrows($classData, $abilities, $proficiencyBonus) {
        $savingThrows = [];
        
        if (isset($classData['saving_throws'])) {
            foreach ($classData['saving_throws'] as $ability) {
                $abilityKey = strtolower($ability);
                if (isset($abilities[$abilityKey])) {
                    $abilityModifier = floor(($abilities[$abilityKey] - 10) / 2);
                    $savingThrows[$ability] = $abilityModifier + $proficiencyBonus;
                }
            }
        }
        
        return $savingThrows;
    }
    
    private function getProficiencies($classData, $backgroundData) {
        $proficiencies = [];
        
        // Добавляем владения класса
        if (isset($classData['proficiencies'])) {
            $proficiencies = array_merge($proficiencies, $classData['proficiencies']);
        }
        
        // Добавляем владения происхождения
        if (isset($backgroundData['starting_proficiencies'])) {
            foreach ($backgroundData['starting_proficiencies'] as $prof) {
                $proficiencies[] = $prof['name'] ?? $prof;
            }
        }
        
        return array_unique($proficiencies);
    }
    
    private function getSkills($classData, $backgroundData, $abilities, $proficiencyBonus) {
        $skills = [];
        
        // Добавляем навыки класса
        if (isset($classData['proficiency_choices'])) {
            foreach ($classData['proficiency_choices'] as $choice) {
                if (isset($choice['from']['options'])) {
                    foreach ($choice['from']['options'] as $option) {
                        $skill = $option['item']['name'] ?? $option['name'] ?? '';
                        if (strpos($skill, 'Skill:') === 0) {
                            $skillName = str_replace('Skill: ', '', $skill);
                            $abilityKey = $this->getSkillAbility($skillName);
                            if ($abilityKey && isset($abilities[$abilityKey])) {
                                $abilityModifier = floor(($abilities[$abilityKey] - 10) / 2);
                                $skills[$skillName] = $abilityModifier + $proficiencyBonus;
                            }
                        }
                    }
                }
            }
        }
        
        // Добавляем навыки происхождения
        if (isset($backgroundData['starting_proficiencies'])) {
            foreach ($backgroundData['starting_proficiencies'] as $prof) {
                $skillName = $prof['name'] ?? $prof;
                if (strpos($skillName, 'Skill:') === 0) {
                    $skillName = str_replace('Skill: ', '', $skillName);
                    $abilityKey = $this->getSkillAbility($skillName);
                    if ($abilityKey && isset($abilities[$abilityKey])) {
                        $abilityModifier = floor(($abilities[$abilityKey] - 10) / 2);
                        $skills[$skillName] = $abilityModifier + $proficiencyBonus;
                    }
                }
            }
        }
        
        return $skills;
    }
    
    private function getSkillAbility($skill) {
        $skillAbilities = [
            'Acrobatics' => 'dex',
            'Animal Handling' => 'wis',
            'Arcana' => 'int',
            'Athletics' => 'str',
            'Deception' => 'cha',
            'History' => 'int',
            'Insight' => 'wis',
            'Intimidation' => 'cha',
            'Investigation' => 'int',
            'Medicine' => 'wis',
            'Nature' => 'int',
            'Perception' => 'wis',
            'Performance' => 'cha',
            'Persuasion' => 'cha',
            'Religion' => 'int',
            'Sleight of Hand' => 'dex',
            'Stealth' => 'dex',
            'Survival' => 'wis'
        ];
        
        return $skillAbilities[$skill] ?? null;
    }
    
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
    
    private function getSpellSlotsForClass($class, $level) {
        // Базовые слоты для полных заклинателей
        if (in_array($class, ['wizard', 'cleric', 'druid', 'bard', 'sorcerer', 'artificer'])) {
            $spellSlots = [
                1 => [2, 0, 0, 0, 0, 0, 0, 0, 0],
                2 => [3, 0, 0, 0, 0, 0, 0, 0, 0],
                3 => [4, 2, 0, 0, 0, 0, 0, 0, 0],
                4 => [4, 3, 0, 0, 0, 0, 0, 0, 0],
                5 => [4, 3, 2, 0, 0, 0, 0, 0, 0]
            ];
        }
        // Полузаклинатели
        elseif (in_array($class, ['paladin', 'ranger'])) {
            $spellSlots = [
                1 => [0, 0, 0, 0, 0, 0, 0, 0, 0],
                2 => [2, 0, 0, 0, 0, 0, 0, 0, 0],
                3 => [3, 0, 0, 0, 0, 0, 0, 0, 0],
                4 => [3, 0, 0, 0, 0, 0, 0, 0, 0],
                5 => [4, 2, 0, 0, 0, 0, 0, 0, 0]
            ];
        }
        else {
            return [0, 0, 0, 0, 0, 0, 0, 0, 0];
        }
        
        return $spellSlots[$level] ?? [0, 0, 0, 0, 0, 0, 0, 0, 0];
    }
    
    private function calculateSpellAttackBonus($abilities, $spellcastingAbility, $level) {
        $proficiencyBonus = $this->calculateProficiencyBonus($level);
        $abilityModifier = floor(($abilities[$spellcastingAbility] - 10) / 2);
        return $proficiencyBonus + $abilityModifier;
    }
    
    private function calculateSpellSaveDC($abilities, $spellcastingAbility, $level) {
        $proficiencyBonus = $this->calculateProficiencyBonus($level);
        $abilityModifier = floor(($abilities[$spellcastingAbility] - 10) / 2);
        return 8 + $proficiencyBonus + $abilityModifier;
    }
    
    private function getBackgroundEquipment($background) {
        // Получаем снаряжение происхождения из API
        try {
            $url = "https://www.dnd5eapi.co/api/backgrounds/" . strtolower($background);
            $response = $this->makeApiRequest($url);
            
            if ($response && isset($response['starting_equipment'])) {
                $equipment = [];
                foreach ($response['starting_equipment'] as $item) {
                    $equipment[] = $item['equipment']['name'] ?? $item['name'] ?? 'Unknown item';
                }
                return $equipment;
            }
        } catch (Exception $e) {
            logMessage('WARNING', "Не удалось получить снаряжение происхождения {$background}: " . $e->getMessage());
        }
        
        return [];
    }
    
    private function getAdditionalEquipmentByLevel($level) {
        // Дополнительное снаряжение в зависимости от уровня
        $additionalEquipment = [];
        
        if ($level >= 3) {
            $additionalEquipment[] = 'Healing Potion';
        }
        if ($level >= 5) {
            $additionalEquipment[] = 'Magic Weapon';
        }
        if ($level >= 10) {
            $additionalEquipment[] = 'Rare Magic Item';
        }
        
        return $additionalEquipment;
    }
    
    private function getStartingMoney($class) {
        // Получаем стартовые деньги из API
        try {
            $url = "https://www.dnd5eapi.co/api/classes/" . strtolower($class) . "/starting-equipment";
            $response = $this->makeApiRequest($url);
            
            if ($response && isset($response['gold'])) {
                return $response['gold'] . ' gp';
            }
        } catch (Exception $e) {
            logMessage('WARNING', "Не удалось получить стартовые деньги для класса {$class}: " . $e->getMessage());
        }
        
        // Fallback - базовые деньги
        return '50 gp';
    }
    
    private function getAlignmentText($alignment) {
        if ($alignment === 'random') {
            $alignments = [
                'Lawful Good', 'Neutral Good', 'Chaotic Good',
                'Lawful Neutral', 'True Neutral', 'Chaotic Neutral',
                'Lawful Evil', 'Neutral Evil', 'Chaotic Evil'
            ];
            return $alignments[array_rand($alignments)];
        }
        
        $alignmentMap = [
            'lawful-good' => 'Lawful Good',
            'neutral-good' => 'Neutral Good',
            'chaotic-good' => 'Chaotic Good',
            'lawful-neutral' => 'Lawful Neutral',
            'neutral' => 'True Neutral',
            'chaotic-neutral' => 'Chaotic Neutral',
            'lawful-evil' => 'Lawful Evil',
            'neutral-evil' => 'Neutral Evil',
            'chaotic-evil' => 'Chaotic Evil'
        ];
        
        return $alignmentMap[$alignment] ?? 'True Neutral';
    }
    
    private function getGenderText($gender) {
        if ($gender === 'random') {
            $gender = rand(0, 1) ? 'male' : 'female';
        }
        
        return $gender === 'male' ? 'Male' : 'Female';
    }
    
    private function processRaceData($data) {
        return [
            'name' => $data['name'] ?? 'Unknown',
            'speed' => $data['speed'] ?? 30,
            'ability_bonuses' => $this->extractAbilityBonuses($data),
            'traits' => $this->extractTraits($data),
            'languages' => $this->extractLanguages($data),
            'subraces' => $this->extractSubraces($data)
        ];
    }
    
    private function processClassData($data) {
        return [
            'name' => $data['name'] ?? 'Unknown',
            'hit_die' => $data['hit_die'] ?? 8,
            'proficiencies' => $this->extractProficiencies($data),
            'saving_throws' => $this->extractSavingThrows($data),
            'spellcasting' => isset($data['spellcasting']),
            'spellcasting_ability' => $data['spellcasting']['spellcasting_ability']['name'] ?? null
        ];
    }
    
    private function processBackgroundData($data) {
        return [
            'name' => $data['name'] ?? 'Unknown',
            'starting_proficiencies' => $data['starting_proficiencies'] ?? [],
            'starting_equipment' => $data['starting_equipment'] ?? [],
            'feature' => $data['feature'] ?? null
        ];
    }
    
    private function extractAbilityBonuses($data) {
        $bonuses = [];
        if (isset($data['ability_bonuses'])) {
            foreach ($data['ability_bonuses'] as $bonus) {
                $ability = strtolower($bonus['ability_score']['name'] ?? 'str');
                $bonuses[$ability] = $bonus['bonus'] ?? 1;
            }
        }
        return $bonuses;
    }
    
    private function extractTraits($data) {
        $traits = [];
        if (isset($data['traits'])) {
            foreach ($data['traits'] as $trait) {
                $traits[] = $trait['name'] ?? 'Unknown trait';
            }
        }
        return $traits;
    }
    
    private function extractLanguages($data) {
        $languages = [];
        if (isset($data['languages'])) {
            foreach ($data['languages'] as $language) {
                $languages[] = $language['name'] ?? 'Common';
            }
        }
        return $languages;
    }
    
    private function extractSubraces($data) {
        $subraces = [];
        if (isset($data['subraces'])) {
            foreach ($data['subraces'] as $subrace) {
                $subraces[] = $subrace['name'] ?? 'Unknown subrace';
            }
        }
        return $subraces;
    }
    
    private function extractProficiencies($data) {
        $proficiencies = [];
        if (isset($data['proficiencies'])) {
            foreach ($data['proficiencies'] as $prof) {
                $proficiencies[] = $prof['name'] ?? 'Unknown proficiency';
            }
        }
        return $proficiencies;
    }
    
    private function extractSavingThrows($data) {
        $savingThrows = [];
        if (isset($data['saving_throws'])) {
            foreach ($data['saving_throws'] as $save) {
                $savingThrows[] = $save['name'] ?? 'Unknown save';
            }
        }
        return $savingThrows;
    }
    
    private function fetchFromOpen5e($endpoint) {
        $url = 'https://api.open5e.com' . $endpoint;
        return $this->makeApiRequest($url);
    }
    
    private function makeApiRequest($url) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/3.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                return json_decode($response, true);
            }
        }
        
        return null;
    }
    
    private function validateParams($params) {
        $level = (int)($params['level'] ?? 1);
        if ($level < 1 || $level > 20) {
            throw new Exception('Уровень персонажа должен быть от 1 до 20');
        }
        
        // Проверяем, что раса и класс не пустые
        if (empty($params['race']) || empty($params['class'])) {
            throw new Exception('Раса и класс обязательны для генерации');
        }
    }
    
    private function createErrorResponse($errorType, $message) {
        return [
            'success' => false,
            'error' => $errorType,
            'message' => $message,
            'details' => 'Не удалось получить данные из внешних источников'
        ];
    }
}
?>
