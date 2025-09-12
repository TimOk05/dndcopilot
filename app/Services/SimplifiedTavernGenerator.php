<?php

/**
 * Упрощенный генератор таверн без выбора биомов
 * Использует специализированный AI сервис и упрощенную базу данных
 */
class SimplifiedTavernGenerator {
    private $taverns_db;
    private $ai_service;
    private $cache_dir;
    private $usedElements = [];
    private $repetitionTracker;
    
    public function __construct() {
        $this->cache_dir = __DIR__ . '/../../data/cache/taverns/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        $this->loadTavernsDatabase();
        $this->initializeRepetitionTracker();
        
        logMessage('INFO', 'SimplifiedTavernGenerator: Инициализирован');
    }
    
    /**
     * Загрузка базы данных таверн
     */
    private function loadTavernsDatabase() {
        $db_file = __DIR__ . '/../../data/pdf/taverns_db_v5_simplified.json';
        
        if (!file_exists($db_file)) {
            throw new Exception('База данных таверн не найдена');
        }
        
        $json_content = file_get_contents($db_file);
        $this->taverns_db = json_decode($json_content, true);
        
        if (!$this->taverns_db) {
            throw new Exception('Ошибка загрузки базы данных таверн');
        }
        
        logMessage('INFO', 'SimplifiedTavernGenerator: База данных таверн загружена');
    }
    
    /**
     * Инициализация трекера повторений
     */
    private function initializeRepetitionTracker() {
        $this->repetitionTracker = [
            'names' => [],
            'locations' => [],
            'owners' => [],
            'staff' => [],
            'events' => [],
            'menu_items' => [],
            'rooms' => [],
            'games' => []
        ];
    }
    
    /**
     * Генерация таверны с упрощенной логикой
     */
    public function generateTavern($params) {
        try {
            $use_ai = isset($params['use_ai']) ? ($params['use_ai'] === 'on') : true;
            $count = (int)($params['count'] ?? 1);
            
            logMessage('INFO', "SimplifiedTavernGenerator: Генерация таверн. Количество: $count");
            
            $taverns = [];
            for ($i = 0; $i < $count; $i++) {
                $tavern = $this->generateSingleTavern($use_ai);
                if ($tavern) {
                    $taverns[] = $tavern;
                }
            }
            
            if (empty($taverns)) {
                throw new Exception('Не удалось сгенерировать таверны');
            }
            
            return [
                'success' => true,
                'taverns' => $taverns,
                'count' => count($taverns),
                'api_info' => [
                    'ai_used' => $use_ai,
                    'data_source' => 'Simplified local database',
                    'cache_info' => 'Enhanced caching system active'
                ]
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'SimplifiedTavernGenerator: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'details' => 'Generation failed due to data processing error'
            ];
        }
    }
    
