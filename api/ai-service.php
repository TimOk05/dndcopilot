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
        
        // Устанавливаем DeepSeek как единственный рабочий API
        $this->preferred_api = 'deepseek';
        
        $this->cache_dir = __DIR__ . '/../cache/ai/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        // Логируем доступные API
        $available_apis = array_filter($this->api_keys);
        logMessage('INFO', 'AI сервис инициализирован. Доступные API: ' . implode(', ', array_keys($available_apis)));
    }
    
    /**
     * Генерация описания персонажа
     */
    public function generateCharacterDescription($character, $use_ai = true) {
        // AI всегда включен - это основной функционал
        // if (!$use_ai) {
        //     return [
        //         'error' => 'AI отключен пользователем',
        //         'message' => 'Генерация описания персонажа отключена пользователем'
        //     ];
        // }
        
        // Проверяем доступность API ключей
        if (empty($this->api_keys['deepseek']) && empty($this->api_keys['openai']) && empty($this->api_keys['google'])) {
            return [
                'error' => 'AI API ключи не настроены',
                'message' => 'Проверьте настройки API ключей в config.php',
                'details' => 'Добавьте API ключи для DeepSeek, OpenAI или Google'
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
        
        // Возвращаем детальную ошибку для диагностики
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось получить описание персонажа от AI API',
            'details' => 'Проверьте: 1) Подключение к интернету, 2) API ключи, 3) SSL настройки',
            'debug_info' => [
                'available_apis' => array_keys(array_filter($this->api_keys)),
                'preferred_api' => $this->preferred_api,
                'curl_available' => function_exists('curl_init')
            ]
        ];
    }
    
    /**
     * Генерация предыстории персонажа
     */
    public function generateCharacterBackground($character, $use_ai = true) {
        // AI всегда включен - это основной функционал
        // if (!$use_ai) {
        //     return [
        //         'error' => 'AI отключен пользователем',
        //         'message' => 'Генерация предыстории персонажа отключена пользователем'
        //     ];
        // }
        
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
        
        // Возвращаем детальную ошибку для диагностики
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось получить предысторию персонажа от AI API',
            'details' => 'Проверьте: 1) Подключение к интернету, 2) API ключи, 3) SSL настройки',
            'debug_info' => [
                'available_apis' => array_keys(array_filter($this->api_keys)),
                'preferred_api' => $this->preferred_api,
                'curl_available' => function_exists('curl_init')
            ]
        ];
    }
    
    /**
     * Генерация тактики для противника
     */
    public function generateEnemyTactics($enemy, $use_ai = true) {
        // AI всегда включен - это основной функционал
        // if (!$use_ai) {
        //     return [
        //         'error' => 'AI отключен пользователем',
        //         'message' => 'Генерация тактики противника отключена пользователем'
        //     ];
        // }
        
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
        
        // Возвращаем детальную ошибку для диагностики
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось получить тактику противника от AI API',
            'details' => 'Проверьте: 1) Подключение к интернету, 2) API ключи, 3) SSL настройки',
            'debug_info' => [
                'available_apis' => array_keys(array_filter($this->api_keys)),
                'preferred_api' => $this->preferred_api,
                'curl_available' => function_exists('curl_init')
            ]
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
        // Получаем список доступных API
        $available_apis = array_filter($this->api_keys);
        
        if (empty($available_apis)) {
            logMessage('ERROR', 'AI API: Нет доступных API ключей');
            return null;
        }
        
        // Пробуем предпочтительный API (DeepSeek)
        if (isset($available_apis['deepseek'])) {
            $response = $this->callSpecificApi('deepseek', $prompt);
            if ($response) {
                logMessage('INFO', 'AI API: Успешно использован DeepSeek');
                return $response;
            }
        }
        
        // Пробуем другие доступные API
        foreach ($available_apis as $api_name => $api_key) {
            if ($api_name !== 'deepseek') {
                $response = $this->callSpecificApi($api_name, $prompt);
                if ($response) {
                    logMessage('INFO', "AI API: Успешно использован {$api_name}");
                    return $response;
                }
            }
        }
        
        logMessage('ERROR', 'AI API: Все доступные API недоступны');
        return null;
    }
    
    /**
     * Вызов конкретного AI API
     */
    private function callSpecificApi($api_name, $prompt) {
        $api_key = $this->api_keys[$api_name] ?? '';
        if (!$api_key) {
            logMessage('WARNING', "AI API: API ключ для {$api_name} не настроен");
            return null;
        }
        
        logMessage('INFO', "AI API: Попытка вызова {$api_name}");
        
        switch ($api_name) {
            case 'deepseek':
                return $this->callDeepSeekApi($prompt, $api_key);
            case 'openai':
                return $this->callOpenAiApi($prompt, $api_key);
            case 'google':
                return $this->callGoogleApi($prompt, $api_key);
            default:
                logMessage('WARNING', "AI API: Неизвестный API: {$api_name}");
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
            logMessage('WARNING', 'cURL не доступен, AI API не может работать');
            return null;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Увеличиваем таймаут
        curl_setopt($ch, CURLOPT_USERAGENT, 'DnD-Copilot/2.0');
        
        // Критически важные настройки SSL для обхода проблем
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_USE_SSL, CURLUSESSL_ALL);
        
        // Дополнительные настройки для Windows
        curl_setopt($ch, CURLOPT_CAINFO, null);
        curl_setopt($ch, CURLOPT_CAPATH, null);
        
        // Добавляем заголовки
        $headers = ['Content-Type: application/json'];
        if ($api_key) {
            $headers[] = 'Authorization: Bearer ' . $api_key;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Выполняем запрос
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        // Логируем детальную информацию
        logMessage('INFO', "AI API запрос к: {$url}, HTTP код: {$httpCode}");
        
        if ($response === false) {
            logMessage('ERROR', "AI API cURL ошибка: {$error} для URL: {$url}");
            return null;
        }
        
        if ($httpCode !== 200) {
            logMessage('ERROR', "AI API HTTP ошибка: {$httpCode} для URL: {$url}");
            logMessage('ERROR', "AI API ответ: {$response}");
            return null;
        }
        
        $result = json_decode($response, true);
        
        // Проверяем, что JSON декодировался корректно
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('ERROR', 'AI API вернул неверный JSON: ' . json_last_error_msg());
            logMessage('ERROR', 'AI API сырой ответ: ' . substr($response, 0, 500));
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
            logMessage('WARNING', 'AI API не вернул текстовый ответ');
            logMessage('DEBUG', 'AI API структура ответа: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            return null;
        }
        
        // Очищаем текст от потенциально проблемных символов
        $ai_text = $this->cleanAiResponse($ai_text);
        
        logMessage('INFO', 'AI API успешно вернул ответ длиной: ' . strlen($ai_text));
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
    
    /**
     * Перевод названия зелья
     */
    public function translatePotionName($name, $target_language = 'ru') {
        if ($target_language === 'en') {
            return $name; // Уже на английском
        }
        
        $cache_key = "potion_name_" . md5($name . '_' . $target_language);
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        $prompt = $this->buildPotionNameTranslationPrompt($name, $target_language);
        $response = $this->callAiApi($prompt);
        
        if ($response) {
            $this->cacheData($cache_key, $response);
            return $response;
        }
        
        // Возвращаем детальную ошибку для диагностики
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось перевести название зелья',
            'details' => 'Проверьте: 1) Подключение к интернету, 2) API ключи, 3) SSL настройки',
            'original_name' => $name
        ];
    }
    
    /**
     * Перевод описания зелья
     */
    public function translatePotionDescription($description, $target_language = 'ru') {
        if ($target_language === 'en') {
            return $description; // Уже на английском
        }
        
        $cache_key = "potion_desc_" . md5($description . '_' . $target_language);
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        $prompt = $this->buildPotionDescriptionTranslationPrompt($description, $target_language);
        $response = $this->callAiApi($prompt);
        
        if ($response) {
            $this->cacheData($cache_key, $response);
            return $response;
        }
        
        // Возвращаем детальную ошибку для диагностики
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось перевести описание зелья',
            'details' => 'Проверьте: 1) Подключение к интернету, 2) API ключи, 3) SSL настройки',
            'original_description' => $description
        ];
    }
    
    /**
     * Перевод эффектов зелья
     */
    public function translatePotionEffects($effects, $target_language = 'ru') {
        if ($target_language === 'en' || empty($effects)) {
            return $effects; // Уже на английском или пустой
        }
        
        $cache_key = "potion_effects_" . md5(json_encode($effects) . '_' . $target_language);
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        $prompt = $this->buildPotionEffectsTranslationPrompt($effects, $target_language);
        $response = $this->callAiApi($prompt);
        
        if ($response) {
            $this->cacheData($cache_key, $response);
            return $response;
        }
        
        // Возвращаем детальную ошибку для диагностики
        return [
            'error' => 'AI API недоступен',
            'message' => 'Не удалось перевести эффекты зелья',
            'details' => 'Проверьте: 1) Подключение к интернету, 2) API ключи, 3) SSL настройки',
            'original_effects' => $effects
        ];
    }
    
    /**
     * Полный перевод зелья
     */
    public function translatePotion($potion_data, $target_language = 'ru') {
        if ($target_language === 'en') {
            return $potion_data; // Уже на английском
        }
        
        $translated_potion = $potion_data;
        
        // Переводим название
        if (isset($potion_data['name'])) {
            $translated_name = $this->translatePotionName($potion_data['name'], $target_language);
            if (is_string($translated_name)) {
                $translated_potion['name'] = $translated_name;
            } else {
                logMessage('WARNING', 'Не удалось перевести название зелья: ' . $potion_data['name']);
            }
        }
        
        // Переводим описание
        if (isset($potion_data['description'])) {
            $translated_desc = $this->translatePotionDescription($potion_data['description'], $target_language);
            if (is_string($translated_desc)) {
                $translated_potion['description'] = $translated_desc;
            } else {
                logMessage('WARNING', 'Не удалось перевести описание зелья: ' . $potion_data['name']);
            }
        }
        
        // Переводим эффекты
        if (isset($potion_data['effects']) && is_array($potion_data['effects'])) {
            $translated_effects = $this->translatePotionEffects($potion_data['effects'], $target_language);
            if (is_array($translated_effects)) {
                $translated_potion['effects'] = $translated_effects;
            } else {
                logMessage('WARNING', 'Не удалось перевести эффекты зелья: ' . $potion_data['name']);
            }
        }
        
        return $translated_potion;
    }
    
    /**
     * Построение промпта для перевода названия зелья
     */
    private function buildPotionNameTranslationPrompt($name, $target_language) {
        $language_name = $target_language === 'ru' ? 'русский' : 'английский';
        
        return "Переведи название зелья D&D с английского на {$language_name} язык:

Название: {$name}

Требования к переводу:
- Сохрани магическую атмосферу D&D
- Используй подходящие термины для фэнтези
- Название должно звучать естественно на {$language_name} языке
- Если это известное зелье (например, Potion of Healing), используй стандартный перевод
- Верни только переведенное название, без дополнительных объяснений

Переведенное название:";
    }
    
    /**
     * Построение промпта для перевода описания зелья
     */
    private function buildPotionDescriptionTranslationPrompt($description, $target_language) {
        $language_name = $target_language === 'ru' ? 'русский' : 'английский';
        
        return "Переведи описание зелья D&D с английского на {$language_name} язык:

Описание: {$description}

Требования к переводу:
- Сохрани все игровые механики и числа
- Используй терминологию D&D на {$language_name} языке
- Сохрани форматирование и структуру
- Переведи все игровые термины (hit points, damage, advantage, etc.)
- Верни только переведенное описание, без дополнительных объяснений

Переведенное описание:";
    }
    
    /**
     * Построение промпта для перевода эффектов зелья
     */
    private function buildPotionEffectsTranslationPrompt($effects, $target_language) {
        $language_name = $target_language === 'ru' ? 'русский' : 'английский';
        $effects_text = is_array($effects) ? implode(', ', $effects) : $effects;
        
        return "Переведи эффекты зелья D&D с английского на {$language_name} язык:

Эффекты: {$effects_text}

Требования к переводу:
- Переведи каждый эффект отдельно
- Используй игровую терминологию D&D на {$language_name} языке
- Сохрани названия типов урона (fire, cold, lightning, etc.)
- Верни список переведенных эффектов через запятую
- Без дополнительных объяснений

Переведенные эффекты:";
    }

}
?>
