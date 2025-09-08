<?php

/**
 * Улучшенный AI сервис с оптимизацией и централизованным управлением
 * Расширяет базовый AiService с дополнительными возможностями
 */
class ImprovedAiService extends AiService {
    private $httpClient;
    private $translationService;
    private $cacheService;
    
    public function __construct() {
        parent::__construct();
        
        // Инициализируем зависимости
        require_once __DIR__ . '/HttpClient.php';
        require_once __DIR__ . '/TranslationService.php';
        require_once __DIR__ . '/CacheService.php';
        
        $this->httpClient = new HttpClient();
        $this->translationService = TranslationService::getInstance();
        $this->cacheService = new CacheService();
    }
    
    /**
     * Генерация описания таверны (исправляет использование generateCharacterDescription)
     */
    public function generateTavernDescription($tavernData, $useCache = true) {
        $cacheKey = 'tavern_desc_' . md5(json_encode($tavernData));
        
        if ($useCache) {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached !== null) {
                logMessage('DEBUG', 'ImprovedAiService: Используем кэшированное описание таверны');
                return $cached;
            }
        }
        
        try {
            $prompt = $this->buildTavernPrompt($tavernData);
            $result = $this->makeApiRequest($prompt);
            
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
            logMessage('ERROR', 'ImprovedAiService: Ошибка генерации описания таверны: ' . $e->getMessage());
            return [
                'error' => 'AI API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Генерация тактики противника
     */
    public function generateEnemyTactics($enemyData, $useCache = true) {
        $cacheKey = 'enemy_tactics_' . md5(json_encode($enemyData));
        
        if ($useCache) {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached !== null) {
                logMessage('DEBUG', 'ImprovedAiService: Используем кэшированную тактику противника');
                return $cached;
            }
        }
        
        try {
            $prompt = $this->buildEnemyTacticsPrompt($enemyData);
            $result = $this->makeApiRequest($prompt);
            
            if ($result && !isset($result['error'])) {
                $tactics = $this->cleanAiResponse($result);
                
                if ($useCache) {
                    $this->cacheService->set($cacheKey, $tactics, 3600); // 1 час
                }
                
                return $tactics;
            }
            
            return [
                'error' => 'AI API недоступен',
                'message' => 'Не удалось сгенерировать тактику противника'
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'ImprovedAiService: Ошибка генерации тактики противника: ' . $e->getMessage());
            return [
                'error' => 'AI API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Перевод действий противника
     */
    public function translateEnemyActions($actions, $targetLanguage = 'ru') {
        if (empty($actions) || $targetLanguage !== 'ru') {
            return $actions;
        }
        
        $cacheKey = 'enemy_actions_' . md5(json_encode($actions));
        $cached = $this->cacheService->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $prompt = $this->buildActionsTranslationPrompt($actions);
            $result = $this->makeApiRequest($prompt);
            
            if ($result && !isset($result['error'])) {
                $translated = $this->cleanAiResponse($result);
                $this->cacheService->set($cacheKey, $translated, 7200); // 2 часа
                return $translated;
            }
            
            return $actions; // Возвращаем оригинал при ошибке
            
        } catch (Exception $e) {
            logMessage('WARNING', 'ImprovedAiService: Ошибка перевода действий: ' . $e->getMessage());
            return $actions;
        }
    }
    
    /**
     * Перевод особых способностей противника
     */
    public function translateEnemySpecialAbilities($abilities, $targetLanguage = 'ru') {
        if (empty($abilities) || $targetLanguage !== 'ru') {
            return $abilities;
        }
        
        $cacheKey = 'enemy_abilities_' . md5(json_encode($abilities));
        $cached = $this->cacheService->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $prompt = $this->buildAbilitiesTranslationPrompt($abilities);
            $result = $this->makeApiRequest($prompt);
            
            if ($result && !isset($result['error'])) {
                $translated = $this->cleanAiResponse($result);
                $this->cacheService->set($cacheKey, $translated, 7200); // 2 часа
                return $translated;
            }
            
            return $abilities; // Возвращаем оригинал при ошибке
            
        } catch (Exception $e) {
            logMessage('WARNING', 'ImprovedAiService: Ошибка перевода способностей: ' . $e->getMessage());
            return $abilities;
        }
    }
    
    /**
     * Построение промпта для описания таверны
     */
    private function buildTavernPrompt($tavernData) {
        $name = $tavernData['name'] ?? 'Таверна';
        $type = $tavernData['type'] ?? 'обычная';
        $atmosphere = $tavernData['atmosphere'] ?? 'дружелюбная';
        $location = $tavernData['location'] ?? 'в городе';
        
        return "Создай атмосферное описание таверны для D&D 5e на русском языке.

Название: {$name}
Тип: {$type}
Атмосфера: {$atmosphere}
Расположение: {$location}

Опиши:
- Внешний вид и интерьер
- Атмосферу и настроение
- Особенности заведения
- Типичных посетителей
- Интересные детали

Длина: 2-3 абзаца, атмосферно и детально.";
    }
    
    /**
     * Построение промпта для тактики противника
     */
    private function buildEnemyTacticsPrompt($enemyData) {
        $name = $enemyData['name'] ?? 'Противник';
        $type = $enemyData['type'] ?? 'существо';
        $cr = $enemyData['challenge_rating'] ?? 'неизвестный';
        
        return "Создай тактику боя для противника в D&D 5e на русском языке.

Противник: {$name}
Тип: {$type}
Уровень сложности: CR {$cr}

Опиши:
- Основную тактику в бою
- Приоритетные цели
- Использование способностей
- Поведение при низком HP
- Особенности группового боя (если применимо)

Длина: 1-2 абзаца, практично и полезно для мастера.";
    }
    
    /**
     * Построение промпта для перевода действий
     */
    private function buildActionsTranslationPrompt($actions) {
        $actionsText = '';
        foreach ($actions as $action) {
            $actionsText .= "- {$action['name']}: {$action['description']}\n";
        }
        
        return "Переведи на русский язык действия противника для D&D 5e. Сохрани игровую терминологию и форматирование.

Действия:
{$actionsText}

Верни результат в том же JSON формате с переведенными названиями и описаниями.";
    }
    
    /**
     * Построение промпта для перевода способностей
     */
    private function buildAbilitiesTranslationPrompt($abilities) {
        $abilitiesText = '';
        foreach ($abilities as $ability) {
            $abilitiesText .= "- {$ability['name']}: {$ability['description']}\n";
        }
        
        return "Переведи на русский язык особые способности противника для D&D 5e. Сохрани игровую терминологию и форматирование.

Способности:
{$abilitiesText}

Верни результат в том же JSON формате с переведенными названиями и описаниями.";
    }
    
    /**
     * Массовая генерация контента (для оптимизации)
     */
    public function generateBatchContent($requests, $useCache = true) {
        $results = [];
        
        foreach ($requests as $index => $request) {
            try {
                $type = $request['type'] ?? 'character';
                $data = $request['data'] ?? [];
                
                switch ($type) {
                    case 'character':
                        $results[$index] = $this->generateCharacterDescription($data, $useCache);
                        break;
                    case 'tavern':
                        $results[$index] = $this->generateTavernDescription($data, $useCache);
                        break;
                    case 'enemy_tactics':
                        $results[$index] = $this->generateEnemyTactics($data, $useCache);
                        break;
                    default:
                        $results[$index] = ['error' => 'Неизвестный тип генерации'];
                }
                
            } catch (Exception $e) {
                logMessage('ERROR', "ImprovedAiService: Ошибка в batch генерации $index: " . $e->getMessage());
                $results[$index] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    /**
     * Очистка кэша AI контента
     */
    public function clearAiCache() {
        $this->cacheService->clear();
        logMessage('INFO', 'ImprovedAiService: Кэш AI контента очищен');
    }
    
    /**
     * Получение статистики использования AI
     */
    public function getAiStats() {
        return [
            'cache_hits' => $this->cacheService->getStats(),
            'api_calls' => $this->getApiCallStats(),
            'last_cleanup' => $this->cacheService->getLastCleanup()
        ];
    }
}