    /**
     * Генерация одной таверны
     */
    private function generateSingleTavern($use_ai) {
        try {
            // Генерируем основные элементы
            $name = $this->generateTavernName();
            $location = $this->selectUniqueElement($this->taverns_db['core']['locations'], 'locations');
            $owner = $this->selectUniqueElement($this->taverns_db['core']['owners'], 'owners');
            $staff = $this->selectUniqueElement($this->taverns_db['core']['staff_archetypes'], 'staff');
            $event = $this->selectUniqueElement($this->taverns_db['core']['events'], 'events');
            $menu_item = $this->selectUniqueElement($this->taverns_db['core']['menu_items'], 'menu_items');
            $room = $this->selectUniqueElement($this->taverns_db['core']['rooms'], 'rooms');
            $game = $this->selectUniqueElement($this->taverns_db['core']['games'], 'games');
            
            // Создаем базовую структуру таверны
            $tavern = [
                'name' => $name,
                'location' => [
                    'text_ru' => $location['text_ru']
                ],
                'owner' => [
                    'name_ru' => $owner['name_ru'],
                    'race' => $owner['race'],
                    'personality' => $owner['personality'],
                    'background' => $owner['background']
                ],
                'staff' => [
                    [
                        'role' => $staff['role'],
                        'name_ru' => $staff['name_ru'],
                        'personality' => $staff['personality'],
                        'race' => $staff['race'] ?? 'человек',
                        'traits' => [$staff['personality']]
                    ]
                ],
                'events' => [
                    [
                        'name_ru' => $event['name_ru'],
                        'description_ru' => $event['description_ru'],
                        'type' => $event['name_ru']
                    ]
                ],
                'menu' => [
                    'drinks' => [
                        [
                            'name_ru' => $menu_item['name_ru'],
                            'description_ru' => $menu_item['description_ru'],
                            'price' => $menu_item['price'],
                            'formatted_price' => $menu_item['price']
                        ]
                    ]
                ],
                'rooms' => [
                    [
                        'type' => $room['name_ru'],
                        'description_ru' => $room['description_ru'],
                        'capacity' => $room['capacity'],
                        'price' => $room['price'],
                        'beds' => 1,
                        'cleanliness' => 'хорошая',
                        'notes_ru' => $room['description_ru']
                    ]
                ],
                'games' => [
                    'main' => [
                        'name_ru' => $game['name_ru'],
                        'description_ru' => $game['description_ru'],
                        'style' => 'традиционная'
                    ]
                ]
            ];
            
            // Генерируем AI описание, если включено
            if ($use_ai) {
                try {
                    require_once __DIR__ . '/TavernAiService.php';
                    $this->ai_service = new TavernAiService();
                    $description = $this->ai_service->generateTavernDescription($tavern);
                    if ($description && !isset($description['error'])) {
                        $tavern['ai_description'] = $description;
                    }
                } catch (Exception $e) {
                    logMessage('WARNING', 'AI генерация описания не удалась: ' . $e->getMessage());
                    $tavern['ai_description_error'] = $e->getMessage();
                }
            }
            
            // Добавляем метаданные
            $tavern['metadata'] = [
                'generated_at' => date('Y-m-d H:i:s'),
                'version' => $this->taverns_db['version'],
                'ai_used' => $use_ai
            ];
            
            return $tavern;
            
        } catch (Exception $e) {
            logMessage('ERROR', 'SimplifiedTavernGenerator: Ошибка генерации таверны - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Генерация названия таверны
     */
    private function generateTavernName() {
        $prefixes = $this->taverns_db['core']['name_patterns']['prefixes'];
        $nouns = $this->taverns_db['core']['name_patterns']['nouns'];
        $suffixes = $this->taverns_db['core']['name_patterns']['suffixes'];
        
        // Выбираем случайный паттерн
        $pattern = rand(1, 3);
        
        switch ($pattern) {
            case 1:
                // Префикс + Существительное
                $prefix = $prefixes[array_rand($prefixes)];
                $noun = $nouns[array_rand($nouns)];
                return "{$prefix} {$noun}";
                
            case 2:
                // Существительное + Суффикс
                $noun = $nouns[array_rand($nouns)];
                $suffix = $suffixes[array_rand($suffixes)];
                return "{$noun} {$suffix}";
                
            case 3:
                // Префикс + Существительное + Суффикс
                $prefix = $prefixes[array_rand($prefixes)];
                $noun = $nouns[array_rand($nouns)];
                $suffix = $suffixes[array_rand($suffixes)];
                return "{$prefix} {$noun} {$suffix}";
                
            default:
                return "Таверна " . $nouns[array_rand($nouns)];
        }
    }
    
    /**
     * Выбор уникального элемента с избежанием повторений
     */
    private function selectUniqueElement($items, $type, $exclude = []) {
        if (empty($items)) {
            return null;
        }
        
        $max_attempts = 10;
        $attempt = 0;
        
        do {
            $item = $items[array_rand($items)];
            $item_key = md5(json_encode($item));
            
            // Проверяем, не использовался ли уже этот элемент
            if (!isset($this->repetitionTracker[$type]) || !in_array($item_key, $this->repetitionTracker[$type])) {
                // Добавляем в трекер
                if (!isset($this->repetitionTracker[$type])) {
                    $this->repetitionTracker[$type] = [];
                }
                $this->repetitionTracker[$type][] = $item_key;
                
                // Ограничиваем размер трекера
                if (count($this->repetitionTracker[$type]) > 50) {
                    array_shift($this->repetitionTracker[$type]);
                }
                
                return $item;
            }
            
            $attempt++;
        } while ($attempt < $max_attempts);
        
        // Если не удалось найти уникальный элемент, возвращаем случайный
        return $items[array_rand($items)];
    }
    
    /**
     * Получение статистики использования элементов
     */
    public function getUsageStats() {
        $stats = [];
        foreach ($this->repetitionTracker as $type => $items) {
            $stats[$type] = count($items);
        }
        return $stats;
    }
    
    /**
     * Сброс трекера повторений
     */
    public function resetRepetitionTracker() {
        $this->initializeRepetitionTracker();
        logMessage('INFO', 'SimplifiedTavernGenerator: Трекер повторений сброшен');
    }
}
?>
