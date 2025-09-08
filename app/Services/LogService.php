<?php

/**
 * Улучшенный сервис для работы с логами
 * Предоставляет структурированное логирование с различными уровнями
 */
class LogService {
    private static $instance = null;
    private $logDir;
    private $logFile;
    private $logLevel;
    private $enabled;
    private $logBuffer = [];
    private $maxBufferSize = 100;
    
    private function __construct() {
        $this->logDir = defined('LOG_DIR') ? LOG_DIR : 'data/logs';
        $this->logFile = defined('LOG_FILE') ? LOG_FILE : 'app.log';
        $this->logLevel = defined('LOG_LEVEL') ? LOG_LEVEL : 'INFO';
        $this->enabled = defined('LOG_ENABLED') ? LOG_ENABLED : true;
        
        // Создаем директорию логов если не существует
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Уровни логирования
     */
    private $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];
    
    /**
     * Проверка, должен ли лог быть записан
     */
    private function shouldLog($level) {
        if (!$this->enabled) {
            return false;
        }
        
        $currentLevel = $this->levels[$this->logLevel] ?? 1;
        $messageLevel = $this->levels[$level] ?? 1;
        
        return $messageLevel >= $currentLevel;
    }
    
    /**
     * Форматирование сообщения лога
     */
    private function formatMessage($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $pid = getmypid();
        $memory = memory_get_usage(true);
        $memoryFormatted = $this->formatBytes($memory);
        
        $formattedMessage = "[{$timestamp}] [{$level}] [PID:{$pid}] [MEM:{$memoryFormatted}] {$message}";
        
        if (!empty($context)) {
            $formattedMessage .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        return $formattedMessage . PHP_EOL;
    }
    
    /**
     * Форматирование размера в байтах
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Запись в лог
     */
    private function writeLog($level, $message, $context = []) {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $formattedMessage = $this->formatMessage($level, $message, $context);
        
        // Добавляем в буфер
        $this->logBuffer[] = [
            'timestamp' => time(),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'formatted' => $formattedMessage
        ];
        
        // Если буфер переполнен, записываем в файл
        if (count($this->logBuffer) >= $this->maxBufferSize) {
            $this->flushBuffer();
        }
    }
    
    /**
     * Запись буфера в файл
     */
    public function flushBuffer() {
        if (empty($this->logBuffer)) {
            return;
        }
        
        $logPath = $this->logDir . '/' . $this->logFile;
        $content = '';
        
        foreach ($this->logBuffer as $entry) {
            $content .= $entry['formatted'];
        }
        
        file_put_contents($logPath, $content, FILE_APPEND | LOCK_EX);
        $this->logBuffer = [];
    }
    
    /**
     * Логирование DEBUG
     */
    public function debug($message, $context = []) {
        $this->writeLog('DEBUG', $message, $context);
    }
    
    /**
     * Логирование INFO
     */
    public function info($message, $context = []) {
        $this->writeLog('INFO', $message, $context);
    }
    
    /**
     * Логирование WARNING
     */
    public function warning($message, $context = []) {
        $this->writeLog('WARNING', $message, $context);
    }
    
    /**
     * Логирование ERROR
     */
    public function error($message, $context = []) {
        $this->writeLog('ERROR', $message, $context);
    }
    
    /**
     * Логирование CRITICAL
     */
    public function critical($message, $context = []) {
        $this->writeLog('CRITICAL', $message, $context);
    }
    
    /**
     * Логирование исключения
     */
    public function exception($exception, $context = []) {
        $message = "Exception: " . $exception->getMessage();
        $context['exception'] = [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        $this->error($message, $context);
    }
    
    /**
     * Логирование API запроса
     */
    public function apiRequest($method, $url, $data = null, $response = null, $duration = null) {
        $context = [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'response' => $response,
            'duration' => $duration
        ];
        
        $this->info("API Request: {$method} {$url}", $context);
    }
    
    /**
     * Логирование API ошибки
     */
    public function apiError($method, $url, $error, $data = null) {
        $context = [
            'method' => $method,
            'url' => $url,
            'error' => $error,
            'data' => $data
        ];
        
        $this->error("API Error: {$method} {$url} - {$error}", $context);
    }
    
    /**
     * Логирование кэша
     */
    public function cache($action, $key, $hit = null, $ttl = null) {
        $context = [
            'action' => $action,
            'key' => $key,
            'hit' => $hit,
            'ttl' => $ttl
        ];
        
        $message = "Cache {$action}: {$key}";
        if ($hit !== null) {
            $message .= " (Hit: " . ($hit ? 'Yes' : 'No') . ")";
        }
        
        $this->debug($message, $context);
    }
    
    /**
     * Логирование производительности
     */
    public function performance($operation, $duration, $memory = null, $context = []) {
        $context['operation'] = $operation;
        $context['duration'] = $duration;
        $context['memory'] = $memory;
        
        $message = "Performance: {$operation} took {$duration}ms";
        if ($memory) {
            $message .= " (Memory: {$this->formatBytes($memory)})";
        }
        
        $this->info($message, $context);
    }
    
    /**
     * Логирование безопасности
     */
    public function security($event, $details = []) {
        $context = [
            'event' => $event,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $this->warning("Security Event: {$event}", $context);
    }
    
    /**
     * Логирование пользовательских действий
     */
    public function userAction($action, $userId = null, $details = []) {
        $context = [
            'action' => $action,
            'user_id' => $userId,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->info("User Action: {$action}", $context);
    }
    
    /**
     * Получение последних записей лога
     */
    public function getRecentLogs($lines = 100) {
        $logPath = $this->logDir . '/' . $this->logFile;
        
        if (!file_exists($logPath)) {
            return [];
        }
        
        $logs = [];
        $handle = fopen($logPath, 'r');
        
        if ($handle) {
            $lineCount = 0;
            $buffer = [];
            
            // Читаем файл построчно
            while (($line = fgets($handle)) !== false) {
                $buffer[] = $line;
                $lineCount++;
                
                // Если буфер переполнен, удаляем старые записи
                if (count($buffer) > $lines) {
                    array_shift($buffer);
                }
            }
            
            fclose($handle);
            $logs = $buffer;
        }
        
        return $logs;
    }
    
    /**
     * Получение статистики логов
     */
    public function getLogStats() {
        $logPath = $this->logDir . '/' . $this->logFile;
        
        if (!file_exists($logPath)) {
            return [
                'file_exists' => false,
                'size' => 0,
                'lines' => 0,
                'last_modified' => null
            ];
        }
        
        $stats = [
            'file_exists' => true,
            'size' => filesize($logPath),
            'size_formatted' => $this->formatBytes(filesize($logPath)),
            'lines' => 0,
            'last_modified' => filemtime($logPath),
            'levels' => []
        ];
        
        // Подсчитываем строки и уровни
        $handle = fopen($logPath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $stats['lines']++;
                
                // Извлекаем уровень из строки
                if (preg_match('/\[(\w+)\]/', $line, $matches)) {
                    $level = $matches[1];
                    $stats['levels'][$level] = ($stats['levels'][$level] ?? 0) + 1;
                }
            }
            fclose($handle);
        }
        
        return $stats;
    }
    
    /**
     * Очистка старых логов
     */
    public function cleanupLogs($days = 30) {
        $logPath = $this->logDir . '/' . $this->logFile;
        
        if (!file_exists($logPath)) {
            return 0;
        }
        
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $lines = file($logPath);
        $newLines = [];
        $removedCount = 0;
        
        foreach ($lines as $line) {
            // Извлекаем timestamp из строки
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $logTime = strtotime($matches[1]);
                if ($logTime >= $cutoffTime) {
                    $newLines[] = $line;
                } else {
                    $removedCount++;
                }
            } else {
                $newLines[] = $line; // Сохраняем строки без timestamp
            }
        }
        
        // Записываем обновленный лог
        file_put_contents($logPath, implode('', $newLines));
        
        return $removedCount;
    }
    
    /**
     * Получение конфигурации логгера
     */
    public function getConfig() {
        return [
            'enabled' => $this->enabled,
            'level' => $this->logLevel,
            'dir' => $this->logDir,
            'file' => $this->logFile,
            'buffer_size' => $this->maxBufferSize,
            'available_levels' => array_keys($this->levels)
        ];
    }
    
    /**
     * Установка уровня логирования
     */
    public function setLevel($level) {
        if (isset($this->levels[$level])) {
            $this->logLevel = $level;
            return true;
        }
        return false;
    }
    
    /**
     * Включение/выключение логирования
     */
    public function setEnabled($enabled) {
        $this->enabled = $enabled;
    }
    
    /**
     * Деструктор - записываем буфер при завершении
     */
    public function __destruct() {
        $this->flushBuffer();
    }
}
?>
