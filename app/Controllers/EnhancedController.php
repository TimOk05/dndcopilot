<?php
/**
 * Улучшенный контроллер для работы с персонажами
 * Использует полную базу данных JSON
 */

require_once __DIR__ . '/../Services/EnhancedCharacterService.php';

class EnhancedController {
    private $characterService;
    
    public function __construct() {
        $this->characterService = new EnhancedCharacterService();
    }
    
    /**
     * Генерирует персонажа
     */
    public function generateCharacter($params) {
        try {
            $this->validateCharacterParams($params);
            return $this->characterService->generateCharacter($params);
        } catch (Exception $e) {
            logMessage('ERROR', 'Enhanced character generation failed', [
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
     * Получает заклинания для класса
     */
    public function getSpellsForClass($classId, $level = 1) {
        try {
            $class = $this->characterService->getClassById($classId);
            if (!$class) {
                return [];
            }
            
            // Создаем временный персонаж для генерации заклинаний
            $tempParams = [
                'race' => 'human',
                'class' => $classId,
                'level' => $level,
                'gender' => 'random',
                'alignment' => 'random'
            ];
            
            $character = $this->characterService->generateCharacter($tempParams);
            return $character['spells'] ?? [];
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to get spells for class', [
                'error' => $e->getMessage(),
                'class_id' => $classId,
                'level' => $level
            ]);
            return [];
        }
    }
    
    /**
     * Получает зелья
     */
    public function getPotions($count = 5) {
        try {
            // Создаем временный персонаж для генерации зелий
            $tempParams = [
                'race' => 'human',
                'class' => 'fighter',
                'level' => 1,
                'gender' => 'random',
                'alignment' => 'random'
            ];
            
            $character = $this->characterService->generateCharacter($tempParams);
            return $character['potions'] ?? [];
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to get potions', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Получает снаряжение для класса
     */
    public function getEquipmentForClass($classId) {
        try {
            $class = $this->characterService->getClassById($classId);
            if (!$class) {
                return [];
            }
            
            // Создаем временный персонаж для генерации снаряжения
            $tempParams = [
                'race' => 'human',
                'class' => $classId,
                'level' => 1,
                'gender' => 'random',
                'alignment' => 'random'
            ];
            
            $character = $this->characterService->generateCharacter($tempParams);
            return $character['equipment'] ?? [];
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to get equipment for class', [
                'error' => $e->getMessage(),
                'class_id' => $classId
            ]);
            return [];
        }
    }
    
    /**
     * Получает статистику базы данных
     */
    public function getDatabaseStats() {
        try {
            $races = $this->characterService->getRaces();
            $classes = $this->characterService->getClasses();
            
            return [
                'races_count' => count($races),
                'classes_count' => count($classes),
                'database_status' => 'loaded',
                'last_check' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            logMessage('ERROR', 'Failed to get database stats', [
                'error' => $e->getMessage()
            ]);
            return [
                'races_count' => 0,
                'classes_count' => 0,
                'database_status' => 'error',
                'last_check' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage()
            ];
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
        
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации: ' . implode(', ', $errors));
        }
    }
}
?>
