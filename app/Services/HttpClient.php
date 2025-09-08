<?php

/**
 * Единый HTTP клиент для всех API запросов
 * Устраняет дублирование кода и обеспечивает консистентность
 */
class HttpClient {
    private $defaultOptions = [
        'timeout' => 30,
        'user_agent' => 'DnD-Copilot/1.0',
        'verify_ssl' => false, // Для совместимости с Windows
        'follow_redirects' => true,
        'max_redirects' => 5
    ];
    
    private $cacheService;
    private $cacheEnabled = true;
    
    public function __construct($cacheService = null) {
        $this->cacheService = $cacheService;
    }
    
    /**
     * Выполнение GET запроса
     */
    public function get($url, $options = []) {
        return $this->request('GET', $url, null, $options);
    }
    
    /**
     * Выполнение POST запроса
     */
    public function post($url, $data = null, $options = []) {
        return $this->request('POST', $url, $data, $options);
    }
    
    /**
     * Основной метод выполнения HTTP запросов
     */
    public function request($method, $url, $data = null, $options = []) {
        $options = array_merge($this->defaultOptions, $options);
        
        // Проверяем кэш для GET запросов
        if ($method === 'GET' && $this->cacheEnabled && $this->cacheService) {
            $cacheKey = 'http_' . md5($url . serialize($options));
            $cached = $this->cacheService->get($cacheKey);
            if ($cached !== null) {
                logMessage('DEBUG', "HttpClient: Используем кэшированный ответ для $url");
                return $cached;
            }
        }
        
        $startTime = microtime(true);
        
        try {
            // Пробуем cURL сначала
            $response = $this->makeCurlRequest($method, $url, $data, $options);
            
            if ($response === false) {
                // Fallback на file_get_contents
                logMessage('WARNING', "HttpClient: cURL failed, trying file_get_contents for $url");
                $response = $this->makeFileGetContentsRequest($method, $url, $data, $options);
            }
            
            $endTime = microtime(true);
            $requestTime = round(($endTime - $startTime) * 1000, 2);
            
            logMessage('INFO', "HttpClient: $method $url completed in {$requestTime}ms");
            
            // Сохраняем в кэш для GET запросов
            if ($method === 'GET' && $this->cacheEnabled && $this->cacheService && $response !== false) {
                $cacheKey = 'http_' . md5($url . serialize($options));
                $this->cacheService->set($cacheKey, $response, 3600); // 1 час
            }
            
            return $response;
            
        } catch (Exception $e) {
            logMessage('ERROR', "HttpClient: Request failed for $url - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Выполнение запроса через cURL
     */
    private function makeCurlRequest($method, $url, $data, $options) {
        if (!function_exists('curl_init')) {
            return false;
        }
        
        $ch = curl_init();
        
        // Базовые настройки
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
        curl_setopt($ch, CURLOPT_USERAGENT, $options['user_agent']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $options['follow_redirects']);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $options['max_redirects']);
        
        // SSL настройки для совместимости
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['verify_ssl']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $options['verify_ssl'] ? 2 : 0);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_USE_SSL, CURLUSESSL_ALL);
        
        // Настройки для POST запросов
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                if (is_array($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json'
                    ]);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
            }
        }
        
        // Дополнительные заголовки
        if (isset($options['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
                curl_getinfo($ch, CURLINFO_HEADER_OUT) ?: [],
                $options['headers']
            ));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($error)) {
            logMessage('WARNING', "HttpClient: cURL error for $url - $error");
            return false;
        }
        
        if ($httpCode >= 400) {
            logMessage('WARNING', "HttpClient: HTTP $httpCode for $url");
            return false;
        }
        
        // Пытаемся декодировать JSON
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return $response;
    }
    
    /**
     * Fallback запрос через file_get_contents
     */
    private function makeFileGetContentsRequest($method, $url, $data, $options) {
        if ($method !== 'GET') {
            throw new Exception('file_get_contents supports only GET requests');
        }
        
        $contextOptions = [
            'http' => [
                'method' => $method,
                'header' => [
                    'User-Agent: ' . $options['user_agent'],
                    'Accept: application/json'
                ],
                'timeout' => $options['timeout']
            ]
        ];
        
        // SSL настройки
        if (!$options['verify_ssl']) {
            $contextOptions['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false
            ];
        }
        
        $context = stream_context_create($contextOptions);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            throw new Exception("file_get_contents failed: " . ($error['message'] ?? 'Unknown error'));
        }
        
        // Пытаемся декодировать JSON
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return $response;
    }
    
    /**
     * Проверка доступности интернета
     */
    public function checkInternetConnection() {
        try {
            $response = $this->get('https://httpbin.org/status/200', ['timeout' => 5]);
            return $response !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Отключение кэширования
     */
    public function disableCache() {
        $this->cacheEnabled = false;
    }
    
    /**
     * Включение кэширования
     */
    public function enableCache() {
        $this->cacheEnabled = true;
    }
}
