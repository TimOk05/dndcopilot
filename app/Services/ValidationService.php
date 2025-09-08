<?php

/**
 * Сервис для валидации данных
 * Предоставляет методы для проверки различных типов данных
 */
class ValidationService {
    private static $instance = null;
    
    private function __construct() {
        // Приватный конструктор для singleton
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Валидация email
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Валидация URL
     */
    public function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Валидация IP адреса
     */
    public function validateIp($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Валидация целого числа
     */
    public function validateInteger($value, $min = null, $max = null) {
        if (!is_numeric($value) || (int)$value != $value) {
            return false;
        }
        
        $intValue = (int)$value;
        
        if ($min !== null && $intValue < $min) {
            return false;
        }
        
        if ($max !== null && $intValue > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация числа с плавающей точкой
     */
    public function validateFloat($value, $min = null, $max = null) {
        if (!is_numeric($value)) {
            return false;
        }
        
        $floatValue = (float)$value;
        
        if ($min !== null && $floatValue < $min) {
            return false;
        }
        
        if ($max !== null && $floatValue > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация строки
     */
    public function validateString($value, $minLength = null, $maxLength = null, $pattern = null) {
        if (!is_string($value)) {
            return false;
        }
        
        $length = strlen($value);
        
        if ($minLength !== null && $length < $minLength) {
            return false;
        }
        
        if ($maxLength !== null && $length > $maxLength) {
            return false;
        }
        
        if ($pattern !== null && !preg_match($pattern, $value)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация массива
     */
    public function validateArray($value, $minCount = null, $maxCount = null, $requiredKeys = []) {
        if (!is_array($value)) {
            return false;
        }
        
        $count = count($value);
        
        if ($minCount !== null && $count < $minCount) {
            return false;
        }
        
        if ($maxCount !== null && $count > $maxCount) {
            return false;
        }
        
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Валидация JSON
     */
    public function validateJson($value) {
        if (!is_string($value)) {
            return false;
        }
        
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Валидация даты
     */
    public function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Валидация времени
     */
    public function validateTime($time, $format = 'H:i:s') {
        $d = DateTime::createFromFormat($format, $time);
        return $d && $d->format($format) === $time;
    }
    
    /**
     * Валидация даты и времени
     */
    public function validateDateTime($datetime, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $datetime);
        return $d && $d->format($format) === $datetime;
    }
    
    /**
     * Валидация файла
     */
    public function validateFile($file, $allowedTypes = [], $maxSize = null) {
        if (!is_array($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        if ($maxSize !== null && $file['size'] > $maxSize) {
            return false;
        }
        
        if (!empty($allowedTypes)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Валидация D&D расы
     */
    public function validateDndRace($race) {
        $validRaces = [
            'human', 'elf', 'dwarf', 'halfling', 'dragonborn', 'gnome',
            'half-elf', 'half-orc', 'tiefling', 'aasimar', 'genasi',
            'goliath', 'firbolg', 'kenku', 'lizardfolk', 'tabaxi',
            'triton', 'bugbear', 'goblin', 'hobgoblin', 'orc', 'yuan-ti',
            'aarakocra', 'kobold', 'minotaur', 'centaur', 'satyr',
            'shifter', 'warforged'
        ];
        
        return in_array(strtolower($race), $validRaces);
    }
    
    /**
     * Валидация D&D класса
     */
    public function validateDndClass($class) {
        $validClasses = [
            'barbarian', 'bard', 'cleric', 'druid', 'fighter', 'monk',
            'paladin', 'ranger', 'rogue', 'sorcerer', 'warlock', 'wizard',
            'artificer', 'blood hunter'
        ];
        
        return in_array(strtolower($class), $validClasses);
    }
    
    /**
     * Валидация D&D выравнивания
     */
    public function validateDndAlignment($alignment) {
        $validAlignments = [
            'lawful good', 'neutral good', 'chaotic good',
            'lawful neutral', 'neutral', 'chaotic neutral',
            'lawful evil', 'neutral evil', 'chaotic evil'
        ];
        
        return in_array(strtolower($alignment), $validAlignments);
    }
    
    /**
     * Валидация D&D характеристик
     */
    public function validateDndAbilityScore($score) {
        return $this->validateInteger($score, 1, 30);
    }
    
    /**
     * Валидация D&D уровня
     */
    public function validateDndLevel($level) {
        return $this->validateInteger($level, 1, 20);
    }
    
    /**
     * Валидация D&D CR (Challenge Rating)
     */
    public function validateDndCR($cr) {
        $validCRs = [
            '0', '1/8', '1/4', '1/2', '1', '2', '3', '4', '5', '6', '7', '8',
            '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19',
            '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30'
        ];
        
        return in_array($cr, $validCRs);
    }
    
    /**
     * Валидация D&D размера
     */
    public function validateDndSize($size) {
        $validSizes = ['tiny', 'small', 'medium', 'large', 'huge', 'gargantuan'];
        return in_array(strtolower($size), $validSizes);
    }
    
    /**
     * Валидация D&D типа существа
     */
    public function validateDndCreatureType($type) {
        $validTypes = [
            'aberration', 'beast', 'celestial', 'construct', 'dragon',
            'elemental', 'fey', 'fiend', 'giant', 'humanoid', 'monstrosity',
            'ooze', 'plant', 'undead'
        ];
        
        return in_array(strtolower($type), $validTypes);
    }
    
    /**
     * Валидация D&D среды обитания
     */
    public function validateDndEnvironment($environment) {
        $validEnvironments = [
            'arctic', 'coastal', 'desert', 'forest', 'grassland', 'hill',
            'mountain', 'swamp', 'underdark', 'underwater', 'urban'
        ];
        
        return in_array(strtolower($environment), $validEnvironments);
    }
    
    /**
     * Валидация параметров генерации персонажа
     */
    public function validateCharacterGenerationParams($params) {
        $errors = [];
        
        if (isset($params['race']) && !$this->validateDndRace($params['race'])) {
            $errors[] = 'Invalid race: ' . $params['race'];
        }
        
        if (isset($params['class']) && !$this->validateDndClass($params['class'])) {
            $errors[] = 'Invalid class: ' . $params['class'];
        }
        
        if (isset($params['alignment']) && !$this->validateDndAlignment($params['alignment'])) {
            $errors[] = 'Invalid alignment: ' . $params['alignment'];
        }
        
        if (isset($params['level']) && !$this->validateDndLevel($params['level'])) {
            $errors[] = 'Invalid level: ' . $params['level'];
        }
        
        if (isset($params['language']) && !in_array($params['language'], ['ru', 'en'])) {
            $errors[] = 'Invalid language: ' . $params['language'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Валидация параметров генерации противников
     */
    public function validateEnemyGenerationParams($params) {
        $errors = [];
        
        if (isset($params['cr']) && !$this->validateDndCR($params['cr'])) {
            $errors[] = 'Invalid CR: ' . $params['cr'];
        }
        
        if (isset($params['type']) && !$this->validateDndCreatureType($params['type'])) {
            $errors[] = 'Invalid creature type: ' . $params['type'];
        }
        
        if (isset($params['environment']) && !$this->validateDndEnvironment($params['environment'])) {
            $errors[] = 'Invalid environment: ' . $params['environment'];
        }
        
        if (isset($params['count']) && !$this->validateInteger($params['count'], 1, 10)) {
            $errors[] = 'Invalid count: ' . $params['count'];
        }
        
        if (isset($params['language']) && !in_array($params['language'], ['ru', 'en'])) {
            $errors[] = 'Invalid language: ' . $params['language'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Валидация параметров генерации таверн
     */
    public function validateTavernGenerationParams($params) {
        $errors = [];
        
        if (isset($params['count']) && !$this->validateInteger($params['count'], 1, 5)) {
            $errors[] = 'Invalid count: ' . $params['count'];
        }
        
        if (isset($params['language']) && !in_array($params['language'], ['ru', 'en'])) {
            $errors[] = 'Invalid language: ' . $params['language'];
        }
        
        if (isset($params['style']) && !in_array($params['style'], ['fantasy', 'medieval', 'modern', 'steampunk'])) {
            $errors[] = 'Invalid style: ' . $params['style'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Валидация параметров генерации зелий
     */
    public function validatePotionGenerationParams($params) {
        $errors = [];
        
        if (isset($params['count']) && !$this->validateInteger($params['count'], 1, 10)) {
            $errors[] = 'Invalid count: ' . $params['count'];
        }
        
        if (isset($params['language']) && !in_array($params['language'], ['ru', 'en'])) {
            $errors[] = 'Invalid language: ' . $params['language'];
        }
        
        if (isset($params['rarity']) && !in_array($params['rarity'], ['common', 'uncommon', 'rare', 'very_rare', 'legendary'])) {
            $errors[] = 'Invalid rarity: ' . $params['rarity'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Валидация параметров AI чата
     */
    public function validateAIChatParams($params) {
        $errors = [];
        
        if (!isset($params['message']) || !$this->validateString($params['message'], 1, 1000)) {
            $errors[] = 'Invalid message: must be 1-1000 characters';
        }
        
        if (isset($params['language']) && !in_array($params['language'], ['ru', 'en'])) {
            $errors[] = 'Invalid language: ' . $params['language'];
        }
        
        if (isset($params['context']) && !$this->validateString($params['context'], 0, 500)) {
            $errors[] = 'Invalid context: must be 0-500 characters';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Санитизация строки
     */
    public function sanitizeString($value, $maxLength = null) {
        $sanitized = trim($value);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        if ($maxLength !== null && strlen($sanitized) > $maxLength) {
            $sanitized = substr($sanitized, 0, $maxLength);
        }
        
        return $sanitized;
    }
    
    /**
     * Санитизация массива
     */
    public function sanitizeArray($array, $maxLength = null) {
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value, $maxLength);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $maxLength);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Получение списка всех доступных валидаторов
     */
    public function getAvailableValidators() {
        return [
            'email' => 'Email validation',
            'url' => 'URL validation',
            'ip' => 'IP address validation',
            'integer' => 'Integer validation',
            'float' => 'Float validation',
            'string' => 'String validation',
            'array' => 'Array validation',
            'json' => 'JSON validation',
            'date' => 'Date validation',
            'time' => 'Time validation',
            'datetime' => 'DateTime validation',
            'file' => 'File validation',
            'dnd_race' => 'D&D race validation',
            'dnd_class' => 'D&D class validation',
            'dnd_alignment' => 'D&D alignment validation',
            'dnd_ability_score' => 'D&D ability score validation',
            'dnd_level' => 'D&D level validation',
            'dnd_cr' => 'D&D CR validation',
            'dnd_size' => 'D&D size validation',
            'dnd_creature_type' => 'D&D creature type validation',
            'dnd_environment' => 'D&D environment validation'
        ];
    }
}
?>
