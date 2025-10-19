<?php
/**
 * Контроллер для работы с персонажами
 * Отделяет логику от представления
 */

require_once __DIR__ . '/../Services/ImprovedCharacterService.php';

class CharacterController {
    private $characterService;
    
    public function __construct() {
        $this->characterService = new ImprovedCharacterService();
    }
    
    /**
     * Генерирует персонажа
     */
    public function generateCharacter($params) {
        try {
            return $this->characterService->generateCharacter($params);
        } catch (Exception $e) {
            error_log("Character generation failed: " . $e->getMessage());
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
            error_log("Failed to load races: " . $e->getMessage());
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
            error_log("Failed to load classes: " . $e->getMessage());
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
            error_log("Failed to load race: " . $e->getMessage());
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
            error_log("Failed to load class: " . $e->getMessage());
            return null;
        }
    }
}
?>
