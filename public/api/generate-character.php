<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Подключаем конфигурацию
require_once __DIR__ . '/../../config/config.php';

class CharacterGenerator {
    private $dataPath;
    
    public function __construct() {
        $this->dataPath = __DIR__ . '/../../data/персонажи/';
    }
    
    /**
     * Загружает данные из JSON файла
     */
    private function loadJsonData($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Файл не найден: $filePath");
        }
        
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Ошибка парсинга JSON: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Получает список всех доступных рас
     */
    public function getRaces() {
        $racesData = $this->loadJsonData($this->dataPath . 'расы/races.json');
        $races = [];
        
        foreach ($racesData['races'] as $raceId => $race) {
            $races[] = [
                'id' => $raceId,
                'name' => $race['name'],
                'name_en' => $race['name_en'],
                'has_subraces' => !empty($race['subraces']),
                'subraces' => isset($race['subraces']) ? array_map(function($subrace) {
                    return [
                        'id' => $subrace['id'],
                        'name' => $subrace['name']
                    ];
                }, $race['subraces']) : []
            ];
        }
        
        return $races;
    }
    
    /**
     * Получает список всех доступных классов
     */
    public function getClasses() {
        $classes = [];
        $classDirs = glob($this->dataPath . 'классы/*', GLOB_ONLYDIR);
        
        foreach ($classDirs as $classDir) {
            $className = basename($classDir);
            $classFile = $classDir . '/' . $className . '.json';
            
            if (file_exists($classFile)) {
                $classData = $this->loadJsonData($classFile);
                $classInfo = $classData['class'];
                
                $classes[] = [
                    'id' => $classInfo['id'],
                    'name' => $classInfo['name']['ru'],
                    'name_en' => $classInfo['name']['en'],
                    'hit_die' => $classInfo['hit_die'],
                    'primary_abilities' => $classInfo['primary_abilities'],
                    'has_subclasses' => !empty($classInfo['subclasses']),
                    'subclasses' => isset($classInfo['subclasses']) ? array_map(function($subclass) {
                        return [
                            'name' => $subclass['name']
                        ];
                    }, $classInfo['subclasses']) : []
                ];
            }
        }
        
        return $classes;
    }
    
    /**
     * Получает данные класса по ID
     */
    public function getClassById($classId) {
        // Маппинг английских ID на русские папки
        $classMapping = [
            'fighter' => 'воин',
            'wizard' => 'волшебник',
            'rogue' => 'плут',
            'cleric' => 'жрец',
            'ranger' => 'следопыт',
            'barbarian' => 'варвар',
            'bard' => 'бард',
            'druid' => 'друид',
            'monk' => 'монах',
            'paladin' => 'паладин',
            'sorcerer' => 'чародей',
            'warlock' => 'колдун',
            'artificer' => 'изобретатель'
        ];
        
        $russianName = $classMapping[$classId] ?? $classId;
        $classFile = $this->dataPath . 'классы/' . $russianName . '/' . $russianName . '.json';
        
        if (file_exists($classFile)) {
            return $this->loadJsonData($classFile);
        }
        
        throw new Exception("Класс не найден: $classId");
    }
    
