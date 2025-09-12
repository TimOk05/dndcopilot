<?php
// Заголовки для HTTP запросов
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Services/ai-service.php';
require_once __DIR__ . '/../../app/Services/SimplifiedTavernGenerator.php';

try {
    $generator = new SimplifiedTavernGenerator();
        $result = $generator->generateTavern($_POST);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
    http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
}
?>