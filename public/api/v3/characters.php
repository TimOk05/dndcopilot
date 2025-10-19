<?php
/**
 * Унифицированный API для работы с персонажами D&D 5e
 * Версия 3.0 - Улучшенная архитектура
 */

header('Content-Type: application/json');

// Подключаем конфигурацию
require_once __DIR__ . '/../../../config/config.php';

// Подключаем сервисы
require_once __DIR__ . '/../../../app/Controllers/UnifiedController.php';
require_once __DIR__ . '/../../../app/Services/UnifiedCharacterService.php';

// Создаем контроллер
$characterController = new UnifiedController();

// Получаем метод запроса
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Получаем параметры
$input = [];
if ($method === 'POST') {
    $input = $_POST;
} elseif ($method === 'GET') {
    $input = $_GET;
}

// Получаем действие
$action = $input['action'] ?? 'generate';

try {
    switch ($action) {
        case 'generate':
            if ($method !== 'POST') {
                throw new Exception('Для генерации персонажа используйте POST запрос');
            }
            
            // Валидация входных данных
            $race = $input['race'] ?? 'human';
            $class = $input['class'] ?? 'fighter';
            $level = isset($input['level']) ? (int)$input['level'] : 1;
            $gender = $input['gender'] ?? 'random';
            $alignment = $input['alignment'] ?? 'random';
            $subrace = $input['subrace'] ?? '';
            $archetype = $input['archetype'] ?? '';
            
            // Валидация уровня
            if ($level < 1 || $level > 20) {
                throw new Exception('Уровень персонажа должен быть от 1 до 20');
            }
            
            // Генерируем персонажа
            $character = $characterController->generateCharacter([
                'race' => $race,
                'class' => $class,
                'level' => $level,
                'gender' => $gender,
                'alignment' => $alignment,
                'subrace' => $subrace,
                'archetype' => $archetype
            ]);
            
            // Логируем успешную генерацию
            logMessage('INFO', 'Character generated successfully', [
                'race' => $race,
                'class' => $class,
                'level' => $level,
                'name' => $character['name']
            ]);
            
            // Возвращаем результат
            echo json_encode([
                'success' => true,
                'character' => $character,
                'meta' => [
                    'race' => $race,
                    'class' => $class,
                    'level' => $level,
                    'generated_at' => date('Y-m-d H:i:s')
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'races':
            $races = $characterController->getRaces();
            echo json_encode([
                'success' => true,
                'races' => $races
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'classes':
            $classes = $characterController->getClasses();
            echo json_encode([
                'success' => true,
                'classes' => $classes
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'race':
            $raceId = $input['id'] ?? '';
            if (!$raceId) {
                throw new Exception('Не указан ID расы');
            }
            
            $race = $characterController->getRaceById($raceId);
            if (!$race) {
                throw new Exception('Раса не найдена');
            }
            
            echo json_encode([
                'success' => true,
                'race' => $race
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'class':
            $classId = $input['id'] ?? '';
            if (!$classId) {
                throw new Exception('Не указан ID класса');
            }
            
            $class = $characterController->getClassById($classId);
            if (!$class) {
                throw new Exception('Класс не найден');
            }
            
            echo json_encode([
                'success' => true,
                'class' => $class
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'test':
            // Тестовая генерация
            $testCharacter = $characterController->generateCharacter([
                'race' => 'human',
                'class' => 'fighter',
                'level' => 1,
                'gender' => 'random',
                'alignment' => 'random'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'API работает корректно',
                'test_character' => $testCharacter,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'stats':
            // Статистика сервиса
            $stats = $characterController->getServiceStats();
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'subraces':
            $raceId = $input['race'] ?? '';
            if (!$raceId) {
                throw new Exception('Не указан ID расы');
            }
            
            $subraces = $characterController->getSubraces($raceId);
            echo json_encode([
                'success' => true,
                'subraces' => $subraces
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'archetypes':
            $classId = $input['class'] ?? '';
            if (!$classId) {
                throw new Exception('Не указан ID класса');
            }
            
            $archetypes = $characterController->getArchetypes($classId);
            echo json_encode([
                'success' => true,
                'archetypes' => $archetypes
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception('Неизвестное действие: ' . $action);
    }
    
} catch (Exception $e) {
    // Логируем ошибку
    logMessage('ERROR', 'API request failed', [
        'error' => $e->getMessage(),
        'action' => $action,
        'method' => $method
    ]);
    
    // Возвращаем ошибку
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'API_ERROR'
    ], JSON_UNESCAPED_UNICODE);
}
?>
