<?php
header('Content-Type: application/json');

try {
    echo "Testing AI Service...\n";
    
    // Подключаем необходимые файлы
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/app/Services/ai-service.php';
    
    echo "✓ Files loaded\n";
    
    // Создаем AI сервис
    $ai_service = new AiService();
    echo "✓ AI Service created\n";
    
    // Тестируем простую генерацию
    echo "Testing text generation...\n";
    $result = $ai_service->generateText("Создай краткое описание персонажа D&D");
    
    echo json_encode([
        'success' => !isset($result['error']),
        'error' => $result['error'] ?? null,
        'message' => $result['message'] ?? null,
        'result' => $result['data'] ?? null
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
