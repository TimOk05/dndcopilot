<?php
// Убираем заголовки для использования в тестах
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}
require_once __DIR__ . '/../../config/config.php';

class EnemyGenerator {
    private $dnd5e_api_url = 'https://www.dnd5eapi.co/api';
    private $deepseek_api_key;
    private $cache_dir;
    private $max_retries = 3;
    private $retry_delay = 1000; // миллисекунды
    
    public function __construct() {
        $this->deepseek_api_key = getApiKey('deepseek');
        $this->cache_dir = __DIR__ . '/../../data/cache/dnd_api';
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
        $use_ai = isset($params['use_ai']) ? ($params['use_ai'] === 'on') : true;
        
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
            logMessage('INFO', "EnemyGenerator: Начинаем генерацию противников. threat_level: $threat_level, count: $count");
            
            // Получаем список монстров из API с retry
            $monsters = $this->getMonstersListWithRetry();
            
            // Если API недоступен, возвращаем ошибку (NO_FALLBACK политика)
            if (empty($monsters)) {
                throw new Exception('База данных монстров недоступна. Все данные должны поступать из внешних API.');
            }
            
            // Фильтруем монстров по CR и типу
            logMessage('INFO', "EnemyGenerator: Фильтруем монстров. CR range: " . json_encode($cr_range));
            $filtered_monsters = $this->filterMonsters($monsters, $cr_range, $enemy_type, $environment);
            logMessage('INFO', "EnemyGenerator: После фильтрации найдено монстров: " . count($filtered_monsters));
            
            // Если не найдено монстров, пробуем расширить диапазон
            if (empty($filtered_monsters)) {
                logMessage('INFO', "EnemyGenerator: Не найдены монстры, расширяем диапазон CR");
                $expanded_range = $this->expandCRRange($cr_range);
                $filtered_monsters = $this->filterMonsters($monsters, $expanded_range, $enemy_type, $environment);
                logMessage('INFO', "EnemyGenerator: После расширения найдено монстров: " . count($filtered_monsters));
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
                'cr_numeric' => is_numeric($threat_level) ? (float)$threat_level : null
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', "EnemyGenerator: Ошибка генерации: " . $e->getMessage());
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
                logMessage('INFO', "EnemyGenerator: Используем кэшированный список монстров");
                return $cached_data;
            }
        }
        
        // Пробуем получить с retry
        for ($attempt = 1; $attempt <= $this->max_retries; $attempt++) {
            try {
                logMessage('INFO', "EnemyGenerator: Попытка $attempt получить список монстров");
                $monsters = $this->getMonstersList();
                
                if ($monsters && !empty($monsters)) {
                    // Сохраняем в кэш
                    file_put_contents($cache_file, json_encode($monsters));
                    return $monsters;
                }
            } catch (Exception $e) {
                logMessage('WARNING', "EnemyGenerator: Попытка $attempt не удалась: " . $e->getMessage());
                if ($attempt < $this->max_retries) {
                    usleep($this->retry_delay * 1000); // Задержка перед следующей попыткой
                }
            }
        }
        
        // Для локальной разработки используем fallback данные
        if ($this->isLocalDevelopment()) {
            logMessage('INFO', "EnemyGenerator: Используем fallback данные для локальной разработки");
            return $this->getFallbackMonstersList();
        }
        
        // На продакшне возвращаем null - это приведет к ошибке (NO_FALLBACK политика)
        logMessage('ERROR', "EnemyGenerator: API недоступен после всех попыток");
        return null;
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
     * Фильтрация монстров по параметрам с расширенной базой
     */
    private function filterMonsters($monsters, $cr_range, $enemy_type, $environment) {
        $filtered = [];
        $checked_count = 0;
        $max_checks = 100; // Увеличиваем количество проверок для расширенной базы
        
        // Сначала проверяем API монстров
        if (isset($monsters['results']) && !empty($monsters['results'])) {
        foreach ($monsters['results'] as $monster) {
            if ($checked_count >= $max_checks) {
                break;
            }
            $checked_count++;
            
            try {
                // Получаем детали монстра
                $monster_details = $this->getMonsterDetails($monster['index']);
                
                if (!$monster_details || !$this->hasCompleteData($monster_details)) {
                    continue;
                }
                
            // Проверяем CR
                if (!isset($monster_details['challenge_rating'])) {
                    continue;
                }
                
                if (!$this->checkCRRange($monster_details['challenge_rating'], $cr_range)) {
                    continue;
                }
                
                // Проверяем тип
                if (!isset($monster_details['type'])) {
                    continue;
                }
                
                if ($enemy_type && !$this->checkType($monster_details['type'], $enemy_type)) {
                continue;
            }
            
                // Проверяем среду (необязательно - пропускаем если нет информации)
                if ($environment && isset($monster_details['environment'])) {
                    if (!$this->checkEnvironment($monster_details, $environment)) {
                        continue;
                    }
                }
                
                // Проверяем совместимость
                if (!$this->checkCompatibility($monster_details, $cr_range)) {
                continue;
            }
                
                $filtered[] = $monster_details;
                
                // Ограничиваем количество проверенных монстров
                    if (count($filtered) >= 20) {
                    break;
                }
                
            } catch (Exception $e) {
                logMessage('WARNING', "EnemyGenerator: Ошибка получения деталей монстра {$monster['name']}: " . $e->getMessage());
                continue;
                }
            }
        }
        
        // NO_FALLBACK политика: не добавляем данные из внутренней базы
        
        logMessage('INFO', "EnemyGenerator: Итоговое количество подходящих монстров: " . count($filtered));
        return $filtered;
    }
    
