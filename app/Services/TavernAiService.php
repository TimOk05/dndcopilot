<?php

require_once __DIR__ . '/ImprovedAiService.php';

/**
 * Специализированный AI сервис для генерации таверн
 * Устраняет повторения и создает уникальные описания
 */
class TavernAiService extends ImprovedAiService {
    private $usedPrompts = [];
    private $promptVariations = [];
    
    public function __construct() {
        parent::__construct();
        $this->initializePromptVariations();
        
        // Инициализируем зависимости
        require_once __DIR__ . '/CacheService.php';
        $this->cacheService = new CacheService();
    }
    
    /**
     * Инициализация вариаций промптов
     */
    private function initializePromptVariations() {
        $this->promptVariations = [
            'cinematic' => [
                'style' => 'кинематографичный',
                'focus' => 'атмосфера и визуальные детали',
                'length' => 'средний (400-600 слов)'
            ],
            'detailed' => [
                'style' => 'детальный и информативный',
                'focus' => 'практические детали и особенности',
                'length' => 'длинный (600-900 слов)'
            ],
            'mysterious' => [
                'style' => 'загадочный и интригующий',
                'focus' => 'тайны и секреты',
                'length' => 'средний (400-600 слов)'
            ],
            'adventurous' => [
                'style' => 'приключенческий и динамичный',
                'focus' => 'возможности для приключений',
                'length' => 'средний (400-600 слов)'
            ],
            'cozy' => [
                'style' => 'уютный и домашний',
                'focus' => 'комфорт и гостеприимство',
                'length' => 'короткий (300-500 слов)'
            ]
        ];
    }
    
    /**
     * Генерация описания таверны с избежанием повторений
     */
    public function generateTavernDescription($tavernData, $useCache = true) {
        $cacheKey = 'tavern_desc_' . md5(json_encode($tavernData));
        
        if ($useCache) {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached !== null) {
                logMessage('DEBUG', 'TavernAiService: Используем кэшированное описание таверны');
                return $cached;
            }
        }
        
