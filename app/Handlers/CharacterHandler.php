<?php
/**
 * Обработчик для работы с персонажами
 */

require_once __DIR__ . '/../../app/Controllers/UnifiedController.php';

class CharacterHandler {
    private $controller;
    
    public function __construct() {
        $this->controller = new UnifiedController();
    }
    
    /**
     * Генерирует персонажа через API v3
     */
    public function generateCharacter($params = []) {
        try {
            $character = $this->controller->generateCharacter($params);
            return [
                'success' => true,
                'character' => $character
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при генерации персонажа: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Генерирует случайного персонажа
     */
    public function generateRandomCharacter() {
        $races = ['human', 'elf', 'dwarf', 'halfling', 'gnome'];
        $classes = ['fighter', 'wizard', 'cleric', 'rogue', 'barbarian'];
        
        $params = [
            'race' => $races[array_rand($races)],
            'class' => $classes[array_rand($classes)],
            'level' => rand(1, 5),
            'gender' => 'random',
            'alignment' => 'random'
        ];
        
        return $this->generateCharacter($params);
    }
    
    /**
     * Генерирует персонажа по быстрому шаблону
     */
    public function generateQuickCharacter($template) {
        $templates = [
            'human_fighter' => ['race' => 'human', 'class' => 'fighter'],
            'elf_wizard' => ['race' => 'elf', 'class' => 'wizard'],
            'dwarf_cleric' => ['race' => 'dwarf', 'class' => 'cleric'],
            'halfling_rogue' => ['race' => 'halfling', 'class' => 'rogue'],
            'gnome_barbarian' => ['race' => 'gnome', 'class' => 'barbarian']
        ];
        
        if (!isset($templates[$template])) {
            return [
                'success' => false,
                'message' => 'Неизвестный шаблон: ' . $template
            ];
        }
        
        $params = array_merge($templates[$template], [
            'level' => 1,
            'gender' => 'random',
            'alignment' => 'random'
        ]);
        
        return $this->generateCharacter($params);
    }
    
    /**
     * Получает список рас
     */
    public function getRaces() {
        try {
            $races = $this->controller->getRaces();
            return [
                'success' => true,
                'races' => $races
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке рас: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получает список классов
     */
    public function getClasses() {
        try {
            $classes = $this->controller->getClasses();
            return [
                'success' => true,
                'classes' => $classes
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке классов: ' . $e->getMessage()
            ];
        }
    }
}
?>
