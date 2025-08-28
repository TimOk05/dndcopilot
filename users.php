<?php
require_once 'config.php';

// Файл для хранения пользователей
$users_file = 'users.json';
$login_attempts_file = 'login_attempts.json';

// Функция для логирования активности
function logActivity($action, $username, $ip, $success = true, $details = []) {
    $context = array_merge([
        'action' => $action,
        'username' => $username,
        'ip' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ], $details);
    
    $level = $success ? 'INFO' : 'WARNING';
    $message = "User activity: $action - $username - $ip";
    
    logMessage($message, $level, $context);
}

// Функция для проверки блокировки IP
function isIPBlocked($ip) {
    global $login_attempts_file;
    
    if (!file_exists($login_attempts_file)) {
        return false;
    }
    
    $attempts = json_decode(file_get_contents($login_attempts_file), true) ?: [];
    
    if (isset($attempts[$ip])) {
        $last_attempt = $attempts[$ip]['last_attempt'];
        $count = $attempts[$ip]['count'];
        
        // Если прошло время блокировки, сбрасываем счетчик
        if (time() - $last_attempt > LOCKOUT_TIME) {
            unset($attempts[$ip]);
            file_put_contents($login_attempts_file, json_encode($attempts));
            return false;
        }
        
        // Если превышен лимит попыток
        if ($count >= MAX_LOGIN_ATTEMPTS) {
            return true;
        }
    }
    
    return false;
}

// Функция для записи попытки входа
function recordLoginAttempt($ip, $success) {
    global $login_attempts_file;
    
    $attempts = json_decode(file_get_contents($login_attempts_file), true) ?: [];
    
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = ['count' => 0, 'last_attempt' => time()];
    }
    
    if ($success) {
        // Успешный вход - сбрасываем счетчик
        unset($attempts[$ip]);
    } else {
        // Неудачная попытка
        $attempts[$ip]['count']++;
        $attempts[$ip]['last_attempt'] = time();
    }
    
    file_put_contents($login_attempts_file, json_encode($attempts));
}

// Функция для загрузки пользователей
function loadUsers() {
    global $users_file;
    if (file_exists($users_file)) {
        $data = file_get_contents($users_file);
        return json_decode($data, true) ?: [];
    }
    return [];
}

// Функция для сохранения пользователей
function saveUsers($users) {
    global $users_file;
    file_put_contents($users_file, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// Функция для проверки сложности пароля
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Пароль должен содержать минимум " . PASSWORD_MIN_LENGTH . " символов";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Пароль должен содержать хотя бы одну заглавную букву";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Пароль должен содержать хотя бы одну строчную букву";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Пароль должен содержать хотя бы одну цифру";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Пароль должен содержать хотя бы один специальный символ";
    }
    
    return $errors;
}

// Функция для проверки лимита регистраций с IP
function isRegistrationLimitExceeded($ip) {
    $registration_attempts_file = 'registration_attempts.json';
    
    if (!file_exists($registration_attempts_file)) {
        return false;
    }
    
    $attempts = json_decode(file_get_contents($registration_attempts_file), true) ?: [];
    
    if (isset($attempts[$ip])) {
        $last_attempt = $attempts[$ip]['last_attempt'];
        $count = $attempts[$ip]['count'];
        
        // Если прошло 24 часа, сбрасываем счетчик
        if (time() - $last_attempt > 86400) {
            unset($attempts[$ip]);
            file_put_contents($registration_attempts_file, json_encode($attempts));
            return false;
        }
        
        // Максимум 1 регистрация в день с одного IP
        if ($count >= 1) {
            return true;
        }
    }
    
    return false;
}

// Функция для проверки общего количества пользователей
function isUserLimitExceeded() {
    $users = loadUsers();
    $maxUsers = 100; // Максимум 100 пользователей в системе
    
    return count($users) >= $maxUsers;
}

// Функция для проверки подозрительной активности
function isSuspiciousActivity($ip) {
    $suspicious_ips_file = 'suspicious_ips.json';
    
    if (!file_exists($suspicious_ips_file)) {
        return false;
    }
    
    $suspicious = json_decode(file_get_contents($suspicious_ips_file), true) ?: [];
    
    return isset($suspicious[$ip]) && $suspicious[$ip]['blocked_until'] > time();
}

