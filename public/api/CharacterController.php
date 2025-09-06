<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../users.php';
require_once __DIR__ . '/CharacterService.php';
require_once __DIR__ . '/CacheService.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

class CharacterController {
    private $characterService;
    private $cacheService;
    
    public function __construct() {
        $this->characterService = new CharacterService();
        $this->cacheService = new CacheService();
    }
    
    /**
     * Обработка запроса генерации персонажа
     */
    public function generateCharacter($request) {
        try {
            // Валидация входных данных
            $validation = $this->validateRequest($request);
            if (!$validation['valid']) {
                return $this->errorResponse($validation['error'], 400);
            }
            
            // Проверяем кэш
            $cacheKey = $this->generateCacheKey($request);
            $cachedResult = $this->cacheService->get($cacheKey);
            
            if ($cachedResult !== null) {
                logMessage('Character served from cache', 'INFO', ['params' => $request]);
                return $this->successResponse($cachedResult);
            }
            
            // Генерируем персонажа
            $result = $this->characterService->generateCharacter($request);
            
            if ($result['success']) {
                // Сохраняем в кэш на 1 час
                $this->cacheService->set($cacheKey, $result['character'], 3600);
                
                logMessage('Character generated successfully', 'INFO', [
                    'race' => $request['race'],
                    'class' => $request['class'],
                    'level' => $request['level']
                ]);
                
                return $this->successResponse($result['character']);
            } else {
                return $this->errorResponse($result['error'], 500);
            }
            
        } catch (Exception $e) {
            logMessage('Character generation error: ' . $e->getMessage(), 'ERROR', [
                'request' => $request,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Внутренняя ошибка сервера', 500);
        }
    }
    
    /**
     * Обработка запроса получения списка рас
     */
    public function getRaces() {
        $cacheKey = 'races_list';
        $cachedRaces = $this->cacheService->get($cacheKey);
        
        if ($cachedRaces !== null) {
            return $this->successResponse($cachedRaces);
        }
        
        $races = [
            'human' => 'Человек',
            'elf' => 'Эльф',
            'dwarf' => 'Дварф',
            'halfling' => 'Полурослик',
            'orc' => 'Орк',
            'tiefling' => 'Тифлинг',
            'dragonborn' => 'Драконорожденный',
            'gnome' => 'Гном',
            'half-elf' => 'Полуэльф',
            'half-orc' => 'Полуорк',
            'tabaxi' => 'Табакси',
            'aarakocra' => 'Ааракокра',
            'goblin' => 'Гоблин',
            'kenku' => 'Кенку',
            'lizardfolk' => 'Ящеролюд',
            'triton' => 'Тритон',
            'yuan-ti' => 'Юань-ти',
            'goliath' => 'Голиаф',
            'firbolg' => 'Фирболг',
            'bugbear' => 'Багбир',
            'hobgoblin' => 'Хобгоблин',
            'kobold' => 'Кобольд'
        ];
        
        $this->cacheService->set($cacheKey, $races, 86400); // 24 часа
        
        return $this->successResponse($races);
    }
    
    /**
     * Обработка запроса получения списка классов
     */
    public function getClasses() {
        $cacheKey = 'classes_list';
        $cachedClasses = $this->cacheService->get($cacheKey);
        
        if ($cachedClasses !== null) {
            return $this->successResponse($cachedClasses);
        }
        
        $classes = [
            'fighter' => 'Воин',
            'wizard' => 'Волшебник',
            'rogue' => 'Плут',
            'cleric' => 'Жрец',
            'ranger' => 'Следопыт',
            'barbarian' => 'Варвар',
            'bard' => 'Бард',
            'druid' => 'Друид',
            'monk' => 'Монах',
            'paladin' => 'Паладин',
            'sorcerer' => 'Чародей',
            'warlock' => 'Колдун',
            'artificer' => 'Изобретатель'
        ];
        
        $this->cacheService->set($cacheKey, $classes, 86400); // 24 часа
        
        return $this->successResponse($classes);
    }
    
    /**
     * Обработка запроса получения статистики
     */
    public function getStats() {
        $cacheStats = $this->cacheService->getStats();
        
        return $this->successResponse([
            'cache' => $cacheStats,
            'app_version' => APP_VERSION,
            'environment' => ENVIRONMENT,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Очистка кэша
     */
    public function clearCache() {
        $deleted = $this->cacheService->clear();
        
        logMessage('Cache cleared manually', 'INFO', ['deleted_files' => $deleted]);
        
        return $this->successResponse([
            'message' => 'Кэш очищен',
            'deleted_files' => $deleted
        ]);
    }
    
    /**
     * Валидация запроса
     */
    private function validateRequest($request) {
        $requiredFields = ['race', 'class', 'level'];
        
        foreach ($requiredFields as $field) {
            if (!isset($request[$field]) || empty($request[$field])) {
                return ['valid' => false, 'error' => "Поле '$field' обязательно для заполнения"];
            }
        }
        
        // Валидация уровня
        if (!is_numeric($request['level']) || $request['level'] < 1 || $request['level'] > 20) {
            return ['valid' => false, 'error' => 'Уровень должен быть числом от 1 до 20'];
        }
        
        // Валидация расы
        $validRaces = ['human', 'elf', 'dwarf', 'halfling', 'orc', 'tiefling', 'dragonborn', 'gnome', 'half-elf', 'half-orc', 'tabaxi', 'aarakocra', 'goblin', 'kenku', 'lizardfolk', 'triton', 'yuan-ti', 'goliath', 'firbolg', 'bugbear', 'hobgoblin', 'kobold'];
        if (!in_array($request['race'], $validRaces)) {
            return ['valid' => false, 'error' => 'Неверная раса'];
        }
        
        // Валидация класса
        $validClasses = ['fighter', 'wizard', 'rogue', 'cleric', 'ranger', 'barbarian', 'bard', 'druid', 'monk', 'paladin', 'sorcerer', 'warlock', 'artificer'];
        if (!in_array($request['class'], $validClasses)) {
            return ['valid' => false, 'error' => 'Неверный класс'];
        }
        
        // Валидация пола (если указан)
        if (isset($request['gender']) && !in_array($request['gender'], ['male', 'female', 'random'])) {
            return ['valid' => false, 'error' => 'Неверный пол'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Генерация ключа кэша
     */
    private function generateCacheKey($request) {
        $keyData = [
            'race' => $request['race'],
            'class' => $request['class'],
            'level' => $request['level'],
            'gender' => $request['gender'] ?? 'random'
        ];
        
        return 'character_' . md5(json_encode($keyData));
    }
    
    /**
     * Формирование успешного ответа
     */
    private function successResponse($data) {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Формирование ответа с ошибкой
     */
    private function errorResponse($message, $code = 400) {
        return [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Обработка запросов
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    
    $controller = new CharacterController();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'generate':
                if ($method === 'POST') {
                    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                    $result = $controller->generateCharacter($input);
                } else {
                    $result = ['success' => false, 'error' => 'Метод не поддерживается', 'code' => 405, 'timestamp' => date('Y-m-d H:i:s')];
                }
                break;
                
            case 'races':
                $result = $controller->getRaces();
                break;
                
            case 'classes':
                $result = $controller->getClasses();
                break;
                
            case 'stats':
                $result = $controller->getStats();
                break;
                
            case 'clear-cache':
                if (function_exists('hasRole') && hasRole('admin')) {
                    $result = $controller->clearCache();
                } else {
                    $result = ['success' => false, 'error' => 'Недостаточно прав', 'code' => 403, 'timestamp' => date('Y-m-d H:i:s')];
                }
                break;
                
            default:
                $result = ['success' => false, 'error' => 'Неизвестное действие', 'code' => 404, 'timestamp' => date('Y-m-d H:i:s')];
        }
        
        http_response_code($result['code'] ?? 200);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        logMessage('Controller error: ' . $e->getMessage(), 'ERROR', [
            'action' => $action,
            'method' => $method,
            'trace' => $e->getTraceAsString()
        ]);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Внутренняя ошибка сервера',
            'code' => 500,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>
