<?php
    header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

class CharacterGeneratorV2 {
    private $deepseek_api_key;
    private $occupations = [];
    private $race_names = [];
    
    public function __construct() {
        $this->deepseek_api_key = getApiKey('deepseek');
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
        
            // Получаем данные расы и класса
            $race_data = $this->getRaceData($race);
            $class_data = $this->getClassData($class);
            
            // Генерируем характеристики
            $abilities = $this->generateAbilities($race_data, $level);
            
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
                'spells' => $this->getSpells($class_data, $level, $abilities),
                'features' => $this->getFeatures($class_data, $level),
                'equipment' => $this->getEquipment($class_data),
                'saving_throws' => $this->getSavingThrows($class_data, $abilities)
            ];
            
            // Добавляем описание
            $character['description'] = $this->generateDescription($character, $use_ai);
            $character['background'] = $this->generateBackground($character, $use_ai);
            
            logMessage('INFO', 'Character generated successfully', [
                'race' => $race,
                'class' => $class,
                'level' => $level
            ]);
            
            return [
                'success' => true,
                'character' => $character
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
     * Получение данных расы
     */
    private function getRaceData($race) {
        $races = [
            'human' => [
                'name' => 'Человек',
                'ability_bonuses' => ['str' => 1, 'dex' => 1, 'con' => 1, 'int' => 1, 'wis' => 1, 'cha' => 1],
                'traits' => ['Универсальность', 'Дополнительное владение навыком'],
                'speed' => 30
            ],
            'elf' => [
                'name' => 'Эльф',
                'ability_bonuses' => ['dex' => 2],
                'traits' => ['Темное зрение', 'Келебрас', 'Иммунитет к усыплению', 'Транс'],
                'speed' => 30
            ],
            'dwarf' => [
                'name' => 'Дварф',
                'ability_bonuses' => ['con' => 2],
                'traits' => ['Темное зрение', 'Устойчивость к яду', 'Владение боевым топором'],
                'speed' => 25
            ],
            'halfling' => [
                'name' => 'Полурослик',
                'ability_bonuses' => ['dex' => 2],
                'traits' => ['Удача', 'Смелость', 'Ловкость полурослика'],
                'speed' => 25
            ],
            'tiefling' => [
                'name' => 'Тифлинг',
                'ability_bonuses' => ['cha' => 2, 'int' => 1],
                'traits' => ['Темное зрение', 'Устойчивость к огню', 'Адское наследие'],
                'speed' => 30
            ],
            'dragonborn' => [
                'name' => 'Драконорожденный',
                'ability_bonuses' => ['str' => 2, 'cha' => 1],
                'traits' => ['Дыхание дракона', 'Устойчивость к урону', 'Драконье наследие'],
                'speed' => 30
            ]
        ];
        
        return $races[$race] ?? $races['human'];
    }
    
    /**
     * Получение данных класса
     */
    private function getClassData($class) {
        $classes = [
            'fighter' => [
                'name' => 'Воин',
                'hit_die' => 10,
                'proficiencies' => ['Все доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
                'features' => ['Боевой стиль', 'Second Wind'],
                'spellcasting' => false
            ],
            'wizard' => [
                'name' => 'Волшебник',
                'hit_die' => 6,
                'proficiencies' => ['Кинжалы', 'Посохи', 'Арбалеты'],
                'features' => ['Заклинания', 'Восстановление заклинаний'],
                'spellcasting' => true,
                'spellcasting_ability' => 'int'
            ],
            'rogue' => [
                'name' => 'Плут',
                'hit_die' => 8,
                'proficiencies' => ['Легкие доспехи', 'Простое оружие', 'Короткие мечи', 'Длинные мечи'],
                'features' => ['Скрытность', 'Sneak Attack'],
                'spellcasting' => false
            ],
            'cleric' => [
                'name' => 'Жрец',
                'hit_die' => 8,
                'proficiencies' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие'],
                'features' => ['Заклинания', 'Божественный домен'],
                'spellcasting' => true,
                'spellcasting_ability' => 'wis'
            ],
            'ranger' => [
                'name' => 'Следопыт',
                'hit_die' => 10,
                'proficiencies' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
                'features' => ['Любимый враг', 'Естественный исследователь'],
                'spellcasting' => true,
                'spellcasting_ability' => 'wis'
            ]
        ];
        
        return $classes[$class] ?? $classes['fighter'];
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
        
        // Применяем бонусы расы
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
        
        if (isset($this->race_names[$race])) {
            $raceData = $this->race_names[$race];
            
            if ($gender === 'random') {
                $gender = rand(0, 1) ? 'male' : 'female';
            }
            
            $nameList = [];
            
            if ($gender === 'male' && !empty($raceData['male'])) {
                $nameList = $raceData['male'];
            } elseif ($gender === 'female' && !empty($raceData['female'])) {
                $nameList = $raceData['female'];
            }
            
            if (empty($nameList) && !empty($raceData['unisex'])) {
                $nameList = $raceData['unisex'];
            }
            
            if (!empty($nameList)) {
                return $nameList[array_rand($nameList)];
            }
        }
        
        // Fallback имена
        $fallbackNames = [
            'male' => ['Алексей', 'Дмитрий', 'Иван', 'Михаил', 'Сергей', 'Андрей', 'Владимир', 'Николай', 'Петр', 'Александр'],
            'female' => ['Анна', 'Елена', 'Мария', 'Ольга', 'Татьяна', 'Ирина', 'Наталья', 'Светлана', 'Екатерина', 'Юлия']
        ];
        
        if ($gender === 'random') {
            $gender = rand(0, 1) ? 'male' : 'female';
        }
        
        return $fallbackNames[$gender][array_rand($fallbackNames[$gender])];
    }
    
    /**
     * Получение случайной профессии
     */
    private function getRandomOccupation() {
        if (empty($this->occupations)) {
            return 'Странник';
        }
        
        $occupation = $this->occupations[array_rand($this->occupations)];
        $name = $occupation['name_ru'] ?? 'Странник';
        
        // Очищаем от лишних символов
        $name = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);
        
        if (empty($name) || strlen($name) < 2) {
            return 'Странник';
        }
        
        return $name;
    }
    
    /**
     * Расчет хитов
     */
    private function calculateHP($class_data, $con, $level) {
        $con_bonus = floor(($con - 10) / 2);
        $base_hp = $class_data['hit_die'] + $con_bonus;
        $additional_hp = 0;
        
        for ($i = 2; $i <= $level; $i++) {
            $additional_hp += rand(1, $class_data['hit_die']) + $con_bonus;
        }
        
        return max(1, $base_hp + $additional_hp);
    }
    
    /**
     * Расчет класса доспеха
     */
    private function calculateAC($class_data, $dex) {
        $dex_bonus = floor(($dex - 10) / 2);
        
        if (in_array('Все доспехи', $class_data['proficiencies'])) {
            return 16 + min(2, $dex_bonus); // Кольчуга
        } elseif (in_array('Средние доспехи', $class_data['proficiencies'])) {
            return 14 + min(2, $dex_bonus); // Кожаный доспех
        } else {
            return 10 + $dex_bonus; // Без доспеха
        }
    }
    
    /**
     * Расчет инициативы
     */
    private function calculateInitiative($dex) {
        return floor(($dex - 10) / 2);
    }
    
    /**
     * Расчет бонуса мастерства
     */
    private function calculateProficiencyBonus($level) {
        return floor(($level - 1) / 4) + 2;
    }
    
    /**
     * Расчет бонуса атаки
     */
    private function calculateAttackBonus($class_data, $abilities, $level) {
        $proficiency_bonus = $this->calculateProficiencyBonus($level);
        $str_bonus = floor(($abilities['str'] - 10) / 2);
        $attack_bonus = $proficiency_bonus + $str_bonus;
        
        return $attack_bonus >= 0 ? '+' . $attack_bonus : $attack_bonus;
    }
    
    /**
     * Расчет урона
     */
    private function calculateDamage($class_data, $abilities, $level) {
        $damage_die = $class_data['hit_die'];
        $damage_bonus = floor(($abilities['str'] - 10) / 2);
        
        $dice_count = 1;
        if ($level >= 5) $dice_count = 2;
        if ($level >= 11) $dice_count = 3;
        if ($level >= 20) $dice_count = 4;
        
        $damage_formula = $dice_count . 'd' . $damage_die;
        
        if ($damage_bonus > 0) {
            $damage_formula .= ' + ' . $damage_bonus;
        } elseif ($damage_bonus < 0) {
            $damage_formula .= ' - ' . abs($damage_bonus);
        }
        
        return $damage_formula;
    }
    
    /**
     * Получение основного оружия
     */
    private function getMainWeapon($class_data) {
        $weapons = [];
        
        if (in_array('Воинское оружие', $class_data['proficiencies'])) {
            $weapons = ['Длинный меч', 'Боевой топор', 'Молот', 'Копье', 'Алебарда', 'Меч-рапира'];
        } elseif (in_array('Простое оружие', $class_data['proficiencies'])) {
            $weapons = ['Булава', 'Короткий меч', 'Кинжал', 'Дубина', 'Копье', 'Топор'];
        }
        
        if (empty($weapons)) {
            $weapons = ['Кинжал', 'Дубина', 'Копье'];
        }
        
        return $weapons[array_rand($weapons)];
    }
    
    /**
     * Получение заклинаний
     */
    private function getSpells($class_data, $level, $abilities) {
        if (!$class_data['spellcasting']) {
            return [];
        }
        
        $spellcasting_ability = $class_data['spellcasting_ability'] ?? 'int';
        $ability_score = $abilities[$spellcasting_ability];
        $ability_modifier = floor(($ability_score - 10) / 2);
        
        $spells = [];
        
        // Заклинания 1 уровня
        if ($level >= 1) {
            $level1_spells = [
                [
                'name' => 'Свет',
                'level' => 1,
                    'school' => 'Воплощение',
                    'casting_time' => '1 действие',
                    'range' => 'Касание',
                    'components' => 'V, M (светлячок или светящийся мох)',
                    'duration' => '1 час',
                    'description' => 'Вы касаетесь объекта размером не больше 10 футов в любом измерении. Пока заклинание активно, объект испускает яркий свет в радиусе 20 футов и тусклый свет еще на 20 футов.',
                    'damage' => null
                ],
                [
                    'name' => 'Магическая стрела',
                    'level' => 1,
                    'school' => 'Воплощение',
                    'casting_time' => '1 действие',
                    'range' => '120 футов',
                    'components' => 'V, S',
                    'duration' => 'Мгновенно',
                    'description' => 'Вы создаете три светящихся дротика магической энергии. Каждый дротик поражает цель по вашему выбору, которую вы можете видеть в пределах дистанции.',
                    'damage' => '1d4 + ' . $ability_modifier . ' урона силовым полем за дротик'
                ]
            ];
            
            $spell_count = min(2, count($level1_spells));
            $selected_spells = array_rand($level1_spells, $spell_count);
            if (!is_array($selected_spells)) {
                $selected_spells = [$selected_spells];
            }
            
            foreach ($selected_spells as $index) {
                $spells[] = $level1_spells[$index];
            }
        }
        
        return $spells;
    }
    
    /**
     * Получение способностей
     */
    private function getFeatures($class_data, $level) {
        $features = $class_data['features'];
        
        if ($level >= 2) {
            $features[] = 'Дополнительная атака';
        }
        if ($level >= 5) {
            $features[] = 'Улучшенная критическая атака';
        }
        
        return $features;
    }
    
    /**
     * Получение снаряжения
     */
    private function getEquipment($class_data) {
        $equipment = [];
        
        // Доспехи
        if (in_array('Все доспехи', $class_data['proficiencies'])) {
            $armors = ['Кольчуга', 'Кожаный доспех', 'Кожаная броня', 'Стеганый доспех'];
            $equipment[] = $armors[array_rand($armors)];
        } elseif (in_array('Средние доспехи', $class_data['proficiencies'])) {
            $armors = ['Кожаный доспех', 'Кожаная броня', 'Стеганый доспех'];
            $equipment[] = $armors[array_rand($armors)];
        } elseif (in_array('Легкие доспехи', $class_data['proficiencies'])) {
            $armors = ['Кожаная броня', 'Стеганый доспех'];
            $equipment[] = $armors[array_rand($armors)];
        }
        
        // Щиты
        if (in_array('Щиты', $class_data['proficiencies'])) {
            $equipment[] = 'Деревянный щит';
        }
        
        // Оружие
        if (in_array('Воинское оружие', $class_data['proficiencies'])) {
            $weapons = ['Длинный меч', 'Боевой топор', 'Молот', 'Копье', 'Алебарда'];
            $equipment[] = $weapons[array_rand($weapons)];
        } elseif (in_array('Простое оружие', $class_data['proficiencies'])) {
            $weapons = ['Булава', 'Короткий меч', 'Кинжал', 'Дубина', 'Копье'];
            $equipment[] = $weapons[array_rand($weapons)];
        }
        
        // Базовое снаряжение
        $equipment[] = 'Рюкзак исследователя';
        $equipment[] = 'Веревка (50 футов)';
        $equipment[] = 'Факел';
        $equipment[] = 'Трутница';
        
        // Зелья
        $equipment[] = 'Зелье (генерируется отдельно)';
        
        // Деньги
        $gold = rand(5, 25);
        $equipment[] = "{$gold} золотых монет";
        
        return $equipment;
    }
    
    /**
     * Получение бросков способностей
     */
    private function getSavingThrows($class_data, $abilities) {
        $saving_throws = [];
        
        if (isset($class_data['spellcasting']) && $class_data['spellcasting']) {
            $spellcasting_ability = $class_data['spellcasting_ability'] ?? 'int';
            $spellcasting_ability_score = $abilities[$spellcasting_ability] ?? 10;
            $spellcasting_ability_modifier = floor(($spellcasting_ability_score - 10) / 2);
            $saving_throws[] = ['name' => 'Заклинания', 'modifier' => $spellcasting_ability_modifier];
        }

        $saving_throws[] = ['name' => 'Сила', 'modifier' => floor(($abilities['str'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Ловкость', 'modifier' => floor(($abilities['dex'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Телосложение', 'modifier' => floor(($abilities['con'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Интеллект', 'modifier' => floor(($abilities['int'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Мудрость', 'modifier' => floor(($abilities['wis'] - 10) / 2)];
        $saving_throws[] = ['name' => 'Харизма', 'modifier' => floor(($abilities['cha'] - 10) / 2)];
        
        return $saving_throws;
    }
    
    /**
     * Генерация описания
     */
    private function generateDescription($character, $use_ai) {
        if ($use_ai && $this->deepseek_api_key) {
            try {
                $prompt = "Опиши внешность и характер персонажа {$character['name']}, {$character['race']} {$character['class']} {$character['level']} уровня. Профессия: {$character['occupation']}. Пол: {$character['gender']}. Мировоззрение: {$character['alignment']}. Ответ должен быть кратким (2-3 предложения) и атмосферным.";
                $response = $this->callDeepSeek($prompt);
                if ($response) {
                    return $response;
                }
            } catch (Exception $e) {
                logMessage('ERROR', 'AI description generation failed: ' . $e->getMessage());
            }
        }
        
        return "Описание персонажа недоступно";
    }
    
    /**
     * Генерация предыстории
     */
    private function generateBackground($character, $use_ai) {
        if ($use_ai && $this->deepseek_api_key) {
            try {
                $prompt = "Создай краткую предысторию персонажа {$character['name']}, {$character['race']} {$character['class']} {$character['level']} уровня. Профессия: {$character['occupation']}. Пол: {$character['gender']}. Мировоззрение: {$character['alignment']}. Включи мотивацию и ключевое событие из прошлого. Ответ должен быть кратким (2-3 предложения) и интересным.";
                $response = $this->callDeepSeek($prompt);
                if ($response) {
                    return $response;
                }
            } catch (Exception $e) {
                logMessage('ERROR', 'AI background generation failed: ' . $e->getMessage());
            }
        }
        
        return "Предыстория персонажа недоступна";
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
     * Вызов DeepSeek API
     */
    private function callDeepSeek($prompt) {
        if (!$this->deepseek_api_key) {
            return null;
        }
        
        if (!function_exists('curl_init')) {
            return null;
        }
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Создавай интересных и атмосферных персонажей.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 200,
            'temperature' => 0.8
        ];
        
        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->deepseek_api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
        
        return null;
    }
}

// Обработка запроса
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $generator = new CharacterGeneratorV2();
        $result = $generator->generateCharacter($_POST);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Метод не поддерживается'
        ]);
}
?>