    /**
     * Генерирует персонажа по заданным параметрам
     */
    public function generateCharacterByParams($params) {
        try {
            // Загружаем данные рас и классов
            $racesData = $this->loadJsonData($this->dataPath . 'расы/races.json');
            $raceData = $racesData['races'][$params['race']];
            
            if (!$raceData) {
                throw new Exception("Раса не найдена: " . $params['race']);
            }
            
            // Получаем данные класса
            $classData = $this->getClassById($params['class']);
            $fullClassData = $classData['class'];
            
            // Выбираем подрасу если указана
            $selectedSubrace = null;
            if ($params['subrace'] && isset($raceData['subraces'])) {
                foreach ($raceData['subraces'] as $subrace) {
                    if ($subrace['id'] === $params['subrace']) {
                        $selectedSubrace = $subrace;
                        break;
                    }
                }
            }
            
            // Выбираем архетип если указан
            $selectedSubclass = null;
            if ($params['subclass'] && isset($fullClassData['subclasses'])) {
                foreach ($fullClassData['subclasses'] as $subclass) {
                    if ($subclass['name'] === $params['subclass']) {
                        $selectedSubclass = $subclass;
                        break;
                    }
                }
            }
            
            // Генерируем базовые характеристики (метод 4d6 drop lowest)
            $abilities = ['STR', 'DEX', 'CON', 'INT', 'WIS', 'CHA'];
            $abilityScores = [];
            
            foreach ($abilities as $ability) {
                $rolls = [];
                for ($i = 0; $i < 4; $i++) {
                    $rolls[] = rand(1, 6);
                }
                sort($rolls);
                array_shift($rolls); // Убираем наименьший
                $abilityScores[$ability] = array_sum($rolls);
            }
            
            // Применяем бонусы расы
            $finalScores = $abilityScores;
            
            // Бонусы основной расы
            if (isset($raceData['ability_bonuses'])) {
                foreach ($raceData['ability_bonuses'] as $bonus) {
                    if (isset($bonus['ability']) && $bonus['ability'] !== 'ALL') {
                        $finalScores[$bonus['ability']] += $bonus['bonus'];
                    } elseif ($bonus['ability'] === 'ALL') {
                        foreach ($finalScores as $ability => $score) {
                            $finalScores[$ability] += $bonus['bonus'];
                        }
                    }
                }
            }
            
            // Бонусы подрасы
            if ($selectedSubrace && isset($selectedSubrace['ability_bonuses'])) {
                foreach ($selectedSubrace['ability_bonuses'] as $bonus) {
                    if (isset($bonus['ability']) && $bonus['ability'] !== 'ALL') {
                        $finalScores[$bonus['ability']] += $bonus['bonus'];
                    }
                }
            }
            
            // Генерируем стартовое снаряжение
            $equipment = $this->generateStartingEquipment($fullClassData);
            
            // Создаем персонажа
            $character = [
                'race' => [
                    'id' => $params['race'],
                    'name' => $raceData['name'],
                    'name_en' => $raceData['name_en']
                ],
                'subrace' => $selectedSubrace ? [
                    'id' => $selectedSubrace['id'],
                    'name' => $selectedSubrace['name']
                ] : null,
                'class' => [
                    'id' => $params['class'],
                    'name' => $fullClassData['name']['ru'],
                    'name_en' => $fullClassData['name']['en'],
                    'hit_die' => $fullClassData['hit_die']
                ],
                'subclass' => $selectedSubclass ? [
                    'name' => $selectedSubclass['name']
                ] : null,
                'level' => $params['level'],
                'gender' => $params['gender'],
                'alignment' => $params['alignment'],
                'age' => $params['age'],
                'ability_scores' => $finalScores,
                'ability_modifiers' => $this->calculateModifiers($finalScores),
                'equipment' => $equipment,
                'traits' => $this->getRaceTraits($raceData, $selectedSubrace),
                'languages' => $this->getLanguages($raceData, $selectedSubrace),
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            return $character;
            
        } catch (Exception $e) {
            throw new Exception("Ошибка генерации персонажа по параметрам: " . $e->getMessage());
        }
    }
    
    /**
     * Генерирует случайного персонажа
     */
    public function generateRandomCharacter() {
        try {
            // Получаем доступные расы и классы
            $races = $this->getRaces();
            $classes = $this->getClasses();
            
            // Выбираем случайную расу
            $selectedRace = $races[array_rand($races)];
            $raceData = $this->loadJsonData($this->dataPath . 'расы/races.json');
            $fullRaceData = $raceData['races'][$selectedRace['id']];
            
            // Выбираем подрасу если есть
            $selectedSubrace = null;
            if (!empty($fullRaceData['subraces'])) {
                $selectedSubrace = $fullRaceData['subraces'][array_rand($fullRaceData['subraces'])];
            }
            
            // Выбираем случайный класс
            $selectedClass = $classes[array_rand($classes)];
            $classData = $this->getClassById($selectedClass['id']);
            $fullClassData = $classData['class'];
            
            // Выбираем архетип если есть
            $selectedSubclass = null;
            if (!empty($fullClassData['subclasses'])) {
                $selectedSubclass = $fullClassData['subclasses'][array_rand($fullClassData['subclasses'])];
            }
            
            // Генерируем базовые характеристики (метод 4d6 drop lowest)
            $abilities = ['STR', 'DEX', 'CON', 'INT', 'WIS', 'CHA'];
            $abilityScores = [];
            
            foreach ($abilities as $ability) {
                $rolls = [];
                for ($i = 0; $i < 4; $i++) {
                    $rolls[] = rand(1, 6);
                }
                sort($rolls);
                array_shift($rolls); // Убираем наименьший
                $abilityScores[$ability] = array_sum($rolls);
            }
            
            // Применяем бонусы расы
            $finalScores = $abilityScores;
            
            // Бонусы основной расы
            if (isset($fullRaceData['ability_bonuses'])) {
                foreach ($fullRaceData['ability_bonuses'] as $bonus) {
                    if (isset($bonus['ability']) && $bonus['ability'] !== 'ALL') {
                        $finalScores[$bonus['ability']] += $bonus['bonus'];
                    } elseif ($bonus['ability'] === 'ALL') {
                        foreach ($finalScores as $ability => $score) {
                            $finalScores[$ability] += $bonus['bonus'];
                        }
                    }
                }
            }
            
            // Бонусы подрасы
            if ($selectedSubrace && isset($selectedSubrace['ability_bonuses'])) {
                foreach ($selectedSubrace['ability_bonuses'] as $bonus) {
                    if (isset($bonus['ability']) && $bonus['ability'] !== 'ALL') {
                        $finalScores[$bonus['ability']] += $bonus['bonus'];
                    }
                }
            }
            
            // Генерируем стартовое снаряжение
            $equipment = $this->generateStartingEquipment($fullClassData);
            
            // Создаем персонажа
            $character = [
                'name' => '', // Поле имени оставляем пустым как просил пользователь
                'race' => [
                    'id' => $selectedRace['id'],
                    'name' => $selectedRace['name'],
                    'name_en' => $selectedRace['name_en']
                ],
                'subrace' => $selectedSubrace ? [
                    'id' => $selectedSubrace['id'],
                    'name' => $selectedSubrace['name']
                ] : null,
                'class' => [
                    'id' => $selectedClass['id'],
                    'name' => $selectedClass['name'],
                    'name_en' => $selectedClass['name_en'],
                    'hit_die' => $selectedClass['hit_die']
                ],
                'subclass' => $selectedSubclass ? [
                    'name' => $selectedSubclass['name']
                ] : null,
                'level' => 1,
                'ability_scores' => $finalScores,
                'ability_modifiers' => $this->calculateModifiers($finalScores),
                'equipment' => $equipment,
                'traits' => $this->getRaceTraits($fullRaceData, $selectedSubrace),
                'languages' => $this->getLanguages($fullRaceData, $selectedSubrace),
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            return $character;
            
        } catch (Exception $e) {
            throw new Exception("Ошибка генерации персонажа: " . $e->getMessage());
        }
    }
    
    /**
     * Генерирует стартовое снаряжение для класса
     */
    private function generateStartingEquipment($classData) {
        $equipment = [];
        
        if (isset($classData['starting_equipment']['choices'])) {
            foreach ($classData['starting_equipment']['choices'] as $choice) {
                if (!empty($choice['options'])) {
                    $selectedOption = $choice['options'][array_rand($choice['options'])];
                    $equipment[] = $selectedOption;
                }
            }
        }
        
        if (isset($classData['starting_equipment']['fixed'])) {
            $equipment = array_merge($equipment, $classData['starting_equipment']['fixed']);
        }
        
        return $equipment;
    }
    
    /**
     * Вычисляет модификаторы характеристик
     */
    private function calculateModifiers($scores) {
        $modifiers = [];
        foreach ($scores as $ability => $score) {
            $modifiers[$ability] = floor(($score - 10) / 2);
        }
        return $modifiers;
    }
    
    /**
     * Получает черты расы
     */
    private function getRaceTraits($raceData, $subrace = null) {
        $traits = [];
        
        if (isset($raceData['traits'])) {
            foreach ($raceData['traits'] as $trait) {
                $traits[] = [
                    'name' => $trait['name'],
                    'description' => $trait['description']
                ];
            }
        }
        
        if ($subrace && isset($subrace['traits'])) {
            foreach ($subrace['traits'] as $trait) {
                $traits[] = [
                    'name' => $trait['name'],
                    'description' => $trait['description']
                ];
            }
        }
        
        return $traits;
    }
    
    /**
     * Получает языки расы
     */
    private function getLanguages($raceData, $subrace = null) {
        $languages = [];
        
        if (isset($raceData['languages'])) {
            $languages = array_merge($languages, $raceData['languages']);
        }
        
        if ($subrace && isset($subrace['languages'])) {
            $languages = array_merge($languages, $subrace['languages']);
        }
        
        return array_unique($languages);
    }
}

// Обработка запросов
try {
    $generator = new CharacterGenerator();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'generate';
        
        switch ($action) {
            case 'races':
                $response = $generator->getRaces();
                break;
                
            case 'classes':
                $response = $generator->getClasses();
                break;
                
            case 'generate':
            default:
                $response = $generator->generateRandomCharacter();
                break;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Обработка POST запроса для генерации персонажа по параметрам
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            // Если JSON не получен, пробуем получить данные из FormData
            $input = [
                'race' => $_POST['race'] ?? null,
                'class' => $_POST['class'] ?? null,
                'subrace' => $_POST['subrace'] ?? null,
                'subclass' => $_POST['subclass'] ?? null,
                'level' => intval($_POST['level'] ?? 1),
                'gender' => $_POST['gender'] ?? 'male',
                'alignment' => $_POST['alignment'] ?? 'neutral',
                'age' => $_POST['age'] ?? 'adult'
            ];
        }
        
        if (!$input || !isset($input['race']) || !isset($input['class'])) {
            throw new Exception('Неверные входные данные');
        }
        
        // Генерируем персонажа по заданным параметрам
        $character = $generator->generateCharacterByParams($input);
        
        echo json_encode($character, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
