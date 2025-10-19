<?php
/**
 * Унифицированный контроллер для работы с персонажами
 * Версия 3.0 - Улучшенная архитектура
 */

require_once __DIR__ . '/../Services/UnifiedCharacterService.php';

class UnifiedController {
    private $characterService;
    
    public function __construct() {
        $this->characterService = new UnifiedCharacterService();
    }
    
    /**
     * Генерирует персонажа
     */
    public function generateCharacter($params) {
        try {
            // Валидация параметров
            $this->validateCharacterParams($params);
            
            return $this->characterService->generateCharacter($params);
        } catch (Exception $e) {
            logMessage('ERROR', 'Character generation failed', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Получает список рас
     */
    public function getRaces() {
        try {
            return $this->characterService->getRaces();
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to load races', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Получает список классов
     */
    public function getClasses() {
        try {
            return $this->characterService->getClasses();
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to load classes', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Получает расу по ID
     */
    public function getRaceById($raceId) {
        try {
            return $this->characterService->getRaceById($raceId);
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to load race', [
                'error' => $e->getMessage(),
                'race_id' => $raceId
            ]);
            return null;
        }
    }
    
    /**
     * Получает класс по ID
     */
    public function getClassById($classId) {
        try {
            return $this->characterService->getClassById($classId);
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to load class', [
                'error' => $e->getMessage(),
                'class_id' => $classId
            ]);
            return null;
        }
    }
    
    /**
     * Получает подрасы для расы
     */
    public function getSubraces($raceId) {
        try {
            return $this->characterService->getSubraces($raceId);
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to load subraces', [
                'error' => $e->getMessage(),
                'race_id' => $raceId
            ]);
            return [];
        }
    }
    
    /**
     * Получает архетипы для класса
     */
    public function getArchetypes($classId) {
        try {
            return $this->characterService->getArchetypes($classId);
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to load archetypes', [
                'error' => $e->getMessage(),
                'class_id' => $classId
            ]);
            return [];
        }
    }
    
    /**
     * Валидирует параметры для генерации персонажа
     */
    private function validateCharacterParams($params) {
        $errors = [];
        
        // Проверяем обязательные параметры
        if (empty($params['race'])) {
            $errors[] = 'Раса не указана';
        }
        
        if (empty($params['class'])) {
            $errors[] = 'Класс не указан';
        }
        
        // Проверяем уровень
        if (isset($params['level'])) {
            $level = (int)$params['level'];
            if ($level < 1 || $level > 20) {
                $errors[] = 'Уровень должен быть от 1 до 20';
            }
        }
        
        // Проверяем пол
        if (isset($params['gender']) && !in_array($params['gender'], ['male', 'female', 'random'])) {
            $errors[] = 'Пол должен быть male, female или random';
        }
        
        // Проверяем мировоззрение
        if (isset($params['alignment']) && !in_array($params['alignment'], [
            'Законопослушный добрый', 'Нейтральный добрый', 'Хаотичный добрый',
            'Законопослушный нейтральный', 'Истинно нейтральный', 'Хаотичный нейтральный',
            'Законопослушный злой', 'Нейтральный злой', 'Хаотичный злой', 'random'
        ])) {
            $errors[] = 'Неверное мировоззрение';
        }
        
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации: ' . implode(', ', $errors));
        }
    }
    
    /**
     * Получает статистику сервиса
     */
    public function getServiceStats() {
        try {
            $races = $this->characterService->getRaces();
            $classes = $this->characterService->getClasses();
            
            return [
                'races_count' => count($races),
                'classes_count' => count($classes),
                'service_status' => 'active',
                'last_check' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to get service stats', [
                'error' => $e->getMessage()
            ]);
            return [
                'races_count' => 0,
                'classes_count' => 0,
                'service_status' => 'error',
                'last_check' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage()
            ];
        }
    }
}
?>