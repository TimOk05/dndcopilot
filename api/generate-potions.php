<?php
/**
 * API для генерации зелий D&D через официальную D&D 5e API
 * Использует https://www.dnd5eapi.co/api для получения реальных зелий
 * Поддерживает поиск по характеристикам: редкость, тип, эффект
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
    private $cache_file = __DIR__ . '/../logs/cache/potions_cache.json';
    private $cache_duration = 3600; // 1 час
    
    public function __construct() {
        // Создаем директорию для кеша если не существует
        $cache_dir = dirname($this->cache_file);
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
    }
    
    /**
     * Генерация зелий через D&D API с фильтрацией по характеристикам
     */
    public function generatePotions($params) {
        $count = (int)($params['count'] ?? 1);
        $rarity = $params['rarity'] ?? '';
        $type = $params['type'] ?? '';
        $effect = $params['effect'] ?? '';
        
        // Валидация параметров
        if ($count < 1 || $count > 20) {
            throw new Exception('Количество зелий должно быть от 1 до 20');
        }
        
        try {
            // Получаем все зелья из кеша или API
            $all_potions = $this->getAllPotions();
            
            // Фильтруем зелья по параметрам
            $filtered_potions = $this->filterPotionsByCriteria($all_potions, $rarity, $type, $effect);
            
            if (empty($filtered_potions)) {
                // Если нет точных совпадений, возвращаем случайные зелья
                $filtered_potions = $this->getRandomPotionsFallback($all_potions, $count);
            }
            
            // Выбираем случайные зелья
            $selected_potions = $this->selectRandomPotions($filtered_potions, $count);
            
            // Получаем детальную информацию о каждом зелье
            $detailed_potions = [];
            foreach ($selected_potions as $potion) {
                $detailed_potion = $this->getPotionDetails($potion);
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
                    'rarity' => $rarity,
                    'type' => $type,
                    'effect' => $effect
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
     * Получение всех зелий из кеша или API
     */
    private function getAllPotions() {
        // Проверяем кеш
        if ($this->isCacheValid()) {
            $cached_data = $this->loadFromCache();
            if ($cached_data && isset($cached_data['potions'])) {
                return $cached_data['potions'];
            }
        }
        
        // Получаем данные из API
        $potions = $this->fetchPotionsFromAPI();
        
        // Сохраняем в кеш
        $this->saveToCache($potions);
        
        return $potions;
    }
    
    /**
     * Получение зелий из D&D API
     */
    private function fetchPotionsFromAPI() {
        $potions = [];
        
        // Получаем список всех магических предметов
        $magic_items = $this->getMagicItemsList();
        
        // Фильтруем только зелья
        foreach ($magic_items as $item) {
            $name = strtolower($item['name']);
            if ($this->isPotion($name)) {
                $potions[] = $item;
            }
        }
        
        return $potions;
    }
    
    /**
     * Проверка, является ли предмет зельем
     */
    private function isPotion($name) {
        $potion_keywords = [
            'potion', 'elixir', 'philter', 'oil', 'tincture', 'essence',
            'brew', 'concoction', 'draught', 'tonic', 'extract', 'salve',
            'balm', 'unguent', 'ointment', 'decoction', 'infusion'
        ];
        
        foreach ($potion_keywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Фильтрация зелий по критериям
     */
    private function filterPotionsByCriteria($potions, $rarity, $type, $effect) {
        $filtered = [];
        
        foreach ($potions as $potion) {
            $matches = true;
            
            // Фильтр по редкости
            if ($rarity && !empty($potion['rarity'])) {
                $potion_rarity = strtolower($potion['rarity']['name'] ?? '');
                $request_rarity = strtolower($rarity);
                
                // Нормализуем названия редкости
                $rarity_mapping = [
                    'common' => 'common',
                    'uncommon' => 'uncommon', 
                    'rare' => 'rare',
                    'very rare' => 'very rare',
                    'very_rare' => 'very rare',
                    'legendary' => 'legendary'
                ];
                
                $normalized_request = $rarity_mapping[$request_rarity] ?? $request_rarity;
                if ($potion_rarity !== $normalized_request) {
                    $matches = false;
                }
            }
            
            // Фильтр по типу (определяется по названию и описанию)
            if ($type && $matches) {
                $potion_type = $this->determinePotionType($potion);
                if ($potion_type !== $type) {
                    $matches = false;
                }
            }
            
            // Фильтр по эффекту
            if ($effect && $matches) {
                $potion_effects = $this->getPotionEffects($potion);
                $effect_found = false;
                foreach ($potion_effects as $potion_effect) {
                    if (stripos($potion_effect, $effect) !== false) {
                        $effect_found = true;
                        break;
                    }
                }
                if (!$effect_found) {
                    $matches = false;
                }
            }
            
            if ($matches) {
                $filtered[] = $potion;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Fallback: получение случайных зелий если фильтр не дал результатов
     */
    private function getRandomPotionsFallback($all_potions, $count) {
        $potion_types = ['Восстановление', 'Усиление', 'Защита', 'Иллюзия', 'Трансмутация', 'Некромантия', 'Прорицание', 'Эвокация', 'Универсальное'];
        $fallback_potions = [];
        
        // Пытаемся найти зелья разных типов
        foreach ($potion_types as $type) {
            if (count($fallback_potions) >= $count) break;
            
            foreach ($all_potions as $potion) {
                if (count($fallback_potions) >= $count) break;
                
                $potion_type = $this->determinePotionType($potion);
                if ($potion_type === $type && !in_array($potion, $fallback_potions)) {
                    $fallback_potions[] = $potion;
                }
            }
        }
        
        // Если все еще мало зелий, добавляем любые доступные
        if (count($fallback_potions) < $count) {
            foreach ($all_potions as $potion) {
                if (count($fallback_potions) >= $count) break;
                if (!in_array($potion, $fallback_potions)) {
                    $fallback_potions[] = $potion;
                }
            }
        }
        
        return $fallback_potions;
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
        // Если у нас уже есть детальная информация в кеше
        if (isset($potion['desc']) && isset($potion['rarity'])) {
            return $this->formatPotionData($potion);
        }
        
        // Получаем детальную информацию из API
        if (isset($potion['url'])) {
            $url = $this->dnd5e_api_url . $potion['url'];
            $response = $this->makeRequest($url);
            
            if ($response) {
                return $this->formatPotionData($response);
            }
        }
        
        // Если не удалось получить детали, возвращаем базовую информацию
        return $this->formatBasicPotionData($potion);
    }
    
    /**
     * Форматирование данных зелья
     */
    private function formatPotionData($potion_data) {
        $type = $this->determinePotionType($potion_data);
        $effects = $this->getPotionEffects($potion_data);
        
        return [
            'name' => $potion_data['name'],
            'rarity' => $potion_data['rarity']['name'] ?? 'Unknown',
            'type' => $type,
            'description' => $this->formatDescription($potion_data['desc'] ?? []),
            'effects' => $effects,
            'value' => $this->getPotionValue($potion_data),
            'weight' => '0.5 фунта',
            'icon' => $this->getPotionIcon($type),
            'color' => $this->getPotionColor($potion_data['rarity']['name'] ?? 'Common'),
            'properties' => $this->getPotionProperties($potion_data),
            'equipment_category' => $potion_data['equipment_category']['name'] ?? 'Adventuring Gear',
            'cost' => $this->getPotionCost($potion_data)
        ];
    }

    /**
     * Форматирование базовых данных зелья (когда детали недоступны)
     */
    private function formatBasicPotionData($potion_data) {
        $type = $this->determinePotionType($potion_data);
        
        return [
            'name' => $potion_data['name'],
            'rarity' => $potion_data['rarity']['name'] ?? 'Common',
            'type' => $type,
            'description' => 'Описание зелья недоступно. Это магическое зелье, которое можно найти в мире D&D.',
            'effects' => ['Магический эффект'],
            'value' => $this->getPotionValue($potion_data),
            'weight' => '0.5 фунта',
            'icon' => $this->getPotionIcon($type),
            'color' => $this->getPotionColor($potion_data['rarity']['name'] ?? 'Common'),
            'properties' => ['Питье', 'Магическое'],
            'equipment_category' => 'Adventuring Gear',
            'cost' => $this->getPotionValue($potion_data)
        ];
    }
    
    /**
     * Определение типа зелья
     */
    private function determinePotionType($potion_data) {
        $name = strtolower($potion_data['name']);
        $desc = strtolower(implode(' ', $potion_data['desc'] ?? []));
        
        // Определяем тип по названию и описанию
        if (strpos($name, 'healing') !== false || strpos($desc, 'heal') !== false || strpos($desc, 'hit point') !== false || strpos($desc, 'regain') !== false || strpos($desc, 'restore') !== false) {
            return 'Восстановление';
        } elseif (strpos($name, 'strength') !== false || strpos($name, 'giant') !== false || strpos($desc, 'strength') !== false || strpos($desc, 'advantage') !== false || strpos($desc, 'enhance') !== false) {
            return 'Усиление';
        } elseif (strpos($name, 'resistance') !== false || strpos($name, 'invulnerability') !== false || strpos($desc, 'resistance') !== false || strpos($desc, 'immune') !== false || strpos($desc, 'protection') !== false) {
            return 'Защита';
        } elseif (strpos($name, 'invisibility') !== false || strpos($name, 'disguise') !== false || strpos($desc, 'invisible') !== false || strpos($desc, 'disguise') !== false || strpos($desc, 'illusion') !== false) {
            return 'Иллюзия';
        } elseif (strpos($name, 'flying') !== false || strpos($name, 'growth') !== false || strpos($name, 'diminution') !== false || strpos($desc, 'fly') !== false || strpos($desc, 'size') !== false || strpos($desc, 'transform') !== false) {
            return 'Трансмутация';
        } elseif (strpos($name, 'poison') !== false || strpos($desc, 'poison') !== false || strpos($desc, 'damage') !== false || strpos($desc, 'harm') !== false) {
            return 'Некромантия';
        } elseif (strpos($name, 'clairvoyance') !== false || strpos($name, 'vision') !== false || strpos($name, 'foresight') !== false || strpos($desc, 'see') !== false || strpos($desc, 'vision') !== false || strpos($desc, 'divination') !== false) {
            return 'Прорицание';
        } elseif (strpos($name, 'fire') !== false || strpos($name, 'frost') !== false || strpos($name, 'lightning') !== false || strpos($name, 'thunder') !== false || strpos($desc, 'fire') !== false || strpos($desc, 'cold') !== false || strpos($desc, 'lightning') !== false || strpos($desc, 'energy') !== false) {
            return 'Эвокация';
        } else {
            return 'Универсальное';
        }
    }
    
    /**
     * Получение эффектов зелья
     */
    private function getPotionEffects($potion_data) {
        $effects = [];
        $desc = $potion_data['desc'] ?? [];
        
        foreach ($desc as $paragraph) {
            // Ищем ключевые слова эффектов
            $keywords = [
                'heal', 'damage', 'advantage', 'disadvantage', 'resistance', 'immune',
                'invisible', 'fly', 'strength', 'poison', 'see', 'vision', 'fire', 'cold',
                'lightning', 'acid', 'thunder', 'force', 'necrotic', 'radiant', 'psychic',
                'restore', 'enhance', 'protection', 'illusion', 'transform', 'divination'
            ];
            
            foreach ($keywords as $keyword) {
                if (stripos($paragraph, $keyword) !== false) {
                    $effects[] = ucfirst($keyword);
                }
            }
        }
        
        return array_unique($effects);
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
            'very_rare' => '1000 золотых',
            'legendary' => '5000 золотых'
        ];
        
        return $values[$rarity] ?? '100 золотых';
    }
    
    /**
     * Получение стоимости в стандартном формате D&D
     */
    private function getPotionCost($potion_data) {
        if (isset($potion_data['cost'])) {
            $cost = $potion_data['cost'];
            if (isset($cost['quantity']) && isset($cost['unit'])) {
                $unit_names = [
                    'cp' => 'медных монет',
                    'sp' => 'серебряных монет', 
                    'ep' => 'электрумовых монет',
                    'gp' => 'золотых монет',
                    'pp' => 'платиновых монет'
                ];
                $unit_name = $unit_names[$cost['unit']] ?? $cost['unit'];
                return $cost['quantity'] . ' ' . $unit_name;
            }
        }
        
        // Fallback к базовой стоимости по редкости
        return $this->getPotionValue($potion_data);
    }
    
    /**
     * Получение иконки зелья
     */
    private function getPotionIcon($type) {
        $icons = [
            'Восстановление' => '🩹',
            'Усиление' => '💪',
            'Защита' => '🛡️',
            'Иллюзия' => '👁️',
            'Трансмутация' => '🔄',
            'Некромантия' => '💀',
            'Прорицание' => '🔮',
            'Эвокация' => '⚡',
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
            'Very_Rare' => '#9c27b0',
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
     * Проверка валидности кеша
     */
    private function isCacheValid() {
        if (!file_exists($this->cache_file)) {
            return false;
        }
        
        $file_time = filemtime($this->cache_file);
        return (time() - $file_time) < $this->cache_duration;
    }
    
    /**
     * Загрузка из кеша
     */
    private function loadFromCache() {
        $content = file_get_contents($this->cache_file);
        return json_decode($content, true);
    }
    
    /**
     * Сохранение в кеш
     */
    private function saveToCache($potions) {
        $data = [
            'timestamp' => time(),
            'potions' => $potions
        ];
        
        file_put_contents($this->cache_file, json_encode($data, JSON_UNESCAPED_UNICODE));
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
            return null;
        }
        
        if ($http_code === 200 && $response) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return null;
    }
    
    /**
     * Получение статистики зелий
     */
    public function getStats() {
        try {
            $all_potions = $this->getAllPotions();
            
            $stats = [
                'total_potions' => count($all_potions),
                'rarity_distribution' => [],
                'type_distribution' => []
            ];
            
            foreach ($all_potions as $potion) {
                // Статистика по редкости
                $rarity = $potion['rarity']['name'] ?? 'Unknown';
                $stats['rarity_distribution'][$rarity] = ($stats['rarity_distribution'][$rarity] ?? 0) + 1;
                
                // Статистика по типу
                $type = $this->determinePotionType($potion);
                $stats['type_distribution'][$type] = ($stats['type_distribution'][$type] ?? 0) + 1;
            }
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Поиск зелий по характеристикам
     */
    public function searchPotions($params) {
        $query = $params['query'] ?? '';
        $rarity = $params['rarity'] ?? '';
        $type = $params['type'] ?? '';
        $effect = $params['effect'] ?? '';
        
        try {
            $all_potions = $this->getAllPotions();
            $filtered_potions = $this->filterPotionsByCriteria($all_potions, $rarity, $type, $effect);
            
            // Дополнительная фильтрация по поисковому запросу
            if ($query) {
                $filtered_potions = array_filter($filtered_potions, function($potion) use ($query) {
                    $name = strtolower($potion['name']);
                    $desc = strtolower(implode(' ', $potion['desc'] ?? []));
                    $search = strtolower($query);
                    
                    return strpos($name, $search) !== false || strpos($desc, $search) !== false;
                });
            }
            
            // Получаем детальную информацию
            $detailed_potions = [];
            foreach (array_slice($filtered_potions, 0, 20) as $potion) { // Ограничиваем 20 результатами
                $detailed_potion = $this->getPotionDetails($potion);
                if ($detailed_potion) {
                    $detailed_potions[] = $detailed_potion;
                }
            }
            
            return [
                'success' => true,
                'data' => $detailed_potions,
                'count' => count($detailed_potions),
                'total_found' => count($filtered_potions)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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
                $result = ['Восстановление', 'Усиление', 'Защита', 'Иллюзия', 'Трансмутация', 'Некромантия', 'Прорицание', 'Эвокация', 'Универсальное'];
                break;
                
            case 'effects':
                $result = ['Heal', 'Damage', 'Advantage', 'Disadvantage', 'Resistance', 'Immune', 'Invisible', 'Fly', 'Strength', 'Poison', 'See', 'Vision', 'Fire', 'Cold', 'Lightning', 'Acid', 'Thunder', 'Force', 'Necrotic', 'Radiant', 'Psychic'];
                break;
                
            case 'stats':
                $result = $generator->getStats();
                break;
                
            case 'search':
                $result = $generator->searchPotions($_GET);
                break;
                
            case 'random':
                $result = $generator->generatePotions($_GET);
                break;
                
            default:
                throw new Exception('Неизвестное действие');
        }
        
        if (in_array($action, ['random', 'search', 'stats'])) {
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
