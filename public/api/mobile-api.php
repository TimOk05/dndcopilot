<?php
// Заголовки только если это HTTP запрос (не CLI)
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

require_once __DIR__ . '/../../config/config.php';

// Обработка preflight запросов (только для HTTP запросов)
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Неизвестное действие'];

    switch ($action) {
        case 'generate_character':
            $race = $_POST['race'] ?? '';
            $characterClass = $_POST['class'] ?? '';
            $level = (int)($_POST['level'] ?? 1);
            $alignment = $_POST['alignment'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $language = $_POST['language'] ?? 'ru';
            $use_ai = true; // AI всегда включен
            
            if (empty($race) || empty($characterClass)) {
                $response = ['success' => false, 'message' => 'Не указаны раса или класс'];
                break;
            }
            
            // Используем тот же CharacterService что и в ПК версии
            require_once __DIR__ . '/../../app/Services/CharacterService.php';
            $generator = new CharacterService();
            
            $params = [
                'race' => $race,
                'class' => $characterClass,
                'level' => $level,
                'alignment' => $alignment,
                'gender' => $gender,
                'language' => $language,
                'use_ai' => $use_ai ? 'on' : 'off'
            ];
            
            $result = $generator->generateCharacter($params);
            
            // Согласно политике NO_FALLBACK - показываем ошибку вместо fallback
            if (!$result['success']) {
                $response = [
                    'success' => false,
                    'error' => $result['error'] ?? 'Неизвестная ошибка генерации персонажа',
                    'message' => $result['message'] ?? 'Не удалось сгенерировать персонажа',
                    'details' => 'Проверьте подключение к интернету и настройки API'
                ];
            } else {
                $response = $result; // Возвращаем полный результат как в ПК версии
            }
            break;
            
        case 'generate_enemy':
            $threat_level = $_POST['threat_level'] ?? '';
            $count = (int)($_POST['count'] ?? 1);
            $enemy_type = $_POST['enemy_type'] ?? '';
            $environment = $_POST['environment'] ?? '';
            $use_ai = true; // AI всегда включен
            
            if (empty($threat_level)) {
                $response = ['success' => false, 'message' => 'Не указан уровень угрозы'];
                break;
            }
            
            // Используем тот же EnemyGenerator что и в ПК версии
            require_once __DIR__ . '/generate-enemies.php';
            $generator = new EnemyGenerator();
            
            $params = [
                'threat_level' => $threat_level,
                'count' => $count,
                'enemy_type' => $enemy_type,
                'environment' => $environment,
                'use_ai' => $use_ai ? 'on' : 'off'
            ];
            
            $result = $generator->generateEnemies($params);
            $response = $result; // Возвращаем полный результат как в ПК версии
            break;
            
        case 'ai_chat':
            $question = $_POST['question'] ?? '';
            $pdf_content = $_POST['pdf_content'] ?? '';
            $use_ai = true; // AI всегда включен
            
            if (empty($question)) {
                $response = ['success' => false, 'message' => 'Не указан вопрос'];
                break;
            }
            
            if (!$use_ai) {
                $response = ['success' => false, 'message' => 'AI чат требует включенного AI'];
                break;
            }
            
            // Используем тот же AI чат что и в ПК версии
            require_once __DIR__ . '/ai-chat.php';
            
            // Подготавливаем данные для AI чата
            $chatData = [
                'message' => $question,
                'pdf_content' => $pdf_content
            ];
            
            // Вызываем AI чат напрямую
            $aiResponse = processAiChat($chatData);
            $response = $aiResponse; // Возвращаем полный результат как в ПК версии
            break;
            
        case 'generate_potions':
            $rarity = $_POST['rarity'] ?? '';
            $type = $_POST['type'] ?? '';
            $count = (int)($_POST['count'] ?? 1);
            $language = $_POST['language'] ?? 'ru';
            $use_ai = true; // AI всегда включен
            
            // Используем тот же генератор зелий что и в ПК версии
            require_once __DIR__ . '/generate-potions.php';
            
            $params = [
                'rarity' => $rarity,
                'type' => $type,
                'count' => $count,
                'language' => $language,
                'use_ai' => $use_ai ? 'on' : 'off'
            ];
            
            $result = generatePotions($params);
            $response = $result; // Возвращаем полный результат как в ПК версии
            break;
            
        case 'generate_taverns':
            $count = (int)($_POST['count'] ?? 1);
            $use_ai = true; // AI всегда включен
            
            // Используем тот же генератор таверн что и в ПК версии
            require_once __DIR__ . '/../../app/Services/SimplifiedTavernGenerator.php';
            
            $generator = new SimplifiedTavernGenerator();
            $params = [
                'count' => $count,
                'use_ai' => $use_ai ? 'on' : 'off'
            ];
            
            $result = $generator->generateTavern($params);
            $response = $result; // Возвращаем полный результат как в ПК версии
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Неизвестное действие: ' . $action];
    }
} catch (Exception $e) {
    logMessage('ERROR', 'Mobile API error: ' . $e->getMessage());
    $response = ['success' => false, 'error' => $e->getMessage()];
}

// Выводим ответ
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
