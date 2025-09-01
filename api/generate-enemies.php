<?php
// Убираем заголовки для использования в тестах
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}
require_once __DIR__ . '/../config.php';

class EnemyGenerator {
    private $dnd5e_api_url = 'https://www.dnd5eapi.co/api';
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
                return $cached_data['results'];
            }
        }
        
        // Пробуем получить с retry
        for ($attempt = 1; $attempt <= $this->max_retries; $attempt++) {
            try {
                error_log("EnemyGenerator: Попытка $attempt получить список монстров");
                $monsters = $this->getMonstersList();
                
                if ($monsters && !empty($monsters)) {
                    // Сохраняем в кэш
                    file_put_contents($cache_file, json_encode(['results' => $monsters, 'timestamp' => time()]));
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
            // Получаем детальную информацию о монстре
            $monster_details = $this->getMonsterDetails($monster['url']);
            if (!$monster_details) {
                return null;
            }
            
            // Генерируем базовые характеристики
            $enemy = [
                'name' => $monster_details['name'],
                'type' => $monster_details['type'],
                'challenge_rating' => $monster_details['challenge_rating'],
                'hit_points' => $monster_details['hit_points'],
                'armor_class' => $monster_details['armor_class'],
                'speed' => $monster_details['speed'],
                'abilities' => $monster_details['abilities'],
                'actions' => $monster_details['actions'],
                'special_abilities' => $monster_details['special_abilities'] ?? [],
                'environment' => $monster_details['environment'] ?? 'Не определена',
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
        $url = $this->dnd5e_api_url . '/monsters/' . basename($monster_url);
        return $this->makeRequest($url);
    }
    
    /**
     * Фильтрация монстров по параметрам
     */
    private function filterMonsters($monsters, $cr_range, $enemy_type, $environment) {
        $filtered = [];
        
        foreach ($monsters as $monster) {
            // Проверяем CR
            if (!$this->checkCRRange($monster['challenge_rating'], $cr_range)) {
                continue;
            }
            
            // Проверяем тип
            if ($enemy_type && !$this->checkType($monster['type'], $enemy_type)) {
                continue;
            }
            
            // Проверяем среду
            if ($environment && !$this->checkEnvironment($monster, $environment)) {
                continue;
            }
            
            // Проверяем совместимость
            if (!$this->checkCompatibility($monster, $cr_range)) {
                continue;
            }
            
            $filtered[] = $monster;
        }
        
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
     * Генерация текста с помощью AI
     */
    private function generateWithAI($prompt) {
        try {
            $url = 'https://api.deepseek.com/v1/chat/completions';
            $data = [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 150,
                'temperature' => 0.7
            ];
            
            $result = $this->makeAIRequest($url, $data);
            
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("EnemyGenerator: Ошибка AI генерации: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Выполнение HTTP запроса с улучшенной обработкой ошибок
     */
    private function makeRequest($url) {
        error_log("EnemyGenerator: makeRequest для URL: $url");
        
        if (!function_exists('curl_init')) {
            throw new Exception('cURL не доступен на сервере');
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $end_time = microtime(true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $request_time = round(($end_time - $start_time) * 1000, 2);
        error_log("EnemyGenerator: Запрос завершен за {$request_time}ms, HTTP: $http_code");
        
        if ($error) {
            error_log("EnemyGenerator: CURL Error for $url: $error");
            throw new Exception("Ошибка сети: $error");
        }
        
        if ($http_code === 200 && $response) {
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
        
        error_log("EnemyGenerator: HTTP Error for $url: $http_code, response size: " . strlen($response));
        
        if ($http_code === 0) {
            throw new Exception("API недоступен. Проверьте подключение к интернету.");
        } elseif ($http_code >= 400 && $http_code < 500) {
            throw new Exception("Ошибка запроса к API (HTTP $http_code)");
        } elseif ($http_code >= 500) {
            throw new Exception("Ошибка сервера API (HTTP $http_code)");
        } else {
            throw new Exception("Неожиданный ответ API (HTTP $http_code)");
        }
    }
    
    /**
     * Выполнение AI запроса
     */
    private function makeAIRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->deepseek_api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Ошибка AI API: $error");
        }
        
        if ($http_code !== 200) {
            throw new Exception("AI API вернул HTTP $http_code");
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
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
