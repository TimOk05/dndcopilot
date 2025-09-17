<?php
/**
 * External API Service - Универсальный сервис для работы с внешними API
 * Поддерживает различные внешние библиотеки и API для расширения функциональности D&D
 */

class ExternalApiService {
    private $api_endpoints = [
        // D&D API
        'dnd5eapi' => 'https://www.dnd5eapi.co/api',
        'open5e' => 'https://api.open5e.com',
        'dnd_su' => 'https://dnd.su/api', // Русский справочник D&D
        'srd_dnd' => 'https://srd.dnd-5e.org/api', // System Reference Document
        
        // Генерация имен
        'fantasy_names' => 'https://www.fantasynamegenerators.com/api',
        'name_generator' => 'https://namegenerator.biz/api',
        
        // Бросок костей
        'dice_roll' => 'https://roll.diceapi.com',
        'dice_api' => 'https://api.diceapi.com',
        
        // Погода
        'weather' => 'https://api.openweathermap.org/data/2.5/weather',
        
        // Переводы
        'translate' => 'https://translation.googleapis.com/language/translate/v2',
        
        // Генерация изображений
        'image_generation' => 'https://api.openai.com/v1/images/generations',
        
        // Дополнительные D&D ресурсы
        'dnd_wiki' => 'https://dnd.wiki/api',
        'spell_api' => 'https://spell-api.com',
        'monster_api' => 'https://monster-api.com',
        
        // Генерация контента
        'lore_generator' => 'https://lore-generator.com/api',
        'quest_generator' => 'https://quest-generator.com/api',
        'npc_generator' => 'https://npc-generator.com/api'
    ];
    
    private $api_keys = [];
    private $cache_duration = 3600; // 1 час
    private $cache_dir;
    private $http_service;
    
    public function __construct() {
        $this->api_keys = [
            'weather' => getApiKey('openweathermap') ?? '',
            'translate' => getApiKey('google') ?? '',
            'image_generation' => getApiKey('openai') ?? ''
        ];
        
        $this->cache_dir = __DIR__ . '/../../data/cache/external_api/';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        // Инициализируем HTTP сервис
        require_once __DIR__ . '/PowerShellHttpService.php';
        $this->http_service = new PowerShellHttpService();
        
        logMessage('INFO', 'ExternalApiService инициализирован');
    }
    
