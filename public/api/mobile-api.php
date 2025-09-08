<?php
// Заголовки только если это HTTP запрос (не CLI)
if (php_sapi_name() !== 'cli') {
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
}

require_once __DIR__ . '/../../config/config.php';

// Обработка preflight запросов (только для HTTP запросов)
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Авторизация отключена для мобильной версии (как в основных API)
// if (php_sapi_name() !== 'cli' && !isLoggedIn()) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'error' => 'Не авторизован']);
//     exit;
// }

// ===== ОБЪЯВЛЕНИЯ ФУНКЦИЙ =====

/**
 * Генерация персонажа для мобильной версии (полная версия)
 */
if (!function_exists('generateMobileCharacterFull')) {
function generateMobileCharacterFull($race, $characterClass, $level, $alignment, $gender, $language) {
    try {
        // Загружаем данные имен
        $namesData = null;
        $namesFile = __DIR__ . '/../../data/pdf/dnd_race_names_ru_v2.json';
        if (file_exists($namesFile)) {
            $namesContent = file_get_contents($namesFile);
            $namesData = json_decode($namesContent, true);
        }
        
        // Генерируем имя
        $name = generateCharacterName($race, $namesData);
        
        // Генерируем характеристики
        $abilities = generateAbilities();
        
        // Рассчитываем производные характеристики
        $hp = calculateHP($characterClass, $level, $abilities['con']);
        $ac = calculateAC($characterClass, $abilities['dex']);
        $speed = 30; // Базовая скорость
        $initiative = floor(($abilities['dex'] - 10) / 2);
        $proficiency_bonus = floor(($level - 1) / 4) + 2;
        
        // Рассчитываем бонус атаки
        $primary_ability = getPrimaryAbility($characterClass);
        $ability_modifier = floor(($abilities[$primary_ability] - 10) / 2);
        $attack_bonus = $proficiency_bonus + $ability_modifier;
        
        // Рассчитываем урон
        $damage = calculateDamage($characterClass, $abilities, $level);
        
        // Получаем основное оружие
        $main_weapon = getMainWeapon($characterClass);
        
        // Получаем спасброски
        $saving_throws = getSavingThrows($characterClass, $abilities, $proficiency_bonus);
        
        // Получаем владения
        $proficiencies = getProficiencies($characterClass);
        
        // Генерируем описание с AI (если доступен)
        $description = generateCharacterDescriptionWithAI($race, $characterClass, $level);
        
        // Получаем особенности класса
        $features = getClassFeatures($characterClass, $level);
        
        // Получаем снаряжение
        $equipment = getClassEquipment($characterClass);
        
        // Получаем заклинания
        $spells = getClassSpells($characterClass, $level);
        
        // Генерируем предысторию
        $background = generateCharacterBackground($race, $characterClass, $level);
        
        // Переводим названия рас и классов
        $race_display = translateRace($race);
        $class_display = translateClass($characterClass);
        
        $character = [
            'name' => $name,
            'race' => $race_display,
            'class' => $class_display,
            'level' => $level,
            'alignment' => translateAlignment($alignment),
            'gender' => translateGender($gender),
            'occupation' => getRandomOccupation(),
            'abilities' => $abilities,
            'hit_points' => $hp,
            'armor_class' => $ac,
            'speed' => $speed,
            'initiative' => $initiative,
            'proficiency_bonus' => $proficiency_bonus,
            'attack_bonus' => $attack_bonus,
            'damage' => $damage,
            'main_weapon' => $main_weapon,
            'proficiencies' => $proficiencies,
            'saving_throws' => $saving_throws,
            'features' => $features,
            'equipment' => $equipment,
            'spells' => $spells,
            'description' => $description,
            'background' => $background,
            'languages' => ['Общий', translateRaceLanguage($race)]
        ];
        
        return [
            'success' => true,
            'character' => $character,
            'language' => $language,
            'api_info' => [
                'dnd_api_used' => false,
                'ai_api_used' => false,
                'data_source' => 'Local fallback data',
                'cache_info' => 'No caching used'
            ]
        ];
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile character generation failed: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'details' => 'Generation failed due to local data processing error'
        ];
    }
}
}

/**
 * Генерация персонажа для мобильной версии (упрощенная версия)
 */
