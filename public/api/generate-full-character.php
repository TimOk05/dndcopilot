<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Подключаем конфигурацию
require_once __DIR__ . '/../../config/config.php';

class FullCharacterGenerator {
    private $dataPath;
    private $namesPath;
    
    public function __construct() {
        $this->dataPath = __DIR__ . '/../../data/персонажи/';
        $this->namesPath = __DIR__ . '/../../names/';
    }
    
    /**
     * Загружает данные из JSON файла
     */
    private function loadJsonData($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Файл не найден: $filePath");
        }
        
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Ошибка парсинга JSON: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Генерирует имя для персонажа
     */
    private function generateName($race, $gender) {
        // Маппинг рас на файлы имен
        $raceMapping = [
            'human' => 'human_names.json',
            'elf' => 'elf_names.json',
            'dwarf' => 'dwarf_names.json',
            'halfling' => 'halfling_names.json',
            'gnome' => 'gnome_names.json',
            'half_elf' => 'half-elf_names.json',
            'half_orc' => 'half-orc_names.json',
            'tiefling' => 'tiefling_names.json',
            'dragonborn' => 'dragonborn_names.json',
            'aasimar' => 'aasimar_names.json',
            'aarakocra' => 'aarakocra_names.json',
            'goblin' => 'goblin_names.json',
            'goliath' => 'goliath_names.json',
            'kenku' => 'kenku_names.json',
            'lizardfolk' => 'lizardfolk_names.json',
            'orc' => 'orc_names.json',
            'tabaxi' => 'tabaxi_names.json',
            'yuan_ti' => 'yuan-ti_names.json'
        ];
        
        $fileName = $raceMapping[$race] ?? 'human_names.json';
        $filePath = $this->namesPath . $fileName;
        
        if (!file_exists($filePath)) {
            // Fallback к общим именам
            $filePath = $this->namesPath . 'human_names.json';
        }
        
        try {
            $namesData = $this->loadJsonData($filePath);
            
            // Выбираем пол
            $genderKey = $gender === 'female' ? 'female' : 'male';
            if (!isset($namesData[$genderKey]) || empty($namesData[$genderKey])) {
                $genderKey = 'male'; // Fallback
            }
            
            $names = $namesData[$genderKey];
            $selectedName = $names[array_rand($names)];
            
            // Добавляем фамилию если есть
            if (isset($namesData['surnames']) && !empty($namesData['surnames'])) {
                $surname = $namesData['surnames'][array_rand($namesData['surnames'])];
                $selectedName .= ' ' . $surname;
            }
            
            return $selectedName;
            
        } catch (Exception $e) {
            // Fallback имена
            $fallbackNames = [
                'male' => ['Александр', 'Дмитрий', 'Максим', 'Сергей', 'Андрей'],
                'female' => ['Анна', 'Мария', 'Елена', 'Ольга', 'Татьяна']
            ];
            
            $genderKey = $gender === 'female' ? 'female' : 'male';
            $names = $fallbackNames[$genderKey];
            return $names[array_rand($names)];
        }
    }
    
