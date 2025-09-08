<?php

/**
 * Базовый класс для всех сервисов
 * Предоставляет общую функциональность и устраняет дублирование кода
 */
abstract class BaseService {
    protected $cacheService;
    protected $httpClient;
    protected $translationService;
    protected $logger;
    
    public function __construct() {
        $this->initializeDependencies();
        $this->logger = $this;
    }
    
    /**
     * Инициализация зависимостей
     */
    protected function initializeDependencies() {
        // Инициализируем только те зависимости, которые нужны
        if ($this->needsCacheService()) {
            require_once __DIR__ . '/CacheService.php';
            $this->cacheService = new CacheService();
        }
        
        if ($this->needsHttpClient()) {
            require_once __DIR__ . '/HttpClient.php';
            $this->httpClient = new HttpClient($this->cacheService);
        }
        
        if ($this->needsTranslationService()) {
            require_once __DIR__ . '/TranslationService.php';
            $this->translationService = TranslationService::getInstance();
        }
    }
    
    /**
     * Определяет, нужен ли сервису кэш
     */
    protected function needsCacheService() {
        return true; // По умолчанию все сервисы используют кэш
    }
    
    /**
     * Определяет, нужен ли сервису HTTP клиент
     */
    protected function needsHttpClient() {
        return false; // По умолчанию не нужен
    }
    
    /**
     * Определяет, нужен ли сервису переводчик
     */
    protected function needsTranslationService() {
        return false; // По умолчанию не нужен
    }
    
    /**
     * Логирование с автоматическим определением класса
     */
    protected function log($level, $message, $context = []) {
        $className = get_class($this);
        $context['service'] = $className;
        logMessage($level, "[$className] $message", $context);
    }
    
    /**
     * Безопасное получение данных из массива
     */
    protected function safeGet($array, $key, $default = null) {
        return isset($array[$key]) ? $array[$key] : $default;
    }
    
    /**
     * Валидация обязательных параметров
     */
    protected function validateRequiredParams($params, $required) {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new InvalidArgumentException('Отсутствуют обязательные параметры: ' . implode(', ', $missing));
        }
    }
    
    /**
     * Создание стандартного ответа об ошибке
     */
    protected function createErrorResponse($message, $code = null) {
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        if ($code !== null) {
            $response['code'] = $code;
        }
        
        return $response;
    }
    
    /**
     * Создание стандартного ответа об успехе
     */
    protected function createSuccessResponse($data = null, $message = null) {
        $response = [
            'success' => true
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return $response;
    }
    
    /**
     * Безопасное выполнение операции с обработкой ошибок
     */
    protected function safeExecute($operation, $errorMessage = 'Операция не удалась') {
        try {
            return $operation();
        } catch (Exception $e) {
            $this->log('ERROR', $errorMessage . ': ' . $e->getMessage());
            return $this->createErrorResponse($errorMessage);
        }
    }
    
    /**
     * Получение кэшированных данных с автоматическим обновлением
     */
    protected function getCachedData($key, $generator, $ttl = 3600) {
        if (!$this->cacheService) {
            return $generator();
        }
        
        $cached = $this->cacheService->get($key);
        if ($cached !== null) {
            $this->log('DEBUG', "Используем кэшированные данные для ключа: $key");
            return $cached;
        }
        
        $data = $generator();
        if ($data !== null) {
            $this->cacheService->set($key, $data, $ttl);
        }
        
        return $data;
    }
    
    /**
     * Очистка кэша сервиса
     */
    public function clearCache() {
        if ($this->cacheService) {
            $this->cacheService->clear();
            $this->log('INFO', 'Кэш сервиса очищен');
        }
    }
    
    /**
     * Получение статистики сервиса
     */
    public function getStats() {
        $stats = [
            'service' => get_class($this),
            'cache_enabled' => $this->cacheService !== null,
            'http_client_enabled' => $this->httpClient !== null,
            'translation_enabled' => $this->translationService !== null
        ];
        
        if ($this->cacheService) {
            $stats['cache_stats'] = $this->cacheService->getStats();
        }
        
        return $stats;
    }
}
?>
