<?php
/**
 * Улучшенный API для генерации персонажей на основе JSON данных
 * Использует полную базу данных рас, классов, заклинаний, зелий и снаряжения
 */

header('Content-Type: application/json');

// Подключаем конфигурацию
require_once __DIR__ . '/../../../config/config.php';

// Подключаем сервисы
require_once __DIR__ . '/../../../app/Controllers/EnhancedController.php';
require_once __DIR__ . '/../../../app/Services/EnhancedCharacterService.php';

// Создаем контроллер
$characterController = new EnhancedController();

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
            logMessage('INFO', 'Enhanced character generated successfully', [
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
                    'generated_at' => date('Y-m-d H:i:s'),
                    'generator' => 'enhanced'
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'races':
            $races = $characterController->getRaces();
            echo json_encode([
                'success' => true,
                'races' => $races,
                'count' => count($races)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'classes':
            $classes = $characterController->getClasses();
            echo json_encode([
                'success' => true,
                'classes' => $classes,
                'count' => count($classes)
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
            
        case 'spells':
            $classId = $input['class'] ?? '';
            $level = isset($input['level']) ? (int)$input['level'] : 1;
            
            if (!$classId) {
                throw new Exception('Не указан ID класса');
            }
            
            $spells = $characterController->getSpellsForClass($classId, $level);
            echo json_encode([
                'success' => true,
                'spells' => $spells,
                'class' => $classId,
                'level' => $level
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'potions':
            $count = isset($input['count']) ? (int)$input['count'] : 5;
            $potions = $characterController->getPotions($count);
            echo json_encode([
                'success' => true,
                'potions' => $potions,
                'count' => count($potions)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'equipment':
            $classId = $input['class'] ?? '';
            if (!$classId) {
                throw new Exception('Не указан ID класса');
            }
            
            $equipment = $characterController->getEquipmentForClass($classId);
            echo json_encode([
                'success' => true,
                'equipment' => $equipment,
                'class' => $classId
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
                'message' => 'Enhanced API работает корректно',
                'test_character' => $testCharacter,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'stats':
            // Статистика базы данных
            $stats = $characterController->getDatabaseStats();
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception('Неизвестное действие: ' . $action);
    }
    
} catch (Exception $e) {
    // Логируем ошибку
    logMessage('ERROR', 'Enhanced API request failed', [
        'error' => $e->getMessage(),
        'action' => $action,
        'method' => $method
    ]);
    
    // Возвращаем ошибку
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'ENHANCED_API_ERROR'
    ], JSON_UNESCAPED_UNICODE);
}
?>