    /**
     * Генерирует описание и предысторию с помощью ИИ
     */
    private function generateWithAI($character) {
        $apiKey = getApiKey('deepseek');
        
        if (empty($apiKey)) {
            return [
                'description' => $this->generateFallbackDescription($character),
                'background' => $this->generateFallbackBackground($character)
            ];
        }
        
        $prompt = "Создай описание и предысторию для персонажа D&D 5e:

Раса: {$character['race']['name']}
Класс: {$character['class']['name']}
Уровень: {$character['level']}
Пол: {$character['gender']}
Имя: {$character['name']}
Мировоззрение: {$character['alignment']}
Возраст: {$character['age']}

Характеристики:
- Сила: {$character['ability_scores']['STR']}
- Ловкость: {$character['ability_scores']['DEX']}
- Телосложение: {$character['ability_scores']['CON']}
- Интеллект: {$character['ability_scores']['INT']}
- Мудрость: {$character['ability_scores']['WIS']}
- Харизма: {$character['ability_scores']['CHA']}

Создай:
1. Краткое описание внешности и характера (2-3 предложения)
2. Интересную предысторию на 2-3 абзаца

Отвечай только на русском языке, без дублирования на английском.";

        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.8
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            
            if (isset($result['choices'][0]['message']['content'])) {
                $content = $result['choices'][0]['message']['content'];
                
                // Парсим ответ для разделения описания и предыстории
                $parts = preg_split('/\n\s*\n/', trim($content));
                
                $description = '';
                $background = '';
                
                if (count($parts) >= 2) {
                    $description = trim($parts[0]);
                    $background = trim(implode("\n\n", array_slice($parts, 1)));
                } else {
                    // Если не удалось разделить, используем весь текст как описание
                    $description = trim($content);
                    $background = $this->generateFallbackBackground($character);
                }
                
                return [
                    'description' => $description,
                    'background' => $background
                ];
            }
        }
        
        // Fallback если ИИ не сработал
        return [
            'description' => $this->generateFallbackDescription($character),
            'background' => $this->generateFallbackBackground($character)
        ];
    }
    
    /**
     * Генерирует fallback описание
     */
    private function generateFallbackDescription($character) {
        $race = $character['race']['name'];
        $class = $character['class']['name'];
        $gender = $character['gender'] === 'female' ? 'женщина' : 'мужчина';
        
        $descriptions = [
            "{$gender} {$race} {$class} с характерными чертами своей расы и профессии.",
            "{$race} {$class}, чья внешность отражает особенности его народа и выбранного пути.",
            "{$gender} {$race}, избравший путь {$class} и несущий на себе отпечаток своего происхождения."
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    /**
     * Генерирует fallback предысторию
     */
    private function generateFallbackBackground($character) {
        $race = $character['race']['name'];
        $class = $character['class']['name'];
        $alignment = $character['alignment'];
        
        $backgrounds = [
            "{$race} {$class} вырос в обычной семье, но судьба привела его на путь приключений. Его навыки и способности развивались через опыт и обучение.",
            "Происхождение этого {$race} {$class} скрывает множество тайн. Он выбрал свой путь, руководствуясь внутренними убеждениями и стремлением к справедливости.",
            "{$race} {$class} начал свой путь как обычный представитель своего народа, но события жизни заставили его взять в руки оружие и отправиться в мир приключений."
        ];
        
        return $backgrounds[array_rand($backgrounds)];
    }
    
    /**
     * Генерирует полного персонажа
     */
    public function generateFullCharacter($baseCharacter, $useAI = true) {
        try {
            // Генерируем имя
            $baseCharacter['name'] = $this->generateName($baseCharacter['race']['id'], $baseCharacter['gender']);
            
            // Генерируем описание и предысторию
            if ($useAI) {
                $aiContent = $this->generateWithAI($baseCharacter);
                $baseCharacter['description'] = $aiContent['description'];
                $baseCharacter['background'] = $aiContent['background'];
            } else {
                $baseCharacter['description'] = $this->generateFallbackDescription($baseCharacter);
                $baseCharacter['background'] = $this->generateFallbackBackground($baseCharacter);
            }
            
            // Добавляем дополнительную информацию
            $baseCharacter['generated_at'] = date('Y-m-d H:i:s');
            $baseCharacter['generator_version'] = '2.0';
            
            return $baseCharacter;
            
        } catch (Exception $e) {
            throw new Exception("Ошибка генерации полного персонажа: " . $e->getMessage());
        }
    }
}

// Обработка запросов
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['character'])) {
            throw new Exception('Неверные входные данные');
        }
        
        $baseCharacter = $input['character'];
        $useAI = $input['use_ai'] ?? true;
        
        $generator = new FullCharacterGenerator();
        $fullCharacter = $generator->generateFullCharacter($baseCharacter, $useAI);
        
        echo json_encode([
            'success' => true,
            'character' => $fullCharacter
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
