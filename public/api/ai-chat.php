<?php
header('Content-Type: application/json; charset=utf-8');

// Новый простой AI чат
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_message':
            $message = $_POST['message'] ?? '';
            $language = $_POST['language'] ?? 'ru';
            
            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Сообщение не может быть пустым'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Проверяем доступность cURL
            if (!function_exists('curl_init')) {
                $response = "Привет! Я AI помощник для D&D. К сожалению, AI API временно недоступен (отсутствует поддержка cURL), но я могу помочь с базовыми вопросами по D&D. Что вас интересует?";
            } else {
                // Здесь можно добавить реальный вызов AI API
                $response = "Привет! Я AI помощник для D&D. Вы написали: " . $message . ". В данный момент AI API недоступен, но я могу помочь с базовыми вопросами по D&D.";
            }
            
            echo json_encode([
                'success' => true,
                'response' => $response
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get_history':
            echo json_encode([
                'success' => true,
                'history' => []
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
}
?>
