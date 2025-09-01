<?php
// Убираем заголовки для использования в тестах
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}
require_once __DIR__ . '/../config.php';

class EnemyGenerator {
    private $dnd5e_api_url = 'https://www.dnd5eapi.co/api';
    private $deepseek_api_key;
    
    public function __construct() {
        $this->deepseek_api_key = getApiKey('deepseek');
    }
    
    /**
     * Генерация противников на основе уровня угрозы
     */
    public function generateEnemies($params) {
        $threat_level = $params['threat_level'] ?? 'medium';
        $count = (int)($params['count'] ?? 1);
        $enemy_type = $params['enemy_type'] ?? '';
        $environment = $params['environment'] ?? '';
        $use_ai = isset($params['use_ai']) && $params['use_ai'] === 'on';
        
        // Валидация параметров
        if ($count < 1 || $count > 20) {
            throw new Exception('Количество противников должно быть от 1 до 20');
        }
        
        // Проверяем, является ли threat_level числовым значением (конкретный CR)
        if (!in_array($threat_level, ['easy', 'medium', 'hard', 'deadly']) && !is_numeric($threat_level)) {
            throw new Exception('Неверный уровень угрозы. Должен быть easy, medium, hard, deadly или конкретный CR (0, 1, 2, 3...)');
        }
        
        // Определяем CR на основе уровня угрозы
        $cr_range = $this->getCRRange($threat_level);
        
        try {
            $enemies = [];
            error_log("EnemyGenerator: Начинаем генерацию противников. threat_level: $threat_level, count: $count");
            
            // Получаем список монстров из API
            $monsters = $this->getMonstersList();
            
            if (empty($monsters)) {
                throw new Exception('База данных монстров недоступна');
            }
            
            // Фильтруем монстров по CR и типу
            error_log("EnemyGenerator: Фильтруем монстров. CR range: " . json_encode($cr_range));
            $filtered_monsters = $this->filterMonsters($monsters, $cr_range, $enemy_type, $environment);
            error_log("EnemyGenerator: После фильтрации найдено монстров: " . count($filtered_monsters));
            
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
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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
        
        for ($i = 0; $i < $count; $i++) {
            $enemy = $this->generateSingleEnemy($monster, $use_ai);
            if ($enemy) {
                // Добавляем номер для различения
                $enemy['name'] = $enemy['name'] . ' ' . ($i + 1);
                $enemies[] = $enemy;
            }
        }
        
        return $enemies;
    }
    
    /**
     * Генерация одного противника
     */
    private function generateSingleEnemy($monster, $use_ai) {
        // Получаем детальную информацию о монстре
        $monster_details = $this->getMonsterDetails($monster['index']);
        
        if (!$monster_details) {
            throw new Exception('Не удалось получить информацию о монстре: ' . ($monster['name'] ?? 'Unknown'));
        }
        
        // Формируем результат
        $enemy = [
            'name' => $monster_details['name'],
            'type' => $monster_details['type'] ?? 'Unknown',
            'challenge_rating' => $monster_details['challenge_rating'] ?? '0',
            'hit_points' => $monster_details['hit_points'] ?? 0,
            'armor_class' => $monster_details['armor_class'] ?? 10,
            'speed' => $monster_details['speed'] ?? '30 ft',
            'abilities' => $monster_details['abilities'] ?? [],
            'actions' => $monster_details['actions'] ?? [],
            'special_abilities' => $monster_details['special_abilities'] ?? [],
            'environment' => $this->getEnvironment($monster_details),
            'cr_numeric' => $this->parseCR($monster_details['challenge_rating'] ?? '0')
        ];
        
        // Добавляем AI-описание если включено
        if ($use_ai) {
            $enemy['description'] = $this->generateEnemyDescription($enemy);
            $enemy['tactics'] = $this->generateTactics($enemy);
        }
        
        return $enemy;
    }
    
    /**
     * Получение списка монстров
     */
    private function getMonstersList() {
        $url = $this->dnd5e_api_url . '/monsters';
        error_log("EnemyGenerator: Запрос к D&D API: $url");
        
        $response = $this->makeRequest($url);
        error_log("EnemyGenerator: Ответ от D&D API получен: " . ($response ? 'да' : 'нет'));
        
        if ($response && isset($response['results'])) {
            error_log("EnemyGenerator: Найдено монстров: " . count($response['results']));
            return $response['results'];
        }
        
        if ($response) {
            error_log("EnemyGenerator: Структура ответа: " . json_encode(array_keys($response)));
        }
        
        throw new Exception('API D&D недоступен или возвращает неверную структуру');
    }
    
    /**
     * Фильтрация монстров по параметрам
     */
    private function filterMonsters($monsters, $cr_range, $enemy_type, $environment) {
        $filtered = [];
        
        foreach ($monsters as $monster) {
            // Проверяем CR
            $cr = $this->parseCR($monster['challenge_rating']);
            if (!$this->checkCRRange($cr, $cr_range)) {
                continue;
            }
            
            // Проверяем тип (если указан)
            if ($enemy_type && $enemy_type !== '' && strpos(strtolower($monster['type']), strtolower($enemy_type)) === false) {
                continue;
            }
            
            // Проверяем среду (если указана)
            if ($environment && $environment !== '' && !$this->checkEnvironment($monster, $environment)) {
                continue;
            }
            
            $filtered[] = $monster;
        }
        
        return $filtered;
    }
    
    /**
     * Получение детальной информации о монстре
     */
    private function getMonsterDetails($monsterIndex) {
        $url = $this->dnd5e_api_url . '/monsters/' . $monsterIndex;
        $response = $this->makeRequest($url);
        
        if ($response) {
            return $response;
        }
        
        throw new Exception('Не удалось получить детали монстра');
    }
    
    /**
     * Парсинг Challenge Rating в числовое значение
     */
    private function parseCR($cr_string) {
        if (is_numeric($cr_string)) {
            return (float)$cr_string;
        }
        
        // Обработка дробных CR (например, "1/8", "1/4", "1/2")
        if (strpos($cr_string, '/') !== false) {
            $parts = explode('/', $cr_string);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                return (float)$parts[0] / (float)$parts[1];
            }
        }
        
        return 0;
    }
    
