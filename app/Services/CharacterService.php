<?php
require_once __DIR__ . '/../config.php';

class CharacterService {
    private $dndApiUrl;
    private $deepseekApiKey;
    public function __construct() {
        $this->dndApiUrl = DND_API_URL;
        $this->deepseekApiKey = getApiKey('deepseek');
    }
    
    /**
     * Генерация персонажа
     */
    public function generateCharacter($params) {
        try {
            // Валидация параметров
            $validation = $this->validateParams($params);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }
            
            // Генерация базовых характеристик
            $abilities = $this->generateAbilities($params['race']);
            
            // Получение данных расы и класса
            $raceData = $this->getRaceData($params['race']);
            $classData = $this->getClassData($params['class']);
            
            // Генерация имени
            $name = $this->generateName($params['race'], $params['gender'] ?? 'random');
            
            // Расчет параметров
            $character = $this->calculateCharacterStats($params, $abilities, $raceData, $classData, $name);
            
            // Генерация описания через AI
            $description = $this->generateDescription($character);
            
            // Генерация предыстории через AI
            $background = $this->generateBackground($character);
            
            $character['description'] = $description;
            $character['background'] = $background;
            
            logMessage('Character generated successfully', 'INFO', [
                'race' => $params['race'],
                'class' => $params['class'],
                'level' => $params['level']
            ]);
            
            return ['success' => true, 'character' => $character];
            
        } catch (Exception $e) {
            logMessage('Character generation failed: ' . $e->getMessage(), 'ERROR', [
                'params' => $params,
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'error' => 'Ошибка генерации персонажа: ' . $e->getMessage()];
        }
    }
    
