<?php

/**
 * Сервис фильтрации монстров по различным критериям
 * Выделен из EnemyGenerator для улучшения читаемости кода
 */
class EnemyFilter {
    private $translationService;
    private $environmentData;
    
    public function __construct($environmentData = []) {
        $this->translationService = TranslationService::getInstance();
        $this->environmentData = $environmentData;
    }
    
    /**
     * Фильтрация монстров по параметрам
     */
    public function filterMonsters($monsters, $crRange, $enemyType, $environment) {
        if (!isset($monsters['results']) || empty($monsters['results'])) {
            return [];
        }
        
        $filtered = [];
        $checkedCount = 0;
        $maxChecks = 500;
        
        foreach ($monsters['results'] as $monster) {
            if ($checkedCount >= $maxChecks) {
                break;
            }
            $checkedCount++;
            
            try {
                // Получаем детали монстра
                $monsterDetails = $this->getMonsterDetails($monster['index']);
                
                if (!$monsterDetails || !$this->hasCompleteData($monsterDetails)) {
                    continue;
                }
                
                // Проверяем все критерии
                if (!$this->checkCRRange($monsterDetails['challenge_rating'], $crRange)) {
                    continue;
                }
                
                if ($enemyType && !$this->checkType($monsterDetails['type'], $enemyType)) {
                    continue;
                }
                
                if ($environment && !$this->checkEnvironment($monsterDetails, $environment)) {
                    continue;
                }
                
                if (!$this->checkCompatibility($monsterDetails, $crRange)) {
                    continue;
                }
                
                $filtered[] = $monsterDetails;
                
                if (count($filtered) >= 50) {
                    break;
                }
                
            } catch (Exception $e) {
                logMessage('WARNING', "EnemyFilter: Ошибка фильтрации монстра {$monster['name']}: " . $e->getMessage());
                continue;
            }
        }
        
        logMessage('INFO', "EnemyFilter: Найдено подходящих монстров: " . count($filtered));
        return $filtered;
    }
    
    /**
     * Проверка диапазона CR
     */
    private function checkCRRange($cr, $range) {
        $crValue = $this->parseCR($cr);
        return $crValue >= $range['min'] && $crValue <= $range['max'];
    }
    
