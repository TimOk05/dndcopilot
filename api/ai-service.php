<?php
/**
 * AI Service - Сервис для работы с различными AI API
 * Генерирует описания, предыстории и другой контент для персонажей D&D
 */

class AiService {
    private $api_keys = [];
    private $preferred_api = 'deepseek'; // deepseek, openai, google
    private $cache_duration = 3600; // 1 час - увеличен для лучшей производительности
    private $cache_dir;
    
    public function __construct() {
        $this->api_keys = [
            'deepseek' => getApiKey('deepseek'),
            'openai' => getApiKey('openai'),
            'google' => getApiKey('google')
        ];
        
        $this->cache_dir = __DIR__ . '/../cache/ai/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Генерация описания персонажа
     */
    public function generateCharacterDescription($character, $use_ai = true) {
        if (!$use_ai) {
            return [
                'error' => 'AI отключен',
                'message' => 'Генерация описания персонажа отключена'
            ];
        }
        
        $cache_key = "desc_" . md5(json_encode($character));
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        $prompt = $this->buildDescriptionPrompt($character);
        $response = $this->callAiApi($prompt);
        
        if ($response) {
            $this->cacheData($cache_key, $response);
            return $response;
        }
        
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось получить описание персонажа от AI API'
        ];
    }
    