    /**
     * Валидация параметров
     */
    private function validateParams($params) {
        $validRaces = ['human', 'elf', 'dwarf', 'halfling', 'orc', 'tiefling', 'dragonborn', 'gnome', 'half-elf', 'half-orc', 'tabaxi', 'aarakocra', 'goblin', 'kenku', 'lizardfolk', 'triton', 'yuan-ti', 'goliath', 'firbolg', 'bugbear', 'hobgoblin', 'kobold'];
        $validClasses = ['fighter', 'wizard', 'rogue', 'cleric', 'ranger', 'barbarian', 'bard', 'druid', 'monk', 'paladin', 'sorcerer', 'warlock', 'artificer'];
        
        if (!isset($params['race']) || !in_array($params['race'], $validRaces)) {
            return ['valid' => false, 'error' => 'Неверная раса'];
        }
        
        if (!isset($params['class']) || !in_array($params['class'], $validClasses)) {
            return ['valid' => false, 'error' => 'Неверный класс'];
        }
        
        if (!isset($params['level']) || $params['level'] < 1 || $params['level'] > 20) {
            return ['valid' => false, 'error' => 'Уровень должен быть от 1 до 20'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Генерация характеристик
     */
    private function generateAbilities($race) {
        $abilities = [];
        $abilityNames = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
        
        foreach ($abilityNames as $ability) {
            // Бросаем 4d6, отбрасываем минимальный
            $rolls = [];
            for ($i = 0; $i < 4; $i++) {
                $rolls[] = rand(1, 6);
            }
            sort($rolls);
            array_shift($rolls); // Убираем минимальный
            $abilities[$ability] = array_sum($rolls);
        }
        
        // Применяем бонусы расы
        $raceBonuses = $this->getRaceAbilityBonuses($race);
        foreach ($raceBonuses as $ability => $bonus) {
            $abilities[$ability] += $bonus;
        }
        
        return $abilities;
    }
    
    /**
     * Получение бонусов характеристик расы
     */
    private function getRaceAbilityBonuses($race) {
        $bonuses = [
            'human' => ['str' => 1, 'dex' => 1, 'con' => 1, 'int' => 1, 'wis' => 1, 'cha' => 1],
            'elf' => ['dex' => 2],
            'dwarf' => ['con' => 2],
            'halfling' => ['dex' => 2],
            'orc' => ['str' => 2, 'con' => 1],
            'tiefling' => ['int' => 1, 'cha' => 2],
            'dragonborn' => ['str' => 2, 'cha' => 1],
            'gnome' => ['int' => 2],
            'half-elf' => ['cha' => 2],
            'half-orc' => ['str' => 2, 'con' => 1],
            'tabaxi' => ['dex' => 2, 'cha' => 1],
            'aarakocra' => ['dex' => 2, 'wis' => 1],
            'goblin' => ['dex' => 2, 'con' => 1],
            'kenku' => ['dex' => 2, 'wis' => 1],
            'lizardfolk' => ['con' => 2, 'wis' => 1],
            'triton' => ['str' => 1, 'con' => 1, 'cha' => 1],
            'yuan-ti' => ['int' => 1, 'cha' => 2],
            'goliath' => ['str' => 2, 'con' => 1],
            'firbolg' => ['wis' => 2, 'str' => 1],
            'bugbear' => ['str' => 2, 'dex' => 1],
            'hobgoblin' => ['con' => 2, 'int' => 1],
            'kobold' => ['dex' => 2, 'str' => -2]
        ];
        
        return $bonuses[$race] ?? [];
    }
    
    /**
     * Получение данных расы
     */
    private function getRaceData($race) {
        // Проверяем доступность cURL
        if (!function_exists('curl_init')) {
            logMessage('cURL не доступен', 'ERROR', ['race' => $race]);
            return null;
        }
        
        try {
            $url = $this->dndApiUrl . "/races/$race";
            $response = $this->makeApiRequest($url);
            
            if ($response) {
                return $response;
            }
        } catch (Exception $e) {
            logMessage('Failed to fetch race data from API', 'ERROR', ['race' => $race, 'error' => $e->getMessage()]);
        }
        
        // API недоступен
        logMessage('Не удалось получить данные расы', 'ERROR', ['race' => $race]);
        return null;
    }
    
    /**
     * Получение данных класса
     */
    private function getClassData($class) {
        // Проверяем доступность cURL
        if (!function_exists('curl_init')) {
            logMessage('cURL не доступен', 'ERROR', ['class' => $class]);
            return null;
        }
        
        try {
            $url = $this->dndApiUrl . "/classes/$class";
            $response = $this->makeApiRequest($url);
            
            if ($response) {
                return $response;
            }
        } catch (Exception $e) {
            logMessage('Failed to fetch class data from API', 'ERROR', ['class' => $class, 'error' => $e->getMessage()]);
        }
        
        // API недоступен
        logMessage('Не удалось получить данные класса', 'ERROR', ['class' => $class]);
        return null;
    }
    
    /**
     * Генерация имени
     */
    private function generateName($race, $gender) {
        $namesFile = __DIR__ . '/../pdf/dnd_race_names_ru_v2.json';
        
        if (file_exists($namesFile)) {
            $namesData = json_decode(file_get_contents($namesFile), true);
            
            if (isset($namesData['data'])) {
                foreach ($namesData['data'] as $raceData) {
                    if (strtolower($raceData['race']) === $race) {
                        if ($gender === 'random') {
                            $gender = rand(0, 1) ? 'male' : 'female';
                        }
                        
                        $nameList = [];
                        if ($gender === 'male' && !empty($raceData['male'])) {
                            $nameList = $raceData['male'];
                        } elseif ($gender === 'female' && !empty($raceData['female'])) {
                            $nameList = $raceData['female'];
                        }
                        
                        if (!empty($nameList)) {
                            return $nameList[array_rand($nameList)];
                        }
                    }
                }
            }
        }
        
        // Имена недоступны
        return "Неизвестный";
    }
    
    /**
     * Расчет параметров персонажа
     */
    private function calculateCharacterStats($params, $abilities, $raceData, $classData, $name) {
        $level = $params['level'];
        $proficiencyBonus = floor(($level - 1) / 4) + 2;
        
        // Расчет хитов
        $hitDie = $classData['hit_die'] ?? 8;
        $conModifier = floor(($abilities['con'] - 10) / 2);
        $hitPoints = $hitDie + $conModifier;
        
        // Дополнительные хиты за уровни
        for ($i = 2; $i <= $level; $i++) {
            $hitPoints += rand(1, $hitDie) + $conModifier;
        }
        
        // Расчет КД
        $armorClass = 10 + floor(($abilities['dex'] - 10) / 2);
        
        // Расчет инициативы
        $initiative = floor(($abilities['dex'] - 10) / 2);
        
        // Расчет попадания и урона
        $attackBonus = $proficiencyBonus + floor(($abilities['str'] - 10) / 2);
        $damage = $this->calculateDamage($classData, $abilities);
        
        // Получение основного оружия
        $mainWeapon = $this->getMainWeapon($classData);
        
        // Случайная профессия
        $occupation = $this->getRandomOccupation();
        
        // Случайное мировоззрение
        $alignment = $this->getRandomAlignment();
        
        return [
            'name' => $name,
            'race' => $params['race'],
            'class' => $params['class'],
            'level' => $level,
            'alignment' => $alignment,
            'gender' => $params['gender'] ?? 'random',
            'occupation' => $occupation,
            'abilities' => $abilities,
            'hit_points' => $hitPoints,
            'armor_class' => $armorClass,
            'speed' => $raceData['speed'] ?? 30,
            'initiative' => $initiative,
            'proficiency_bonus' => $proficiencyBonus,
            'attack_bonus' => '+' . $attackBonus,
            'damage' => $damage,
            'main_weapon' => $mainWeapon,
            'proficiencies' => $this->getProficiencies($classData, $raceData),
            'spells' => $this->getSpells($classData, $level, $abilities),
            'equipment' => $this->getEquipment($classData),
            'saving_throws' => $this->getSavingThrows($classData, $abilities, $proficiencyBonus)
        ];
    }
    
    /**
     * Расчет урона
     */
    private function calculateDamage($classData, $abilities) {
        $strModifier = floor(($abilities['str'] - 10) / 2);
        
        // Определяем оружие в зависимости от класса
        $weapons = [
            'fighter' => ['1d8 + ' . $strModifier, '1d10 + ' . $strModifier],
            'wizard' => ['1d4 + ' . $strModifier],
            'rogue' => ['1d6 + ' . $strModifier],
            'cleric' => ['1d8 + ' . $strModifier],
            'ranger' => ['1d8 + ' . $strModifier],
            'barbarian' => ['1d12 + ' . $strModifier],
            'bard' => ['1d6 + ' . $strModifier],
            'druid' => ['1d6 + ' . $strModifier],
            'monk' => ['1d6 + ' . $strModifier],
            'paladin' => ['1d8 + ' . $strModifier],
            'sorcerer' => ['1d4 + ' . $strModifier],
            'warlock' => ['1d6 + ' . $strModifier],
            'artificer' => ['1d8 + ' . $strModifier]
        ];
        
        $classWeapons = $weapons[$classData['index'] ?? 'fighter'] ?? $weapons['fighter'];
        return $classWeapons[array_rand($classWeapons)];
    }
    
    /**
     * Получение основного оружия
     */
    private function getMainWeapon($classData) {
        $weapons = [
            'fighter' => ['Длинный меч', 'Боевой топор', 'Копье', 'Молот'],
            'wizard' => ['Посох', 'Кинжал', 'Дубинка'],
            'rogue' => ['Короткий меч', 'Кинжал', 'Лук'],
            'cleric' => ['Булава', 'Молот', 'Щит'],
            'ranger' => ['Длинный лук', 'Короткий меч', 'Топор'],
            'barbarian' => ['Большой топор', 'Молот', 'Меч'],
            'bard' => ['Рапира', 'Кинжал', 'Лук'],
            'druid' => ['Посох', 'Серп', 'Кинжал'],
            'monk' => ['Кулаки', 'Посох', 'Кинжал'],
            'paladin' => ['Длинный меч', 'Молот', 'Копье'],
            'sorcerer' => ['Посох', 'Кинжал', 'Дубинка'],
            'warlock' => ['Кинжал', 'Посох', 'Меч'],
            'artificer' => ['Молот', 'Топор', 'Меч']
        ];
        
        $classWeapons = $weapons[$classData['index'] ?? 'fighter'] ?? $weapons['fighter'];
        return $classWeapons[array_rand($classWeapons)];
    }
    
    /**
     * Получение случайной профессии
     */
    private function getRandomOccupation() {
        $occupationsFile = __DIR__ . '/../pdf/d100_unique_traders.json';
        
        if (file_exists($occupationsFile)) {
            $data = json_decode(file_get_contents($occupationsFile), true);
            if (isset($data['data']['occupations'])) {
                $occupations = $data['data']['occupations'];
                return $occupations[array_rand($occupations)];
            }
        }
        
        // Профессии недоступны
        return "Авантюрист";
    }
    
    /**
     * Получение случайного мировоззрения
     */
    private function getRandomAlignment() {
        $alignments = [
            'Законно-добрый', 'Нейтрально-добрый', 'Хаотично-добрый',
            'Законно-нейтральный', 'Истинно-нейтральный', 'Хаотично-нейтральный',
            'Законно-злой', 'Нейтрально-злой', 'Хаотично-злой'
        ];
        return $alignments[array_rand($alignments)];
    }
    
    /**
     * Получение владений
     */
    private function getProficiencies($classData, $raceData) {
        $proficiencies = [];
        
        // Владения класса
        if (isset($classData['proficiencies'])) {
            foreach ($classData['proficiencies'] as $prof) {
                $proficiencies[] = $prof['name'] ?? $prof;
            }
        }
        
        // Владения расы
        if (isset($raceData['proficiencies'])) {
            foreach ($raceData['proficiencies'] as $prof) {
                $proficiencies[] = $prof['name'] ?? $prof;
            }
        }
        
        return array_unique($proficiencies);
    }
    
    /**
     * Получение заклинаний
     */
    private function getSpells($classData, $level, $abilities) {
        $spells = [];
        
        // Только для заклинателей
        $spellcasters = ['wizard', 'cleric', 'druid', 'sorcerer', 'warlock', 'bard', 'paladin', 'ranger'];
        
        if (in_array($classData['index'] ?? '', $spellcasters) && $level >= 1) {
            // Получаем заклинания для класса и уровня
            try {
                $url = $this->dndApiUrl . "/classes/" . $classData['index'] . "/spells";
                $response = $this->makeApiRequest($url);
                
                if ($response && isset($response['results'])) {
                    $spellCount = min(3, count($response['results']));
                    $selectedSpells = array_rand($response['results'], $spellCount);
                    
                    if (!is_array($selectedSpells)) {
                        $selectedSpells = [$selectedSpells];
                    }
                    
                    foreach ($selectedSpells as $index) {
                        $spells[] = $response['results'][$index]['name'];
                    }
                }
            } catch (Exception $e) {
                logMessage('Failed to fetch spells', 'WARNING', ['class' => $classData['index'], 'error' => $e->getMessage()]);
            }
        }
        
        return $spells;
    }
    
    /**
     * Получение снаряжения
     */
    private function getEquipment($classData) {
        $equipment = [];
        
        if (isset($classData['starting_equipment'])) {
            foreach ($classData['starting_equipment'] as $item) {
                $equipment[] = $item['item']['name'] ?? $item;
            }
        }
        
        return $equipment;
    }
    
    /**
     * Получение спасбросков
     */
    private function getSavingThrows($classData, $abilities, $proficiencyBonus) {
        $savingThrows = [];
        
        if (isset($classData['saving_throws'])) {
            foreach ($classData['saving_throws'] as $save) {
                $ability = $save['index'] ?? $save;
                $modifier = floor(($abilities[$ability] - 10) / 2) + $proficiencyBonus;
                $savingThrows[$ability] = '+' . $modifier;
            }
        }
        
        return $savingThrows;
    }
    
    /**
     * Генерация описания персонажа
     */
    private function generateDescription($character) {
        // Проверяем доступность OpenSSL для HTTPS запросов
        if (!OPENSSL_AVAILABLE) {
            logMessage('ERROR', 'OpenSSL не доступен для AI API');
            return "AI API недоступен: OpenSSL не поддерживается";
        }
        
        // Проверяем API ключ
        if (!$this->deepseekApiKey) {
            logMessage('ERROR', 'API ключ DeepSeek не найден');
            return "AI API недоступен: API ключ не найден";
        }
        
        $prompt = $this->buildDescriptionPrompt($character);
        
        try {
            $response = $this->callDeepSeek($prompt);
            if ($response) {
                logMessage('INFO', 'AI описание успешно сгенерировано');
                return $response;
            }
        } catch (Exception $e) {
            logMessage('ERROR', 'AI генерация описания не удалась: ' . $e->getMessage());
        }
        
        // Если AI не сработал
        logMessage('ERROR', 'AI генерация описания не удалась');
        return "AI API недоступен: не удалось сгенерировать описание";
    }
    
    /**
     * Генерация предыстории персонажа
     */
    private function generateBackground($character) {
        // Проверяем доступность OpenSSL для HTTPS запросов
        if (!OPENSSL_AVAILABLE) {
            logMessage('ERROR', 'OpenSSL не доступен для AI API');
            return "AI API недоступен: OpenSSL не поддерживается";
        }
        
        // Проверяем API ключ
        if (!$this->deepseekApiKey) {
            logMessage('ERROR', 'API ключ DeepSeek не найден');
            return "AI API недоступен: API ключ не найден";
        }
        
        $prompt = $this->buildBackgroundPrompt($character);
        
        try {
            $response = $this->callDeepSeek($prompt);
            if ($response) {
                logMessage('INFO', 'AI предыстория успешно сгенерирована');
                return $response;
            }
        } catch (Exception $e) {
            logMessage('ERROR', 'AI генерация предыстории не удалась: ' . $e->getMessage());
        }
        
        // Если AI не сработал
        logMessage('ERROR', 'AI генерация предыстории не удалась');
        return "AI API недоступен: не удалось сгенерировать предысторию";
    }
    
    /**
     * Формирование промпта для описания
     */
    private function buildDescriptionPrompt($character) {
        return "Создай краткое описание персонажа D&D 5e. Персонаж: {$character['name']}, {$character['race']} {$character['class']} {$character['level']} уровня. 
        
Характеристики: СИЛ {$character['abilities']['str']}, ЛОВ {$character['abilities']['dex']}, ТЕЛ {$character['abilities']['con']}, ИНТ {$character['abilities']['int']}, МДР {$character['abilities']['wis']}, ХАР {$character['abilities']['cha']}.
Профессия: {$character['occupation']}. Мировоззрение: {$character['alignment']}.

Опиши внешность, характер и особенности персонажа в 2-3 предложениях на русском языке.";
    }
    
    /**
     * Формирование промпта для предыстории
     */
    private function buildBackgroundPrompt($character) {
        return "Создай краткую предысторию персонажа D&D 5e. Персонаж: {$character['name']}, {$character['race']} {$character['class']} {$character['level']} уровня. 
        
Профессия: {$character['occupation']}. Мировоззрение: {$character['alignment']}.

Напиши краткую предысторию в 3-4 предложения на русском языке, объясняющую как персонаж стал {$character['class']} и что привело его к приключениям.";
    }
    
    /**
     * Вызов DeepSeek API
     */
    private function callDeepSeek($prompt) {
        if (!$this->deepseekApiKey) {
            return null;
        }
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты помощник для создания персонажей D&D 5e. Отвечай кратко и по делу на русском языке.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 300,
            'temperature' => 0.7
        ];
        
        $ch = curl_init(DEEPSEEK_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->deepseekApiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            }
        }
        
        return null;
    }
    
    /**
     * Выполнение API запроса
     */
    private function makeApiRequest($url) {
        // Проверяем доступность cURL
        if (!function_exists('curl_init')) {
            logMessage('cURL не доступен для API запроса', 'WARNING', ['url' => $url]);
            return null;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/3.1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        return null;
    }

}
?>
