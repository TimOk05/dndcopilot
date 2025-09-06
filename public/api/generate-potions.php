<?php
/**
 * Генератор зелий для D&D 5e
 * Использует D&D 5e API для получения реальных данных
 */

// Отключаем прямое отображение ошибок для API (логируем в файл)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Устанавливаем обработчик ошибок
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $log_message = "[" . date('Y-m-d H:i:s') . "] PHP Error [$errno]: $errstr in $errfile on line $errline\n";
    file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
    return false; // Позволяем стандартной обработке ошибок продолжиться
});

// Устанавливаем обработчик исключений
set_exception_handler(function($exception) {
    $log_message = "[" . date('Y-m-d H:i:s') . "] Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
    
    if (!defined('TESTING_MODE')) {
        echo json_encode([
            'success' => false,
            'error' => 'Критическая ошибка: ' . $exception->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
});

// Устанавливаем обработчик фатальных ошибок
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $log_message = "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
    }
});

// Логируем начало выполнения
$log_message = "[" . date('Y-m-d H:i:s') . "] generate-potions.php начал выполнение\n";
file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);

/**
 * API для генерации зелий D&D через официальную D&D 5e API
 * Использует https://www.dnd5eapi.co/api для получения реальных зелий
 * Поддерживает поиск по характеристикам: редкость, тип, эффект
 */

