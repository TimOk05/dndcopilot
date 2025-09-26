<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Простая система пользователей в JSON файле
$users_file = __DIR__ . '/../../data/users.json';

// Функция загрузки пользователей
function loadUsers() {
    global $users_file;
    if (file_exists($users_file)) {
        $data = file_get_contents($users_file);
        return json_decode($data, true) ?: [];
    }
    return [];
}

// Функция сохранения пользователей
function saveUsers($users) {
    global $users_file;
    file_put_contents($users_file, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// Функция регистрации
function registerUser($username, $password, $email) {
    $users = loadUsers();
    
    // Проверяем, не существует ли уже пользователь
    foreach ($users as $user) {
        if ($user['username'] === $username || $user['email'] === $email) {
            return ['success' => false, 'message' => 'Пользователь уже существует'];
        }
    }
    
    // Создаем нового пользователя
    $newUser = [
        'id' => uniqid(),
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
        'is_active' => true
    ];
    
    $users[] = $newUser;
    saveUsers($users);
    
    return ['success' => true, 'message' => 'Регистрация успешна!'];
}

// Функция входа
function loginUser($username, $password) {
    $users = loadUsers();
    
    // Ищем пользователя по username или email
    $user = null;
    foreach ($users as $u) {
        if ($u['username'] === $username || $u['email'] === $username) {
            $user = $u;
            break;
        }
    }
    
    if (!$user) {
        return ['success' => false, 'message' => 'Пользователь не найден'];
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Неверный пароль'];
    }
    
    // Устанавливаем сессию
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    return ['success' => true, 'message' => 'Вход выполнен успешно'];
}

// Функция проверки авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Обработка POST запросов
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
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
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
