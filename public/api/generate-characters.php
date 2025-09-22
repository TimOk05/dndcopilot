<?php
/**
 * API для генерации персонажа D&D 5e через DeepSeek
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/error-handler.php';
require_once __DIR__ . '/../../app/Services/CharacterService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // Мини-валидация полей
        $required = ['class','race','alignment','level','gender','background'];
        $missing = [];
        foreach ($required as $field) {
            if (!isset($input[$field]) || $input[$field] === '') {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            ErrorHandler::apiError('Отсутствуют поля: ' . implode(', ', $missing), 400);
        }

        $service = new CharacterService();
        $character = $service->generateCharacter([
            'class' => $input['class'],
            'race' => $input['race'],
            'alignment' => $input['alignment'],
            'level' => (int)$input['level'],
            'gender' => $input['gender'],
            'background' => $input['background']
        ]);

        logMessage('INFO', 'Character generated', [
            'class' => $input['class'],
            'race' => $input['race'],
            'level' => (int)$input['level']
        ]);

        echo json_encode([
            'success' => true,
            'character' => $character
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        ErrorHandler::apiError('Ошибка генерации персонажа: ' . $e->getMessage(), 500);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не разрешен. Используйте POST запрос.'
    ], JSON_UNESCAPED_UNICODE);
}
?>