    /**
     * Проверка соответствия CR диапазону
     */
    private function checkCRRange($cr, $cr_range) {
        return $cr >= $cr_range['min'] && $cr <= $cr_range['max'];
    }
    
    /**
     * Проверка среды обитания
     */
    private function checkEnvironment($monster_details, $environment) {
        // Простая проверка по названию монстра
        $name = strtolower($monster_details['name']);
        $type = strtolower($monster_details['type']);
        
        $environment_keywords = [
            'arctic' => ['ice', 'snow', 'frost', 'arctic'],
            'coastal' => ['sea', 'coast', 'beach', 'water'],
            'desert' => ['desert', 'sand', 'dune'],
            'forest' => ['forest', 'wood', 'tree'],
            'grassland' => ['grass', 'plain', 'field'],
            'hill' => ['hill', 'mountain'],
            'mountain' => ['mountain', 'peak', 'cliff'],
            'swamp' => ['swamp', 'marsh', 'bog'],
            'underdark' => ['underdark', 'cave', 'underground'],
            'urban' => ['city', 'town', 'urban']
        ];
        
        if (isset($environment_keywords[$environment])) {
            foreach ($environment_keywords[$environment] as $keyword) {
                if (strpos($name, $keyword) !== false || strpos($type, $keyword) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Получение среды обитания
     */
    private function getEnvironment($monster_details) {
        // Определяем среду по типу и названию
        $name = strtolower($monster_details['name']);
        $type = strtolower($monster_details['type']);
        
        if (strpos($name, 'dragon') !== false || strpos($type, 'dragon') !== false) {
            return 'Горы/Подземелье';
        } elseif (strpos($name, 'goblin') !== false || strpos($name, 'orc') !== false) {
            return 'Подземелье/Холмы';
        } elseif (strpos($name, 'wolf') !== false || strpos($name, 'bear') !== false) {
            return 'Лес/Холмы';
        } elseif (strpos($name, 'shark') !== false || strpos($name, 'fish') !== false) {
            return 'Водная';
        } else {
            return 'Различные';
        }
    }
    
    /**
     * Генерация тактики с помощью AI
     */
    private function generateTactics($enemy) {
        $prompt = "Опиши тактику боя для {$enemy['name']} (CR {$enemy['challenge_rating']}, {$enemy['type']}). " .
                 "Включи основные действия, предпочтения в бою, слабости и как лучше использовать этого противника. " .
                 "Ответ должен быть кратким (2-3 предложения) и практичным для мастера D&D.";
        
        try {
            $response = $this->callDeepSeek($prompt);
            return $response ?: 'Тактика не определена';
        } catch (Exception $e) {
            return 'Тактика не определена';
        }
    }

    /**
     * Генерация описания противника с помощью AI
     */
    private function generateEnemyDescription($enemy) {
        $prompt = "Опиши противника {$enemy['name']} (CR {$enemy['challenge_rating']}, {$enemy['type']}). " .
                 "Включи основные характеристики, слабости и как лучше использовать этого противника. " .
                 "Ответ должен быть кратким (2-3 предложения) и практичным для мастера D&D. " .
                 "Не используй специальные символы, звездочки или скобки.";
        
        try {
            $response = $this->callDeepSeek($prompt);
            if ($response) {
                // Очищаем ответ от лишних символов
                $response = str_replace(['*', '(', ')', '[', ']', '_'], '', $response);
                $response = preg_replace('/\s+/', ' ', $response); // Убираем лишние пробелы
                return trim($response);
            }
            return 'Описание не определено';
        } catch (Exception $e) {
            return 'Описание не определено';
        }
    }
    
    /**
     * Вызов DeepSeek API
     */
    private function callDeepSeek($prompt) {
        if (!$this->deepseek_api_key) {
            return null;
        }
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Давай краткие и практичные советы.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 200,
            'temperature' => 0.7
        ];
        
        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->deepseek_api_key
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
        
        return null;
    }
    
    /**
     * Выполнение HTTP запроса
     */
    private function makeRequest($url) {
        error_log("EnemyGenerator: makeRequest для URL: $url");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $end_time = microtime(true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        $request_time = round(($end_time - $start_time) * 1000, 2);
        error_log("EnemyGenerator: Запрос завершен за {$request_time}ms, HTTP: $http_code");
        
        if ($error) {
            error_log("EnemyGenerator: CURL Error for $url: $error");
            return null;
        }
        
        if ($http_code === 200 && $response) {
            error_log("EnemyGenerator: Успешный ответ, размер: " . strlen($response) . " байт");
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                error_log("EnemyGenerator: JSON успешно декодирован");
                return $decoded;
            } else {
                error_log("EnemyGenerator: JSON decode error for $url: " . json_last_error_msg());
                return null;
            }
        }
        
        error_log("EnemyGenerator: HTTP Error for $url: $http_code, response size: " . strlen($response));
        return null;
    }
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            'error' => 'Критическая ошибка: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    error_log("EnemyGenerator: Неподдерживаемый метод: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'success' => false,
        'error' => 'Метод не поддерживается'
    ]);
}
?>
