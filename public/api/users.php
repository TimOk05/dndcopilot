<?php
/**
 * API для работы с пользователями
 * Обрабатывает запросы авторизации, регистрации и выхода
 */

header('Content-Type: application/json');

// Подключаем auth.php для функций работы с пользователями
require_once __DIR__ . '/../../app/Middleware/auth.php';

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Неизвестное действие'];
    
    switch ($action) {
        case 'login':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $response = ['success' => false, 'message' => 'Заполните все поля'];
            } else {
                $response = loginUser($username, $password);
            }
            break;
            
        case 'register':
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($email) || empty($password)) {
                $response = ['success' => false, 'message' => 'Заполните все поля'];
            } else {
                $response = registerUser($username, $password, $email);
            }
            break;
            
        case 'logout':
            session_destroy();
            $response = ['success' => true, 'message' => 'Выход выполнен'];
            break;
            
        case 'admin_login':
            $password = $_POST['password'] ?? '';
            $csrf_token = $_POST['csrf_token'] ?? '';
            
            // Простая проверка админского пароля
            if ($password === 'admin123') {
                $_SESSION['admin'] = true;
                $response = ['success' => true, 'message' => 'Админ вход выполнен'];
            } else {
                $response = ['success' => false, 'message' => 'Неверный пароль'];
            }
            break;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Если это не POST запрос, возвращаем ошибку
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
?>