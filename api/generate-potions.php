<?php
/**
 * API для генерации зелий D&D
 * Использует официальную D&D 5e API для получения реальных зелий
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
    private $cache_dir;
    private $max_retries = 3;
    private $retry_delay = 1000; // миллисекунды
    
    public function __construct() {
        $this->cache_dir = __DIR__ . '/../logs/cache';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Генерация случайных зелий
     */
    public function generateRandomPotions($count = 1, $rarity = null, $type = null) {
        try {
            error_log("PotionGenerator: Начинаем генерацию зелий. count=$count, rarity=$rarity, type=$type");
            
            // Получаем список всех магических предметов из D&D API
            error_log("PotionGenerator: Получаем список магических предметов...");
            $magic_items = $this->getMagicItemsListWithRetry();
            
            if (empty($magic_items)) {
                error_log("PotionGenerator: Список магических предметов пуст");
                throw new Exception('База данных магических предметов недоступна');
            }
            
            error_log("PotionGenerator: Получено магических предметов: " . count($magic_items));
            
            // Фильтруем только зелья
            error_log("PotionGenerator: Фильтруем зелья...");
            $potions = $this->filterPotions($magic_items);
            
            if (empty($potions)) {
                error_log("PotionGenerator: Зелья не найдены");
                throw new Exception('Зелья не найдены в базе данных D&D');
            }
            
            error_log("PotionGenerator: Найдено зелий: " . count($potions));
            
            // Фильтруем по редкости и типу
            error_log("PotionGenerator: Фильтруем по параметрам...");
            $filtered_potions = $this->filterPotionsByParams($potions, $rarity, $type);
            
            if (empty($filtered_potions)) {
                error_log("PotionGenerator: После фильтрации зелья не найдены");
                throw new Exception('Не найдены зелья с указанными параметрами');
            }
            
            error_log("PotionGenerator: После фильтрации осталось зелий: " . count($filtered_potions));
            
            // Выбираем случайные зелья
            error_log("PotionGenerator: Выбираем случайные зелья...");
            $selected_potions = $this->selectRandomPotions($filtered_potions, $count);
            
            // Получаем детальную информацию о каждом зелье
            error_log("PotionGenerator: Получаем детали зелий...");
            $result = [];
            foreach ($selected_potions as $potion) {
                error_log("PotionGenerator: Получаем детали для зелья: " . $potion['name']);
                $potion_details = $this->getPotionDetails($potion['index']);
                if ($potion_details) {
                    $result[] = $this->formatPotionData($potion_details);
                }
            }
            
            if (empty($result)) {
                error_log("PotionGenerator: Не удалось получить детали зелий");
                throw new Exception('Не удалось получить детали зелий');
            }
            
            error_log("PotionGenerator: Успешно сгенерировано зелий: " . count($result));
            return $result;
            
        } catch (Exception $e) {
            error_log("PotionGenerator: Ошибка генерации зелий: " . $e->getMessage());
            throw new Exception('Ошибка генерации зелий: ' . $e->getMessage());
        }
    }
    
    /**
     * Получение списка магических предметов с retry
     */
    private function getMagicItemsListWithRetry() {
        $cache_file = $this->cache_dir . '/magic_items_list.json';
        $cache_time = 3600; // 1 час
        
        error_log("PotionGenerator: Проверяем кэш магических предметов: $cache_file");
        
        // Проверяем кэш
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
            error_log("PotionGenerator: Используем кэшированные данные");
            $cached_data = json_decode(file_get_contents($cache_file), true);
            if ($cached_data && isset($cached_data['results'])) {
                error_log("PotionGenerator: Кэш содержит " . count($cached_data['results']) . " предметов");
                return $cached_data['results'];
            } else {
                error_log("PotionGenerator: Кэш поврежден или пуст");
            }
        } else {
            error_log("PotionGenerator: Кэш не найден или устарел");
        }
        
        // Пробуем получить с retry
        for ($attempt = 1; $attempt <= $this->max_retries; $attempt++) {
            try {
                error_log("PotionGenerator: Попытка $attempt получить список магических предметов");
                $magic_items = $this->getMagicItemsList();
                
                if ($magic_items && isset($magic_items['results']) && !empty($magic_items['results'])) {
                    error_log("PotionGenerator: Успешно получено " . count($magic_items['results']) . " предметов");
                    // Сохраняем в кэш
                    file_put_contents($cache_file, json_encode($magic_items));
                    error_log("PotionGenerator: Данные сохранены в кэш");
                    return $magic_items['results'];
                } else {
                    error_log("PotionGenerator: Получены неверные данные от API");
                }
            } catch (Exception $e) {
                error_log("PotionGenerator: Попытка $attempt не удалась: " . $e->getMessage());
                if ($attempt < $this->max_retries) {
                    error_log("PotionGenerator: Ждем " . ($this->retry_delay / 1000) . " секунд перед следующей попыткой");
                    usleep($this->retry_delay * 1000);
                }
            }
        }
        
        error_log("PotionGenerator: Все попытки исчерпаны");
        throw new Exception('Не удалось получить список магических предметов после ' . $this->max_retries . ' попыток');
    }
    
    /**
     * Получение списка магических предметов
     */
    private function getMagicItemsList() {
        $url = $this->dnd5e_api_url . '/magic-items';
        error_log("PotionGenerator: Запрос к D&D API: $url");
        
        $response = $this->makeRequest($url);
        error_log("PotionGenerator: Ответ от D&D API получен: " . ($response ? 'да' : 'нет'));
        
        if ($response && isset($response['results'])) {
            error_log("PotionGenerator: Найдено магических предметов: " . count($response['results']));
            return $response;
        }
        
        throw new Exception('API D&D недоступен или возвращает неверную структуру');
    }
    
    /**
     * Фильтрация зелий из списка магических предметов
     */
    private function filterPotions($magic_items) {
        $potions = [];
        
        foreach ($magic_items as $item) {
            $name = strtolower($item['name']);
            // Ищем зелья по названию
            if (strpos($name, 'potion') !== false || 
                strpos($name, 'elixir') !== false || 
                strpos($name, 'philter') !== false ||
                strpos($name, 'oil') !== false) {
                $potions[] = $item;
            }
        }
        
        error_log("PotionGenerator: Найдено зелий: " . count($potions));
        return $potions;
    }
    
    /**
     * Фильтрация зелий по параметрам
     */
    private function filterPotionsByParams($potions, $rarity, $type) {
        $filtered = [];
        
        foreach ($potions as $potion) {
            $include = true;
            
            // Фильтр по редкости
            if ($rarity && $rarity !== '') {
                $potion_rarity = strtolower($potion['rarity'] ?? '');
                if ($potion_rarity !== strtolower($rarity)) {
                    $include = false;
                }
            }
            
            // Фильтр по типу (если есть)
            if ($type && $type !== '') {
                // Здесь можно добавить логику фильтрации по типу
                // Пока пропускаем все
            }
            
            if ($include) {
                $filtered[] = $potion;
            }
        }
        
        return $filtered;
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
            $index = array_rand($available);
            $selected[] = $available[$index];
            unset($available[$index]);
        }
        
        return $selected;
    }
    
    /**
     * Получение детальной информации о зелье
     */
    private function getPotionDetails($potion_index) {
        $cache_file = $this->cache_dir . '/potion_' . md5($potion_index) . '.json';
        $cache_time = 86400; // 24 часа
        
        // Проверяем кэш
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
            $cached_data = json_decode(file_get_contents($cache_file), true);
            if ($cached_data) {
                return $cached_data;
            }
        }
        
        // Получаем с API
        $url = $this->dnd5e_api_url . '/magic-items/' . $potion_index;
        $response = $this->makeRequest($url);
        
        if ($response) {
            // Сохраняем в кэш
            file_put_contents($cache_file, json_encode($response));
            return $response;
        }
        
        return null;
    }
    
    /**
     * Форматирование данных зелья
     */
    private function formatPotionData($potion_data) {
        $rarity = $potion_data['rarity'] ?? 'Unknown';
        $rarity_color = $this->getRarityColor($rarity);
        $type_icon = $this->getTypeIcon($potion_data);
        
        return [
            'name' => $potion_data['name'] ?? 'Unknown Potion',
            'description' => is_array($potion_data['desc']) 
                ? implode(' ', $potion_data['desc']) 
                : ($potion_data['desc'] ?? 'Описание недоступно'),
            'rarity' => ucfirst($rarity),
            'type' => $this->determinePotionType($potion_data),
            'value' => $this->getPotionValue($potion_data),
            'weight' => $this->getPotionWeight($potion_data),
            'properties' => $this->getPotionProperties($potion_data),
            'icon' => $type_icon,
            'color' => $rarity_color
        ];
    }
    
    /**
     * Определение цвета редкости
     */
    private function getRarityColor($rarity) {
        $colors = [
            'common' => '#9b9b9b',
            'uncommon' => '#4caf50',
            'rare' => '#2196f3',
            'very rare' => '#9c27b0',
            'legendary' => '#ff9800'
        ];
        
        return $colors[strtolower($rarity)] ?? '#9b9b9b';
    }
    
    /**
     * Определение иконки типа зелья
     */
    private function getTypeIcon($potion_data) {
        $name = strtolower($potion_data['name'] ?? '');
        $desc = strtolower($potion_data['desc'] ?? '');
        
        if (strpos($name, 'healing') !== false || strpos($desc, 'heal') !== false) {
            return '🩹';
        } elseif (strpos($name, 'strength') !== false || strpos($desc, 'strength') !== false) {
            return '💪';
        } elseif (strpos($name, 'protection') !== false || strpos($desc, 'protection') !== false) {
            return '🛡️';
        } elseif (strpos($name, 'invisibility') !== false || strpos($desc, 'invisible') !== false) {
            return '👁️';
        } elseif (strpos($name, 'flying') !== false || strpos($desc, 'fly') !== false) {
            return '🔄';
        } elseif (strpos($name, 'poison') !== false || strpos($desc, 'poison') !== false) {
            return '💀';
        } else {
            return '🔮';
        }
    }
    
    /**
     * Определение типа зелья
     */
    private function determinePotionType($potion_data) {
        $name = strtolower($potion_data['name'] ?? '');
        $desc = strtolower($potion_data['desc'] ?? '');
        
        if (strpos($name, 'healing') !== false || strpos($desc, 'heal') !== false) {
            return 'Восстановление';
        } elseif (strpos($name, 'strength') !== false || strpos($desc, 'strength') !== false) {
            return 'Усиление';
        } elseif (strpos($name, 'protection') !== false || strpos($desc, 'protection') !== false) {
            return 'Защита';
        } elseif (strpos($name, 'invisibility') !== false || strpos($desc, 'invisible') !== false) {
            return 'Иллюзия';
        } elseif (strpos($name, 'flying') !== false || strpos($desc, 'fly') !== false) {
            return 'Трансмутация';
        } elseif (strpos($name, 'poison') !== false || strpos($desc, 'poison') !== false) {
            return 'Некромантия';
        } else {
            return 'Прорицание';
        }
    }
    
    /**
     * Получение стоимости зелья
     */
    private function getPotionValue($potion_data) {
        // Пытаемся получить стоимость из API
        if (isset($potion_data['equipment_category'])) {
            // Здесь можно добавить логику получения стоимости
            return 'Стоимость неизвестна';
        }
        
        return 'Стоимость неизвестна';
    }
    
    /**
     * Получение веса зелья
     */
    private function getPotionWeight($potion_data) {
        // Пытаемся получить вес из API
        if (isset($potion_data['weight'])) {
            return $potion_data['weight'] . ' фунтов';
        }
        
        return '0.5 фунта';
    }
    
    /**
     * Получение свойств зелья
     */
    private function getPotionProperties($potion_data) {
        $properties = ['Питье', 'Магическое'];
        
        // Добавляем свойства на основе данных API
        if (isset($potion_data['rarity'])) {
            $properties[] = ucfirst($potion_data['rarity']);
        }
        
        return $properties;
    }
    
    /**
     * Выполнение HTTP запроса
     */
    private function makeRequest($url) {
        error_log("PotionGenerator: Выполняем HTTP запрос к: $url");
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'user_agent' => 'DnD-Copilot/1.0'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $start_time = microtime(true);
        $response = @file_get_contents($url, false, $context);
        $end_time = microtime(true);
        
        error_log("PotionGenerator: HTTP запрос выполнен за " . round(($end_time - $start_time) * 1000, 2) . " мс");
        
        if ($response === false) {
            $error = error_get_last();
            $error_msg = $error['message'] ?? 'Неизвестная ошибка';
            error_log("PotionGenerator: HTTP запрос не удался: $error_msg");
            throw new Exception('HTTP запрос не удался: ' . $error_msg);
        }
        
        error_log("PotionGenerator: Получен ответ длиной " . strlen($response) . " символов");
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $json_error = json_last_error_msg();
            error_log("PotionGenerator: Ошибка парсинга JSON: $json_error");
            throw new Exception('Ошибка парсинга JSON: ' . $json_error);
        }
        
        error_log("PotionGenerator: JSON успешно распарсен");
        return $data;
    }
    
    /**
     * Получение доступных редкостей
     */
    public function getAvailableRarities() {
        return ['common', 'uncommon', 'rare', 'very rare', 'legendary'];
    }
    
    /**
     * Получение доступных типов
     */
    public function getAvailableTypes() {
        return ['Восстановление', 'Усиление', 'Защита', 'Иллюзия', 'Трансмутация', 'Некромантия', 'Прорицание'];
    }
    
    /**
     * Получение зелий по типу
     */
    public function getPotionsByType($type) {
        try {
            $magic_items = $this->getMagicItemsListWithRetry();
            $potions = $this->filterPotions($magic_items);
            
            $filtered = [];
            foreach ($potions as $potion) {
                $potion_details = $this->getPotionDetails($potion['index']);
                if ($potion_details) {
                    $formatted = $this->formatPotionData($potion_details);
                    if ($formatted['type'] === $type) {
                        $filtered[] = $formatted;
                    }
                }
            }
            
            return $filtered;
            
        } catch (Exception $e) {
            throw new Exception('Ошибка получения зелий по типу: ' . $e->getMessage());
        }
    }
}

