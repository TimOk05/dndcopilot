<?php
/**
 * API для генерации зелий D&D через официальную D&D 5e API
 * Использует https://www.dnd5eapi.co/api для получения реальных зелий
 * Поддерживает поиск по характеристикам: редкость, тип, эффект
 */

// Заголовки только если это прямой HTTP запрос
if (!defined('TESTING_MODE')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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
        if ($count < 1 || $count > 10) {
            throw new Exception('Количество зелий должно быть от 1 до 10');
        }
        
        try {
            // Получаем все зелья из кеша или API
            $all_potions = $this->getAllPotions();
            
            // Фильтруем зелья по параметрам
            $filtered_potions = $this->filterPotionsByCriteria($all_potions, $rarity, $type, $effect);
            
            if (empty($filtered_potions)) {
                throw new Exception('Не найдены зелья с указанными характеристиками');
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
        
        try {
            // Получаем список всех магических предметов
            $magic_items = $this->getMagicItemsList();
            
            // Фильтруем только зелья
            foreach ($magic_items as $item) {
                $name = strtolower($item['name']);
                if ($this->isPotion($name)) {
                    $potions[] = $item;
                }
            }
            
            if (!empty($potions)) {
                return $potions;
            }
        } catch (Exception $e) {
            error_log("Ошибка получения зелий из API: " . $e->getMessage());
        }
        
        // Fallback: возвращаем базовые зелья, если API недоступен
        return $this->getFallbackPotions();
    }
    
    /**
     * Fallback: базовые зелья D&D
     */
    private function getFallbackPotions() {
        return [
            [
                'name' => 'Potion of Healing',
                'url' => '/api/magic-items/potion-of-healing',
                'rarity' => ['name' => 'Common'],
                'desc' => ['A character who drinks the magical red fluid in this vial regains 2d4 + 2 hit points. Drinking or administering a potion takes an action.']
            ],
            [
                'name' => 'Potion of Greater Healing',
                'url' => '/api/magic-items/potion-of-greater-healing',
                'rarity' => ['name' => 'Uncommon'],
                'desc' => ['You regain 4d4 + 4 hit points when you drink this potion. The potion\'s red liquid glimmers when agitated.']
            ],
            [
                'name' => 'Potion of Superior Healing',
                'url' => '/api/magic-items/potion-of-superior-healing',
                'rarity' => ['name' => 'Rare'],
                'desc' => ['You regain 8d4 + 8 hit points when you drink this potion. The potion\'s red liquid glimmers when agitated.']
            ],
            [
                'name' => 'Potion of Supreme Healing',
                'url' => '/api/magic-items/potion-of-supreme-healing',
                'rarity' => ['name' => 'Very Rare'],
                'desc' => ['You regain 10d4 + 20 hit points when you drink this potion. The potion\'s red liquid glimmers when agitated.']
            ],
            [
                'name' => 'Potion of Invisibility',
                'url' => '/api/magic-items/potion-of-invisibility',
                'rarity' => ['name' => 'Very Rare'],
                'desc' => ['This potion\'s container looks empty but feels as though it holds liquid. When you drink it, you become invisible for 1 hour. Anything you wear or carry is invisible with you.']
            ],
            [
                'name' => 'Potion of Flying',
                'url' => '/api/magic-items/potion-of-flying',
                'rarity' => ['name' => 'Very Rare'],
                'desc' => ['When you drink this potion, you gain a flying speed equal to your walking speed for 1 hour and can hover. If you\'re in the air when the potion wears off, you fall unless you have some other means of staying aloft.']
            ],
            [
                'name' => 'Potion of Giant Strength',
                'url' => '/api/magic-items/potion-of-giant-strength',
                'rarity' => ['name' => 'Rare'],
                'desc' => ['When you drink this potion, your Strength score changes to 21 for 1 hour. The potion has no effect on you if your Strength is already 21 or higher.']
            ],
            [
                'name' => 'Potion of Fire Breath',
                'url' => '/api/magic-items/potion-of-fire-breath',
                'rarity' => ['name' => 'Uncommon'],
                'desc' => ['After drinking this potion, you can use a bonus action to exhale fire at a target within 30 feet of you. The target takes 4d6 fire damage, or half as much damage on a successful Dexterity saving throw.']
            ]
        ];
    }
    
    /**
     * Проверка, является ли предмет зельем
     */
    private function isPotion($name) {
        $potion_keywords = [
            'potion', 'elixir', 'philter', 'oil', 'tincture', 'essence',
            'brew', 'concoction', 'draught', 'tonic', 'extract'
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
                if ($potion_rarity !== strtolower($rarity)) {
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
        $url = $this->dnd5e_api_url . $potion['url'];
        $response = $this->makeRequest($url);
        
        if (!$response) {
            return null;
        }
        
        return $this->formatPotionData($response);
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
            'equipment_category' => $potion_data['equipment_category']['name'] ?? 'Adventuring Gear'
        ];
    }
    
    /**
     * Определение типа зелья
     */
    private function determinePotionType($potion_data) {
        $name = strtolower($potion_data['name']);
        $desc = strtolower(implode(' ', $potion_data['desc'] ?? []));
        
        // Определяем тип по названию и описанию
        if (strpos($name, 'healing') !== false || strpos($desc, 'heal') !== false || strpos($desc, 'hit point') !== false || strpos($desc, 'regain') !== false) {
            return 'Восстановление';
        } elseif (strpos($name, 'strength') !== false || strpos($name, 'giant') !== false || strpos($desc, 'strength') !== false || strpos($desc, 'advantage') !== false) {
            return 'Усиление';
        } elseif (strpos($name, 'resistance') !== false || strpos($name, 'invulnerability') !== false || strpos($desc, 'resistance') !== false || strpos($desc, 'immune') !== false) {
            return 'Защита';
        } elseif (strpos($name, 'invisibility') !== false || strpos($name, 'disguise') !== false || strpos($desc, 'invisible') !== false || strpos($desc, 'disguise') !== false) {
            return 'Иллюзия';
        } elseif (strpos($name, 'flying') !== false || strpos($name, 'growth') !== false || strpos($name, 'diminution') !== false || strpos($desc, 'fly') !== false || strpos($desc, 'size') !== false) {
            return 'Трансмутация';
        } elseif (strpos($name, 'poison') !== false || strpos($desc, 'poison') !== false || strpos($desc, 'damage') !== false) {
            return 'Некромантия';
        } elseif (strpos($name, 'clairvoyance') !== false || strpos($name, 'mind reading') !== false || strpos($desc, 'see') !== false || strpos($desc, 'vision') !== false) {
            return 'Прорицание';
        } elseif (strpos($name, 'fire') !== false || strpos($name, 'frost') !== false || strpos($name, 'lightning') !== false || strpos($desc, 'fire') !== false || strpos($desc, 'cold') !== false || strpos($desc, 'lightning') !== false) {
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
                'lightning', 'acid', 'thunder', 'force', 'necrotic', 'radiant', 'psychic'
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
            'legendary' => '5000 золотых'
        ];
        
        return $values[$rarity] ?? '100 золотых';
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
        // Парсим URL
        $parsed = parse_url($url);
        if (!$parsed) {
            return null;
        }
        
        $host = $parsed['host'];
        $port = isset($parsed['port']) ? $parsed['port'] : ($parsed['scheme'] === 'https' ? 443 : 80);
        $path = isset($parsed['path']) ? $parsed['path'] : '/';
        if (isset($parsed['query'])) {
            $path .= '?' . $parsed['query'];
        }
        
        // Для HTTPS используем другой подход
        if ($parsed['scheme'] === 'https') {
            return $this->makeHttpsRequest($host, $path);
        }
        
        // Используем fsockopen для HTTP
        $fp = @fsockopen($host, $port, $errno, $errstr, 30);
        if (!$fp) {
            return null;
        }
        
        // Формируем HTTP запрос
        $request = "GET $path HTTP/1.1\r\n";
        $request .= "Host: $host\r\n";
        $request .= "User-Agent: DnD-Copilot/1.0\r\n";
        $request .= "Connection: close\r\n";
        $request .= "\r\n";
        
        // Отправляем запрос
        fwrite($fp, $request);
        
        // Читаем ответ
        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 1024);
        }
        fclose($fp);
        
        // Парсим HTTP ответ
        $parts = explode("\r\n\r\n", $response, 2);
        if (count($parts) < 2) {
            return null;
        }
        
        $headers = $parts[0];
        $body = $parts[1];
        
        // Проверяем HTTP код
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers, $matches)) {
            $http_code = (int)$matches[1];
            if ($http_code !== 200) {
                return null;
            }
        }
        
        // Декодируем JSON
        if ($body) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return null;
    }
    
    /**
     * Выполнение HTTPS запроса через fsockopen
     */
    private function makeHttpsRequest($host, $path) {
        // Пробуем разные варианты подключения
        $connection_methods = [
            ['ssl://' . $host, 443],
            ['tls://' . $host, 443],
            [$host, 443]
        ];
        
        $fp = null;
        $last_error = '';
        
        foreach ($connection_methods as $method) {
            $fp = @fsockopen($method[0], $method[1], $errno, $errstr, 10);
            if ($fp) {
                break;
            }
            $last_error = "$errstr ($errno)";
        }
        
        if (!$fp) {
            // Логируем ошибку для отладки
            error_log("Не удалось подключиться к $host:443 - $last_error");
            return null;
        }
        
        // Устанавливаем таймаут
        stream_set_timeout($fp, 30);
        
        // Формируем HTTPS запрос
        $request = "GET $path HTTP/1.1\r\n";
        $request .= "Host: $host\r\n";
        $request .= "User-Agent: DnD-Copilot/1.0\r\n";
        $request .= "Accept: application/json\r\n";
        $request .= "Connection: close\r\n";
        $request .= "\r\n";
        
        // Отправляем запрос
        fwrite($fp, $request);
        
        // Читаем ответ
        $response = '';
        $start_time = time();
        
        while (!feof($fp) && (time() - $start_time) < 30) {
            $chunk = fgets($fp, 1024);
            if ($chunk === false) {
                break;
            }
            $response .= $chunk;
        }
        
        fclose($fp);
        
        // Проверяем, получили ли мы ответ
        if (empty($response)) {
            error_log("Пустой ответ от $host$path");
            return null;
        }
        
        // Парсим HTTP ответ
        $parts = explode("\r\n\r\n", $response, 2);
        if (count($parts) < 2) {
            error_log("Неверный формат HTTP ответа от $host$path");
            return null;
        }
        
        $headers = $parts[0];
        $body = $parts[1];
        
        // Проверяем HTTP код
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers, $matches)) {
            $http_code = (int)$matches[1];
            if ($http_code !== 200) {
                error_log("HTTP ошибка $http_code от $host$path");
                return null;
            }
        }
        
        // Декодируем JSON
        if ($body) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            } else {
                error_log("Ошибка JSON от $host$path: " . json_last_error_msg());
                return null;
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

// Обработка запроса только если это прямой HTTP запрос
if (!defined('TESTING_MODE') && isset($_SERVER['REQUEST_METHOD'])) {
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
}
?>
