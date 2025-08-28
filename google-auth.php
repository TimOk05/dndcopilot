<?php
require_once 'config.php';
require_once 'users.php';

// Google OAuth конфигурация
define('GOOGLE_REDIRECT_URI', 'https://tim.dat-studio.com/dnd/google-auth.php');

class GoogleAuth {
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    
    public function __construct() {
        $this->clientId = GOOGLE_CLIENT_ID;
        $this->clientSecret = GOOGLE_CLIENT_SECRET;
        $this->redirectUri = GOOGLE_REDIRECT_URI;
    }
    
    /**
     * Генерация URL для авторизации Google
     */
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'email profile',
            'response_type' => 'code',
            'access_type' => 'offline'
        ];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * Обмен кода авторизации на токен доступа
     */
    public function getAccessToken($code) {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri
        ];
        
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Получение информации о пользователе Google
     */
    public function getUserInfo($accessToken) {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Проверка существования пользователя
     */
    public function checkUserExists($googleUser) {
        $users = loadUsers();
        $email = $googleUser['email'];
        $googleId = $googleUser['id'];
        
        // Ищем пользователя по email или google_id
        foreach ($users as $user) {
            if (isset($user['google_id']) && $user['google_id'] === $googleId) {
                return ['exists' => true, 'user' => $user, 'type' => 'google_id'];
            }
            if (isset($user['email']) && $user['email'] === $email) {
                return ['exists' => true, 'user' => $user, 'type' => 'email'];
            }
        }
        
        return ['exists' => false, 'user' => null, 'type' => null];
    }
    
    /**
     * Создание нового пользователя через Google
     */
    public function createUserFromGoogle($googleUser) {
        $users = loadUsers();
        $email = $googleUser['email'];
        $googleId = $googleUser['id'];
        
        $newUser = [
            'id' => uniqid(),
            'username' => $googleUser['name'] ?? $email,
            'email' => $email,
            'google_id' => $googleId,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
            'is_active' => true,
            'login_count' => 0,
            'auth_method' => 'google'
        ];
        
        $users[] = $newUser;
        saveUsers($users);
        
        logActivity('user_registered_google', $email, $_SERVER['REMOTE_ADDR'] ?? 'unknown', true);
        
        return $newUser;
    }
    
    /**
     * Автоматический вход существующего пользователя
     */
    public function loginExistingUser($user) {
        // Устанавливаем сессию
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['auth_method'] = 'google';
        
        // Обновляем данные пользователя
        $users = loadUsers();
        foreach ($users as &$u) {
            if ($u['id'] === $user['id']) {
                $u['last_login'] = date('Y-m-d H:i:s');
                $u['login_count'] = ($u['login_count'] ?? 0) + 1;
                break;
            }
        }
        saveUsers($users);
        
        logActivity('login_success_google', $user['email'], $_SERVER['REMOTE_ADDR'] ?? 'unknown', true);
        
        return true;
    }
}

// Обработка запросов
$googleAuth = new GoogleAuth();

if (isset($_GET['code'])) {
    // Получаем код авторизации от Google
    $code = $_GET['code'];
    
    try {
        // Обмениваем код на токен
        $tokenData = $googleAuth->getAccessToken($code);
        
        if (isset($tokenData['access_token'])) {
            // Получаем информацию о пользователе
            $userInfo = $googleAuth->getUserInfo($tokenData['access_token']);
            
            if ($userInfo && isset($userInfo['email'])) {
                // Проверяем существование пользователя
                $userCheck = $googleAuth->checkUserExists($userInfo);
                
                if ($userCheck['exists']) {
                    // Пользователь существует - автоматически входим
                    if ($googleAuth->loginExistingUser($userCheck['user'])) {
                        header('Location: index.php?welcome=1');
                        exit;
                    } else {
                        $error = 'Ошибка входа в систему';
                    }
                } else {
                    // Пользователь не существует - перенаправляем на форму с предзаполненными данными
                    $_SESSION['google_user_data'] = $userInfo;
                    header('Location: google-complete-registration.php');
                    exit;
                }
            } else {
                $error = 'Не удалось получить информацию о пользователе';
            }
        } else {
            $error = 'Ошибка получения токена доступа';
        }
    } catch (Exception $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
} elseif (isset($_GET['error'])) {
    $error = 'Ошибка авторизации: ' . $_GET['error'];
} else {
    // Перенаправляем на Google для авторизации
    header('Location: ' . $googleAuth->getAuthUrl());
    exit;
}

// Если произошла ошибка
if (isset($error)) {
    echo "<h1>Ошибка авторизации через Google</h1>";
    echo "<p>$error</p>";
    echo "<p><a href='login.php'>Вернуться к обычному входу</a></p>";
}
?>