// Обработка запросов
error_log("PotionGenerator: Получен запрос. action=" . ($_GET['action'] ?? 'random') . ", count=" . ($_GET['count'] ?? 1) . ", rarity=" . ($_GET['rarity'] ?? 'null') . ", type=" . ($_GET['type'] ?? 'null'));

$generator = new PotionGenerator();

$action = $_GET['action'] ?? 'random';
$count = (int)($_GET['count'] ?? 1);
$rarity = $_GET['rarity'] ?? null;
$type = $_GET['type'] ?? null;

try {
    error_log("PotionGenerator: Обрабатываем действие: $action");
    
    switch ($action) {
        case 'random':
            if ($count > 10) $count = 10; // Ограничиваем количество
            error_log("PotionGenerator: Генерируем $count случайных зелий");
            $result = $generator->generateRandomPotions($count, $rarity, $type);
            break;
            
        case 'by_type':
            if (!$type) {
                throw new Exception('Тип зелья не указан');
            }
            error_log("PotionGenerator: Получаем зелья типа: $type");
            $result = $generator->getPotionsByType($type);
            break;
            
        case 'rarities':
            error_log("PotionGenerator: Получаем доступные редкости");
            $result = $generator->getAvailableRarities();
            break;
            
        case 'types':
            error_log("PotionGenerator: Получаем доступные типы");
            $result = $generator->getAvailableTypes();
            break;
            
        default:
            throw new Exception('Неизвестное действие');
    }
    
    error_log("PotionGenerator: Действие выполнено успешно");
    echo json_encode([
        'success' => true,
        'data' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("PotionGenerator: Ошибка при выполнении действия: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