if (!function_exists('generateMobileCharacter')) {
function generateMobileCharacter($race, $characterClass, $level) {
    try {
        // Загружаем данные имен
        $namesData = null;
        $namesFile = __DIR__ . '/../../data/pdf/dnd_race_names_ru_v2.json';
        if (file_exists($namesFile)) {
            $namesContent = file_get_contents($namesFile);
            $namesData = json_decode($namesContent, true);
        }
        
        // Генерируем имя
        $name = generateCharacterName($race, $namesData);
        
        // Генерируем характеристики
        $abilities = generateAbilities();
        
        // Рассчитываем производные характеристики
        $hp = calculateHP($characterClass, $level, $abilities['con']);
        $ac = calculateAC($characterClass, $abilities['dex']);
        
        // Генерируем описание с AI
        $description = generateCharacterDescriptionWithAI($race, $characterClass, $level);
        
        // Получаем особенности класса
        $features = getClassFeatures($characterClass, $level);
        
        // Получаем снаряжение
        $equipment = getClassEquipment($characterClass);
        
        // Получаем заклинания
        $spells = getClassSpells($characterClass, $level);
        
        $character = [
            'name' => $name,
            'race' => $race,
            'class' => $characterClass,
            'level' => $level,
            'abilities' => $abilities,
            'hp' => $hp,
            'ac' => $ac,
            'description' => $description,
            'features' => $features,
            'equipment' => $equipment,
            'spells' => $spells
        ];
        
        return $character;
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile character generation failed: ' . $e->getMessage());
        throw $e;
    }
}
}

/**
 * Генерация противника для мобильной версии
 */
if (!function_exists('generateMobileEnemy')) {
function generateMobileEnemy($threat_level, $count, $enemy_type, $environment, $use_ai) {
    try {
        $enemies = [];
        
        for ($i = 0; $i < $count; $i++) {
            $enemy = generateSimpleEnemy($threat_level, $enemy_type, $environment);
            $enemies[] = $enemy;
        }
        
        return $enemies;
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile enemy generation failed: ' . $e->getMessage());
        throw $e;
    }
}
}

/**
 * Генерация простого противника
 */
if (!function_exists('generateSimpleEnemy')) {
function generateSimpleEnemy($threat_level, $enemy_type, $environment) {
    $enemies = [
        'easy' => [
            'any' => ['Гоблин', 'Крыса', 'Волк', 'Змея'],
            'humanoid' => ['Гоблин', 'Бандит', 'Культист'],
            'beast' => ['Волк', 'Медведь', 'Орёл'],
            'undead' => ['Скелет', 'Зомби'],
            'fiend' => ['Имп', 'Квазит'],
            'dragon' => ['Дракончик-вылупленец']
        ],
        'medium' => [
            'any' => ['Орк', 'Тролль', 'Огр', 'Гиппогриф'],
            'humanoid' => ['Орк', 'Хобгоблин', 'Берсерк'],
            'beast' => ['Медведь', 'Тигр', 'Гиппогриф'],
            'undead' => ['Гуль', 'Призрак'],
            'fiend' => ['Барбазу', 'Костяной дьявол'],
            'dragon' => ['Молодой дракон']
        ],
        'hard' => [
            'any' => ['Тролль', 'Огр', 'Гигант', 'Дракон'],
            'humanoid' => ['Огр', 'Гигант', 'Друид'],
            'beast' => ['Гигантский медведь', 'Саблезубый тигр'],
            'undead' => ['Лич', 'Драконий зомби'],
            'fiend' => ['Балор', 'Пит-фиенд'],
            'dragon' => ['Взрослый дракон']
        ]
    ];
    
    $enemyList = $enemies[$threat_level][$enemy_type] ?? $enemies[$threat_level]['any'];
    $enemyName = $enemyList[array_rand($enemyList)];
    
    // Базовые характеристики
    $stats = [
        'easy' => ['hp' => rand(10, 25), 'ac' => rand(12, 14), 'attack' => rand(3, 5)],
        'medium' => ['hp' => rand(25, 50), 'ac' => rand(14, 16), 'attack' => rand(5, 8)],
        'hard' => ['hp' => rand(50, 100), 'ac' => rand(16, 18), 'attack' => rand(8, 12)]
    ];
    
    $enemyStats = $stats[$threat_level];
    
    return [
        'name' => $enemyName,
        'type' => $enemy_type,
        'threat_level' => $threat_level,
        'environment' => $environment,
        'hp' => $enemyStats['hp'],
        'ac' => $enemyStats['ac'],
        'attack_bonus' => $enemyStats['attack'],
        'damage' => rand(1, 6) + floor($enemyStats['attack'] / 2),
        'description' => generateEnemyDescription($enemyName, $enemy_type, $environment),
        'tactics' => generateEnemyTactics($enemyName, $enemy_type),
        'speed' => '30 ft'
    ];
}
}

/**
 * Генерация описания противника
 */