        try {
            // Выбираем уникальный стиль промпта
            $promptStyle = $this->selectUniquePromptStyle();
            $prompt = $this->buildTavernPrompt($tavernData, $promptStyle);
            
            $result = $this->generateCharacterDescription($prompt, false);
            
            if ($result && !isset($result['error'])) {
                $description = $this->cleanAiResponse($result);
                
                if ($useCache) {
                    $this->cacheService->set($cacheKey, $description, 3600); // 1 час
                }
                
                return $description;
            }
            
            return [
                'error' => 'AI API недоступен',
                'message' => 'Не удалось сгенерировать описание таверны'
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'TavernAiService: Ошибка генерации описания таверны: ' . $e->getMessage());
            return [
                'error' => 'AI API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Выбор уникального стиля промпта
     */
    private function selectUniquePromptStyle() {
        $availableStyles = array_keys($this->promptVariations);
        $unusedStyles = array_diff($availableStyles, $this->usedPrompts);
        
        if (empty($unusedStyles)) {
            // Сброс при исчерпании всех стилей
            $this->usedPrompts = [];
            $unusedStyles = $availableStyles;
        }
        
        $selectedStyle = $unusedStyles[array_rand($unusedStyles)];
        $this->usedPrompts[] = $selectedStyle;
        
        return $selectedStyle;
    }
    
    /**
     * Построение специализированного промпта для таверны
     */
    private function buildTavernPrompt($tavernData, $style) {
        $variation = $this->promptVariations[$style];
        
        // Извлекаем данные таверны
        $name = $tavernData['name'] ?? 'Таверна';
        $location = $tavernData['location']['text_ru'] ?? 'неизвестное место';
        $owner = $tavernData['owner']['name_ru'] ?? 'неизвестный владелец';
        $ownerRace = $tavernData['owner']['race'] ?? 'человек';
        $biome = $tavernData['biome'] ?? 'город';
        
        // Собираем контекстную информацию
        $staffInfo = $this->formatStaffInfo($tavernData['staff'] ?? []);
        $menuInfo = $this->formatMenuInfo($tavernData['menu'] ?? []);
        $eventsInfo = $this->formatEventsInfo($tavernData['events'] ?? []);
        $atmosphereInfo = $tavernData['ambience']['text_ru'] ?? 'обычная атмосфера';
        $roomsInfo = $this->formatRoomsInfo($tavernData['rooms'] ?? []);
        $gamesInfo = $this->formatGamesInfo($tavernData['games'] ?? []);
        
        // Строим промпт в зависимости от стиля
        switch ($style) {
            case 'cinematic':
                return $this->buildCinematicPrompt($name, $location, $owner, $ownerRace, $biome, $atmosphereInfo, $staffInfo, $menuInfo);
            
            case 'detailed':
                return $this->buildDetailedPrompt($name, $location, $owner, $ownerRace, $biome, $staffInfo, $menuInfo, $eventsInfo, $roomsInfo);
            
            case 'mysterious':
                return $this->buildMysteriousPrompt($name, $location, $owner, $ownerRace, $biome, $eventsInfo);
            
            case 'adventurous':
                return $this->buildAdventurousPrompt($name, $location, $owner, $ownerRace, $biome, $eventsInfo, $gamesInfo);
            
            case 'cozy':
                return $this->buildCozyPrompt($name, $location, $owner, $ownerRace, $biome, $atmosphereInfo, $menuInfo);
            
            default:
                return $this->buildDefaultPrompt($name, $location, $owner, $ownerRace, $biome);
        }
    }
    
    /**
     * Кинематографичный промпт
     */
    private function buildCinematicPrompt($name, $location, $owner, $ownerRace, $biome, $atmosphere, $staff, $menu) {
        return "Создай кинематографичное описание таверны для D&D 5e на русском языке.

ТАВЕРНА: {$name}
МЕСТОПОЛОЖЕНИЕ: {$location}
ВЛАДЕЛЕЦ: {$owner} ({$ownerRace})
БИОМ: {$biome}
АТМОСФЕРА: {$atmosphere}

ПЕРСОНАЛ: {$staff}
МЕНЮ: {$menu}

Создай живое, визуально богатое описание (400-600 слов) включающее:
- Внешний вид и архитектуру таверны
- Атмосферу и настроение
- Характер владельца и взаимодействие с персоналом
- Особенности интерьера и декора
- Типичных посетителей и их поведение
- Звуки, запахи и общую атмосферу

Используй кинематографичный стиль с яркими визуальными образами. Избегай клише и стандартных фраз.";
    }
    
    /**
     * Детальный промпт
     */
    private function buildDetailedPrompt($name, $location, $owner, $ownerRace, $biome, $staff, $menu, $events, $rooms) {
        return "Создай детальное описание таверны для D&D 5e на русском языке.

ТАВЕРНА: {$name}
МЕСТОПОЛОЖЕНИЕ: {$location}
ВЛАДЕЛЕЦ: {$owner} ({$ownerRace})
БИОМ: {$biome}

ПЕРСОНАЛ: {$staff}
МЕНЮ: {$menu}
СОБЫТИЯ: {$events}
КОМНАТЫ: {$rooms}

Создай информативное описание (600-900 слов) включающее:
- Подробное описание архитектуры и планировки
- Детали о персонале и их обязанностях
- Полное меню с описанием блюд и напитков
- Особенности комнат и их предназначение
- Регулярные события и развлечения
- Практическую информацию для путешественников
- Стоимость услуг и особенности обслуживания

Используй информативный стиль с практическими деталями.";
    }
    
    /**
     * Загадочный промпт
     */
    private function buildMysteriousPrompt($name, $location, $owner, $ownerRace, $biome, $events) {
        return "Создай загадочное описание таверны для D&D 5e на русском языке.

ТАВЕРНА: {$name}
МЕСТОПОЛОЖЕНИЕ: {$location}
ВЛАДЕЛЕЦ: {$owner} ({$ownerRace})
БИОМ: {$biome}

СОБЫТИЯ: {$events}

Создай интригующее описание (400-600 слов) включающее:
- Тайны и секреты таверны
- Загадочные истории и легенды
- Подозрительных посетителей
- Скрытые проходы и тайные комнаты
- Странные события и происшествия
- Намеки на магию или сверхъестественное
- Атмосферу таинственности и интриги

Используй загадочный стиль с намеками и недомолвками.";
    }
    
    /**
     * Приключенческий промпт
     */
    private function buildAdventurousPrompt($name, $location, $owner, $ownerRace, $biome, $events, $games) {
        return "Создай приключенческое описание таверны для D&D 5e на русском языке.

ТАВЕРНА: {$name}
МЕСТОПОЛОЖЕНИЕ: {$location}
ВЛАДЕЛЕЦ: {$owner} ({$ownerRace})
БИОМ: {$biome}

СОБЫТИЯ: {$events}
ИГРЫ: {$games}

Создай динамичное описание (400-600 слов) включающее:
- Возможности для приключений и квестов
- Информацию о местных опасностях
- Контакты с гильдиями и организациями
- Места для найма наемников
- Источники информации о подземельях
- Атмосферу авантюризма и риска
- Встречи с другими искателями приключений

Используй динамичный стиль с фокусом на приключения.";
    }
    
    /**
     * Уютный промпт
     */
    private function buildCozyPrompt($name, $location, $owner, $ownerRace, $biome, $atmosphere, $menu) {
        return "Создай уютное описание таверны для D&D 5e на русском языке.

ТАВЕРНА: {$name}
МЕСТОПОЛОЖЕНИЕ: {$location}
ВЛАДЕЛЕЦ: {$owner} ({$ownerRace})
БИОМ: {$biome}
АТМОСФЕРА: {$atmosphere}

МЕНЮ: {$menu}

Создай теплое описание (300-500 слов) включающее:
- Уютную и домашнюю атмосферу
- Гостеприимство владельца и персонала
- Комфортные условия для отдыха
- Вкусную еду и напитки
- Дружелюбных посетителей
- Место для спокойного времяпрепровождения
- Ощущение безопасности и покоя

Используй теплый, уютный стиль с акцентом на комфорт.";
    }
    
    /**
     * Стандартный промпт
     */
    private function buildDefaultPrompt($name, $location, $owner, $ownerRace, $biome) {
        return "Создай описание таверны для D&D 5e на русском языке.

ТАВЕРНА: {$name}
МЕСТОПОЛОЖЕНИЕ: {$location}
ВЛАДЕЛЕЦ: {$owner} ({$ownerRace})
БИОМ: {$biome}

Создай описание (400-600 слов) включающее:
- Внешний вид и атмосферу таверны
- Характер владельца
- Особенности заведения
- Типичных посетителей
- Интересные детали

Используй живой, описательный стиль.";
    }
    
    /**
     * Форматирование информации о персонале
     */
    private function formatStaffInfo($staff) {
        if (empty($staff)) {
            return 'Персонал не указан';
        }
        
        $staffList = [];
        foreach ($staff as $member) {
            $role = $member['role'] ?? 'сотрудник';
            $name = $member['name_ru'] ?? 'Неизвестный';
            $staffList[] = "{$role}: {$name}";
        }
        
        return implode(', ', $staffList);
    }
    
    /**
     * Форматирование информации о меню
     */
    private function formatMenuInfo($menu) {
        if (empty($menu)) {
            return 'Меню не указано';
        }
        
        $menuItems = [];
        
        if (isset($menu['drinks']) && !empty($menu['drinks'])) {
            $drinks = array_column($menu['drinks'], 'name_ru');
            $menuItems[] = 'Напитки: ' . implode(', ', array_slice($drinks, 0, 3));
        }
        
        if (isset($menu['meals']) && !empty($menu['meals'])) {
            $meals = array_column($menu['meals'], 'name_ru');
            $menuItems[] = 'Блюда: ' . implode(', ', array_slice($meals, 0, 3));
        }
        
        return empty($menuItems) ? 'Меню не указано' : implode('; ', $menuItems);
    }
    
    /**
     * Форматирование информации о событиях
     */
    private function formatEventsInfo($events) {
        if (empty($events)) {
            return 'События не указаны';
        }
        
        $eventNames = array_column($events, 'name_ru');
        return implode(', ', array_slice($eventNames, 0, 3));
    }
    
    /**
     * Форматирование информации о комнатах
     */
    private function formatRoomsInfo($rooms) {
        if (empty($rooms)) {
            return 'Комнаты не указаны';
        }
        
        $roomTypes = array_column($rooms, 'type');
        return implode(', ', array_slice($roomTypes, 0, 3));
    }
    
    /**
     * Форматирование информации об играх
     */
    private function formatGamesInfo($games) {
        if (empty($games)) {
            return 'Игры не указаны';
        }
        
        $gameList = [];
        foreach ($games as $category => $game) {
            if (isset($game['name_ru'])) {
                $gameList[] = $game['name_ru'];
            }
        }
        
        return implode(', ', array_slice($gameList, 0, 3));
    }
    
    /**
     * Очистка кэша использованных промптов
     */
    public function clearUsedPrompts() {
        $this->usedPrompts = [];
        logMessage('INFO', 'TavernAiService: Кэш использованных промптов очищен');
    }
    
    /**
     * Получение статистики использования стилей
     */
    public function getPromptStats() {
        return [
            'used_prompts' => $this->usedPrompts,
            'available_styles' => array_keys($this->promptVariations),
            'usage_count' => count($this->usedPrompts)
        ];
    }
}