    /**
     * Генерация предыстории персонажа
     */
    public function generateCharacterBackground($character, $use_ai = true) {
        if (!$use_ai) {
            return [
                'error' => 'AI отключен',
                'message' => 'Генерация предыстории персонажа отключена'
            ];
        }
        
        $cache_key = "bg_" . md5(json_encode($character));
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        $prompt = $this->buildBackgroundPrompt($character);
        $response = $this->callAiApi($prompt);
        
        if ($response) {
            $this->cacheData($cache_key, $response);
            return $response;
        }
        
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось получить предысторию персонажа от AI API'
        ];
    }
    
    /**
     * Генерация тактики для противника
     */
    public function generateEnemyTactics($enemy, $use_ai = true) {
        if (!$use_ai) {
            return [
                'error' => 'AI отключен',
                'message' => 'Генерация тактики противника отключена'
            ];
        }
        
        $cache_key = "tactics_" . md5(json_encode($enemy));
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        $prompt = $this->buildTacticsPrompt($enemy);
        $response = $this->callAiApi($prompt);
        
        if ($response) {
            $this->cacheData($cache_key, $response);
            return $response;
        }
        
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось получить тактику противника от AI API'
        ];
    }
    
    /**
     * Генерация описания локации
     */
    public function generateLocationDescription($location_data, $use_ai = true) {
        if (!$use_ai) {
            return [
                'error' => 'AI отключен',
                'message' => 'Генерация описания локации отключена'
            ];
        }
        
        $cache_key = "location_" . md5(json_encode($location_data));
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        $prompt = $this->buildLocationPrompt($location_data);
        $response = $this->callAiApi($prompt);
        
        if ($response) {
            $this->cacheData($cache_key, $response);
            return $response;
        }
        
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось получить описание локации от AI API'
        ];
    }
    
    /**
     * Построение промпта для описания персонажа
     */
    private function buildDescriptionPrompt($character) {
        $name = $character['name'] ?? 'Персонаж';
        $race = $character['race'] ?? 'Неизвестная раса';
        $class = $character['class'] ?? 'Неизвестный класс';
        $level = $character['level'] ?? 1;
        $occupation = $character['occupation'] ?? 'Авантюрист';
        $gender = $character['gender'] ?? 'Неизвестно';
        $alignment = $character['alignment'] ?? 'Нейтральный';
        
        // Добавляем информацию о характеристиках
        $abilities = $character['abilities'] ?? [];
        $ability_desc = '';
        if (!empty($abilities) && is_array($abilities)) {
            $ability_desc = "Характеристики: ";
            $ability_names = ['str' => 'Сила', 'dex' => 'Ловкость', 'con' => 'Телосложение', 
                            'int' => 'Интеллект', 'wis' => 'Мудрость', 'cha' => 'Харизма'];
            foreach ($abilities as $key => $value) {
                if (isset($ability_names[$key]) && is_numeric($value)) {
                    $ability_desc .= "{$ability_names[$key]}: {$value}, ";
                }
            }
            $ability_desc = rtrim($ability_desc, ', ');
        }
        
        return "Опиши внешность и характер персонажа {$name}, {$race} {$class} {$level} уровня. 
Профессия: {$occupation}. Пол: {$gender}. Мировоззрение: {$alignment}. 
{$ability_desc}

Создай краткое (2-3 предложения), атмосферное описание, которое передает:
- Внешние особенности персонажа
- Характерные черты характера
- Влияние расы и класса на внешность
- Профессиональные навыки

Используй богатый, образный язык в стиле фэнтези.";
    }
    
    /**
     * Построение промпта для предыстории персонажа
     */
    private function buildBackgroundPrompt($character) {
        $name = $character['name'] ?? 'Персонаж';
        $race = $character['race'] ?? 'Неизвестная раса';
        $class = $character['class'] ?? 'Неизвестный класс';
        $level = $character['level'] ?? 1;
        $occupation = $character['occupation'] ?? 'Авантюрист';
        $gender = $character['gender'] ?? 'Неизвестно';
        $alignment = $character['alignment'] ?? 'Нейтральный';
        
        return "Создай краткую предысторию персонажа {$name}, {$race} {$class} {$level} уровня. 
Профессия: {$occupation}. Пол: {$gender}. Мировоззрение: {$alignment}.

Включи в предысторию:
- Ключевое событие из прошлого, которое повлияло на персонажа
- Мотивацию для приключений
- Связь между профессией и классом
- Влияние расы на жизненный путь

Сделай историю интересной и логичной (2-3 предложения). 
Используй атмосферный язык в стиле D&D.";
    }
    
    /**
     * Построение промпта для тактики противника
     */
    private function buildTacticsPrompt($enemy) {
        $name = $enemy['name'] ?? 'Противник';
        $type = $enemy['type'] ?? 'Неизвестный тип';
        $cr = $enemy['challenge_rating'] ?? '1/4';
        $abilities = $enemy['abilities'] ?? [];
        
        $ability_desc = '';
        if (!empty($abilities) && is_array($abilities)) {
            $ability_desc = "Характеристики: ";
            foreach ($abilities as $key => $value) {
                if (is_string($key) && is_numeric($value)) {
                    $ability_desc .= "{$key}: {$value}, ";
                }
            }
            $ability_desc = rtrim($ability_desc, ', ');
        }
        
        return "Опиши тактику боя для {$name} ({$type}, CR {$cr}). 
{$ability_desc}

Объясни:
- Как противник ведет себя в бою
- Какие способности использует в первую очередь
- Когда отступает или меняет тактику
- Как взаимодействует с союзниками

Сделай описание кратким (2-3 предложения) и практичным для мастера.";
    }
    
    /**
     * Построение промпта для описания локации
     */
    private function buildLocationPrompt($location_data) {
        $name = $location_data['name'] ?? 'Локация';
        $type = $location_data['type'] ?? 'Неизвестный тип';
        $climate = $location_data['climate'] ?? 'Умеренный';
        $danger_level = $location_data['danger_level'] ?? 'Средний';
        
        return "Опиши локацию '{$name}' ({$type}). 
Климат: {$climate}. Уровень опасности: {$danger_level}.

Создай атмосферное описание, включающее:
- Визуальные детали окружения
- Звуки и запахи
- Ощущение атмосферы
- Потенциальные опасности

Сделай описание живым и погружающим (2-3 предложения).";
    }
    
    /**
     * Вызов AI API с fallback на разные сервисы
     */
    private function callAiApi($prompt) {
        // Пробуем предпочтительный API
        $response = $this->callSpecificApi($this->preferred_api, $prompt);
        if ($response) {
            return $response;
        }
        
        // Пробуем другие API
        $apis = ['deepseek', 'openai', 'google'];
        foreach ($apis as $api) {
            if ($api !== $this->preferred_api) {
                $response = $this->callSpecificApi($api, $prompt);
                if ($response) {
                    return $response;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Вызов конкретного AI API
     */
    private function callSpecificApi($api_name, $prompt) {
        $api_key = $this->api_keys[$api_name] ?? '';
        if (!$api_key) {
            return null;
        }
        
        switch ($api_name) {
            case 'deepseek':
                return $this->callDeepSeekApi($prompt, $api_key);
            case 'openai':
                return $this->callOpenAiApi($prompt, $api_key);
            case 'google':
                return $this->callGoogleApi($prompt, $api_key);
            default:
                return null;
        }
    }
    
    /**
     * Вызов DeepSeek API
     */
    private function callDeepSeekApi($prompt, $api_key) {
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты опытный мастер D&D, создающий атмосферные описания и истории. Отвечай на русском языке.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 300,
            'temperature' => 0.8
        ];
        
        return $this->makeApiRequest('https://api.deepseek.com/v1/chat/completions', $data, $api_key);
    }
    
    /**
     * Вызов OpenAI API
     */
    private function callOpenAiApi($prompt, $api_key) {
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты опытный мастер D&D, создающий атмосферные описания и истории. Отвечай на русском языке.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 300,
            'temperature' => 0.8
        ];
        
        return $this->makeApiRequest('https://api.openai.com/v1/chat/completions', $data, $api_key);
    }
    
    /**
     * Вызов Google API (Gemini)
     */
    private function callGoogleApi($prompt, $api_key) {
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Ты опытный мастер D&D, создающий атмосферные описания и истории. Отвечай на русском языке.'],
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 300,
                'temperature' => 0.8
            ]
        ];
        
        return $this->makeApiRequest("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$api_key}", $data);
    }
    
    /**
     * Выполнение API запроса
     */
    private function makeApiRequest($url, $data, $api_key = null) {
        if (!function_exists('curl_init')) {
            return null;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/2.0');
        
        $headers = ['Content-Type: application/json'];
        if ($api_key) {
            $headers[] = 'Authorization: Bearer ' . $api_key;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            logMessage('WARNING', "AI API request failed: {$url}, HTTP: {$httpCode}");
            return null;
        }
        
        $result = json_decode($response, true);
        
        // Проверяем, что JSON декодировался корректно
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('ERROR', 'AI API returned invalid JSON: ' . json_last_error_msg());
            return null;
        }
        
        // Извлекаем текст ответа в зависимости от API
        $ai_text = null;
        if (isset($result['choices'][0]['message']['content'])) {
            $ai_text = trim($result['choices'][0]['message']['content']);
        } elseif (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_text = trim($result['candidates'][0]['content']['parts'][0]['text']);
        }
        
        if (!$ai_text) {
            return null;
        }
        
        // Очищаем текст от потенциально проблемных символов
        $ai_text = $this->cleanAiResponse($ai_text);
        
        return $ai_text;
    }
    
    /**
     * Очистка ответа от AI от проблемных символов
     */
    private function cleanAiResponse($text) {
        // Удаляем управляющие символы, кроме переносов строк
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Заменяем кавычки на безопасные
        $text = str_replace(['"', '"', '"', '"'], '"', $text);
        
        // Заменяем апострофы на безопасные
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9A", "\xE2\x80\x9B", "\xE2\x80\xB9", "\xE2\x80\xBA", "\xE2\x80\x9C", "\xE2\x80\x9D"], "'", $text);
        
        // Удаляем множественные пробелы и переносы строк
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Обрезаем пробелы в начале и конце
        $text = trim($text);
        
        // Ограничиваем длину текста
        if (strlen($text) > 1000) {
            $text = substr($text, 0, 1000) . '...';
        }
        
        return $text;
    }
    
    /**
     * Кэширование данных с улучшенной обработкой
     */
    private function cacheData($key, $data) {
        try {
            $filename = $this->cache_dir . md5($key) . '.json';
            $cache_data = [
                'timestamp' => time(),
                'data' => $data,
                'checksum' => md5(json_encode($data, JSON_UNESCAPED_UNICODE))
            ];
            
            // Создаем временный файл для атомарной записи
            $temp_filename = $filename . '.tmp';
            $result = file_put_contents($temp_filename, json_encode($cache_data, JSON_UNESCAPED_UNICODE));
            
            if ($result !== false) {
                // Атомарно переименовываем временный файл
                if (rename($temp_filename, $filename)) {
                    logMessage('INFO', "AI данные успешно закэшированы: {$key}");
                    return true;
                }
            }
            
            // Очищаем временный файл при ошибке
            if (file_exists($temp_filename)) {
                unlink($temp_filename);
            }
            
            logMessage('ERROR', "Ошибка кэширования AI данных: {$key}");
            return false;
            
        } catch (Exception $e) {
            logMessage('ERROR', "Исключение при кэшировании AI данных: {$key} - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение кэшированных данных с улучшенной валидацией
     */
    private function getCachedData($key) {
        try {
            $filename = $this->cache_dir . md5($key) . '.json';
            
            if (!file_exists($filename)) {
                return null;
            }
            
            $file_content = file_get_contents($filename);
            if ($file_content === false) {
                logMessage('WARNING', "Не удалось прочитать AI кэш файл: {$key}");
                return null;
            }
            
            $cache_data = json_decode($file_content, true);
            if (!$cache_data || !isset($cache_data['timestamp']) || !isset($cache_data['data'])) {
                logMessage('WARNING', "Некорректные AI кэш данные: {$key}");
                $this->clearAiCacheFile($filename);
                return null;
            }
            
            // Проверяем срок действия кэша
            if (time() - $cache_data['timestamp'] > $this->cache_duration) {
                logMessage('INFO', "AI кэш устарел: {$key}");
                $this->clearAiCacheFile($filename);
                return null;
            }
            
            // Проверяем целостность данных
            if (isset($cache_data['checksum'])) {
                $current_checksum = md5(json_encode($cache_data['data'], JSON_UNESCAPED_UNICODE));
                if ($cache_data['checksum'] !== $current_checksum) {
                    logMessage('WARNING', "Нарушена целостность AI кэша: {$key}");
                    $this->clearAiCacheFile($filename);
                    return null;
                }
            }
            
            logMessage('INFO', "AI данные получены из кэша: {$key}");
            return $cache_data['data'];
            
        } catch (Exception $e) {
            logMessage('ERROR', "Исключение при чтении AI кэша: {$key} - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Очистка AI кэш файла
     */
    private function clearAiCacheFile($filename) {
        try {
            if (file_exists($filename)) {
                unlink($filename);
                logMessage('INFO', "AI кэш файл очищен: " . basename($filename));
            }
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка очистки AI кэш файла: " . basename($filename) . " - " . $e->getMessage());
        }
    }
    
    /**
     * Очистка всего AI кэша
     */
    public function clearAllAiCache() {
        try {
            $files = glob($this->cache_dir . '*.json');
            $count = 0;
            foreach ($files as $file) {
                if (unlink($file)) {
                    $count++;
                }
            }
            logMessage('INFO', "Очищено AI кэш файлов: {$count}");
            return $count;
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка очистки AI кэша: " . $e->getMessage());
            return 0;
        }
    }
    

}
?>
