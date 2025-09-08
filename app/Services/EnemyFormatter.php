<?php

/**
 * Сервис форматирования данных монстров
 * Выделен из EnemyGenerator для улучшения читаемости кода
 */
class EnemyFormatter {
    private $translationService;
    
    public function __construct() {
        $this->translationService = TranslationService::getInstance();
    }
    
    /**
     * Форматирование хитов
     */
    public function formatHitPoints($hp) {
        if (is_array($hp)) {
            if (isset($hp['average'])) {
                return $hp['average'];
            } elseif (isset($hp[0])) {
                return $hp[0];
            }
            return 'Не определено';
        }
        return $hp;
    }
    
    /**
     * Форматирование класса брони
     */
    public function formatArmorClass($ac) {
        if (is_array($ac)) {
            if (isset($ac[0]['value'])) {
                return $ac[0]['value'];
            } elseif (isset($ac[0])) {
                return $ac[0];
            }
            return 'Не определено';
        }
        return $ac;
    }
    
    /**
     * Форматирование скорости
     */
    public function formatSpeed($speed) {
        if (is_array($speed)) {
            $formatted = [];
            foreach ($speed as $type => $value) {
                if (is_string($type)) {
                    $translatedType = $this->translationService->translateSpeedType($type);
                    $translatedValue = $this->translationService->translateSpeedValue($value);
                    $formatted[] = "$translatedType: $translatedValue";
                } else {
                    $formatted[] = $this->translationService->translateSpeedValue($value);
                }
            }
            return implode(', ', $formatted);
        }
        return $this->translationService->translateSpeedValue($speed);
    }
    
    /**
     * Форматирование характеристик
     */
    public function formatAbilities($monster) {
        $abilities = [
            'str' => $monster['strength'] ?? 10,
            'dex' => $monster['dexterity'] ?? 10,
            'con' => $monster['constitution'] ?? 10,
            'int' => $monster['intelligence'] ?? 10,
            'wis' => $monster['wisdom'] ?? 10,
            'cha' => $monster['charisma'] ?? 10
        ];
        
        return $abilities;
    }
    
    /**
     * Форматирование действий
     */
    public function formatActions($actions) {
        if (empty($actions)) {
            return [];
        }
        
        $formatted = [];
        foreach ($actions as $action) {
            if (isset($action['name']) && isset($action['desc'])) {
                $formatted[] = [
                    'name' => $action['name'],
                    'description' => $action['desc']
                ];
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование специальных способностей
     */
    public function formatSpecialAbilities($abilities) {
        if (empty($abilities)) {
            return [];
        }
        
        $formatted = [];
        foreach ($abilities as $ability) {
            if (isset($ability['name']) && isset($ability['desc'])) {
                $formatted[] = [
                    'name' => $ability['name'],
                    'description' => $ability['desc']
                ];
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование легендарных действий
     */
    public function formatLegendaryActions($actions) {
        if (empty($actions)) {
            return [];
        }
        
        $formatted = [];
        foreach ($actions as $action) {
            if (isset($action['name']) && isset($action['desc'])) {
                $formatted[] = [
                    'name' => $action['name'],
                    'description' => $action['desc']
                ];
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование действий логова
     */
    public function formatLairActions($actions) {
        if (empty($actions)) {
            return [];
        }
        
        $formatted = [];
        foreach ($actions as $action) {
            if (isset($action['name']) && isset($action['desc'])) {
                $formatted[] = [
                    'name' => $action['name'],
                    'description' => $action['desc']
                ];
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование модификаторов урона
     */
    public function formatDamageModifiers($modifiers) {
        if (empty($modifiers)) {
            return [];
        }
        
        $formatted = [];
        foreach ($modifiers as $modifier) {
            $formatted[] = $this->translationService->translateDamageType($modifier);
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование иммунитетов к состояниям
     */
    public function formatConditionImmunities($immunities) {
        if (empty($immunities)) {
            return [];
        }
        
        $formatted = [];
        foreach ($immunities as $immunity) {
            if (isset($immunity['name'])) {
                $formatted[] = $this->translationService->translateCondition($immunity['name']);
            } else {
                $formatted[] = $this->translationService->translateCondition($immunity);
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование чувств
     */
    public function formatSenses($senses) {
        if (empty($senses)) {
            return [];
        }
        
        $formatted = [];
        foreach ($senses as $sense => $value) {
            if (is_string($sense)) {
                $translatedSense = $this->translationService->translateSense($sense);
                $formatted[] = "$translatedSense: $value";
            } else {
                $formatted[] = $value;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Форматирование языков
     */
    public function formatLanguages($languages) {
        if (empty($languages)) {
            return ['Общий'];
        }
        
        if (is_array($languages)) {
            return $languages;
        }
        
        return [$languages];
    }
    
    /**
     * Получение среды обитания монстра
     */
    public function getMonsterEnvironment($monster, $environmentData) {
        $monsterIndex = $monster['index'] ?? '';
        
        if (isset($environmentData[$monsterIndex])) {
            $env = $environmentData[$monsterIndex];
            return $this->translationService->translateEnvironment($env);
        }
        
        return null;
    }
    
    /**
     * Форматирование полного объекта противника
     */
    public function formatEnemy($monster, $environmentData) {
        $enemy = [
            'name' => $monster['name'],
            'type' => $this->translationService->translateCreatureType($monster['type']),
            'challenge_rating' => $monster['challenge_rating'],
            'hit_points' => $this->formatHitPoints($monster['hit_points'] ?? 'Не определено'),
            'armor_class' => $this->formatArmorClass($monster['armor_class'] ?? 'Не определено'),
            'speed' => $this->formatSpeed($monster['speed'] ?? 'Не определено'),
            'abilities' => $this->formatAbilities($monster),
            'actions' => $this->formatActions($monster['actions'] ?? []),
            'special_abilities' => $this->formatSpecialAbilities($monster['special_abilities'] ?? []),
            'legendary_actions' => $this->formatLegendaryActions($monster['legendary_actions'] ?? []),
            'lair_actions' => $this->formatLairActions($monster['lair_actions'] ?? []),
            'damage_vulnerabilities' => $this->formatDamageModifiers($monster['damage_vulnerabilities'] ?? []),
            'damage_resistances' => $this->formatDamageModifiers($monster['damage_resistances'] ?? []),
            'damage_immunities' => $this->formatDamageModifiers($monster['damage_immunities'] ?? []),
            'condition_immunities' => $this->formatConditionImmunities($monster['condition_immunities'] ?? []),
            'senses' => $this->formatSenses($monster['senses'] ?? []),
            'languages' => $this->formatLanguages($monster['languages'] ?? []),
            'alignment' => $monster['alignment'] ?? 'Не определено',
            'size' => $this->translationService->translateSize($monster['size'] ?? 'medium'),
            'environment' => $this->getMonsterEnvironment($monster, $environmentData) ?? 'Данные недоступны',
            'xp' => $monster['xp'] ?? 0
        ];
        
        return $enemy;
    }
}
