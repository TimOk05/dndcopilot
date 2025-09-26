<?php
/**
 * Сервис для работы с AI (DeepSeek)
 * Обеспечивает генерацию контента с помощью AI
 */

class AIService {
    private $apiKey;
    private $apiUrl;
    private $timeout;
    
    public function __construct() {
        $this->apiKey = getApiKey('deepseek');
        $this->apiUrl = DEEPSEEK_API_URL;
        $this->timeout = API_TIMEOUT;
    }
    
    /**
     * Генерирует описание персонажа с помощью AI
     */
    public function generateCharacterDescription($character) {
        $prompt = $this->buildCharacterPrompt($character);
        return $this->callAI($prompt);
    }
    
    /**
     * Генерирует предысторию персонажа с помощью AI
     */
    public function generateCharacterBackground($character) {
        $prompt = $this->buildBackgroundPrompt($character);
        return $this->callAI($prompt);
    }
    
    /**
     * Генерирует детальное описание персонажа с помощью AI
     */
    public function generateDetailedCharacter($character) {
        $prompt = $this->buildDetailedCharacterPrompt($character);
        return $this->callAI($prompt);
    }
    
    /**
     * Строит промпт для генерации описания персонажа
     */
    private function buildCharacterPrompt($character) {
        return "Создай краткое описание персонажа D&D 5e:\n" .
               "Имя: {$character['name']}\n" .
               "Раса: {$character['race']}\n" .
               "Класс: {$character['class']}\n" .
               "Уровень: {$character['level']}\n" .
               "Пол: {$character['gender']}\n" .
               "Мировоззрение: {$character['alignment']}\n" .
               "Характеристики: СИЛ {$character['abilities']['str']}, ЛОВ {$character['abilities']['dex']}, ТЕЛ {$character['abilities']['con']}, ИНТ {$character['abilities']['int']}, МДР {$character['abilities']['wis']}, ХАР {$character['abilities']['cha']}\n\n" .
               "Создай краткое описание внешности и характера персонажа (2-3 предложения).";
    }
    
    /**
     * Строит промпт для генерации предыстории персонажа
     */
    private function buildBackgroundPrompt($character) {
        return "Создай предысторию персонажа D&D 5e:\n" .
               "Имя: {$character['name']}\n" .
               "Раса: {$character['race']}\n" .
               "Класс: {$character['class']}\n" .
               "Предыстория: {$character['background']}\n" .
               "Мировоззрение: {$character['alignment']}\n\n" .
               "Создай интересную предысторию персонажа (3-4 предложения), объясняющую как он стал {$character['class']} и что привело его к приключениям.";
    }
    
    /**
     * Строит промпт для генерации детального описания персонажа
     */
    private function buildDetailedCharacterPrompt($character) {
        return "Создай детальное описание персонажа D&D 5e:\n" .
               "Имя: {$character['name']}\n" .
               "Раса: {$character['race']}\n" .
               "Класс: {$character['class']}\n" .
               "Уровень: {$character['level']}\n" .
               "Пол: {$character['gender']}\n" .
               "Мировоззрение: {$character['alignment']}\n" .
               "Предыстория: {$character['background']}\n" .
               "Характеристики: СИЛ {$character['abilities']['str']}, ЛОВ {$character['abilities']['dex']}, ТЕЛ {$character['abilities']['con']}, ИНТ {$character['abilities']['int']}, МДР {$character['abilities']['wis']}, ХАР {$character['abilities']['cha']}\n" .
               "Хиты: {$character['hit_points']}\n" .
               "КД: {$character['armor_class']}\n" .
               "Скорость: {$character['speed']} футов\n\n" .
               "Создай полное описание персонажа, включая:\n" .
               "1. Внешность (2-3 предложения)\n" .
               "2. Характер и личность (2-3 предложения)\n" .
               "3. Предыстория и мотивация (3-4 предложения)\n" .
               "4. Особые способности или таланты (1-2 предложения)";
    }
    
    /**
     * Вызывает AI API
     */
    private function callAI($prompt) {
        if (empty($this->apiKey)) {
            logMessage('WARNING', 'AI API key not configured');
            return $this->getFallbackResponse($prompt);
        }
        
        if (!OPENSSL_AVAILABLE) {
            logMessage('WARNING', 'OpenSSL not available for AI requests');
            return $this->getFallbackResponse($prompt);
        }
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'Ты - помощник мастера D&D 5e. Создавай интересные и детальные описания персонажей на русском языке.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => $messages,
            'max_tokens' => 1000,
            'temperature' => 0.8
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            logMessage('ERROR', 'AI API request failed', [
                'error' => $error,
                'http_code' => $httpCode
            ]);
            return $this->getFallbackResponse($prompt);
        }
        
        if ($httpCode !== 200) {
            logMessage('ERROR', 'AI API returned error', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return $this->getFallbackResponse($prompt);
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['choices'][0]['message']['content'])) {
            logMessage('ERROR', 'AI API response parsing failed', [
                'response' => $response
            ]);
            return $this->getFallbackResponse($prompt);
        }
        
        $aiResponse = $result['choices'][0]['message']['content'];
        
        // Очищаем ответ от лишних символов
        $aiResponse = $this->cleanAIResponse($aiResponse);
        
        logMessage('INFO', 'AI response generated successfully', [
            'prompt_length' => strlen($prompt),
            'response_length' => strlen($aiResponse)
        ]);
        
        return $aiResponse;
    }
    
    /**
     * Очищает ответ AI от лишних символов
     */
    private function cleanAIResponse($response) {
        // Убираем лишние символы форматирования
        $response = preg_replace('/[*_`>#\-]+/', '', $response);
        $response = str_replace(['"', "'", '"', '"', '«', '»'], '', $response);
        $response = preg_replace('/\n{2,}/', "\n", $response);
        $response = preg_replace('/\s{3,}/', "\n", $response);
        
        // Разбиваем длинные строки
        $lines = explode("\n", $response);
        $formatted = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (mb_strlen($line) > 90) {
                $formatted = array_merge($formatted, str_split($line, 80));
            } else {
                $formatted[] = $line;
            }
        }
        
        return implode("\n", $formatted);
    }
    
    /**
     * Возвращает fallback ответ, если AI недоступен
     */
    private function getFallbackResponse($prompt) {
        if (strpos($prompt, 'описание персонажа') !== false) {
            return "Этот персонаж имеет загадочное прошлое и уникальные способности. Его внешность отражает его расу и класс, а характер формировался под влиянием его предыстории.";
        }
        
        if (strpos($prompt, 'предыстория') !== false) {
            return "Персонаж прошел долгий путь, прежде чем стал искателем приключений. Его прошлое полно интересных событий, которые сформировали его личность и мотивацию.";
        }
        
        return "Персонаж готов к приключениям и имеет все необходимые навыки для успешного путешествия.";
    }
}
?>
