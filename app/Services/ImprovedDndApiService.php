<?php

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
        
        $this->httpClient = new HttpClient();
        $this->translationService = TranslationService::getInstance();
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
     * Получение данных расы с улучшенной обработкой ошибок
     */
    public function getRaceData($race) {
        $cacheKey = 'race_' . strtolower($race);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->dnd5e_api_url . '/races/' . strtolower($race);
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
            $url = $this->dnd5e_api_url . '/classes/' . strtolower($class);
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
            $url = $this->dnd5e_api_url . '/monsters';
            
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
            $url = $this->dnd5e_api_url . '/monsters/' . strtolower($monsterIndex);
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
    public function getSpellsForClass($class, $level) {
        $cacheKey = 'spells_' . strtolower($class) . '_' . $level;
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->dnd5e_api_url . '/classes/' . strtolower($class) . '/spells';
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить заклинания для класса: ' . $class
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
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения заклинаний для класса $class: " . $e->getMessage());
            return [
                'error' => 'API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение снаряжения для класса
     */
    public function getEquipmentForClass($class) {
        $cacheKey = 'equipment_' . strtolower($class);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->dnd5e_api_url . '/classes/' . strtolower($class) . '/starting-equipment';
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить снаряжение для класса: ' . $class
                ];
            }
            
            // Сохраняем в кэш
            $this->saveToCache($cacheKey, $data, 3600);
            
            return $data;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения снаряжения для класса $class: " . $e->getMessage());
            return [
                'error' => 'API недоступен',
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение особенностей класса
     */
    public function getFeaturesForClass($class, $level) {
        $cacheKey = 'features_' . strtolower($class) . '_' . $level;
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $url = $this->dnd5e_api_url . '/classes/' . strtolower($class) . '/levels/' . $level;
            $data = $this->makeApiRequest($url);
            
            if ($data === false) {
                return [
                    'error' => 'API недоступен',
                    'message' => 'Не удалось получить особенности для класса: ' . $class . ' уровня ' . $level
                ];
            }
            
            // Сохраняем в кэш
            $this->saveToCache($cacheKey, $data, 3600);
            
            return $data;
            
        } catch (Exception $e) {
            logMessage('ERROR', "ImprovedDndApiService: Ошибка получения особенностей для класса $class уровня $level: " . $e->getMessage());
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
            $url = $this->dnd5e_api_url . '/races';
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
