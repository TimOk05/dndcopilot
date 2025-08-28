<?php
// Убираем заголовки для использования в тестах
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/fallback-data.php';

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
        if ($count < 1 || $count > 10) {
            throw new Exception('Количество противников должно быть от 1 до 10');
        }
        
        $valid_threat_levels = ['easy', 'medium', 'hard', 'deadly'];
        if (!in_array($threat_level, $valid_threat_levels)) {
            throw new Exception('Неверный уровень угрозы');
        }
        
        // Определяем CR на основе уровня угрозы
        $cr_range = $this->getCRRange($threat_level);
        
        try {
            $enemies = [];
            
            for ($i = 0; $i < $count; $i++) {
                $enemy = $this->generateSingleEnemy($cr_range, $enemy_type, $environment, $use_ai);
                if ($enemy) {
                    $enemies[] = $enemy;
                }
            }
            
            if (empty($enemies)) {
                throw new Exception('Не удалось найти подходящих противников');
            }
            
            return [
                'success' => true,
                'enemies' => $enemies,
                'threat_level' => $threat_level,
                'count' => count($enemies)
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
                return ['min' => 0, 'max' => 2]; // CR 0-2 (1/8, 1/4, 1/2, 1, 2)
            case 'medium':
                return ['min' => 1, 'max' => 5]; // CR 1-5
            case 'hard':
                return ['min' => 3, 'max' => 10]; // CR 3-10
            case 'deadly':
                return ['min' => 8, 'max' => 20]; // CR 8-20
            default:
                return ['min' => 1, 'max' => 5];
        }
    }
    
    /**
     * Генерация одного противника
     */
    private function generateSingleEnemy($cr_range, $enemy_type, $environment, $use_ai) {
        // Используем fallback данные для надежности
        $fallback_monsters = $this->getFallbackMonsters();
        
        if (empty($fallback_monsters)) {
            throw new Exception('База данных монстров недоступна');
        }
        
        // Фильтруем монстров по CR и типу
        $filtered_monsters = $this->filterFallbackMonsters($fallback_monsters, $cr_range, $enemy_type, $environment);
        
        if (empty($filtered_monsters)) {
            // Если не найдено подходящих, берем случайного монстра из подходящего CR
            $filtered_monsters = array_filter($fallback_monsters, function($monster) use ($cr_range) {
                $cr = $this->parseCR($monster['challenge_rating'] ?? '0');
                return $cr >= $cr_range['min'] && $cr <= $cr_range['max'];
            });
            
            if (empty($filtered_monsters)) {
                // Если все еще нет подходящих, берем любого монстра
                $filtered_monsters = $fallback_monsters;
            }
        }
        
        // Выбираем случайного монстра
        $monster = $filtered_monsters[array_rand($filtered_monsters)];
        
        // Получаем детальную информацию о монстре
        $monster_details = $this->getFallbackMonsterDetails($monster['index']);
        
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
            'special_abilities' => $monster_details['special_abilities'] ?? []
        ];
        
        // Добавляем AI-описание если включено
        if ($use_ai) {
            $enemy['description'] = $this->generateEnemyDescription($enemy);
        }
        
        return $enemy;
    }
    
    /**
     * Получение списка монстров
     */
    private function getMonstersList() {
        $url = $this->dnd5e_api_url . '/monsters';
        $response = $this->makeRequest($url);
        
        if ($response && isset($response['results'])) {
            return $response['results'];
        }
        
        // Fallback: возвращаем базовый список монстров если API недоступен
        return $this->getFallbackMonsters();
    }
    
    /**
     * Получение fallback данных монстров
     */
    private function getFallbackMonsters() {
        return FallbackData::getMonsters();
    }
    
    /**
     * Фильтрация fallback монстров
     */
    private function filterFallbackMonsters($monsters, $cr_range, $enemy_type, $environment) {
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
    private function getFallbackMonsterDetails($monsterIndex) {
        $monsters = FallbackData::getMonsters();
        
        foreach ($monsters as $monster) {
            if ($monster['index'] === $monsterIndex) {
                return $monster;
            }
        }
        
        return null;
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
        // Для дробных CR (1/8, 1/4, 1/2) используем специальную логику
        if ($cr <= 0.5) {
            return $cr_range['min'] <= 0.5 && $cr_range['max'] >= 0.5;
        }
        
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
        } else {
            return 'Различные';
        }
    }
    
    /**
     * Извлечение боевых параметров
     */
    private function extractCombatStats($monster_details) {
        $stats = [];
        
        if (isset($monster_details['armor_class'])) {
            $stats['Класс доспеха'] = $monster_details['armor_class'][0]['value'] ?? '10';
        }
        
        if (isset($monster_details['hit_points'])) {
            $stats['Хиты'] = $monster_details['hit_points']['average'] ?? '10';
        }
        
        if (isset($monster_details['speed'])) {
            $speed_parts = [];
            foreach ($monster_details['speed'] as $type => $value) {
                $speed_parts[] = "$type: $value";
            }
            $stats['Скорость'] = implode(', ', $speed_parts);
        }
        
        return $stats;
    }
    
    /**
     * Извлечение действий
     */
    private function extractActions($monster_details) {
        $actions = [];
        
        if (isset($monster_details['actions'])) {
            foreach ($monster_details['actions'] as $action) {
                $actions[] = [
                    'name' => $action['name'],
                    'description' => $action['desc'] ?? 'Нет описания'
                ];
            }
        }
        
        return $actions;
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
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("CURL Error for $url: $error");
            return null;
        }
        
        if ($http_code === 200 && $response) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            } else {
                error_log("JSON decode error for $url: " . json_last_error_msg());
                return null;
            }
        }
        
        error_log("HTTP Error for $url: $http_code");
        return null;
    }
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $generator = new EnemyGenerator();
    $result = $generator->generateEnemies($_POST);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Метод не поддерживается'
    ]);
}
?>
