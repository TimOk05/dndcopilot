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
    
    public function __construct() {
        // Инициализация без AI
    }
    
    /**
     * Генерация зелий через D&D API
     */
    public function generatePotions($params) {
        $count = (int)($params['count'] ?? 1);
        $rarity = $params['rarity'] ?? '';
        $type = $params['type'] ?? '';
        
        error_log("PotionGenerator: Начинаем генерацию зелий. count=$count, rarity=$rarity, type=$type");
        
        // Валидация параметров
        if ($count < 1 || $count > 10) {
            throw new Exception('Количество зелий должно быть от 1 до 10');
        }
        
        try {
            // Получаем список всех магических предметов
            error_log("PotionGenerator: Получаем список магических предметов...");
            $magic_items = $this->getMagicItemsList();
            
            // Фильтруем только зелья
            error_log("PotionGenerator: Фильтруем зелья...");
            $potions = $this->filterPotions($magic_items);
            
            if (empty($potions)) {
                error_log("PotionGenerator: Зелья не найдены");
                throw new Exception('Не найдены зелья в базе данных D&D');
            }
            
            // Выбираем случайные зелья (без фильтрации по параметрам для начала)
            error_log("PotionGenerator: Выбираем случайные зелья...");
            $selected_potions = $this->selectRandomPotions($potions, $count);
            
            // Получаем детальную информацию о каждом зелье
            error_log("PotionGenerator: Получаем детали зелий...");
            $detailed_potions = [];
            foreach ($selected_potions as $potion) {
                error_log("PotionGenerator: Получаем детали для зелья: " . $potion['name']);
                $detailed_potion = $this->getPotionDetails($potion);
                if ($detailed_potion) {
                    $detailed_potions[] = $detailed_potion;
                }
            }
            
            if (empty($detailed_potions)) {
                error_log("PotionGenerator: Не удалось получить детали зелий");
                throw new Exception('Не удалось получить детальную информацию о зельях');
            }
            
            error_log("PotionGenerator: Успешно сгенерировано зелий: " . count($detailed_potions));
            
            return [
                'success' => true,
                'data' => $detailed_potions,
                'count' => count($detailed_potions),
                'total_available' => count($potions)
            ];
            
        } catch (Exception $e) {
            error_log("PotionGenerator: Ошибка генерации зелий: " . $e->getMessage());
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
        error_log("PotionGenerator: Запрос к D&D API: $url");
        
        $response = $this->makeRequest($url);
        
        if ($response && isset($response['results'])) {
            error_log("PotionGenerator: Получено магических предметов: " . count($response['results']));
            return $response['results'];
        }
        
        error_log("PotionGenerator: Ошибка получения магических предметов");
        throw new Exception('Не удалось получить список магических предметов из D&D API');
    }
    
    /**
     * Фильтрация зелий из списка магических предметов
     */
    private function filterPotions($magic_items) {
        $potions = [];
        
        foreach ($magic_items as $item) {
            $name = strtolower($item['name']);
            // Ищем зелья по различным ключевым словам
            if (strpos($name, 'potion') !== false || 
                strpos($name, 'elixir') !== false || 
                strpos($name, 'philter') !== false ||
                strpos($name, 'oil') !== false) {
                $potions[] = $item;
            }
        }
        
        error_log("PotionGenerator: Найдено зелий: " . count($potions));
        if (count($potions) > 0) {
            error_log("PotionGenerator: Примеры зелий: " . implode(', ', array_slice(array_column($potions, 'name'), 0, 5)));
        }
        
        return $potions;
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
    private function getPotionDetails($potion) {
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
     * Выполнение HTTP запроса
     */
    private function makeRequest($url) {
        error_log("PotionGenerator: Выполняем запрос к: $url");
        
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
            error_log("PotionGenerator: CURL Error for $url: $error");
            return null;
        }
        
        error_log("PotionGenerator: HTTP Code for $url: $http_code");
        
        if ($http_code === 200 && $response) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                error_log("PotionGenerator: Успешно декодирован JSON для $url");
                return $decoded;
            } else {
                error_log("PotionGenerator: JSON decode error for $url: " . json_last_error_msg());
                return null;
            }
        }
        
        error_log("PotionGenerator: HTTP Error for $url: $http_code");
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
