<?php
// Убираем заголовки для использования в тестах
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}
require_once __DIR__ . '/../config.php';

class EnemyGenerator {
    private $dnd5e_api_url = 'http://www.dnd5eapi.co/api';
    private $deepseek_api_key;
    private $cache_dir;
    private $max_retries = 3;
    private $retry_delay = 1000; // миллисекунды
    
    public function __construct() {
        $this->deepseek_api_key = getApiKey('deepseek');
        $this->cache_dir = __DIR__ . '/../logs/cache';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Генерация противников на основе уровня угрозы
     */
    public function generateEnemies($params) {
        $threat_level = $params['threat_level'] ?? 'medium';
        $count = (int)($params['count'] ?? 1);
        $enemy_type = $params['enemy_type'] ?? '';
        $environment = $params['environment'] ?? '';
        $use_ai = true; // AI всегда включен
        
        // Валидация параметров
        if ($count < 1 || $count > 20) {
            throw new Exception('Количество противников должно быть от 1 до 20');
        }
        
        // Если threat_level пустой или 'random', генерируем случайный
        if (empty($threat_level) || $threat_level === 'random') {
            $threat_level = $this->getRandomThreatLevel();
        }
        
        // Проверяем, является ли threat_level числовым значением (конкретный CR)
        if (!in_array($threat_level, ['easy', 'medium', 'hard', 'deadly', 'random']) && !is_numeric($threat_level)) {
            throw new Exception('Неверный уровень угрозы. Должен быть easy, medium, hard, deadly, random или конкретный CR (0, 1, 2, 3...)');
        }
        
        // Определяем CR на основе уровня угрозы
        $cr_range = $this->getCRRange($threat_level);
        
        try {
            $enemies = [];
            error_log("EnemyGenerator: Начинаем генерацию противников. threat_level: $threat_level, count: $count");
            
            // Получаем список монстров из API с retry
            $monsters = $this->getMonstersListWithRetry();
            
            if (empty($monsters)) {
                throw new Exception('База данных монстров недоступна после нескольких попыток');
            }
            
            // Фильтруем монстров по CR и типу
            error_log("EnemyGenerator: Фильтруем монстров. CR range: " . json_encode($cr_range));
            $filtered_monsters = $this->filterMonsters($monsters, $cr_range, $enemy_type, $environment);
            error_log("EnemyGenerator: После фильтрации найдено монстров: " . count($filtered_monsters));
            
            // Если не найдено монстров, пробуем расширить диапазон
            if (empty($filtered_monsters)) {
                error_log("EnemyGenerator: Не найдены монстры, расширяем диапазон CR");
                $expanded_range = $this->expandCRRange($cr_range);
                $filtered_monsters = $this->filterMonsters($monsters, $expanded_range, $enemy_type, $environment);
                error_log("EnemyGenerator: После расширения найдено монстров: " . count($filtered_monsters));
            }
            
            if (empty($filtered_monsters)) {
                throw new Exception('Не найдены подходящие противники для указанных параметров');
            }
            
            // Если нужно много противников, выбираем один тип и генерируем несколько
            if ($count > 1) {
                $selected_monster = $filtered_monsters[array_rand($filtered_monsters)];
                $enemies = $this->generateMultipleEnemies($selected_monster, $count, $use_ai);
            } else {
                // Для одного противника выбираем случайного
                $selected_monster = $filtered_monsters[array_rand($filtered_monsters)];
                $enemy = $this->generateSingleEnemy($selected_monster, $use_ai);
                if ($enemy) {
                    $enemies[] = $enemy;
                }
            }
            
            if (empty($enemies)) {
                throw new Exception('Не удалось сгенерировать противников');
            }
            
            return [
                'success' => true,
                'enemies' => $enemies,
                'threat_level' => $threat_level,
                'threat_level_display' => $this->getThreatLevelDisplay($threat_level),
                'count' => count($enemies),
                'cr_range' => $cr_range,
                'cr_numeric' => is_numeric($threat_level) ? (int)$threat_level : null
            ];
            
        } catch (Exception $e) {
            error_log("EnemyGenerator: Ошибка генерации: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение списка монстров с retry логикой
     */
    private function getMonstersListWithRetry() {
        $cache_file = $this->cache_dir . '/monsters_list.json';
        $cache_time = 3600; // 1 час
        
        // Проверяем кэш
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
            $cached_data = json_decode(file_get_contents($cache_file), true);
            if ($cached_data && isset($cached_data['results'])) {
                error_log("EnemyGenerator: Используем кэшированный список монстров");
                return $cached_data;
            }
        }
        
        // Пробуем получить с retry
        for ($attempt = 1; $attempt <= $this->max_retries; $attempt++) {
            try {
                error_log("EnemyGenerator: Попытка $attempt получить список монстров");
                $monsters = $this->getMonstersList();
                
                if ($monsters && !empty($monsters)) {
                    // Сохраняем в кэш
                    file_put_contents($cache_file, json_encode($monsters));
                    return $monsters;
                }
            } catch (Exception $e) {
                error_log("EnemyGenerator: Попытка $attempt не удалась: " . $e->getMessage());
                if ($attempt < $this->max_retries) {
                    usleep($this->retry_delay * 1000); // Задержка перед следующей попыткой
                }
            }
        }
        
        throw new Exception('Не удалось получить список монстров после ' . $this->max_retries . ' попыток');
    }
    
    /**
     * Получение диапазона CR на основе уровня угрозы
     */
    private function getCRRange($threat_level) {
        switch ($threat_level) {
            case 'easy':
                return ['min' => 0, 'max' => 3, 'display' => 'Легкий (CR 0-3)'];
            case 'medium':
                return ['min' => 1, 'max' => 7, 'display' => 'Средний (CR 1-7)'];
            case 'hard':
                return ['min' => 5, 'max' => 12, 'display' => 'Сложный (CR 5-12)'];
            case 'deadly':
                return ['min' => 10, 'max' => 20, 'display' => 'Смертельный (CR 10-20)'];
            default:
                // Если передан конкретный CR, возвращаем его как диапазон
                if (is_numeric($threat_level)) {
                    $cr = (float)$threat_level;
                    return ['min' => $cr, 'max' => $cr, 'display' => "CR $cr"];
                }
                return ['min' => 1, 'max' => 5, 'display' => 'Средний (CR 1-5)'];
        }
    }
    
    /**
     * Получение отображения уровня угрозы
     */
    private function getThreatLevelDisplay($threat_level) {
        if (is_numeric($threat_level)) {
            return "CR $threat_level";
        }
        
        $displays = [
            'easy' => 'Легкий',
            'medium' => 'Средний', 
            'hard' => 'Сложный',
            'deadly' => 'Смертельный'
        ];
        return $displays[$threat_level] ?? 'Неизвестно';
    }
    
    /**
     * Перевод типов существ на русский
     */
    private function translateType($type) {
        $translations = [
            'beast' => 'Зверь',
            'humanoid' => 'Гуманоид',
            'dragon' => 'Дракон',
            'giant' => 'Великан',
            'undead' => 'Нежить',
            'fiend' => 'Исчадие',
            'celestial' => 'Небожитель',
            'elemental' => 'Элементаль',
            'fey' => 'Фей',
            'monstrosity' => 'Чудовище',
            'ooze' => 'Слизь',
            'plant' => 'Растение',
            'construct' => 'Конструкт',
            'aberration' => 'Аберрация',
            'swarm' => 'Рой',
            'dragon turtle' => 'Дракон-черепаха',
            'beholder' => 'Наблюдатель',
            'mind flayer' => 'Пожиратель разума',
            'lich' => 'Лич',
            'vampire' => 'Вампир',
            'werewolf' => 'Оборотень',
            'ghost' => 'Призрак',
            'skeleton' => 'Скелет',
            'zombie' => 'Зомби',
            'goblin' => 'Гоблин',
            'orc' => 'Орк',
            'troll' => 'Тролль',
            'ogre' => 'Огр',
            'hobgoblin' => 'Хобгоблин',
            'bugbear' => 'Багбир',
            'kobold' => 'Кобольд',
            'gnoll' => 'Гнолл'
        ];
        
        return $translations[strtolower($type)] ?? $type;
    }
    
    /**
     * Перевод сред обитания на русский
     */
    private function translateEnvironment($environment) {
        $translations = [
            'forest' => 'Лес',
            'mountain' => 'Горы',
            'desert' => 'Пустыня',
            'swamp' => 'Болото',
            'underdark' => 'Подземелье',
            'water' => 'Вода',
            'urban' => 'Город',
            'grassland' => 'Равнины',
            'hill' => 'Холмы',
            'coastal' => 'Побережье',
            'cave' => 'Пещера',
            'marsh' => 'Топи',
            'aquatic' => 'Водная среда',
            'arctic' => 'Арктика',
            'subterranean' => 'Подземелье',
            'dungeon' => 'Подземелье',
            'ruins' => 'Руины',
            'temple' => 'Храм',
            'tower' => 'Башня',
            'castle' => 'Замок',
            'village' => 'Деревня',
            'wilderness' => 'Дикая местность'
        ];
        
        return $translations[strtolower($environment)] ?? $environment;
    }
    
    /**
     * Генерация нескольких противников одного типа
     */
    private function generateMultipleEnemies($monster, $count, $use_ai) {
        $enemies = [];
        
        // Генерируем одного противника как шаблон
        $base_enemy = $this->generateSingleEnemy($monster, $use_ai);
        if (!$base_enemy) {
            return [];
        }
        
        if ($count === 1) {
            // Для одного противника возвращаем как есть
            $enemies[] = $base_enemy;
        } else {
            // Для нескольких противников создаем группу
            $group_enemy = $base_enemy;
            $group_enemy['name'] = $base_enemy['name'] . ' (x' . $count . ')';
            $group_enemy['count'] = $count;
            $group_enemy['is_group'] = true;
            $group_enemy['group_info'] = [
                'base_name' => $base_enemy['name'],
                'count' => $count,
                'individual_enemies' => []
            ];
            
            // Создаем индивидуальных противников для группы
            for ($i = 1; $i <= $count; $i++) {
                $individual = $base_enemy;
                $individual['name'] = $base_enemy['name'] . ' ' . $i;
                $individual['group_index'] = $i;
                $group_enemy['group_info']['individual_enemies'][] = $individual;
            }
            
            $enemies[] = $group_enemy;
        }
        
        return $enemies;
    }
    
    /**
     * Генерация одного противника
     */
    private function generateSingleEnemy($monster, $use_ai) {
        try {
            // Монстр уже содержит детальную информацию
            $monster_details = $monster;
            
            // Убеждаемся, что характеристики установлены
            if (!isset($monster_details['abilities']) || empty($monster_details['abilities'])) {
                $monster_details['abilities'] = [
                    'str' => 10,
                    'dex' => 10,
                    'con' => 10,
                    'int' => 10,
                    'wis' => 10,
                    'cha' => 10
                ];
            }
            
            // Генерируем базовые характеристики
            $enemy = [
                'name' => $monster_details['name'],
                'type' => $this->translateType($monster_details['type']),
                'challenge_rating' => $monster_details['challenge_rating'],
                'hit_points' => $monster_details['hit_points'] ?? 'Не определено',
                'armor_class' => $this->formatArmorClass($monster_details['armor_class']),
                'speed' => $this->formatSpeed($monster_details['speed'] ?? 'Не определено'),
                'abilities' => $this->formatAbilities($monster_details['abilities'] ?? []),
                'actions' => $this->formatActions($monster_details['actions'] ?? []),
                'special_abilities' => $this->formatSpecialAbilities($monster_details['special_abilities'] ?? []),
                'environment' => $this->translateEnvironment($monster_details['environment'] ?? 'Не определена'),
                'cr_numeric' => $this->parseCR($monster_details['challenge_rating'])
            ];
            
            // Если AI включен, генерируем описание и тактику
            if ($use_ai) {
                $enemy['description'] = $this->generateDescription($monster_details);
                $enemy['tactics'] = $this->generateTactics($monster_details);
            }
            
            return $enemy;
                
        } catch (Exception $e) {
            error_log("EnemyGenerator: Ошибка генерации противника: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получение детальной информации о монстре
     */
    private function getMonsterDetails($monster_url) {
        // URL приходит в формате "/api/2014/monsters/aboleth"
        $url = 'http://www.dnd5eapi.co' . $monster_url;
        return $this->makeRequest($url);
    }
    
    /**
     * Фильтрация монстров по параметрам
     */
    private function filterMonsters($monsters, $cr_range, $enemy_type, $environment) {
        $filtered = [];
        
        // API возвращает объект с ключами count и results
        if (!isset($monsters['results']) || !is_array($monsters['results'])) {
            error_log("EnemyGenerator: Неверная структура данных API: " . json_encode($monsters));
            return [];
        }
        
        // Берем больше монстров для поиска подходящих с полной информацией
        $sample_monsters = array_slice($monsters['results'], 0, 100);
        error_log("EnemyGenerator: Проверяем " . count($sample_monsters) . " монстров из списка");
        
        foreach ($sample_monsters as $monster) {
            try {
                error_log("EnemyGenerator: Обрабатываем монстра: " . ($monster['name'] ?? 'Без имени'));
                
                // Получаем детальную информацию о монстре
                if (!isset($monster['url'])) {
                    error_log("EnemyGenerator: Монстр не содержит URL: " . json_encode($monster));
                    continue;
                }
                
                $monster_details = $this->getMonsterDetails($monster['url']);
                if (!$monster_details) {
                    error_log("EnemyGenerator: Не удалось получить детали для монстра: " . ($monster['name'] ?? 'Без имени'));
                    continue;
                }
                
                // Проверяем полноту данных
                if (!$this->hasCompleteData($monster_details)) {
                    error_log("EnemyGenerator: Монстр {$monster_details['name']} не прошел проверку полноты данных");
                    continue;
                }
                
                error_log("EnemyGenerator: Монстр {$monster_details['name']} прошел проверку полноты данных");
                
            // Проверяем CR
                if (!isset($monster_details['challenge_rating'])) {
                    error_log("EnemyGenerator: Монстр не содержит CR: " . json_encode($monster_details));
                    continue;
                }
                
                error_log("EnemyGenerator: Проверяем монстра {$monster_details['name']} с CR {$monster_details['challenge_rating']} против диапазона " . json_encode($cr_range));
                
                if (!$this->checkCRRange($monster_details['challenge_rating'], $cr_range)) {
                    error_log("EnemyGenerator: Монстр {$monster_details['name']} не прошел проверку CR");
                    continue;
                }
                
                // Проверяем тип
                if (!isset($monster_details['type'])) {
                    error_log("EnemyGenerator: Монстр не содержит тип: " . json_encode($monster_details));
                    continue;
                }
                
                error_log("EnemyGenerator: Проверяем тип {$monster_details['type']} против запрошенного {$enemy_type}");
                
                if ($enemy_type && !$this->checkType($monster_details['type'], $enemy_type)) {
                    error_log("EnemyGenerator: Монстр {$monster_details['name']} не прошел проверку типа");
                continue;
            }
            
                // Проверяем среду (необязательно - пропускаем если нет информации)
                if ($environment && isset($monster_details['environment'])) {
                    if (!$this->checkEnvironment($monster_details, $environment)) {
                        error_log("EnemyGenerator: Монстр {$monster_details['name']} не прошел проверку среды");
                        continue;
                    }
                }
                // Если среда не указана или у монстра нет информации о среде, пропускаем проверку
                
                // Проверяем совместимость
                if (!$this->checkCompatibility($monster_details, $cr_range)) {
                    error_log("EnemyGenerator: Монстр {$monster_details['name']} не прошел проверку совместимости");
                continue;
            }
            
                error_log("EnemyGenerator: Монстр {$monster_details['name']} прошел все проверки!");
                
                $filtered[] = $monster_details;
                
                // Ограничиваем количество проверенных монстров
                if (count($filtered) >= 15) {
                    break;
                }
                
            } catch (Exception $e) {
                error_log("EnemyGenerator: Ошибка получения деталей монстра {$monster['name']}: " . $e->getMessage());
                continue;
            }
        }
        
        error_log("EnemyGenerator: Итоговое количество подходящих монстров: " . count($filtered));
        return $filtered;
    }
    
    /**
     * Проверка совместимости типа и среды с уровнем сложности
     */
    private function checkCompatibility($monster, $cr_range) {
        $cr = $this->parseCR($monster['challenge_rating']);
        $type = strtolower($monster['type']);
        
        // Драконы требуют минимальный CR 1
        if (strpos($type, 'dragon') !== false && $cr_range['min'] < 1) {
            return false;
        }
        
        // Великаны требуют минимальный CR 3
        if (strpos($type, 'giant') !== false && $cr_range['min'] < 3) {
            return false;
        }
        
        // Звери ограничены максимальным CR 8
        if (strpos($type, 'beast') !== false && $cr_range['max'] > 8) {
            return false;
        }
        
        // Подземелье требует минимальный CR 1
        if (isset($monster['environment']) && strpos(strtolower($monster['environment']), 'underdark') !== false && $cr_range['min'] < 1) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Проверка диапазона CR
     */
    private function checkCRRange($cr, $range) {
        $cr_value = $this->parseCR($cr);
        return $cr_value >= $range['min'] && $cr_value <= $range['max'];
    }
    
    /**
     * Проверка типа
     */
    private function checkType($monster_type, $requested_type) {
        return strpos(strtolower($monster_type), strtolower($requested_type)) !== false;
    }
    
    /**
     * Проверка среды
     */
    private function checkEnvironment($monster, $requested_environment) {
        if (!isset($monster['environment'])) {
            return false;
        }
        
        $monster_env = strtolower($monster['environment']);
        $requested_env = strtolower($requested_environment);
        
        // Маппинг сред
        $environment_mapping = [
            'forest' => ['forest', 'grassland', 'hill'],
            'mountain' => ['mountain', 'hill'],
            'desert' => ['desert'],
            'swamp' => ['swamp', 'marsh'],
            'underdark' => ['underdark', 'cave'],
            'water' => ['aquatic', 'coastal'],
            'urban' => ['urban', 'city']
        ];
        
        if (isset($environment_mapping[$requested_env])) {
            foreach ($environment_mapping[$requested_env] as $env) {
                if (strpos($monster_env, $env) !== false) {
                    return true;
                }
            }
        }
        
        return strpos($monster_env, $requested_env) !== false;
    }
    
    /**
     * Форматирование класса брони
     */
    private function formatArmorClass($ac) {
        if (is_array($ac)) {
            if (isset($ac[0]['value'])) {
                return $ac[0]['value'];
            } elseif (isset($ac[0])) {
                return $ac[0];
            }
            return 'Не определено';
        }
        return $ac;
    }
    
    /**
     * Форматирование скорости
     */
    private function formatSpeed($speed) {
        if (is_array($speed)) {
            $formatted = [];
            foreach ($speed as $type => $value) {
                if (is_string($type)) {
                    $translated_type = $this->translateSpeedType($type);
                    $translated_value = $this->translateSpeedValue($value);
                    $formatted[] = "$translated_type: $translated_value";
                } else {
                    $formatted[] = $this->translateSpeedValue($value);
                }
            }
            return implode(', ', $formatted);
        }
        return $this->translateSpeedValue($speed);
    }
    
    /**
     * Перевод типов скорости
     */
    private function translateSpeedType($type) {
        $translations = [
            'walk' => 'Ходьба',
            'fly' => 'Полёт',
            'swim' => 'Плавание',
            'climb' => 'Лазание',
            'burrow' => 'Рытьё',
            'hover' => 'Парение'
        ];
        
        return $translations[strtolower($type)] ?? $type;
    }
    
    /**
     * Перевод значений скорости
     */
    private function translateSpeedValue($value) {
        // Заменяем "ft." на "фт." и убираем лишние пробелы
        $value = str_replace('ft.', 'фт.', $value);
        $value = str_replace('ft', 'фт', $value);
        return trim($value);
    }
    
    /**
     * Форматирование характеристик
     */
    private function formatAbilities($abilities) {
        // Если характеристики не переданы, используем значения по умолчанию
        if (empty($abilities) || !is_array($abilities)) {
            $abilities = [
                'str' => 10,
                'dex' => 10,
                'con' => 10,
                'int' => 10,
                'wis' => 10,
                'cha' => 10
            ];
        }
        
        $formatted = [];
        $ability_names = [
            'str' => 'СИЛ',
            'dex' => 'ЛОВ',
            'con' => 'ТЕЛ',
            'int' => 'ИНТ',
            'wis' => 'МДР',
            'cha' => 'ХАР'
        ];
        
        // Обрабатываем каждую характеристику
        foreach ($ability_names as $eng_key => $rus_name) {
            $value = $abilities[$eng_key] ?? 10; // Значение по умолчанию 10
            
            // Убеждаемся, что значение числовое
            if (!is_numeric($value)) {
                $value = 10;
            }
            
            $modifier = $this->calculateModifier($value);
            
            // Сохраняем в русском формате
            $formatted[$rus_name] = [
                'value' => $value,
                'modifier' => $modifier
            ];
            
            // Также сохраняем оригинальные ключи для совместимости
            $formatted[$eng_key] = $value;
        }
        
        return $formatted;
    }
    
    /**
     * Расчет модификатора характеристики
     */
    private function calculateModifier($ability_score) {
        if (!is_numeric($ability_score)) {
            return 0;
        }
        return floor(($ability_score - 10) / 2);
    }
    
    /**
     * Форматирование действий
     */
    private function formatActions($actions) {
        if (!is_array($actions)) {
            return $actions;
        }
        
        $formatted = [];
        foreach ($actions as $action) {
            if (is_array($action)) {
                if (isset($action['name'])) {
                    $formatted[] = $this->translateAction($action['name']);
                } elseif (isset($action[0])) {
                    $formatted[] = $this->translateAction($action[0]);
                }
            } else {
                $formatted[] = $this->translateAction($action);
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование специальных способностей
     */
    private function formatSpecialAbilities($abilities) {
        if (!is_array($abilities)) {
            return $abilities;
        }
        
        $formatted = [];
        foreach ($abilities as $ability) {
            if (is_array($ability)) {
                if (isset($ability['name'])) {
                    $formatted[] = $this->translateAbility($ability['name']);
                } elseif (isset($ability[0])) {
                    $formatted[] = $this->translateAbility($ability[0]);
                }
            } else {
                $formatted[] = $this->translateAbility($ability);
            }
        }
        
        return $formatted;
    }
    
    /**
     * Перевод действий на русский
     */
    private function translateAction($action) {
        $translations = [
            'Warhammer' => 'Боевой молот',
            'Sword' => 'Меч',
            'Axe' => 'Топор',
            'Bow' => 'Лук',
            'Crossbow' => 'Арбалет',
            'Dagger' => 'Кинжал',
            'Spear' => 'Копье',
            'Mace' => 'Булава',
            'Staff' => 'Посох',
            'Wand' => 'Волшебная палочка',
            'Bite' => 'Укус',
            'Claw' => 'Коготь',
            'Tail' => 'Хвост',
            'Wing' => 'Крыло',
            'Horn' => 'Рог',
            'Tentacle' => 'Щупальце',
            'Spit' => 'Плевок',
            'Breath' => 'Дыхание',
            'Acid Breath' => 'Кислотное дыхание',
            'Fire Breath' => 'Огненное дыхание',
            'Lightning Breath' => 'Молниеносное дыхание',
            'Cold Breath' => 'Ледяное дыхание',
            'Poison Breath' => 'Ядовитое дыхание',
            'Spell' => 'Заклинание',
            'Magic' => 'Магия',
            'Attack' => 'Атака',
            'Multiattack' => 'Множественная атака',
            'Charge' => 'Рывок',
            'Grapple' => 'Захват',
            'Shove' => 'Толчок',
            'Dash' => 'Рывок',
            'Disengage' => 'Отход',
            'Dodge' => 'Уклонение',
            'Help' => 'Помощь',
            'Hide' => 'Скрытие',
            'Ready' => 'Готовность',
            'Search' => 'Поиск',
            'Use an Object' => 'Использование предмета',
            'Scimitar' => 'Скимитар',
            'Rapier' => 'Рапира',
            'Longsword' => 'Длинный меч',
            'Shortsword' => 'Короткий меч',
            'Greatsword' => 'Двуручный меч',
            'Battleaxe' => 'Боевой топор',
            'Greataxe' => 'Двуручный топор',
            'Handaxe' => 'Ручной топор',
            'Light Hammer' => 'Легкий молот',
            'Maul' => 'Кувалда',
            'Morningstar' => 'Моргенштерн',
            'Pike' => 'Пика',
            'Halberd' => 'Алебарда',
            'Glaive' => 'Глефа',
            'Trident' => 'Трезубец',
            'Net' => 'Сеть',
            'Whip' => 'Кнут',
            'Sling' => 'Праща',
            'Light Crossbow' => 'Легкий арбалет',
            'Heavy Crossbow' => 'Тяжелый арбалет',
            'Hand Crossbow' => 'Ручной арбалет',
            'Longbow' => 'Длинный лук',
            'Shortbow' => 'Короткий лук',
            'Blowgun' => 'Духовая трубка',
            'Dart' => 'Дротик',
            'Javelin' => 'Метательное копье',
            'Lance' => 'Копье',
            'Punch' => 'Удар кулаком',
            'Kick' => 'Удар ногой',
            'Headbutt' => 'Удар головой',
            'Trample' => 'Топтание',
            'Gore' => 'Бодание',
            'Constrict' => 'Сжатие',
            'Swallow' => 'Проглатывание',
            'Spit Acid' => 'Плевок кислотой',
            'Spit Poison' => 'Плевок ядом',
            'Spit Fire' => 'Плевок огнем',
            'Spit Lightning' => 'Плевок молнией',
            'Spit Cold' => 'Плевок льдом',
            'Slam' => 'Мощный удар',
            'Stab' => 'Колющий удар',
            'Slash' => 'Режущий удар',
            'Crush' => 'Дробящий удар',
            'Pounce' => 'Бросок',
            'Leap' => 'Прыжок',
            'Rush' => 'Натиск',
            'Tackle' => 'Захват',
            'Pin' => 'Прижимание',
            'Throw' => 'Бросок',
            'Swing' => 'Размашистый удар',
            'Thrust' => 'Толчок',
            'Strike' => 'Удар',
            'Hit' => 'Попадание',
            'Miss' => 'Промах',
            'Block' => 'Блок',
            'Parry' => 'Парирование',
            'Dodge' => 'Уклонение',
            'Counter' => 'Контратака',
            'Riposte' => 'Ответный удар',
            'Feint' => 'Финт',
            'Disarm' => 'Обезоруживание',
            'Trip' => 'Подножка',
            'Sunder' => 'Разрушение',
            'Cleave' => 'Рассечение',
            'Whirlwind' => 'Вихрь',
            'Spin' => 'Вращение',
            'Roll' => 'Кувырок',
            'Tumble' => 'Падение',
            'Crawl' => 'Ползание',
            'Climb' => 'Лазание',
            'Swim' => 'Плавание',
            'Fly' => 'Полёт',
            'Teleport' => 'Телепортация',
            'Phase' => 'Фазирование',
            'Shift' => 'Сдвиг',
            'Blink' => 'Мерцание',
            'Fade' => 'Исчезновение',
            'Appear' => 'Появление',
            'Vanish' => 'Исчезновение',
            'Materialize' => 'Материализация',
            'Dematerialize' => 'Дематериализация'
        ];
        
        return $translations[$action] ?? $action;
    }
    
    /**
     * Перевод способностей на русский
     */
    private function translateAbility($ability) {
        $translations = [
            'Heated Body' => 'Раскаленное тело',
            'Heated Weapons' => 'Раскаленное оружие',
            'Illumination' => 'Освещение',
            'Amphibious' => 'Земноводность',
            'Innate Spellcasting' => 'Врожденное колдовство',
            'Magic Resistance' => 'Сопротивление магии',
            'Magic Weapons' => 'Магическое оружие',
            'Regeneration' => 'Регенерация',
            'Spider Climb' => 'Лазание по стенам',
            'Web Walker' => 'Хождение по паутине',
            'Pack Tactics' => 'Тактика стаи',
            'Keen Senses' => 'Острые чувства',
            'Darkvision' => 'Темное зрение',
            'Blindsight' => 'Слепое зрение',
            'Tremorsense' => 'Чувство вибраций',
            'Truesight' => 'Истинное зрение',
            'Flying' => 'Полет',
            'Swimming' => 'Плавание',
            'Burrowing' => 'Рытье',
            'Climbing' => 'Лазание',
            'Hover' => 'Парение',
            'Invisible' => 'Невидимость',
            'Resistance' => 'Сопротивление',
            'Immunity' => 'Иммунитет',
            'Vulnerability' => 'Уязвимость',
            'Legendary Actions' => 'Легендарные действия',
            'Lair Actions' => 'Действия логова',
            'Regional Effects' => 'Региональные эффекты',
            'Acid Absorption' => 'Поглощение кислоты',
            'Fire Absorption' => 'Поглощение огня',
            'Lightning Absorption' => 'Поглощение молнии',
            'Cold Absorption' => 'Поглощение холода',
            'Poison Absorption' => 'Поглощение яда',
            'Acid Immunity' => 'Иммунитет к кислоте',
            'Fire Immunity' => 'Иммунитет к огню',
            'Lightning Immunity' => 'Иммунитет к молнии',
            'Cold Immunity' => 'Иммунитет к холоду',
            'Poison Immunity' => 'Иммунитет к яду',
            'Acid Resistance' => 'Сопротивление кислоте',
            'Fire Resistance' => 'Сопротивление огню',
            'Lightning Resistance' => 'Сопротивление молнии',
            'Cold Resistance' => 'Сопротивление холоду',
            'Poison Resistance' => 'Сопротивление яду',
            'Bludgeoning Resistance' => 'Сопротивление дробящему урону',
            'Piercing Resistance' => 'Сопротивление колющему урону',
            'Slashing Resistance' => 'Сопротивление режущему урону',
            'Necrotic Resistance' => 'Сопротивление некротическому урону',
            'Radiant Resistance' => 'Сопротивление излучению',
            'Psychic Resistance' => 'Сопротивление психическому урону',
            'Thunder Resistance' => 'Сопротивление звуковому урону',
            'Force Resistance' => 'Сопротивление силовому урону',
            'Acid Vulnerability' => 'Уязвимость к кислоте',
            'Fire Vulnerability' => 'Уязвимость к огню',
            'Lightning Vulnerability' => 'Уязвимость к молнии',
            'Cold Vulnerability' => 'Уязвимость к холоду',
            'Poison Vulnerability' => 'Уязвимость к яду',
            'Bludgeoning Vulnerability' => 'Уязвимость к дробящему урону',
            'Piercing Vulnerability' => 'Уязвимость к колющему урону',
            'Slashing Vulnerability' => 'Уязвимость к режущему урону',
            'Necrotic Vulnerability' => 'Уязвимость к некротическому урону',
            'Radiant Vulnerability' => 'Уязвимость к излучению',
            'Psychic Vulnerability' => 'Уязвимость к психическому урону',
            'Thunder Vulnerability' => 'Уязвимость к звуковому урону',
            'Force Vulnerability' => 'Уязвимость к силовому урону',
            'Sunlight Sensitivity' => 'Чувствительность к солнечному свету',
            'Water Breathing' => 'Водное дыхание',
            'Air Breathing' => 'Воздушное дыхание',
            'Amphibious' => 'Земноводность',
            'Shapechanger' => 'Оборотень',
            'Undead Fortitude' => 'Нежить-стойкость',
            'Turn Immunity' => 'Иммунитет к обращению',
            'Turn Resistance' => 'Сопротивление обращению',
            'Charm Immunity' => 'Иммунитет к очарованию',
            'Frightened Immunity' => 'Иммунитет к страху',
            'Paralyzed Immunity' => 'Иммунитет к параличу',
            'Petrified Immunity' => 'Иммунитет к окаменению',
            'Poisoned Immunity' => 'Иммунитет к отравлению',
            'Stunned Immunity' => 'Иммунитет к оглушению',
            'Unconscious Immunity' => 'Иммунитет к потере сознания',
            'Exhaustion Immunity' => 'Иммунитет к истощению',
            'Grappled Immunity' => 'Иммунитет к захвату',
            'Restrained Immunity' => 'Иммунитет к сковыванию',
            'Prone Immunity' => 'Иммунитет к опрокидыванию',
            'Blinded Immunity' => 'Иммунитет к ослеплению',
            'Deafened Immunity' => 'Иммунитет к оглушению',
            'Silenced Immunity' => 'Иммунитет к замалчиванию',
            'Invisible Immunity' => 'Иммунитет к невидимости',
            'Hidden Immunity' => 'Иммунитет к скрытию',
            'Surprised Immunity' => 'Иммунитет к неожиданности',
            'Incapacitated Immunity' => 'Иммунитет к недееспособности',
            'Incapacitated' => 'Недееспособность',
            'Charmed' => 'Очарованный',
            'Frightened' => 'Испуганный',
            'Paralyzed' => 'Парализованный',
            'Petrified' => 'Окаменевший',
            'Poisoned' => 'Отравленный',
            'Stunned' => 'Оглушенный',
            'Unconscious' => 'Без сознания',
            'Exhausted' => 'Истощенный',
            'Grappled' => 'Захваченный',
            'Restrained' => 'Скованный',
            'Prone' => 'Опрокинутый',
            'Blinded' => 'Ослепленный',
            'Deafened' => 'Оглушенный',
            'Silenced' => 'Замалчиваемый',
            'Antimagic Susceptibility' => 'Восприимчивость к антимагии',
            'False Appearance' => 'Ложный облик',
            'Magic Resistance' => 'Сопротивление магии',
            'Magic Weapons' => 'Магическое оружие',
            'Regeneration' => 'Регенерация',
            'Spider Climb' => 'Лазание по стенам',
            'Web Walker' => 'Хождение по паутине',
            'Pack Tactics' => 'Тактика стаи',
            'Keen Senses' => 'Острые чувства',
            'Darkvision' => 'Темное зрение',
            'Blindsight' => 'Слепое зрение',
            'Tremorsense' => 'Чувство вибраций',
            'Truesight' => 'Истинное зрение',
            'Flying' => 'Полет',
            'Swimming' => 'Плавание',
            'Burrowing' => 'Рытье',
            'Climbing' => 'Лазание',
            'Hover' => 'Парение',
            'Invisible' => 'Невидимость',
            'Resistance' => 'Сопротивление',
            'Immunity' => 'Иммунитет',
            'Vulnerability' => 'Уязвимость',
            'Legendary Actions' => 'Легендарные действия',
            'Lair Actions' => 'Действия логова',
            'Regional Effects' => 'Региональные эффекты',
            'Acid Absorption' => 'Поглощение кислоты',
            'Fire Absorption' => 'Поглощение огня',
            'Lightning Absorption' => 'Поглощение молнии',
            'Cold Absorption' => 'Поглощение холода',
            'Poison Absorption' => 'Поглощение яда',
            'Acid Immunity' => 'Иммунитет к кислоте',
            'Fire Immunity' => 'Иммунитет к огню',
            'Lightning Immunity' => 'Иммунитет к молнии',
            'Cold Immunity' => 'Иммунитет к холоду',
            'Poison Immunity' => 'Иммунитет к яду',
            'Acid Resistance' => 'Сопротивление кислоте',
            'Fire Resistance' => 'Сопротивление огню',
            'Lightning Resistance' => 'Сопротивление молнии',
            'Cold Resistance' => 'Сопротивление холоду',
            'Poison Resistance' => 'Сопротивление яду',
            'Bludgeoning Resistance' => 'Сопротивление дробящему урону',
            'Piercing Resistance' => 'Сопротивление колющему урону',
            'Slashing Resistance' => 'Сопротивление режущему урону',
            'Necrotic Resistance' => 'Сопротивление некротическому урону',
            'Radiant Resistance' => 'Сопротивление излучению',
            'Psychic Resistance' => 'Сопротивление психическому урону',
            'Thunder Resistance' => 'Сопротивление звуковому урону',
            'Force Resistance' => 'Сопротивление силовому урону',
            'Acid Vulnerability' => 'Уязвимость к кислоте',
            'Fire Vulnerability' => 'Уязвимость к огню',
            'Lightning Vulnerability' => 'Уязвимость к молнии',
            'Cold Vulnerability' => 'Уязвимость к холоду',
            'Poison Vulnerability' => 'Уязвимость к яду',
            'Bludgeoning Vulnerability' => 'Уязвимость к дробящему урону',
            'Piercing Vulnerability' => 'Уязвимость к колющему урону',
            'Slashing Vulnerability' => 'Уязвимость к режущему урону',
            'Necrotic Vulnerability' => 'Уязвимость к некротическому урону',
            'Radiant Vulnerability' => 'Уязвимость к излучению',
            'Psychic Vulnerability' => 'Уязвимость к психическому урону',
            'Thunder Vulnerability' => 'Уязвимость к звуковому урону',
            'Force Vulnerability' => 'Уязвимость к силовому урону',
            'Sunlight Sensitivity' => 'Чувствительность к солнечному свету',
            'Water Breathing' => 'Водное дыхание',
            'Air Breathing' => 'Воздушное дыхание',
            'Amphibious' => 'Земноводность',
            'Shapechanger' => 'Оборотень',
            'Undead Fortitude' => 'Нежить-стойкость',
            'Turn Immunity' => 'Иммунитет к обращению',
            'Turn Resistance' => 'Сопротивление обращению',
            'Charm Immunity' => 'Иммунитет к очарованию',
            'Frightened Immunity' => 'Иммунитет к страху',
            'Paralyzed Immunity' => 'Иммунитет к параличу',
            'Petrified Immunity' => 'Иммунитет к окаменению',
            'Poisoned Immunity' => 'Иммунитет к отравлению',
            'Stunned Immunity' => 'Иммунитет к оглушению',
            'Unconscious Immunity' => 'Иммунитет к потере сознания',
            'Exhaustion Immunity' => 'Иммунитет к истощению',
            'Grappled Immunity' => 'Иммунитет к захвату',
            'Restrained Immunity' => 'Иммунитет к сковыванию',
            'Prone Immunity' => 'Иммунитет к опрокидыванию',
            'Blinded Immunity' => 'Иммунитет к ослеплению',
            'Deafened Immunity' => 'Иммунитет к оглушению',
            'Silenced Immunity' => 'Иммунитет к замалчиванию',
            'Invisible Immunity' => 'Иммунитет к невидимости',
            'Hidden Immunity' => 'Иммунитет к скрытию',
            'Surprised Immunity' => 'Иммунитет к неожиданности',
            'Incapacitated Immunity' => 'Иммунитет к недееспособности',
            'Incapacitated' => 'Недееспособность',
            'Charmed' => 'Очарованный',
            'Frightened' => 'Испуганный',
            'Paralyzed' => 'Парализованный',
            'Petrified' => 'Окаменевший',
            'Poisoned' => 'Отравленный',
            'Stunned' => 'Оглушенный',
            'Unconscious' => 'Без сознания',
            'Exhausted' => 'Истощенный',
            'Grappled' => 'Захваченный',
            'Restrained' => 'Скованный',
            'Prone' => 'Опрокинутый',
            'Blinded' => 'Ослепленный',
            'Deafened' => 'Оглушенный',
            'Silenced' => 'Замалчиваемый'
        ];
        
        return $translations[$ability] ?? $ability;
    }
    
    /**
     * Проверка полноты данных монстра
     */
    private function hasCompleteData($monster) {
        // Проверяем только самые важные поля
        $required_fields = ['name', 'type', 'challenge_rating'];
        
        foreach ($required_fields as $field) {
            if (!isset($monster[$field]) || empty($monster[$field])) {
                error_log("EnemyGenerator: Монстр не имеет обязательного поля '$field'");
                return false;
            }
        }
        
        // Хиты и класс брони могут быть не указаны у некоторых монстров
        if (!isset($monster['hit_points']) || empty($monster['hit_points'])) {
            error_log("EnemyGenerator: Монстр {$monster['name']} не имеет хитов, используем значение по умолчанию");
            $monster['hit_points'] = 'Не определено';
        }
        
        if (!isset($monster['armor_class']) || empty($monster['armor_class'])) {
            error_log("EnemyGenerator: Монстр {$monster['name']} не имеет класса брони, используем значение по умолчанию");
            $monster['armor_class'] = 'Не определено';
        }
        
        // Характеристики могут быть не указаны у некоторых монстров
        if (!isset($monster['abilities']) || empty($monster['abilities'])) {
            error_log("EnemyGenerator: Монстр {$monster['name']} не имеет характеристик, используем значения по умолчанию");
            $monster['abilities'] = [
                'str' => 10,
                'dex' => 10,
                'con' => 10,
                'int' => 10,
                'wis' => 10,
                'cha' => 10
            ];
        }
        
        return true;
    }
    
    /**
     * Парсинг CR в числовое значение
     */
    private function parseCR($cr) {
        if (is_numeric($cr)) {
            return (float)$cr;
        }
        
        // Обработка дробных CR (например, "1/4", "1/2")
        if (strpos($cr, '/') !== false) {
            $parts = explode('/', $cr);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                return (float)$parts[0] / (float)$parts[1];
            }
        }
        
        // Обработка специальных случаев
        $cr_map = [
            '0' => 0,
            '1/8' => 0.125,
            '1/4' => 0.25,
            '1/2' => 0.5
        ];
        
        return $cr_map[$cr] ?? 0;
    }
    
    /**
     * Генерация описания с помощью AI
     */
    private function generateDescription($monster) {
        try {
            $prompt = "Опиши кратко монстра {$monster['name']} ({$monster['type']}) с CR {$monster['challenge_rating']}. " .
                     "Опиши его внешний вид, характер и поведение. Ответ должен быть на русском языке, 2-3 предложения.";
            
            return $this->generateWithAI($prompt);
        } catch (Exception $e) {
            error_log("EnemyGenerator: Ошибка генерации описания: " . $e->getMessage());
            return "Монстр {$monster['name']} - {$monster['type']} с уровнем сложности {$monster['challenge_rating']}.";
        }
    }
    
    /**
     * Генерация тактики с помощью AI
     */
    private function generateTactics($monster) {
        try {
            $prompt = "Опиши тактику боя для монстра {$monster['name']} ({$monster['type']}) с CR {$monster['challenge_rating']}. " .
                     "Как он должен действовать в бою? Ответ должен быть на русском языке, 2-3 предложения.";
            
            return $this->generateWithAI($prompt);
        } catch (Exception $e) {
            error_log("EnemyGenerator: Ошибка генерации тактики: " . $e->getMessage());
            return "Монстр использует стандартную тактику для своего типа и уровня сложности.";
        }
    }

    /**
     * Генерация с помощью AI
     */
    private function generateWithAI($prompt) {
        if (!$this->deepseek_api_key) {
            return null;
        }
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Создавай интересных и атмосферных описаний для монстров.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 150,
            'temperature' => 0.8
        ];
        
        // Используем file_get_contents вместо cURL
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->deepseek_api_key
                ],
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ]);
        
        try {
            $response = file_get_contents('https://api.deepseek.com/v1/chat/completions', false, $context);
            
            if ($response === false) {
                error_log("AI API request failed: file_get_contents returned false");
                return null;
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("AI API request failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Выполнение HTTP запроса с улучшенной обработкой ошибок
     */
    private function makeRequest($url) {
        error_log("EnemyGenerator: makeRequest для URL: $url");
        
        // Используем file_get_contents вместо cURL
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: DnD-Copilot/1.0',
                    'Accept: application/json'
                ],
                'timeout' => 30
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $start_time = microtime(true);
        $response = @file_get_contents($url, false, $context);
        $end_time = microtime(true);
        
        $request_time = round(($end_time - $start_time) * 1000, 2);
        error_log("EnemyGenerator: Запрос завершен за {$request_time}ms");
        
        if ($response === false) {
            $error = error_get_last();
            $error_msg = $error ? $error['message'] : 'Неизвестная ошибка';
            error_log("EnemyGenerator: file_get_contents failed: $error_msg");
            throw new Exception("Не удалось получить данные от API: $error_msg");
        }
        
            error_log("EnemyGenerator: Успешный ответ, размер: " . strlen($response) . " байт");
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                error_log("EnemyGenerator: JSON успешно декодирован");
                return $decoded;
            } else {
                error_log("EnemyGenerator: JSON decode error for $url: " . json_last_error_msg());
                throw new Exception("Ошибка разбора ответа API");
            }
        }
        
    /**
     * Выполнение AI запроса
     */
    private function makeAIRequest($url, $data) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->deepseek_api_key,
                    'User-Agent: DnD-Copilot/1.0'
                ],
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            $error_msg = $error ? $error['message'] : 'Неизвестная ошибка';
            error_log("EnemyGenerator: AI API request failed: $error_msg");
            throw new Exception("Не удалось получить ответ от AI API: $error_msg");
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("EnemyGenerator: AI API JSON decode error: " . json_last_error_msg());
            throw new Exception("Ошибка парсинга AI API ответа");
        }
        
        return $result;
    }

    /**
     * Получение случайного уровня угрозы
     */
    private function getRandomThreatLevel() {
        $levels = ['easy', 'medium', 'hard', 'deadly'];
        return $levels[array_rand($levels)];
    }

    /**
     * Расширение диапазона CR если не найдены монстры
     */
    private function expandCRRange($cr_range) {
        $expanded = $cr_range;
        
        // Расширяем диапазон на 2 в каждую сторону
        if ($expanded['min'] > 0) {
            $expanded['min'] = max(0, $expanded['min'] - 2);
        }
        $expanded['max'] = min(30, $expanded['max'] + 2);
        
        error_log("EnemyGenerator: Расширенный CR диапазон: " . json_encode($expanded));
        return $expanded;
    }
    
    /**
     * Получение списка монстров
     */
    private function getMonstersList() {
        $url = $this->dnd5e_api_url . '/monsters';
        return $this->makeRequest($url);
    }
}

// Обработка запроса
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("EnemyGenerator: Получен POST запрос с данными: " . json_encode($_POST));
    
    try {
        $generator = new EnemyGenerator();
        $result = $generator->generateEnemies($_POST);
        
        error_log("EnemyGenerator: Результат генерации: " . json_encode($result));
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("EnemyGenerator: Критическая ошибка: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>
