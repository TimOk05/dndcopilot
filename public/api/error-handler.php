<?php
/**
 * Централизованная обработка ошибок для D&D генераторов
 */

class ErrorHandler {
    private static $logFile = __DIR__ . '/../../data/logs/api_errors.log';
    
    /**
     * Инициализация обработчика ошибок
     */
    public static function init() {
        // Создаем директорию для логов если её нет
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Устанавливаем обработчик ошибок
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }
    
    /**
     * Обработка PHP ошибок
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $error = [
            'type' => 'PHP Error',
            'level' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
        
        // Возвращаем true чтобы предотвратить стандартную обработку ошибки
        return true;
    }
    
    /**
     * Обработка исключений
     */
    public static function handleException($exception) {
        $error = [
            'type' => 'Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
    }
    
    /**
     * Логирование ошибки
     */
    public static function logError($error) {
        $logEntry = json_encode($error, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n---\n";
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Форматированный ответ с ошибкой для API
     */
    public static function apiError($message, $code = 500, $details = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($details && DEBUG_MODE) {
            $response['details'] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Проверка доступности внешнего API
     */
    public static function checkApiAvailability($url, $timeout = 5) {
        if (!function_exists('curl_init')) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $result !== false && $httpCode === 200;
    }
    
    /**
     * Безопасный HTTP запрос с обработкой ошибок
     */
    public static function safeHttpRequest($url, $options = []) {
        $defaultOptions = [
            'method' => 'GET',
            'timeout' => 30,
            'headers' => [],
            'data' => null
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        if (!function_exists('curl_init')) {
            throw new Exception('cURL extension not available');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        
        if ($options['method'] === 'POST' && $options['data']) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['data']);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP error: $httpCode");
        }
        
        return $response;
    }
}

// Инициализируем обработчик ошибок
ErrorHandler::init();
?>