    /**
     * Проверка типа существа
     */
    private function checkType($monsterType, $requestedType) {
        $typeTranslations = [
            'гуманоид' => 'humanoid',
            'зверь' => 'beast',
            'дракон' => 'dragon',
            'великан' => 'giant',
            'нежить' => 'undead',
            'исчадие' => 'fiend',
            'небожитель' => 'celestial',
            'элементаль' => 'elemental',
            'фей' => 'fey',
            'чудовище' => 'monstrosity',
            'слизь' => 'ooze',
            'растение' => 'plant',
            'конструкт' => 'construct',
            'аберрация' => 'aberration',
            'рой' => 'swarm'
        ];
        
        $monsterTypeLower = strtolower($monsterType);
        $requestedTypeLower = strtolower($requestedType);
        
        // Если запрашиваемый тип на русском, переводим его
        if (isset($typeTranslations[$requestedTypeLower])) {
            $requestedTypeLower = $typeTranslations[$requestedTypeLower];
        }
        
        // Проверяем точное совпадение или вхождение
        if ($monsterTypeLower === $requestedTypeLower) {
            return true;
        }
        
        if (strpos($monsterTypeLower, $requestedTypeLower) !== false) {
            return true;
        }
        
        if (strpos($requestedTypeLower, $monsterTypeLower) !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Проверка среды обитания
     */
    private function checkEnvironment($monster, $requestedEnvironment) {
        $monsterIndex = $monster['index'] ?? '';
        
        if (!isset($this->environmentData[$monsterIndex])) {
            return false;
        }
        
        $monsterEnv = $this->environmentData[$monsterIndex];
        $requestedEnv = strtolower($requestedEnvironment);
        
        // Переводим русские названия сред в английские
        $envTranslations = [
            'лес' => 'forest',
            'горы' => 'mountain',
            'пустыня' => 'desert',
            'болото' => 'swamp',
            'подземелье' => 'underdark',
            'вода' => 'water',
            'город' => 'urban',
            'арктика' => 'arctic',
            'побережье' => 'coastal',
            'равнины' => 'grassland',
            'холмы' => 'hill',
            'джунгли' => 'jungle',
            'пещера' => 'cave',
            'подземелье' => 'underground',
            'водная' => 'aquatic',
            'океан' => 'ocean',
            'море' => 'sea',
            'пляж' => 'beach',
            'берег' => 'shore',
            'тундра' => 'tundra',
            'холодная' => 'cold',
            'засушливая' => 'arid',
            'болото' => 'marsh',
            'водно-болотные угодья' => 'wetland',
            'нагорье' => 'highland',
            'лесная местность' => 'woodland',
            'небесная' => 'celestial',
            'стихийная' => 'elemental'
        ];
        
        if (isset($envTranslations[$requestedEnv])) {
            $requestedEnv = $envTranslations[$requestedEnv];
        }
        
        // Прямое сравнение
        if ($monsterEnv === $requestedEnv) {
            return true;
        }
        
        // Проверяем маппинг сред
        return $this->checkEnvironmentMapping($monsterEnv, $requestedEnv);
    }
    
    /**
     * Проверка маппинга сред
     */
    private function checkEnvironmentMapping($monsterEnv, $requestedEnv) {
        $environmentMapping = [
            'forest' => ['forest', 'grassland', 'hill', 'woodland', 'jungle'],
            'mountain' => ['mountain', 'hill', 'highland'],
            'desert' => ['desert', 'arid'],
            'swamp' => ['swamp', 'marsh', 'wetland'],
            'underdark' => ['underdark', 'cave', 'underground'],
            'water' => ['aquatic', 'coastal', 'ocean', 'sea'],
            'urban' => ['urban', 'city', 'town'],
            'arctic' => ['arctic', 'tundra', 'cold'],
            'coastal' => ['coastal', 'beach', 'shore']
        ];
        
        if (isset($environmentMapping[$requestedEnv])) {
            foreach ($environmentMapping[$requestedEnv] as $env) {
                if ($monsterEnv === $env) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Проверка совместимости типа и среды с уровнем сложности
     */
    private function checkCompatibility($monster, $crRange) {
        $cr = $this->parseCR($monster['challenge_rating']);
        $type = strtolower($monster['type']);
        
        // Драконы требуют минимальный CR 1
        if (strpos($type, 'dragon') !== false && $crRange['min'] < 1) {
            return false;
        }
        
        // Великаны требуют минимальный CR 3
        if (strpos($type, 'giant') !== false && $crRange['min'] < 3) {
            return false;
        }
        
        // Звери ограничены максимальным CR 8
        if (strpos($type, 'beast') !== false && $crRange['max'] > 8) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Проверка полноты данных монстра
     */
    private function hasCompleteData($monster) {
        $requiredFields = ['name', 'type', 'challenge_rating'];
        
        foreach ($requiredFields as $field) {
            if (!isset($monster[$field]) || empty($monster[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Парсинг CR в числовое значение
     */
    private function parseCR($cr) {
        if (is_numeric($cr)) {
            return (float)$cr;
        }
        
        // Обработка дробных CR (например, "1/4", "1/2")
        if (strpos($cr, '/') !== false) {
            $parts = explode('/', $cr);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                return (float)$parts[0] / (float)$parts[1];
            }
        }
        
        // Обработка специальных случаев
        $crMap = [
            '0' => 0,
            '1/8' => 0.125,
            '1/4' => 0.25,
            '1/2' => 0.5
        ];
        
        return $crMap[$cr] ?? 0;
    }
    
    /**
     * Получение деталей монстра (заглушка - должна быть реализована в основном классе)
     */
    private function getMonsterDetails($monsterIndex) {
        // Это должно быть реализовано в основном EnemyGenerator
        // или передаваться как зависимость
        throw new Exception('getMonsterDetails должен быть реализован в основном классе');
    }
}
