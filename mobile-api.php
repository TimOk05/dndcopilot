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
    // Загружаем данные имен
    $namesData = json_decode(file_get_contents('pdf/dnd_race_names_ru_v2.json'), true);
    $name = generateCharacterName($race, $namesData);
    
    // Базовые характеристики
    $abilities = generateAbilities();
    $proficiencyBonus = floor(($level - 1) / 4) + 2;
    
    // Рассчитываем характеристики
    $hp = calculateHP($characterClass, $level, $abilities['con']);
    $ac = calculateAC($characterClass, $abilities['dex']);
    
    // Генерируем описание
    $description = generateCharacterDescription($race, $characterClass, $level);
    
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
}

/**
 * Генерация противника для мобильной версии
 */
function generateMobileEnemy($cr) {
    // Загружаем данные противников
    $enemiesData = json_decode(file_get_contents('api/fallback-data.php'), true);
    
    // Фильтруем по CR
    $filteredEnemies = array_filter($enemiesData['enemies'] ?? [], function($enemy) use ($cr) {
        return $enemy['cr'] == $cr;
    });
    
    if (empty($filteredEnemies)) {
        // Если нет точного совпадения, берем ближайшего
        $filteredEnemies = array_filter($enemiesData['enemies'] ?? [], function($enemy) use ($cr) {
            return $enemy['cr'] <= $cr + 1 && $enemy['cr'] >= $cr - 1;
        });
    }
    
    if (empty($filteredEnemies)) {
        // Берем случайного противника
        $filteredEnemies = $enemiesData['enemies'] ?? [];
    }
    
    $enemy = $filteredEnemies[array_rand($filteredEnemies)];
    
    return [
        'name' => $enemy['name'] ?? 'Неизвестный противник',
        'cr' => $enemy['cr'] ?? $cr,
        'hp' => $enemy['hp'] ?? rand(10, 50),
        'ac' => $enemy['ac'] ?? rand(10, 18),
        'abilities' => $enemy['abilities'] ?? generateAbilities(),
        'actions' => $enemy['actions'] ?? ['Атака'],
        'description' => $enemy['description'] ?? 'Описание противника',
        'tactics' => generateEnemyTactics($enemy['name'] ?? 'Противник')
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
    $raceNames = null;
    
    // Ищем расу в данных
    foreach ($namesData['data'] ?? [] as $raceData) {
        if ($raceData['race'] === $race) {
            $raceNames = $raceData;
            break;
        }
    }
    
    if ($raceNames) {
        $allNames = array_merge(
            $raceNames['male'] ?? [],
            $raceNames['female'] ?? [],
            $raceNames['unisex'] ?? []
        );
        
        if (!empty($allNames)) {
            return $allNames[array_rand($allNames)];
        }
    }
    
    // Fallback имена
    $fallbackNames = ['Александр', 'Елена', 'Михаил', 'Анна', 'Дмитрий', 'Мария'];
    return $fallbackNames[array_rand($fallbackNames)];
}

/**
 * Генерация характеристик
 */
function generateAbilities() {
    return [
        'str' => rand(8, 18),
        'dex' => rand(8, 18),
        'con' => rand(8, 18),
        'int' => rand(8, 18),
        'wis' => rand(8, 18),
        'cha' => rand(8, 18)
    ];
}

/**
 * Расчет HP
 */
function calculateHP($class, $level, $conMod) {
    $baseHP = [
        'barbarian' => 12,
        'fighter' => 10,
        'paladin' => 10,
        'ranger' => 10,
        'wizard' => 6,
        'sorcerer' => 6,
        'warlock' => 8,
        'cleric' => 8,
        'druid' => 8,
        'monk' => 8,
        'rogue' => 8,
        'bard' => 8,
        'artificer' => 8
    ];
    
    $hitDie = $baseHP[$class] ?? 8;
    $conBonus = floor(($conMod - 10) / 2);
    
    return $hitDie + $conBonus + ($level - 1) * (rand(1, $hitDie) + $conBonus);
}

/**
 * Расчет AC
 */
function calculateAC($class, $dexMod) {
    $dexBonus = floor(($dexMod - 10) / 2);
    
    switch ($class) {
        case 'barbarian':
        case 'monk':
            return 10 + $dexBonus; // Упрощенный расчет для мобильной версии
        case 'wizard':
        case 'sorcerer':
            return 10 + $dexBonus;
        default:
            return 10 + $dexBonus + 2; // Предполагаем наличие доспехов
    }
}

/**
 * Генерация описания персонажа
 */
function generateCharacterDescription($race, $class, $level) {
    $descriptions = [
        'human' => 'Человек - самая распространенная раса в мире. Адаптивные и амбициозные.',
        'elf' => 'Эльф - древняя раса, известная своей грацией и долголетием.',
        'dwarf' => 'Дварф - крепкая раса, известная своим мастерством в ремеслах.',
        'halfling' => 'Полурослик - маленькая, но храбрая раса, любящая комфорт и приключения.',
        'orc' => 'Орк - сильная раса, известная своей воинственностью и выносливостью.',
        'tiefling' => 'Тифлинг - потомок демонов, часто сталкивающийся с предрассудками.',
        'dragonborn' => 'Драконорожденный - раса с кровью драконов, гордая и могущественная.',
        'gnome' => 'Гном - маленькая раса, известная своим любопытством и изобретательностью.'
    ];
    
    $raceDesc = $descriptions[$race] ?? 'Таинственная раса с уникальными способностями.';
    
    return "Это $level-уровневый $class $race. " . $raceDesc;
}

/**
 * Получение особенностей класса
 */
function getClassFeatures($class, $level) {
    $features = [
        'fighter' => ['Боевой стиль', 'Second Wind'],
        'wizard' => ['Заклинания', 'Arcane Recovery'],
        'rogue' => ['Sneak Attack', 'Cunning Action'],
        'cleric' => ['Заклинания', 'Divine Domain'],
        'ranger' => ['Favored Enemy', 'Natural Explorer'],
        'barbarian' => ['Rage', 'Unarmored Defense'],
        'bard' => ['Bardic Inspiration', 'Song of Rest'],
        'druid' => ['Druidic', 'Wild Shape'],
        'monk' => ['Unarmored Defense', 'Martial Arts'],
        'paladin' => ['Divine Sense', 'Lay on Hands'],
        'sorcerer' => ['Sorcery Points', 'Metamagic'],
        'warlock' => ['Pact Magic', 'Eldritch Invocations'],
        'artificer' => ['Magical Tinkering', 'Infuse Item']
    ];
    
    return $features[$class] ?? ['Особенности класса'];
}

/**
 * Получение снаряжения класса
 */
function getClassEquipment($class) {
    $equipment = [
        'fighter' => ['Меч', 'Щит', 'Кольчуга'],
        'wizard' => ['Посох', 'Книга заклинаний', 'Компонентный мешочек'],
        'rogue' => ['Короткие мечи', 'Кожаная броня', 'Воровские инструменты'],
        'cleric' => ['Булава', 'Щит', 'Священный символ'],
        'ranger' => ['Длинный лук', 'Короткий меч', 'Кожаная броня'],
        'barbarian' => ['Секира', 'Кожаная броня', 'Рюкзак'],
        'bard' => ['Лютня', 'Кожаная броня', 'Инструмент'],
        'druid' => ['Древесный посох', 'Кожаная броня', 'Друидский фокус'],
        'monk' => ['Короткий меч', 'Простая одежда', 'Монашеское оружие'],
        'paladin' => ['Меч', 'Щит', 'Кольчуга'],
        'sorcerer' => ['Посох', 'Компонентный мешочек', 'Простая одежда'],
        'warlock' => ['Короткий меч', 'Кожаная броня', 'Мистический фокус'],
        'artificer' => ['Инструменты', 'Кожаная броня', 'Артифисерский фокус']
    ];
    
    return $equipment[$class] ?? ['Базовое снаряжение'];
}

/**
 * Получение заклинаний класса
 */
function getClassSpells($class, $level) {
    $spellcasters = ['wizard', 'sorcerer', 'warlock', 'cleric', 'druid', 'bard', 'paladin', 'ranger', 'artificer'];
    
    if (!in_array($class, $spellcasters)) {
        return [];
    }
    
    $spells = [
        'wizard' => ['Magic Missile', 'Shield', 'Fireball'],
        'sorcerer' => ['Charm Person', 'Magic Missile', 'Fireball'],
        'warlock' => ['Eldritch Blast', 'Hex', 'Armor of Agathys'],
        'cleric' => ['Cure Wounds', 'Bless', 'Spiritual Weapon'],
        'druid' => ['Cure Wounds', 'Entangle', 'Call Lightning'],
        'bard' => ['Vicious Mockery', 'Cure Wounds', 'Charm Person'],
        'paladin' => ['Cure Wounds', 'Bless', 'Divine Smite'],
        'ranger' => ['Cure Wounds', 'Hunter\'s Mark', 'Conjure Animals'],
        'artificer' => ['Cure Wounds', 'Magic Stone', 'Faerie Fire']
    ];
    
    return $spells[$class] ?? [];
}

/**
 * Генерация тактики противника
 */
function generateEnemyTactics($enemyName) {
    $tactics = [
        'Гоблин' => 'Атакует из засады, использует численное преимущество',
        'Орк' => 'Прямая атака, полагается на силу и выносливость',
        'Тролль' => 'Регенерация, атакует ближайшую цель',
        'Дракон' => 'Использует дыхание, летает для преимущества',
        'Нежить' => 'Нечувствителен к страху, атакует без устали',
        'Демон' => 'Телепортация, использует магические способности'
    ];
    
    foreach ($tactics as $enemy => $tactic) {
        if (stripos($enemyName, $enemy) !== false) {
            return $tactic;
        }
    }
    
    return 'Атакует ближайшую цель, использует доступные способности';
}
?>