// Функция для записи подозрительной активности
function recordSuspiciousActivity($ip, $reason) {
    $suspicious_ips_file = 'suspicious_ips.json';
    
    $suspicious = json_decode(file_get_contents($suspicious_ips_file), true) ?: [];
    
    $suspicious[$ip] = [
        'blocked_until' => time() + 86400, // Блокировка на 24 часа
        'reason' => $reason,
        'first_detected' => time()
    ];
    
    file_put_contents($suspicious_ips_file, json_encode($suspicious));
}

// Функция для записи попытки регистрации
function recordRegistrationAttempt($ip, $success) {
    $registration_attempts_file = 'registration_attempts.json';
    
    $attempts = json_decode(file_get_contents($registration_attempts_file), true) ?: [];
    
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = ['count' => 0, 'last_attempt' => time()];
    }
    
    if ($success) {
        // Успешная регистрация - увеличиваем счетчик
        $attempts[$ip]['count']++;
        $attempts[$ip]['last_attempt'] = time();
    } else {
        // Неудачная попытка - только обновляем время
        $attempts[$ip]['last_attempt'] = time();
    }
    
    file_put_contents($registration_attempts_file, json_encode($attempts));
}

// Функция для регистрации пользователя (только через Google)
function registerUser($username, $password, $email = null, $googleId = null) {
    // Проверяем, что регистрация происходит только через Google
    if (!$googleId || !$email) {
        return ['success' => false, 'message' => 'Регистрация возможна только через Google аккаунт. Используйте кнопку "Зарегистрироваться через Google".'];
    }
    
    $users = loadUsers();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Проверяем лимит регистраций с IP
    if (isRegistrationLimitExceeded($ip)) {
        logActivity('registration_blocked', $username, $ip, false, ['reason' => 'IP limit exceeded']);
        return ['success' => false, 'message' => 'Превышен лимит регистраций с вашего IP адреса. Попробуйте завтра.'];
    }

    // Проверяем общее количество пользователей
    if (isUserLimitExceeded()) {
        logActivity('registration_blocked', $username, $ip, false, ['reason' => 'User limit exceeded']);
        return ['success' => false, 'message' => 'Превышен лимит пользователей в системе. Попробуйте позже.'];
    }

    // Проверяем подозрительную активность
    if (isSuspiciousActivity($ip)) {
        logActivity('registration_blocked', $username, $ip, false, ['reason' => 'Suspicious activity detected']);
        return ['success' => false, 'message' => 'Ваша активность была отмечена как подозрительная. Попробуйте позже.'];
    }
    
    // Очищаем входные данные
    $username = sanitizeInput($username);
    $email = sanitizeInput($email);
    
    // Проверяем сложность пароля
    $passwordErrors = validatePassword($password);
    if (!empty($passwordErrors)) {
        recordRegistrationAttempt($ip, false);
        return ['success' => false, 'message' => implode(', ', $passwordErrors)];
    }
    
    // Проверяем, не существует ли уже пользователь с таким email или google_id
    foreach ($users as $user) {
        if (isset($user['email']) && hash_equals($user['email'], $email)) {
            recordRegistrationAttempt($ip, false);
            return ['success' => false, 'message' => 'Пользователь с таким email уже существует'];
        }
        if (isset($user['google_id']) && hash_equals($user['google_id'], $googleId)) {
            recordRegistrationAttempt($ip, false);
            return ['success' => false, 'message' => 'Этот Google аккаунт уже привязан к другому пользователю'];
        }
    }
    
    // Создаем нового пользователя
    $newUser = [
        'id' => uniqid(),
        'username' => $username,
        'email' => $email,
        'google_id' => $googleId,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user',
        'created_at' => date('Y-m-d H:i:s'),
        'is_active' => true,
        'login_count' => 0,
        'auth_method' => 'google_with_password'
    ];
    
    $users[] = $newUser;
    saveUsers($users);
    
    // Записываем успешную регистрацию
    recordRegistrationAttempt($ip, true);
    
    logActivity('user_registered_google_with_password', $username, $ip, true);
    
    return ['success' => true, 'message' => 'Пользователь успешно зарегистрирован'];
}

