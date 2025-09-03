<?php
/**
 * Альтернативный AI Service - попытки обойти проблемы с OpenSSL
 */

class AlternativeAiService {
    private $deepseek_api_key;
    private $cache_dir;
    
    public function __construct() {
        $this->deepseek_api_key = getApiKey('deepseek');
        $this->cache_dir = __DIR__ . '/../cache/ai/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Попытка генерации через различные методы
     */
    public function generateWithFallback($prompt, $type = 'description') {
        // Метод 1: Попытка через cURL с отключенной SSL проверкой
        $result = $this->tryCurlMethod($prompt);
        if ($result) {
            return $result;
        }
        
        // Метод 2: Попытка через file_get_contents с отключенной SSL проверкой
        $result = $this->tryFileGetContentsMethod($prompt);
        if ($result) {
            return $result;
        }
        
        // Метод 3: Попытка через альтернативные настройки cURL
        $result = $this->tryAlternativeCurlMethod($prompt);
        if ($result) {
            return $result;
        }
        
        // НЕ используем fallback - возвращаем ошибку
        logMessage('ERROR', "Все методы AI API недоступны для типа: $type");
        return [
            'error' => 'AI API полностью недоступен',
            'message' => 'Не удалось подключиться к AI API ни одним из доступных методов',
            'details' => 'Проверьте подключение к интернету, настройки SSL и доступность API ключей',
            'type' => $type
        ];
    }
    
    /**
     * Метод 1: cURL с отключенной SSL проверкой
     */
    private function tryCurlMethod($prompt) {
        if (!function_exists('curl_init')) {
            logMessage('WARNING', 'cURL не доступен в альтернативном сервисе');
            return null;
        }
        
        try {
            $data = [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Создавай интересные описания на русском языке.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 150,
                'temperature' => 0.8
            ];
            
            $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->deepseek_api_key
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            // Критически важные настройки для обхода SSL проблем
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            curl_setopt($ch, CURLOPT_USE_SSL, CURLUSESSL_ALL);
            
            // Дополнительные настройки для Windows
            curl_setopt($ch, CURLOPT_CAINFO, null);
            curl_setopt($ch, CURLOPT_CAPATH, null);
            
            logMessage('INFO', 'Пробуем cURL метод для AI API');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response === false) {
                logMessage('WARNING', "cURL метод не удался: $error");
                return null;
            }
            
            if ($httpCode !== 200) {
                logMessage('WARNING', "cURL HTTP ошибка: $httpCode - $response");
                return null;
            }
            
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                logMessage('INFO', "cURL метод успешен! Получен ответ от AI");
                return trim($result['choices'][0]['message']['content']);
            } else {
                logMessage('WARNING', 'cURL метод: неверная структура ответа от AI API');
                return null;
            }
            
        } catch (Exception $e) {
            logMessage('WARNING', "cURL метод исключение: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Метод 2: file_get_contents с отключенной SSL проверкой
     */
    private function tryFileGetContentsMethod($prompt) {
        try {
            $data = [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Создавай интересные описания.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 150,
                'temperature' => 0.8
            ];
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->deepseek_api_key,
                        'User-Agent: DnD-Copilot/1.0'
                    ],
                    'content' => json_encode($data),
                    'timeout' => 15
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'SNI_enabled' => false
                ]
            ]);
            
            $response = @file_get_contents('https://api.deepseek.com/v1/chat/completions', false, $context);
            
            if ($response === false) {
                $error = error_get_last();
                logMessage('WARNING', "file_get_contents метод не удался: " . ($error['message'] ?? 'неизвестная ошибка'));
                return null;
            }
            
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                logMessage('INFO', "file_get_contents метод успешен!");
                return trim($result['choices'][0]['message']['content']);
            }
            
        } catch (Exception $e) {
            logMessage('WARNING', "file_get_contents метод исключение: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Метод 3: Попытка через альтернативные настройки cURL
     */
    private function tryAlternativeCurlMethod($prompt) {
        if (!function_exists('curl_init')) {
            return null;
        }
        
        try {
            $data = [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ты помощник мастера D&D. Создавай интересные описания на русском языке.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 150,
                'temperature' => 0.8
            ];
            
            $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->deepseek_api_key
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            // Альтернативные настройки SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1);
            
            logMessage('INFO', 'Пробуем альтернативный cURL метод для AI API');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response === false) {
                logMessage('WARNING', "Альтернативный cURL метод не удался: $error");
                return null;
            }
            
            if ($httpCode !== 200) {
                logMessage('WARNING', "Альтернативный cURL HTTP ошибка: $httpCode - $response");
                return null;
            }
            
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                logMessage('INFO', "Альтернативный cURL метод успешен! Получен ответ от AI");
                return trim($result['choices'][0]['message']['content']);
            } else {
                logMessage('WARNING', 'Альтернативный cURL метод: неверная структура ответа от AI API');
                return null;
            }
            
        } catch (Exception $e) {
            logMessage('WARNING', "Альтернативный cURL метод исключение: " . $e->getMessage());
        }
        
        return null;
    }
}
?>