if (!function_exists('generateEnemyDescription')) {
function generateEnemyDescription($name, $type, $environment) {
    $descriptions = [
        'Гоблин' => 'Маленький, злобный гуманоид с острыми зубами и хитрыми глазами. Обычно вооружен коротким мечом и луком.',
        'Орк' => 'Крупный, мускулистый гуманоид с зеленой кожей и свирепым выражением лица. Предпочитает тяжелое оружие.',
        'Тролль' => 'Огромное, регенерирующее существо с длинными когтями и острыми зубами. Очень опасно в ближнем бою.',
        'Дракон' => 'Могучее, чешуйчатое существо с крыльями и смертоносным дыханием. Один из самых опасных противников.',
        'Скелет' => 'Анимированные кости, движимые темной магией. Неутомимые и бесстрашные.',
        'Зомби' => 'Нежить, созданная из трупа. Медленные, но упорные и заразные.'
    ];
    
    return $descriptions[$name] ?? "{$name} - {$type} существо, обитающее в {$environment} среде.";
}
}

/**
 * Генерация тактики противника
 */
if (!function_exists('generateEnemyTactics')) {
function generateEnemyTactics($name, $type) {
    $tactics = [
        'Гоблин' => 'Использует скрытность и засады. Предпочитает атаковать издалека из лука.',
        'Орк' => 'Атакует в лоб с яростью. Использует численное преимущество.',
        'Тролль' => 'Регенерирует раны. Нужно атаковать огнем или кислотой.',
        'Дракон' => 'Использует полет и дыхание. Очень умный и тактически подкованный.',
        'Скелет' => 'Атакует без устали. Не боится смерти.',
        'Зомби' => 'Медленно приближается, пытаясь схватить и заразить.'
    ];
    
    return $tactics[$name] ?? "{$name} использует стандартную тактику для {$type} существ.";
}
}

/**
 * Запрос к AI для мобильной версии
 */
if (!function_exists('askMobileAI')) {
function askMobileAI($question) {
    try {
        // Используем AI напрямую через API
        require_once __DIR__ . '/../../config/config.php';
        
        $api_key = getApiKey('deepseek');
        if (empty($api_key)) {
            throw new Exception('API ключ не настроен');
        }
        
        $url = 'https://api.deepseek.com/v1/chat/completions';
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Ты помощник по D&D. Отвечай на русском языке кратко и по делу.'
                ],
                [
                    'role' => 'user',
                    'content' => $question
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_key
                ],
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            throw new Exception('Не удалось подключиться к API');
        }
        
        $result = json_decode($response, true);
        if (isset($result['error'])) {
            throw new Exception($result['error']['message'] ?? 'Ошибка API');
        }
        
        $ai_response = $result['choices'][0]['message']['content'] ?? 'Ответ не получен';
        
        return [
            'response' => $ai_response,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile AI chat failed: ' . $e->getMessage());
        throw new Exception('AI недоступен: ' . $e->getMessage());
    }
}
}


/**
 * Генерация имени персонажа
 */
