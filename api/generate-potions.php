<?php
/**
 * API для генерации зелий D&D через официальную D&D 5e API
 * Использует https://www.dnd5eapi.co/api для получения реальных зелий
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config.php';

class PotionGenerator {
    private $dnd5e_api_url = 'https://www.dnd5eapi.co/api';
    private $deepseek_api_key;
    
    public function __construct() {
        $this->deepseek_api_key = getApiKey('deepseek');
    }
    
    /**
     * Генерация зелий через D&D API
     */
    public function generatePotions($params) {
        $count = (int)($params['count'] ?? 1);
        $rarity = $params['rarity'] ?? '';
        $type = $params['type'] ?? '';
        $use_ai = isset($params['use_ai']) && $params['use_ai'] === 'on';
        
        // Валидация параметров
        if ($count < 1 || $count > 10) {
            throw new Exception('Количество зелий должно быть от 1 до 10');
        }
        
        try {
            // Получаем список всех магических предметов
            $magic_items = $this->getMagicItemsList();
            
            // Фильтруем только зелья
            $potions = $this->filterPotions($magic_items);
            
            if (empty($potions)) {
                throw new Exception('Не найдены зелья в базе данных D&D');
            }
            
            // Фильтруем по редкости и типу
            $filtered_potions = $this->filterByParams($potions, $rarity, $type);
            
            if (empty($filtered_potions)) {
                throw new Exception('Не найдены подходящие зелья для указанных параметров');
            }
            
            // Выбираем случайные зелья
            $selected_potions = $this->selectRandomPotions($filtered_potions, $count);
            
            // Получаем детальную информацию о каждом зелье
            $detailed_potions = [];
            foreach ($selected_potions as $potion) {
                $detailed_potion = $this->getPotionDetails($potion, $use_ai);
                if ($detailed_potion) {
                    $detailed_potions[] = $detailed_potion;
                }
            }
            
            if (empty($detailed_potions)) {
                throw new Exception('Не удалось получить детальную информацию о зельях');
            }
            
            return [
                'success' => true,
                'data' => $detailed_potions,
                'count' => count($detailed_potions),
                'filters' => [
                    'rarity' => $rarity ?: 'любая',
                    'type' => $type ?: 'любой'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение списка магических предметов из D&D API
     */
    private function getMagicItemsList() {
        $url = $this->dnd5e_api_url . '/magic-items';
        $response = $this->makeRequest($url);
        
        if ($response && isset($response['results'])) {
            return $response['results'];
        }
        
        throw new Exception('Не удалось получить список магических предметов из D&D API');
    }
    
    /**
     * Фильтрация зелий из списка магических предметов
     */
    private function filterPotions($magic_items) {
        $potions = [];
        
        foreach ($magic_items as $item) {
            $name = strtolower($item['name']);
            if (strpos($name, 'potion') !== false) {
                $potions[] = $item;
            }
        }
        
        return $potions;
    }
    
    /**
     * Фильтрация зелий по параметрам
     */
    private function filterByParams($potions, $rarity, $type) {
        $filtered = $potions;
        
        // Если указана редкость, фильтруем по ней
        if ($rarity && $rarity !== '') {
            $filtered = array_filter($filtered, function($potion) use ($rarity) {
                // Получаем детали зелья для проверки редкости
                $details = $this->getPotionDetails($potion, false);
                if ($details && isset($details['rarity'])) {
                    return strtolower($details['rarity']) === strtolower($rarity);
                }
                return false;
            });
        }
        
        // Если указан тип, фильтруем по нему
        if ($type && $type !== '') {
            $filtered = array_filter($filtered, function($potion) use ($type) {
                $details = $this->getPotionDetails($potion, false);
                if ($details && isset($details['type'])) {
                    return $details['type'] === $type;
                }
                return false;
            });
        }
        
        return array_values($filtered);
    }
    
    /**
     * Выбор случайных зелий
     */
    private function selectRandomPotions($potions, $count) {
        if (count($potions) <= $count) {
            return $potions;
        }
        
        $selected = [];
        $available = array_values($potions);
        
        for ($i = 0; $i < $count; $i++) {
            if (empty($available)) break;
            $index = array_rand($available);
            $selected[] = $available[$index];
            unset($available[$index]);
        }
        
        return $selected;
    }
    
    /**
     * Получение детальной информации о зелье
     */
    private function getPotionDetails($potion, $use_ai) {
        $url = $this->dnd5e_api_url . $potion['url'];
        $response = $this->makeRequest($url);
        
        if (!$response) {
            return null;
        }
        
        // Формируем результат
        $result = [
            'name' => $response['name'],
            'rarity' => $response['rarity']['name'] ?? 'Unknown',
            'type' => $this->determinePotionType($response),
            'description' => $this->formatDescription($response['desc'] ?? []),
            'value' => $this->getPotionValue($response),
            'weight' => $this->getPotionWeight($response),
            'icon' => $this->getPotionIcon($response),
            'color' => $this->getPotionColor($response['rarity']['name'] ?? 'Common'),
            'properties' => $this->getPotionProperties($response)
        ];
        
        // Добавляем AI-описание если включено
        if ($use_ai && $this->deepseek_api_key) {
            $result['ai_description'] = $this->generateAIDescription($result);
        }
        
        return $result;
    }
    
    /**
     * Определение типа зелья
     */
    private function determinePotionType($potion_data) {
        $name = strtolower($potion_data['name']);
        $desc = strtolower(implode(' ', $potion_data['desc'] ?? []));
        
        // Определяем тип по названию и описанию
        if (strpos($name, 'healing') !== false || strpos($desc, 'heal') !== false || strpos($desc, 'hit point') !== false) {
            return 'Восстановление';
        } elseif (strpos($name, 'strength') !== false || strpos($name, 'giant') !== false || strpos($desc, 'strength') !== false) {
            return 'Усиление';
        } elseif (strpos($name, 'resistance') !== false || strpos($name, 'invulnerability') !== false || strpos($desc, 'resistance') !== false) {
            return 'Защита';
        } elseif (strpos($name, 'invisibility') !== false || strpos($name, 'disguise') !== false || strpos($desc, 'invisible') !== false) {
            return 'Иллюзия';
        } elseif (strpos($name, 'flying') !== false || strpos($name, 'growth') !== false || strpos($name, 'diminution') !== false) {
            return 'Трансмутация';
        } elseif (strpos($name, 'poison') !== false || strpos($desc, 'poison') !== false) {
            return 'Некромантия';
        } elseif (strpos($name, 'clairvoyance') !== false || strpos($name, 'mind reading') !== false) {
            return 'Прорицание';
        } else {
            return 'Универсальное';
        }
    }
    
    /**
     * Форматирование описания
     */
    private function formatDescription($desc_array) {
        if (empty($desc_array)) {
            return 'Описание недоступно';
        }
        
        return implode(' ', $desc_array);
    }
    
    /**
     * Получение стоимости зелья
     */
    private function getPotionValue($potion_data) {
        $rarity = strtolower($potion_data['rarity']['name'] ?? 'common');
        
        $values = [
            'common' => '50 золотых',
            'uncommon' => '150 золотых',
            'rare' => '500 золотых',
            'very rare' => '1000 золотых',
            'legendary' => '5000 золотых'
        ];
        
        return $values[$rarity] ?? '100 золотых';
    }
    
    /**
     * Получение веса зелья
     */
    private function getPotionWeight($potion_data) {
        return '0.5 фунта';
    }
    
    /**
     * Получение иконки зелья
     */
    private function getPotionIcon($potion_data) {
        $type = $this->determinePotionType($potion_data);
        
        $icons = [
            'Восстановление' => '🩹',
            'Усиление' => '💪',
            'Защита' => '🛡️',
            'Иллюзия' => '👁️',
            'Трансмутация' => '🔄',
            'Некромантия' => '💀',
            'Прорицание' => '🔮',
            'Универсальное' => '🧪'
        ];
        
        return $icons[$type] ?? '🧪';
    }
    
    /**
     * Получение цвета зелья
     */
    private function getPotionColor($rarity) {
        $colors = [
            'Common' => '#9b9b9b',
            'Uncommon' => '#4caf50',
            'Rare' => '#2196f3',
            'Very Rare' => '#9c27b0',
            'Legendary' => '#ff9800'
        ];
        
        return $colors[$rarity] ?? '#9b9b9b';
    }
    
    /**
     * Получение свойств зелья
     */
    private function getPotionProperties($potion_data) {
        $properties = ['Питье', 'Магическое'];
        
        // Добавляем редкость
        $properties[] = $potion_data['rarity']['name'] ?? 'Unknown';
        
        // Добавляем специальные свойства
        $name = strtolower($potion_data['name']);
        if (strpos($name, 'poison') !== false) {
            $properties[] = 'Яд';
        }
        
        return $properties;
    }
    
    /**
     * Генерация AI-описания
     */
    private function generateAIDescription($potion) {
        $prompt = "Опиши зелье '{$potion['name']}' ({$potion['rarity']} редкость, {$potion['type']}). " .
                 "Дополни описание практическими советами для мастера D&D. " .
                 "Ответ должен быть кратким (2-3 предложения) и полезным.";
        
        try {
            $response = $this->callDeepSeek($prompt);
            if ($response) {
                return trim($response);
            }
        } catch (Exception $e) {
            error_log("Ошибка AI генерации описания зелья: " . $e->getMessage());
        }
        
        return null;
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
                ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Давай краткие и практичные описания зелий.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 150,
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
    try {
        $generator = new PotionGenerator();
        $result = $generator->generatePotions($_POST);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Критическая ошибка: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'random';
    
    try {
        $generator = new PotionGenerator();
        
        switch ($action) {
            case 'rarities':
                $result = ['Common', 'Uncommon', 'Rare', 'Very Rare', 'Legendary'];
                break;
                
            case 'types':
                $result = ['Восстановление', 'Усиление', 'Защита', 'Иллюзия', 'Трансмутация', 'Некромантия', 'Прорицание', 'Универсальное'];
                break;
                
            case 'random':
                $result = $generator->generatePotions($_GET);
                break;
                
            default:
                throw new Exception('Неизвестное действие');
        }
        
        if ($action === 'random') {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => true,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Метод не поддерживается'
    ]);
}
?>
