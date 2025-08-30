<?php
// Убираем заголовки для использования в тестах
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

// Проверяем метод запроса только если это не CLI
if (php_sapi_name() !== 'cli' && (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST')) {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}
require_once __DIR__ . '/../config.php';

class CharacterGeneratorV2 {
    private $dnd5e_api_url = 'https://www.dnd5eapi.co/api';
    private $deepseek_api_key;
    private $occupations = [];
    private $raceNames = [];
    
    public function __construct() {
        $this->deepseek_api_key = getApiKey('deepseek');
        $this->loadLocalData();
    }
    
    /**
     * Загрузка локальных данных из JSON файлов
     */
    private function loadLocalData() {
        // Загружаем профессии
        $occupationsFile = __DIR__ . '/../pdf/d100_unique_traders.json';
        if (file_exists($occupationsFile)) {
            $jsonData = json_decode(file_get_contents($occupationsFile), true);
            if (isset($jsonData['data']['occupations'])) {
                $this->occupations = $jsonData['data']['occupations'];
            }
        }
        
        // Загружаем имена
        $namesFile = __DIR__ . '/../pdf/dnd_race_names_ru_v2.json';
        if (file_exists($namesFile)) {
            $jsonData = json_decode(file_get_contents($namesFile), true);
            if (isset($jsonData['data'])) {
                foreach ($jsonData['data'] as $raceData) {
                    $raceKey = strtolower($raceData['race']);
                    $this->raceNames[$raceKey] = $raceData;
                }
            }
        }
    }
    
    /**
     * Генерация персонажа
     */
    public function generateCharacter($params) {
        $race = $params['race'] ?? 'human';
        $class = $params['class'] ?? 'fighter';
        $level = (int)($params['level'] ?? 1);
        $alignment = $params['alignment'] ?? 'neutral';
        $gender = $params['gender'] ?? 'random';
        $use_ai = isset($params['use_ai']) && $params['use_ai'] === 'on';
        
        try {
            // 1. Получаем данные из API D&D
            $race_data = $this->getRaceDataFromAPI($race);
            $class_data = $this->getClassDataFromAPI($class);
            
            // 2. Генерируем характеристики
            $abilities = $this->generateAbilities($race_data, $level);
            
            // 3. Получаем имя из JSON
            $name = $this->generateName($race, $gender);
            
            // 4. Получаем профессию из JSON
            $occupation = $this->getRandomOccupation();
            
            // 5. Рассчитываем параметры
            $character = [
                'name' => $name,
                'race' => $race_data['name'] ?? $race,
                'class' => $class_data['name'] ?? $class,
                'level' => $level,
                'alignment' => $this->getAlignmentText($alignment),
                'gender' => $this->getGenderText($gender),
                'occupation' => $occupation,
                'abilities' => $abilities,
                'hit_points' => $this->calculateHP($class_data, $abilities['con'], $level),
                'armor_class' => $this->calculateAC($class_data, $abilities['dex']),
                'speed' => $this->getSpeed($race_data),
                'initiative' => $this->calculateInitiative($abilities['dex']),
                'proficiency_bonus' => $this->calculateProficiencyBonus($level),
                'attack_bonus' => $this->calculateAttackBonus($class_data, $abilities, $level),
                'damage' => $this->calculateDamage($class_data, $abilities, $level),
                'main_weapon' => $this->getMainWeapon($class_data),
                'proficiencies' => $this->getProficiencies($class_data),
                'spells' => $this->getSpells($class_data, $level, $abilities['int'], $abilities['wis'], $abilities['cha']),
                'features' => $this->getFeatures($class_data, $level),
                'equipment' => $this->getEquipment($class_data),
                'saving_throws' => $this->getSavingThrows($class_data, $abilities)
            ];
            
            // 6. Генерируем описание и предысторию (AI или fallback)
            $character['use_ai'] = $use_ai ? 'on' : 'off';
            $character['description'] = $this->generateDescription($character);
            $character['background'] = $this->generateBackground($character);
            
            return [
                'success' => true,
                'npc' => $character
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение данных расы из API D&D
     */
    private function getRaceDataFromAPI($race) {
        try {
            $url = $this->dnd5e_api_url . "/races/$race";
            $response = $this->makeApiRequest($url);
            
            if ($response) {
                return $response;
            }
        } catch (Exception $e) {
            error_log("Failed to fetch race data from API: " . $e->getMessage());
        }
        
        // Fallback данные
        return $this->getFallbackRaceData($race);
    }
    
    /**
     * Получение данных класса из API D&D
     */
    private function getClassDataFromAPI($class) {
        try {
            $url = $this->dnd5e_api_url . "/classes/$class";
            $response = $this->makeApiRequest($url);
            
            if ($response) {
                return $response;
            }
        } catch (Exception $e) {
            error_log("Failed to fetch class data from API: " . $e->getMessage());
        }
        
        // Fallback данные
        return $this->getFallbackClassData($class);
    }
    
    /**
     * Выполнение API запроса
     */
    private function makeApiRequest($url) {
        if (!function_exists('curl_init')) {
            return null;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response && $httpCode === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    /**
     * Генерация имени из JSON файла
     */
    private function generateName($race, $gender) {
        $race = strtolower($race);
        
        if (isset($this->raceNames[$race])) {
            $raceData = $this->raceNames[$race];
            
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
        
        $gender = $gender === 'random' ? (rand(0, 1) ? 'male' : 'female') : $gender;
        return $fallbackNames[$gender][array_rand($fallbackNames[$gender])];
    }
    
    /**
     * Получение случайной профессии из JSON
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
        $name = preg_replace('/\d+/', '', $name);
        $name = preg_replace('/\s+([А-ЯЁ])/u', ' $1', $name);
        $name = trim($name);
        
        if (empty($name) || strlen($name) < 2) {
            return 'Странник';
        }
        
        return $name;
    }
    
    /**
     * Генерация описания с помощью AI
     */
    private function generateDescription($character) {
        if ($this->deepseek_api_key) {
            $characterInfo = $this->buildCharacterInfo($character);
            
            $prompt = "Опиши внешность и характер персонажа на основе его полных данных:\n\n" . $characterInfo . "\n" .
                     "Включи детали внешности, особенности поведения и характерные черты, связанные с его расой, классом, профессией и характеристиками. " .
                     "Ответ должен быть кратким (2-3 предложения) и атмосферным.";
            
            try {
                $response = $this->callDeepSeek($prompt);
                if ($response) {
                    return $response;
                }
            } catch (Exception $e) {
                error_log("AI description generation failed: " . $e->getMessage());
            }
        }
        
        // Fallback описание
        return $this->generateFallbackDescription($character);
    }
    
    /**
     * Генерация предыстории с помощью AI
     */
    private function generateBackground($character) {
        if ($this->deepseek_api_key) {
            $characterInfo = $this->buildCharacterInfo($character);
            
            $prompt = "Создай краткую предысторию персонажа на основе его полных данных:\n\n" . $characterInfo . "\n" .
                     "Включи мотивацию, ключевое событие из прошлого и цель персонажа, связанные с его расой, классом, профессией и характеристиками. " .
                     "Ответ должен быть кратким (2-3 предложения) и интересным.";
            
            try {
                $response = $this->callDeepSeek($prompt);
                if ($response) {
                    return $response;
                }
            } catch (Exception $e) {
                error_log("AI background generation failed: " . $e->getMessage());
            }
        }
        
        // Fallback предыстория
        return $this->generateFallbackBackground($character);
    }
    
    /**
     * Формирование информации о персонаже для AI
     */
    private function buildCharacterInfo($character) {
        $info = "Персонаж: {$character['name']}, {$character['race']} {$character['class']} {$character['level']} уровня.\n";
        $info .= "Профессия: {$character['occupation']}\n";
        $info .= "Пол: {$character['gender']}\n";
        $info .= "Мировоззрение: {$character['alignment']}\n";
        $info .= "Характеристики: СИЛ {$character['abilities']['str']}, ЛОВ {$character['abilities']['dex']}, ТЕЛ {$character['abilities']['con']}, ИНТ {$character['abilities']['int']}, МДР {$character['abilities']['wis']}, ХАР {$character['abilities']['cha']}\n";
        $info .= "Боевые параметры: Хиты {$character['hit_points']}, КД {$character['armor_class']}, Скорость {$character['speed']} футов, Инициатива {$character['initiative']}, Бонус мастерства +{$character['proficiency_bonus']}\n";
        $info .= "Урон: {$character['damage']}\n";
        
        if (!empty($character['proficiencies'])) {
            $info .= "Владения: " . implode(', ', $character['proficiencies']) . "\n";
        }
        
        if (!empty($character['spells'])) {
            $info .= "Заклинания: " . implode(', ', array_column($character['spells'], 'name')) . "\n";
        }
        
        return $info;
    }
    
    /**
     * Вызов DeepSeek API
     */
    private function callDeepSeek($prompt) {
        if (!$this->deepseek_api_key) {
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
        
        // Проверяем доступность cURL
        if (function_exists('curl_init')) {
            return $this->callDeepSeekWithCurl($data);
        } else {
            return $this->callDeepSeekWithFileGetContents($data);
        }
    }
    
    private function callDeepSeekWithCurl($data) {
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
        
        if ($response && $httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            }
        }
        
        return null;
    }
    
    private function callDeepSeekWithFileGetContents($data) {
        // Проверяем, доступен ли HTTPS wrapper
        if (!in_array('https', stream_get_wrappers())) {
            error_log("HTTPS wrapper not available, using fallback description");
            return null;
        }
        
        $url = 'https://api.deepseek.com/v1/chat/completions';
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->deepseek_api_key
                ],
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log("Failed to call DeepSeek API, using fallback description");
            return null;
        }
        
        $response = json_decode($result, true);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }
        
        error_log("Invalid response from DeepSeek API, using fallback description");
        return null;
    }
    
    // ... остальные методы остаются такими же как в оригинальном файле ...
    
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
            foreach ($race_data['ability_bonuses'] as $bonus) {
                $ability = $bonus['ability_score']['name'] ?? $bonus['ability_score'];
                $ability = strtolower($ability);
                if (isset($abilities[$ability])) {
                    $abilities[$ability] += $bonus['bonus'];
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
        array_shift($rolls);
        return array_sum($rolls);
    }
    
    /**
     * Расчет хитов
     */
    private function calculateHP($class_data, $con_modifier, $level) {
        $hit_die = $class_data['hit_die'] ?? 8;
        $con_bonus = floor(($con_modifier - 10) / 2);
        $base_hp = $hit_die + $con_bonus;
        $additional_hp = 0;
        
        for ($i = 2; $i <= $level; $i++) {
            $additional_hp += rand(1, $hit_die) + $con_bonus;
        }
        
        return max(1, $base_hp + $additional_hp);
    }
    
    /**
     * Расчет класса доспеха
     */
    private function calculateAC($class_data, $dex_modifier) {
        $dex_bonus = floor(($dex_modifier - 10) / 2);
        
        // Проверяем владения доспехами
        $proficiencies = $class_data['proficiencies'] ?? [];
        $proficiency_names = array_column($proficiencies, 'name');
        
        if (in_array('All Armor', $proficiency_names)) {
            return 16 + min(2, $dex_bonus); // Кольчуга
        } elseif (in_array('Medium Armor', $proficiency_names)) {
            return 14 + min(2, $dex_bonus); // Кожаный доспех
        } else {
            return 10 + $dex_bonus; // Без доспеха
        }
    }
    
    /**
     * Получение скорости
     */
    private function getSpeed($race_data) {
        return $race_data['speed']['walk'] ?? 30;
    }
    
    /**
     * Расчет инициативы
     */
    private function calculateInitiative($dex_modifier) {
        return floor(($dex_modifier - 10) / 2);
    }
    
    /**
     * Расчет бонуса мастерства
     */
    private function calculateProficiencyBonus($level) {
        return floor(($level - 1) / 4) + 2;
    }
    
    /**
     * Расчет попадания (атаки)
     */
    private function calculateAttackBonus($class_data, $abilities, $level = 1) {
        $proficiency_bonus = $this->calculateProficiencyBonus($level);
        $str_bonus = floor(($abilities['str'] - 10) / 2);
        $attack_bonus = $proficiency_bonus + $str_bonus;
        
        if ($attack_bonus >= 0) {
            return '+' . $attack_bonus;
        } else {
            return $attack_bonus;
        }
    }
    
    /**
     * Расчет урона
     */
    private function calculateDamage($class_data, $abilities, $level = 1) {
        $hit_die = $class_data['hit_die'] ?? 8;
        $damage_bonus = floor(($abilities['str'] - 10) / 2);
        
        $dice_count = 1;
        $dice_size = $hit_die;
        
        if ($level >= 5) {
            $dice_count = 2;
        }
        if ($level >= 11) {
            $dice_count = 3;
        }
        if ($level >= 20) {
            $dice_count = 4;
        }
        
        $damage_formula = $dice_count . 'd' . $dice_size;
        
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
        $proficiencies = $class_data['proficiencies'] ?? [];
        $proficiency_names = array_column($proficiencies, 'name');
        
        if (in_array('Martial Weapons', $proficiency_names)) {
            $weapons = ['Длинный меч', 'Боевой топор', 'Молот', 'Копье', 'Алебарда', 'Меч-рапира'];
        } elseif (in_array('Simple Weapons', $proficiency_names)) {
            $weapons = ['Булава', 'Короткий меч', 'Кинжал', 'Дубина', 'Копье', 'Топор'];
        } else {
            $weapons = ['Кинжал', 'Дубина', 'Копье'];
        }
        
        return $weapons[array_rand($weapons)];
    }
    
    /**
     * Получение владений
     */
    private function getProficiencies($class_data) {
        $proficiencies = $class_data['proficiencies'] ?? [];
        return array_column($proficiencies, 'name');
    }
    
    /**
     * Получение заклинаний
     */
    private function getSpells($class_data, $level, $int, $wis, $cha) {
        if (!isset($class_data['spellcasting'])) {
            return [];
        }
        
        // Простая реализация заклинаний
        $spells = [];
        if ($level >= 1) {
            $spells[] = [
                'name' => 'Свет',
                'level' => 1,
                'school' => 'Воплощение'
            ];
        }
        
        return $spells;
    }
    
    /**
     * Получение способностей
     */
    private function getFeatures($class_data, $level) {
        $features = [];
        
        if (isset($class_data['class_levels'])) {
            foreach ($class_data['class_levels'] as $classLevel) {
                if ($classLevel['level'] <= $level) {
                    if (isset($classLevel['features'])) {
                        foreach ($classLevel['features'] as $feature) {
                            $features[] = $feature['name'];
                        }
                    }
                }
            }
        }
        
        return $features;
    }
    
    /**
     * Получение снаряжения
     */
    private function getEquipment($class_data) {
        $equipment = [];
        
        // Базовое снаряжение
        $equipment[] = 'Рюкзак исследователя';
        $equipment[] = 'Веревка (50 футов)';
        $equipment[] = 'Факел';
        $equipment[] = 'Трутница';
        
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
        
        if (isset($class_data['saving_throws'])) {
            foreach ($class_data['saving_throws'] as $save) {
                $ability = strtolower($save['name']);
                $modifier = floor(($abilities[$ability] - 10) / 2);
                $saving_throws[] = ['name' => $save['name'], 'modifier' => $modifier];
            }
        }
        
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
     * Fallback данные для рас
     */
    private function getFallbackRaceData($race) {
        $fallback_races = [
            'human' => [
                'name' => 'Человек',
                'ability_bonuses' => [['ability_score' => 'str', 'bonus' => 1], ['ability_score' => 'dex', 'bonus' => 1], ['ability_score' => 'con', 'bonus' => 1], ['ability_score' => 'int', 'bonus' => 1], ['ability_score' => 'wis', 'bonus' => 1], ['ability_score' => 'cha', 'bonus' => 1]],
                'speed' => ['walk' => 30]
            ],
            'elf' => [
                'name' => 'Эльф',
                'ability_bonuses' => [['ability_score' => 'dex', 'bonus' => 2]],
                'speed' => ['walk' => 30]
            ],
            'dwarf' => [
                'name' => 'Дварф',
                'ability_bonuses' => [['ability_score' => 'con', 'bonus' => 2]],
                'speed' => ['walk' => 25]
            ]
        ];
        
        return $fallback_races[$race] ?? $fallback_races['human'];
    }
    
    /**
     * Fallback данные для классов
     */
    private function getFallbackClassData($class) {
        $fallback_classes = [
            'fighter' => [
                'name' => 'Воин',
                'hit_die' => 10,
                'proficiencies' => [['name' => 'All Armor'], ['name' => 'Shields'], ['name' => 'Simple Weapons'], ['name' => 'Martial Weapons']]
            ],
            'wizard' => [
                'name' => 'Волшебник',
                'hit_die' => 6,
                'proficiencies' => [['name' => 'Daggers'], ['name' => 'Quarterstaffs'], ['name' => 'Light Crossbows']],
                'spellcasting' => true
            ],
            'rogue' => [
                'name' => 'Плут',
                'hit_die' => 8,
                'proficiencies' => [['name' => 'Light Armor'], ['name' => 'Simple Weapons'], ['name' => 'Shortswords'], ['name' => 'Longswords']]
            ]
        ];
        
        return $fallback_classes[$class] ?? $fallback_classes['fighter'];
    }
    
    /**
     * Fallback описание
     */
    private function generateFallbackDescription($character) {
        $name = $character['name'];
        $race = $character['race'];
        $class = $character['class'];
        $occupation = $character['occupation'];
        $gender = $character['gender'];
        
        // Базовые описания по расам
        $raceDescriptions = [
            'Эльф' => [
                'female' => 'высокая и грациозная, с острыми чертами лица и длинными ушами',
                'male' => 'высокий и грациозный, с острыми чертами лица и длинными ушами'
            ],
            'Человек' => [
                'female' => 'среднего роста, с выразительными глазами и уверенной походкой',
                'male' => 'среднего роста, с выразительными глазами и уверенной походкой'
            ],
            'Дварф' => [
                'female' => 'коренастая и крепкая, с густой бородой и пронзительным взглядом',
                'male' => 'коренастый и крепкий, с густой бородой и пронзительным взглядом'
            ]
        ];
        
        $raceDesc = $raceDescriptions[$race][$gender] ?? 'с характерной внешностью';
        
        // Описания по классам
        $classDescriptions = [
            'Волшебник' => 'носит мантию с таинственными символами, в глазах мерцает магическая энергия',
            'Воин' => 'облачен в прочную броню, движения уверенные и расчетливые',
            'Плут' => 'движется бесшумно, в глазах читается хитрость и опыт'
        ];
        
        $classDesc = $classDescriptions[$class] ?? 'выглядит опытным и уверенным';
        
        return "{$name} - {$raceDesc} {$race} {$class}. {$classDesc}. Профессия {$occupation} наложила отпечаток на характер - в каждом движении чувствуется внутренняя сила, опыт и готовность к приключениям.";
    }
    
    /**
     * Fallback предыстория
     */
    private function generateFallbackBackground($character) {
        $name = $character['name'];
        $occupation = $character['occupation'];
        $class = $character['class'];
        $race = $character['race'];
        $gender = $character['gender'];
        
        // Предыстории по расам
        $raceBackgrounds = [
            'Эльф' => [
                'female' => 'Родилась в древнем эльфийском лесу, где провела первые столетия жизни',
                'male' => 'Родился в древнем эльфийском лесу, где провел первые столетия жизни'
            ],
            'Человек' => [
                'female' => 'Выросла в шумном человеческом городе, среди торговцев и ремесленников',
                'male' => 'Вырос в шумном человеческом городе, среди торговцев и ремесленников'
            ],
            'Дварф' => [
                'female' => 'Родилась в подземных залах, среди звонких молотов и искр кузнечного дела',
                'male' => 'Родился в подземных залах, среди звонких молотов и искр кузнечного дела'
            ]
        ];
        
        $raceBg = $raceBackgrounds[$race][$gender] ?? 'Происходит из простой семьи';
        
        // Мотивации по классам
        $classMotivations = [
            'Волшебник' => 'Стремление к знаниям и магической силе привело к изучению древних томов',
            'Воин' => 'Желание защищать слабых и сражаться за справедливость определило жизненный путь',
            'Плут' => 'Ловкость рук и острый ум помогли выжить в опасном мире'
        ];
        
        $classMotivation = $classMotivations[$class] ?? 'Стремление к приключениям и славе';
        
        return "{$raceBg}. Работа {$occupation} научила ценить упорство, мастерство и важность связей. {$classMotivation}. Теперь {$name} путешествует по миру, используя свои навыки для помощи другим и поиска новых приключений.";
    }
}

// Обработка запроса только если это не CLI
if (php_sapi_name() !== 'cli') {
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
}
?>
