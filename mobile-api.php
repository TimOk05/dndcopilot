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
    
    // Генерируем описание с AI
    $description = generateCharacterDescriptionWithAI($race, $characterClass, $level);
        
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
        // Создаем простых противников для мобильной версии
        $enemies = [];
        
        for ($i = 0; $i < $count; $i++) {
            $enemy = generateSimpleEnemy($threat_level, $enemy_type, $environment);
            $enemies[] = $enemy;
        }
        
        // Если запрошено несколько противников, возвращаем массив
        if ($count > 1) {
            return $enemies;
        } else {
            // Для одного противника возвращаем объект
            return $enemies[0];
        }
        
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile enemy generation failed: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Генерация простого противника
 */
function generateSimpleEnemy($threat_level, $enemy_type, $environment) {
    // Базовые противники по уровню угрозы
    $enemies_by_threat = [
        'easy' => [
            ['name' => 'Гоблин', 'cr' => '1/4', 'hp' => 7, 'ac' => 15, 'type' => 'humanoid'],
            ['name' => 'Кобольд', 'cr' => '1/8', 'hp' => 5, 'ac' => 12, 'type' => 'humanoid'],
            ['name' => 'Волк', 'cr' => '1/4', 'hp' => 11, 'ac' => 13, 'type' => 'beast']
        ],
        'medium' => [
            ['name' => 'Орк', 'cr' => '1/2', 'hp' => 15, 'ac' => 13, 'type' => 'humanoid'],
            ['name' => 'Тролль', 'cr' => '5', 'hp' => 84, 'ac' => 15, 'type' => 'giant'],
            ['name' => 'Медведь', 'cr' => '1', 'hp' => 34, 'ac' => 11, 'type' => 'beast']
        ],
        'hard' => [
            ['name' => 'Огр', 'cr' => '2', 'hp' => 59, 'ac' => 11, 'type' => 'giant'],
            ['name' => 'Мантикора', 'cr' => '3', 'hp' => 68, 'ac' => 14, 'type' => 'monstrosity'],
            ['name' => 'Дракон', 'cr' => '8', 'hp' => 136, 'ac' => 18, 'type' => 'dragon']
        ],
        'deadly' => [
            ['name' => 'Древний Дракон', 'cr' => '20', 'hp' => 546, 'ac' => 22, 'type' => 'dragon'],
            ['name' => 'Лич', 'cr' => '21', 'hp' => 135, 'ac' => 17, 'type' => 'undead'],
            ['name' => 'Демон', 'cr' => '15', 'hp' => 200, 'ac' => 19, 'type' => 'fiend']
        ]
    ];
    
    // Если указан конкретный CR
    if (is_numeric($threat_level)) {
        $cr = floatval($threat_level);
        if ($cr <= 1) {
            $threat_level = 'easy';
        } elseif ($cr <= 5) {
            $threat_level = 'medium';
        } elseif ($cr <= 10) {
            $threat_level = 'hard';
        } else {
            $threat_level = 'deadly';
        }
    }
    
    // Выбираем случайного противника
    $available_enemies = $enemies_by_threat[$threat_level] ?? $enemies_by_threat['easy'];
    $selected = $available_enemies[array_rand($available_enemies)];
    
    // Генерируем характеристики
    $abilities = [
        'str' => rand(8, 18),
        'dex' => rand(8, 18),
        'con' => rand(8, 18),
        'int' => rand(8, 18),
        'wis' => rand(8, 18),
        'cha' => rand(8, 18)
    ];
    
    // Генерируем описание и тактику
    $description = generateEnemyDescription($selected['name'], $selected['type'], $environment);
    $tactics = generateEnemyTactics($selected['name'], $selected['type']);
    
    return [
        'name' => $selected['name'],
        'cr' => $selected['cr'],
        'challenge_rating' => $selected['cr'],
        'hp' => $selected['hp'],
        'hit_points' => $selected['hp'],
        'ac' => $selected['ac'],
        'armor_class' => $selected['ac'],
        'abilities' => $abilities,
        'actions' => ['Атака', 'Защита', 'Специальная способность'],
        'description' => $description,
        'tactics' => $tactics,
        'type' => $selected['type'],
        'environment' => $environment ?: 'Различные',
        'speed' => '30 ft'
    ];
}