    /**
     * Генерация имен персонажей
     */
    public function generateCharacterNames($race = 'human', $gender = 'any', $count = 1) {
        $cache_key = "names_{$race}_{$gender}_{$count}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        try {
            // Используем локальную генерацию имен как fallback
            $names = $this->generateLocalNames($race, $gender, $count);
            
            $result = [
                'success' => true,
                'names' => $names,
                'race' => $race,
                'gender' => $gender,
                'count' => count($names),
                'source' => 'local_generator'
            ];
            
            $this->cacheData($cache_key, $result);
            return $result;
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка генерации имен: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Бросок костей D&D
     */
    public function rollDice($dice_string = '1d20') {
        $cache_key = "dice_{$dice_string}_" . time(); // Не кэшируем броски костей
        
        try {
            // Парсим строку костей (например, "2d6+3", "1d20", "3d4-1")
            $result = $this->parseDiceString($dice_string);
            
            if (!$result) {
                throw new Exception("Неверный формат костей: $dice_string");
            }
            
            $rolls = [];
            $total = 0;
            
            for ($i = 0; $i < $result['count']; $i++) {
                $roll = rand(1, $result['sides']);
                $rolls[] = $roll;
                $total += $roll;
            }
            
            $final_total = $total + $result['modifier'];
            
            $dice_result = [
                'success' => true,
                'dice_string' => $dice_string,
                'rolls' => $rolls,
                'total' => $final_total,
                'breakdown' => [
                    'dice_count' => $result['count'],
                    'dice_sides' => $result['sides'],
                    'modifier' => $result['modifier'],
                    'dice_total' => $total,
                    'final_total' => $final_total
                ],
                'timestamp' => time()
            ];
            
            return $dice_result;
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка броска костей: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение погоды для локации
     */
    public function getWeather($location = 'Moscow') {
        $cache_key = "weather_{$location}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        if (empty($this->api_keys['weather'])) {
            return [
                'success' => false,
                'error' => 'API ключ для погоды не настроен'
            ];
        }
        
        try {
            $url = $this->api_endpoints['weather'] . "?q={$location}&appid=" . $this->api_keys['weather'] . "&units=metric&lang=ru";
            
            $data = $this->http_service->get($url);
            
            if ($data && isset($data['main'])) {
                $weather_result = [
                    'success' => true,
                    'location' => $data['name'] ?? $location,
                    'temperature' => $data['main']['temp'] ?? 0,
                    'feels_like' => $data['main']['feels_like'] ?? 0,
                    'humidity' => $data['main']['humidity'] ?? 0,
                    'pressure' => $data['main']['pressure'] ?? 0,
                    'description' => $data['weather'][0]['description'] ?? 'Неизвестно',
                    'wind_speed' => $data['wind']['speed'] ?? 0,
                    'timestamp' => time()
                ];
                
                $this->cacheData($cache_key, $weather_result);
                return $weather_result;
            } else {
                throw new Exception('Неверный ответ от API погоды');
            }
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка получения погоды: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Перевод текста
     */
    public function translateText($text, $target_language = 'ru', $source_language = 'en') {
        if (empty($this->api_keys['translate'])) {
            return [
                'success' => false,
                'error' => 'API ключ для переводов не настроен'
            ];
        }
        
        try {
            $url = $this->api_endpoints['translate'] . "?key=" . $this->api_keys['translate'];
            
            $post_data = [
                'q' => $text,
                'target' => $target_language,
                'source' => $source_language,
                'format' => 'text'
            ];
            
            // Используем PowerShell для POST запроса
            $temp_file = tempnam(sys_get_temp_dir(), 'translate_');
            $ps_command = sprintf(
                'powershell -Command "$body = \'%s\'; Invoke-WebRequest -Uri \'%s\' -Method POST -Body $body -ContentType \'application/x-www-form-urlencoded\' -OutFile \'%s\'"',
                http_build_query($post_data),
                $url,
                $temp_file
            );
            
            exec($ps_command, $output, $return_code);
            
            if ($return_code === 0 && file_exists($temp_file)) {
                $response = file_get_contents($temp_file);
                $data = json_decode($response, true);
                
                if ($data && isset($data['data']['translations'][0]['translatedText'])) {
                    $translated_text = $data['data']['translations'][0]['translatedText'];
                    
                    unlink($temp_file);
                    
                    return [
                        'success' => true,
                        'original_text' => $text,
                        'translated_text' => $translated_text,
                        'source_language' => $source_language,
                        'target_language' => $target_language
                    ];
                }
            }
            
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
            
            throw new Exception('Неверный ответ от API переводов');
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка перевода: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Генерация изображений
     */
    public function generateImage($prompt, $size = '512x512', $count = 1) {
        if (empty($this->api_keys['image_generation'])) {
            return [
                'success' => false,
                'error' => 'API ключ для генерации изображений не настроен'
            ];
        }
        
        try {
            $url = $this->api_endpoints['image_generation'];
            
            $post_data = [
                'prompt' => $prompt,
                'n' => $count,
                'size' => $size,
                'response_format' => 'url'
            ];
            
            // Используем PowerShell для POST запроса
            $temp_file = tempnam(sys_get_temp_dir(), 'image_gen_');
            $ps_command = sprintf(
                'powershell -Command "$headers = @{\'Authorization\' = \'Bearer %s\'; \'Content-Type\' = \'application/json\'}; $body = \'%s\'; Invoke-WebRequest -Uri \'%s\' -Method POST -Headers $headers -Body $body -OutFile \'%s\'"',
                $this->api_keys['image_generation'],
                json_encode($post_data),
                $url,
                $temp_file
            );
            
            exec($ps_command, $output, $return_code);
            
            if ($return_code === 0 && file_exists($temp_file)) {
                $response = file_get_contents($temp_file);
                $data = json_decode($response, true);
                
                if ($data && isset($data['data'])) {
                    $images = [];
                    foreach ($data['data'] as $image_data) {
                        $images[] = $image_data['url'];
                    }
                    
                    unlink($temp_file);
                    
                    return [
                        'success' => true,
                        'prompt' => $prompt,
                        'images' => $images,
                        'count' => count($images)
                    ];
                }
            }
            
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
            
            throw new Exception('Неверный ответ от API генерации изображений');
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка генерации изображения: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение информации о заклинаниях из дополнительных источников
     */
    public function getSpellInfo($spell_name) {
        $cache_key = "spell_{$spell_name}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        try {
            // Пробуем разные источники
            $sources = ['dnd5eapi', 'open5e', 'spell_api'];
            $spell_data = null;
            
            foreach ($sources as $source) {
                try {
                    $spell_data = $this->getSpellFromSource($spell_name, $source);
                    if ($spell_data) {
                        break;
                    }
                } catch (Exception $e) {
                    logMessage('WARNING', "Ошибка получения заклинания из {$source}: " . $e->getMessage());
                    continue;
                }
            }
            
            if ($spell_data) {
                $result = [
                    'success' => true,
                    'spell' => $spell_data,
                    'source' => $source ?? 'unknown'
                ];
                
                $this->cacheData($cache_key, $result);
                return $result;
            } else {
                throw new Exception('Заклинание не найдено в доступных источниках');
            }
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка получения заклинания: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение информации о монстрах из дополнительных источников
     */
    public function getMonsterInfo($monster_name) {
        $cache_key = "monster_{$monster_name}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        try {
            // Пробуем разные источники
            $sources = ['dnd5eapi', 'open5e', 'monster_api'];
            $monster_data = null;
            
            foreach ($sources as $source) {
                try {
                    $monster_data = $this->getMonsterFromSource($monster_name, $source);
                    if ($monster_data) {
                        break;
                    }
                } catch (Exception $e) {
                    logMessage('WARNING', "Ошибка получения монстра из {$source}: " . $e->getMessage());
                    continue;
                }
            }
            
            if ($monster_data) {
                $result = [
                    'success' => true,
                    'monster' => $monster_data,
                    'source' => $source ?? 'unknown'
                ];
                
                $this->cacheData($cache_key, $result);
                return $result;
            } else {
                throw new Exception('Монстр не найден в доступных источниках');
            }
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка получения монстра: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Генерация квестов
     */
    public function generateQuest($quest_type = 'adventure', $difficulty = 'medium', $theme = 'fantasy') {
        $cache_key = "quest_{$quest_type}_{$difficulty}_{$theme}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        try {
            // Используем AI для генерации квеста
            require_once __DIR__ . '/ai-service.php';
            $ai_service = new AiService();
            
            $prompt = "Создай квест для D&D 5e:\n";
            $prompt .= "Тип: {$quest_type}\n";
            $prompt .= "Сложность: {$difficulty}\n";
            $prompt .= "Тема: {$theme}\n\n";
            $prompt .= "Включи:\n";
            $prompt .= "- Краткое описание квеста\n";
            $prompt .= "- Цель квеста\n";
            $prompt .= "- Потенциальные препятствия\n";
            $prompt .= "- Награды\n";
            $prompt .= "- Советы для мастера\n\n";
            $prompt .= "Сделай описание живым и интересным (2-3 абзаца).";
            
            $ai_response = $ai_service->generateText($prompt);
            
            if (isset($ai_response['error'])) {
                throw new Exception($ai_response['message'] ?? 'Ошибка AI генерации');
            }
            
            $quest_data = [
                'type' => $quest_type,
                'difficulty' => $difficulty,
                'theme' => $theme,
                'description' => $ai_response['text'] ?? $ai_response,
                'generated_at' => time()
            ];
            
            $result = [
                'success' => true,
                'quest' => $quest_data,
                'source' => 'ai_generation'
            ];
            
            $this->cacheData($cache_key, $result);
            return $result;
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка генерации квеста: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Генерация лора и предыстории
     */
    public function generateLore($lore_type = 'location', $setting = 'medieval', $mood = 'mysterious') {
        $cache_key = "lore_{$lore_type}_{$setting}_{$mood}";
        $cached = $this->getCachedData($cache_key);
        if ($cached) {
            return $cached;
        }
        
        try {
            // Используем AI для генерации лора
            require_once __DIR__ . '/ai-service.php';
            $ai_service = new AiService();
            
            $prompt = "Создай лор для D&D 5e:\n";
            $prompt .= "Тип: {$lore_type}\n";
            $prompt .= "Сеттинг: {$setting}\n";
            $prompt .= "Настроение: {$mood}\n\n";
            $prompt .= "Включи:\n";
            $prompt .= "- Подробное описание\n";
            $prompt .= "- Исторические детали\n";
            $prompt .= "- Интересные особенности\n";
            $prompt .= "- Связи с игровым миром\n\n";
            $prompt .= "Сделай описание атмосферным и погружающим (3-4 абзаца).";
            
            $ai_response = $ai_service->generateText($prompt);
            
            if (isset($ai_response['error'])) {
                throw new Exception($ai_response['message'] ?? 'Ошибка AI генерации');
            }
            
            $lore_data = [
                'type' => $lore_type,
                'setting' => $setting,
                'mood' => $mood,
                'description' => $ai_response['text'] ?? $ai_response,
                'generated_at' => time()
            ];
            
            $result = [
                'success' => true,
                'lore' => $lore_data,
                'source' => 'ai_generation'
            ];
            
            $this->cacheData($cache_key, $result);
            return $result;
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка генерации лора: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение заклинания из конкретного источника
     */
    private function getSpellFromSource($spell_name, $source) {
        switch ($source) {
            case 'dnd5eapi':
                $url = $this->api_endpoints['dnd5eapi'] . "/spells/" . urlencode($spell_name);
                break;
            case 'open5e':
                $url = $this->api_endpoints['open5e'] . "/spells/" . urlencode($spell_name);
                break;
            case 'spell_api':
                $url = $this->api_endpoints['spell_api'] . "/spell/" . urlencode($spell_name);
                break;
            default:
                throw new Exception("Неизвестный источник: {$source}");
        }
        
        $data = $this->http_service->get($url);
        
        if (!$data) {
            throw new Exception("Нет данных от источника {$source}");
        }
        
        return $data;
    }
    
    /**
     * Получение монстра из конкретного источника
     */
    private function getMonsterFromSource($monster_name, $source) {
        switch ($source) {
            case 'dnd5eapi':
                $url = $this->api_endpoints['dnd5eapi'] . "/monsters/" . urlencode($monster_name);
                break;
            case 'open5e':
                $url = $this->api_endpoints['open5e'] . "/monsters/" . urlencode($monster_name);
                break;
            case 'monster_api':
                $url = $this->api_endpoints['monster_api'] . "/monster/" . urlencode($monster_name);
                break;
            default:
                throw new Exception("Неизвестный источник: {$source}");
        }
        
        $data = $this->http_service->get($url);
        
        if (!$data) {
            throw new Exception("Нет данных от источника {$source}");
        }
        
        return $data;
    }
    
    /**
     * Локальная генерация имен
     */
    private function generateLocalNames($race, $gender, $count) {
        $name_templates = [
            'human' => [
                'male' => ['Александр', 'Дмитрий', 'Максим', 'Сергей', 'Андрей', 'Алексей', 'Артём', 'Илья', 'Кирилл', 'Михаил'],
                'female' => ['Анна', 'Мария', 'Елена', 'Наталья', 'Ольга', 'Татьяна', 'Ирина', 'Екатерина', 'Светлана', 'Юлия'],
                'any' => ['Александр', 'Дмитрий', 'Максим', 'Сергей', 'Андрей', 'Анна', 'Мария', 'Елена', 'Наталья', 'Ольга']
            ],
            'elf' => [
                'male' => ['Элронд', 'Леголас', 'Трандуил', 'Эрестор', 'Глорфиндел', 'Келеборн', 'Элронд', 'Амрот', 'Гил-галад', 'Финрод'],
                'female' => ['Арвен', 'Галадриэль', 'Нимродель', 'Лютиэн', 'Ариэль', 'Элвинг', 'Идриль', 'Анкалаимэ', 'Эарендиль', 'Тинвиэль'],
                'any' => ['Элронд', 'Леголас', 'Арвен', 'Галадриэль', 'Трандуил', 'Нимродель', 'Эрестор', 'Лютиэн', 'Глорфиндел', 'Ариэль']
            ],
            'dwarf' => [
                'male' => ['Торин', 'Балин', 'Двалин', 'Фили', 'Кили', 'Оин', 'Глоин', 'Бифур', 'Бофур', 'Бомбур'],
                'female' => ['Дис', 'Тордис', 'Фрида', 'Хельга', 'Сигрид', 'Астрид', 'Ингеборг', 'Гудрун', 'Сигурд', 'Брунхильда'],
                'any' => ['Торин', 'Балин', 'Дис', 'Двалин', 'Тордис', 'Фили', 'Фрида', 'Кили', 'Хельга', 'Оин']
            ],
            'orc' => [
                'male' => ['Грумш', 'Луртз', 'Шарк', 'Горбаг', 'Углук', 'Азог', 'Болг', 'Гришнак', 'Мог', 'Снага'],
                'female' => ['Углук', 'Шарк', 'Горбаг', 'Луртз', 'Азог', 'Болг', 'Гришнак', 'Мог', 'Снага', 'Грумш'],
                'any' => ['Грумш', 'Луртз', 'Шарк', 'Горбаг', 'Углук', 'Азог', 'Болг', 'Гришнак', 'Мог', 'Снага']
            ]
        ];
        
        $race_lower = strtolower($race);
        $gender_lower = strtolower($gender);
        
        if (!isset($name_templates[$race_lower])) {
            $race_lower = 'human';
        }
        
        if (!isset($name_templates[$race_lower][$gender_lower])) {
            $gender_lower = 'any';
        }
        
        $names = $name_templates[$race_lower][$gender_lower];
        $selected_names = [];
        
        for ($i = 0; $i < $count; $i++) {
            $selected_names[] = $names[array_rand($names)];
        }
        
        return $selected_names;
    }
    
    /**
     * Парсинг строки костей
     */
    private function parseDiceString($dice_string) {
        // Поддерживаемые форматы: "1d20", "2d6+3", "3d4-1", "1d100"
        if (preg_match('/^(\d+)d(\d+)([+-]\d+)?$/', $dice_string, $matches)) {
            return [
                'count' => (int)$matches[1],
                'sides' => (int)$matches[2],
                'modifier' => isset($matches[3]) ? (int)$matches[3] : 0
            ];
        }
        
        return false;
    }
    
    /**
     * Кэширование данных
     */
    private function cacheData($key, $data) {
        try {
            $filename = $this->cache_dir . md5($key) . '.json';
            $cache_data = [
                'timestamp' => time(),
                'data' => $data
            ];
            
            file_put_contents($filename, json_encode($cache_data, JSON_UNESCAPED_UNICODE));
            logMessage('INFO', "Внешние API данные закэшированы: {$key}");
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка кэширования внешних API: {$key} - " . $e->getMessage());
        }
    }
    
    /**
     * Получение кэшированных данных
     */
    private function getCachedData($key) {
        try {
            $filename = $this->cache_dir . md5($key) . '.json';
            
            if (!file_exists($filename)) {
                return null;
            }
            
            $file_content = file_get_contents($filename);
            $cache_data = json_decode($file_content, true);
            
            if (!$cache_data || !isset($cache_data['timestamp']) || !isset($cache_data['data'])) {
                return null;
            }
            
            // Проверяем срок действия кэша
            if (time() - $cache_data['timestamp'] > $this->cache_duration) {
                unlink($filename);
                return null;
            }
            
            logMessage('INFO', "Внешние API данные получены из кэша: {$key}");
            return $cache_data['data'];
            
        } catch (Exception $e) {
            logMessage('ERROR', "Ошибка чтения кэша внешних API: {$key} - " . $e->getMessage());
            return null;
        }
    }
}
?>
