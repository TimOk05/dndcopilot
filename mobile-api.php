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
            $cr = $_POST['cr'] ?? '';
            
            if (empty($cr)) {
                $response = ['success' => false, 'message' => 'Не указан уровень угрозы'];
                break;
            }
            
            // Загружаем данные для генерации противника
            $enemyData = generateMobileEnemy($cr);
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
function generateMobileEnemy($cr) {
    try {
        // Загружаем данные противников из fallback-data
        require_once 'api/fallback-data.php';
        $fallbackData = new FallbackData();
        $enemiesData = $fallbackData->getAllData();
        
        if (!$enemiesData || !isset($enemiesData['enemies'])) {
            throw new Exception('База данных противников недоступна');
        }
        
        // Фильтруем по CR
        $filteredEnemies = array_filter($enemiesData['enemies'], function($enemy) use ($cr) {
            return isset($enemy['cr']) && $enemy['cr'] == $cr;
        });
        
        if (empty($filteredEnemies)) {
            // Если нет точного совпадения, ищем ближайший CR
            $availableCRs = array_unique(array_column($enemiesData['enemies'], 'cr'));
            sort($availableCRs);
            
            $closestCR = $availableCRs[0];
            foreach ($availableCRs as $availableCR) {
                if ($availableCR >= $cr) {
                    $closestCR = $availableCR;
                    break;
                }
            }
            
            $filteredEnemies = array_filter($enemiesData['enemies'], function($enemy) use ($closestCR) {
                return isset($enemy['cr']) && $enemy['cr'] == $closestCR;
            });
        }
        
        if (empty($filteredEnemies)) {
            throw new Exception('Не найдены подходящие противники');
        }
        
        // Выбираем случайного противника
        $enemy = $filteredEnemies[array_rand($filteredEnemies)];
        
        // Генерируем дополнительные данные
        $enemy['description'] = generateEnemyDescription($enemy);
        $enemy['tactics'] = generateEnemyTactics($enemy);
        
        logMessage('INFO', 'Mobile enemy generated successfully', [
            'cr' => $cr,
            'name' => $enemy['name']
        ]);
        
        return $enemy;
        
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile enemy generation failed: ' . $e->getMessage());
        throw $e;
    }
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

/**
 * Генерация описания противника
 */
function generateEnemyDescription($enemy) {
    $type = $enemy['type'] ?? 'unknown';
    $cr = $enemy['cr'] ?? 1;
    
    $descriptions = [
        'beast' => "Дикое животное с острыми клыками и когтями. Глаза горят диким огнем, а каждое движение выдает хищную природу.",
        'humanoid' => "Гуманоид с опытным взглядом и уверенными движениями. В руках крепко сжимает оружие, готовый к бою.",
        'undead' => "Нежить с пустыми глазницами и неестественными движениями. От него исходит зловещая аура смерти.",
        'dragon' => "Величественный дракон с чешуйчатой кожей и острыми когтями. В глазах горит древняя мудрость и опасность.",
        'fiend' => "Демон с рогами и крыльями, от которого исходит адское пламя. Каждое движение пропитано злом и ненавистью.",
        'celestial' => "Небесное существо с сияющей аурой и ангельскими крыльями. От него исходит божественная энергия.",
        'construct' => "Механическое существо с металлическими частями и магическими рунами. Движения точны и безжалостны.",
        'elemental' => "Элементаль, состоящий из чистой стихии. Тело постоянно меняет форму, подчиняясь силам природы.",
        'fey' => "Фей с эфирной красотой и загадочным взглядом. Каждое движение грациозно и наполнено магией.",
        'giant' => "Гигант огромного роста с мощными мышцами. Каждый шаг заставляет землю дрожать.",
        'monstrosity' => "Чудовище с уродливыми чертами и неестественными конечностями. Его вид внушает ужас и отвращение.",
        'ooze' => "Слизь с желеобразным телом и кислотными выделениями. Постоянно меняет форму и размер.",
        'plant' => "Растительное существо с ветвистыми конечностями и листьями. Движения медленны, но смертоносны.",
        'unknown' => "Таинственное существо с неопределенной природой. Его истинная форма скрыта от глаз смертных."
    ];
    
    $baseDescription = $descriptions[$type] ?? $descriptions['unknown'];
    
    // Добавляем информацию о силе
    if ($cr <= 1) {
        $strength = "Это относительно слабое существо, подходящее для начинающих авантюристов.";
    } elseif ($cr <= 5) {
        $strength = "Это существо средней силы, способное бросить вызов опытной группе.";
    } elseif ($cr <= 10) {
        $strength = "Это опасное существо, требующее серьезной подготовки для победы.";
    } else {
        $strength = "Это смертельно опасное существо, способное уничтожить целую группу авантюристов.";
    }
    
    return $baseDescription . " " . $strength;
}

/**
 * Генерация тактики противника
 */
function generateEnemyTactics($enemy) {
    $type = $enemy['type'] ?? 'unknown';
    $cr = $enemy['cr'] ?? 1;
    
    $tactics = [
        'beast' => "Дикие звери полагаются на инстинкты и физическую силу. Они атакуют ближайшую цель, используя клыки и когти.",
        'humanoid' => "Гуманоиды используют тактику и стратегию. Они могут отступать, перегруппировываться и использовать окружение.",
        'undead' => "Нежить не чувствует боли и страха. Они атакуют без остановки, пока не будут полностью уничтожены.",
        'dragon' => "Драконы используют свою летающую способность и дыхание. Они атакуют с воздуха и избегают ближнего боя.",
        'fiend' => "Демоны используют магию и хитрость. Они могут телепортироваться и использовать проклятия.",
        'celestial' => "Небесные существа используют исцеляющую магию и защитные заклинания. Они предпочитают дистанционный бой.",
        'construct' => "Конструкты следуют запрограммированным инструкциям. Они атакуют систематично и без эмоций.",
        'elemental' => "Элементали используют силы природы. Они могут создавать препятствия и использовать стихийную магию.",
        'fey' => "Фей используют иллюзии и очарование. Они предпочитают избегать прямого конфликта.",
        'giant' => "Гиганты полагаются на грубую силу. Они атакуют в ближнем бою, используя огромное оружие.",
        'monstrosity' => "Чудовища используют неожиданные атаки и уродливые способности. Они непредсказуемы в бою.",
        'ooze' => "Слизи медленно движутся, но могут разделяться и поглощать противников.",
        'plant' => "Растительные существа используют яды и способность к регенерации. Они атакуют из засады.",
        'unknown' => "Тактика этого существа неизвестна. Оно может использовать любые доступные методы атаки."
    ];
    
    return $tactics[$type] ?? $tactics['unknown'];
}
?>
