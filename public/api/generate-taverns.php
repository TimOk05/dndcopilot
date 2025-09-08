<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Services/ai-service.php';
require_once __DIR__ . '/../../app/Services/ImprovedTavernGenerator.php';

class TavernGenerator {
    private $taverns_db;
    private $ai_service;
    private $cache_dir;
    
    public function __construct() {
        $this->ai_service = new AiService();
        $this->cache_dir = __DIR__ . '/../../data/cache/taverns/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        $this->loadTavernsDatabase();
        
        logMessage('INFO', 'TavernGenerator: Инициализирован');
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
        
        logMessage('INFO', 'TavernGenerator: База данных таверн v3.1 загружена');
    }
    
    /**
     * Генерация таверны
     */
    public function generateTavern($params) {
        try {
            $biome = $params['biome'] ?? '';
            $use_ai = isset($params['use_ai']) ? ($params['use_ai'] === 'on') : true;
            
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
            
            logMessage('INFO', "TavernGenerator: Генерация таверны. Биом: $biome");
            
            // Генерируем одну таверну
            $tavern = $this->generateSingleTavern($biome, $use_ai);
            if (!$tavern) {
                throw new Exception('Не удалось сгенерировать таверну');
            }
            
            return [
                'success' => true,
                'tavern' => $tavern,
                'biome' => $biome,
                'ai_used' => $use_ai
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', "TavernGenerator: Ошибка генерации: " . $e->getMessage());
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
            // Создаем черновик таверны
            $draft = $this->createTavernDraft($biome);
            
            // Генерируем описание через AI если включено
            if ($use_ai) {
                $description = $this->generateTavernDescription($draft);
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
            logMessage('ERROR', "TavernGenerator: Ошибка генерации таверны: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создание черновика таверны
     */
    private function createTavernDraft($biome) {
        $core = $this->taverns_db['core'];
        $special = $this->taverns_db['specials'][$biome];
        
        // Генерируем название
        $name = $this->generateTavernName($core['name_patterns']);
        
        // Выбираем локацию
        $all_locations = array_merge($core['locations'], $special['locations'] ?? []);
        $location = $this->selectByRarity($all_locations, $biome);
        
        // Выбираем владельца
        $owner = $this->selectByRarity($core['owners'], $biome);
        
        // Выбираем персонал
        $all_staff = array_merge($core['staff_archetypes'], $special['staff_archetypes'] ?? []);
        $staff = $this->selectStaff($all_staff, $biome);
        
        // Выбираем атмосферу
        $all_ambience = array_merge($core['ambience'], $special['ambience'] ?? []);
        $ambience = $this->selectByRarity($all_ambience, $biome);
        
        // Создаем меню
        $menu = $this->createMenu($core['menu'], $special['menu_additions'] ?? [], $biome);
        
        // Выбираем комнаты
        $rooms = $this->selectRooms($core['rooms']);
        
        // Выбираем события
        $all_events = array_merge($core['events'], $special['events'] ?? []);
        $events = $this->selectEvents($all_events, $biome);
        
        // Выбираем игры
        $games = $this->selectGames($core['games']);
        
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
            'biome' => $biome
        ];
    }
    
    /**
     * Генерация названия таверны
     */
    private function generateTavernName($name_patterns) {
        $prefix = $name_patterns['prefixes'][array_rand($name_patterns['prefixes'])];
        $noun = $name_patterns['nouns'][array_rand($name_patterns['nouns'])];
        $variant = $name_patterns['variants'][array_rand($name_patterns['variants'])];
        
        return str_replace(['{prefix}', '{noun}'], [$prefix, $noun], $variant);
    }
    
    /**
     * Выбор элемента по редкости с учетом биома
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
     * Выбор персонала
     */
    private function selectStaff($staff_archetypes, $biome) {
        $staff_count = rand(2, 4);
        $selected_staff = [];
        $excluded_staff = [];
        
        for ($i = 0; $i < $staff_count; $i++) {
            $staff_member = $this->selectByRarity($staff_archetypes, $biome, $excluded_staff);
            if ($staff_member) {
                $selected_staff[] = $staff_member;
                $excluded_staff[] = $this->getItemKey($staff_member);
            }
        }
        
        return $selected_staff;
    }
    
    /**
     * Создание меню
     */
    private function createMenu($core_menu, $special_additions, $biome) {
        // Объединяем меню, избегая дубликатов по названию
        $menu = [
            'drinks' => $this->mergeMenuItems($core_menu['drinks'], $special_additions['drinks'] ?? []),
            'meals' => $this->mergeMenuItems($core_menu['meals'], $special_additions['meals'] ?? []),
            'sides' => $this->mergeMenuItems($core_menu['sides'], $special_additions['sides'] ?? [])
        ];
        
        // Выбираем случайные элементы для меню
        $selected_menu = [
            'drinks' => $this->selectMenuItems($menu['drinks'], 3, 6, $biome),
            'meals' => $this->selectMenuItems($menu['meals'], 2, 4, $biome),
            'sides' => $this->selectMenuItems($menu['sides'], 1, 3, $biome)
        ];
        
        return $selected_menu;
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
     * Выбор элементов меню
     */
    private function selectMenuItems($items, $min, $max, $biome = 'city') {
        $count = rand($min, min($max, count($items)));
        $selected = [];
        $excluded_items = [];
        
        for ($i = 0; $i < $count; $i++) {
            $item = $this->selectByRarity($items, $biome, $excluded_items);
            if ($item) {
                // Форматируем цену из JSON
                $item['formatted_price'] = $this->formatPrice($item['price'] ?? null);
                $selected[] = $item;
                $excluded_items[] = $this->getItemKey($item);
            }
        }
        
        return $selected;
    }
    
    /**
     * Форматирование цены из JSON
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
     * Выбор комнат
     */
    private function selectRooms($rooms) {
        $room_count = rand(1, 2);
        $selected_rooms = [];
        $excluded_rooms = [];
        
        for ($i = 0; $i < $room_count; $i++) {
            $room = $this->selectByRarity($rooms, 'city', $excluded_rooms);
            if ($room) {
                $selected_rooms[] = $room;
                $excluded_rooms[] = $this->getItemKey($room);
            }
        }
        
        return $selected_rooms;
    }
    
    /**
     * Выбор событий
     */
    private function selectEvents($events, $biome) {
        $event_count = rand(1, 3);
        $selected_events = [];
        $excluded_events = [];
        
        for ($i = 0; $i < $event_count; $i++) {
            $event = $this->selectByRarity($events, $biome, $excluded_events);
            if ($event) {
                $selected_events[] = $event;
                $excluded_events[] = $this->getItemKey($event);
            }
        }
        
        return $selected_events;
    }
    
    /**
     * Выбор игр
     */
    private function selectGames($games) {
        $selected_games = [];
        
        // Выбираем по одной игре из каждой категории
        foreach ($games as $category => $game_list) {
            if (!empty($game_list)) {
                $game = $this->selectByRarity($game_list, 'city');
                if ($game) {
                    $selected_games[$category] = $game;
                }
            }
        }
        
        return $selected_games;
    }
    
    /**
     * Генерация описания таверны через AI
     */
    private function generateTavernDescription($draft) {
        try {
            $prompt = $this->buildTavernDescriptionPrompt($draft);
            $result = $this->ai_service->generateCharacterDescription($draft, true);
            
            if (isset($result['error'])) {
                return $result;
            }
            
            return $result;
            
        } catch (Exception $e) {
            logMessage('ERROR', "TavernGenerator: Ошибка AI генерации: " . $e->getMessage());
            return [
                'error' => 'AI API недоступен',
                'message' => 'Не удалось сгенерировать описание таверны',
                'details' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Построение промпта для описания таверны
     */
    private function buildTavernDescriptionPrompt($draft) {
        $name = $draft['name'];
        $location = $draft['location']['text_ru'] ?? 'неизвестное место';
        $owner = $draft['owner']['name_ru'] ?? 'неизвестный владелец';
        $owner_race = $draft['owner']['race'] ?? 'человек';
        $biome = $draft['biome'];
        
        return "Опиши атмосферную таверну D&D 5e. Таверна: {$name}, расположена {$location}. 
Владелец: {$owner} ({$owner_race}). Биом: {$biome}.

Создай живое описание (600-900 слов) включающее:
- Внешний вид и атмосферу таверны
- Характер владельца и персонала
- Особенности меню и напитков
- Интересные детали и секреты
- Возможные приключения и квесты

Используй кинематографичный стиль без излишней 'пурпурности'.";
    }
}

// Обработка запроса
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    logMessage('INFO', "TavernGenerator: Получен POST запрос с данными: " . json_encode($_POST));
    
    try {
        // Используем улучшенный генератор для лучшего качества
        $generator = new ImprovedTavernGenerator();
        $result = $generator->generateTavern($_POST);
        
        logMessage('INFO', "TavernGenerator: Результат генерации: " . json_encode($result));
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        logMessage('ERROR', "TavernGenerator: Критическая ошибка: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Функция-обертка для генерации таверн (для использования в мобильной версии)
 */
function generateTaverns($params) {
    try {
        // Используем улучшенный генератор для мобильной версии
        $generator = new ImprovedTavernGenerator();
        return $generator->generateTavern($params);
    } catch (Exception $e) {
        logMessage('ERROR', 'generateTaverns wrapper error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
