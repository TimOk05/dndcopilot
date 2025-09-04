<?php
// Заголовки только если это HTTP запрос (не CLI)
if (php_sapi_name() !== 'cli') {
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
}

require_once 'config.php';

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Проверяем авторизацию для мобильной версии (только для HTTP запросов)
if (php_sapi_name() !== 'cli' && !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

// ===== ОБЪЯВЛЕНИЯ ФУНКЦИЙ =====

/**
 * Генерация персонажа для мобильной версии
 */
if (!function_exists('generateMobileCharacter')) {
function generateMobileCharacter($race, $characterClass, $level) {
    try {
        // Загружаем данные имен
        $namesData = null;
        $namesFile = 'data/names.json';
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
        require_once 'config.php';
        
        $api_key = defined('DEEPSEEK_API_KEY') ? DEEPSEEK_API_KEY : '';
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
        require_once 'api/ai-service.php';
        
        $aiService = new AiService();
        $character = ['race' => $race, 'class' => $characterClass, 'level' => $level];
        $result = $aiService->generateCharacterDescription($character);
        
        if (isset($result['error'])) {
            throw new Exception($result['error']);
        }
        
        return $result;
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile AI description failed: ' . $e->getMessage());
        throw new Exception('AI недоступен для генерации описания: ' . $e->getMessage());
    }
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

// ===== ОСНОВНОЙ КОД =====

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Неизвестное действие'];

try {
    switch ($action) {
        case 'generate_character':
            $race = $_POST['race'] ?? '';
            $characterClass = $_POST['class'] ?? '';
            $level = (int)($_POST['level'] ?? 1);
            
            if (empty($race) || empty($characterClass)) {
                $response = ['success' => false, 'message' => 'Не указаны раса или класс'];
                break;
            }
            
            // Загружаем данные для генерации персонажа
            $characterData = generateMobileCharacter($race, $characterClass, $level);
            $response = ['success' => true, 'character' => $characterData];
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
            
            $enemyData = generateMobileEnemy($threat_level, $count, $enemy_type, $environment, $use_ai);
            $response = ['success' => true, 'enemies' => $enemyData];
            break;
            
        case 'ai_chat':
            $question = $_POST['question'] ?? '';
            
            if (empty($question)) {
                $response = ['success' => false, 'message' => 'Не указан вопрос'];
                break;
            }
            
            $aiResponse = askMobileAI($question);
            $response = ['success' => true, 'data' => $aiResponse];
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