// Заголовки только если это прямой HTTP запрос (не CLI)
if (!defined('TESTING_MODE') && php_sapi_name() !== 'cli') {
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Services/language-service.php';
require_once __DIR__ . '/../../app/Services/ai-service.php';

class PotionGenerator {
    private $dnd5e_api_url = 'http://www.dnd5eapi.co';
    private $cache_file = __DIR__ . '/../../data/logs/cache/potions_cache.json';
    private $cache_duration = 3600; // 1 час
    private $language_service;
    private $ai_service;
    
    public function __construct() {
        // Создаем директорию для кеша если не существует
        $cache_dir = dirname($this->cache_file);
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Инициализируем сервисы
        $this->language_service = new LanguageService();
        $this->ai_service = new AiService();
        
        logMessage('INFO', 'PotionGenerator инициализирован с поддержкой языков');
    }
    
    /**
     * Генерация зелий через D&D API с фильтрацией по характеристикам
     */
    public function generatePotions($params) {
        $log_message = "[" . date('Y-m-d H:i:s') . "] generatePotions вызван с параметрами: " . json_encode($params) . "\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        
        $count = (int)($params['count'] ?? 1);
        $rarity = $params['rarity'] ?? '';
        $type = $params['type'] ?? '';
        $effect = $params['effect'] ?? '';
        $language = $params['language'] ?? $this->language_service->getCurrentLanguage();
        
        $log_message = "[" . date('Y-m-d H:i:s') . "] Параметры после обработки: count=$count, rarity=$rarity, type=$type, effect=$effect, language=$language\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        
        // Валидация параметров
        if ($count < 1 || $count > 10) {
            throw new Exception('Количество зелий должно быть от 1 до 10');
        }
        
        try {
            // Получаем все зелья из кеша или API
            $all_potions = $this->getAllPotions();
            $log_message = "[" . date('Y-m-d H:i:s') . "] Получено зелий из getAllPotions: " . count($all_potions) . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            
            // Получаем детальную информацию для всех зелий (выборочно для производительности)
            $detailed_potions = [];
            $sample_size = min(50, count($all_potions)); // Ограничиваем выборку для производительности
            $sample_potions = array_slice($all_potions, 0, $sample_size);
            
            foreach ($sample_potions as $potion) {
                $detailed_potion = $this->getPotionDetails($potion);
                if ($detailed_potion) {
                    $detailed_potions[] = $detailed_potion;
                }
            }
            
            $log_message = "[" . date('Y-m-d H:i:s') . "] Получено детальных зелий: " . count($detailed_potions) . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            
            // Теперь фильтруем детальные зелья по параметрам
            $filtered_potions = $this->filterPotionsByCriteria($detailed_potions, $rarity, $type, $effect);
            $log_message = "[" . date('Y-m-d H:i:s') . "] После фильтрации осталось зелий: " . count($filtered_potions) . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            
            if (empty($filtered_potions)) {
                throw new Exception('Не найдены зелья с указанными характеристиками');
            }
            
            // Выбираем случайные зелья из отфильтрованного списка
            $selected_potions = $this->selectRandomPotions($filtered_potions, $count);
            $log_message = "[" . date('Y-m-d H:i:s') . "] Выбрано случайных зелий: " . count($selected_potions) . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            
            // У нас уже есть детальная информация, поэтому используем выбранные зелья как есть
            $detailed_potions = $selected_potions;
            
            $log_message = "[" . date('Y-m-d H:i:s') . "] Итого получено детальных зелий: " . count($detailed_potions) . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            
            if (empty($detailed_potions)) {
                throw new Exception('Не удалось получить детальную информацию о зельях');
            }
            
            // Переводим зелья на русский язык
            $translated_potions = $this->translatePotions($detailed_potions, 'ru');
            
            return [
                'success' => true,
                'data' => $translated_potions,
                'count' => count($translated_potions),
                'language' => $language,
                'filters' => [
                    'rarity' => $rarity,
                    'type' => $type,
                    'effect' => $effect
                ],
                'translation_info' => [
                    'translated' => true,
                    'ai_used' => true
                ]
            ];
            
        } catch (Exception $e) {
            $log_message = "[" . date('Y-m-d H:i:s') . "] Ошибка в generatePotions: " . $e->getMessage() . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            
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
        $log_message = "[" . date('Y-m-d H:i:s') . "] getAllPotions вызван\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        
        $potions = [];
        
        try {
            // Получаем список всех магических предметов
            $log_message = "[" . date('Y-m-d H:i:s') . "] Вызываем getMagicItemsList\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            
            $magic_items = $this->getMagicItemsList();
            
            $log_message = "[" . date('Y-m-d H:i:s') . "] Получено магических предметов: " . count($magic_items) . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            
            // Фильтруем только зелья
            foreach ($magic_items as $item) {
                $name = strtolower($item['name']);
                if ($this->isPotion($name)) {
                    $potions[] = $item;
                }
            }
            
            $log_message = "[" . date('Y-m-d H:i:s') . "] Найдено зелий: " . count($potions) . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            
            if (!empty($potions)) {
                return $potions;
            }
        } catch (Exception $e) {
            $log_message = "[" . date('Y-m-d H:i:s') . "] Ошибка в getAllPotions: " . $e->getMessage() . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        }
        
        $log_message = "[" . date('Y-m-d H:i:s') . "] getAllPotions возвращает пустой массив\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        
        return [];
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
        
        // Если API недоступен, возвращаем пустой массив
        return [];
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
     * Безопасное приведение к нижнему регистру для русского текста
     */
    private function safeLower($text) {
        // Сначала обрабатываем русские буквы
        $russianMap = [
            'А' => 'а', 'Б' => 'б', 'В' => 'в', 'Г' => 'г', 'Д' => 'д', 'Е' => 'е', 'Ё' => 'ё',
            'Ж' => 'ж', 'З' => 'з', 'И' => 'и', 'Й' => 'й', 'К' => 'к', 'Л' => 'л', 'М' => 'м',
            'Н' => 'н', 'О' => 'о', 'П' => 'п', 'Р' => 'р', 'С' => 'с', 'Т' => 'т', 'У' => 'у',
            'Ф' => 'ф', 'Х' => 'х', 'Ц' => 'ц', 'Ч' => 'ч', 'Ш' => 'ш', 'Щ' => 'щ', 'Ъ' => 'ъ',
            'Ы' => 'ы', 'Ь' => 'ь', 'Э' => 'э', 'Ю' => 'ю', 'Я' => 'я'
        ];
        $text = strtr($text, $russianMap);
        
        // Затем обрабатываем английские буквы
        $englishMap = [
            'A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd', 'E' => 'e', 'F' => 'f', 'G' => 'g',
            'H' => 'h', 'I' => 'i', 'J' => 'j', 'K' => 'k', 'L' => 'l', 'M' => 'm', 'N' => 'n',
            'O' => 'o', 'P' => 'p', 'Q' => 'q', 'R' => 'r', 'S' => 's', 'T' => 't', 'U' => 'u',
            'V' => 'v', 'W' => 'w', 'X' => 'x', 'Y' => 'y', 'Z' => 'z'
        ];
        return strtr($text, $englishMap);
    }
    
    /**
     * Преобразование русских названий в английские для фильтрации
     */
    private function translateFilterToEnglish($value, $type) {
        if (empty($value)) return $value;
        
        if ($type === 'rarity') {
            $rarity_map = [
                'необычная' => 'uncommon', 
                'редкая' => 'rare',
                'очень редкая' => 'very rare',
                'легендарная' => 'legendary',
                'артефакт' => 'artifact'
            ];
            $lower_value = $this->safeLower($value);
            $result = $rarity_map[$lower_value] ?? $value;
            return $result;
        }
        
        if ($type === 'type') {
            // Типы эффектов уже на русском языке, не переводим
            return $value;
        }
        
        return $value;
    }
    
    /**
     * Фильтрация зелий по критериям
     */
    private function filterPotionsByCriteria($potions, $rarity, $type, $effect) {
        $filtered = [];
        
        // Преобразуем русские фильтры в английские
        $english_rarity = $this->translateFilterToEnglish($rarity, 'rarity');
        $english_type = $this->translateFilterToEnglish($type, 'type');
        
        
        foreach ($potions as $potion) {
            $matches = true;
            
            // Фильтр по редкости
            if ($english_rarity && !empty($potion['rarity'])) {
                $potion_rarity = $this->safeLower($potion['rarity']['name'] ?? '');
                $target_rarity = $this->safeLower($english_rarity);
                
                
                if ($potion_rarity !== $target_rarity) {
                    $matches = false;
                }
            }
            
            // Фильтр по типу эффекта (Восстановление, Усиление, Защита и т.д.)
            if ($type && $matches) {
                $potion_effect_type = $this->determinePotionType($potion);
                if ($potion_effect_type !== $type) {
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
        $log_message = "[" . date('Y-m-d H:i:s') . "] getMagicItemsList вызван\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        
        $cache_file = __DIR__ . '/../../data/cache/magic_items.json';
        
        // Проверяем кеш
        if (file_exists($cache_file)) {
            $cache_time = filemtime($cache_file);
            $current_time = time();
            
            // Кеш действителен 1 час
            if (($current_time - $cache_time) < 3600) {
                $log_message = "[" . date('Y-m-d H:i:s') . "] Загружаем из кеша\n";
                file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
                
                $cached_data = json_decode(file_get_contents($cache_file), true);
                if ($cached_data && isset($cached_data['results'])) {
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Из кеша загружено: " . count($cached_data['results']) . " предметов\n";
                    file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
                    return $cached_data['results'];
                }
            }
        }
        
        $log_message = "[" . date('Y-m-d H:i:s') . "] Загружаем из API\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        
        try {
            $url = 'http://www.dnd5eapi.co/api/magic-items';
            
            // Используем file_get_contents для HTTP (работает без SSL)
            $response = @file_get_contents($url);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && isset($data['results'])) {
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Из API загружено: " . count($data['results']) . " предметов\n";
                    file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
                    
                    // Сохраняем в кеш
                    if (!is_dir(dirname($cache_file))) {
                        mkdir(dirname($cache_file), 0755, true);
                    }
                    file_put_contents($cache_file, json_encode($data));
                    
                    return $data['results'];
                } else {
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Ошибка: неверный формат ответа API\n";
                    file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
                }
            } else {
                $log_message = "[" . date('Y-m-d H:i:s') . "] Ошибка: пустой ответ от API\n";
                file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
            }
        } catch (Exception $e) {
            $log_message = "[" . date('Y-m-d H:i:s') . "] Исключение в getMagicItemsList: " . $e->getMessage() . "\n";
            file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        }
        
        $log_message = "[" . date('Y-m-d H:i:s') . "] getMagicItemsList возвращает пустой массив\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        
        return [];
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
            'rarity' => $potion_data['rarity'] ?? ['name' => 'Unknown'],
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
            error_log("Неверный URL: $url");
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
            error_log("Не удалось подключиться к $host:$port - $errstr ($errno)");
            return null;
        }
        
        // Формируем HTTP запрос
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
        $used_method = null;
        
        foreach ($connection_methods as $method) {
            $fp = @fsockopen($method[0], $method[1], $errno, $errstr, 10);
            if ($fp) {
                error_log("Успешное подключение к $method[0]:$method[1]");
                $used_method = $method;
                break;
            }
            $last_error = "$errstr ($errno)";
        }
        
        if (!$fp) {
            // Логируем ошибку для отладки
            error_log("Не удалось подключиться к $host:443 - $last_error");
            
            // Попробуем альтернативный метод через file_get_contents
            return $this->makeHttpsRequestAlternative($host, $path);
        }
        
        // Если подключение есть, но SSL недоступен, используем альтернативный метод
        if ($used_method && strpos($used_method[0], 'ssl://') === false && strpos($used_method[0], 'tls://') === false) {
            error_log("SSL недоступен, используем альтернативный метод");
            fclose($fp);
            return $this->makeHttpsRequestAlternative($host, $path);
        }
        
        // Устанавливаем таймаут
        stream_set_timeout($fp, 30);
        
        // Формируем HTTPS запрос (используем HTTP/1.1 для HTTPS)
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
     * Альтернативный метод HTTPS запроса через file_get_contents
     */
    private function makeHttpsRequestAlternative($host, $path) {
        $url = "https://$host$path";
        error_log("Пробуем альтернативный метод для $url");
        
        // Создаем контекст для HTTPS
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: DnD-Copilot/1.0',
                    'Accept: application/json',
                    'Connection: close'
                ],
                'timeout' => 30,
                'follow_location' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        // Пытаемся получить данные
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            error_log("file_get_contents не удался для $url");
            return null;
        }
        
        // Возвращаем сырой ответ (как и основной метод)
        error_log("Альтернативный метод успешен для $url, длина ответа: " . strlen($response));
        return $response;
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
     * Перевод зелий на русский язык
     */
    private function translatePotions($potions, $target_language) {
        if ($target_language !== 'ru') {
            return $potions; // Переводим только на русский
        }
        
        $log_message = "[" . date('Y-m-d H:i:s') . "] Начинаем перевод " . count($potions) . " зелий на русский язык\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        
        $translated_potions = [];
        
        foreach ($potions as $potion) {
            try {
                $translated_potion = $this->ai_service->translatePotion($potion, $target_language);
                
                // Проверяем, что перевод прошел успешно
                if (is_array($translated_potion) && !isset($translated_potion['error'])) {
                    // Сохраняем оригинальное название для фильтрации
                    $translated_potion['original_name'] = $potion['name'];
                    
                    // Добавляем локализованные названия редкости и типа
                    $translated_potion['rarity_localized'] = $this->language_service->getRarityName($translated_potion['rarity']['name'] ?? 'common', $target_language);
                    $translated_potion['type_localized'] = $this->language_service->getPotionTypeName($translated_potion['type'], $target_language);
                    
                    $translated_potions[] = $translated_potion;
                    
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Успешно переведено зелье: " . $potion['name'] . "\n";
                    file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
                } else {
                    // Если перевод не удался, используем оригинальное зелье с предупреждением
                    logMessage('WARNING', 'Не удалось перевести зелье: ' . $potion['name']);
                    $potion['translation_error'] = 'Перевод недоступен';
                    $potion['original_name'] = $potion['name']; // Сохраняем оригинальное название
                    $translated_potions[] = $potion;
                }
            } catch (Exception $e) {
                logMessage('ERROR', 'Ошибка перевода зелья ' . $potion['name'] . ': ' . $e->getMessage());
                $potion['translation_error'] = 'Ошибка перевода: ' . $e->getMessage();
                $potion['original_name'] = $potion['name']; // Сохраняем оригинальное название
                $translated_potions[] = $potion;
            }
        }
        
        $log_message = "[" . date('Y-m-d H:i:s') . "] Перевод завершен. Переведено зелий: " . count($translated_potions) . "\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
        
        return $translated_potions;
    }
    
    /**
     * Получение информации о языках
     */
    public function getLanguageInfo() {
        return $this->language_service->getLanguageInfo();
    }
    
    /**
     * Получение локализованных редкостей
     */
    public function getLocalizedRarities($language = 'ru') {
        $rarities = ['Uncommon', 'Rare', 'Very Rare', 'Legendary'];
        $localized = [];
        
        foreach ($rarities as $rarity) {
            $localized[] = [
                'value' => $rarity,
                'name' => $this->language_service->getRarityName($rarity, $language)
            ];
        }
        
        return $localized;
    }
    
    /**
     * Получение локализованных типов эффектов зелий
     */
    public function getLocalizedTypes($language = 'ru') {
        $types = ['Восстановление', 'Усиление', 'Защита', 'Иллюзия', 'Трансмутация', 'Некромантия', 'Прорицание', 'Эвокация', 'Универсальное'];
        $localized = [];
        
        foreach ($types as $type) {
            $localized[] = [
                'value' => $type,
                'name' => $type // Названия уже на русском
            ];
        }
        
        return $localized;
    }
    
    /**
     * Получение локализованных типов эффектов зелий
     */
    public function getLocalizedEffectTypes($language = 'ru') {
        $types = ['Восстановление', 'Усиление', 'Защита', 'Иллюзия', 'Трансмутация', 'Некромантия', 'Прорицание', 'Эвокация', 'Универсальное'];
        $localized = [];
        
        foreach ($types as $type) {
            $localized[] = [
                'value' => $type,
                'name' => $this->language_service->getPotionTypeName($type, $language)
            ];
        }
        
        return $localized;
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
        
        // Логируем параметры для отладки
        $log_message = "[" . date('Y-m-d H:i:s') . "] GET запрос к generate-potions.php: action=$action, params=" . json_encode($_GET) . "\n";
        file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
    
    try {
        $generator = new PotionGenerator();
        
        switch ($action) {
            case 'rarities':
                $language = $_GET['language'] ?? 'ru';
                $result = $generator->getLocalizedRarities($language);
                break;
                
            case 'types':
                $language = $_GET['language'] ?? 'ru';
                $result = $generator->getLocalizedTypes($language);
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
                
            case 'languages':
                $result = ['current' => 'ru', 'supported' => ['ru'], 'default' => 'ru'];
                break;
                
            case 'random':
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Вызываем generatePotions с параметрами: " . json_encode($_GET) . "\n";
                    file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
                    
                $result = $generator->generatePotions($_GET);
                    
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Результат generatePotions: " . json_encode($result) . "\n";
                    file_put_contents(__DIR__ . '/../../data/logs/app.log', $log_message, FILE_APPEND | LOCK_EX);
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
