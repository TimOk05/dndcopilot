<?php
/**
 * Language Service - Упрощенный сервис для русского языка
 * Всегда возвращает русский язык
 */

class LanguageService {
    private $current_language = 'ru';
    
    public function __construct() {
        // Язык всегда русский
        $this->current_language = 'ru';
    }
    
    /**
     * Получить текущий язык
     */
    public function getCurrentLanguage() {
        return $this->current_language;
    }
    
    /**
     * Получить название редкости на русском
     */
    public function getRarityName($rarity, $language = 'ru') {
        $rarities = [
            'common' => 'Обычная',
            'uncommon' => 'Необычная',
            'rare' => 'Редкая',
            'very rare' => 'Очень редкая',
            'legendary' => 'Легендарная',
            'artifact' => 'Артефакт'
        ];
        
        return $rarities[$rarity] ?? $rarity;
    }
    
    /**
     * Получить название типа зелья на русском
     */
    public function getPotionTypeName($type, $language = 'ru') {
        $types = [
            'potion' => 'Зелье',
            'elixir' => 'Эликсир',
            'oil' => 'Масло',
            'poison' => 'Яд',
            'antidote' => 'Противоядие'
        ];
        
        return $types[$type] ?? $type;
    }
    
    /**
     * Получить название класса на русском
     */
    public function getClassName($class, $language = 'ru') {
        $classes = [
            'fighter' => 'Воин',
            'wizard' => 'Маг',
            'rogue' => 'Плут',
            'cleric' => 'Жрец',
            'ranger' => 'Следопыт',
            'paladin' => 'Паладин',
            'barbarian' => 'Варвар',
            'bard' => 'Бард',
            'druid' => 'Друид',
            'monk' => 'Монах',
            'sorcerer' => 'Чародей',
            'warlock' => 'Колдун'
        ];
        
        return $classes[$class] ?? $class;
    }
    
    /**
     * Получить название расы на русском
     */
    public function getRaceName($race, $language = 'ru') {
        $races = [
            'human' => 'Человек',
            'elf' => 'Эльф',
            'dwarf' => 'Дворф',
            'halfling' => 'Полурослик',
            'dragonborn' => 'Драконорожденный',
            'gnome' => 'Гном',
            'half-elf' => 'Полуэльф',
            'half-orc' => 'Полуорк',
            'tiefling' => 'Тифлинг'
        ];
        
        return $races[$race] ?? $race;
    }
    
    /**
     * Получить информацию о языках (для совместимости)
     */
    public function getLanguageInfo() {
        return [
            'current' => 'ru',
            'supported' => ['ru'],
            'default' => 'ru'
        ];
    }
}
?>