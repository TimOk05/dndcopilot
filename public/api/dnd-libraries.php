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

try {
    switch ($action) {
        case 'get_comprehensive_spell':
            $spell_name = $_GET['spell'] ?? $_POST['spell'] ?? '';
            
            if (empty($spell_name)) {
                throw new Exception('Название заклинания не указано');
            }
            
            // Получаем информацию из всех доступных источников
            $spell_data = [];
            $sources_used = [];
            
            // Пробуем D&D API сервис (пока нет метода getSpellData, используем внешние сервисы)
            try {
                // Временно пропускаем D&D API для заклинаний
                logMessage('INFO', "D&D API для заклинаний пока не реализован, используем внешние сервисы");
            } catch (Exception $e) {
                logMessage('WARNING', "D&D API не доступен для заклинания {$spell_name}: " . $e->getMessage());
            }
            
            // Пробуем внешние сервисы
            try {
                $external_spell = $external_service->getSpellInfo($spell_name);
                if ($external_spell && $external_spell['success']) {
                    $spell_data['external_api'] = $external_spell;
                    $sources_used[] = 'external_api';
                }
            } catch (Exception $e) {
                logMessage('WARNING', "Внешние API не доступны для заклинания {$spell_name}: " . $e->getMessage());
            }
            
            // Если нет данных, генерируем через AI
            if (empty($spell_data)) {
                $ai_prompt = "Создай подробное описание заклинания D&D 5e '{$spell_name}'. Включи: уровень, школу магии, время накладывания, дистанцию, компоненты, длительность, описание эффекта, классы которые могут использовать это заклинание.";
                $ai_response = $ai_service->generateText($ai_prompt);
                
                if (!isset($ai_response['error'])) {
                    $spell_data['ai_generated'] = [
                        'name' => $spell_name,
                        'description' => $ai_response['text'] ?? $ai_response,
                        'source' => 'ai_generation'
                    ];
                    $sources_used[] = 'ai_generation';
                }
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
            
            // Пробуем D&D API сервис (пока нет метода getMonsterData, используем внешние сервисы)
            try {
                // Временно пропускаем D&D API для монстров
                logMessage('INFO', "D&D API для монстров пока не реализован, используем внешние сервисы");
            } catch (Exception $e) {
                logMessage('WARNING', "D&D API не доступен для монстра {$monster_name}: " . $e->getMessage());
            }
            
            // Пробуем внешние сервисы
            try {
                $external_monster = $external_service->getMonsterInfo($monster_name);
                if ($external_monster && $external_monster['success']) {
                    $monster_data['external_api'] = $external_monster;
                    $sources_used[] = 'external_api';
                }
            } catch (Exception $e) {
                logMessage('WARNING', "Внешние API не доступны для монстра {$monster_name}: " . $e->getMessage());
            }
            
            // Если нет данных, генерируем через AI
            if (empty($monster_data)) {
                $ai_prompt = "Создай подробное описание монстра D&D 5e '{$monster_name}'. Включи: размер, тип, выравнивание, AC, HP, скорость, характеристики (STR, DEX, CON, INT, WIS, CHA), навыки, чувства, языки, CR, особенности и действия.";
                $ai_response = $ai_service->generateText($ai_prompt);
                
                if (!isset($ai_response['error'])) {
                    $monster_data['ai_generated'] = [
                        'name' => $monster_name,
                        'description' => $ai_response['text'] ?? $ai_response,
                        'source' => 'ai_generation'
                    ];
                    $sources_used[] = 'ai_generation';
                }
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
            
            // Генерируем через AI
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
            }
            
            // Дополняем через внешние сервисы
            if ($content_type === 'quest') {
                try {
                    $quest_data = $external_service->generateQuest($content_type, $complexity, $theme);
                    if ($quest_data && $quest_data['success']) {
                        $content_data['external_api'] = $quest_data;
                        $sources_used[] = 'external_api';
                    }
                } catch (Exception $e) {
                    logMessage('WARNING', "Внешние API не доступны для генерации квеста: " . $e->getMessage());
                }
            } elseif ($content_type === 'lore') {
                try {
                    $lore_data = $external_service->generateLore($content_type, $theme, $complexity);
                    if ($lore_data && $lore_data['success']) {
                        $content_data['external_api'] = $lore_data;
                        $sources_used[] = 'external_api';
                    }
                } catch (Exception $e) {
                    logMessage('WARNING', "Внешние API не доступны для генерации лора: " . $e->getMessage());
                }
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
            
            // Тест внешних сервисов
            try {
                $external_test = $external_service->generateCharacterNames('human', 'any', 1);
                $tests['external_api'] = [
                    'success' => $external_test['success'] ?? false,
                    'names_generated' => isset($external_test['names']) ? count($external_test['names']) : 0
                ];
            } catch (Exception $e) {
                $tests['external_api'] = ['success' => false, 'error' => $e->getMessage()];
            }
            
            $result = [
                'success' => true,
                'message' => 'Тест всех источников завершен',
                'tests' => $tests,
                'timestamp' => time()
            ];
            
            break;
            
        default:
            throw new Exception('Неизвестное действие: ' . $action);
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
?>
