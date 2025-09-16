<?php
header('Content-Type: application/json');

// Простой тест AI чата
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_message':
            $message = $_POST['message'] ?? '';
            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Сообщение не может быть пустым']);
                exit;
            }
            
            // Имитируем ответ AI
            $response = "Привет! Я AI помощник для D&D. Вы написали: " . $message;
            
            echo json_encode([
                'success' => true,
                'response' => $response
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
}
?>
