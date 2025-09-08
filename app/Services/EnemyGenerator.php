<?php

require_once __DIR__ . '/EnemyFilter.php';
require_once __DIR__ . '/EnemyFormatter.php';
require_once __DIR__ . '/ImprovedDndApiService.php';
require_once __DIR__ . '/TranslationService.php';
require_once __DIR__ . '/CacheService.php';

/**
 * Генератор противников для D&D 5e
 * Использует EnemyFilter и EnemyFormatter для модульной архитектуры
 */
class EnemyGenerator {
    private $dndApiService;
    private $enemyFilter;
    private $enemyFormatter;
    private $translationService;
    private $cacheService;
    
    public function __construct() {
        $this->dndApiService = new ImprovedDndApiService();
        $this->enemyFilter = new EnemyFilter();
        $this->enemyFormatter = new EnemyFormatter();
        $this->translationService = TranslationService::getInstance();
        $this->cacheService = new CacheService();
        
        logMessage('INFO', 'EnemyGenerator: Инициализирован');
    }
    
    /**
     * Генерация противников
     */
    public function generateEnemies($params) {
        try {
            $cr = $params['cr'] ?? '1-3';
            $type = $params['type'] ?? 'any';
            $environment = $params['environment'] ?? 'any';
            $language = $params['language'] ?? 'ru';
            $use_ai = isset($params['use_ai']) ? ($params['use_ai'] === 'on') : true;
            
            logMessage('INFO', "EnemyGenerator: Генерация противников. CR: $cr, Тип: $type, Среда: $environment, AI: " . ($use_ai ? 'Вкл' : 'Выкл'));
            
            // Получаем всех монстров
            $monsters = $this->dndApiService->getMonstersList();
            if (!$monsters || isset($monsters['error'])) {
                throw new Exception('Не удалось получить данные о монстрах');
            }
            
            // Фильтруем монстров
            $filteredMonsters = $this->enemyFilter->filterMonsters($monsters, $cr, $type, $environment);
            
            if (empty($filteredMonsters)) {
                throw new Exception('Не найдено монстров, соответствующих критериям');
            }
            
            // Выбираем случайных монстров
            $selectedMonsters = $this->selectRandomMonsters($filteredMonsters, 3);
            
            // Форматируем данные
            $enemies = [];
            foreach ($selectedMonsters as $monster) {
                $enemy = $this->enemyFormatter->formatEnemy($monster, $language, $use_ai);
                if ($enemy) {
                    $enemies[] = $enemy;
                }
            }
            
            if (empty($enemies)) {
                throw new Exception('Не удалось сформатировать данные о противниках');
            }
            
            return [
                'success' => true,
                'enemies' => $enemies,
                'count' => count($enemies),
                'filters' => [
                    'cr' => $cr,
                    'type' => $type,
                    'environment' => $environment
                ]
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', "EnemyGenerator: Ошибка генерации: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Выбор случайных монстров
     */
    private function selectRandomMonsters($monsters, $count) {
        if (count($monsters) <= $count) {
            return $monsters;
        }
        
        $selected = [];
        $indices = array_rand($monsters, $count);
        
        if (!is_array($indices)) {
            $indices = [$indices];
        }
        
        foreach ($indices as $index) {
            $selected[] = $monsters[$index];
        }
        
        return $selected;
    }
    
    /**
     * Получение информации о монстре по индексу
     */
    public function getMonsterByIndex($index) {
        try {
            $monster = $this->dndApiService->getMonsterDetails($index);
            if (!$monster || isset($monster['error'])) {
                throw new Exception('Монстр не найден');
            }
            
            return [
                'success' => true,
                'monster' => $monster
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', "EnemyGenerator: Ошибка получения монстра: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение всех доступных типов монстров
     */
    public function getMonsterTypes() {
        try {
            $monsters = $this->dndApiService->getMonstersList();
            if (!$monsters || isset($monsters['error'])) {
                throw new Exception('Не удалось получить данные о монстрах');
            }
            
            $types = [];
            if (isset($monsters['results']) && is_array($monsters['results'])) {
                foreach ($monsters['results'] as $monster) {
                    if (isset($monster['type'])) {
                        $types[] = $monster['type'];
                    }
                }
            }
            
            $types = array_unique($types);
            sort($types);
            
            return [
                'success' => true,
                'types' => $types
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', "EnemyGenerator: Ошибка получения типов: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение всех доступных сред обитания
     */
    public function getEnvironments() {
        try {
            $monsters = $this->dndApiService->getMonstersList();
            if (!$monsters || isset($monsters['error'])) {
                throw new Exception('Не удалось получить данные о монстрах');
            }
            
            $environments = [];
            if (isset($monsters['results']) && is_array($monsters['results'])) {
                foreach ($monsters['results'] as $monster) {
                    if (isset($monster['environment'])) {
                        $envs = is_array($monster['environment']) ? $monster['environment'] : [$monster['environment']];
                        foreach ($envs as $env) {
                            $environments[] = $env;
                        }
                    }
                }
            }
            
            $environments = array_unique($environments);
            sort($environments);
            
            return [
                'success' => true,
                'environments' => $environments
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', "EnemyGenerator: Ошибка получения сред: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
