<?php

/**
 * Централизованный сервис для работы с конфигурацией
 * Предоставляет единый интерфейс для доступа к настройкам приложения
 */
class ConfigService {
    private static $instance = null;
    private $config = [];
    private $configFile = null;
    
    private function __construct() {
        $this->configFile = __DIR__ . '/../../config/config.php';
        $this->loadConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Загрузка конфигурации
     */
    private function loadConfig() {
        if (file_exists($this->configFile)) {
            require_once $this->configFile;
            
            $this->config = [
                // API ключи
                'deepseek_api_key' => getApiKey('deepseek'),
                'openai_api_key' => getApiKey('openai'),
                'google_api_key' => getApiKey('google'),
                
                // API URLs
                'deepseek_api_url' => DEEPSEEK_API_URL,
                'openai_api_url' => OPENAI_API_URL,
                'google_api_url' => GOOGLE_API_URL,
                'dnd_api_url' => DND_API_URL,
                
                // Настройки приложения
                'app_name' => APP_NAME,
                'app_version' => APP_VERSION,
                'app_debug' => DEBUG_MODE,
                'app_timezone' => APP_TIMEZONE,
                
                // Настройки кэширования
                'cache_enabled' => CACHE_ENABLED,
                'cache_duration' => CACHE_DURATION,
                'cache_dir' => CACHE_DIR,
                
                // Настройки логирования
                'log_enabled' => LOG_ENABLED,
                'log_level' => LOG_LEVEL,
                'log_file' => LOG_FILE,
                'log_dir' => LOG_DIR,
                
                // Настройки безопасности
                'session_lifetime' => SESSION_LIFETIME,
                'csrf_enabled' => CSRF_ENABLED,
                'cors_enabled' => CORS_ENABLED,
                'cors_origins' => CORS_ORIGINS,
                
                // Настройки API
                'api_timeout' => API_TIMEOUT,
                'api_retry_attempts' => API_RETRY_ATTEMPTS,
                'api_retry_delay' => API_RETRY_DELAY,
                
                // Настройки базы данных (если используется)
                'db_host' => defined('DB_HOST') ? DB_HOST : null,
                'db_name' => defined('DB_NAME') ? DB_NAME : null,
                'db_user' => defined('DB_USER') ? DB_USER : null,
                'db_pass' => defined('DB_PASS') ? DB_PASS : null,
                
                // Настройки файлов
                'upload_dir' => defined('UPLOAD_DIR') ? UPLOAD_DIR : 'data/uploads',
                'max_file_size' => defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : 10485760, // 10MB
                'allowed_file_types' => defined('ALLOWED_FILE_TYPES') ? ALLOWED_FILE_TYPES : ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                
                // Настройки производительности
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'max_input_vars' => ini_get('max_input_vars'),
                
                // Настройки локализации
                'default_language' => defined('DEFAULT_LANGUAGE') ? DEFAULT_LANGUAGE : 'ru',
                'supported_languages' => defined('SUPPORTED_LANGUAGES') ? SUPPORTED_LANGUAGES : ['ru', 'en'],
                
                // Настройки темы
                'default_theme' => defined('DEFAULT_THEME') ? DEFAULT_THEME : 'light',
                'available_themes' => defined('AVAILABLE_THEMES') ? AVAILABLE_THEMES : ['light', 'dark', 'mystic', 'orange']
            ];
        }
    }
    
    /**
     * Получение значения конфигурации
     */
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Установка значения конфигурации (в памяти)
     */
    public function set($key, $value) {
        $this->config[$key] = $value;
        return $this;
    }
    
    /**
     * Проверка существования ключа конфигурации
     */
    public function has($key) {
        return isset($this->config[$key]);
    }
    
    /**
     * Получение всех настроек
     */
    public function getAll() {
        return $this->config;
    }
    
    /**
     * Получение настроек API
     */
    public function getApiConfig() {
        return [
            'deepseek' => [
                'key' => $this->get('deepseek_api_key'),
                'url' => $this->get('deepseek_api_url'),
                'timeout' => $this->get('api_timeout'),
                'retry_attempts' => $this->get('api_retry_attempts'),
                'retry_delay' => $this->get('api_retry_delay')
            ],
            'openai' => [
                'key' => $this->get('openai_api_key'),
                'url' => $this->get('openai_api_url'),
                'timeout' => $this->get('api_timeout'),
                'retry_attempts' => $this->get('api_retry_attempts'),
                'retry_delay' => $this->get('api_retry_delay')
            ],
            'google' => [
                'key' => $this->get('google_api_key'),
                'url' => $this->get('google_api_url'),
                'timeout' => $this->get('api_timeout'),
                'retry_attempts' => $this->get('api_retry_attempts'),
                'retry_delay' => $this->get('api_retry_delay')
            ],
            'dnd' => [
                'url' => $this->get('dnd_api_url'),
                'timeout' => $this->get('api_timeout'),
                'retry_attempts' => $this->get('api_retry_attempts'),
                'retry_delay' => $this->get('api_retry_delay')
            ]
        ];
    }
    
    /**
     * Получение настроек кэширования
     */
    public function getCacheConfig() {
        return [
            'enabled' => $this->get('cache_enabled'),
            'duration' => $this->get('cache_duration'),
            'dir' => $this->get('cache_dir')
        ];
    }
    
    /**
     * Получение настроек логирования
     */
    public function getLogConfig() {
        return [
            'enabled' => $this->get('log_enabled'),
            'level' => $this->get('log_level'),
            'file' => $this->get('log_file'),
            'dir' => $this->get('log_dir')
        ];
    }
    
    /**
     * Получение настроек безопасности
     */
    public function getSecurityConfig() {
        return [
            'session_lifetime' => $this->get('session_lifetime'),
            'csrf_enabled' => $this->get('csrf_enabled'),
            'cors_enabled' => $this->get('cors_enabled'),
            'cors_origins' => $this->get('cors_origins')
        ];
    }
    
    /**
     * Получение настроек приложения
     */
    public function getAppConfig() {
        return [
            'name' => $this->get('app_name'),
            'version' => $this->get('app_version'),
            'debug' => $this->get('app_debug'),
            'timezone' => $this->get('app_timezone'),
            'default_language' => $this->get('default_language'),
            'supported_languages' => $this->get('supported_languages'),
            'default_theme' => $this->get('default_theme'),
            'available_themes' => $this->get('available_themes')
        ];
    }
    
    /**
     * Получение настроек производительности
     */
    public function getPerformanceConfig() {
        return [
            'memory_limit' => $this->get('memory_limit'),
            'max_execution_time' => $this->get('max_execution_time'),
            'max_input_vars' => $this->get('max_input_vars'),
            'api_timeout' => $this->get('api_timeout'),
            'api_retry_attempts' => $this->get('api_retry_attempts'),
            'api_retry_delay' => $this->get('api_retry_delay')
        ];
    }
    
    /**
     * Получение настроек файлов
     */
    public function getFileConfig() {
        return [
            'upload_dir' => $this->get('upload_dir'),
            'max_file_size' => $this->get('max_file_size'),
            'allowed_file_types' => $this->get('allowed_file_types')
        ];
    }
    
    /**
     * Проверка валидности конфигурации
     */
    public function validateConfig() {
        $errors = [];
        
        // Проверяем обязательные настройки
        $required = [
            'app_name' => 'Название приложения',
            'app_version' => 'Версия приложения',
            'cache_dir' => 'Директория кэша',
            'log_dir' => 'Директория логов'
        ];
        
        foreach ($required as $key => $description) {
            if (!$this->has($key) || empty($this->get($key))) {
                $errors[] = "Отсутствует обязательная настройка: {$description} ({$key})";
            }
        }
        
        // Проверяем API ключи
        $apiKeys = ['deepseek_api_key', 'openai_api_key', 'google_api_key'];
        $hasAnyApiKey = false;
        
        foreach ($apiKeys as $key) {
            if ($this->has($key) && !empty($this->get($key))) {
                $hasAnyApiKey = true;
                break;
            }
        }
        
        if (!$hasAnyApiKey) {
            $errors[] = "Не настроен ни один API ключ для AI сервисов";
        }
        
        // Проверяем директории
        $dirs = ['cache_dir', 'log_dir'];
        foreach ($dirs as $dir) {
            $path = $this->get($dir);
            if ($path && !is_dir($path)) {
                $errors[] = "Директория не существует: {$path}";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Получение информации о конфигурации
     */
    public function getConfigInfo() {
        $validation = $this->validateConfig();
        
        return [
            'config_file' => $this->configFile,
            'config_exists' => file_exists($this->configFile),
            'config_modified' => file_exists($this->configFile) ? filemtime($this->configFile) : null,
            'total_settings' => count($this->config),
            'validation' => $validation,
            'app_info' => $this->getAppConfig(),
            'performance' => $this->getPerformanceConfig()
        ];
    }
    
    /**
     * Получение настроек для конкретного сервиса
     */
    public function getServiceConfig($serviceName) {
        $serviceConfigs = [
            'ai' => $this->getApiConfig(),
            'cache' => $this->getCacheConfig(),
            'log' => $this->getLogConfig(),
            'security' => $this->getSecurityConfig(),
            'file' => $this->getFileConfig()
        ];
        
        return $serviceConfigs[$serviceName] ?? [];
    }
    
    /**
     * Проверка доступности API
     */
    public function checkApiAvailability() {
        $apis = $this->getApiConfig();
        $availability = [];
        
        foreach ($apis as $name => $config) {
            $availability[$name] = [
                'configured' => !empty($config['key']) || $name === 'dnd',
                'url' => $config['url'] ?? null,
                'timeout' => $config['timeout'] ?? null
            ];
        }
        
        return $availability;
    }
}
?>
