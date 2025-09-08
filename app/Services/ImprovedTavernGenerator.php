<?php

/**
 * Улучшенный генератор таверн с системой избежания повторений
 * Использует специализированный AI сервис и расширенную базу данных
 */
class ImprovedTavernGenerator {
    private $taverns_db;
    private $ai_service;
    private $cache_dir;
    private $usedElements = [];
    private $repetitionTracker;
    
    public function __construct() {
        require_once __DIR__ . '/TavernAiService.php';
        $this->ai_service = new TavernAiService();
        $this->cache_dir = __DIR__ . '/../../data/cache/taverns/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        $this->loadTavernsDatabase();
        $this->initializeRepetitionTracker();
        
        logMessage('INFO', 'ImprovedTavernGenerator: Инициализирован');
    }
    
    /**
     * Загрузка базы данных таверн
     */
    private function loadTavernsDatabase() {
        $db_file = __DIR__ . '/../../data/pdf/taverns_db_v3_1.json';
        
        if (!file_exists($db_file)) {
            throw new Exception('База данных таверн не найдена');
        }
        
        $json_content = file_get_contents($db_file);
        $this->taverns_db = json_decode($json_content, true);
        
        if (!$this->taverns_db) {
            throw new Exception('Ошибка загрузки базы данных таверн');
        }
        
        logMessage('INFO', 'ImprovedTavernGenerator: База данных таверн загружена');
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
     * Генерация таверны с улучшенной логикой
     */
    public function generateTavern($params) {
        try {
            $biome = $params['biome'] ?? '';
            $use_ai = isset($params['use_ai']) ? ($params['use_ai'] === 'on') : true;
            $count = (int)($params['count'] ?? 1);
            
            // Если биом не указан, выбираем случайный
            if (empty($biome)) {
                $valid_biomes = array_keys($this->taverns_db['specials']);
                $biome = $valid_biomes[array_rand($valid_biomes)];
            } else {
                $valid_biomes = array_keys($this->taverns_db['specials']);
                if (!in_array($biome, $valid_biomes)) {
                    throw new Exception('Неверный биом. Доступные: ' . implode(', ', $valid_biomes));
                }
            }
            
            logMessage('INFO', "ImprovedTavernGenerator: Генерация таверн. Биом: $biome, Количество: $count");
            
            $taverns = [];
            for ($i = 0; $i < $count; $i++) {
                $tavern = $this->generateSingleTavern($biome, $use_ai);
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
                'biome' => $biome,
                'count' => count($taverns),
                'ai_used' => $use_ai,
                'generation_info' => [
                    'used_elements' => $this->getUsedElementsStats(),
                    'avoided_repetitions' => $this->getRepetitionStats()
                ]
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedTavernGenerator: Ошибка генерации: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Генерация одной таверны
     */
    private function generateSingleTavern($biome, $use_ai) {
        try {
            // Создаем черновик таверны с избежанием повторений
            $draft = $this->createUniqueTavernDraft($biome);
            
            // Генерируем описание через специализированный AI сервис
            if ($use_ai) {
                $description = $this->ai_service->generateTavernDescription($draft, true);
                if (isset($description['error'])) {
                    logMessage('WARNING', "AI генерация описания не удалась: " . $description['message']);
                    $draft['description_error'] = $description;
                } else {
                    $draft['description'] = $description;
                }
            } else {
                $draft['description'] = null;
            }
            
            return $draft;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedTavernGenerator: Ошибка генерации таверны: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создание уникального черновика таверны
     */
    private function createUniqueTavernDraft($biome) {
        $core = $this->taverns_db['core'];
        $special = $this->taverns_db['specials'][$biome];
        
        // Генерируем уникальное название
        $name = $this->generateUniqueTavernName($core['name_patterns']);
        
        // Выбираем уникальную локацию
        $all_locations = array_merge($core['locations'], $special['locations'] ?? []);
        $location = $this->selectUniqueElement($all_locations, 'locations', $biome);
        
        // Выбираем уникального владельца
        $owner = $this->selectUniqueElement($core['owners'], 'owners', $biome);
        
        // Выбираем уникальный персонал
        $all_staff = array_merge($core['staff_archetypes'], $special['staff_archetypes'] ?? []);
        $staff = $this->selectUniqueStaff($all_staff, $biome);
        
        // Выбираем уникальную атмосферу
        $all_ambience = array_merge($core['ambience'], $special['ambience'] ?? []);
        $ambience = $this->selectUniqueElement($all_ambience, 'ambience', $biome);
        
        // Создаем уникальное меню
        $menu = $this->createUniqueMenu($core['menu'], $special['menu_additions'] ?? [], $biome);
        
        // Выбираем уникальные комнаты
        $rooms = $this->selectUniqueRooms($core['rooms']);
        
        // Выбираем уникальные события
        $all_events = array_merge($core['events'], $special['events'] ?? []);
        $events = $this->selectUniqueEvents($all_events, $biome);
        
        // Выбираем уникальные игры
        $games = $this->selectUniqueGames($core['games']);
        
        // Добавляем уникальные особенности если доступны
        $unique_features = $this->generateUniqueFeatures($biome);
        
        return [
            'name' => $name,
            'location' => $location,
            'owner' => $owner,
            'staff' => $staff,
            'ambience' => $ambience,
            'menu' => $menu,
            'rooms' => $rooms,
            'events' => $events,
            'games' => $games,
            'unique_features' => $unique_features,
            'biome' => $biome,
            'generation_timestamp' => time()
        ];
    }
    
    /**
     * Генерация уникального названия таверны
     */
    private function generateUniqueTavernName($name_patterns) {
        $max_attempts = 50;
        $attempt = 0;
        
        do {
            $prefix = $name_patterns['prefixes'][array_rand($name_patterns['prefixes'])];
            $noun = $name_patterns['nouns'][array_rand($name_patterns['nouns'])];
            $variant = $name_patterns['variants'][array_rand($name_patterns['variants'])];
            
            $name = str_replace(['{prefix}', '{noun}'], [$prefix, $noun], $variant);
            $attempt++;
            
        } while (in_array($name, $this->repetitionTracker['names']) && $attempt < $max_attempts);
        
        $this->repetitionTracker['names'][] = $name;
        
        // Ограничиваем размер массива для производительности
        if (count($this->repetitionTracker['names']) > 100) {
            array_shift($this->repetitionTracker['names']);
        }
        
        return $name;
    }
    
    /**
     * Выбор уникального элемента с избежанием повторений
     */
    private function selectUniqueElement($items, $type, $biome, $exclude = []) {
        if (empty($items)) {
            return null;
        }
        
        $max_attempts = 20;
        $attempt = 0;
        
        do {
            $selected = $this->selectByRarity($items, $biome, $exclude);
            $item_key = $this->getItemKey($selected);
            $attempt++;
            
        } while (in_array($item_key, $this->repetitionTracker[$type]) && $attempt < $max_attempts);
        
        if ($selected) {
            $this->repetitionTracker[$type][] = $item_key;
            
            // Ограничиваем размер массива
            if (count($this->repetitionTracker[$type]) > 50) {
                array_shift($this->repetitionTracker[$type]);
            }
        }
        
        return $selected;
    }
    
    /**
     * Выбор уникального персонала
     */
    private function selectUniqueStaff($staff_archetypes, $biome) {
        $staff_count = rand(2, 4);
        $selected_staff = [];
        $excluded_staff = [];
        
        for ($i = 0; $i < $staff_count; $i++) {
            $staff_member = $this->selectUniqueElement($staff_archetypes, 'staff', $biome, $excluded_staff);
            if ($staff_member) {
                $selected_staff[] = $staff_member;
                $excluded_staff[] = $this->getItemKey($staff_member);
            }
        }
        
        return $selected_staff;
    }
    
    /**
     * Создание уникального меню
     */
    private function createUniqueMenu($core_menu, $special_additions, $biome) {
        // Объединяем меню, избегая дубликатов
        $menu = [
            'drinks' => $this->mergeMenuItems($core_menu['drinks'], $special_additions['drinks'] ?? []),
            'meals' => $this->mergeMenuItems($core_menu['meals'], $special_additions['meals'] ?? []),
            'sides' => $this->mergeMenuItems($core_menu['sides'], $special_additions['sides'] ?? [])
        ];
        
        // Выбираем уникальные элементы для меню
        $selected_menu = [
            'drinks' => $this->selectUniqueMenuItems($menu['drinks'], 3, 6, $biome, 'drinks'),
            'meals' => $this->selectUniqueMenuItems($menu['meals'], 2, 4, $biome, 'meals'),
            'sides' => $this->selectUniqueMenuItems($menu['sides'], 1, 3, $biome, 'sides')
        ];
        
        return $selected_menu;
    }
    
    /**
     * Выбор уникальных элементов меню
     */
    private function selectUniqueMenuItems($items, $min, $max, $biome, $menu_type) {
        $count = rand($min, min($max, count($items)));
        $selected = [];
        $excluded_items = [];
        
        for ($i = 0; $i < $count; $i++) {
            $item = $this->selectByRarity($items, $biome, $excluded_items);
            if ($item) {
                // Проверяем на повторения в глобальном трекере
                $item_key = $this->getItemKey($item);
                if (!in_array($item_key, $this->repetitionTracker['menu_items'])) {
                    $item['formatted_price'] = $this->formatPrice($item['price'] ?? null);
                    $selected[] = $item;
                    $excluded_items[] = $item_key;
                    $this->repetitionTracker['menu_items'][] = $item_key;
                }
            }
        }
        
        return $selected;
    }
    
    /**
     * Выбор уникальных комнат
     */
    private function selectUniqueRooms($rooms) {
        $room_count = rand(1, 2);
        $selected_rooms = [];
        $excluded_rooms = [];
        
        for ($i = 0; $i < $room_count; $i++) {
            $room = $this->selectByRarity($rooms, 'city', $excluded_rooms);
            if ($room) {
                $room_key = $this->getItemKey($room);
                if (!in_array($room_key, $this->repetitionTracker['rooms'])) {
                    $selected_rooms[] = $room;
                    $excluded_rooms[] = $room_key;
                    $this->repetitionTracker['rooms'][] = $room_key;
                }
            }
        }
        
        return $selected_rooms;
    }
    
    /**
     * Выбор уникальных событий
     */
    private function selectUniqueEvents($events, $biome) {
        $event_count = rand(1, 3);
        $selected_events = [];
        $excluded_events = [];
        
        for ($i = 0; $i < $event_count; $i++) {
            $event = $this->selectByRarity($events, $biome, $excluded_events);
            if ($event) {
                $event_key = $this->getItemKey($event);
                if (!in_array($event_key, $this->repetitionTracker['events'])) {
                    $selected_events[] = $event;
                    $excluded_events[] = $event_key;
                    $this->repetitionTracker['events'][] = $event_key;
                }
            }
        }
        
        return $selected_events;
    }
    
    /**
     * Выбор уникальных игр
     */
    private function selectUniqueGames($games) {
        $selected_games = [];
        
        foreach ($games as $category => $game_list) {
            if (!empty($game_list)) {
                $game = $this->selectByRarity($game_list, 'city');
                if ($game) {
                    $game_key = $this->getItemKey($game);
                    if (!in_array($game_key, $this->repetitionTracker['games'])) {
                        $selected_games[$category] = $game;
                        $this->repetitionTracker['games'][] = $game_key;
                    }
                }
            }
        }
        
        return $selected_games;
    }
    
    /**
     * Генерация уникальных особенностей
     */
    private function generateUniqueFeatures($biome) {
        $features = [];
        
        // Добавляем случайные уникальные особенности
        $feature_types = ['special_item', 'historical_event', 'mysterious_occurrence'];
        $feature_count = rand(0, 2);
        
        for ($i = 0; $i < $feature_count; $i++) {
            $feature_type = $feature_types[array_rand($feature_types)];
            $features[] = $this->generateRandomFeature($feature_type, $biome);
        }
        
        return $features;
    }
    
    /**
     * Генерация случайной особенности
     */
    private function generateRandomFeature($type, $biome) {
        $features = [
            'special_item' => [
                'name' => 'Особый предмет',
                'description' => 'В таверне есть уникальный предмет с интересной историей'
            ],
            'historical_event' => [
                'name' => 'Историческое событие',
                'description' => 'Здесь произошло важное историческое событие'
            ],
            'mysterious_occurrence' => [
                'name' => 'Загадочное происшествие',
                'description' => 'В таверне происходят странные и необъяснимые события'
            ]
        ];
        
        return $features[$type] ?? $features['special_item'];
    }
    
    /**
     * Выбор элемента по редкости (унаследован из оригинального класса)
     */
    private function selectByRarity($items, $biome, $exclude = []) {
        if (empty($items)) {
            return null;
        }
        
        $weights = $this->taverns_db['weights'];
        $biome_tags = $this->taverns_db['specials'][$biome]['tags'] ?? [];
        
        // Фильтруем исключенные элементы
        $available_items = array_filter($items, function($item) use ($exclude) {
            $item_key = $this->getItemKey($item);
            return !in_array($item_key, $exclude);
        });
        
        if (empty($available_items)) {
            return null;
        }
        
        $weighted_items = [];
        foreach ($available_items as $item) {
            $weight = $weights['rarity'][$item['rarity']] ?? 1;
            
            // Применяем tag_boost если есть совпадающие теги
            if (isset($item['tags']) && !empty($biome_tags)) {
                $common_tags = array_intersect($item['tags'], $biome_tags);
                if (!empty($common_tags)) {
                    $weight *= (1 + $weights['tag_boost'] * count($common_tags));
                }
            }
            
            $weighted_items[] = ['item' => $item, 'weight' => $weight];
        }
        
        return $this->weightedRandomSelect($weighted_items);
    }
    
    /**
     * Получение уникального ключа для элемента
     */
    private function getItemKey($item) {
        if (isset($item['name_ru'])) {
            return $item['name_ru'];
        } elseif (isset($item['text_ru'])) {
            return $item['text_ru'];
        } elseif (isset($item['type'])) {
            return $item['type'];
        } elseif (isset($item['role'])) {
            return $item['role'];
        }
        return md5(serialize($item));
    }
    
    /**
     * Взвешенный случайный выбор
     */
    private function weightedRandomSelect($weighted_items) {
        $total_weight = array_sum(array_column($weighted_items, 'weight'));
        $random = mt_rand() / mt_getrandmax() * $total_weight;
        
        $current_weight = 0;
        foreach ($weighted_items as $weighted_item) {
            $current_weight += $weighted_item['weight'];
            if ($random <= $current_weight) {
                return $weighted_item['item'];
            }
        }
        
        return $weighted_items[0]['item']; // Fallback
    }
    
    /**
     * Объединение элементов меню без дубликатов
     */
    private function mergeMenuItems($core_items, $special_items) {
        $merged = $core_items;
        $existing_names = array_column($core_items, 'name_ru');
        
        foreach ($special_items as $item) {
            if (!in_array($item['name_ru'], $existing_names)) {
                $merged[] = $item;
                $existing_names[] = $item['name_ru'];
            }
        }
        
        return $merged;
    }
    
    /**
     * Форматирование цены
     */
    private function formatPrice($price_data) {
        if (!isset($price_data)) {
            return 'Цена не указана';
        }
        
        $price_parts = [];
        
        if (isset($price_data['cp']) && $price_data['cp'] > 0) {
            $price_parts[] = $price_data['cp'] . ' медных';
        }
        if (isset($price_data['sp']) && $price_data['sp'] > 0) {
            $price_parts[] = $price_data['sp'] . ' серебряных';
        }
        if (isset($price_data['gp']) && $price_data['gp'] > 0) {
            $price_parts[] = $price_data['gp'] . ' золотых';
        }
        
        return empty($price_parts) ? 'Цена не указана' : implode(', ', $price_parts);
    }
    
    /**
     * Получение статистики использованных элементов
     */
    private function getUsedElementsStats() {
        $stats = [];
        foreach ($this->repetitionTracker as $type => $elements) {
            $stats[$type] = count($elements);
        }
        return $stats;
    }
    
    /**
     * Получение статистики избежания повторений
     */
    private function getRepetitionStats() {
        $total_elements = array_sum(array_map('count', $this->repetitionTracker));
        return [
            'total_unique_elements_used' => $total_elements,
            'tracker_status' => 'active',
            'last_reset' => 'never'
        ];
    }
    
    /**
     * Очистка трекера повторений
     */
    public function clearRepetitionTracker() {
        $this->initializeRepetitionTracker();
        $this->ai_service->clearUsedPrompts();
        logMessage('INFO', 'ImprovedTavernGenerator: Трекер повторений очищен');
    }
    
    /**
     * Получение статистики генерации
     */
    public function getGenerationStats() {
        return [
            'used_elements' => $this->getUsedElementsStats(),
            'repetition_stats' => $this->getRepetitionStats(),
            'ai_stats' => $this->ai_service->getPromptStats()
        ];
    }
}
