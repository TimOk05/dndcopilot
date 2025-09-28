<?php
/**
 * API для сохранения заметок
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

try {
    $content = $_POST['content'] ?? '';
    $title = $_POST['title'] ?? '';
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Пустое содержимое']);
        exit;
    }
    
    // Инициализируем массив заметок, если его нет
    if (!isset($_SESSION['notes'])) {
        $_SESSION['notes'] = [];
    }
    
    // Если есть заголовок, добавляем его в начало заметки
    if ($title) {
        $content = "<h3>$title</h3>" . $content;
    }
    
    $_SESSION['notes'][] = $content;
    
    echo json_encode(['success' => true, 'message' => 'Заметка сохранена']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
}
?>