/**
 * Генерация описания противника
 */
function generateEnemyDescription($name, $type, $environment) {
    $descriptions = [
        'Гоблин' => 'Маленький, злобный гуманоид с острыми зубами и хитрыми глазами. Гоблины известны своей коварностью и любовью к засадам.',
        'Орк' => 'Крупный, мускулистый гуманоид с зеленой кожей и свирепым выражением лица. Орки - воинственные существа, которые полагаются на грубую силу.',
        'Дракон' => 'Величественное и могущественное существо с чешуйчатой кожей, острыми когтями и способностью дышать различными стихиями.',
        'Тролль' => 'Огромное, уродливое существо с регенерирующими способностями. Тролли известны своей невероятной силой и живучестью.',
        'Волк' => 'Дикий хищник с острыми зубами и развитыми охотничьими инстинктами. Волки часто охотятся стаями.',
        'Медведь' => 'Крупное, мощное животное с острыми когтями и невероятной силой. Медведи могут быть очень опасными, когда защищают свою территорию.'
    ];
    
    return $descriptions[$name] ?? "{$name} - {$type} существо, обитающее в {$environment} среде.";
}

/**
 * Генерация тактики противника
 */
function generateEnemyTactics($name, $type) {
    $tactics = [
        'Гоблин' => 'Гоблины предпочитают засады и атаки из укрытия. Они используют численное преимущество и стараются избегать честного боя.',
        'Орк' => 'Орки атакуют в лоб, полагаясь на свою силу и ярость. Они не отступают и сражаются до конца.',
        'Дракон' => 'Драконы используют свое дыхание, полет и магические способности. Они умны и тактически подходят к бою.',
        'Тролль' => 'Тролли полагаются на свою регенерацию и физическую силу. Они атакуют агрессивно, зная, что могут восстановиться.',
        'Волк' => 'Волки охотятся стаями, окружая жертву и атакуя с разных сторон. Они используют свою скорость и координацию.',
        'Медведь' => 'Медведи защищают свою территорию агрессивно. Они используют свои когти и мощные лапы для нанесения урона.'
    ];
    
    return $tactics[$name] ?? "{$name} использует стандартную тактику для {$type} существ.";
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
    try {
        // Используем основной AI сервис
        require_once 'api/ai-service.php';
        
        $aiService = new AiService();
        
        // Создаем промпт для AI чата
        $prompt = "Пользователь задает вопрос о D&D: " . $question . "\n\nОтветь кратко и по делу на русском языке.";
        
        // Используем метод генерации описания для ответа на вопрос
        $result = $aiService->generateCharacterDescription([
            'name' => 'AI Assistant',
            'type' => 'AI Chat',
            'challenge_rating' => 'N/A'
        ], true);
        
        if (isset($result['error'])) {
            // Fallback к локальным ответам
            $response = generateAIResponse($question);
            return [
                'response' => $response,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            return [
                'response' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile AI chat failed: ' . $e->getMessage());
        // Fallback к локальным ответам
        $response = generateAIResponse($question);
        return [
            'response' => $response,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Генерация ответа AI на основе вопроса
 */
function generateAIResponse($question) {
    $question = trim($question);
    
    // Простые ответы на частые вопросы
    if (stripos($question, 'правила') !== false || stripos($question, 'как играть') !== false) {
        return "D&D - это настольная ролевая игра, где игроки создают персонажей и отправляются в приключения под руководством Мастера игры. Основные правила включают бросок d20 для проверок, использование характеристик (Сила, Ловкость, Телосложение, Интеллект, Мудрость, Харизма) и взаимодействие с миром через ролевую игру. Мастер описывает ситуацию, игроки решают, что делать, и бросают кости для определения успеха.";
    }
    
    if (stripos($question, 'класс') !== false || stripos($question, 'классы') !== false) {
        return "В D&D есть множество классов: Воин (Fighter) - мастер ближнего боя, Волшебник (Wizard) - заклинатель с книгой заклинаний, Плут (Rogue) - скрытность и ловкость, Жрец (Cleric) - божественная магия, Следопыт (Ranger) - выживание и природа, Варвар (Barbarian) - ярость и сила, Бард (Bard) - вдохновение и магия, Друид (Druid) - природа и превращения, Монах (Monk) - боевые искусства, Паладин (Paladin) - священный воин, Чародей (Sorcerer) - врожденная магия, Колдун (Warlock) - пакт с покровителем, Изобретатель (Artificer) - магические предметы. Каждый класс имеет уникальные способности и стиль игры.";
    }
    
    if (stripos($question, 'раса') !== false || stripos($question, 'расы') !== false) {
        return "Расы в D&D включают: Человек (адаптивность), Эльф (грация и долголетие), Дварф (стойкость и мастерство), Полурослик (ловкость и удача), Орк (сила и выносливость), Тифлинг (харизма и демоническое наследие), Драконорожденный (драконья сила), Гном (любознательность), Полуэльф (двойное наследие), Полуорк (сила и хитрость), Табакси (кошачья ловкость), Ааракокра (полет), Гоблин (хитрость), Кенку (подражание), Ящеролюд (примитивная сила), Тритон (водная среда), Юань-ти (змеиная магия), Голиаф (гигантская сила), Фирболг (природная магия), Багбир (скрытность), Хобгоблин (дисциплина), Кобольд (хитрость). Каждая раса дает уникальные бонусы к характеристикам и способности.";
    }
    
    if (stripos($question, 'кости') !== false || stripos($question, 'd20') !== false) {
        return "В D&D используются различные кости: d4 (тетраэдр), d6 (куб), d8 (октаэдр), d10 (декаэдр), d12 (додекаэдр), d20 (икосаэдр) и d100 (процентные кости). Основная кость - d20, которая используется для большинства проверок. Бросок d20 + модификатор характеристики + бонус мастерства (если применимо) определяет успех действия. Критический успех - 20, критический провал - 1.";
    }
    
    if (stripos($question, 'здоровье') !== false || stripos($question, 'хиты') !== false) {
        return "Хиты (HP) определяют, сколько урона может выдержать персонаж. Они рассчитываются на основе кости хитов класса + модификатор Телосложения. При получении урона хиты уменьшаются, при достижении 0 персонаж теряет сознание и начинает делать спасброски от смерти. При -10 хитов персонаж умирает.";
    }
    
    if (stripos($question, 'заклинания') !== false || stripos($question, 'магия') !== false) {
        return "Заклинания в D&D используют систему слотов заклинаний. У каждого класса заклинателей есть определенное количество слотов каждого уровня. Заклинания требуют вербальных (слова), соматических (жесты) или материальных (компоненты) компонентов для произнесения. Заклинания 1-9 уровня, где 9 уровень - самые мощные. Некоторые заклинания можно произносить как ритуалы без траты слотов.";
    }
    
    if (stripos($question, 'бросок') !== false || stripos($question, 'проверка') !== false) {
        return "Проверки в D&D - это броски d20 + модификатор характеристики + бонус мастерства (если применимо). Спасброски - проверки для избежания опасности. Атаки - проверки для попадания по противнику. Проверки навыков - для выполнения сложных действий. Сложность проверки (СЛ) определяет Мастер игры.";
    }
    
    if (stripos($question, 'уровень') !== false || stripos($question, 'прокачка') !== false) {
        return "Уровни в D&D от 1 до 20. При получении опыта персонаж повышает уровень, получая новые способности, заклинания, хиты и улучшения характеристик. Каждый класс имеет уникальную таблицу прогрессии. На 4, 8, 12, 16, 19 уровнях можно увеличить характеристики или взять черту.";
    }
    
    if (stripos($question, 'снаряжение') !== false || stripos($question, 'экипировка') !== false) {
        return "Снаряжение включает оружие (ближний бой и дальний), доспехи (легкие, средние, тяжелые), щиты, инструменты, зелья, свитки, магические предметы. Каждый класс начинает с базовым снаряжением. Деньги измеряются в медных (м), серебряных (с), золотых (з) и платиновых (п) монетах.";
    }
    
    if (stripos($question, 'бой') !== false || stripos($question, 'сражение') !== false) {
        return "Бой в D&D происходит по инициативе. Каждый участник бросает d20 + модификатор Ловкости для определения порядка ходов. В свой ход можно: переместиться, совершить действие (атака, заклинание, использование предмета), бонусное действие (если доступно), свободное действие (говор, жесты). Атака: d20 + модификатор атаки против КД противника.";
    }
    
    // Общий ответ
    return "Это интересный вопрос о D&D! Для получения более подробной информации рекомендую обратиться к официальным правилам или спросить у опытного Мастера игры. Если у вас есть конкретные вопросы о правилах, классах, расах или механиках игры, я буду рад помочь!";
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
    
    // Имена недоступны
    return "Неизвестный";
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
 * Генерация описания персонажа с AI
 */
function generateCharacterDescriptionWithAI($race, $characterClass, $level) {
    try {
        // Используем основной AI сервис
        require_once 'api/ai-service.php';
        
        $aiService = new AiService();
        
        // Создаем данные персонажа для AI
        $characterData = [
            'name' => 'Персонаж',
            'type' => $race . ' ' . $characterClass,
            'challenge_rating' => $level
        ];
        
        $result = $aiService->generateCharacterDescription($characterData, true);
        
        if (isset($result['error'])) {
            // Fallback к обычному описанию
            return generateCharacterDescription($race, $characterClass, $level);
        } else {
            return $result;
        }
    } catch (Exception $e) {
        logMessage('ERROR', 'Mobile AI description failed: ' . $e->getMessage());
        // Fallback к обычному описанию
        return generateCharacterDescription($race, $characterClass, $level);
    }
}

/**
 * Генерация описания персонажа (fallback)
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
        'human' => "Опытный {$className} с решительным взглядом и уверенными движениями. Годы тренировок сделали каждое действие отточенным и эффективным. Человеческая адаптивность позволяет быстро осваивать новые техники и стратегии.",
        'elf' => "Благородный эльф-{$className} с изящными чертами лица и проницательным взглядом. Эльфийская грация сочетается с боевым мастерством, а долгая жизнь дала глубокое понимание своего искусства.",
        'dwarf' => "Крепкий дварф-{$className} с честным взглядом и надежными руками мастера. Дварфийская стойкость проявляется в каждом движении, а врожденная выносливость позволяет выдерживать самые тяжелые испытания.",
        'halfling' => "Жизнерадостный полурослик-{$className} с озорными глазами и быстрыми движениями. Природная ловкость позволяет находить выход из любой ситуации, а оптимизм помогает преодолевать трудности.",
        'tiefling' => "Загадочный тифлинг-{$className} с изящными рогами и глазами, мерцающими внутренним огнем. Врожденная харизма завораживает окружающих, а демоническое наследие дает особые способности.",
        'dragonborn' => "Величественный драконорожденный-{$className} с чешуйчатой кожей и гордой осанкой. Драконье наследие проявляется в каждом жесте, а врожденная сила делает его грозным противником.",
        'orc' => "Мощный орк-{$className} с зеленой кожей и свирепым выражением лица. Оркская ярость в бою сочетается с неожиданной мудростью, накопленной в суровых условиях.",
        'gnome' => "Изобретательный гном-{$className} с живыми глазами и быстрыми руками. Гномья любознательность и технические навыки делают его уникальным специалистом в своем деле.",
        'half-elf' => "Гармоничный полуэльф-{$className}, сочетающий эльфийскую грацию с человеческой страстностью. Двойное наследие дает уникальные преимущества и глубокое понимание обеих культур.",
        'half-orc' => "Сильный полуорк-{$className}, объединяющий оркскую мощь с человеческой хитростью. Врожденная сила и выносливость делают его надежным союзником в любом деле."
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
                    $equipment[] = 'Зелье (генерируется отдельно)';
    
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