// Функция для аутентификации пользователя
function authenticateUser($username, $password) {
    $users = loadUsers();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Проверяем блокировку IP
    if (isIPBlocked($ip)) {
        logActivity('login_blocked', $username, $ip, false, ['reason' => 'IP blocked']);
        return ['success' => false, 'message' => 'Слишком много неудачных попыток входа. Попробуйте позже.'];
    }

    // Проверяем подозрительную активность
    if (isSuspiciousActivity($ip)) {
        logActivity('login_blocked', $username, $ip, false, ['reason' => 'Suspicious activity detected']);
        return ['success' => false, 'message' => 'Ваша активность была отмечена как подозрительная. Попробуйте позже.'];
    }
    
    // Ищем пользователя по email или username
    $user = null;
    foreach ($users as $u) {
        if (hash_equals($u['email'], $username) || hash_equals($u['username'], $username)) {
            $user = $u;
            break;
        }
    }
    
    if (!$user) {
        recordLoginAttempt($ip, false);
        logActivity('login_failed', $username, $ip, false, ['reason' => 'user_not_found']);
        return ['success' => false, 'message' => 'Неверное имя пользователя/email или пароль'];
    }
    
    // Проверяем, что у пользователя есть пароль (Google аккаунты с паролем)
    if (!isset($user['password_hash']) || empty($user['password_hash'])) {
        recordLoginAttempt($ip, false);
        logActivity('login_failed', $username, $ip, false, ['reason' => 'no_password_set']);
        return ['success' => false, 'message' => 'Для входа используйте Google аккаунт. У вас не установлен пароль для приложения.'];
    }
    
    // Проверяем пароль
    if (!password_verify($password, $user['password_hash'])) {
        recordLoginAttempt($ip, false);
        logActivity('login_failed', $username, $ip, false, ['reason' => 'wrong_password']);
        return ['success' => false, 'message' => 'Неверное имя пользователя или пароль'];
    }
    
    // Проверяем активность пользователя
    if (!($user['is_active'] ?? true)) {
        recordLoginAttempt($ip, false);
        logActivity('login_failed', $username, $ip, false, ['reason' => 'user_inactive']);
        return ['success' => false, 'message' => 'Аккаунт заблокирован'];
    }
    
    // Успешный вход
    recordLoginAttempt($ip, true);
    
    // Обновляем данные пользователя
    $user['last_login'] = date('Y-m-d H:i:s');
    $user['login_count'] = ($user['login_count'] ?? 0) + 1;
    
    // Обновляем пользователя в массиве
    foreach ($users as &$u) {
        if ($u['id'] === $user['id']) {
            $u = $user;
            break;
        }
    }
    
    saveUsers($users);
    
    // Устанавливаем сессию
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    logActivity('login_success', $username, $ip, true);
    
    return ['success' => true, 'message' => 'Вход выполнен успешно'];
}

// Функция для проверки авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Функция для получения текущего пользователя
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Возвращаем имя пользователя из сессии
    return $_SESSION['username'];
}

// Функция для получения данных текущего пользователя
function getCurrentUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $users = loadUsers();
    foreach ($users as $user) {
        if (isset($user['id']) && $user['id'] === $_SESSION['user_id']) {
            return $user;
        }
        // Также проверяем по имени пользователя для совместимости
        if (isset($user['username']) && $user['username'] === $_SESSION['username']) {
            return $user;
        }
    }
    
    return null;
}

// Функция для выхода из системы
function logout() {
    if (isLoggedIn()) {
        logActivity('logout', $_SESSION['username'], $_SERVER['REMOTE_ADDR'] ?? 'unknown', true);
    }
    
    session_destroy();
    return ['success' => true, 'message' => 'Выход выполнен успешно'];
}

// Функция для проверки роли пользователя
function hasRole($role) {
    $user = getCurrentUserData();
    return $user && isset($user['role']) && $user['role'] === $role;
}

// Функция для изменения пароля
function changePassword($userId, $currentPassword, $newPassword) {
    $users = loadUsers();
    
    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            if (password_verify($currentPassword, $user['password_hash'])) {
                // Проверяем сложность нового пароля
                $passwordErrors = validatePassword($newPassword);
                if (!empty($passwordErrors)) {
                    return ['success' => false, 'errors' => $passwordErrors];
                }
                
                $user['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                $user['password_changed_at'] = date('Y-m-d H:i:s');
                
                saveUsers($users);
                
                logActivity('password_changed', $user['username'], $_SERVER['REMOTE_ADDR'] ?? 'unknown', true);
                
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Неверный текущий пароль'];
            }
        }
    }
    
    return ['success' => false, 'error' => 'Пользователь не найден'];
}

