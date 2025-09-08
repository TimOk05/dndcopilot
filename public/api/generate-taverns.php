<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Services/ai-service.php';
require_once __DIR__ . '/../../app/Services/SimplifiedTavernGenerator.php';

// Обработка запроса
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $generator = new SimplifiedTavernGenerator();
        $result = $generator->generateTavern($_POST);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} elseif (isset($_SERVER['REQUEST_METHOD'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Только POST запросы поддерживаются'
    ], JSON_UNESCAPED_UNICODE);
}
?>