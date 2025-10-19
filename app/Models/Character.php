<?php
/**
 * Модель персонажа
 * Представляет структуру данных персонажа
 */

class Character {
    public $name;
    public $race;
    public $class;
    public $level;
    public $gender;
    public $alignment;
    public $background;
    public $abilities;
    public $modifiers;
    public $hit_points;
    public $armor_class;
    public $speed;
    public $initiative;
    public $proficiency_bonus;
    public $equipment;
    public $spells;
    public $potions;
    public $personality;
    public $description;
    public $background_story;
    
    public function __construct($data = []) {
        $this->name = $data['name'] ?? '';
        $this->race = $data['race'] ?? '';
        $this->class = $data['class'] ?? '';
        $this->level = $data['level'] ?? 1;
        $this->gender = $data['gender'] ?? 'random';
        $this->alignment = $data['alignment'] ?? 'random';
        $this->background = $data['background'] ?? [];
        $this->abilities = $data['abilities'] ?? [];
        $this->modifiers = $data['modifiers'] ?? [];
        $this->hit_points = $data['hit_points'] ?? 0;
        $this->armor_class = $data['armor_class'] ?? 10;
        $this->speed = $data['speed'] ?? 30;
        $this->initiative = $data['initiative'] ?? 0;
        $this->proficiency_bonus = $data['proficiency_bonus'] ?? 2;
        $this->equipment = $data['equipment'] ?? [];
        $this->spells = $data['spells'] ?? [];
        $this->potions = $data['potions'] ?? [];
        $this->personality = $data['personality'] ?? [];
        $this->description = $data['description'] ?? '';
        $this->background_story = $data['background_story'] ?? '';
    }
    
    /**
     * Преобразует персонажа в массив
     */
    public function toArray() {
        return [
            'name' => $this->name,
            'race' => $this->race,
            'class' => $this->class,
            'level' => $this->level,
            'gender' => $this->gender,
            'alignment' => $this->alignment,
            'background' => $this->background,
            'abilities' => $this->abilities,
            'modifiers' => $this->modifiers,
            'hit_points' => $this->hit_points,
            'armor_class' => $this->armor_class,
            'speed' => $this->speed,
            'initiative' => $this->initiative,
            'proficiency_bonus' => $this->proficiency_bonus,
            'equipment' => $this->equipment,
            'spells' => $this->spells,
            'potions' => $this->potions,
            'personality' => $this->personality,
            'description' => $this->description,
            'background_story' => $this->background_story
        ];
    }
    
    /**
     * Валидирует данные персонажа
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->name)) {
            $errors[] = 'Имя персонажа не может быть пустым';
        }
        
        if (empty($this->race)) {
            $errors[] = 'Раса персонажа не может быть пустой';
        }
        
        if (empty($this->class)) {
            $errors[] = 'Класс персонажа не может быть пустым';
        }
        
        if ($this->level < 1 || $this->level > 20) {
            $errors[] = 'Уровень персонажа должен быть от 1 до 20';
        }
        
        if ($this->hit_points <= 0) {
            $errors[] = 'Хиты персонажа должны быть больше 0';
        }
        
        if ($this->armor_class < 0) {
            $errors[] = 'Класс доспеха не может быть отрицательным';
        }
        
        return $errors;
    }
    
    /**
     * Вычисляет общий модификатор атаки
     */
    public function getAttackModifier($ability) {
        $abilityModifier = $this->modifiers[$ability] ?? 0;
        return $abilityModifier + $this->proficiency_bonus;
    }
    
    /**
     * Вычисляет модификатор спасброска
     */
    public function getSavingThrowModifier($ability) {
        $abilityModifier = $this->modifiers[$ability] ?? 0;
        // Здесь можно добавить проверку на владение спасброском
        return $abilityModifier;
    }
    
    /**
     * Получает модификатор навыка
     */
    public function getSkillModifier($skill) {
        // Здесь можно добавить логику для навыков
        $ability = $this->getSkillAbility($skill);
        return $this->modifiers[$ability] ?? 0;
    }
    
    /**
     * Определяет характеристику для навыка
     */
    private function getSkillAbility($skill) {
        $skillAbilities = [
            'athletics' => 'str',
            'acrobatics' => 'dex',
            'sleight_of_hand' => 'dex',
            'stealth' => 'dex',
            'arcana' => 'int',
            'history' => 'int',
            'investigation' => 'int',
            'nature' => 'int',
            'religion' => 'int',
            'animal_handling' => 'wis',
            'insight' => 'wis',
            'medicine' => 'wis',
            'perception' => 'wis',
            'survival' => 'wis',
            'deception' => 'cha',
            'intimidation' => 'cha',
            'performance' => 'cha',
            'persuasion' => 'cha'
        ];
        
        return $skillAbilities[$skill] ?? 'str';
    }
}
?>