// Функция для проверки прав администратора
function isAdmin() {
    // Проверяем, есть ли флаг администратора в сессии
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        return true;
    }
    
    // Проверяем роль пользователя
    $user = getCurrentUserData();
    return $user && isset($user['role']) && $user['role'] === 'admin';
}

// Функция для проверки пароля администратора
function checkAdminPassword($password) {
    // Здесь можно настроить пароль администратора
    // Для безопасности рекомендуется хранить хеш пароля в конфигурации
    $adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123'; // Пароль по умолчанию
    
    // Проверяем хеш пароля
    if (password_verify($password, password_hash($adminPassword, PASSWORD_DEFAULT))) {
        return true;
    }
    
    // Для обратной совместимости проверяем прямой пароль
    if (hash_equals($password, $adminPassword)) {
        return true;
    }
    
    return false;
}

// Функция для удаления пользователя (только для администратора)
function deleteUser($userId) {
    if (!hasRole('admin')) {
        return ['success' => false, 'error' => 'Недостаточно прав'];
    }
    
    $users = loadUsers();
    $deletedUser = null;
    
    foreach ($users as $key => $user) {
        if ($user['id'] === $userId) {
            $deletedUser = $user;
            unset($users[$key]);
            break;
        }
    }
    
    if ($deletedUser) {
        $users = array_values($users); // Переиндексируем массив
        saveUsers($users);
        
        logActivity('user_deleted', $deletedUser['username'], $_SERVER['REMOTE_ADDR'] ?? 'unknown', true, ['deleted_by' => $_SESSION['username']]);
        
        return ['success' => true, 'user' => $deletedUser];
    }
    
    return ['success' => false, 'error' => 'Пользователь не найден'];
}

// Функция для получения статистики пользователей
function getUserStats() {
    $users = loadUsers();
    $stats = [
        'total_users' => count($users),
        'active_users' => 0,
        'recent_logins' => 0,
        'admin_users' => 0
    ];
    
    $recentTime = time() - (7 * 24 * 60 * 60); // 7 дней
    
    foreach ($users as $user) {
        if ($user['is_active'] ?? true) {
            $stats['active_users']++;
        }
        
        if ($user['role'] === 'admin') {
            $stats['admin_users']++;
        }
        
        if (isset($user['last_login'])) {
            $lastLogin = strtotime($user['last_login']);
            if ($lastLogin > $recentTime) {
                $stats['recent_logins']++;
            }
        }
    }
    
    return $stats;
}

// Обработка POST запросов для API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Неизвестное действие'];
    
    try {
        switch ($action) {
            case 'login':
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                // Проверяем CSRF токен
                if (!verifyCSRFToken($csrfToken)) {
                    $response = ['success' => false, 'message' => 'Ошибка безопасности. Обновите страницу.'];
                    break;
                }
                
                if (empty($username) || empty($password)) {
                    $response = ['success' => false, 'message' => 'Заполните все поля'];
                    break;
                }
                
                $result = authenticateUser($username, $password);
                $response = $result;
                break;
                

                
            case 'logout':
                $result = logout();
                $response = $result;
                break;
                
            case 'admin_login':
                $password = $_POST['password'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';
                
                // Проверяем CSRF токен
                if (!verifyCSRFToken($csrfToken)) {
                    $response = ['success' => false, 'message' => 'Ошибка безопасности. Обновите страницу.'];
                    break;
                }
                
                if (empty($password)) {
                    $response = ['success' => false, 'message' => 'Введите пароль администратора'];
                    break;
                }
                
                if (checkAdminPassword($password)) {
                    $_SESSION['is_admin'] = true;
                    logActivity('admin_login', 'admin', $_SERVER['REMOTE_ADDR'] ?? 'unknown', true);
                    $response = ['success' => true, 'message' => 'Доступ администратора предоставлен'];
                } else {
                    logActivity('admin_login_failed', 'admin', $_SERVER['REMOTE_ADDR'] ?? 'unknown', false);
                    $response = ['success' => false, 'message' => 'Неверный пароль администратора'];
                }
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Неизвестное действие'];
        }
    } catch (Exception $e) {
        logMessage('User API error: ' . $e->getMessage(), 'ERROR', [
            'action' => $action,
            'trace' => $e->getTraceAsString()
        ]);
        $response = ['success' => false, 'message' => 'Внутренняя ошибка сервера'];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
