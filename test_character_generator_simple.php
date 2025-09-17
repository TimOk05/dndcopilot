<?php
header('Content-Type: application/json');

try {
    echo "Testing CharacterGeneratorV4...\n";
    
    // Подключаем все необходимые файлы
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/app/Services/dnd-api-service.php';
    require_once __DIR__ . '/app/Services/ai-service.php';
    require_once __DIR__ . '/app/Services/language-service.php';
    require_once __DIR__ . '/public/api/generate-characters.php';
    
    echo "✓ All files loaded\n";
    
    // Создаем генератор
    $generator = new CharacterGeneratorV4();
    echo "✓ Generator created\n";
    
    // Тестируем с минимальными параметрами
    $params = [
        'race' => 'human',
        'class' => 'fighter',
        'level' => 1,
        'alignment' => 'neutral',
        'gender' => 'random'
    ];
    
    echo "Testing generation with params: " . json_encode($params) . "\n";
    
    $result = $generator->generateCharacter($params);
    
    echo json_encode([
        'success' => $result['success'] ?? false,
        'error' => $result['error'] ?? null,
        'message' => $result['message'] ?? null,
        'character_name' => $result['character']['name'] ?? null
    ], JSON_UNESCAPED_UNICODE);
    
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
