<?php
/**
 * Full Character Generation API
 * Полноценная генерация персонажей из внешних источников без fallback данных
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Services/FullCharacterService.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Не авторизован'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        logMessage('INFO', 'Начинаем полную генерацию персонажа', $_POST);
        
        // Создаем сервис полной генерации
        $fullCharacterService = new FullCharacterService();
        
        // Генерируем персонажа
        $result = $fullCharacterService->generateFullCharacter($_POST);
        
        // Логируем результат
        if ($result['success']) {
            logMessage('INFO', 'Полная генерация персонажа завершена успешно', [
                'race' => $_POST['race'] ?? 'unknown',
                'class' => $_POST['class'] ?? 'unknown',
                'level' => $_POST['level'] ?? 1
            ]);
        } else {
            logMessage('ERROR', 'Ошибка полной генерации персонажа', [
                'error' => $result['error'] ?? 'unknown',
                'message' => $result['message'] ?? 'unknown'
            ]);
        }
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        logMessage('ERROR', 'Исключение при полной генерации персонажа: ' . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'error' => 'Внутренняя ошибка сервера',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Только POST запросы поддерживаются'
    ], JSON_UNESCAPED_UNICODE);
}
?>
