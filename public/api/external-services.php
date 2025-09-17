<?php
// API для внешних сервисов
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Services/ExternalApiService.php';

// Обработка CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
    exit(0);
}

$service = new ExternalApiService();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'generate_names':
            $race = $_GET['race'] ?? $_POST['race'] ?? 'human';
            $gender = $_GET['gender'] ?? $_POST['gender'] ?? 'any';
            $count = (int)($_GET['count'] ?? $_POST['count'] ?? 1);
            
            $result = $service->generateCharacterNames($race, $gender, $count);
            break;
            
        case 'roll_dice':
            $dice_string = $_GET['dice'] ?? $_POST['dice'] ?? '1d20';
            
            $result = $service->rollDice($dice_string);
            break;
            
        case 'get_weather':
            $location = $_GET['location'] ?? $_POST['location'] ?? 'Moscow';
            
            $result = $service->getWeather($location);
            break;
            
        case 'translate':
            $text = $_GET['text'] ?? $_POST['text'] ?? '';
            $target_lang = $_GET['target'] ?? $_POST['target'] ?? 'ru';
            $source_lang = $_GET['source'] ?? $_POST['source'] ?? 'en';
            
            if (empty($text)) {
                throw new Exception('Текст для перевода не указан');
            }
            
            $result = $service->translateText($text, $target_lang, $source_lang);
            break;
            
        case 'generate_image':
            $prompt = $_GET['prompt'] ?? $_POST['prompt'] ?? '';
            $size = $_GET['size'] ?? $_POST['size'] ?? '512x512';
            $count = (int)($_GET['count'] ?? $_POST['count'] ?? 1);
            
            if (empty($prompt)) {
                throw new Exception('Промпт для генерации изображения не указан');
            }
            
            $result = $service->generateImage($prompt, $size, $count);
            break;
            
        case 'get_spell':
            $spell_name = $_GET['spell'] ?? $_POST['spell'] ?? '';
            
            if (empty($spell_name)) {
                throw new Exception('Название заклинания не указано');
            }
            
            $result = $service->getSpellInfo($spell_name);
            break;
            
        case 'get_monster':
            $monster_name = $_GET['monster'] ?? $_POST['monster'] ?? '';
            
            if (empty($monster_name)) {
                throw new Exception('Название монстра не указано');
            }
            
            $result = $service->getMonsterInfo($monster_name);
            break;
            
        case 'generate_quest':
            $quest_type = $_GET['type'] ?? $_POST['type'] ?? 'adventure';
            $difficulty = $_GET['difficulty'] ?? $_POST['difficulty'] ?? 'medium';
            $theme = $_GET['theme'] ?? $_POST['theme'] ?? 'fantasy';
            
            $result = $service->generateQuest($quest_type, $difficulty, $theme);
            break;
            
        case 'generate_lore':
            $lore_type = $_GET['type'] ?? $_POST['type'] ?? 'location';
            $setting = $_GET['setting'] ?? $_POST['setting'] ?? 'medieval';
            $mood = $_GET['mood'] ?? $_POST['mood'] ?? 'mysterious';
            
            $result = $service->generateLore($lore_type, $setting, $mood);
            break;
            
        case 'test':
            // Тестовый endpoint для проверки всех сервисов
            $tests = [];
            
            // Тест генерации имен
            $name_test = $service->generateCharacterNames('human', 'any', 3);
            $tests['names'] = $name_test;
            
            // Тест броска костей
            $dice_test = $service->rollDice('2d6+3');
            $tests['dice'] = $dice_test;
            
            // Тест погоды (если настроен API ключ)
            if (!empty(getApiKey('openweathermap'))) {
                $weather_test = $service->getWeather('Moscow');
                $tests['weather'] = $weather_test;
            } else {
                $tests['weather'] = ['success' => false, 'error' => 'API ключ не настроен'];
            }
            
            // Тест генерации квеста
            $quest_test = $service->generateQuest('adventure', 'medium', 'fantasy');
            $tests['quest'] = $quest_test;
            
            // Тест генерации лора
            $lore_test = $service->generateLore('location', 'medieval', 'mysterious');
            $tests['lore'] = $lore_test;
            
            $result = [
                'success' => true,
                'message' => 'Тест внешних сервисов завершен',
                'tests' => $tests,
                'timestamp' => time()
            ];
            break;
            
        default:
            throw new Exception('Неизвестное действие: ' . $action);
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    logMessage('ERROR', "External API ошибка: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'action' => $action
    ], JSON_UNESCAPED_UNICODE);
}
?>
