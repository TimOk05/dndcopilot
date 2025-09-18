<?php
/**
 * D&D Libraries API - Расширенный API для работы с различными D&D библиотеками
 * Поддерживает множественные источники данных для максимального охвата информации
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Services/ExternalApiService.php';
require_once __DIR__ . '/../../app/Services/dnd-api-service.php';
require_once __DIR__ . '/../../app/Services/ai-service.php';

// Обработка CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
    exit(0);
}

$external_service = new ExternalApiService();
$dnd_service = new DndApiService();
$ai_service = new AiService();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$type = $_GET['type'] ?? $_POST['type'] ?? '';

try {
    switch ($action) {
        default:
            // Обработка запросов типа type=races, type=classes, type=backgrounds
            if (!empty($type)) {
                switch ($type) {
                    case 'races':
                        $result = getRacesData($_GET['race'] ?? '');
                        break;
                    case 'classes':
                        $result = getClassesData();
                        break;
                    case 'backgrounds':
                        $result = getBackgroundsData();
                        break;
                    case 'spells':
                        $result = getSpellsData($_GET['class'] ?? '', $_GET['level'] ?? '');
                        break;
                    case 'equipment':
                        $result = getEquipmentData($_GET['category'] ?? '');
                        break;
                    default:
                        throw new Exception('Неизвестный тип данных: ' . $type);
                }
            } else {
                throw new Exception('Не указан тип данных');
            }
            break;
        case 'get_comprehensive_spell':
            $spell_name = $_GET['spell'] ?? $_POST['spell'] ?? '';
            
            if (empty($spell_name)) {
                throw new Exception('Название заклинания не указано');
            }
            
            // Получаем информацию из всех доступных источников
            $spell_data = [];
            $sources_used = [];
            
            // Получаем данные заклинания только из D&D API
            $spell_data = $dnd_service->getSpellData($spell_name);
            if ($spell_data && !isset($spell_data['error'])) {
                $sources_used[] = 'dnd_api';
            } else {
                throw new Exception("D&D API недоступен для получения заклинания: {$spell_name}");
            }
            
            $result = [
                'success' => !empty($spell_data),
                'spell_name' => $spell_name,
                'data' => $spell_data,
                'sources_used' => $sources_used,
                'total_sources' => count($sources_used)
            ];
            
            if (empty($spell_data)) {
                $result['error'] = 'Заклинание не найдено ни в одном доступном источнике';
            }
            
            break;
            
        case 'get_comprehensive_monster':
            $monster_name = $_GET['monster'] ?? $_POST['monster'] ?? '';
            
            if (empty($monster_name)) {
                throw new Exception('Название монстра не указано');
            }
            
            // Получаем информацию из всех доступных источников
            $monster_data = [];
            $sources_used = [];
            
            // Получаем данные монстра только из D&D API
            $monster_data = $dnd_service->getMonsterData($monster_name);
            if ($monster_data && !isset($monster_data['error'])) {
                $sources_used[] = 'dnd_api';
            } else {
                throw new Exception("D&D API недоступен для получения монстра: {$monster_name}");
            }
            
            $result = [
                'success' => !empty($monster_data),
                'monster_name' => $monster_name,
                'data' => $monster_data,
                'sources_used' => $sources_used,
                'total_sources' => count($sources_used)
            ];
            
            if (empty($monster_data)) {
                $result['error'] = 'Монстр не найден ни в одном доступном источнике';
            }
            
            break;
            
        case 'generate_comprehensive_content':
            $content_type = $_GET['type'] ?? $_POST['type'] ?? 'adventure';
            $theme = $_GET['theme'] ?? $_POST['theme'] ?? 'fantasy';
            $complexity = $_GET['complexity'] ?? $_POST['complexity'] ?? 'medium';
            
            $content_data = [];
            $sources_used = [];
            
            // Генерируем контент только через AI
            $ai_prompt = "Создай {$content_type} для D&D 5e с темой '{$theme}' и сложностью '{$complexity}'. Сделай контент подробным, интересным и готовым к использованию в игре.";
            $ai_response = $ai_service->generateText($ai_prompt);
            
            if (!isset($ai_response['error'])) {
                $content_data['ai_generated'] = [
                    'type' => $content_type,
                    'theme' => $theme,
                    'complexity' => $complexity,
                    'content' => $ai_response['text'] ?? $ai_response,
                    'source' => 'ai_generation'
                ];
                $sources_used[] = 'ai_generation';
            } else {
                throw new Exception('AI недоступен для генерации контента');
            }
            
            $result = [
                'success' => !empty($content_data),
                'content_type' => $content_type,
                'theme' => $theme,
                'complexity' => $complexity,
                'data' => $content_data,
                'sources_used' => $sources_used,
                'total_sources' => count($sources_used)
            ];
            
            if (empty($content_data)) {
                $result['error'] = 'Не удалось сгенерировать контент ни одним доступным способом';
            }
            
            break;
            
        case 'get_available_sources':
            // Возвращаем список всех доступных источников
            $sources = [
                'dnd_api' => [
                    'name' => 'D&D 5e API',
                    'url' => 'https://www.dnd5eapi.co/api',
                    'status' => 'available',
                    'description' => 'Официальный API D&D 5e с данными о монстрах, заклинаниях, классах и расах'
                ],
                'open5e' => [
                    'name' => 'Open5e API',
                    'url' => 'https://api.open5e.com',
                    'status' => 'available',
                    'description' => 'Открытый API с данными D&D 5e'
                ],
                'ai_generation' => [
                    'name' => 'AI Generation',
                    'status' => 'available',
                    'description' => 'Генерация контента через DeepSeek AI'
                ],
                'external_api' => [
                    'name' => 'External APIs',
                    'status' => 'available',
                    'description' => 'Дополнительные внешние API для расширенной функциональности'
                ]
            ];
            
            $result = [
                'success' => true,
                'sources' => $sources,
                'total_sources' => count($sources)
            ];
            
            break;
            
        case 'test_all_sources':
            // Тестируем все доступные источники
            $tests = [];
            
            // Тест D&D API
            try {
                // Тестируем доступность D&D API через простой запрос
                $dnd_test = $dnd_service->getRaceData('human');
                $tests['dnd_api'] = [
                    'success' => !empty($dnd_test) && !isset($dnd_test['error']),
                    'test_method' => 'getRaceData'
                ];
            } catch (Exception $e) {
                $tests['dnd_api'] = ['success' => false, 'error' => $e->getMessage()];
            }
            
            // Тест AI генерации
            try {
                $ai_test = $ai_service->generateText('Тестовое сообщение для проверки AI');
                $tests['ai_generation'] = [
                    'success' => !isset($ai_test['error']),
                    'response_length' => isset($ai_test['text']) ? strlen($ai_test['text']) : 0
                ];
            } catch (Exception $e) {
                $tests['ai_generation'] = ['success' => false, 'error' => $e->getMessage()];
            }
            
            
            $result = [
                'success' => true,
                'message' => 'Тест всех источников завершен',
                'tests' => $tests,
                'timestamp' => time()
            ];
            
            break;
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    logMessage('ERROR', "D&D Libraries API ошибка: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'action' => $action
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Получение данных о расах
 */