if (!function_exists('generateCharacterName')) {
function generateCharacterName($race, $namesData) {
    $race = strtolower($race);
    
    if ($namesData && isset($namesData['data'])) {
        foreach ($namesData['data'] as $raceData) {
            if (strtolower($raceData['race']) === $race) {
                $gender = rand(0, 1) ? 'male' : 'female';
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
        }
    }
    
    // Имена недоступны
    return "Неизвестный";
}
}

/**
 * Генерация характеристик
 */
if (!function_exists('generateAbilities')) {
function generateAbilities() {
    $abilities = [];
    $abilityNames = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
    
    foreach ($abilityNames as $ability) {
        $rolls = [];
        for ($i = 0; $i < 4; $i++) {
            $rolls[] = rand(1, 6);
        }
        sort($rolls);
        array_shift($rolls); // Убираем минимальный
        $abilities[$ability] = array_sum($rolls);
    }
    
    return $abilities;
}
}

/**
 * Расчет хитов
 */
if (!function_exists('calculateHP')) {
function calculateHP($characterClass, $level, $con) {
    $hitDie = getHitDie($characterClass);
    $conBonus = floor(($con - 10) / 2);
    
    $baseHP = $hitDie + $conBonus;
    $additionalHP = 0;
    
    for ($i = 2; $i <= $level; $i++) {
        $additionalHP += rand(1, $hitDie) + $conBonus;
    }
    
    return max(1, $baseHP + $additionalHP);
}
}

/**
 * Получение кости хитов класса
 */
if (!function_exists('getHitDie')) {
function getHitDie($characterClass) {
    $hitDice = [
        'fighter' => 10,
        'wizard' => 6,
        'rogue' => 8,
        'cleric' => 8,
        'ranger' => 10,
        'barbarian' => 12,
        'bard' => 8,
        'druid' => 8,
        'monk' => 8,
        'paladin' => 10,
        'sorcerer' => 6,
        'warlock' => 8,
        'artificer' => 8
    ];
    
    return $hitDice[$characterClass] ?? 8;
}
}

/**
 * Расчет класса доспеха
 */
if (!function_exists('calculateAC')) {
function calculateAC($characterClass, $dex) {
    $dexBonus = floor(($dex - 10) / 2);
    
    $armorProficiencies = getArmorProficiencies($characterClass);
    
    if (in_array('heavy', $armorProficiencies)) {
        return 16 + min(2, $dexBonus); // Кольчуга
    } elseif (in_array('medium', $armorProficiencies)) {
        return 14 + min(2, $dexBonus); // Кожаный доспех
    } else {
        return 10 + $dexBonus; // Без доспеха
    }
}
}

/**
 * Получение владений доспехами класса
 */
if (!function_exists('getArmorProficiencies')) {
function getArmorProficiencies($characterClass) {
    $proficiencies = [
        'fighter' => ['light', 'medium', 'heavy'],
        'wizard' => [],
        'rogue' => ['light'],
        'cleric' => ['light', 'medium'],
        'ranger' => ['light', 'medium'],
        'barbarian' => ['light', 'medium'],
        'bard' => ['light'],
        'druid' => ['light', 'medium'],
        'monk' => [],
        'paladin' => ['light', 'medium', 'heavy'],
        'sorcerer' => [],
        'warlock' => ['light'],
        'artificer' => ['light', 'medium']
    ];
    
    return $proficiencies[$characterClass] ?? [];
}
}

/**
 * Генерация описания персонажа с AI
 */
if (!function_exists('generateCharacterDescriptionWithAI')) {
function generateCharacterDescriptionWithAI($race, $characterClass, $level) {
    try {
        // Используем основной AI сервис
        require_once __DIR__ . '/../../app/Services/ai-service.php';
        
        $aiService = new AiService();
        $character = ['race' => $race, 'class' => $characterClass, 'level' => $level];
        $result = $aiService->generateCharacterDescription($character);
        
        if (isset($result['error'])) {
            throw new Exception($result['error']);
        }
        
        return $result;
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile AI description failed: ' . $e->getMessage());
        // Возвращаем fallback описание вместо исключения
        return generateFallbackDescription($race, $characterClass, $level);
    }
}
}

/**
 * Генерация fallback описания персонажа
 */
if (!function_exists('generateFallbackDescription')) {
function generateFallbackDescription($race, $characterClass, $level) {
    $race_display = translateRace($race);
    $class_display = translateClass($characterClass);
    
    $descriptions = [
        'dragonborn' => 'Чешуйчатая кожа цвета вороненой стали и пронзительные глаза цвета расплавленного золота выдают драконью природу этого существа.',
        'human' => 'Среднего роста и телосложения, с решительным взглядом и уверенной походкой.',
        'elf' => 'Стройная фигура, заостренные уши и изящные черты лица выдают эльфийское происхождение.',
        'dwarf' => 'Крепкое телосложение, густая борода и упорный взгляд характерны для этого представителя горного народа.',
        'halfling' => 'Невысокий рост, кудрявые волосы и жизнерадостное выражение лица делают этого персонажа узнаваемым.',
        'gnome' => 'Маленький рост, острые черты лица и любопытный взгляд выдают гномье происхождение.',
        'tiefling' => 'Рога, хвост и необычный цвет кожи выдают инфернальное происхождение этого персонажа.',
        'half-elf' => 'Сочетание эльфийской грации и человеческой решительности в чертах лица.',
        'half-orc' => 'Крупное телосложение, выступающие клыки и мускулистая фигура выдают орчье происхождение.'
    ];
    
    $base_description = $descriptions[$race] ?? "Представитель расы {$race_display}.";
    
    $class_descriptions = [
        'monk' => ' Плавные движения и собранная осанка говорят о дисциплине, привитой в монастыре.',
        'fighter' => ' Военная выправка и уверенность в движениях выдают опытного воина.',
        'wizard' => ' В глазах читается мудрость и жажда знаний, а в руках чувствуется магическая сила.',
        'rogue' => ' Быстрые, точные движения и внимательный взгляд выдают опытного плута.',
        'cleric' => ' Спокойствие и внутренняя сила говорят о глубокой вере и связи с божеством.',
        'ranger' => ' Легкая походка и зоркий взгляд выдают опытного следопыта и охотника.',
        'barbarian' => ' Дикая сила и неукротимый дух читаются в каждом движении.',
        'bard' => ' Артистичная осанка и обаятельная улыбка выдают талантливого артиста.',
        'druid' => ' Связь с природой чувствуется в каждом движении и взгляде.',
        'paladin' => ' Благородная осанка и внутренняя сила говорят о священном долге.',
        'sorcerer' => ' В глазах плещется магическая энергия, а в движениях чувствуется врожденная сила.',
        'warlock' => ' Загадочная аура и необычная сила выдают связь с потусторонними силами.',
        'artificer' => ' Изобретательный взгляд и умелые руки выдают мастера-творца.'
    ];
    
    $class_description = $class_descriptions[$characterClass] ?? " Представитель класса {$class_display}.";
    
    return $base_description . $class_description;
}
}


/**
 * Получение особенностей класса
 */
if (!function_exists('getClassFeatures')) {
function getClassFeatures($characterClass, $level) {
    $baseFeatures = [
        'fighter' => ['Боевой стиль', 'Second Wind'],
        'wizard' => ['Заклинания', 'Восстановление заклинаний'],
        'rogue' => ['Скрытность', 'Sneak Attack'],
        'cleric' => ['Заклинания', 'Божественный домен'],
        'ranger' => ['Любимый враг', 'Естественный исследователь'],
        'barbarian' => ['Ярость', 'Защита без доспехов'],
        'bard' => ['Вдохновение барда', 'Песнь отдыха'],
        'druid' => ['Дикий облик', 'Друидский'],
        'monk' => ['Безоружная защита', 'Боевые искусства'],
        'paladin' => ['Божественное чувство', 'Божественное здоровье'],
        'sorcerer' => ['Магическое происхождение', 'Метамагия'],
        'warlock' => ['Пакт с покровителем', 'Мистические арканумы'],
        'artificer' => ['Магическое изобретение', 'Инфузия']
    ];
    
    $features = $baseFeatures[$characterClass] ?? ['Базовые способности'];
    
    if ($level >= 2) {
        $features[] = 'Дополнительная атака';
    }
    if ($level >= 5) {
        $features[] = 'Улучшенная критическая атака';
    }
    
    return $features;
}
}

/**
 * Получение снаряжения класса
 */
if (!function_exists('getClassEquipment')) {
function getClassEquipment($characterClass) {
    $equipment = [];
    
    // Доспехи
    $armorProficiencies = getArmorProficiencies($characterClass);
    if (in_array('heavy', $armorProficiencies)) {
        $equipment[] = 'Кольчуга';
    } elseif (in_array('medium', $armorProficiencies)) {
        $equipment[] = 'Кожаный доспех';
    } else {
        $equipment[] = 'Одежда';
    }
    
    // Оружие
    $weaponProficiencies = getWeaponProficiencies($characterClass);
    if (in_array('martial', $weaponProficiencies)) {
        $weapons = ['Длинный меч', 'Топор', 'Копье'];
        $equipment[] = $weapons[array_rand($weapons)];
    } else {
        $weapons = ['Короткий меч', 'Кинжал', 'Булава'];
        $equipment[] = $weapons[array_rand($weapons)];
    }
    
    // Щит
    if (in_array('shield', $weaponProficiencies)) {
        $equipment[] = 'Щит';
    }
    
    // Снаряжение
    $equipment[] = 'Рюкзак';
    $equipment[] = 'Веревка (50 футов)';
    $equipment[] = 'Трутница';
    
    // Зелья
    $equipment[] = 'Зелье (генерируется отдельно)';
    
    // Деньги
    $gold = rand(5, 25);
    $equipment[] = "{$gold} золотых монет";
    
    return $equipment;
}
}

/**
 * Получение владений оружием класса
 */
if (!function_exists('getWeaponProficiencies')) {
function getWeaponProficiencies($characterClass) {
    $proficiencies = [
        'fighter' => ['simple', 'martial', 'shield'],
        'wizard' => ['daggers', 'quarterstaffs', 'light_crossbows'],
        'rogue' => ['simple', 'shortswords', 'longswords'],
        'cleric' => ['simple'],
        'ranger' => ['simple', 'martial'],
        'barbarian' => ['simple', 'martial'],
        'bard' => ['simple', 'longswords', 'rapiers'],
        'druid' => ['simple'],
        'monk' => ['simple', 'shortswords'],
        'paladin' => ['simple', 'martial', 'shield'],
        'sorcerer' => ['daggers', 'quarterstaffs', 'light_crossbows'],
        'warlock' => ['simple'],
        'artificer' => ['simple']
    ];
    
    return $proficiencies[$characterClass] ?? ['simple'];
}
}

/**
 * Получение заклинаний класса
 */
if (!function_exists('getClassSpells')) {
function getClassSpells($characterClass, $level) {
    $spellcasters = ['wizard', 'cleric', 'ranger', 'bard', 'druid', 'paladin', 'sorcerer', 'warlock', 'artificer'];
    
    if (!in_array($characterClass, $spellcasters)) {
        return [];
    }
    
    $spells = [];
    
    // Заклинания 1 уровня
    if ($level >= 1) {
        $level1_spells = [
            'Свет',
            'Магическая стрела',
            'Лечение ран',
            'Щит',
            'Обнаружение магии',
            'Компрессионная волна'
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
    
    // Заклинания 2 уровня
    if ($level >= 3) {
        $level2_spells = ['Огненный шар', 'Невидимость', 'Улучшение способностей'];
        $spells[] = $level2_spells[array_rand($level2_spells)];
    }
    
    // Заклинания 3 уровня
    if ($level >= 5) {
        $level3_spells = ['Молния', 'Полет', 'Огненный шар'];
        $spells[] = $level3_spells[array_rand($level3_spells)];
    }
    
    return $spells;
}
}

/**
 * Получение основной характеристики класса
 */
if (!function_exists('getPrimaryAbility')) {
function getPrimaryAbility($characterClass) {
    $primary_abilities = [
        'fighter' => 'str',
        'wizard' => 'int',
        'rogue' => 'dex',
        'cleric' => 'wis',
        'ranger' => 'dex',
        'barbarian' => 'str',
        'bard' => 'cha',
        'druid' => 'wis',
        'monk' => 'dex',
        'paladin' => 'str',
        'sorcerer' => 'cha',
        'warlock' => 'cha',
        'artificer' => 'int'
    ];
    
    return $primary_abilities[$characterClass] ?? 'str';
}
}

/**
 * Расчет урона
 */
if (!function_exists('calculateDamage')) {
function calculateDamage($characterClass, $abilities, $level) {
    $primary_ability = getPrimaryAbility($characterClass);
    $ability_modifier = floor(($abilities[$primary_ability] - 10) / 2);
    
    // Базовый урон оружия
    $base_damage = [
        'fighter' => '1d8',
        'wizard' => '1d6',
        'rogue' => '1d4',
        'cleric' => '1d8',
        'ranger' => '1d8',
        'barbarian' => '1d12',
        'bard' => '1d8',
        'druid' => '1d6',
        'monk' => '1d6',
        'paladin' => '1d8',
        'sorcerer' => '1d6',
        'warlock' => '1d4',
        'artificer' => '1d8'
    ];
    
    $damage = $base_damage[$characterClass] ?? '1d6';
    
    // Форматируем модификатор
    if ($ability_modifier >= 0) {
        return $damage . '+' . $ability_modifier;
    } else {
        return $damage . $ability_modifier;
    }
}
}

/**
 * Получение основного оружия
 */
if (!function_exists('getMainWeapon')) {
function getMainWeapon($characterClass) {
    $weapons = [
        'fighter' => 'Меч',
        'wizard' => 'Посох',
        'rogue' => 'Кинжал',
        'cleric' => 'Булава',
        'ranger' => 'Лук',
        'barbarian' => 'Топор',
        'bard' => 'Рапира',
        'druid' => 'Посох',
        'monk' => 'Кулаки',
        'paladin' => 'Меч',
        'sorcerer' => 'Посох',
        'warlock' => 'Кинжал',
        'artificer' => 'Молот'
    ];
    
    return $weapons[$characterClass] ?? 'Меч';
}
}

/**
 * Получение спасбросков
 */
if (!function_exists('getSavingThrows')) {
function getSavingThrows($characterClass, $abilities, $proficiency_bonus) {
    $saving_throws = [];
    
    // Определяем спасброски по классу
    $class_saving_throws = [
        'fighter' => ['str', 'con'],
        'wizard' => ['int', 'wis'],
        'rogue' => ['dex', 'int'],
        'cleric' => ['wis', 'cha'],
        'ranger' => ['str', 'dex'],
        'barbarian' => ['str', 'con'],
        'bard' => ['dex', 'cha'],
        'druid' => ['int', 'wis'],
        'monk' => ['str', 'dex'],
        'paladin' => ['wis', 'cha'],
        'sorcerer' => ['con', 'cha'],
        'warlock' => ['wis', 'cha'],
        'artificer' => ['con', 'int']
    ];
    
    $proficient_throws = $class_saving_throws[$characterClass] ?? ['str', 'dex'];
    
    // Рассчитываем все спасброски
    $ability_names = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
    $ability_display = ['СИЛ', 'ЛОВ', 'ТЕЛ', 'ИНТ', 'МДР', 'ХАР'];
    
    foreach ($ability_names as $index => $ability) {
        $modifier = floor(($abilities[$ability] - 10) / 2);
        $bonus = in_array($ability, $proficient_throws) ? $modifier + $proficiency_bonus : $modifier;
        $saving_throws[$ability_display[$index]] = $bonus;
    }
    
    return $saving_throws;
}
}

/**
 * Получение владений
 */
if (!function_exists('getProficiencies')) {
function getProficiencies($characterClass) {
    $proficiencies = [
        'fighter' => ['Все доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
        'wizard' => ['Кинжалы', 'Дартсы', 'Пращи', 'Посохи', 'Легкие арбалеты'],
        'rogue' => ['Легкие доспехи', 'Простое оружие', 'Ручные арбалеты', 'Длинные мечи', 'Рапиры', 'Короткие мечи'],
        'cleric' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие'],
        'ranger' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
        'barbarian' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
        'bard' => ['Легкие доспехи', 'Простое оружие', 'Ручные арбалеты', 'Длинные мечи', 'Рапиры', 'Короткие мечи'],
        'druid' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Булавы', 'Кинжалы', 'Дартсы', 'Джевелины', 'Булавы', 'Посохи', 'Пращи', 'Копья'],
        'monk' => ['Простое оружие', 'Короткие мечи'],
        'paladin' => ['Все доспехи', 'Щиты', 'Простое оружие', 'Воинское оружие'],
        'sorcerer' => ['Кинжалы', 'Дартсы', 'Пращи', 'Посохи', 'Легкие арбалеты'],
        'warlock' => ['Легкие доспехи', 'Простое оружие'],
        'artificer' => ['Легкие доспехи', 'Средние доспехи', 'Щиты', 'Простое оружие']
    ];
    
    return $proficiencies[$characterClass] ?? ['Простое оружие'];
}
}

/**
 * Генерация предыстории персонажа
 */
if (!function_exists('generateCharacterBackground')) {
function generateCharacterBackground($race, $characterClass, $level) {
    $backgrounds = [
        'Аколит' => 'Вы провели свою жизнь в служении храму, изучая священные тексты и выполняя религиозные обряды.',
        'Преступник' => 'Вы жили вне закона, зарабатывая на жизнь воровством, мошенничеством и другими незаконными делами.',
        'Народный герой' => 'Вы были простым человеком, пока не стали героем в глазах народа, защитив их от угрозы.',
        'Благородный' => 'Вы родились в знатной семье и выросли в роскоши, окруженные слугами и богатством.',
        'Солдат' => 'Вы служили в армии, участвовали в сражениях и знаете дисциплину военной жизни.',
        'Мудрец' => 'Вы посвятили свою жизнь изучению знаний, собирая информацию и исследуя тайны мира.',
        'Моряк' => 'Вы провели большую часть жизни на кораблях, путешествуя по морям и океанам.',
        'Бродяга' => 'Вы жили на улицах, выживая благодаря своей хитрости и умению находить возможности.'
    ];
    
    return $backgrounds[array_rand($backgrounds)];
}
}

/**
 * Получение случайной профессии
 */
if (!function_exists('getRandomOccupation')) {
function getRandomOccupation() {
    $occupations = [
        'Кузнец', 'Торговец', 'Охотник', 'Рыбак', 'Фермер', 'Шахтер', 
        'Плотник', 'Каменщик', 'Повар', 'Трактирщик', 'Ткач', 'Авантюрист',
        'Лекарь', 'Писарь', 'Стражник', 'Проводник', 'Картограф', 'Алхимик'
    ];
    
    return $occupations[array_rand($occupations)];
}
}

/**
 * Перевод расы
 */
if (!function_exists('translateRace')) {
function translateRace($race) {
    $translations = [
        'human' => 'Человек',
        'elf' => 'Эльф',
        'dwarf' => 'Дварф',
        'halfling' => 'Полурослик',
        'dragonborn' => 'Драконорождённый',
        'gnome' => 'Гном',
        'half-elf' => 'Полуэльф',
        'half-orc' => 'Полуорк',
        'tiefling' => 'Тифлинг'
    ];
    
    return $translations[$race] ?? ucfirst($race);
}
}

/**
 * Перевод класса
 */
if (!function_exists('translateClass')) {
function translateClass($class) {
    $translations = [
        'fighter' => 'Воин',
        'wizard' => 'Волшебник',
        'rogue' => 'Плут',
        'cleric' => 'Жрец',
        'ranger' => 'Следопыт',
        'barbarian' => 'Варвар',
        'bard' => 'Бард',
        'druid' => 'Друид',
        'monk' => 'Монах',
        'paladin' => 'Паладин',
        'sorcerer' => 'Чародей',
        'warlock' => 'Колдун',
        'artificer' => 'Артифисер'
    ];
    
    return $translations[$class] ?? ucfirst($class);
}
}

/**
 * Перевод мировоззрения
 */
if (!function_exists('translateAlignment')) {
function translateAlignment($alignment) {
    $translations = [
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
    
    return $translations[$alignment] ?? 'Нейтральный';
}
}

/**
 * Перевод пола
 */
if (!function_exists('translateGender')) {
function translateGender($gender) {
    if ($gender === 'random') {
        $gender = rand(0, 1) ? 'male' : 'female';
    }
    
    return $gender === 'male' ? 'Мужчина' : 'Женщина';
}
}

/**
 * Перевод языка расы
 */
if (!function_exists('translateRaceLanguage')) {
function translateRaceLanguage($race) {
    $languages = [
        'human' => 'Общий',
        'elf' => 'Эльфийский',
        'dwarf' => 'Дварфский',
        'halfling' => 'Полуросличий',
        'dragonborn' => 'Драконий',
        'gnome' => 'Гномский',
        'half-elf' => 'Эльфийский',
        'half-orc' => 'Орчий',
        'tiefling' => 'Инфернальный'
    ];
    
    return $languages[$race] ?? 'Общий';
}
}

// ===== ОСНОВНОЙ КОД =====

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Неизвестное действие'];

try {
    switch ($action) {
        case 'generate_character':
            $race = $_POST['race'] ?? '';
            $characterClass = $_POST['class'] ?? '';
            $level = (int)($_POST['level'] ?? 1);
            $alignment = $_POST['alignment'] ?? 'neutral';
            $gender = $_POST['gender'] ?? 'random';
            $language = $_POST['language'] ?? 'ru';
            
            if (empty($race) || empty($characterClass)) {
                $response = ['success' => false, 'message' => 'Не указаны раса или класс'];
                break;
            }
            
            // Используем тот же CharacterGeneratorV4 что и в ПК версии
            require_once __DIR__ . '/generate-characters.php';
            $generator = new CharacterGeneratorV4();
            
            $params = [
                'race' => $race,
                'class' => $characterClass,
                'level' => $level,
                'alignment' => $alignment,
                'gender' => $gender,
                'language' => $language,
                'use_ai' => 'on' // AI всегда включен для мобильной версии
            ];
            
            $result = $generator->generateCharacter($params);
            
            // Если API недоступен, используем упрощенную генерацию с полными данными
            if (!$result['success'] && isset($result['error']) && strpos($result['error'], 'API недоступен') !== false) {
                $response = generateMobileCharacterFull($race, $characterClass, $level, $alignment, $gender, $language);
            } else {
                $response = $result; // Возвращаем полный результат как в ПК версии
            }
            break;
            
        case 'generate_enemy':
            $threat_level = $_POST['threat_level'] ?? '';
            $count = (int)($_POST['count'] ?? 1);
            $enemy_type = $_POST['enemy_type'] ?? '';
            $environment = $_POST['environment'] ?? '';
            $use_ai = isset($_POST['use_ai']) && $_POST['use_ai'] === 'on';
            
            if (empty($threat_level)) {
                $response = ['success' => false, 'message' => 'Не указан уровень угрозы'];
                break;
            }
            
            // Используем тот же EnemyGenerator что и в ПК версии
            require_once __DIR__ . '/generate-enemies.php';
            $generator = new EnemyGenerator();
            
            $params = [
                'threat_level' => $threat_level,
                'count' => $count,
                'enemy_type' => $enemy_type,
                'environment' => $environment,
                'use_ai' => $use_ai ? 'on' : 'off'
            ];
            
            $result = $generator->generateEnemies($params);
            $response = $result; // Возвращаем полный результат как в ПК версии
            break;
            
        case 'ai_chat':
            $question = $_POST['question'] ?? '';
            $pdf_content = $_POST['pdf_content'] ?? '';
            
            if (empty($question)) {
                $response = ['success' => false, 'message' => 'Не указан вопрос'];
                break;
            }
            
            // Используем тот же AI чат что и в ПК версии
            require_once __DIR__ . '/ai-chat.php';
            
            // Подготавливаем данные для AI чата
            $chatData = [
                'message' => $question,
                'pdf_content' => $pdf_content
            ];
            
            // Вызываем AI чат напрямую
            $aiResponse = processAiChat($chatData);
            $response = $aiResponse; // Возвращаем полный результат как в ПК версии
            break;
            
        case 'generate_potions':
            $rarity = $_POST['rarity'] ?? '';
            $type = $_POST['type'] ?? '';
            $count = (int)($_POST['count'] ?? 1);
            $language = $_POST['language'] ?? 'ru';
            
            // Используем тот же генератор зелий что и в ПК версии
            require_once __DIR__ . '/generate-potions.php';
            
            $params = [
                'rarity' => $rarity,
                'type' => $type,
                'count' => $count,
                'language' => $language
            ];
            
            $result = generatePotions($params);
            $response = $result; // Возвращаем полный результат как в ПК версии
            break;
            
        case 'generate_taverns':
            $biome = $_POST['biome'] ?? '';
            $rarity = $_POST['rarity'] ?? '';
            $count = (int)($_POST['count'] ?? 1);
            $use_ai = isset($_POST['use_ai']) && $_POST['use_ai'] === 'on';
            
            // Используем тот же генератор таверн что и в ПК версии
            require_once __DIR__ . '/generate-taverns.php';
            
            $params = [
                'biome' => $biome,
                'rarity' => $rarity,
                'count' => $count,
                'use_ai' => $use_ai ? 'on' : 'off'
            ];
            
            $result = generateTaverns($params);
            $response = $result; // Возвращаем полный результат как в ПК версии
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Неизвестное действие: ' . $action];
    }
} catch (Exception $e) {
    logMessage('ERROR', 'Mobile API error: ' . $e->getMessage());
    $response = ['success' => false, 'error' => $e->getMessage()];
}

// Выводим ответ
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>