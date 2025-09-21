<?php
/**
 * API для генерации заклинаний D&D 5e
 * Поддерживает фильтрацию по уровню и классу
 */

header('Content-Type: application/json');

// Подключаем конфигурацию
require_once __DIR__ . '/../../config/config.php';

// Подключаем сервис заклинаний
require_once __DIR__ . '/../../app/Services/SpellService.php';

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Получаем данные из запроса
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        // Валидация входных данных
        $level = isset($input['level']) ? (int)$input['level'] : null;
        $class = $input['class'] ?? null;
        $count = isset($input['count']) ? (int)$input['count'] : 1;
        
        // Валидация
        $errors = [];
        
        if ($level === null || $level < 0 || $level > 9) {
            $errors[] = 'Уровень заклинания должен быть от 0 до 9';
        }
        
        if ($count < 1 || $count > 5) {
            $errors[] = 'Количество заклинаний должно быть от 1 до 5';
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
        $spellService = new SpellService();
        
        // Генерируем заклинания
        $spells = $spellService->generateSpells($level, $class, $count);
        
        // Логируем успешную генерацию
        logMessage('INFO', 'Spells generated successfully', [
            'level' => $level,
            'class' => $class,
            'count' => $count,
            'generated_count' => count($spells)
        ]);
        
        // Возвращаем результат
        echo json_encode([
            'success' => true,
            'spells' => $spells,
            'meta' => [
                'level' => $level,
                'class' => $class,
                'requested_count' => $count,
                'generated_count' => count($spells)
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // Логируем ошибку
        logMessage('ERROR', 'Spell generation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Возвращаем ошибку
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при генерации заклинаний: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    // Если это не POST запрос, возвращаем ошибку
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не разрешен. Используйте POST запрос.'
    ], JSON_UNESCAPED_UNICODE);
}
?>
