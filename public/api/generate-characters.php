<?php
/**
 * API для генерации персонажей D&D 5e
 * Использует локальные JSON файлы и AI для создания персонажей
 */

header('Content-Type: application/json');

// Подключаем конфигурацию
require_once __DIR__ . '/../../config/config.php';

// Проверяем, что функции загружены
if (!function_exists('logMessage')) {
    function logMessage($level, $message, $context = []) {
        // Заглушка для логирования
        error_log("[$level] $message");
    }
}

// Подключаем сервисы
require_once __DIR__ . '/../../app/Services/CharacterService.php';
require_once __DIR__ . '/../../app/Services/AIService.php';

// Обработка POST запросов
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем, это запрос на сохранение заметки?
    if (isset($_POST['fast_action']) && $_POST['fast_action'] === 'save_note') {
        // Обрабатываем сохранение заметки
        session_start();
        
        $content = $_POST['content'] ?? '';
        $title = $_POST['title'] ?? '';
        
        // Инициализируем массив заметок, если его нет
        if (!isset($_SESSION['notes'])) {
            $_SESSION['notes'] = [];
        }
        
        if ($content) {
            // Если есть заголовок, добавляем его в начало заметки
            if ($title) {
                $content = "<h3>$title</h3>" . $content;
            }
            
            $_SESSION['notes'][] = $content;
            echo 'OK';
        } else {
            echo 'Ошибка: пустое содержимое';
        }
        exit;
    }
    
    try {
        // Получаем данные из запроса
        $input = $_POST; // Используем FormData из frontend
        
        // Валидация входных данных
        $race = $input['race'] ?? 'human';
        $class = $input['class'] ?? 'fighter';
        $level = isset($input['level']) ? (int)$input['level'] : 1;
        $gender = $input['gender'] ?? 'random';
        $alignment = $input['alignment'] ?? 'random';
        $subrace = $input['subrace'] ?? '';
        $archetype = $input['archetype'] ?? '';
        
        // Валидация
        $errors = [];
        
        if ($level < 1 || $level > 20) {
            $errors[] = 'Уровень персонажа должен быть от 1 до 20';
        }
        
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => 'Ошибки валидации',
                'errors' => $errors
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Создаем экземпляр сервиса
        $characterService = new CharacterService();
        
        // Генерируем персонажа
        $character = $characterService->generateCharacter([
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
        
    } catch (Exception $e) {
        // Логируем ошибку
        logMessage('ERROR', 'Character generation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Возвращаем ошибку
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при генерации персонажа: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Обработка GET запросов для получения данных
    try {
        $characterService = new CharacterService();
        
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'races':
                $races = $characterService->getRaces();
                echo json_encode([
                    'success' => true,
                    'races' => $races
                ], JSON_UNESCAPED_UNICODE);
                break;
                
            case 'classes':
                $classes = $characterService->getClasses();
                echo json_encode([
                    'success' => true,
                    'classes' => $classes
                ], JSON_UNESCAPED_UNICODE);
                break;
                
                
            case 'subraces':
                $raceId = $_GET['race'] ?? '';
                if ($raceId) {
                    $subraces = $characterService->getSubraces($raceId);
                    echo json_encode([
                        'success' => true,
                        'subraces' => $subraces
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не указан ID расы'
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
                
            case 'archetypes':
                $classId = $_GET['class'] ?? '';
                if ($classId) {
                    $archetypes = $characterService->getArchetypes($classId);
                    echo json_encode([
                        'success' => true,
                        'archetypes' => $archetypes
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не указан ID класса'
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Неизвестное действие'
                ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        logMessage('ERROR', 'API request failed', [
            'error' => $e->getMessage(),
            'action' => $action ?? 'unknown'
        ]);
        
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при получении данных: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    // Если это не POST или GET запрос, возвращаем ошибку
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не разрешен. Используйте POST или GET запрос.'
    ], JSON_UNESCAPED_UNICODE);
}
?>
