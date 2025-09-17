<?php
header('Content-Type: application/json');

try {
    echo "Testing step by step...\n";
    
    // Шаг 1: Проверяем config
    echo "Step 1: Loading config...\n";
    require_once __DIR__ . '/config/config.php';
    echo "✓ Config loaded\n";
    
    // Шаг 2: Тестируем DndApiService
    echo "Step 2: Testing DndApiService...\n";
    require_once __DIR__ . '/app/Services/dnd-api-service.php';
    $dnd_api = new DndApiService();
    echo "✓ DndApiService created\n";
    
    // Шаг 3: Тестируем LanguageService
    echo "Step 3: Testing LanguageService...\n";
    require_once __DIR__ . '/app/Services/language-service.php';
    $language_service = new LanguageService();
    echo "✓ LanguageService created\n";
    
    // Шаг 4: Тестируем AiService (последний, так как может быть проблемным)
    echo "Step 4: Testing AiService...\n";
    require_once __DIR__ . '/app/Services/ai-service.php';
    $ai_service = new AiService();
    echo "✓ AiService created\n";
    
    echo json_encode([
        'success' => true,
        'message' => 'All services created successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
