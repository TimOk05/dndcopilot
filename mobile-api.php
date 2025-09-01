<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
require_once 'users.php';

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Проверяем авторизацию для мобильной версии
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

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
            $response = ['success' => true, 'data' => $characterData];
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
            
            // Загружаем данные для генерации противника
            $enemyData = generateMobileEnemy($threat_level, $count, $enemy_type, $environment, $use_ai);
            $response = ['success' => true, 'data' => $enemyData];
            break;
            
        case 'ai_chat':
            $question = $_POST['question'] ?? '';
            
            if (empty($question)) {
                $response = ['success' => false, 'message' => 'Не указан вопрос'];
                break;
            }
            
            // Отправляем запрос к AI
            $aiResponse = askMobileAI($question);
            $response = ['success' => true, 'data' => $aiResponse];
            break;
            
        case 'roll_dice':
            $dice = $_POST['dice'] ?? '1d20';
            
            if (!preg_match('/^(\d{1,2})d(\d{1,3})$/', $dice, $matches)) {
                $response = ['success' => false, 'message' => 'Неверный формат костей'];
                break;
            }
            
            $count = (int)$matches[1];
            $sides = (int)$matches[2];
            $rolls = [];
            $total = 0;
            
            for ($i = 0; $i < $count; $i++) {
                $roll = rand(1, $sides);
                $rolls[] = $roll;
                $total += $roll;
            }
            
            $result = [
                'dice' => $dice,
                'rolls' => $rolls,
                'total' => $total,
                'display' => $count === 1 ? $rolls[0] : implode(', ', $rolls) . ' = ' . $total
            ];
            
            $response = ['success' => true, 'data' => $result];
            break;
            
        case 'save_note':
            $content = $_POST['content'] ?? '';
            $type = $_POST['type'] ?? 'general';
            
            if (empty($content)) {
                $response = ['success' => false, 'message' => 'Пустое содержимое заметки'];
                break;
            }
            
            // Сохраняем заметку в сессии
            if (!isset($_SESSION['mobile_notes'])) {
                $_SESSION['mobile_notes'] = [];
            }
            
            $note = [
                'id' => time() . rand(100, 999),
                'content' => $content,
                'type' => $type,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $_SESSION['mobile_notes'][] = $note;
            $response = ['success' => true, 'data' => $note];
            break;
            
        case 'get_notes':
            $notes = $_SESSION['mobile_notes'] ?? [];
            $response = ['success' => true, 'data' => $notes];
            break;
            
        case 'delete_note':
            $noteId = $_POST['note_id'] ?? '';
            
            if (empty($noteId)) {
                $response = ['success' => false, 'message' => 'Не указан ID заметки'];
                break;
            }
            
            if (isset($_SESSION['mobile_notes'])) {
                $_SESSION['mobile_notes'] = array_filter($_SESSION['mobile_notes'], function($note) use ($noteId) {
                    return $note['id'] != $noteId;
                });
            }
            
            $response = ['success' => true, 'message' => 'Заметка удалена'];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Неизвестное действие'];
    }
} catch (Exception $e) {
    logMessage('Mobile API error: ' . $e->getMessage(), 'ERROR', [
        'action' => $action,
        'trace' => $e->getTraceAsString()
    ]);
    $response = ['success' => false, 'message' => 'Внутренняя ошибка сервера'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * Генерация персонажа для мобильной версии
 */
function generateMobileCharacter($race, $characterClass, $level) {
    try {
    // Загружаем данные имен
    $namesData = json_decode(file_get_contents('pdf/dnd_race_names_ru_v2.json'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('ERROR', 'Failed to parse race names JSON: ' . json_last_error_msg());
            $namesData = null;
        }
        
    $name = generateCharacterName($race, $namesData);
    
    // Базовые характеристики
    $abilities = generateAbilities();
    $proficiencyBonus = floor(($level - 1) / 4) + 2;
    
    // Рассчитываем характеристики
    $hp = calculateHP($characterClass, $level, $abilities['con']);
    $ac = calculateAC($characterClass, $abilities['dex']);
    
    // Генерируем описание
    $description = generateCharacterDescription($race, $characterClass, $level);
        
        logMessage('INFO', 'Mobile character generated successfully', [
            'race' => $race,
            'class' => $characterClass,
            'level' => $level
        ]);
    
    return [
        'name' => $name,
        'race' => $race,
        'class' => $characterClass,
        'level' => $level,
        'abilities' => $abilities,
        'hp' => $hp,
        'ac' => $ac,
        'proficiency_bonus' => $proficiencyBonus,
        'description' => $description,
        'features' => getClassFeatures($characterClass, $level),
        'equipment' => getClassEquipment($characterClass),
        'spells' => getClassSpells($characterClass, $level)
    ];
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile character generation failed: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Генерация противника для мобильной версии
 */
function generateMobileEnemy($threat_level, $count, $enemy_type, $environment, $use_ai) {
    try {
        // Используем основной API генерации противников
        require_once 'api/generate-enemies.php';
        $generator = new EnemyGenerator();
        
        // Передаем параметры для генерации
        $params = [
            'threat_level' => $threat_level,
            'count' => $count,
            'enemy_type' => $enemy_type,
            'environment' => $environment,
            'use_ai' => $use_ai
        ];
        
        $result = $generator->generateEnemies($params);
        
        if (!$result['success']) {
            throw new Exception($result['error']);
        }
        
        // Если запрошено несколько противников, возвращаем массив
        if ($count > 1) {
            $mobileEnemies = [];
            foreach ($result['enemies'] as $enemy) {
                $mobileEnemies[] = adaptEnemyForMobile($enemy);
            }
            return $mobileEnemies;
        } else {
            // Для одного противника возвращаем объект
            return adaptEnemyForMobile($result['enemies'][0]);
        }
        
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile enemy generation failed: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Адаптация данных противника для мобильной версии
 */
function adaptEnemyForMobile($enemy) {
    return [
        'name' => $enemy['name'],
        'cr' => $enemy['challenge_rating'],
        'challenge_rating' => $enemy['challenge_rating'],
        'hp' => $enemy['hit_points'],
        'hit_points' => $enemy['hit_points'],
        'ac' => $enemy['armor_class'],
        'armor_class' => $enemy['armor_class'],
        'abilities' => $enemy['abilities'],
        'actions' => array_map(function($action) {
            return is_array($action) ? ($action['name'] ?? 'Атака') : $action;
        }, $enemy['actions']),
        'description' => $enemy['description'] ?? 'Описание не определено',
        'tactics' => $enemy['tactics'] ?? 'Тактика не определена',
        'type' => $enemy['type'],
        'environment' => $enemy['environment'] ?? 'Различные',
        'speed' => $enemy['speed'] ?? '30 ft'
    ];
}

/**
 * Запрос к AI для мобильной версии
 */
function askMobileAI($question) {
    // Используем существующий AI чат
    require_once 'api/ai-chat.php';
    
    $aiChat = new AIChat();
    $result = $aiChat->processMessage($question);
    
    if ($result['success']) {
        return [
            'response' => $result['response'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        return [
            'response' => 'Извините, не удалось получить ответ от AI. Попробуйте позже.',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Генерация имени персонажа
 */
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
    
    // Fallback имена
    $fallbackNames = [
        'male' => ['Алексей', 'Дмитрий', 'Иван', 'Михаил', 'Сергей', 'Андрей', 'Владимир', 'Николай', 'Петр', 'Александр'],
        'female' => ['Анна', 'Елена', 'Мария', 'Ольга', 'Татьяна', 'Ирина', 'Наталья', 'Светлана', 'Екатерина', 'Юлия']
    ];
    
    $gender = rand(0, 1) ? 'male' : 'female';
    return $fallbackNames[$gender][array_rand($fallbackNames[$gender])];
}

/**
 * Генерация характеристик
 */
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

/**
 * Расчет хитов
 */
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

/**
 * Получение кости хитов класса
 */
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

/**
 * Расчет класса доспеха
 */
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

/**
 * Получение владений доспехами класса
 */
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

/**
 * Генерация описания персонажа
 */
function generateCharacterDescription($race, $characterClass, $level) {
    $raceNames = [
        'human' => 'Человек',
        'elf' => 'Эльф',
        'dwarf' => 'Дварф',
        'halfling' => 'Полурослик',
        'tiefling' => 'Тифлинг',
        'dragonborn' => 'Драконорожденный'
    ];
    
    $classNames = [
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
        'artificer' => 'Изобретатель'
    ];
    
    $raceName = $raceNames[$race] ?? $race;
    $className = $classNames[$characterClass] ?? $characterClass;
    
    $descriptions = [
        'human' => "Опытный {$className} с решительным взглядом и уверенными движениями. Годы тренировок сделали каждое действие отточенным и эффективным.",
        'elf' => "Благородный эльф-{$className} с изящными чертами лица и проницательным взглядом. Эльфийская грация сочетается с боевым мастерством.",
        'dwarf' => "Крепкий дварф-{$className} с честным взглядом и надежными руками мастера. Дварфийская стойкость проявляется в каждом движении.",
        'halfling' => "Жизнерадостный полурослик-{$className} с озорными глазами и быстрыми движениями. Природная ловкость позволяет находить выход из любой ситуации.",
        'tiefling' => "Загадочный тифлинг-{$className} с изящными рогами и глазами, мерцающими внутренним огнем. Врожденная харизма завораживает окружающих.",
        'dragonborn' => "Величественный драконорожденный-{$className} с чешуйчатой кожей и гордой осанкой. Драконье наследие проявляется в каждом жесте."
    ];
    
    return $descriptions[$race] ?? "Опытный {$raceName}-{$className} {$level} уровня, готовый к любым испытаниям.";
}

/**
 * Получение способностей класса
 */
function getClassFeatures($characterClass, $level) {
    $features = [];
    
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

/**
 * Получение снаряжения класса
 */
function getClassEquipment($characterClass) {
    $equipment = [];
    
    // Доспехи
    $armorProficiencies = getArmorProficiencies($characterClass);
    if (in_array('heavy', $armorProficiencies)) {
        $equipment[] = 'Кольчуга';
    } elseif (in_array('medium', $armorProficiencies)) {
        $equipment[] = 'Кожаный доспех';
    } elseif (in_array('light', $armorProficiencies)) {
        $equipment[] = 'Кожаная броня';
    }
    
    // Оружие
    $weaponProficiencies = getWeaponProficiencies($characterClass);
    if (in_array('martial', $weaponProficiencies)) {
        $weapons = ['Длинный меч', 'Боевой топор', 'Молот', 'Копье', 'Алебарда'];
        $equipment[] = $weapons[array_rand($weapons)];
    } elseif (in_array('simple', $weaponProficiencies)) {
        $weapons = ['Булава', 'Короткий меч', 'Кинжал', 'Дубина', 'Копье'];
        $equipment[] = $weapons[array_rand($weapons)];
    }
    
    // Базовое снаряжение
    $equipment[] = 'Рюкзак исследователя';
    $equipment[] = 'Веревка (50 футов)';
    $equipment[] = 'Факел';
    $equipment[] = 'Трутница';
    
    // Зелья
    $potions = ['Зелье лечения', 'Зелье невидимости', 'Зелье прыгучести', 'Зелье сопротивления огню'];
    $equipment[] = $potions[array_rand($potions)];
    
    // Деньги
    $gold = rand(5, 25);
    $equipment[] = "{$gold} золотых монет";
    
    return $equipment;
}

/**
 * Получение владений оружием класса
 */
function getWeaponProficiencies($characterClass) {
    $proficiencies = [
        'fighter' => ['simple', 'martial'],
        'wizard' => ['daggers', 'quarterstaffs', 'light_crossbows'],
        'rogue' => ['simple', 'shortswords', 'longswords'],
        'cleric' => ['simple'],
        'ranger' => ['simple', 'martial'],
        'barbarian' => ['simple', 'martial'],
        'bard' => ['simple', 'hand_crossbows', 'longswords'],
        'druid' => ['simple'],
        'monk' => ['simple', 'shortswords'],
        'paladin' => ['simple', 'martial'],
        'sorcerer' => ['daggers', 'quarterstaffs', 'light_crossbows'],
        'warlock' => ['simple'],
        'artificer' => ['simple']
    ];
    
    return $proficiencies[$characterClass] ?? ['simple'];
}

/**
 * Получение заклинаний класса
 */
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


?>

