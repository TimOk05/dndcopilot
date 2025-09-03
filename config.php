<?php
// Конфигурационный файл для DnD приложения

// Настройки базы данных (если используется)
define('DB_HOST', 'localhost');
define('DB_NAME', 'dnd_app');
define('DB_USER', 'root');
define('DB_PASS', '');

// API ключи
function getApiKey($service) {
    $apiKeys = [
        'deepseek' => 'sk-1e898ddba737411e948af435d767e893', // ✅ Работающий API ключ DeepSeek
        'openai' => '',   // ❌ OpenAI временно отключен
        'google' => ''    // ❌ Google не нужен
    ];
    
    return $apiKeys[$service] ?? '';
}

// Настройки приложения
define('APP_NAME', 'DnD AI Assistant');
define('APP_VERSION', '2.0');
define('DEBUG_MODE', true);

// Настройки безопасности
define('SESSION_TIMEOUT', 3600); // 1 час
define('MAX_LOGIN_ATTEMPTS', 5);
define('PASSWORD_MIN_LENGTH', 8);

// Настройки кэширования
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 час

// Настройки логирования
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Функция для логирования
function logMessage($level, $message, $context = []) {
    if (!LOG_ENABLED) return;
    
    $logLevels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    $currentLevel = $logLevels[LOG_LEVEL] ?? 1;
    $messageLevel = $logLevels[$level] ?? 1;
    
    if ($messageLevel >= $currentLevel) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message";
        
        if (!empty($context)) {
            $logEntry .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= PHP_EOL;
        
        $logFile = __DIR__ . '/logs/app.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Функция для обработки ошибок
function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorMessage = "PHP Error [$errno]: $errstr in $errfile on line $errline";
    logMessage('ERROR', $errorMessage);
    
    if (DEBUG_MODE) {
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Внутренняя ошибка сервера'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    return true;
}

// Устанавливаем обработчик ошибок
set_error_handler('handleError');

// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Функция для проверки авторизации
function isAuthenticated() {
    session_start();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Функция для получения текущего пользователя
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    $usersFile = __DIR__ . '/users.json';
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);
        $userId = $_SESSION['user_id'];
        
        foreach ($users as $user) {
            if ($user['id'] == $userId) {
                return $user;
            }
        }
    }
    
    return null;
}

// Функция для валидации входных данных
function validateInput($data, $rules = []) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        if (!isset($data[$field]) || empty($data[$field])) {
            if (isset($rule['required']) && $rule['required']) {
                $errors[$field] = "Поле '$field' обязательно для заполнения";
            }
            continue;
        }
        
        $value = $data[$field];
        
        if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
            $errors[$field] = "Поле '$field' должно содержать минимум {$rule['min_length']} символов";
        }
        
        if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            $errors[$field] = "Поле '$field' должно содержать максимум {$rule['max_length']} символов";
        }
        
        if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
            $errors[$field] = "Поле '$field' имеет неверный формат";
        }
    }
    
    return $errors;
}

// Функция для очистки входных данных
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
    } else {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

// Функция для генерации CSRF токена
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Функция для проверки CSRF токена
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Настройки CORS
function setCORSHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Настройки AI API
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1/chat/completions');
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('GOOGLE_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent');
define('API_TIMEOUT', 15);

// Настройки D&D API
define('DND_API_URL', 'https://www.dnd5eapi.co/api');

// Проверка поддержки OpenSSL
define('OPENSSL_AVAILABLE', extension_loaded('openssl'));

// Инициализация приложения
function initApp() {
    // Устанавливаем кодировку (проверяем доступность mbstring)
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
    
    // Настраиваем временную зону
    date_default_timezone_set('Europe/Moscow');
    
    // Устанавливаем заголовки безопасности
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Настраиваем CORS
    setCORSHeaders();
    
    // Логируем запуск приложения
    logMessage('INFO', 'Application started', [
        'version' => APP_VERSION,
        'debug_mode' => DEBUG_MODE
    ]);
}

// Запускаем инициализацию
initApp();
?>