function getRacesData($specific_race = '') {
    global $dnd_service, $external_service, $ai_service;
    
    $races_data = [];
    $sources_used = [];
    
    // Получаем расы только из D&D API
    if (empty($specific_race)) {
        $races_data = $dnd_service->getRacesList();
        if ($races_data && !isset($races_data['error'])) {
            $sources_used[] = 'dnd_api';
        } else {
            throw new Exception('D&D API недоступен для получения списка рас');
        }
    } else {
        $races_data = $dnd_service->getRaceData($specific_race);
        if ($races_data && !isset($races_data['error'])) {
            $sources_used[] = 'dnd_api';
        } else {
            throw new Exception("D&D API недоступен для получения данных расы: {$specific_race}");
        }
    }
    
    return [
        'success' => !empty($races_data),
        'races' => $races_data,
        'sources_used' => $sources_used,
        'total_sources' => count($sources_used)
    ];
}

/**
 * Получение данных о классах
 */
function getClassesData() {
    global $dnd_service, $external_service, $ai_service;
    
    $classes_data = [];
    $sources_used = [];
    
    // Получаем классы только из D&D API
    $classes_data = $dnd_service->getClassesList();
    if ($classes_data && !isset($classes_data['error'])) {
        $sources_used[] = 'dnd_api';
    } else {
        throw new Exception('D&D API недоступен для получения списка классов');
    }
    
    return [
        'success' => !empty($classes_data),
        'classes' => $classes_data,
        'sources_used' => $sources_used,
        'total_sources' => count($sources_used)
    ];
}

/**
 * Получение данных о происхождениях
 */
function getBackgroundsData() {
    global $dnd_service, $external_service, $ai_service;
    
    $backgrounds_data = [];
    $sources_used = [];
    
    // Получаем происхождения только из D&D API
    $backgrounds_data = $dnd_service->getBackgroundsList();
    if ($backgrounds_data && !isset($backgrounds_data['error'])) {
        $sources_used[] = 'dnd_api';
    } else {
        throw new Exception('D&D API недоступен для получения списка происхождений');
    }
    
    return [
        'success' => !empty($backgrounds_data),
        'backgrounds' => $backgrounds_data,
        'sources_used' => $sources_used,
        'total_sources' => count($sources_used)
    ];
}

/**
 * Получение данных о заклинаниях
 */
function getSpellsData($class = '', $level = '') {
    global $dnd_service, $external_service, $ai_service;
    
    $spells_data = [];
    $sources_used = [];
    
    // Получаем заклинания только из D&D API
    $spells_data = $dnd_service->getSpellsForClass($class, $level);
    if ($spells_data && !isset($spells_data['error'])) {
        $sources_used[] = 'dnd_api';
    } else {
        throw new Exception('D&D API недоступен для получения заклинаний');
    }
    
    return [
        'success' => !empty($spells_data),
        'spells' => $spells_data,
        'sources_used' => $sources_used,
        'total_sources' => count($sources_used)
    ];
}

/**
 * Получение данных об оборудовании
 */
function getEquipmentData($category = '') {
    global $dnd_service, $external_service, $ai_service;
    
    $equipment_data = [];
    $sources_used = [];
    
    // Получаем оборудование только из D&D API
    $equipment_data = $dnd_service->getEquipmentList($category);
    if ($equipment_data && !isset($equipment_data['error'])) {
        $sources_used[] = 'dnd_api';
    } else {
        throw new Exception('D&D API недоступен для получения оборудования');
    }
    
    return [
        'success' => !empty($equipment_data),
        'equipment' => $equipment_data,
        'sources_used' => $sources_used,
        'total_sources' => count($sources_used)
    ];
}

?>
