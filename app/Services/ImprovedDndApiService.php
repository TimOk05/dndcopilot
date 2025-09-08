<?php

require_once __DIR__ . '/dnd-api-service.php';

/**
 * Улучшенный D&D API сервис с использованием централизованного HTTP клиента
 * Расширяет базовый DndApiService с оптимизациями
 */
class ImprovedDndApiService extends DndApiService {
    private $httpClient;
    private $translationService;
    
    public function __construct() {
        parent::__construct();
        
        // Инициализируем зависимости
        require_once __DIR__ . '/HttpClient.php';
        require_once __DIR__ . '/TranslationService.php';
        require_once __DIR__ . '/CacheService.php';
        
        $this->httpClient = new HttpClient();
        $this->translationService = TranslationService::getInstance();
        $this->cacheService = new CacheService();
        
        // Инициализируем api_endpoints из родительского класса
        $this->api_endpoints = [
            'open5e' => 'https://api.open5e.com',
            'dnd5eapi' => 'https://www.dnd5eapi.co/api'
        ];
    }
    
    /**
     * Переопределяем makeApiRequest для использования HttpClient
     */
    protected function makeApiRequest($url, $method = 'GET', $data = null) {
        try {
            if ($method === 'GET') {
                return $this->httpClient->get($url);
            } else {
                return $this->httpClient->post($url, $data);
            }
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: HTTP запрос не удался: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение данных из кэша
     */
    protected function getFromCache($key) {
        return $this->cacheService->get($key);
    }
    
    /**
     * Сохранение данных в кэш
     */
    protected function saveToCache($key, $data, $ttl = 3600) {
        return $this->cacheService->set($key, $data, $ttl);
    }
    
    /**
     * Получение статистики кэша
     */
    protected function getCacheStats() {
        return $this->cacheService->getStats();
    }
    
    /**
     * Получение статистики API вызовов
     */
    protected function getApiCallStats() {
        return [
            'total_calls' => $this->httpClient->getTotalCalls(),
            'successful_calls' => $this->httpClient->getSuccessfulCalls(),
            'failed_calls' => $this->httpClient->getFailedCalls()
        ];
    }
    
    /**
     * Получение времени последней очистки кэша
     */
    protected function getLastCacheCleanup() {
        return $this->cacheService->getLastCleanup();
    }
    
    /**
     * Получение данных расы с улучшенной обработкой ошибок
     */
    public function getRaceData($race) {
        $cacheKey = 'race_' . strtolower($race);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->api_endpoints['dnd5eapi'] . '/races/' . strtolower($race);
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить данные расы: ' . $race
                ];
            }
            
            // Сохраняем в кэш
            $this->saveToCache($cacheKey, $data, 3600);
            
            return $data;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения данных расы $race: " . $e->getMessage());
            return [
                'error' => 'API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение данных класса с улучшенной обработкой ошибок
     */
    public function getClassData($class) {
        $cacheKey = 'class_' . strtolower($class);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->api_endpoints['dnd5eapi'] . '/classes/' . strtolower($class);
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить данные класса: ' . $class
                ];
            }
            
            // Сохраняем в кэш
            $this->saveToCache($cacheKey, $data, 3600);
            
            return $data;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения данных класса $class: " . $e->getMessage());
            return [
                'error' => 'API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение списка монстров с улучшенной фильтрацией
     */
    public function getMonstersList($filters = []) {
        $cacheKey = 'monsters_list_' . md5(json_encode($filters));
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->api_endpoints['dnd5eapi'] . '/monsters';
            
            // Добавляем параметры фильтрации если есть
            if (!empty($filters)) {
                $url .= '?' . http_build_query($filters);
            }
            
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить список монстров'
                ];
            }
            
            // Сохраняем в кэш
            $this->saveToCache($cacheKey, $data, 1800); // 30 минут
            
            return $data;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения списка монстров: " . $e->getMessage());
            return [
                'error' => 'API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение деталей монстра с улучшенной обработкой
     */
    public function getMonsterDetails($monsterIndex) {
        $cacheKey = 'monster_' . strtolower($monsterIndex);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->api_endpoints['dnd5eapi'] . '/monsters/' . strtolower($monsterIndex);
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить данные монстра: ' . $monsterIndex
                ];
            }
            
            // Сохраняем в кэш
            $this->saveToCache($cacheKey, $data, 3600);
            
            return $data;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения данных монстра $monsterIndex: " . $e->getMessage());
            return [
                'error' => 'API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение заклинаний для класса с улучшенной обработкой
     */
    public function getSpellsForClass($class_name, $level = 1) {
        $cacheKey = 'spells_' . strtolower($class_name) . '_' . $level;
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->api_endpoints['dnd5eapi'] . '/classes/' . strtolower($class_name) . '/spells';
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить заклинания для класса: ' . $class_name
                ];
            }
            
            // Фильтруем заклинания по уровню
            if (isset($data['results']) && is_array($data['results'])) {
                $filteredSpells = [];
                foreach ($data['results'] as $spell) {
                    // Здесь можно добавить логику фильтрации по уровню
                    $filteredSpells[] = $spell;
                }
                $data['results'] = $filteredSpells;
            }
            
            // Сохраняем в кэш
            $this->saveToCache($cacheKey, $data, 3600);
            
            return $data;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения заклинаний для класса $class_name: " . $e->getMessage());
            return [
                'error' => 'API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение снаряжения для класса
     */
    public function getEquipmentForClass($class_name) {
        $cacheKey = 'equipment_' . strtolower($class_name);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->api_endpoints['dnd5eapi'] . '/classes/' . strtolower($class_name) . '/starting-equipment';
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить снаряжение для класса: ' . $class_name
                ];
            }
            
            // Сохраняем в кэш
            $this->saveToCache($cacheKey, $data, 3600);
            
            return $data;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения снаряжения для класса $class_name: " . $e->getMessage());
            return [
                'error' => 'API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение особенностей класса
     */
    public function getFeaturesForClass($class_name, $level) {
        $cacheKey = 'features_' . strtolower($class_name) . '_' . $level;
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->api_endpoints['dnd5eapi'] . '/classes/' . strtolower($class_name) . '/levels/' . $level;
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить особенности для класса: ' . $class_name . ' уровня ' . $level
                ];
            }
            
            // Сохраняем в кэш
            $this->saveToCache($cacheKey, $data, 3600);
            
            return $data;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения особенностей для класса $class_name уровня $level: " . $e->getMessage());
            return [
                'error' => 'API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Проверка доступности API
     */
    public function checkApiHealth() {
        try {
            $url = $this->api_endpoints['dnd5eapi'] . '/races';
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'API недоступен'
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => 'API работает нормально',
                'response_time' => $this->httpClient->getLastResponseTime()
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Ошибка проверки API: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение статистики использования API
     */
    public function getApiStats() {
        return [
            'cache_hits' => $this->getCacheStats(),
            'api_calls' => $this->getApiCallStats(),
            'last_cleanup' => $this->getLastCacheCleanup()
        ];
    }
}