    /**
     * Получение деталей монстра
     */
    private function getMonsterDetails($monster_index) {
        $cache_file = $this->cache_dir . '/monster_' . md5($monster_index) . '.json';
        $cache_time = 7200; // 2 часа
        
        // Проверяем кэш
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
            $cached_data = json_decode(file_get_contents($cache_file), true);
            if ($cached_data) {
                return $cached_data;
            }
        }
        
        // Для локальной разработки используем fallback данные
        if ($this->isLocalDevelopment()) {
            logMessage('INFO', "EnemyGenerator: Используем fallback данные монстра для локальной разработки");
            return $this->getFallbackMonsterData($monster_index);
        }
        
        // Получаем данные из API
        $url = $this->dnd5e_api_url . '/monsters/' . $monster_index;
        $monster_data = $this->makeRequest($url);
        
        if ($monster_data) {
            // Сохраняем в кэш
            file_put_contents($cache_file, json_encode($monster_data));
        }
        
        return $monster_data;
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
     * Генерация одного противника
     */
    private function generateSingleEnemy($monster, $use_ai) {
        try {
            $enemy = [
                'name' => $monster['name'],
                'type' => $this->translateType($monster['type']),
                'challenge_rating' => $monster['challenge_rating'],
                'hit_points' => $this->formatHitPoints($monster['hit_points'] ?? 'Не определено'),
                'armor_class' => $this->formatArmorClass($monster['armor_class'] ?? 'Не определено'),
                'speed' => $this->formatSpeed($monster['speed'] ?? 'Не определено'),
                'abilities' => $this->formatAbilities($monster['abilities'] ?? [], $monster['type'] ?? ''),
                'actions' => $this->formatActions($monster['actions'] ?? []),
                'special_abilities' => $this->formatSpecialAbilities($monster['special_abilities'] ?? []),
                'legendary_actions' => $this->formatLegendaryActions($monster['legendary_actions'] ?? []),
                'lair_actions' => $this->formatLairActions($monster['lair_actions'] ?? []),
                'damage_vulnerabilities' => $this->formatDamageModifiers($monster['damage_vulnerabilities'] ?? []),
                'damage_resistances' => $this->formatDamageModifiers($monster['damage_resistances'] ?? []),
                'damage_immunities' => $this->formatDamageModifiers($monster['damage_immunities'] ?? []),
                'condition_immunities' => $this->formatConditionImmunities($monster['condition_immunities'] ?? []),
                'senses' => $this->formatSenses($monster['senses'] ?? []),
                'languages' => $this->formatLanguages($monster['languages'] ?? []),
                'alignment' => $monster['alignment'] ?? 'Не определено',
                'size' => $this->translateSize($monster['size'] ?? 'medium'),
                'xp' => $monster['xp'] ?? 0
            ];
            
            // Генерируем описание и тактику с AI (всегда включено)
                $description_result = $this->generateDescription($monster);
                if (isset($description_result['error'])) {
                logMessage('WARNING', "AI генерация описания не удалась: " . $description_result['message']);
                $enemy['description'] = "Описание недоступно (AI API недоступен)";
                } else {
                    $enemy['description'] = $description_result;
                }
                
                $tactics_result = $this->generateTactics($monster);
                if (isset($tactics_result['error'])) {
                logMessage('WARNING', "AI генерация тактики не удалась: " . $tactics_result['message']);
                $enemy['tactics'] = "Тактика недоступна (AI API недоступен)";
                } else {
                    $enemy['tactics'] = $tactics_result;
                }
            // } else {
            //     // AI всегда включен - это основной функционал
            //     // Если AI отключен, возвращаем ошибку
            //     // return [
            //     //     'success' => false,
            //     //     'error' => 'AI отключен',
            //     //     'message' => 'Генерация противника невозможна без AI API',
            //     //     'details' => 'Включите AI генерацию для создания противников с описаниями и тактикой'
            //     // ];
            //     
            //     // Продолжаем генерацию без AI описаний
            //     logMessage('INFO', 'AI генерация отключена, создаем противника без описаний');
            // }
            
            return $enemy;
            
        } catch (Exception $e) {
            logMessage('ERROR', "EnemyGenerator: Ошибка генерации противника: " . $e->getMessage());
            return null;
        }
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
     * Форматирование хитов
     */
    private function formatHitPoints($hp) {
        if (is_array($hp)) {
            if (isset($hp['average'])) {
                return $hp['average'];
            } elseif (isset($hp[0])) {
                return $hp[0];
            }
            return 'Не определено';
        }
        return $hp;
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
    private function formatAbilities($abilities, $monsterType = '') {
        if (empty($abilities)) {
            // Генерируем случайные характеристики для каждого монстра
            return [
                'str' => $this->generateRandomAbility($monsterType),
                'dex' => $this->generateRandomAbility($monsterType),
                'con' => $this->generateRandomAbility($monsterType),
                'int' => $this->generateRandomAbility($monsterType),
                'wis' => $this->generateRandomAbility($monsterType),
                'cha' => $this->generateRandomAbility($monsterType)
            ];
        }
        
        $formatted = [];
        $ability_names = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
        
        foreach ($ability_names as $ability) {
            if (isset($abilities[$ability])) {
                $formatted[$ability] = $abilities[$ability];
            } else {
                $formatted[$ability] = $this->generateRandomAbility($monsterType);
            }
        }
        
        return $formatted;
    }
    
    /**
     * Генерация случайной характеристики с учетом типа монстра
     */
    private function generateRandomAbility($monsterType = '') {
        $baseRange = [8, 18];
        
        // Корректируем диапазон в зависимости от типа монстра
        switch (strtolower($monsterType)) {
            case 'dragon':
            case 'giant':
            case 'demon':
            case 'devil':
                $baseRange = [12, 20]; // Сильные монстры
                break;
            case 'goblin':
            case 'kobold':
            case 'imp':
                $baseRange = [6, 14]; // Слабые монстры
                break;
            case 'undead':
            case 'construct':
                $baseRange = [10, 16]; // Средние монстры
                break;
        }
        
        return rand($baseRange[0], $baseRange[1]);
    }
    
    /**
     * Проверка, является ли это локальной разработкой
     */
    private function isLocalDevelopment() {
        // Проверяем по домену или IP
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $isLocal = strpos($host, 'localhost') !== false || 
                   strpos($host, '127.0.0.1') !== false ||
                   strpos($host, '192.168.') !== false ||
                   strpos($host, '10.0.') !== false;
        
        // Проверяем, является ли это продакшн доменом
        $isProduction = strpos($host, 'tim.dat-studio.com') !== false;
        
        logMessage('INFO', "EnemyGenerator: Host: $host, isLocal: " . ($isLocal ? 'true' : 'false') . ", isProduction: " . ($isProduction ? 'true' : 'false'));
        
        // Fallback данные используются ТОЛЬКО для локальной разработки
        // На продакшне всегда используются данные из внешних API
        return $isLocal && !$isProduction;
    }
    
    /**
     * Получение fallback списка монстров для локальной разработки
     */
    private function getFallbackMonstersList() {
        return [
            'results' => [
                // Easy (CR 0-3)
                ['name' => 'Kobold', 'url' => '/api/monsters/kobold', 'index' => 'kobold'],
                ['name' => 'Goblin', 'url' => '/api/monsters/goblin', 'index' => 'goblin'],
                ['name' => 'Bandit', 'url' => '/api/monsters/bandit', 'index' => 'bandit'],
                ['name' => 'Cultist', 'url' => '/api/monsters/cultist', 'index' => 'cultist'],
                ['name' => 'Skeleton', 'url' => '/api/monsters/skeleton', 'index' => 'skeleton'],
                ['name' => 'Zombie', 'url' => '/api/monsters/zombie', 'index' => 'zombie'],
                ['name' => 'Wolf', 'url' => '/api/monsters/wolf', 'index' => 'wolf'],
                ['name' => 'Bear', 'url' => '/api/monsters/bear', 'index' => 'bear'],
                ['name' => 'Spider', 'url' => '/api/monsters/spider', 'index' => 'spider'],
                ['name' => 'Orc', 'url' => '/api/monsters/orc', 'index' => 'orc'],
                
                // Medium (CR 4-7)
                ['name' => 'Ogre', 'url' => '/api/monsters/ogre', 'index' => 'ogre'],
                ['name' => 'Troll', 'url' => '/api/monsters/troll', 'index' => 'troll'],
                ['name' => 'Hill Giant', 'url' => '/api/monsters/hill-giant', 'index' => 'hill-giant'],
                ['name' => 'Wyvern', 'url' => '/api/monsters/wyvern', 'index' => 'wyvern'],
                ['name' => 'Manticore', 'url' => '/api/monsters/manticore', 'index' => 'manticore'],
                
                // Hard (CR 8-12)
                ['name' => 'Dragon', 'url' => '/api/monsters/dragon', 'index' => 'dragon'],
                ['name' => 'Giant', 'url' => '/api/monsters/giant', 'index' => 'giant'],
                ['name' => 'Demon', 'url' => '/api/monsters/demon', 'index' => 'demon'],
                ['name' => 'Devil', 'url' => '/api/monsters/devil', 'index' => 'devil'],
                ['name' => 'Lich', 'url' => '/api/monsters/lich', 'index' => 'lich'],
                
                // Deadly (CR 13+)
                ['name' => 'Ancient Dragon', 'url' => '/api/monsters/ancient-dragon', 'index' => 'ancient-dragon'],
                ['name' => 'Tarrasque', 'url' => '/api/monsters/tarrasque', 'index' => 'tarrasque'],
                ['name' => 'Balor', 'url' => '/api/monsters/balor', 'index' => 'balor'],
                ['name' => 'Pit Fiend', 'url' => '/api/monsters/pit-fiend', 'index' => 'pit-fiend'],
                ['name' => 'Undead', 'url' => '/api/monsters/undead', 'index' => 'undead'],
                ['name' => 'Construct', 'url' => '/api/monsters/construct', 'index' => 'construct']
            ]
        ];
    }
    
    /**
     * Получение fallback данных монстра для локальной разработки
     */
    private function getFallbackMonsterData($monster_index) {
        $monsterName = basename($monster_index);
        
        // Базовые данные для каждого типа монстра
        $fallbackData = [
            // Easy (CR 0-3)
            'kobold' => [
                'name' => 'Kobold',
                'type' => 'humanoid',
                'challenge_rating' => '1/8',
                'hit_points' => '5 (2d6 - 2)',
                'armor_class' => '12',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 7, 'dex' => 15, 'con' => 9,
                    'int' => 8, 'wis' => 7, 'cha' => 8
                ],
                'actions' => [
                    ['name' => 'Dagger', 'desc' => 'Melee Weapon Attack: +4 to hit, reach 5 ft., one target. Hit: 4 (1d4 + 2) piercing damage.']
                ]
            ],
            'goblin' => [
                'name' => 'Goblin',
                'type' => 'humanoid',
                'challenge_rating' => '1/4',
                'hit_points' => '7 (2d6)',
                'armor_class' => '15 (leather armor, shield)',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 8, 'dex' => 14, 'con' => 10,
                    'int' => 10, 'wis' => 8, 'cha' => 8
                ],
                'actions' => [
                    ['name' => 'Scimitar', 'desc' => 'Melee Weapon Attack: +4 to hit, reach 5 ft., one target. Hit: 5 (1d6 + 2) slashing damage.']
                ]
            ],
            'orc' => [
                'name' => 'Orc',
                'type' => 'humanoid',
                'challenge_rating' => '1/2',
                'hit_points' => '15 (2d8 + 6)',
                'armor_class' => '13 (hide armor)',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 16, 'dex' => 12, 'con' => 16,
                    'int' => 7, 'wis' => 11, 'cha' => 10
                ],
                'actions' => [
                    ['name' => 'Greataxe', 'desc' => 'Melee Weapon Attack: +5 to hit, reach 5 ft., one target. Hit: 9 (1d12 + 3) slashing damage.']
                ]
            ],
            'bandit' => [
                'name' => 'Bandit',
                'type' => 'humanoid',
                'challenge_rating' => '1/8',
                'hit_points' => '11 (2d8 + 2)',
                'armor_class' => '12 (leather armor)',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 11, 'dex' => 12, 'con' => 12,
                    'int' => 12, 'wis' => 10, 'cha' => 10
                ],
                'actions' => [
                    ['name' => 'Scimitar', 'desc' => 'Melee Weapon Attack: +3 to hit, reach 5 ft., one target. Hit: 4 (1d6 + 1) slashing damage.']
                ]
            ],
            'cultist' => [
                'name' => 'Cultist',
                'type' => 'humanoid',
                'challenge_rating' => '1/8',
                'hit_points' => '9 (2d8)',
                'armor_class' => '12 (leather armor)',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 11, 'dex' => 12, 'con' => 10,
                    'int' => 10, 'wis' => 11, 'cha' => 10
                ],
                'actions' => [
                    ['name' => 'Scimitar', 'desc' => 'Melee Weapon Attack: +3 to hit, reach 5 ft., one target. Hit: 4 (1d6 + 1) slashing damage.']
                ]
            ],
            'skeleton' => [
                'name' => 'Skeleton',
                'type' => 'undead',
                'challenge_rating' => '1/4',
                'hit_points' => '13 (2d8 + 4)',
                'armor_class' => '13 (armor scraps)',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 10, 'dex' => 14, 'con' => 15,
                    'int' => 6, 'wis' => 8, 'cha' => 5
                ],
                'actions' => [
                    ['name' => 'Shortsword', 'desc' => 'Melee Weapon Attack: +4 to hit, reach 5 ft., one target. Hit: 5 (1d6 + 2) piercing damage.']
                ]
            ],
            'zombie' => [
                'name' => 'Zombie',
                'type' => 'undead',
                'challenge_rating' => '1/4',
                'hit_points' => '22 (3d8 + 9)',
                'armor_class' => '8',
                'speed' => '20 ft.',
                'abilities' => [
                    'str' => 13, 'dex' => 6, 'con' => 16,
                    'int' => 3, 'wis' => 6, 'cha' => 5
                ],
                'actions' => [
                    ['name' => 'Slam', 'desc' => 'Melee Weapon Attack: +3 to hit, reach 5 ft., one target. Hit: 4 (1d6 + 1) bludgeoning damage.']
                ]
            ],
            'wolf' => [
                'name' => 'Wolf',
                'type' => 'beast',
                'challenge_rating' => '1/4',
                'hit_points' => '11 (2d8 + 2)',
                'armor_class' => '13 (natural armor)',
                'speed' => '40 ft.',
                'abilities' => [
                    'str' => 12, 'dex' => 15, 'con' => 12,
                    'int' => 3, 'wis' => 12, 'cha' => 6
                ],
                'actions' => [
                    ['name' => 'Bite', 'desc' => 'Melee Weapon Attack: +4 to hit, reach 5 ft., one target. Hit: 7 (2d4 + 2) piercing damage.']
                ]
            ],
            'bear' => [
                'name' => 'Bear',
                'type' => 'beast',
                'challenge_rating' => '1',
                'hit_points' => '19 (3d8 + 6)',
                'armor_class' => '11 (natural armor)',
                'speed' => '40 ft., climb 30 ft.',
                'abilities' => [
                    'str' => 15, 'dex' => 10, 'con' => 14,
                    'int' => 2, 'wis' => 13, 'cha' => 7
                ],
                'actions' => [
                    ['name' => 'Bite', 'desc' => 'Melee Weapon Attack: +4 to hit, reach 5 ft., one target. Hit: 8 (1d8 + 4) piercing damage.']
                ]
            ],
            'spider' => [
                'name' => 'Spider',
                'type' => 'beast',
                'challenge_rating' => '0',
                'hit_points' => '1 (1d4 - 1)',
                'armor_class' => '12',
                'speed' => '20 ft., climb 20 ft.',
                'abilities' => [
                    'str' => 2, 'dex' => 14, 'con' => 8,
                    'int' => 1, 'wis' => 10, 'cha' => 2
                ],
                'actions' => [
                    ['name' => 'Bite', 'desc' => 'Melee Weapon Attack: +4 to hit, reach 5 ft., one target. Hit: 1 piercing damage.']
                ]
            ],
            
            // Medium (CR 4-7)
            'ogre' => [
                'name' => 'Ogre',
                'type' => 'giant',
                'challenge_rating' => '2',
                'hit_points' => '59 (7d10 + 21)',
                'armor_class' => '11 (hide armor)',
                'speed' => '40 ft.',
                'abilities' => [
                    'str' => 19, 'dex' => 8, 'con' => 16,
                    'int' => 5, 'wis' => 7, 'cha' => 7
                ],
                'actions' => [
                    ['name' => 'Greatclub', 'desc' => 'Melee Weapon Attack: +6 to hit, reach 5 ft., one target. Hit: 13 (2d8 + 4) bludgeoning damage.']
                ]
            ],
            'troll' => [
                'name' => 'Troll',
                'type' => 'giant',
                'challenge_rating' => '5',
                'hit_points' => '84 (8d10 + 40)',
                'armor_class' => '15 (natural armor)',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 18, 'dex' => 13, 'con' => 20,
                    'int' => 7, 'wis' => 9, 'cha' => 7
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The troll makes three attacks: one with its bite and two with its claws.']
                ]
            ],
            'hill-giant' => [
                'name' => 'Hill Giant',
                'type' => 'giant',
                'challenge_rating' => '5',
                'hit_points' => '105 (10d12 + 40)',
                'armor_class' => '13 (natural armor)',
                'speed' => '40 ft.',
                'abilities' => [
                    'str' => 21, 'dex' => 8, 'con' => 19,
                    'int' => 5, 'wis' => 9, 'cha' => 6
                ],
                'actions' => [
                    ['name' => 'Greatclub', 'desc' => 'Melee Weapon Attack: +8 to hit, reach 10 ft., one target. Hit: 18 (3d8 + 5) bludgeoning damage.']
                ]
            ],
            'wyvern' => [
                'name' => 'Wyvern',
                'type' => 'dragon',
                'challenge_rating' => '6',
                'hit_points' => '110 (13d10 + 39)',
                'armor_class' => '13 (natural armor)',
                'speed' => '20 ft., fly 80 ft.',
                'abilities' => [
                    'str' => 19, 'dex' => 10, 'con' => 16,
                    'int' => 5, 'wis' => 12, 'cha' => 6
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The wyvern makes two attacks: one with its bite and one with its stinger. While flying, it can use its claws in place of one other attack.']
                ]
            ],
            'manticore' => [
                'name' => 'Manticore',
                'type' => 'monstrosity',
                'challenge_rating' => '3',
                'hit_points' => '68 (8d10 + 24)',
                'armor_class' => '14 (natural armor)',
                'speed' => '30 ft., fly 50 ft.',
                'abilities' => [
                    'str' => 17, 'dex' => 16, 'con' => 17,
                    'int' => 7, 'wis' => 12, 'cha' => 8
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The manticore makes three attacks: one with its bite and two with its claws or three with its tail spikes.']
                ]
            ],
            
            // Hard (CR 8-12)
            'dragon' => [
                'name' => 'Dragon',
                'type' => 'dragon',
                'challenge_rating' => '8',
                'hit_points' => '200 (16d12 + 96)',
                'armor_class' => '18 (natural armor)',
                'speed' => '40 ft., fly 80 ft.',
                'abilities' => [
                    'str' => 23, 'dex' => 10, 'con' => 21,
                    'int' => 14, 'wis' => 13, 'cha' => 17
                ],
                'actions' => [
                    ['name' => 'Bite', 'desc' => 'Melee Weapon Attack: +11 to hit, reach 10 ft., one target. Hit: 17 (2d10 + 6) piercing damage.']
                ]
            ],
            'giant' => [
                'name' => 'Giant',
                'type' => 'giant',
                'challenge_rating' => '9',
                'hit_points' => '162 (13d12 + 78)',
                'armor_class' => '16 (chain mail)',
                'speed' => '40 ft.',
                'abilities' => [
                    'str' => 25, 'dex' => 8, 'con' => 22,
                    'int' => 6, 'wis' => 10, 'cha' => 7
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The giant makes two greatsword attacks.']
                ]
            ],
            'demon' => [
                'name' => 'Demon',
                'type' => 'fiend',
                'challenge_rating' => '10',
                'hit_points' => '200 (16d10 + 112)',
                'armor_class' => '19 (natural armor)',
                'speed' => '30 ft., fly 60 ft.',
                'abilities' => [
                    'str' => 22, 'dex' => 15, 'con' => 24,
                    'int' => 12, 'wis' => 13, 'cha' => 20
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The demon makes three attacks: one with its bite, one with its claws, and one with its tail.']
                ]
            ],
            'devil' => [
                'name' => 'Devil',
                'type' => 'fiend',
                'challenge_rating' => '11',
                'hit_points' => '200 (16d10 + 112)',
                'armor_class' => '19 (natural armor)',
                'speed' => '30 ft., fly 60 ft.',
                'abilities' => [
                    'str' => 22, 'dex' => 15, 'con' => 24,
                    'int' => 12, 'wis' => 13, 'cha' => 20
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The devil makes three attacks: one with its bite, one with its claws, and one with its tail.']
                ]
            ],
            'lich' => [
                'name' => 'Lich',
                'type' => 'undead',
                'challenge_rating' => '12',
                'hit_points' => '135 (18d8 + 54)',
                'armor_class' => '17 (natural armor)',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 11, 'dex' => 16, 'con' => 16,
                    'int' => 20, 'wis' => 14, 'cha' => 16
                ],
                'actions' => [
                    ['name' => 'Paralyzing Touch', 'desc' => 'Melee Spell Attack: +12 to hit, reach 5 ft., one creature. Hit: 10 (3d6) cold damage.']
                ]
            ],
            
            // Deadly (CR 13+)
            'ancient-dragon' => [
                'name' => 'Ancient Dragon',
                'type' => 'dragon',
                'challenge_rating' => '20',
                'hit_points' => '546 (28d20 + 280)',
                'armor_class' => '22 (natural armor)',
                'speed' => '40 ft., fly 80 ft.',
                'abilities' => [
                    'str' => 30, 'dex' => 10, 'con' => 30,
                    'int' => 18, 'wis' => 15, 'cha' => 23
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The dragon can use its Frightful Presence. It then makes three attacks: one with its bite and two with its claws.']
                ]
            ],
            'tarrasque' => [
                'name' => 'Tarrasque',
                'type' => 'monstrosity',
                'challenge_rating' => '30',
                'hit_points' => '676 (33d20 + 330)',
                'armor_class' => '25 (natural armor)',
                'speed' => '40 ft.',
                'abilities' => [
                    'str' => 30, 'dex' => 11, 'con' => 30,
                    'int' => 3, 'wis' => 11, 'cha' => 11
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The tarrasque can use its Frightful Presence. It then makes five attacks: one with its bite, two with its claws, one with its horns, and one with its tail.']
                ]
            ],
            'balor' => [
                'name' => 'Balor',
                'type' => 'fiend',
                'challenge_rating' => '19',
                'hit_points' => '262 (21d12 + 126)',
                'armor_class' => '19 (natural armor)',
                'speed' => '40 ft., fly 80 ft.',
                'abilities' => [
                    'str' => 26, 'dex' => 15, 'con' => 22,
                    'int' => 20, 'wis' => 16, 'cha' => 22
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The balor makes two attacks: one with its longsword and one with its whip.']
                ]
            ],
            'pit-fiend' => [
                'name' => 'Pit Fiend',
                'type' => 'fiend',
                'challenge_rating' => '20',
                'hit_points' => '300 (24d10 + 168)',
                'armor_class' => '19 (natural armor)',
                'speed' => '30 ft., fly 60 ft.',
                'abilities' => [
                    'str' => 26, 'dex' => 14, 'con' => 24,
                    'int' => 22, 'wis' => 18, 'cha' => 24
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The pit fiend makes four attacks: one with its bite, one with its claw, one with its mace, and one with its tail.']
                ]
            ],
            'undead' => [
                'name' => 'Undead',
                'type' => 'undead',
                'challenge_rating' => '15',
                'hit_points' => '200 (16d10 + 112)',
                'armor_class' => '18 (natural armor)',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 20, 'dex' => 12, 'con' => 24,
                    'int' => 10, 'wis' => 13, 'cha' => 16
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The undead makes two attacks: one with its bite and one with its claws.']
                ]
            ],
            'construct' => [
                'name' => 'Construct',
                'type' => 'construct',
                'challenge_rating' => '16',
                'hit_points' => '200 (16d10 + 112)',
                'armor_class' => '20 (natural armor)',
                'speed' => '30 ft.',
                'abilities' => [
                    'str' => 24, 'dex' => 8, 'con' => 24,
                    'int' => 3, 'wis' => 11, 'cha' => 1
                ],
                'actions' => [
                    ['name' => 'Multiattack', 'desc' => 'The construct makes two attacks: one with its slam and one with its fist.']
                ]
            ]
        ];
        
        return $fallbackData[$monsterName] ?? $fallbackData['goblin'];
    }
    
    /**
     * Форматирование действий с AI переводом
     */
    private function formatActions($actions) {
        if (empty($actions)) {
            return [];
        }
        
        $formatted = [];
        foreach ($actions as $action) {
            if (isset($action['name']) && isset($action['desc'])) {
                try {
                    // Переводим название и описание через AI
                    $translated_name = $this->translateEnemyText($action['name']);
                    $translated_desc = $this->translateEnemyText($action['desc']);
                    
                $formatted[] = [
                        'name' => $translated_name,
                        'description' => $translated_desc
                ];
                } catch (Exception $e) {
                    // Если AI перевод недоступен, пропускаем это действие
                    logMessage('WARNING', "Пропускаем действие '{$action['name']}' из-за ошибки AI перевода");
                    continue;
                }
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование специальных способностей с AI переводом
     */
    private function formatSpecialAbilities($abilities) {
        if (empty($abilities)) {
            return [];
        }
        
        $formatted = [];
        foreach ($abilities as $ability) {
            if (isset($ability['name']) && isset($ability['desc'])) {
                try {
                    // Переводим название и описание через AI
                    $translated_name = $this->translateEnemyText($ability['name']);
                    $translated_desc = $this->translateEnemyText($ability['desc']);
                    
                $formatted[] = [
                        'name' => $translated_name,
                        'description' => $translated_desc
                ];
                } catch (Exception $e) {
                    // Если AI перевод недоступен, пропускаем эту способность
                    logMessage('WARNING', "Пропускаем способность '{$ability['name']}' из-за ошибки AI перевода");
                    continue;
                }
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование легендарных действий с AI переводом
     */
    private function formatLegendaryActions($actions) {
        if (empty($actions)) {
            return [];
        }
        
        $formatted = [];
        foreach ($actions as $action) {
            if (isset($action['name']) && isset($action['desc'])) {
                try {
                    // Переводим название и описание через AI
                    $translated_name = $this->translateEnemyText($action['name']);
                    $translated_desc = $this->translateEnemyText($action['desc']);
                    
                $formatted[] = [
                        'name' => $translated_name,
                        'description' => $translated_desc
                ];
                } catch (Exception $e) {
                    // Если AI перевод недоступен, пропускаем это действие
                    logMessage('WARNING', "Пропускаем легендарное действие '{$action['name']}' из-за ошибки AI перевода");
                    continue;
                }
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование действий логова с AI переводом
     */
    private function formatLairActions($actions) {
        if (empty($actions)) {
            return [];
        }
        
        $formatted = [];
        foreach ($actions as $action) {
            if (isset($action['name']) && isset($action['desc'])) {
                try {
                    // Переводим название и описание через AI
                    $translated_name = $this->translateEnemyText($action['name']);
                    $translated_desc = $this->translateEnemyText($action['desc']);
                    
                $formatted[] = [
                        'name' => $translated_name,
                        'description' => $translated_desc
                ];
                } catch (Exception $e) {
                    // Если AI перевод недоступен, пропускаем это действие
                    logMessage('WARNING', "Пропускаем действие логова '{$action['name']}' из-за ошибки AI перевода");
                    continue;
                }
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование модификаторов урона
     */
    private function formatDamageModifiers($modifiers) {
        if (empty($modifiers)) {
            return [];
        }
        
        $formatted = [];
        foreach ($modifiers as $modifier) {
            $formatted[] = $this->translateDamageType($modifier);
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование иммунитетов к состояниям
     */
    private function formatConditionImmunities($immunities) {
        if (empty($immunities)) {
            return [];
        }
        
        $formatted = [];
        foreach ($immunities as $immunity) {
            if (isset($immunity['name'])) {
                $formatted[] = $this->translateCondition($immunity['name']);
            } else {
                $formatted[] = $this->translateCondition($immunity);
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование чувств
     */
    private function formatSenses($senses) {
        if (empty($senses)) {
            return [];
        }
        
        $formatted = [];
        foreach ($senses as $sense => $value) {
            if (is_string($sense)) {
                $translated_sense = $this->translateSense($sense);
                $formatted[] = "$translated_sense: $value";
            } else {
                $formatted[] = $value;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование языков
     */
    private function formatLanguages($languages) {
        if (empty($languages)) {
            return ['Общий'];
        }
        
        if (is_array($languages)) {
            return $languages;
        }
        
        return [$languages];
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
            'swarm' => 'Рой'
        ];
        
        return $translations[strtolower($type)] ?? $type;
    }
    
    /**
     * Перевод размеров на русский
     */
    private function translateSize($size) {
        $translations = [
            'tiny' => 'Крошечный',
            'small' => 'Маленький',
            'medium' => 'Средний',
            'large' => 'Большой',
            'huge' => 'Огромный',
            'gargantuan' => 'Гигантский'
        ];
        
        return $translations[strtolower($size)] ?? $size;
    }
    
    /**
     * Перевод типов урона на русский
     */
    private function translateDamageType($type) {
        $translations = [
            'acid' => 'Кислота',
            'bludgeoning' => 'Дробящий',
            'cold' => 'Холод',
            'fire' => 'Огонь',
            'force' => 'Силовой',
            'lightning' => 'Молния',
            'necrotic' => 'Некротический',
            'piercing' => 'Колющий',
            'poison' => 'Яд',
            'psychic' => 'Психический',
            'radiant' => 'Излучение',
            'slashing' => 'Режущий',
            'thunder' => 'Звуковой'
        ];
        
        return $translations[strtolower($type)] ?? $type;
    }
    
    /**
     * Перевод состояний на русский
     */
    private function translateCondition($condition) {
        $translations = [
            'blinded' => 'Ослепленный',
            'charmed' => 'Очарованный',
            'deafened' => 'Оглушенный',
            'exhaustion' => 'Истощенный',
            'frightened' => 'Испуганный',
            'grappled' => 'Захваченный',
            'incapacitated' => 'Недееспособный',
            'invisible' => 'Невидимый',
            'paralyzed' => 'Парализованный',
            'petrified' => 'Окаменевший',
            'poisoned' => 'Отравленный',
            'prone' => 'Опрокинутый',
            'restrained' => 'Скованный',
            'stunned' => 'Оглушенный',
            'unconscious' => 'Без сознания'
        ];
        
        return $translations[strtolower($condition)] ?? $condition;
    }
    
    /**
     * Перевод чувств на русский
     */
    private function translateSense($sense) {
        $translations = [
            'blindsight' => 'Слепозрение',
            'darkvision' => 'Темновидение',
            'tremorsense' => 'Чувство вибрации',
            'truesight' => 'Истинное зрение'
        ];
        
        return $translations[strtolower($sense)] ?? $sense;
    }
    
    /**
     * Проверка полноты данных монстра
     */
    private function hasCompleteData($monster) {
        // Проверяем только самые важные поля
        $required_fields = ['name', 'type', 'challenge_rating'];
        
        foreach ($required_fields as $field) {
            if (!isset($monster[$field]) || empty($monster[$field])) {
                logMessage('WARNING', "EnemyGenerator: Монстр не имеет обязательного поля '$field'");
                return false;
            }
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
            // Создаем объект персонажа для AI сервиса
            $enemy_data = [
                'name' => $monster['name'],
                'type' => $monster['type'],
                'challenge_rating' => $monster['challenge_rating']
            ];
            
            // Используем основной AI сервис для генерации описания
            require_once __DIR__ . '/../../app/Services/ai-service.php';
            $ai_service = new AiService();
            
            // Используем метод генерации описания персонажа, но передаем данные монстра
            $result = $ai_service->generateCharacterDescription($enemy_data, true);
            
            // Если получили ошибку, возвращаем её
            if (isset($result['error'])) {
                return $result;
            }
            
            // Если получили успешный результат, возвращаем его
            return $result;
            
        } catch (Exception $e) {
            logMessage('ERROR', "EnemyGenerator: Ошибка генерации описания: " . $e->getMessage());
            return [
                'error' => 'AI API недоступен',
                'message' => 'Не удалось сгенерировать описание монстра',
                'details' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Генерация тактики с помощью AI
     */
    private function generateTactics($monster) {
        try {
            // Создаем объект противника для AI сервиса
            $enemy_data = [
                'name' => $monster['name'],
                'type' => $monster['type'],
                'challenge_rating' => $monster['challenge_rating']
            ];
            
            // Используем основной AI сервис для генерации тактики
            require_once __DIR__ . '/../../app/Services/ai-service.php';
            $ai_service = new AiService();
            
            // Используем метод генерации тактики противника
            $result = $ai_service->generateEnemyTactics($enemy_data, true);
            
            // Если получили ошибку, возвращаем её
            if (isset($result['error'])) {
                return $result;
            }
            
            // Если получили успешный результат, возвращаем его
            return $result;
            
        } catch (Exception $e) {
            logMessage('ERROR', "EnemyGenerator: Ошибка генерации тактики: " . $e->getMessage());
            return [
                'error' => 'AI API недоступен',
                'message' => 'Не удалось сгенерировать тактику монстра',
                'details' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Генерация с помощью AI
     */
    private function generateWithAI($prompt) {
        if (!$this->deepseek_api_key) {
            return null;
        }
        
        // Проверяем доступность cURL
        if (!function_exists('curl_init')) {
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
        
        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->deepseek_api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
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
    
    /**
     * Выполнение HTTP запроса с улучшенной обработкой ошибок
     */
    private function makeRequest($url) {
        logMessage('INFO', "EnemyGenerator: makeRequest для URL: $url");
        
        // Пробуем сначала cURL, если доступен
        if (function_exists('curl_init')) {
            return $this->makeCurlRequest($url);
        }
        
        // Fallback на file_get_contents
        return $this->makeFileGetContentsRequest($url);
    }
    
    /**
     * Выполнение запроса через cURL
     */
    private function makeCurlRequest($url) {
        logMessage('INFO', "EnemyGenerator: Используем cURL для запроса");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $end_time = microtime(true);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $request_time = round(($end_time - $start_time) * 1000, 2);
        logMessage('INFO', "EnemyGenerator: cURL запрос завершен за {$request_time}ms, HTTP код: $httpCode");
        
        if ($response === false || !empty($error)) {
            logMessage('ERROR', "EnemyGenerator: cURL failed: $error");
            throw new Exception("Не удалось получить данные от API через cURL: $error");
        }
        
        if ($httpCode !== 200) {
            logMessage('ERROR', "EnemyGenerator: HTTP код $httpCode для $url");
            throw new Exception("API вернул код ошибки: $httpCode");
        }
        
        logMessage('INFO', "EnemyGenerator: Успешный cURL ответ, размер: " . strlen($response) . " байт");
        
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            logMessage('INFO', "EnemyGenerator: JSON успешно декодирован через cURL");
            return $decoded;
        } else {
            logMessage('ERROR', "EnemyGenerator: JSON decode error for $url: " . json_last_error_msg());
            throw new Exception("Ошибка разбора ответа API");
        }
    }
    
    /**
     * Выполнение запроса через file_get_contents
     */
    private function makeFileGetContentsRequest($url) {
        logMessage('INFO', "EnemyGenerator: Используем file_get_contents для запроса");
        
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
        logMessage('INFO', "EnemyGenerator: file_get_contents запрос завершен за {$request_time}ms");
        
        if ($response === false) {
            $error = error_get_last();
            $error_msg = $error ? $error['message'] : 'Неизвестная ошибка';
            logMessage('ERROR', "EnemyGenerator: file_get_contents failed: $error_msg");
            throw new Exception("Не удалось получить данные от API: $error_msg");
        }
        
        logMessage('INFO', "EnemyGenerator: Успешный file_get_contents ответ, размер: " . strlen($response) . " байт");
        
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
            logMessage('INFO', "EnemyGenerator: JSON успешно декодирован через file_get_contents");
                return $decoded;
            } else {
            logMessage('ERROR', "EnemyGenerator: JSON decode error for $url: " . json_last_error_msg());
                throw new Exception("Ошибка разбора ответа API");
            }
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
        
        logMessage('INFO', "EnemyGenerator: Расширенный CR диапазон: " . json_encode($expanded));
        return $expanded;
    }
    
    /**
     * Получение списка монстров
     */
    private function getMonstersList() {
        $url = $this->dnd5e_api_url . '/monsters';
        return $this->makeRequest($url);
    }
    
    /**
     * AI перевод текста противника
     */
    private function translateEnemyText($text) {
        if (empty($text)) {
            return $text;
        }
        
        // Проверяем, нужен ли перевод (если текст уже на русском, не переводим)
        if (preg_match('/[а-яё]/iu', $text)) {
            return $text;
        }
        
        try {
            // Используем AI сервис для перевода
            require_once __DIR__ . '/../../app/Services/ai-service.php';
            $ai_service = new AiService();
            
            // Создаем промпт для перевода
            $prompt = "Переведи на русский язык следующий текст из D&D, сохранив игровую терминологию и форматирование:\n\n" . $text;
            
            $result = $ai_service->generateText($prompt);
            
            if (isset($result['error'])) {
                logMessage('ERROR', "AI перевод не удался: " . $result['message']);
                throw new Exception('AI перевод недоступен: ' . $result['message']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка AI перевода: " . $e->getMessage());
            throw new Exception('AI перевод недоступен: ' . $e->getMessage());
        }
    }
    
}

// Обработка запроса
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    logMessage('INFO', "EnemyGenerator: Получен POST запрос с данными: " . json_encode($_POST));
    
    try {
        $generator = new EnemyGenerator();
        $result = $generator->generateEnemies($_POST);
        
        logMessage('INFO', "EnemyGenerator: Результат генерации: " . json_encode($result));
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        logMessage('ERROR', "EnemyGenerator: Критическая ошибка: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>
