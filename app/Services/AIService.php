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
     * Возвращает fallback ответ, используя данные из JSON файлов
     */
    private function getFallbackResponse($prompt) {
        // Извлекаем данные персонажа из промпта
        $characterData = $this->extractCharacterDataFromPrompt($prompt);
        
        if (strpos($prompt, 'описание персонажа') !== false) {
            return $this->generateDescriptionFromJSON($characterData);
        }
        
        if (strpos($prompt, 'предыстория') !== false) {
            return $this->generateBackgroundFromJSON($characterData);
        }
        
        return "Персонаж готов к приключениям и имеет все необходимые навыки для успешного путешествия.";
    }
    
    /**
     * Извлекает данные персонажа из промпта
     */
    private function extractCharacterDataFromPrompt($prompt) {
        $data = [];
        
        // Извлекаем имя
        if (preg_match('/Имя: ([^\n]+)/', $prompt, $matches)) {
            $data['name'] = trim($matches[1]);
        }
        
        // Извлекаем расу
        if (preg_match('/Раса: ([^\n]+)/', $prompt, $matches)) {
            $data['race'] = trim($matches[1]);
        }
        
        // Извлекаем класс
        if (preg_match('/Класс: ([^\n]+)/', $prompt, $matches)) {
            $data['class'] = trim($matches[1]);
        }
        
        // Извлекаем уровень
        if (preg_match('/Уровень: (\d+)/', $prompt, $matches)) {
            $data['level'] = (int)$matches[1];
        }
        
        // Извлекаем мировоззрение
        if (preg_match('/Мировоззрение: ([^\n]+)/', $prompt, $matches)) {
            $data['alignment'] = trim($matches[1]);
        }
        
        // Извлекаем характеристики
        if (preg_match('/Характеристики: СИЛ (\d+), ЛОВ (\d+), ТЕЛ (\d+), ИНТ (\d+), МДР (\d+), ХАР (\d+)/', $prompt, $matches)) {
            $data['abilities'] = [
                'str' => (int)$matches[1],
                'dex' => (int)$matches[2],
                'con' => (int)$matches[3],
                'int' => (int)$matches[4],
                'wis' => (int)$matches[5],
                'cha' => (int)$matches[6]
            ];
        }
        
        return $data;
    }
    
    /**
     * Генерирует описание персонажа на основе данных из JSON файлов
     */
    private function generateDescriptionFromJSON($data) {
        $description = [];
        
        // Загружаем данные расы из JSON
        if (isset($data['race'])) {
            $raceData = $this->loadRaceData($data['race']);
            if ($raceData) {
                $description[] = $this->generateAppearanceFromJSON($raceData, $data['abilities'] ?? []);
                $description[] = $this->generateRaceTraitsFromJSON($raceData);
            }
        }
        
        // Загружаем данные класса из JSON
        if (isset($data['class'])) {
            $classData = $this->loadClassData($data['class']);
            if ($classData) {
                $description[] = $this->generateClassFeaturesFromJSON($classData, $data['level'] ?? 1);
            }
        }
        
        return implode("\n\n", array_filter($description));
    }
    
    /**
     * Генерирует предысторию персонажа на основе данных из JSON файлов
     */
    private function generateBackgroundFromJSON($data) {
        $background = [];
        
        // Загружаем данные расы из JSON
        if (isset($data['race'])) {
            $raceData = $this->loadRaceData($data['race']);
            if ($raceData) {
                $background[] = $this->generateRaceBackgroundFromJSON($raceData);
            }
        }
        
        // Загружаем данные класса из JSON
        if (isset($data['class'])) {
            $classData = $this->loadClassData($data['class']);
            if ($classData) {
                $background[] = $this->generateClassBackgroundFromJSON($classData, $data['level'] ?? 1);
            }
        }
        
        return implode("\n\n", array_filter($background));
    }
    
    /**
     * Загружает данные расы из JSON файла
     */
    private function loadRaceData($raceId) {
        $racesFile = __DIR__ . '/../../data/персонажи/расы/races.json';
        if (!file_exists($racesFile)) {
            return null;
        }
        
        $racesData = json_decode(file_get_contents($racesFile), true);
        if (!$racesData || !isset($racesData['races'])) {
            return null;
        }
        
        // Ищем расу по ID или имени
        foreach ($racesData['races'] as $race) {
            if ($race['id'] === $raceId || 
                strtolower($race['name']) === strtolower($raceId) ||
                strtolower($race['name_en']) === strtolower($raceId)) {
                return $race;
            }
        }
        
        return null;
    }
    
    /**
     * Загружает данные класса из JSON файла
     */
    private function loadClassData($classId) {
        $classDir = __DIR__ . '/../../data/персонажи/классы/' . strtolower($classId);
        $classFile = $classDir . '/' . strtolower($classId) . '.json';
        
        if (!file_exists($classFile)) {
            return null;
        }
        
        $classData = json_decode(file_get_contents($classFile), true);
        return $classData['class'] ?? null;
    }
    
    /**
     * Генерирует описание внешности на основе данных из JSON
     */
    private function generateAppearanceFromJSON($raceData, $abilities) {
        $appearance = "Внешность: ";
        
        // Используем данные из JSON о размере и характеристиках расы
        if (isset($raceData['size'])) {
            $appearance .= $this->getSizeDescription($raceData['size']);
        }
        
        if (isset($raceData['height_ft'])) {
            $appearance .= " Рост около {$raceData['height_ft']} футов.";
        }
        
        if (isset($raceData['weight_lb'])) {
            $appearance .= " Вес в пределах {$raceData['weight_lb']}.";
        }
        
        // Добавляем детали на основе характеристик
        if (!empty($abilities)) {
            $details = [];
            if ($abilities['str'] >= 16) $details[] = 'мощного телосложения';
            if ($abilities['dex'] >= 16) $details[] = 'ловкий и подвижный';
            if ($abilities['con'] >= 16) $details[] = 'здоровый и выносливый';
            if ($abilities['int'] >= 16) $details[] = 'с умным взглядом';
            if ($abilities['wis'] >= 16) $details[] = 'с проницательными глазами';
            if ($abilities['cha'] >= 16) $details[] = 'с харизматичной внешностью';
            
            if (!empty($details)) {
                $appearance .= ' ' . ucfirst(implode(', ', $details)) . '.';
            }
        }
        
        return $appearance;
    }
    
    /**
     * Получает описание размера
     */
    private function getSizeDescription($size) {
        $sizeDescriptions = [
            'Small' => 'Небольшого размера',
            'Medium' => 'Среднего размера',
            'Large' => 'Крупного размера'
        ];
        
        return $sizeDescriptions[$size] ?? 'Среднего размера';
    }
    
    /**
     * Генерирует расовые черты на основе данных из JSON
     */
    private function generateRaceTraitsFromJSON($raceData) {
        if (!isset($raceData['traits']) || empty($raceData['traits'])) {
            return '';
        }
        
        $traits = "Расовые черты:\n";
        foreach ($raceData['traits'] as $trait) {
            $traits .= "• {$trait['name']}: {$trait['description']}\n";
        }
        
        return trim($traits);
    }
    
    /**
     * Генерирует классовые способности на основе данных из JSON
     */
    private function generateClassFeaturesFromJSON($classData, $level) {
        if (!isset($classData['class_features']) || empty($classData['class_features'])) {
            return '';
        }
        
        $features = "Классовые способности:\n";
        foreach ($classData['class_features'] as $feature) {
            if ($feature['level'] <= $level) {
                $features .= "• {$feature['name']} (ур. {$feature['level']}): {$feature['description']}\n";
            }
        }
        
        return trim($features);
    }
    
    /**
     * Генерирует предысторию расы на основе данных из JSON
     */
    private function generateRaceBackgroundFromJSON($raceData) {
        $background = '';
        
        if (isset($raceData['name'])) {
            $background .= "Раса: {$raceData['name']}\n";
        }
        
        if (isset($raceData['alignment'])) {
            $background .= "Типичное мировоззрение: {$raceData['alignment']}\n";
        }
        
        if (isset($raceData['lifespan_years'])) {
            $lifespan = $raceData['lifespan_years'];
            $background .= "Продолжительность жизни: {$lifespan['min']}-{$lifespan['max']} лет (в среднем {$lifespan['avg']})\n";
        }
        
        if (isset($raceData['languages'])) {
            $background .= "Языки: " . implode(', ', $raceData['languages']) . "\n";
        }
        
        return trim($background);
    }
    
    /**
     * Генерирует предысторию класса на основе данных из JSON
     */
    private function generateClassBackgroundFromJSON($classData, $level) {
        $background = '';
        
        if (isset($classData['name']['ru'])) {
            $background .= "Класс: {$classData['name']['ru']} (уровень {$level})\n";
        }
        
        if (isset($classData['hit_die'])) {
            $background .= "Кость хитов: {$classData['hit_die']}\n";
        }
        
        if (isset($classData['primary_abilities'])) {
            $background .= "Основные характеристики: " . implode(', ', $classData['primary_abilities']) . "\n";
        }
        
        if (isset($classData['saving_throws'])) {
            $background .= "Спасброски: " . implode(', ', $classData['saving_throws']) . "\n";
        }
        
        if (isset($classData['armor_proficiencies'])) {
            $background .= "Владение доспехами: " . implode(', ', $classData['armor_proficiencies']) . "\n";
        }
        
        if (isset($classData['weapon_proficiencies'])) {
            $background .= "Владение оружием: " . implode(', ', $classData['weapon_proficiencies']) . "\n";
        }
        
        return trim($background);
    }
    
}
?>
